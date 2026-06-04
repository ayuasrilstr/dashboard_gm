# APS RPA

Otomatisasi ini membuka shortcut `IOS-APS - Shortcut.lnk`, menunggu window aplikasi muncul, lalu menjalankan langkah-langkah yang ditulis di `config.json`.

## Cara pakai

1. Salin `config.example.json` menjadi `config.json`.
2. Jalankan:

```powershell
python main.py
```

Untuk validasi aman tanpa membuka aplikasi:

```powershell
python main.py --check-only
```

Untuk melihat judul window aktif agar `window_title_contains` bisa disetel lebih tepat:

```powershell
python main.py --list-windows
```

Set credential di PowerShell sebelum menjalankan RPA. Ini harus dijalankan di terminal yang sama dengan `python main.py`:

```powershell
$env:APS_USERNAME="username-anda"
$env:APS_PASSWORD="password-anda"
python main.py
```

Atau buat file `.env` dari contoh `.env.example`:

```text
APS_USERNAME=username-anda
APS_PASSWORD=password-anda
```

Jika memakai `.env`, cukup jalankan:

```powershell
python main.py
```

Untuk aplikasi yang tidak bisa di-inspect, ambil koordinat tombol/menu dengan:

```powershell
python mouse_position.py
```

Setelah posisi terlihat, tambahkan step klik seperti ini:

```json
{ "action": "click", "x": 100, "y": 200 }
```

Log tersimpan di folder `logs`, screenshot tersimpan di folder `screenshots`, dan file hasil download baru akan disalin ke `archive` jika opsi `copy_downloads.enabled` diaktifkan. Skrip menunggu download selesai sampai `copy_downloads.timeout_seconds`, lalu hanya menyalin file yang muncul sejak proses RPA dimulai.

Untuk menghindari popup Windows `Open File - Security Warning`, skrip mengaktifkan `SEE_MASK_NOZONECHECKS` hanya saat membuka shortcut. Jika popup tetap muncul, fallback di bagian `security_warning` akan mencoba `Enter` lalu `Alt+R`.

## Format step

Step yang tersedia:

```json
{ "action": "wait", "seconds": 2 }
{ "action": "click", "x": 100, "y": 200 }
{ "action": "key", "key": "enter" }
{ "action": "key", "key": "backspace", "count": 30 }
{ "action": "hotkey", "keys": ["ctrl", "s"] }
{ "action": "type", "text": "contoh teks" }
{ "action": "type_month_date", "which": "start", "months_offset": 0, "format": "%d/%m/%Y" }
{ "action": "type_env", "env": "APS_USERNAME" }
{ "action": "screenshot", "name": "nama-screenshot" }
```

Untuk melengkapi proses download, isi `steps` sesuai urutan klik atau keyboard di aplikasi IOS-APS.
