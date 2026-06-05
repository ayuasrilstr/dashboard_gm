from playwright.async_api import TimeoutError as PlaywrightTimeoutError
from playwright.async_api import async_playwright
from dotenv import load_dotenv
import argparse
import asyncio
import json
import msvcrt
import os
import shutil
import threading
import time
from calendar import monthrange
from datetime import datetime, timedelta
from html import escape
from pathlib import Path

load_dotenv()

ROOT_DIR = Path(__file__).resolve().parent

USERNAME = os.getenv("ENGAGE_USERNAME")
PASSWORD = os.getenv("ENGAGE_PASSWORD")

LOGIN_URL = "http://192.168.8.59:88/csreport1.5/public/login"

REPORT_URL = "http://192.168.8.59:88/csreport1.5/public/wwaw/kundenspez/0000971826"

DOWNLOAD_PATH = ROOT_DIR / "downloads" / "engage_download.xlsx"
DOWNLOAD_DIR = Path(DOWNLOAD_PATH).parent
ARCHIVE_DIR = ROOT_DIR / "archive"
LOG_DIR = ROOT_DIR / "logs"
LOG_PATH = LOG_DIR / "scheduler.log"
LOCK_PATH = LOG_DIR / "scheduler.lock"
SCHEDULE_START_HOUR = 7
SCHEDULE_END_HOUR = 23
SCHEDULE_INTERVAL_HOURS = 2
ARCHIVE_ENABLED = os.getenv("ENGAGE_KEEP_ARCHIVE", "").strip().lower() in {"1", "true", "yes", "on"}
RUN_IN_PROGRESS = False
LOCK_FILE = None

if not USERNAME or not PASSWORD:
    raise RuntimeError("ENGAGE_USERNAME dan ENGAGE_PASSWORD harus diisi di file .env")

DOWNLOAD_DIR.mkdir(parents=True, exist_ok=True)
if ARCHIVE_ENABLED:
    ARCHIVE_DIR.mkdir(parents=True, exist_ok=True)
LOG_DIR.mkdir(parents=True, exist_ok=True)

conditions = [
    {"storage": "32", "direction": "1", "filename": "32_inflow.xlsx"},
    {"storage": "32a", "direction": "1", "filename": "32a_inflow.xlsx"},
    {"storage": "32a", "direction": "2", "filename": "32a_outflow.xlsx"},
]

REPORT_COLUMNS = [
    ("#", "number"),
    ("Date", "We_datum"),
    ("Storage Nr", "We_lagnr"),
    ("Location Nr", "We_lagfnr"),
    ("Item Nr", "We_artnr"),
    ("Item Name", "Art_name"),
    ("Item Name 2", "Art_name2"),
    ("Serial Nr", "We_sernr"),
    ("Address Nr", "We_adrnr"),
    ("Address Name", "Adr_name"),
    ("Storage 2", "We_lagnr2"),
    ("Location 2", "We_lagfnr2"),
    ("Qty", "We_stck"),
    ("Unit", "Art_me"),
    ("Text", "We_name"),
    ("Cost Center", "We_kstnr"),
    ("Prod. Nr", "We_prdnr"),
    ("Udef 1", "We_flds00"),
    ("Udef 2", "We_flds01"),
    ("Udef 3", "We_flds02"),
    ("Udef 4", "We_flds03"),
    ("Udef 5", "We_flds04"),
    ("Udef 6", "We_flds05"),
    ("Udef 7", "We_flds06"),
    ("Udef 8", "We_flds07"),
    ("Udef 9", "We_flds08"),
    ("Udef 10", "We_flds09"),
    ("User Creator", "We_bennr"),
]

MONTH_ABBR = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
]


async def has_csrf_error(page):
    content = await page.content()
    return (
        "Failed to refresh CSRF token" in content
        or "Something wrong in requested link: /refresh-csrf" in content
    )


