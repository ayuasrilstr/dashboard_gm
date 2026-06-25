import sys
import zipfile
import xml.etree.ElementTree as ET
from pathlib import Path


NS = {"a": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}


def read_shared_strings(zf):
    try:
        data = zf.read("xl/sharedStrings.xml")
    except KeyError:
        return []
    root = ET.fromstring(data)
    values = []
    for si in root.findall("a:si", NS):
        parts = [node.text or "" for node in si.findall(".//a:t", NS)]
        values.append("".join(parts))
    return values


def cell_value(cell, shared_strings):
    cell_type = cell.attrib.get("t", "")
    v = cell.find("a:v", NS)
    if cell_type == "s" and v is not None and v.text is not None:
        idx = int(v.text)
        return shared_strings[idx] if 0 <= idx < len(shared_strings) else ""
    if cell_type == "inlineStr":
        return "".join(node.text or "" for node in cell.findall(".//a:t", NS))
    return "" if v is None or v.text is None else v.text


def normalize(value):
    return str(value or "").strip()


def contains(value, needle):
    return needle.lower() in normalize(value).lower()


def is_heat_item(item_nr):
    return contains(item_nr, "HT")


def dashboard_match_32(row_data):
    return (
        is_heat_item(row_data.get("Item Nr") or row_data.get("Item No."))
        and contains(row_data.get("Udef 5"), "rpl")
        and contains(row_data.get("Udef 4"), "rpl")
    )


def dashboard_match_32a(row_data):
    return (
        is_heat_item(row_data.get("Item Nr") or row_data.get("Item No."))
        and contains(row_data.get("Text"), "csdb")
        and contains(row_data.get("Text"), "transfer")
        and contains(row_data.get("Text"), "bundle_receive")
        and not contains(row_data.get("Udef 6"), "sk")
        and not contains(row_data.get("W"), "sk")
    )


def is_return(row_data):
    text = normalize(row_data.get("Text")).lower()
    if not text:
        text = normalize(row_data.get("O")).lower()
    return "return" in text or "retutn" in text


def inspect(path, needle, verbose=False, dashboard=False):
    path = Path(path)
    with zipfile.ZipFile(path, "r") as zf:
        shared_strings = read_shared_strings(zf)
        sheet_name = None
        for candidate in zf.namelist():
            if candidate.startswith("xl/worksheets/sheet") and candidate.endswith(".xml"):
                sheet_name = candidate
                break
        if not sheet_name:
            return
        root = ET.fromstring(zf.read(sheet_name))
        headers = {}
        totals = {"all": 0.0, "return": 0.0, "non_return": 0.0}
        for row in root.findall(".//a:sheetData/a:row", NS):
            values = []
            hit = False
            row_data = {}
            row_num = int(row.attrib.get("r", "0") or 0)
            for cell in row.findall("a:c", NS):
                value = cell_value(cell, shared_strings)
                ref = cell.attrib.get("r", "")
                col = "".join(ch for ch in ref if ch.isalpha())
                row_data[col] = value
                values.append(f"{ref}={value}")
                if needle in value:
                    hit = True
            if row_num == 1:
                headers = {cell.attrib.get("r", "").rstrip("0123456789"): cell_value(cell, shared_strings) for cell in row.findall("a:c", NS)}
                continue
            if dashboard:
                header_row = {headers.get(col, col): val for col, val in row_data.items()}
                report_is_32a = "32a" in path.name.lower()
                report_is_32 = "32_" in path.name.lower() or path.name.lower().endswith("32.xlsx") or "32." in path.name.lower()
                matched = False
                if report_is_32a:
                    matched = dashboard_match_32a(header_row)
                elif report_is_32:
                    matched = dashboard_match_32(header_row)
                if not matched:
                    continue
                if needle and needle not in normalize(header_row.get("Cost Center") or header_row.get("Prod. Nr") or header_row.get("Udef 8")):
                    # keep if needle is found in any field for compatibility
                    if not any(needle in normalize(v) for v in header_row.values()):
                        continue
                if is_return(header_row) and "outflow" in path.name.lower():
                    continue
            if hit:
                qty_raw = row_data.get("M", "") or row_data.get("Qty", "") or row_data.get("QTY", "")
                try:
                    qty = float(qty_raw)
                except Exception:
                    qty = 0.0
                totals["all"] += qty
                text = str(row_data.get("O", "") or row_data.get("Text", "")).lower()
                if "return" in text or "retutn" in text:
                    totals["return"] += qty
                else:
                    totals["non_return"] += qty
                if verbose:
                    print(f"FILE: {path}")
                    print(" | ".join(values))
    print(f"SUMMARY: {path} all={totals['all']} return={totals['return']} non_return={totals['non_return']}")


def inspect_sheetinfo(path, max_rows=12):
    path = Path(path)
    with zipfile.ZipFile(path, "r") as zf:
        shared_strings = read_shared_strings(zf)
        sheets = [name for name in zf.namelist() if name.startswith("xl/worksheets/sheet") and name.endswith(".xml")]
        print(f"FILE: {path}")
        print("SHEETS:", ", ".join(sheets))
        if not sheets:
            return
        root = ET.fromstring(zf.read(sheets[0]))
        for row in root.findall(".//a:sheetData/a:row", NS)[:max_rows]:
            row_num = row.attrib.get("r", "")
            values = []
            for cell in row.findall("a:c", NS):
                ref = cell.attrib.get("r", "")
                values.append(f"{ref}={cell_value(cell, shared_strings)}")
            print(f"ROW {row_num}: " + " | ".join(values))


if __name__ == "__main__":
    verbose = "--verbose" in sys.argv
    dashboard = "--dashboard" in sys.argv
    sheetinfo = "--sheetinfo" in sys.argv
    args = [arg for arg in sys.argv[1:] if arg not in ("--verbose", "--dashboard")]
    args = [arg for arg in args if arg != "--sheetinfo"]
    if sheetinfo:
        for arg in args:
            inspect_sheetinfo(arg)
        raise SystemExit(0)
    needle = args[0]
    for arg in args[1:]:
        inspect(arg, needle, verbose=verbose, dashboard=dashboard)
