# Engage RPA Dashboard CodeIgniter 3

Dashboard ini dibuat sebagai aplikasi PHP CodeIgniter 3 untuk membaca data dari folder RPA yang dikelompokkan di `rpa/`.

## Cara pasang

1. Download CodeIgniter 3 secara manual.
2. Web CodeIgniter berada di `web/application` dan `web/system`.
3. RPA berada di `rpa/engage-rpa`, `rpa/aps-rpa`, dan `rpa/accessories-rpa`.
4. Buka lewat XAMPP:

```text
http://localhost/dashboard_subprocess/index.php/dashboard
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

Tombol `Run Download` menjalankan `python main.py --once` dari folder `rpa/engage-rpa`.

## Sumber data Heat

```text
rpa/engage-rpa        -> DATABASE / output aktual
rpa/aps-rpa           -> DELIVERY / data JO APS
rpa/accessories-rpa   -> CONTROLIST / data Accessories
```
