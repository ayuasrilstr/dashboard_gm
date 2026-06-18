import json
import math
import re
import urllib.request
import zipfile
from collections import defaultdict
from datetime import datetime, timedelta
from pathlib import Path
from xml.sax.saxutils import escape
import xml.etree.ElementTree as ET


ROOT = Path(r"c:\xampp\htdocs\dashboard_gm")
OUTPUT_DIR = ROOT / "outputs" / "mid_june_ready_detail"
OUTPUT_FILE = OUTPUT_DIR / "mid_june_ready_253_detail.xlsx"
API_URL = "http://localhost/dashboard_gm/index.php/dashboard_heat/api/status?delivery_count=4"
def candidate_inflow_paths(name):
    paths = []
    download_dir = ROOT / "rpa" / "engage-rpa" / "downloads"
    archive_dir = ROOT / "rpa" / "engage-rpa" / "archive"
    paths.extend(download_dir.glob(f"{name}*.xlsx"))
    paths.extend(archive_dir.rglob(f"{name}*.xlsx"))
    return paths


def latest_valid_xlsx(paths):
    valid = []
    for path in paths:
        if path and path.is_file():
            valid.append(path)
    if not valid:
        return None
    valid.sort(key=lambda p: p.stat().st_mtime, reverse=True)
    return valid[0]


INFLOW_FILES = [
    latest_valid_xlsx(candidate_inflow_paths("32_inflow")),
    latest_valid_xlsx(candidate_inflow_paths("32a_inflow")),
]


def excel_serial_to_date(value):
    try:
        serial = float(value)
    except Exception:
        return None
    if not math.isfinite(serial) or serial <= 0:
        return None
    return datetime(1899, 12, 30) + timedelta(days=serial)


def parse_delivery(value):
    if value is None:
        return ""
    text = str(value).strip()
    if not text:
        return ""
    if re.fullmatch(r"\d+(\.\d+)?", text):
        dt = excel_serial_to_date(text)
        return dt.strftime("%d %b %Y") if dt else text
    for fmt in ("%Y-%m-%d %H:%M:%S.%f", "%Y-%m-%d %H:%M:%S", "%Y-%m-%d"):
        try:
            return datetime.strptime(text, fmt).strftime("%d %b %Y")
        except ValueError:
            pass
    return text


def parse_delivery_sort(value):
    text = str(value).strip()
    if re.fullmatch(r"\d+(\.\d+)?", text):
        dt = excel_serial_to_date(text)
        return dt if dt else datetime.max
    for fmt in ("%Y-%m-%d %H:%M:%S.%f", "%Y-%m-%d %H:%M:%S", "%Y-%m-%d"):
        try:
            return datetime.strptime(text, fmt)
        except ValueError:
            pass
    return datetime.max


def col_letter(n):
    letters = ""
    while n:
        n, rem = divmod(n - 1, 26)
        letters = chr(65 + rem) + letters
    return letters


