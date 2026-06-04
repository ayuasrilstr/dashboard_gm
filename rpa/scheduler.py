import argparse
import sys
import os
import time
import subprocess
import msvcrt
from datetime import datetime, timedelta
from pathlib import Path

ROOT_DIR = Path(__file__).resolve().parent
LOG_DIR = ROOT_DIR / "logs"
LOG_PATH = LOG_DIR / "scheduler.log"
LOCK_PATH = LOG_DIR / "scheduler.lock"

SCHEDULE_START_HOUR = 7
SCHEDULE_END_HOUR = 23
SCHEDULE_INTERVAL_HOURS = 2

LOCK_FILE = None
RUN_IN_PROGRESS = False

# Ensure logs directory exists
LOG_DIR.mkdir(parents=True, exist_ok=True)

def log(message):
    log_text = f"[{datetime.now():%Y-%m-%d %H:%M:%S}] [Master Scheduler] {message}"
    print(log_text)
    try:
        with LOG_PATH.open("a", encoding="utf-8") as log_file:
            log_file.write(log_text + "\n")
    except Exception as e:
        print(f"Gagal menulis log: {e}")

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

def run_rpa_script(name, args, cwd):
    log(f"Menjalankan RPA: {name}...")
    try:
        cmd = [sys.executable, "main.py"] + args
        log(f"Command: {' '.join(cmd)} di {cwd}")
        
        result = subprocess.run(
            cmd,
            cwd=str(cwd),
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace"
        )
        
        if result.stdout:
            for line in result.stdout.splitlines():
                if line.strip():
                    log(f"[{name}] {line}")
                    
        if result.stderr:
            for line in result.stderr.splitlines():
                if line.strip():
                    log(f"[{name} ERR] {line}")
                    
        if result.returncode == 0:
            log(f"RPA {name} selesai dengan sukses.")
            return True
        else:
            log(f"RPA {name} gagal dengan exit code {result.returncode}.")
            return False
            
    except Exception as e:
        log(f"Error saat menjalankan RPA {name}: {e}")
        return False

def run_all_rpa_once():
    global RUN_IN_PROGRESS
    if RUN_IN_PROGRESS:
        log("Download dilewati karena proses sebelumnya masih berjalan.")
        return
        
    RUN_IN_PROGRESS = True
    log("=== MEMULAI DOWNLOAD SEMUA RPA ===")
    
    # 1. Accessories RPA
    accessories_dir = ROOT_DIR / "accessories-rpa"
    if accessories_dir.exists():
        run_rpa_script("Accessories RPA", ["--headless"], accessories_dir)
    else:
        log("Folder accessories-rpa tidak ditemukan.")
        
    # 2. Engage RPA
    engage_dir = ROOT_DIR / "engage-rpa"
    if engage_dir.exists():
        run_rpa_script("Engage RPA", ["--once"], engage_dir)
    else:
        log("Folder engage-rpa tidak ditemukan.")
        
    # 3. APS RPA
    aps_dir = ROOT_DIR / "aps-rpa"
    if aps_dir.exists():
        run_rpa_script("APS RPA", [], aps_dir)
    else:
        log("Folder aps-rpa tidak ditemukan.")
        
    log("=== SELESAI DOWNLOAD SEMUA RPA ===")
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
        f"Scheduler aktif. Download semua RPA setiap {SCHEDULE_INTERVAL_HOURS} jam "
        f"dari {SCHEDULE_START_HOUR:02d}:00 sampai {SCHEDULE_END_HOUR:02d}:00."
    )

    try:
        now = datetime.now()
        if SCHEDULE_START_HOUR <= now.hour <= SCHEDULE_END_HOUR:
            run_all_rpa_once()

        while True:
            next_run = get_next_run_time()
            wait_seconds = max(0, (next_run - datetime.now()).total_seconds())
            log(f"Run berikutnya: {next_run:%Y-%m-%d %H:%M:%S}")
            time.sleep(wait_seconds)

            run_all_rpa_once()

            time.sleep(60)
    finally:
        release_process_lock()

def main():
    parser = argparse.ArgumentParser(description="Master RPA Scheduler")
    parser.add_argument("--once", action="store_true", help="Jalankan semua RPA sekali lalu keluar")
    args = parser.parse_args()

    if args.once:
        if not acquire_process_lock():
            log("Download sekali dilewati karena scheduler/proses lain sedang berjalan.")
            return 1
        try:
            run_all_rpa_once()
        finally:
            release_process_lock()
        return 0

    run_scheduler()
    return 0

if __name__ == "__main__":
    sys.exit(main())
