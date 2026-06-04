import ctypes
import msvcrt
import time
from ctypes import wintypes


user32 = ctypes.WinDLL("user32", use_last_error=True)


class POINT(ctypes.Structure):
    _fields_ = [("x", wintypes.LONG), ("y", wintypes.LONG)]


def current_position() -> tuple[int, int]:
    point = POINT()
    user32.GetCursorPos(ctypes.byref(point))
    return point.x, point.y


def main() -> None:
    print("Arahkan mouse ke tombol/menu yang dibutuhkan.")
    print("Tekan Enter untuk ambil posisi, atau Ctrl+C untuk berhenti.")
    try:
        while True:
            x, y = current_position()
            print(f"x={x}, y={y}".ljust(40), end="\r", flush=True)
            if msvcrt.kbhit() and msvcrt.getwch() == "\r":
                print(f"\nPosisi dipilih: x={x}, y={y}")
            time.sleep(0.15)
    except KeyboardInterrupt:
        x, y = current_position()
        print(f"\nPosisi terakhir: x={x}, y={y}")


if __name__ == "__main__":
    main()
