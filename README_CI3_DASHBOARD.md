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

Mapping fitur per file/fungsi ada di `CODE_FEATURE_MAP.md`.

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

## Fitur aplikasi

### 1. Dashboard Heat Transfer

Halaman utama dashboard Heat Transfer tersedia di:

```text
http://localhost/dashboard_gm/index.php/dashboard_heat
```

Fitur yang ditampilkan:

- **Total Output**: total output produksi dari data Engage/RPA.
- **Balance Qty**: sisa qty yang belum selesai dari periode delivery aktif.
- **Balance Breakdown**: breakdown balance per periode delivery.
- **QTY PDK vs Output**: grafik perbandingan plan PDK dan output.
- **Ready To Load**: grafik qty ready per periode delivery.
- **Kapasitas vs Output**: grafik output harian, input, kapasitas, dan gap/surplus.
- **Top Priority Orders**: daftar order prioritas berdasarkan delivery terdekat.
- **Last Update**: status data terbaru dan ringkasan kapasitas/output terakhir.
- **Run Download**: menjalankan scheduler RPA satu kali dari web.
- **Kalender kerja**: mengatur hari libur, setengah hari, seperempat hari, dan Minggu kerja.
- **Analytics**: card ringkas, detail card, management insight, dan pilihan card yang ingin ditampilkan.

### 2. Run Download

Tombol `Run Download` memanggil endpoint `dashboard_heat/api/run-download`, lalu menjalankan:

```text
python rpa/scheduler.py --once
```

Urutan downloader:

1. Accessories RPA
2. Engage RPA
3. APS RPA

Output downloader dipakai sebagai input dashboard.

### 3. Kalender kerja

Kalender kerja disimpan di:

```text
web/application/cache/dashboard_heat_holidays.json
```

Jenis tanggal:

- **Holiday**: dihitung `0` hari kerja.
- **Half day**: dihitung `0.5` hari kerja.
- **Quarter day**: dihitung `0.25` hari kerja.
- **Work day**: dipakai untuk membuat hari Minggu tetap dihitung sebagai hari kerja.

Edit kalender membutuhkan login. Setelah modal kalender atau Analytics Display ditutup, session login di-reset agar akses berikutnya wajib login ulang.

### 4. Analytics Display

Analytics Display adalah menu untuk memilih card Analytics yang ditampilkan. Menu ini membutuhkan login.

Default card saat ini:

- `Production Status`
- `Output Achievement`
- `Data Accuracy`

Card lain tetap tersedia di menu pilihan, antara lain:

- `Monitoring Coverage`
- `Source Sync`
- `Data Update`
- `Ready Coverage`
- `Req. Daily Output`
- `Total Ready Load`
- `Avg Daily Output`
- `Avg Daily Capacity`
- `Capacity Gap / Capacity Surplus`
- `Sequence Issues`
- `Critical Orders`
- Card CAP/prevention/handling yang dipilih manual

Card yang memiliki isi sama tidak ditampilkan sebagai opsi terpisah. Contoh: `Data Reliability` digabung ke `Data Accuracy`, dan `Plan Completion` digabung ke `Output Achievement`.

## Sumber data dan asal angka

### APS / JO Tracking

File:

```text
rpa/aps-rpa/downloads/JO.xlsx
```

Dipakai untuk:

- Data JO/order.
- Style.
- Delivery date.
- QTY PDK/plan.
- Period delivery seperti `MID June` atau `END June`.
- Prioritas order berdasarkan tanggal delivery.

### Engage 32a Outflow

File:

```text
rpa/engage-rpa/downloads/32a_outflow.xlsx
```

Dipakai untuk:

- Output aktual Heat Transfer.
- Output per order.
- Output per hari untuk grafik kapasitas vs output.
- Total output dashboard.

### Engage 32a Inflow

File:

```text
rpa/engage-rpa/downloads/32a_inflow.xlsx
```

Dipakai untuk:

- Input/masuk ke area 32a.
- Data pembanding pada grafik kapasitas vs output.

### Engage 32 Inflow

File:

