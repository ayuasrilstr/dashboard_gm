import argparse
import ctypes
import json
import logging
import os
import shutil
import sys
import time
from ctypes import wintypes
from datetime import datetime, timedelta
from pathlib import Path

try:
    from PIL import ImageGrab
except ImportError:
    ImageGrab = None


ROOT = Path(__file__).resolve().parent
DEFAULT_CONFIG = ROOT / "config.json"
DEFAULT_ENV = ROOT / ".env"

user32 = ctypes.WinDLL("user32", use_last_error=True)

EnumWindowsProc = ctypes.WINFUNCTYPE(wintypes.BOOL, wintypes.HWND, wintypes.LPARAM)

user32.EnumWindows.argtypes = [EnumWindowsProc, wintypes.LPARAM]
user32.EnumWindows.restype = wintypes.BOOL
user32.GetWindowTextLengthW.argtypes = [wintypes.HWND]
user32.GetWindowTextLengthW.restype = ctypes.c_int
user32.GetWindowTextW.argtypes = [wintypes.HWND, wintypes.LPWSTR, ctypes.c_int]
user32.GetWindowTextW.restype = ctypes.c_int
user32.IsWindowVisible.argtypes = [wintypes.HWND]
user32.IsWindowVisible.restype = wintypes.BOOL
user32.SetForegroundWindow.argtypes = [wintypes.HWND]
user32.SetForegroundWindow.restype = wintypes.BOOL
user32.ShowWindow.argtypes = [wintypes.HWND, ctypes.c_int]
user32.ShowWindow.restype = wintypes.BOOL
user32.keybd_event.argtypes = [wintypes.BYTE, wintypes.BYTE, wintypes.DWORD, ctypes.POINTER(ctypes.c_ulong)]
user32.mouse_event.argtypes = [wintypes.DWORD, wintypes.DWORD, wintypes.DWORD, wintypes.DWORD, ctypes.POINTER(ctypes.c_ulong)]
user32.SetCursorPos.argtypes = [ctypes.c_int, ctypes.c_int]
user32.SetCursorPos.restype = wintypes.BOOL
user32.GetSystemMetrics.argtypes = [ctypes.c_int]
user32.GetSystemMetrics.restype = ctypes.c_int
user32.OpenClipboard.argtypes = [wintypes.HWND]
user32.OpenClipboard.restype = wintypes.BOOL
user32.EmptyClipboard.restype = wintypes.BOOL
user32.SetClipboardData.argtypes = [wintypes.UINT, wintypes.HANDLE]
user32.SetClipboardData.restype = wintypes.HANDLE
user32.CloseClipboard.restype = wintypes.BOOL

kernel32 = ctypes.WinDLL("kernel32", use_last_error=True)
kernel32.GlobalAlloc.argtypes = [wintypes.UINT, ctypes.c_size_t]
kernel32.GlobalAlloc.restype = wintypes.HGLOBAL
kernel32.GlobalLock.argtypes = [wintypes.HGLOBAL]
kernel32.GlobalLock.restype = wintypes.LPVOID
kernel32.GlobalUnlock.argtypes = [wintypes.HGLOBAL]
kernel32.GlobalUnlock.restype = wintypes.BOOL

SW_RESTORE = 9
KEYEVENTF_KEYUP = 0x0002
MOUSEEVENTF_LEFTDOWN = 0x0002
MOUSEEVENTF_LEFTUP = 0x0004
SM_CXSCREEN = 0
SM_CYSCREEN = 1
GMEM_MOVEABLE = 0x0002
CF_UNICODETEXT = 13

VK = {
    "alt": 0x12,
    "backspace": 0x08,
    "ctrl": 0x11,
    "delete": 0x2E,
    "down": 0x28,
    "end": 0x23,
    "enter": 0x0D,
    "esc": 0x1B,
    "f1": 0x70,
    "f2": 0x71,
    "f3": 0x72,
    "f4": 0x73,
    "f5": 0x74,
    "f6": 0x75,
    "f7": 0x76,
    "f8": 0x77,
    "f9": 0x78,
    "f10": 0x79,
    "f11": 0x7A,
    "f12": 0x7B,
    "home": 0x24,
    "left": 0x25,
    "pagedown": 0x22,
    "pageup": 0x21,
    "right": 0x27,
    "shift": 0x10,
    "space": 0x20,
    "tab": 0x09,
    "up": 0x26,
}

for digit in "0123456789":
    VK[digit] = ord(digit)
for letter in "abcdefghijklmnopqrstuvwxyz":
    VK[letter] = ord(letter.upper())