async def open_report(page, retries=3):
    for attempt in range(1, retries + 1):
        await page.goto(REPORT_URL, wait_until="domcontentloaded")
        await page.wait_for_timeout(2000)

        if await has_csrf_error(page):
            print(f"CSRF token gagal refresh, coba ulang buka report ({attempt}/{retries})")
            await page.reload(wait_until="domcontentloaded")
            await page.wait_for_timeout(3000)
            continue

        try:
            await page.wait_for_selector("#warein_lagnrvon", timeout=30000)
            return
        except PlaywrightTimeoutError:
            if attempt == retries:
                raise
            print(f"Form report belum siap, coba ulang buka report ({attempt}/{retries})")
            await page.wait_for_timeout(3000)

    raise RuntimeError("Gagal membuka halaman report karena CSRF token tidak bisa refresh")


async def close_visible_message_dialog(page):
    dialog = page.locator("#qw_meldung_dialog.modal.show")
    if not await dialog.count():
        return ""

    try:
        if not await dialog.first.is_visible(timeout=1000):
            return ""
    except PlaywrightTimeoutError:
        return ""

    message = normalize_dialog_text(await dialog.first.inner_text())
    log(f"Dialog report muncul: {message}")

    buttons = dialog.locator("button")
    for index in range(await buttons.count()):
        button = buttons.nth(index)
        if await button.is_visible():
            await button.click()
            await page.wait_for_timeout(1000)
            return message

    await page.keyboard.press("Escape")
    await page.wait_for_timeout(1000)
    return message


async def set_input_value(page, selector, value):
    if await page.locator(selector).count() == 0:
        return False

    await page.locator(selector).evaluate(
        """(element, inputValue) => {
            element.value = inputValue;
            element.dispatchEvent(new Event('input', { bubbles: true }));
            element.dispatchEvent(new Event('change', { bubbles: true }));
            element.dispatchEvent(new Event('blur', { bubbles: true }));
        }""",
        value,
    )
    return True


async def set_date_filter(page, date_from, date_to):
    from_set = await set_input_value(page, "#warein_datvon", date_from)
    to_set = await set_input_value(page, "#warein_datbis", date_to)

    if not from_set or not to_set:
        raise RuntimeError("Field tanggal warein_datvon/warein_datbis tidak ditemukan di halaman report")


async def wait_for_loading_done(page, timeout=600000):
    try:
        await page.wait_for_selector("#wwloading.modal.show", state="hidden", timeout=timeout)
    except PlaywrightTimeoutError:
        log("Loading report masih muncul terlalu lama, proses dilanjutkan dengan hati-hati")


async def get_displayed_row_count(page):
    return await page.locator("#IDD_LISTE2 tr").count()


async def click_display_and_get_row_count(page):
    async with page.expect_response(
        lambda response: "khtx/warein/fillscreen" in response.url,
        timeout=600000,
    ) as response_info:
        await page.click("#IDD_DISPLAY")

    response = await response_info.value
    body = await response.text()
    row_count = count_response_rows(body)

    await page.wait_for_timeout(1000)
    await wait_for_loading_done(page)

    return row_count


def count_response_rows(body):
    try:
        payload = json.loads(body)
    except json.JSONDecodeError:
        return body.count("<tr")

    data = payload.get("data")
    if isinstance(data, list):
        return len(data)

    if isinstance(data, str):
        return data.count("<tr")

    return 0


def normalize_dialog_text(value):
    return " ".join(value.split())


def is_date_filter_error(message):
    lowered = message.lower()
    return "date from must be filled" in lowered or "date is not in correct format" in lowered


def log(message):
    log_text = f"[{datetime.now():%Y-%m-%d %H:%M:%S}] {message}"
    print(log_text)
    with LOG_PATH.open("a", encoding="utf-8") as log_file:
        log_file.write(log_text + "\n")


def acquire_process_lock():
    global LOCK_FILE

    LOCK_FILE = LOCK_PATH.open("a+")
    try:
        LOCK_FILE.seek(0)
        msvcrt.locking(LOCK_FILE.fileno(), msvcrt.LK_NBLCK, 1)
    except OSError:
        LOCK_FILE.close()
        LOCK_FILE = None
        return False

    return True