def build_simple_xlsx(rows, output_path):
    def cell_xml(ref, value, style=None):
        attrs = [f'r="{ref}"']
        if style is not None:
            attrs.append(f's="{style}"')
        if isinstance(value, (int, float)) and not isinstance(value, bool):
            attrs.append('t="n"')
            return f'<c {" ".join(attrs)}><v>{value}</v></c>'
        return f'<c {" ".join(attrs)} t="inlineStr"><is><t>{escape("" if value is None else str(value))}</t></is></c>'

    def row_xml(row_num, values, style_map=None):
        style_map = style_map or {}
        cells = []
        for idx, value in enumerate(values, start=1):
            cells.append(cell_xml(f"{col_letter(idx)}{row_num}", value, style_map.get(idx)))
        return f'<row r="{row_num}">{"".join(cells)}</row>'

    xml_rows = []
    xml_rows.append(row_xml(1, ["Material To Load - MID June"], {1: 1}))
    xml_rows.append(row_xml(2, ["Total Qty Ready", 253, "Total Orders", len(rows)], {1: 0, 2: 0, 3: 0, 4: 0}))
    xml_rows.append("<row r=\"3\"/>")
    headers = ["No", "Order", "Style", "Delivery", "Qty Ready", "Accessories Completed", "Item Nr"]
    xml_rows.append(row_xml(4, headers, {i: 2 for i in range(1, len(headers) + 1)}))
    for idx, item in enumerate(rows, start=5):
        xml_rows.append(row_xml(
            idx,
            [
                idx - 4,
                item["order"],
                item["style"],
                item["delivery"],
                item["qty_ready"],
                item["accessories_completed"],
                item["item_nr"],
            ],
        ))

    last_row = 4 + len(rows)
    sheet_xml = f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <dimension ref="A1:G{last_row}"/>
  <sheetViews>
    <sheetView workbookViewId="0">
      <pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/>
      <selection pane="bottomLeft" activeCell="A5" sqref="A5"/>
    </sheetView>
  </sheetViews>
  <sheetFormatPr defaultRowHeight="20"/>
  <cols>
    <col min="1" max="1" width="7"/>
    <col min="2" max="2" width="18"/>
    <col min="3" max="3" width="24"/>
    <col min="4" max="4" width="16"/>
    <col min="5" max="5" width="12"/>
    <col min="6" max="6" width="20"/>
    <col min="7" max="7" width="40"/>
  </cols>
  <sheetData>
    {''.join(xml_rows)}
  </sheetData>
  <autoFilter ref="A4:G{last_row}"/>
  <mergeCells count="1">
    <mergeCell ref="A1:G1"/>
  </mergeCells>
</worksheet>'''

    workbook_xml = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="MID June Ready" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>'''

    styles_xml = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="3">
    <font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><sz val="14"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>
  </fonts>
  <fills count="3">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFD9E2EC"/></left>
      <right style="thin"><color rgb="FFD9E2EC"/></right>
      <top style="thin"><color rgb="FFD9E2EC"/></top>
      <bottom style="thin"><color rgb="FFD9E2EC"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="3">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
  </cellXfs>
</styleSheet>'''

    content_types = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>'''

    root_rels = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>'''

    workbook_rels = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>'''

    output_path.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(output_path, "w", compression=zipfile.ZIP_DEFLATED) as zf:
        zf.writestr("[Content_Types].xml", content_types)
        zf.writestr("_rels/.rels", root_rels)
        zf.writestr("xl/workbook.xml", workbook_xml)
        zf.writestr("xl/_rels/workbook.xml.rels", workbook_rels)
        zf.writestr("xl/styles.xml", styles_xml)
        zf.writestr("xl/worksheets/sheet1.xml", sheet_xml)


def read_xlsx_table(path):
    if not zipfile.is_zipfile(path):
        html = path.read_text(encoding="utf-8", errors="ignore")

        def strip_tags(text):
            text = re.sub(r"<br\s*/?>", "\n", text, flags=re.I)
            text = re.sub(r"<[^>]+>", "", text)
            return (
                text.replace("&amp;", "&")
                .replace("&lt;", "<")
                .replace("&gt;", ">")
                .replace("&quot;", '"')
                .replace("&#039;", "'")
                .strip()
            )

        head_match = re.search(r'<tr[^>]*id="IDD_THEAD"[^>]*>(.*?)</tr>', html, re.S | re.I)
        if not head_match:
            return []
        headers = [strip_tags(cell) for cell in re.findall(r"<th[^>]*>(.*?)</th>", head_match.group(1), re.S | re.I)]
        body_match = re.search(r'<tbody[^>]*id="IDD_LISTE2"[^>]*>(.*?)</tbody>', html, re.S | re.I)
        if not body_match:
            return []
        rows = []
        for row_html in re.findall(r"<tr[^>]*>(.*?)</tr>", body_match.group(1), re.S | re.I):
            cells = [strip_tags(cell) for cell in re.findall(r"<td[^>]*>(.*?)</td>", row_html, re.S | re.I)]
            if not cells:
                continue
            row = {}
            for idx, header in enumerate(headers):
                if idx < len(cells):
                    row[header] = cells[idx]
            rows.append(row)
        return rows

    ns = {"a": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}
    with zipfile.ZipFile(path) as zf:
        shared = []
        if "xl/sharedStrings.xml" in zf.namelist():
            root = ET.fromstring(zf.read("xl/sharedStrings.xml"))
            for si in root.findall("a:si", ns):
                shared.append("".join(t.text or "" for t in si.iterfind(".//a:t", ns)))

        sheet_name = "xl/worksheets/sheet1.xml"
        root = ET.fromstring(zf.read(sheet_name))
        rows = []
        for row_node in root.findall(".//a:row", ns):
            row = {}
            for c in row_node.findall("a:c", ns):
                ref = c.attrib.get("r", "")
                match = re.match(r"([A-Z]+)(\d+)", ref)
                if not match:
                    continue
                col = match.group(1)
                t = c.attrib.get("t")
                v = c.find("a:v", ns)
                val = ""
                if t == "s" and v is not None:
                    val = shared[int(v.text)]
                elif t == "inlineStr":
                    val = "".join(tt.text or "" for tt in c.iterfind(".//a:t", ns))
                elif v is not None:
                    val = v.text or ""
                row[col] = val
            if row:
                rows.append(row)
        return rows