```text
rpa/engage-rpa/downloads/32_inflow.xlsx
```

Dipakai sebagai data pendukung alur warehouse/produksi.

### Accessories Controlist

File:

```text
rpa/accessories-rpa/downloads/CONTROLIST.xlsx
```

Dipakai untuk:

- Menandai order Accessories yang sudah `COMPLETED`.
- Mendukung perhitungan order ready/completed.

## Struktur data dashboard

Model `Dashboard_model.php` mengubah file RPA menjadi struktur utama berikut:

| Field JSON | Isi | Asal utama |
| --- | --- | --- |
| `kpis.total_output` | Total output produksi | Engage 32a Outflow |
| `kpis.balance_qty` | Total balance dari periode dipilih | APS JO dan output Engage |
| `qty_pdk_vs_output` | PDK vs output per periode | APS JO + Engage Outflow |
| `ready_to_load` | Qty ready per periode | APS JO + Engage/Accessories |
| `output_vs_capacity` | Output, input, capacity harian | Engage Outflow + Engage Inflow + kalkulasi capacity |
| `top_priority_orders` | Order prioritas delivery | APS JO + ready/output |
| `sources` | Status file sumber data | File RPA di folder downloads |
| `delivery_workdays` | Kalender kerja tiap periode | Kalender dashboard |
| `management_analytics` | Metrics, status, insight, detail, CAP | Hasil kalkulasi dashboard |

## Perhitungan utama

### 1. QTY PDK vs Output

Periode delivery dibentuk dari delivery date APS:

```text
Tanggal 1-15  -> MID <bulan>
Tanggal 16-akhir bulan -> END <bulan>
```

Rumus per periode:

```text
QTY PDK = total qty plan/PDK dari APS pada periode tersebut
QTY Output = total output Engage yang cocok dengan order/periode tersebut
Balance = max(0, QTY PDK - QTY Output)
```

### 2. Total Output

```text
Total Output = total output dari data Engage 32a Outflow
```

Nilai ini ditampilkan di KPI utama dan dipakai untuk insight Output Achievement.

### 3. Balance Qty

```text
Balance Qty = total QTY PDK - total QTY Output
```

Untuk analytics, balance bisa difokuskan ke periode berjalan.

### 4. Ready To Load

```text
Ready To Load = qty order/periode yang sudah tersedia/ready berdasarkan gabungan data APS, Engage, dan Accessories
```

Nilai ini ditampilkan per periode delivery.

### 5. Capacity Harian

Capacity dihitung dari balance delivery aktif dan sisa hari kerja:

```text
Daily Capacity = balance delivery aktif / sisa hari kerja delivery aktif
```

Data capacity dipakai untuk grafik `Kapasitas vs Output`.

### 6. Avg Daily Output

```text
Avg Daily Output = total output harian / jumlah hari yang punya output atau capacity
```

Sumber output harian berasal dari Engage 32a Outflow.

### 7. Avg Daily Capacity

```text
Avg Daily Capacity = total capacity harian / jumlah hari yang punya output atau capacity
```

### 8. Capacity Gap / Surplus

```text
Capacity Gap = total capacity - total daily output
```

Interpretasi:

- Jika hasil positif, masih ada gap kapasitas tersedia.
- Jika hasil negatif, output lebih besar dari capacity dan ditampilkan sebagai surplus.

### 9. Ready Coverage

```text
Ready Coverage Days = Total Ready Load / Avg Daily Capacity
```

Status:

| Nilai | Status |
| --- | --- |
| `>= 10 hari` | `good` |
| `>= 5` dan `< 10 hari` | `watch` |
| `< 5 hari` | `risk` |

### 10. Required Daily Output

Kebutuhan output harian dihitung dari periode berjalan:

```text
Required Daily Output = balance periode berjalan / sisa hari kerja export periode berjalan
```

Sisa hari kerja export memakai buffer export:

```text
Export Remaining Workdays = Remaining Workdays - 4 hari buffer export
```

Nilai buffer export saat ini:

```text
4 hari kerja
```

Status Required Daily Output:

| Kondisi | Status |
| --- | --- |
| `Avg Daily Output >= Required Daily Output` | `good` |
| `Avg Daily Output >= Required Daily Output * 0.9` | `watch` |
| Di bawah itu | `risk` |

### 11. Output Achievement

Rumus:

```text
Output Achievement = output_base / pdk_base * 100
```

`pdk_base` dan `output_base` memakai periode berjalan jika tersedia. Jika tidak tersedia, sistem fallback ke total PDK/output dashboard.

Status:

| Nilai | Status |
| --- | --- |
| `>= 90%` | `good` |
| `>= 75%` dan `< 90%` | `watch` |
| `< 75%` | `risk` |

### 12. Data Accuracy

Data Accuracy memeriksa apakah periode sebelumnya sudah clear sebelum periode berjalan diproses.

Alur:

1. Cari periode berjalan pertama yang masih punya `balance > 0` atau `ready > 0`.
2. Ambil dua periode sebelum periode berjalan.
3. Jika periode sebelumnya masih punya `balance + ready > 0`, maka dianggap issue.

Rumus score:

```text
Data Accuracy Score = max(0, 100 - (jumlah issue * 20))
```

Status:

| Nilai | Status |
| --- | --- |
| `>= 90%` | `good` |
| `>= 75%` dan `< 90%` | `watch` |
| `< 75%` | `risk` |

### 13. Critical Orders

Critical Orders dihitung dari order prioritas yang delivery date-nya berada dalam horizon 5 hari kerja.

```text
Critical Order = delivery_workdays_left(order.delivery) <= 5
```

Status:

| Kondisi | Status |
| --- | --- |
| Tidak ada critical order | `good` |
| Ada critical order | `risk` |

### 14. Source Sync

```text
Source Sync = jumlah source file tersedia / total source file yang dimonitor
```

Contoh:

```text
5/5
```

Artinya 5 dari 5 file sumber dashboard tersedia.

### 15. Monitoring Coverage

Monitoring Coverage menunjukkan modul dashboard yang masuk scope monitoring, bukan kapasitas produksi.

Scope saat ini:

- APS JO Tracking
- Engage 32a Inflow
- Engage 32a Outflow
- Accessories Controlist
- Dashboard Analytics

### 16. Data Update

Data Update mengambil timestamp terakhir dari source dashboard:

```text
Data Update = latest updated_at dari daftar sources
```

Detail card menampilkan update terakhir per source.

## Status keseluruhan produksi

Backend menghitung status keseluruhan lewat fungsi:

```text
Dashboard_model::build_overall_condition()
```

Input perhitungan:

- `achievement_rate`
- `ready_coverage_days`
- `required_daily_output`
- `avg_daily_output`
- `avg_daily_capacity`
- `critical_orders`
- `data_accuracy`
- periode berjalan
- kalender kerja periode berjalan

### Risk point dan watch point

Sistem menambahkan risk/watch point dari kondisi berikut:

| Kondisi | Efek |
| --- | --- |
| `achievement_rate < 75%` | tambah risk |
| `achievement_rate >= 75%` dan `< 90%` | tambah watch |
| `ready_coverage_days < 5` | tambah risk |
| `ready_coverage_days >= 5` dan `< 10` | tambah watch |
| `remaining export workdays > 11` | tambah 3 risk |
| `remaining export workdays >= 5` dan `<= 11` | tambah 2 watch |
| `remaining export workdays < 4` | dianggap sisa hari aman |
| `remaining_days <= 0` dan masih ada balance | tambah risk |
| `avg_daily_capacity < required_daily_output` | tambah risk |
| `avg_daily_output < required_daily_output` | tambah watch |
| `avg_daily_output < required_daily_output * 1.1` | tambah watch |
| `critical_orders > 0` | tambah risk |
| `data_accuracy.score < 75` | tambah risk |
| `data_accuracy.score >= 75` dan `< 90` | tambah watch |

### Mapping status keseluruhan

Urutan keputusan status:

| Kondisi | Status | Level |
| --- | --- | --- |
| `remaining export workdays > 11` | `risk` | `High Risk` |
| `remaining export workdays >= 5` | `watch` | `Medium Risk` |
| `remaining export workdays < 4` | `good` | `Low Risk` |
| `risk_points >= 3` | `risk` | `High Risk` |
| `risk_points > 0` atau `watch_points >= 2` | `watch` | `Medium Risk` |
| `watch_points > 0` | `watch` | `Medium Risk` |
| Selain itu | `good` | `Low Risk` |

Catatan penting: pada UI Analytics saat ini, card `Production Status` default ditampilkan sebagai label general `On Track`. Nilai backend yang lebih detail tetap tersedia di `management_analytics.overall_condition` dengan status `good`, `watch`, atau `risk`. Jika ingin status card benar-benar mengikuti kalkulasi backend, mapping yang disarankan:

| Backend | Label tampilan |
| --- | --- |
| `good / Low Risk` | `On Track` |
| `watch / Medium Risk` | `Need Attention` |
| `risk / High Risk` | `At Risk` |

## Management Insight

Management Insight di view dibentuk dari card Analytics yang sedang tampil. Jika card diganti lewat Analytics Display, insight ikut berubah.

Contoh mapping:

| Card tampil | Insight memakai data |
| --- | --- |
| `Production Status` | status card, total output, balance qty |
| `Output Achievement` | achievement dan total output |
| `Data Accuracy` | score akurasi data |
| `Monitoring Coverage` | scope modul dashboard |
| `Source Sync` | jumlah source tersinkron |
| `Data Update` | timestamp update terakhir source |
| `Ready Coverage` | coverage days dan total ready load |
| `Req. Daily Output` | kebutuhan output harian dan balance |
| `Total Ready Load` | total ready dari ready-to-load |
| `Avg Daily Output` | rata-rata output harian |
| `Avg Daily Capacity` | rata-rata kapasitas harian |
| `Capacity Gap / Surplus` | selisih capacity dan output |
| `Sequence Issues` | jumlah issue urutan data |
| `Critical Orders` | jumlah order kritis |

## CAP / Prevention & Handling

CAP dibuat oleh:

```text
Dashboard_model::build_management_action_plan()
```

CAP muncul berdasarkan kondisi:

| CAP | Muncul jika | Status |
| --- | --- | --- |
| `Data Accuracy` | score `< 90%` | mengikuti status Data Accuracy |
| `Output Achievement` | achievement `< 90%` | `watch` jika `>=75%`, `risk` jika `<75%` |
| `Coverage Ready Load` | ready coverage `< 10 hari` | `watch` jika `>=5 hari`, `risk` jika `<5 hari` |
| `Kebutuhan Output Harian` | avg daily output `< required daily output` | `watch` jika masih `>=90%` requirement, selain itu `risk` |
| `Order Delivery Kritis` | critical orders `> 0` | `risk` |
| `Kondisi Terkendali` | tidak ada CAP lain | `good` |

CAP berisi:

- masalah
- penyebab
- prevention
- handling

Di Analytics Display, CAP bisa dipilih manual sebagai card/tindak lanjut.

## Batasan dan catatan operasional

- Dashboard sangat bergantung pada file RPA terbaru. Jika file tidak tersedia, angka bisa kosong atau fallback ke cache.
- APS, Engage, dan Accessories harus memakai format/header Excel yang masih sesuai dengan parser.
- Accessories bersifat tambahan; data utama Heat tetap APS dan Engage.
- Kalender kerja memengaruhi sisa hari kerja, required daily output, capacity, dan status overall.
- Buffer export saat ini adalah `4 hari kerja`.
- Critical order memakai horizon `5 hari kerja`.
- Data Accuracy bukan audit seluruh data, tetapi validasi sequence antar periode delivery.
- Source Sync hanya memeriksa ketersediaan source, bukan menjamin seluruh isi file benar.
- Monitoring Coverage menunjukkan area yang dipantau, bukan achievement produksi.
- Cache dashboard berada di `web/application/cache/dashboard_heat_data.json`; jika data terlihat tidak berubah, cek cache, source timestamp, dan log scheduler.