def release_process_lock():
    global LOCK_FILE

    if LOCK_FILE is None:
        return

    try:
        LOCK_FILE.seek(0)
        msvcrt.locking(LOCK_FILE.fileno(), msvcrt.LK_UNLCK, 1)
    finally:
        LOCK_FILE.close()
        LOCK_FILE = None


def add_months(value, months):
    month_index = value.month - 1 + months
    year = value.year + month_index // 12
    month = month_index % 12 + 1
    day = min(value.day, monthrange(year, month)[1])
    return value.replace(year=year, month=month, day=day)


def format_report_date(value):
    return f"{value:%Y-%m-%d}"


def get_month_periods(reference_date=None):
    if reference_date is None:
        # Default to last 10 days (including today)
        date_to = datetime.now()
        date_from = date_to - timedelta(days=9)
        return [
            {
                "key": "last-10-days",
                "label": f"Last 10 Days ({date_from:%d %b} - {date_to:%d %b})",
                "date_from": date_from,
                "date_to": date_to,
            }
        ]

    year = reference_date.year
    month = reference_date.month
    last_day = monthrange(year, month)[1]
    month_label = MONTH_ABBR[month - 1]

    return [
        {
            "key": f"{year}-{month:02d}",
            "label": f"{month_label} {year}",
            "date_from": datetime(year, month, 1),
            "date_to": datetime(year, month, last_day),
        }
    ]


def get_archive_path(download_path):
    if not ARCHIVE_ENABLED:
        return None

    timestamp = datetime.now()
    archive_dir = ARCHIVE_DIR / timestamp.strftime("%Y-%m-%d")

    if download_path.parent != DOWNLOAD_DIR:
        archive_dir = archive_dir / download_path.parent.name

    archive_dir.mkdir(parents=True, exist_ok=True)
    return archive_dir / f"{download_path.stem}_{timestamp:%H%M%S}{download_path.suffix}"


async def save_download(download, download_path):
    temp_path = download_path.with_name(f".{download_path.stem}.tmp{download_path.suffix}")
    archive_path = get_archive_path(download_path)

    download_path.parent.mkdir(parents=True, exist_ok=True)

    if temp_path.exists():
        temp_path.unlink()

    await download.save_as(temp_path)

    for attempt in range(1, 6):
        try:
            if archive_path:
                shutil.copy2(temp_path, archive_path)
                log(f"Arsip tersimpan: {archive_path}")
            os.replace(temp_path, download_path)
            return
        except PermissionError:
            if attempt == 5:
                raise PermissionError(
                    f"Tidak bisa menulis {download_path}. Tutup file Excel tersebut jika sedang dibuka."
                )
            await asyncio.sleep(2)


def build_report_html(rows):
    total_qty = sum(parse_float(row.get("We_stck")) for row in rows)
    colspan = len(REPORT_COLUMNS) - 1
    header_html = "".join(
        f'<th class="text-center bg-dark text-light align-middle">{escape(label)}</th>'
        for label, _ in REPORT_COLUMNS
    )
    body_html = []

    for number, row in enumerate(rows, start=1):
        cells = []
        for _, key in REPORT_COLUMNS:
            value = number if key == "number" else row.get(key, "")
            css_class = ' class="text-right"' if key == "We_stck" else ""
            cells.append(f"<td{css_class}>{escape(str(value if value is not None else ''))}</td>")
        body_html.append("<tr>" + "".join(cells) + "</tr>")

    return f"""<div class="mt-2" id="IDD_CONTENT_CONTAINER_ALLPAGE" style="display: none;">
        <div class="col-12 table-responsive mx-0 px-0" id="IDD_CONTENT_TABLE2"><div class="col-12 table-responsive mx-0 px-0" id="IDD_CONTENT_TABLE">
            <table class="table table-sm shadow-sm table-bordered" id="IDD_CONTENT_TABLE_TABLE">
                <thead>
                    <tr id="IDD_TOTAL2"><td colspan="{colspan}" class="text-right font-weight-bold">Total</td><td class="text-right font-weight-bold">{total_qty:.4f}</td></tr>
                    <tr id="IDD_THEAD">{header_html}</tr>
                </thead><tbody id="IDD_LISTE2">{''.join(body_html)}</tbody></table></div></div>
    </div>"""