def load_config(path: Path) -> dict:
    if not path.exists():
        raise FileNotFoundError(f"Config tidak ditemukan: {path}")
    with path.open("r", encoding="utf-8") as handle:
        return json.load(handle)


def load_env_file(path: Path) -> None:
    if not path.exists():
        return
    for line_number, raw_line in enumerate(path.read_text(encoding="utf-8").splitlines(), start=1):
        line = raw_line.strip()
        if not line or line.startswith("#"):
            continue
        if "=" not in line:
            raise ValueError(f"Format .env tidak valid di baris {line_number}: {raw_line}")
        name, value = line.split("=", 1)
        name = name.strip()
        value = value.strip().strip('"').strip("'")
        if not name:
            raise ValueError(f"Nama environment kosong di .env baris {line_number}")
        os.environ.setdefault(name, value)


def setup_logging() -> Path:
    log_dir = ROOT / "logs"
    log_dir.mkdir(exist_ok=True)
    log_path = log_dir / f"run-{datetime.now():%Y%m%d-%H%M%S}.log"
    logging.basicConfig(
        level=logging.INFO,
        format="%(asctime)s %(levelname)s %(message)s",
        handlers=[
            logging.FileHandler(log_path, encoding="utf-8"),
            logging.StreamHandler(sys.stdout),
        ],
    )
    return log_path


def window_title(hwnd: int) -> str:
    length = user32.GetWindowTextLengthW(hwnd)
    if length == 0:
        return ""
    buffer = ctypes.create_unicode_buffer(length + 1)
    user32.GetWindowTextW(hwnd, buffer, length + 1)
    return buffer.value


def visible_windows() -> list[tuple[int, str]]:
    result: list[tuple[int, str]] = []

    @EnumWindowsProc
    def callback(hwnd, _):
        if user32.IsWindowVisible(hwnd):
            title = window_title(hwnd).strip()
            if title:
                result.append((int(hwnd), title))
        return True

    user32.EnumWindows(callback, 0)
    return result


def title_matches(title: str, title_contains: str | list[str] | None) -> bool:
    if not title_contains:
        return True
    options = title_contains if isinstance(title_contains, list) else [title_contains]
    title_lower = title.lower()
    return any(str(option).lower() in title_lower for option in options)


def find_window(title_contains: str | list[str] | None) -> tuple[int, str] | None:
    windows = visible_windows()
    for hwnd, title in windows:
        if title_matches(title, title_contains):
            return hwnd, title
    return None


def wait_for_window(title_contains: str | list[str] | None, timeout_seconds: int) -> tuple[int, str]:
    deadline = time.time() + timeout_seconds
    while time.time() < deadline:
        found = find_window(title_contains)
        if found:
            return found
        time.sleep(1)
    visible = ", ".join(title for _, title in visible_windows()[:10])
    raise TimeoutError(f"Window tidak ditemukan. Window terlihat: {visible}")


def wait_for_any_window(title_options: list[str], timeout_seconds: int) -> tuple[int, str] | None:
    deadline = time.time() + timeout_seconds
    lowered = [title.lower() for title in title_options]
    while time.time() < deadline:
        for hwnd, title in visible_windows():
            title_lower = title.lower()
            if any(option in title_lower for option in lowered):
                return hwnd, title
        time.sleep(0.5)
    return None


def focus_window(hwnd: int) -> None:
    user32.ShowWindow(hwnd, SW_RESTORE)
    user32.SetForegroundWindow(hwnd)
    time.sleep(0.5)


def press_key(name: str) -> None:
    key = VK.get(name.lower())
    if key is None:
        raise ValueError(f"Tombol tidak dikenal: {name}")
    user32.keybd_event(key, 0, 0, None)
    time.sleep(0.03)
    user32.keybd_event(key, 0, KEYEVENTF_KEYUP, None)


def press_hotkey(keys: list[str]) -> None:
    codes = []
    for key_name in keys:
        key = VK.get(key_name.lower())
        if key is None:
            raise ValueError(f"Tombol tidak dikenal: {key_name}")
        codes.append(key)
    for key in codes:
        user32.keybd_event(key, 0, 0, None)
        time.sleep(0.03)
    for key in reversed(codes):
        user32.keybd_event(key, 0, KEYEVENTF_KEYUP, None)
        time.sleep(0.03)


