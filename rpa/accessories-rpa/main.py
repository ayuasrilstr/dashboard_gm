from dotenv import load_dotenv
from pathlib import Path
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError
import argparse
import asyncio
import json
import os
from datetime import datetime


ROOT = Path(__file__).resolve().parent
DEFAULT_CONFIG = ROOT / "config.json"
DEFAULT_ENV = ROOT / ".env"


def load_env():
    if DEFAULT_ENV.exists():
        load_dotenv(DEFAULT_ENV)
    else:
        load_dotenv()


def load_config(path):
    with Path(path).open("r", encoding="utf-8-sig") as handle:
        return json.load(handle)


def log(message):
    log_dir = ROOT / "logs"
    log_dir.mkdir(parents=True, exist_ok=True)
    text = f"[{datetime.now():%Y-%m-%d %H:%M:%S}] {message}"
    print(text.encode("utf-8", errors="replace").decode("utf-8", errors="replace"))
    with (log_dir / "accessories-rpa.log").open("a", encoding="utf-8") as handle:
        handle.write(text + "\n")


def project_path(value):
    path = Path(str(value))
    return path if path.is_absolute() else ROOT / path


def format_filename(template):
    return datetime.now().strftime(template)


async def persist_download(download, target):
    temp_target = target.with_name(f".{target.stem}.{datetime.now():%Y%m%d%H%M%S%f}.tmp{target.suffix}")
    if temp_target.exists():
        temp_target.unlink()
    if target.exists():
        try:
            target.unlink()
        except PermissionError:
            pass

    await download.save_as(temp_target)

    for attempt in range(1, 6):
        try:
            os.replace(temp_target, target)
            return target
        except PermissionError:
            if attempt == 5:
                raise PermissionError(f"Tidak bisa menulis {target}. Tutup file Excel tersebut jika sedang dibuka.")
            await asyncio.sleep(2)


def env_value(name):
    value = os.getenv(name)
    if value is None:
        raise RuntimeError(f"Environment variable belum diset: {name}")
    return value


def date_value(which):
    today = datetime.now().date()
    if which == "month_start":
        return today.replace(day=1).isoformat()
    if which == "today":
        return today.isoformat()
    if which.startswith("days_back_"):
        try:
            days = int(which.split("_")[-1])
            from datetime import timedelta
            return (today - timedelta(days=days)).isoformat()
        except ValueError:
            raise ValueError(f"Format days_back tidak valid: {which}")

    raise ValueError(f"Pilihan tanggal tidak dikenal: {which}")


async def run_step(page, step):
    action = step.get("action")
    name = step.get("name", action)
    log(f"Step: {name}")

    if action == "goto":
        await page.goto(step["url"], wait_until=step.get("wait_until", "domcontentloaded"))
        return

    if action == "wait":
        await page.wait_for_timeout(int(float(step.get("seconds", 1)) * 1000))
        return

    if action == "wait_for_selector":
        await page.wait_for_selector(step["selector"], timeout=int(step.get("timeout_ms", 30000)))
        return

    if action == "fill":
        await page.fill(step["selector"], step.get("value", ""))
        return

    if action == "fill_env":
        await page.fill(step["selector"], env_value(step["env"]))
        return

    if action == "fill_date":
        await page.fill(step["selector"], date_value(step["which"]))
        return

    if action == "fill_date_offset":
        from datetime import timedelta
        days_back = int(step.get("days_back", 0))
        date_str = (datetime.now().date() - timedelta(days=days_back)).isoformat()
        await page.fill(step["selector"], date_str)
        return

    if action == "click":
        await page.click(
            step["selector"],
            timeout=int(step.get("timeout_ms", 30000)),
            force=bool(step.get("force", False)),
        )
        return

    if action == "press":
        await page.press(step["selector"], step["key"])
        return

    if action == "select_option":
        await page.select_option(step["selector"], value=str(step["value"]))
        return

    if action == "download":
        directory = project_path(step.get("directory", "downloads"))
        directory.mkdir(parents=True, exist_ok=True)
        filename = format_filename(step.get("filename", "CONTROLIST_%Y%m%d_%H%M%S.xlsx"))
        target = directory / filename

        async with page.expect_download(timeout=int(step.get("timeout_ms", 120000))) as download_info:
            await page.click(
                step["selector"],
                timeout=int(step.get("click_timeout_ms", 30000)),
                force=bool(step.get("force", False)),
            )

        download = await download_info.value
        await persist_download(download, target)
        log(f"Download tersimpan: {target}")
        return

    if action == "screenshot":
        directory = project_path(step.get("directory", "screenshots"))
        directory.mkdir(parents=True, exist_ok=True)
        filename = format_filename(step.get("filename", "screenshot_%Y%m%d_%H%M%S.png"))
        await page.screenshot(path=str(directory / filename), full_page=bool(step.get("full_page", True)))
        return

    raise ValueError(f"Action tidak dikenal: {action}")


async def run(config, headless=False):
    downloads = project_path(config.get("downloads_dir", "downloads"))
    downloads.mkdir(parents=True, exist_ok=True)

    async with async_playwright() as playwright:
        browser = await playwright.chromium.launch(headless=headless)
        context = await browser.new_context(accept_downloads=True)
        page = await context.new_page()

        try:
            for step in config.get("steps", []):
                await run_step(page, step)
        finally:
            await context.close()
            await browser.close()


def check_config(config):
    required = ["steps"]
    for key in required:
        if key not in config:
            raise RuntimeError(f"Config belum lengkap: {key}")

    if not isinstance(config["steps"], list):
        raise RuntimeError("Config steps harus berupa array.")


def main():
    parser = argparse.ArgumentParser(description="RPA download Accessories Controlist")
    parser.add_argument("--config", default=str(DEFAULT_CONFIG))
    parser.add_argument("--check-only", action="store_true")
    parser.add_argument("--headless", action="store_true")
    args = parser.parse_args()

    load_env()
    config = load_config(args.config)
    check_config(config)

    if args.check_only:
        log("Config valid. Mode check-only aktif, browser tidak dibuka.")
        return 0

    try:
        asyncio.run(run(config, headless=args.headless))
        log("RPA Accessories selesai.")
        return 0
    except PlaywrightTimeoutError as error:
        log(f"Timeout: {error}")
        return 1
    except Exception as error:
        log(f"RPA Accessories gagal: {error}")
        raise


if __name__ == "__main__":
    raise SystemExit(main())