def parse_float(value):
    try:
        return float(value)
    except (TypeError, ValueError):
        return 0.0


def save_report_rows(rows, download_path):
    temp_path = download_path.with_name(f".{download_path.stem}.tmp{download_path.suffix}")
    archive_path = get_archive_path(download_path)

    download_path.parent.mkdir(parents=True, exist_ok=True)

    if temp_path.exists():
        temp_path.unlink()

    temp_path.write_text(build_report_html(rows), encoding="utf-8")

    for attempt in range(1, 6):
        try:
            if archive_path:
                shutil.copy2(temp_path, archive_path)
                log(f"Arsip tersimpan: {archive_path}")
            os.replace(temp_path, download_path)
            return
        except PermissionError:
            if attempt == 5:
                raise PermissionError(
                    f"Tidak bisa menulis {download_path}. Tutup file Excel tersebut jika sedang dibuka."
                )
            time.sleep(2)


async def fetch_report_page(page, page_number, timeout_ms=600000):
    return await page.evaluate(
        """async ({ pageNumber, timeoutMs }) => {
            async function refreshToken() {
                const response = await fetch(csreporturl + 'refresh-csrf');
                const data = await response.json();
                document.querySelectorAll('input[name="_token"]').forEach((input) => {
                    input.value = data.csrf_token;
                });
                return data.csrf_token;
            }

            function valueOrZero(selector) {
                const element = document.querySelector(selector);
                if (!element || element.value === '') return '0';
                return element.value;
            }

            const token = await refreshToken();
            const params = new URLSearchParams({
                _token: token,
                datvon: document.querySelector('#warein_datvon').value,
                datbis: document.querySelector('#warein_datbis').value,
                warein_lagnrvon: valueOrZero('#warein_lagnrvon'),
                warein_lagnrbis: valueOrZero('#warein_lagnrbis'),
                warein_lagfnrvon: valueOrZero('#warein_lagfnrvon'),
                warein_lagfnrbis: valueOrZero('#warein_lagfnrbis'),
                warein_artnr: valueOrZero('#warein_artnr'),
                warein_artgnr: valueOrZero('#warein_artgnr'),
                warein_artname: valueOrZero('#warein_artname'),
                warein_sernr: valueOrZero('#warein_sernr'),
                warein_adrnr: valueOrZero('#warein_adrnr'),
                warein_adrname: valueOrZero('#warein_adrname'),
                warein_kstnr: valueOrZero('#warein_kstnr'),
                warein_prdnr: valueOrZero('#warein_prdnr'),
                warein_name: valueOrZero('#warein_name'),
                warein_flds00: valueOrZero('#warein_flds00'),
                warein_flds01: valueOrZero('#warein_flds01'),
                warein_flds02: valueOrZero('#warein_flds02'),
                warein_flds03: valueOrZero('#warein_flds03'),
                warein_flds04: valueOrZero('#warein_flds04'),
                warein_flds05: valueOrZero('#warein_flds05'),
                warein_flds06: valueOrZero('#warein_flds06'),
                warein_flds07: valueOrZero('#warein_flds07'),
                warein_flds08: valueOrZero('#warein_flds08'),
                warein_flds09: valueOrZero('#warein_flds09'),
                warein_direction: document.querySelector('#warein_direction').value,
                warein_bennr: valueOrZero('#warein_bennr'),
                ipage: String(pageNumber),
            });

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
            let response;
            try {
                response = await fetch(csreporturl + 'khtx/warein/fillscreen', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: params.toString(),
                    signal: controller.signal,
                });
            } finally {
                clearTimeout(timeoutId);
            }

            if (!response.ok) {
                throw new Error(`fillscreen gagal: ${response.status}`);
            }

            return await response.json();
        }""",
        {"pageNumber": page_number, "timeoutMs": timeout_ms},
    )