def click(x: int, y: int) -> None:
    screen_width = user32.GetSystemMetrics(SM_CXSCREEN)
    screen_height = user32.GetSystemMetrics(SM_CYSCREEN)
    if not (0 <= x < screen_width and 0 <= y < screen_height):
        raise ValueError(f"Koordinat klik di luar layar: x={x}, y={y}, layar={screen_width}x{screen_height}")
    user32.SetCursorPos(x, y)
    time.sleep(0.1)
    user32.mouse_event(MOUSEEVENTF_LEFTDOWN, 0, 0, 0, None)
    time.sleep(0.05)
    user32.mouse_event(MOUSEEVENTF_LEFTUP, 0, 0, 0, None)


def type_text(text: str) -> None:
    for char in text:
        if char == "\n":
            press_key("enter")
        elif char == "\t":
            press_key("tab")
        else:
            # SendInput would be ideal, but VkKeyScanW keeps this dependency-free.
            vk_scan = user32.VkKeyScanW(ord(char))
            if vk_scan == -1:
                raise ValueError(f"Karakter tidak bisa diketik otomatis: {char!r}")
            vk = vk_scan & 0xFF
            shift_state = (vk_scan >> 8) & 0xFF
            if shift_state & 1:
                user32.keybd_event(VK["shift"], 0, 0, None)
            user32.keybd_event(vk, 0, 0, None)
            time.sleep(0.02)
            user32.keybd_event(vk, 0, KEYEVENTF_KEYUP, None)
            if shift_state & 1:
                user32.keybd_event(VK["shift"], 0, KEYEVENTF_KEYUP, None)
        time.sleep(0.03)


def set_clipboard_text(text: str) -> None:
    data = (text + "\0").encode("utf-16-le")
    handle = kernel32.GlobalAlloc(GMEM_MOVEABLE, len(data))
    if not handle:
        raise ctypes.WinError(ctypes.get_last_error())
    locked = kernel32.GlobalLock(handle)
    if not locked:
        raise ctypes.WinError(ctypes.get_last_error())
    ctypes.memmove(locked, data, len(data))
    kernel32.GlobalUnlock(handle)

    if not user32.OpenClipboard(None):
        raise ctypes.WinError(ctypes.get_last_error())
    try:
        user32.EmptyClipboard()
        if not user32.SetClipboardData(CF_UNICODETEXT, handle):
            raise ctypes.WinError(ctypes.get_last_error())
    finally:
        user32.CloseClipboard()


def paste_text(text: str) -> None:
    set_clipboard_text(text)
    press_hotkey(["ctrl", "v"])