def fetch_dashboard_rows():
    with urllib.request.urlopen(API_URL, timeout=30) as response:
        payload = json.loads(response.read().decode("utf-8"))
    rows = payload["dashboard_data"]["ready_period_details"]["MID June"]
    clean = []
    for item in rows:
        clean.append({
            "order": item.get("order", ""),
            "style": item.get("style", ""),
            "delivery": parse_delivery(item.get("delivery", "")),
            "delivery_sort": parse_delivery_sort(item.get("delivery", "")),
            "qty_ready": int(item.get("qty", 0) or 0),
            "accessories_completed": int(item.get("accessories_completed", 0) or 0),
        })
    clean.sort(key=lambda r: (r["delivery_sort"], r["order"]))
    return clean


def extract_item_numbers():
    orders_to_items = defaultdict(set)
    for path in INFLOW_FILES:
        if not path or not path.is_file():
            continue
        rows = read_xlsx_table(path)
        if not rows:
            continue
        if rows and "Item Nr" in rows[0]:
            data_rows = rows
            for row in data_rows:
                order = str(
                    row.get("Cost Center", "")
                    or row.get("Order", "")
                    or row.get("Prod. Nr", "")
                    or row.get("Udef 8", "")
                ).strip()
                item = str(row.get("Item Nr", "")).strip()
                qty = row.get("Qty", "")
                try:
                    qty_num = abs(float(qty))
                except Exception:
                    qty_num = 0
                if not order or not item or qty_num <= 0:
                    continue
                orders_to_items[order].add(item)
            continue

        header_row = None
        header_lookup = {}
        for idx, row in enumerate(rows):
            lookup = {str(value).strip().lower(): col for col, value in row.items() if str(value).strip()}
            if "item nr" in lookup and ("cost center" in lookup or "order" in lookup):
                header_row = idx
                header_lookup = lookup
                break
        if header_row is None:
            continue
        order_col = header_lookup.get("cost center") or header_lookup.get("order") or header_lookup.get("prod. nr") or header_lookup.get("udef 8")
        item_col = header_lookup.get("item nr")
        qty_col = header_lookup.get("qty")
        if not order_col or not item_col or not qty_col:
            continue
        for row in rows[header_row + 1:]:
            order = str(row.get(order_col, "")).strip()
            item = str(row.get(item_col, "")).strip()
            qty = row.get(qty_col, "")
            try:
                qty_num = abs(float(qty))
            except Exception:
                qty_num = 0
            if not order or not item or qty_num <= 0:
                continue
            orders_to_items[order].add(item)
    return orders_to_items


def main():
    rows = fetch_dashboard_rows()
    item_map = extract_item_numbers()
    for row in rows:
        items = sorted(item_map.get(row["order"], []))
        row["item_nr"] = ", ".join(items)
    build_simple_xlsx(rows, OUTPUT_FILE)
    if not OUTPUT_FILE.is_file():
        raise RuntimeError("XLSX output missing")
    if len(rows) != 22:
        raise RuntimeError(f"Expected 22 rows for MID June detail, got {len(rows)}")
    total = sum(r["qty_ready"] for r in rows)
    if total != 253:
        raise RuntimeError(f"Expected total ready 253, got {total}")
    print(str(OUTPUT_FILE.resolve()))


if __name__ == "__main__":
    main()