async def fetch_report_rows(page):
    first_page = await fetch_report_page(page, 1)
    total_pages = int(first_page.get("total_page") or 1)
    rows = first_page.get("data") if isinstance(first_page.get("data"), list) else []
    log(f"Fetch page 1/{total_pages}: total row sementara {len(rows)}")

    for page_number in range(2, total_pages + 1):
        page_data = await fetch_report_page(page, page_number)
        data = page_data.get("data") if isinstance(page_data.get("data"), list) else []
        rows.extend(data)
        if page_number % 10 == 0 or page_number == total_pages:
            log(f"Fetch page {page_number}/{total_pages}: total row sementara {len(rows)}")

    return rows


async def download_reports(reference_date=None, storage_filter=None, direction_filter=None):
    async with async_playwright() as p:

        browser = await p.chromium.launch(
            headless=True
        )

        context = await browser.new_context(
            accept_downloads=True
        )

        page = await context.new_page()
        page.set_default_timeout(600000)
        page.set_default_navigation_timeout(600000)

        # buka login
        await page.goto(LOGIN_URL)

        # isi login
        await page.fill("#email", USERNAME)
        await page.fill("#password", PASSWORD)

        # klik login
        await page.click("button[type='submit']")

        await page.wait_for_load_state("networkidle")
        await page.wait_for_timeout(3000)

        log("Login berhasil")

        # buka report
        await open_report(page)

        log("Halaman report berhasil dibuka")

        for period in get_month_periods(reference_date):
            date_from = format_report_date(period["date_from"])
            date_to = format_report_date(period["date_to"])

            log(f"Proses periode {period['label']}: {date_from} sampai {date_to}")

            for condition in conditions:

                storage = condition["storage"]
                direction = condition["direction"]
                if storage_filter and storage.lower() != storage_filter.lower():
                    continue
                if direction_filter and direction != direction_filter:
                    continue

                log(f"Proses download Storage={storage}, Direction={direction}, Periode={period['label']}")

                # reload halaman report setiap loop
                await open_report(page)

                # isi filter
                await page.fill("#warein_lagnrvon", storage)
                await set_date_filter(page, date_from, date_to)

                # pilih direction
                await page.select_option("#warein_direction", direction)

                # klik display
                row_count = await click_display_and_get_row_count(page)

                log("Display data diklik")

                dialog_message = await close_visible_message_dialog(page)
                if is_date_filter_error(dialog_message):
                    raise RuntimeError(f"Filter tanggal gagal: {dialog_message}")

                log(f"Jumlah row response: {row_count}")

                if await has_csrf_error(page):
                    log("CSRF error setelah klik Display, ulang proses filter")
                    await open_report(page)
                    await page.fill("#warein_lagnrvon", storage)
                    await set_date_filter(page, date_from, date_to)
                    await page.select_option("#warein_direction", direction)
                    row_count = await click_display_and_get_row_count(page)
                    dialog_message = await close_visible_message_dialog(page)
                    if is_date_filter_error(dialog_message):
                        raise RuntimeError(f"Filter tanggal gagal: {dialog_message}")
                    log(f"Jumlah row response setelah retry: {row_count}")

                if row_count == 0:
                    log(f"Download dilewati karena data kosong: Storage={storage}, Direction={direction}, Periode={period['label']}")
                    continue

                filename = condition["filename"]

                download_path = DOWNLOAD_DIR / filename

                rows = await fetch_report_rows(page)
                log(f"Jumlah row export: {len(rows)}")
                save_report_rows(rows, download_path)

                log(f"Download berhasil: {download_path}")

                await page.wait_for_timeout(3000)

                await asyncio.sleep(5)

        await browser.close()