def add_months(value: datetime, months: int) -> datetime:
    month_index = value.month - 1 + months
    year = value.year + month_index // 12
    month = month_index % 12 + 1
    first_next_month = datetime(year + (month // 12), (month % 12) + 1, 1)
    last_day = (first_next_month - timedelta(days=1)).day
    return value.replace(year=year, month=month, day=min(value.day, last_day))


def month_date(months_offset: int, which: str, date_format: str) -> str:
    target = add_months(datetime.now(), months_offset)
    if which == "start":
        target = target.replace(day=1)
    elif which == "end":
        target = add_months(target.replace(day=1), 1) - timedelta(days=1)
    else:
        raise ValueError(f"Pilihan tanggal bulan tidak dikenal: {which}")
    return target.strftime(date_format)


def format_filename(template: str) -> str:
    now = datetime.now()
    return now.strftime(template)


def wait_for_file_stable(path: Path, timeout_seconds: int, stable_seconds: float) -> None:
    deadline = time.time() + timeout_seconds
    last_state: tuple[int, float] | None = None
    stable_since: float | None = None

    while time.time() < deadline:
        if path.exists() and path.is_file():
            stat = path.stat()
            state = (stat.st_size, stat.st_mtime)
            if state == last_state:
                if stable_since is None:
                    stable_since = time.time()
                if time.time() - stable_since >= stable_seconds:
                    logging.info("File tersimpan dan stabil: %s", path)
                    return
            else:
                last_state = state
                stable_since = None
        time.sleep(0.5)

    raise TimeoutError(f"File belum selesai tersimpan dalam {timeout_seconds} detik: {path}")


def save_as_file(step: dict) -> Path:
    title_contains = step.get("title_contains", ["Save As", "Save"])
    found = wait_for_any_window(title_contains if isinstance(title_contains, list) else [title_contains], int(step.get("timeout_seconds", 30)))
    if not found:
        visible = ", ".join(title for _, title in visible_windows()[:10])
        raise TimeoutError(f"Popup Save As tidak ditemukan. Window terlihat: {visible}")

    directory = project_path(step.get("directory", ROOT / "downloads" / "dashboard"))
    directory.mkdir(parents=True, exist_ok=True)
    filename = format_filename(str(step.get("filename", "jo-export-%Y%m%d-%H%M%S.xlsx")))
    full_path = directory / filename

    hwnd, title = found
    logging.info("Popup Save As ditemukan: %s", title)
    focus_window(hwnd)
    paste_text(str(full_path))
    time.sleep(0.5)
    press_key("enter")
    wait_for_file_stable(
        full_path,
        int(step.get("save_timeout_seconds", 120)),
        float(step.get("stable_seconds", 2)),
    )
    return full_path


def screenshot(name: str) -> Path | None:
    if ImageGrab is None:
        logging.warning("Pillow ImageGrab tidak tersedia, screenshot dilewati.")
        return None
    screenshot_dir = ROOT / "screenshots"
    screenshot_dir.mkdir(exist_ok=True)
    path = screenshot_dir / f"{datetime.now():%Y%m%d-%H%M%S}-{name}.png"
    ImageGrab.grab().save(path)
    logging.info("Screenshot: %s", path)
    return path


def project_path(value) -> Path:
    path = Path(str(value))
    return path if path.is_absolute() else ROOT / path


def copy_downloads(source_dir: Path, archive_dir: Path, patterns: list[str], since: float | None = None) -> list[Path]:
    if not source_dir.exists():
        logging.warning("Folder download browser tidak ditemukan: %s", source_dir)
        return []
    archive_dir.mkdir(exist_ok=True)
    copied = []
    for pattern in patterns:
        for source in source_dir.glob(pattern):
            if source.is_file() and (since is None or source.stat().st_mtime >= since):
                target = archive_dir / source.name
                shutil.copy2(source, target)
                copied.append(target)
                logging.info("File disalin ke archive: %s", target)
    return copied


def wait_for_downloads(source_dir: Path, patterns: list[str], since: float, timeout_seconds: int) -> list[Path]:
    if not source_dir.exists():
        logging.warning("Folder download browser tidak ditemukan: %s", source_dir)
        return []

    deadline = time.time() + timeout_seconds
    temporary_suffixes = (".crdownload", ".download", ".part", ".tmp")
    while time.time() < deadline:
        matches: list[Path] = []
        temporary_files = []
        for pattern in patterns:
            for source in source_dir.glob(pattern):
                if source.is_file() and source.stat().st_mtime >= since:
                    matches.append(source)
        for source in source_dir.iterdir():
            if source.is_file() and source.suffix.lower() in temporary_suffixes and source.stat().st_mtime >= since:
                temporary_files.append(source)
        if matches and not temporary_files:
            return matches
        time.sleep(1)

    logging.warning("Tidak ada file download baru yang selesai dalam %s detik.", timeout_seconds)
    return []


def run_step(step: dict) -> None:
    action = step.get("action")
    log_step = dict(step)
    if "password" in str(log_step).lower() or log_step.get("env"):
        log_step = {**log_step, "text": "***"}
    logging.info("Step: %s", log_step)
    if action == "wait":
        time.sleep(float(step.get("seconds", 1)))
    elif action == "key":
        for _ in range(int(step.get("count", 1))):
            press_key(step["key"])
    elif action == "hotkey":
        press_hotkey(step["keys"])
    elif action == "click":
        for _ in range(int(step.get("count", 1))):
            click(int(step["x"]), int(step["y"]))
            time.sleep(float(step.get("interval", 0.15)))
    elif action == "type":
        type_text(str(step.get("text", "")))
    elif action == "paste":
        paste_text(str(step.get("text", "")))
    elif action == "type_month_date":
        text = month_date(
            int(step.get("months_offset", 0)),
            str(step.get("which", "start")),
            str(step.get("format", "%Y/%m/%d")),
        )
        type_text(text)
    elif action == "paste_month_date":
        text = month_date(
            int(step.get("months_offset", 0)),
            str(step.get("which", "start")),
            str(step.get("format", "%Y/%m/%d")),
        )
        paste_text(text)
    elif action == "save_as":
        save_as_file(step)
    elif action == "type_env":
        name = str(step["env"])
        value = os.environ.get(name)
        if value is None:
            env_hint = f"Buat file {DEFAULT_ENV} atau set di PowerShell sebelum menjalankan python main.py."
            if (ROOT / ".env.example").exists() and not DEFAULT_ENV.exists():
                env_hint = f"File .env belum ada. Salin .env.example menjadi {DEFAULT_ENV}, lalu isi credential."
            raise ValueError(f"Environment variable belum diset: {name}. {env_hint}")
        type_text(value)
    elif action == "screenshot":
        screenshot(str(step.get("name", "step")))
    else:
        raise ValueError(f"Action tidak dikenal: {action}")


def handle_security_warning(config: dict) -> None:
    warning = config.get("security_warning", {})
    if not warning.get("enabled", True):
        return

    title_options = warning.get("title_contains", ["Open File", "Security Warning"])
    timeout = int(warning.get("timeout_seconds", 8))
    found = wait_for_any_window(title_options, timeout)
    if not found:
        logging.info("Popup security warning tidak muncul.")
        return

    hwnd, title = found
    logging.info("Popup security warning ditemukan: %s", title)
    focus_window(hwnd)
    screenshot("security-warning")

    for step in warning.get("steps", [{"action": "hotkey", "keys": ["alt", "r"]}]):
        run_step(step)
        time.sleep(0.5)


def handle_already_running_warning(config: dict) -> None:
    warning = config.get("already_running_warning", {})
    if not warning.get("enabled", True):
        return

    found = wait_for_any_window(warning.get("title_contains", ["Error"]), int(warning.get("timeout_seconds", 2)))
    if not found:
        return

    hwnd, title = found
    logging.info("Popup aplikasi sudah berjalan ditemukan: %s", title)
    focus_window(hwnd)
    screenshot("already-running-warning")
    for step in warning.get("steps", [{"action": "key", "key": "enter"}]):
        run_step(step)
        time.sleep(0.5)


def start_shortcut(shortcut: Path, config: dict) -> None:
    launch_cfg = config.get("launch", {})
    bypass_zone_check = launch_cfg.get("bypass_zone_check", True)

    old_zone_check = os.environ.get("SEE_MASK_NOZONECHECKS")
    if bypass_zone_check:
        logging.info("Bypass Open File security warning aktif untuk proses launch ini.")
        os.environ["SEE_MASK_NOZONECHECKS"] = "1"

    try:
        os.startfile(shortcut)
    finally:
        if bypass_zone_check:
            if old_zone_check is None:
                os.environ.pop("SEE_MASK_NOZONECHECKS", None)
            else:
                os.environ["SEE_MASK_NOZONECHECKS"] = old_zone_check


def main() -> int:
    parser = argparse.ArgumentParser(description="RPA download IOS-APS")
    parser.add_argument("--config", default=str(DEFAULT_CONFIG), help="Path config JSON")
    parser.add_argument("--check-only", action="store_true", help="Validasi config tanpa membuka aplikasi")
    parser.add_argument("--list-windows", action="store_true", help="Tampilkan daftar window aktif lalu keluar")
    args = parser.parse_args()

    log_path = setup_logging()
    load_env_file(DEFAULT_ENV)
    config = load_config(Path(args.config))
    shortcut = Path(config["shortcut_path"])
    if not shortcut.exists():
        raise FileNotFoundError(f"Shortcut tidak ditemukan: {shortcut}")

    logging.info("Log: %s", log_path)
    if args.list_windows:
        for hwnd, title in visible_windows():
            logging.info("Window aktif: %s | %s", hwnd, title)
        return 0
    if args.check_only:
        logging.info("Config valid. Shortcut ditemukan: %s", shortcut)
        logging.info("Mode check-only aktif, aplikasi tidak dibuka.")
        return 0

    download_started_at = time.time()

    logging.info("Membuka shortcut: %s", shortcut)
    start_shortcut(shortcut, config)
    handle_security_warning(config)
    handle_already_running_warning(config)

    hwnd, title = wait_for_window(config.get("window_title_contains"), int(config.get("window_timeout_seconds", 60)))
    logging.info("Window ditemukan: %s", title)
    focus_window(hwnd)
    screenshot("opened")

    for step in config.get("steps", []):
        focus_window(hwnd)
        run_step(step)

    download_cfg = config.get("copy_downloads")
    if download_cfg and download_cfg.get("enabled"):
        source_dir = project_path(download_cfg.get("source_dir", str(Path.home() / "Downloads")))
        archive_dir = project_path(download_cfg.get("archive_dir", str(ROOT / "archive")))
        patterns = download_cfg.get("patterns", ["*"])
        timeout_seconds = int(download_cfg.get("timeout_seconds", 120))
        wait_for_downloads(source_dir, patterns, download_started_at, timeout_seconds)
        copied = copy_downloads(source_dir, archive_dir, patterns, since=download_started_at)
        if not copied:
            logging.warning("Tidak ada file baru yang disalin ke archive.")

    screenshot("finished")
    logging.info("Selesai.")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception:
        logging.exception("RPA gagal.")
        raise
