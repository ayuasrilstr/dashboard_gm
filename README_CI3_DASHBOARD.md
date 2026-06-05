# Dashboard GM

Dashboard GM adalah aplikasi dashboard internal berbasis web untuk memantau data produksi, terutama modul **Heat Transfer**. Web ini membaca hasil download RPA dari APS, Engage, dan Accessories, lalu menggabungkannya menjadi indikator dashboard seperti output, balance, ready to load, kapasitas harian, dan prioritas order.

## Dibuat menggunakan

- **PHP CodeIgniter 3** untuk backend MVC.
- **HTML, CSS, dan JavaScript** untuk tampilan dashboard.
- **Python** untuk RPA downloader.
- **Playwright Python** untuk RPA berbasis browser seperti Engage dan Accessories.
- **Windows GUI automation** untuk RPA APS melalui aplikasi IOS-APS.
- **XAMPP/Apache** sebagai server lokal.
- **Excel `.xlsx`** sebagai media pertukaran data dari RPA ke dashboard.

## Cara pasang

1. Download CodeIgniter 3 secara manual.
2. Web CodeIgniter berada di `web/application` dan `web/system`.
3. RPA berada di `rpa/engage-rpa`, `rpa/aps-rpa`, dan `rpa/accessories-rpa`.
4. Buka lewat XAMPP:

```text
http://localhost/dashboard_gm/index.php/dashboard_heat
```

## File utama

- `web/application/controllers/Dashboard.php` sebagai hub dashboard.
- `web/application/controllers/Dashboard_base.php` sebagai base controller dashboard.
- `web/application/controllers/Dashboard_heat.php` sebagai modul Heat.
- `web/application/models/Dashboard_model.php` untuk data Heat saat ini.
- `web/application/views/dashboards/heat/index.php` untuk view Heat.
- `web/application/config/routes.php`

## Pola modul dashboard

Untuk bagian baru, buat controller dan view terpisah:

```text
web/application/controllers/Dashboard_nama_bagian.php
web/application/models/Dashboard_nama_bagian_model.php
web/application/views/dashboards/nama_bagian/index.php
web/application/views/analytics/nama_bagian/index.php
```

Contoh URL:

```text
/dashboard_heat
/dashboard_cutting
/dashboard_sewing
```

Tombol `Run Download` pada dashboard menjalankan master scheduler:

```text
python rpa/scheduler.py --once
```

Scheduler menjalankan RPA secara berurutan:

1. Accessories RPA
2. Engage RPA
3. APS RPA

## Sumber data Heat

```text
rpa/engage-rpa        -> DATABASE / output aktual
rpa/aps-rpa           -> DELIVERY / data JO APS
rpa/accessories-rpa   -> CONTROLIST / data Accessories
```

## Cara pengambilan data

### Accessories

Accessories RPA login ke CIUROX, membuka halaman Accessories GM, mengisi filter tanggal dan status `COMPLETED`, lalu export Controlist.

Output file:

```text
rpa/accessories-rpa/downloads/CONTROLIST.xlsx
```

File ini dibuat tetap agar download berikutnya menimpa data lama dan folder server tidak dipenuhi file timestamp.

### Engage

Engage RPA login ke web Engage, membuka report warehouse, lalu mengambil data per kombinasi storage dan direction. Default periode download adalah **10 hari terakhir termasuk hari ini**.

Output file:

```text
rpa/engage-rpa/downloads/32_inflow.xlsx
rpa/engage-rpa/downloads/32a_inflow.xlsx
rpa/engage-rpa/downloads/32a_outflow.xlsx
```

Masing-masing file mewakili satu filter:

- `32_inflow.xlsx`: Storage `32`, Direction `1`
- `32a_inflow.xlsx`: Storage `32a`, Direction `1`
- `32a_outflow.xlsx`: Storage `32a`, Direction `2`

### APS

APS RPA membuka aplikasi IOS-APS, login, mengisi filter JO, lalu export Excel. Periode Delivery Date saat ini adalah dari **awal bulan lalu** sampai **akhir 2 bulan ke depan**.

Output file:

```text
rpa/aps-rpa/downloads/JO.xlsx
```

File APS juga dibuat tetap supaya setiap download terbaru menggantikan file sebelumnya.

## Cara dashboard Heat membaca data

Model utama ada di:

```text
web/application/models/Dashboard_model.php
```

Dashboard Heat membaca file RPA berikut:

```text
APS         : rpa/aps-rpa/downloads/JO.xlsx
Accessories : rpa/accessories-rpa/downloads/CONTROLIST.xlsx
Engage      : rpa/engage-rpa/downloads/32_inflow.xlsx
              rpa/engage-rpa/downloads/32a_inflow.xlsx
              rpa/engage-rpa/downloads/32a_outflow.xlsx
```

Data dianggap lengkap jika file APS, `32a_inflow`, dan `32a_outflow` tersedia serta header Excel bisa dibaca. Accessories bersifat tambahan untuk menghitung order yang sudah completed.