def run_async_job(coro):
    try:
        asyncio.get_running_loop()
    except RuntimeError:
        return asyncio.run(coro)

    result = {}

    def target():
        try:
            result["value"] = asyncio.run(coro)
        except Exception as error:
            result["error"] = error

    thread = threading.Thread(target=target)
    thread.start()
    thread.join()

    if "error" in result:
        raise result["error"]

    return result.get("value")


def run_download_once(reference_date=None, storage_filter=None, direction_filter=None):
    global RUN_IN_PROGRESS

    if RUN_IN_PROGRESS:
        log("Download dilewati karena proses sebelumnya masih berjalan")
        return

    RUN_IN_PROGRESS = True
    try:
        log("Mulai download report")
        run_async_job(download_reports(reference_date, storage_filter, direction_filter))
        log("Selesai download report")
    except Exception as error:
        log(f"Download gagal: {error}")
    finally:
        RUN_IN_PROGRESS = False


def get_next_run_time():
    now = datetime.now()
    schedule_start = now.replace(hour=SCHEDULE_START_HOUR, minute=0, second=0, microsecond=0)
    schedule_end = now.replace(hour=SCHEDULE_END_HOUR, minute=0, second=0, microsecond=0)

    if now <= schedule_start:
        return schedule_start

    if now > schedule_end:
        tomorrow = now + timedelta(days=1)
        return tomorrow.replace(hour=SCHEDULE_START_HOUR, minute=0, second=0, microsecond=0)

    elapsed_seconds = (now - schedule_start).total_seconds()
    elapsed_slots = int(elapsed_seconds // (SCHEDULE_INTERVAL_HOURS * 3600))
    next_run = schedule_start + timedelta(hours=SCHEDULE_INTERVAL_HOURS * (elapsed_slots + 1))

    if next_run > schedule_end:
        tomorrow = now + timedelta(days=1)
        return tomorrow.replace(hour=SCHEDULE_START_HOUR, minute=0, second=0, microsecond=0)

    return next_run


def run_scheduler():
    if not acquire_process_lock():
        log("Scheduler sudah berjalan di proses lain. Instance ini dihentikan.")
        return

    log(
        f"Scheduler aktif. Download setiap {SCHEDULE_INTERVAL_HOURS} jam "
        f"dari {SCHEDULE_START_HOUR:02d}:00 sampai {SCHEDULE_END_HOUR:02d}:00."
    )

    try:
        now = datetime.now()
        if SCHEDULE_START_HOUR <= now.hour <= SCHEDULE_END_HOUR:
            run_download_once()

        while True:
            next_run = get_next_run_time()
            wait_seconds = max(0, (next_run - datetime.now()).total_seconds())
            log(f"Run berikutnya: {next_run:%Y-%m-%d %H:%M:%S}")
            time.sleep(wait_seconds)

            run_download_once()

            time.sleep(60)
    finally:
        release_process_lock()


def parse_reference_month(value):
    try:
        return datetime.strptime(value, "%Y-%m")
    except ValueError as error:
        raise argparse.ArgumentTypeError("Format bulan harus YYYY-MM, contoh: 2026-04") from error


def main():
    parser = argparse.ArgumentParser(description="Engage RPA downloader")
    parser.add_argument("--once", action="store_true", help="Jalankan download sekali lalu keluar")
    parser.add_argument(
        "--reference-month",
        type=parse_reference_month,
        help="Bulan download dalam format YYYY-MM. Contoh 2026-05 untuk data bulan Mei.",
    )
    parser.add_argument("--storage", help="Filter storage, contoh: 32a")
    parser.add_argument("--direction", help="Filter direction, contoh: 2 untuk outflow")
    args = parser.parse_args()

    if args.once:
        if not acquire_process_lock():
            log("Download sekali dilewati karena scheduler/proses lain masih berjalan.")
            return

        try:
            run_download_once(args.reference_month, args.storage, args.direction)
        finally:
            release_process_lock()
        return

    run_scheduler()


if __name__ == "__main__":
    main()
