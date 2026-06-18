import json
import math
import os
import re
import urllib.request
import zipfile
from datetime import datetime, timedelta
from pathlib import Path
from xml.sax.saxutils import escape


OUTPUT_DIR = Path("outputs") / "ready_to_load_mid_june"
OUTPUT_FILE = OUTPUT_DIR / "material_to_load_mid_june.xlsx"
API_URL = "http://localhost/dashboard_gm/index.php/dashboard_heat/api/status?delivery_count=4"


def excel_serial_to_date(value):
    try:
        serial = float(value)
    except Exception:
        return None
    if not math.isfinite(serial) or serial <= 0:
        return None
    base = datetime(1899, 12, 30)
    return base + timedelta(days=serial)


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


def col_letter(n):
    letters = ""
    while n:
        n, rem = divmod(n - 1, 26)
        letters = chr(65 + rem) + letters
    return letters


def cell_xml(ref, value, style=None):
    attrs = [f'r="{ref}"']
    if style is not None:
        attrs.append(f's="{style}"')
    if isinstance(value, (int, float)) and not isinstance(value, bool):
        attrs.append('t="n"')
        return f'<c {" ".join(attrs)}><v>{value}</v></c>'
    text = escape("" if value is None else str(value))
    return f'<c {" ".join(attrs)} t="inlineStr"><is><t>{text}</t></is></c>'


def row_xml(row_num, values, style_map=None):
    style_map = style_map or {}
    cells = []
    for idx, value in enumerate(values, start=1):
        ref = f"{col_letter(idx)}{row_num}"
        style = style_map.get(idx)
        cells.append(cell_xml(ref, value, style))
    return f'<row r="{row_num}">{"".join(cells)}</row>'


def build_sheet_xml(rows):
    xml_rows = []
    xml_rows.append(row_xml(1, ["Material To Load - MID June"], {1: 1}))
    xml_rows.append(row_xml(2, ["Source: dashboard_heat ready_period_details['MID June']", "Total Qty Ready: 1731"], {1: 0, 2: 0}))
    xml_rows.append("<row r=\"3\"/>")
    xml_rows.append(row_xml(4, ["Order", "Style", "Delivery", "Qty Ready", "Accessories Completed"], {1: 2, 2: 2, 3: 2, 4: 2, 5: 2}))

    for i, item in enumerate(rows, start=5):
        xml_rows.append(row_xml(
            i,
            [
                item["order"],
                item["style"],
                item["delivery"],
                item["qty"],
                item["accessories_completed"],
            ],
        ))

    last_row = 4 + len(rows)
    return f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <dimension ref="A1:E{last_row}"/>
  <sheetViews>
    <sheetView workbookViewId="0">
      <pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/>
      <selection pane="bottomLeft" activeCell="A5" sqref="A5"/>
    </sheetView>
  </sheetViews>
  <sheetFormatPr defaultRowHeight="20"/>
  <cols>
    <col min="1" max="1" width="18"/>
    <col min="2" max="2" width="22"/>
    <col min="3" max="3" width="16"/>
    <col min="4" max="4" width="12"/>
    <col min="5" max="5" width="22"/>
  </cols>
  <sheetData>
    {''.join(xml_rows)}
  </sheetData>
  <autoFilter ref="A4:E{last_row}"/>
  <mergeCells count="1">
    <mergeCell ref="A1:E1"/>
  </mergeCells>
</worksheet>'''


def build_workbook_xml():
    return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="MID June Detail" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>'''


def build_styles_xml():
    return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="3">
    <font>
      <sz val="11"/>
      <color theme="1"/>
      <name val="Calibri"/>
      <family val="2"/>
    </font>
    <font>
      <b/>
      <sz val="14"/>
      <color theme="1"/>
      <name val="Calibri"/>
      <family val="2"/>
    </font>
    <font>
      <b/>
      <color rgb="FFFFFFFF"/>
      <name val="Calibri"/>
      <family val="2"/>
    </font>
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
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="3">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
  </cellXfs>
</styleSheet>'''


def build_content_types_xml():
    return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>'''


def build_root_rels_xml():
    return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>'''


def build_workbook_rels_xml():
    return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>'''


def fetch_rows():
    with urllib.request.urlopen(API_URL, timeout=30) as response:
        payload = json.loads(response.read().decode("utf-8"))

    rows = payload["dashboard_data"].get("ready_period_details", {}).get("MID June", [])
    normalized = []
    for item in rows:
        normalized.append({
            "order": item.get("order", ""),
            "style": item.get("style", ""),
            "delivery": parse_delivery(item.get("delivery", "")),
            "qty": int(item.get("qty", 0) or 0),
            "accessories_completed": int(item.get("accessories_completed", 0) or 0),
        })

    normalized.sort(key=lambda r: (r["delivery"], r["order"]))
    return normalized


def build_xlsx(rows):
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    sheet_xml = build_sheet_xml(rows)
    files = {
        "[Content_Types].xml": build_content_types_xml(),
        "_rels/.rels": build_root_rels_xml(),
        "xl/workbook.xml": build_workbook_xml(),
        "xl/_rels/workbook.xml.rels": build_workbook_rels_xml(),
        "xl/styles.xml": build_styles_xml(),
        "xl/worksheets/sheet1.xml": sheet_xml,
    }

    with zipfile.ZipFile(OUTPUT_FILE, "w", compression=zipfile.ZIP_DEFLATED) as zf:
        for path, xml in files.items():
            zf.writestr(path, xml)


def verify(rows):
    if not OUTPUT_FILE.is_file():
        raise RuntimeError("Workbook output was not created")
    if len(rows) != 36:
        raise RuntimeError(f"Expected 36 rows, got {len(rows)}")
    total = sum(r["qty"] for r in rows)
    if total != 1731:
        raise RuntimeError(f"Expected total 1731, got {total}")
    with zipfile.ZipFile(OUTPUT_FILE, "r") as zf:
        sheet = zf.read("xl/worksheets/sheet1.xml").decode("utf-8")
        if "Material To Load - MID June" not in sheet or "Total Qty Ready: 1731" not in sheet:
            raise RuntimeError("Workbook XML missing expected title or total")


def main():
    rows = fetch_rows()
    build_xlsx(rows)
    verify(rows)
    print(str(OUTPUT_FILE.resolve()))


if __name__ == "__main__":
    main()
