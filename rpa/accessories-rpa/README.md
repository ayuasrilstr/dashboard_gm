# Accessories RPA

RPA ini mengambil data Controlist Accessories dari CIUROX:

```text
http://192.168.8.23/ciurox/login.php
```

Alur saat ini:

1. Login ke CIUROX.
2. Buka menu `Accessories GM`.
3. Filter tanggal dari awal bulan berjalan sampai hari ini.
4. Pilih status `COMPLETED`.
5. Export Excel ke folder `downloads`.
6. File yang sudah lewat hari ini akan dipindahkan otomatis ke folder `archive` saat run berikutnya.

## Cara pakai

```powershell
cd rpa\accessories-rpa
python main.py
```

Validasi config tanpa membuka browser:

```powershell
python main.py --check-only
```

Credential disimpan di `.env`:

```text
ACCESSORIES_USERNAME=username-anda
ACCESSORIES_PASSWORD=password-anda
```

Output export:

```text
downloads/CONTROLIST_YYYYMMDD_HHMMSS.xlsx
archive/YYYY-MM/YYYY-MM-DD_CONTROLIST_YYYYMMDD_HHMMSS.xlsx
```
