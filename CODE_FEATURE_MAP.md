# Code Feature Map

Dokumen ini adalah tanda/mapping fitur untuk kode custom project Dashboard GM. File bawaan framework CodeIgniter di `web/system` tidak dipetakan satu per satu karena fungsinya adalah core/vendor framework.

## Ringkasan Fitur

| Fitur | File utama | Output/hasil |
| --- | --- | --- |
| Dashboard Heat Transfer | `web/application/controllers/Dashboard_heat.php`, `web/application/models/Dashboard_model.php`, `web/application/views/dashboards/heat/index.php` | Halaman dashboard `dashboard_heat` dan API status |
| Data APS / JO Tracking | `rpa/aps-rpa/main.py`, `rpa/aps-rpa/config.json` | `rpa/aps-rpa/downloads/JO.xlsx` |
| Data Engage / Warehouse | `rpa/engage-rpa/main.py` | `32_inflow.xlsx`, `32a_inflow.xlsx`, `32a_outflow.xlsx` |
| Data Accessories / Controlist | `rpa/accessories-rpa/main.py`, `rpa/accessories-rpa/config.json` | `rpa/accessories-rpa/downloads/CONTROLIST.xlsx` |
| Master scheduler RPA | `rpa/scheduler.py` | Menjalankan Accessories, Engage, lalu APS |
| Kalender kerja/libur Heat | `Dashboard_heat.php`, `Dashboard_model.php`, `web/application/cache/dashboard_heat_holidays.json` | Simpan hari libur, setengah hari, seperempat hari, Minggu kerja |
| Cache dashboard Heat | `Dashboard_model.php`, `web/application/cache/dashboard_heat_data.json` | Mempercepat load dashboard |
| History kapasitas Heat | `Dashboard_model.php`, `web/application/cache/dashboard_heat_capacity_history.json` | Kapasitas historis untuk chart/output |
| Download file dari web | `Dashboard_heat.php`, `Dashboard_model.php` | Endpoint `dashboard_heat/download?file=...` |

## Alur Data Utama

1. `rpa/scheduler.py` menjalankan semua downloader.
2. `rpa/accessories-rpa/main.py` download `CONTROLIST.xlsx`.
3. `rpa/engage-rpa/main.py` download file inflow/outflow Engage.
4. `rpa/aps-rpa/main.py` download `JO.xlsx` dari IOS-APS.
5. `web/application/models/Dashboard_model.php` membaca semua file Excel hasil RPA.
6. `web/application/controllers/Dashboard_heat.php` menyajikan data model sebagai JSON API.
7. `web/application/views/dashboards/heat/index.php` menampilkan chart, tabel, analytics, kalender, dan tombol aksi.

## Web CodeIgniter

### `index.php`

Bootstrap CodeIgniter. Fitur: entry point aplikasi web lewat XAMPP/Apache.

### `web/application/config/routes.php`

Fitur routing:

- `default_controller = dashboard`: URL root diarahkan ke controller `Dashboard`.
- `translate_uri_dashes = FALSE`: nama route tidak otomatis mengubah dash menjadi underscore.

### `web/application/config/dashboard.php`

Fitur konfigurasi dashboard:

- Path alternatif file Excel dashboard Heat.
- Path UNC/share untuk data dashboard.
- Default kalender libur Heat.
- Username/password untuk edit Kalender Libur.

### `web/application/controllers/Dashboard.php`

Fitur hub dashboard:

- `index()`: redirect halaman utama ke `dashboard_heat`.

### `web/application/controllers/Dashboard_base.php`

Fitur base controller:

- `__construct()`: load helper URL untuk controller dashboard.
- `json($payload, $status)`: helper response JSON standar untuk API.

### `web/application/controllers/Dashboard_heat.php`

Fitur controller Heat Transfer:

- `__construct()`: mulai session dan load `Dashboard_model`.
- `index()`: render halaman dashboard Heat dan inject URL API.
- `api($action)`: router internal untuk API `status`, `run-download`, `save-workdays`, `calendar-login`, dan `calendar-logout`.
- `api_status()`: ambil data dashboard, waktu server, dan status login kalender.
- `run_download()`: jalankan scheduler sekali dari tombol `Run Download`.
- `save_workdays()`: simpan konfigurasi kalender kerja/libur.
- `calendar_login()`: login admin kalender libur.
- `calendar_logout()`: logout admin kalender libur.
- `calendar_authenticated()`: cek session login kalender.
- `dashboard_config()`: baca konfigurasi `dashboard.php`.
- `download()`: download file sumber/report dari dashboard.

## Model Dashboard

### `web/application/models/Dashboard_model.php`

File ini adalah pusat data dan kalkulasi Dashboard Heat. Mapping fungsi berdasarkan fitur:

#### Path, Config, dan Cache

- `root_path()`, `data_dir()`, `data_file()`: menentukan lokasi root dan file data.
- `data_file_diagnostics()`, `data_file_candidates()`, `data_dir_candidates()`: diagnosa kandidat path data.
- `dashboard_config()`, `log_path()`: baca config dan path log.
- `heat_dashboard_cache_path()`, `read_heat_dashboard_cache()`, `write_heat_dashboard_cache()`: cache hasil dashboard Heat.
- `heat_capacity_history_path()`, `read_heat_capacity_history()`, `write_heat_capacity_history()`: cache/history kapasitas.

#### Kalender Heat

- `heat_holidays_path()`: lokasi file kalender.
- `get_heat_holiday_settings()`: baca setting kalender.
- `save_heat_holiday_settings()`: simpan setting kalender.
- `normalize_calendar_dates()`: normalisasi daftar tanggal kalender.
- `dashboard_calendar_signature()`: signature cache berdasarkan kalender.
- `dashboard_holidays()`, `dashboard_calendar_days()`: baca hari libur dan hari kerja khusus.
- `build_delivery_workdays()`, `build_current_period_calendar()`, `build_period_calendar()`: hitung kalender per periode.
- `count_workdays()`, `calendar_workday_value()`, `date_range()`: utilitas hitung hari kerja.

#### Status, Logs, dan Download

- `get_report_status()`: status file/report.
- `read_recent_logs()`: baca log scheduler terbaru.
- `run_download_once()`: jalankan `rpa/scheduler.py --once`.
- `get_download_path()`: validasi path file yang boleh didownload.

#### Pembacaan Report Excel/HTML

- `latest_report_path()`, `report_filename_candidates()`: cari file report terbaru.
- `read_html_report()`, `parse_table_rows()`: baca report HTML.
- `is_xlsx_zip()`, `read_xlsx_report()`, `read_xlsx_sheet_grid()`: baca file `.xlsx`.
- `xlsx_sheet_path_by_name()`, `xlsx_sheet_name_for_hint()`, `xlsx_sheet_map()`, `first_xlsx_sheet_name()`: cari sheet Excel.
- `read_xlsx_shared_strings()`, `xlsx_cell_value()`, `xlsx_column_index()`: parsing cell Excel.
- `normalize_xlsx_report_rows()`, `xlsx_header_score()`, `combine_xlsx_group_headers()`: rapikan header/row Excel.

#### Sumber Data RPA

- `heat_rpa_sources()`: daftar sumber file APS, Engage, Accessories.
- `latest_matching_file()`, `latest_existing_file()`, `latest_mtime_iso()`: cari file sumber terbaru.
- `source_status_rows()`: status tiap sumber data.
- `read_combined_engage_outflow_report()`: gabungkan data outflow Engage.
- `engage_rpa_history_roots()`: lokasi history Engage.
- `build_heat_data_from_rpa_sources()`: gabungkan APS, Engage, dan Accessories menjadi data Heat.

#### Dashboard Heat Data

- `get_dashboard_sheet()`: baca sheet dashboard legacy.
- `get_heat_dashboard_data($delivery_count)`: endpoint data utama Heat.
- `get_heat_dashboard_data_from_rpa($delivery_count)`: ambil data dari file RPA.
- `build_heat_data_from_database_delivery()`: bangun data dari database/delivery source.
- `normalize_delivery_count()`: batas jumlah periode delivery.

#### Perhitungan Output, Balance, Ready, dan Capacity

- `extract_qty_pdk_vs_output()`: ambil qty PDK vs output.
- `extract_ready_to_load()`: ambil ready to load.
- `extract_output_vs_capacity()`: ambil output vs capacity.
- `build_output_vs_capacity_from_engage_daily()`: chart output/capacity dari Engage.
- `build_output_vs_capacity_from_rpa()`: chart output/capacity dari data RPA.
- `build_output_vs_capacity_from_source()`: chart output/capacity dari source legacy.
- `capacity_for_output_day()`: kapasitas per tanggal output.
- `source_prod_days_left()`, `source_daily_capacity()`, `source_daily_capacity_detail()`: hitung sisa hari produksi dan kapasitas.

#### Order, Period, dan Prioritas

- `normalize_order_number()`: rapikan nomor order.
- `period_label_from_date_value()`, `period_label_from_excel_date()`, `format_period_label()`: label periode delivery.
- `parse_date_timestamp()`, `excel_date_serial()`, `excel_serial_to_date()`, `excel_serial_to_timestamp()`: parsing tanggal.
- `summarize_engage_rows_by_order()`: ringkas Engage per order.
- `summarize_accessories_completed_orders()`: order Accessories yang completed.
- `build_priority_orders_from_rpa()`, `build_priority_orders_from_source()`: daftar prioritas order.
- `extract_top_priority_orders()`: top priority dari sumber dashboard.
- `is_heat_transfer_delivery()`, `is_database_out()`: filter data Heat.

#### Analytics Management

- `build_management_analytics()`: ringkasan analytics dashboard.
- `build_overall_condition()`: status kondisi keseluruhan.
- `build_management_action_plan()`: rekomendasi/action plan.
- `build_data_accuracy()`: indikator kelengkapan/akurasi data.
- `current_running_period()`, `previous_period_labels()`, `find_period_by_label()`: periode aktif dan pembanding.
- `analytics_insight()`: format insight analytics.

#### Helper Format dan Normalisasi

- `format_display_date()`, `format_output_day_label()`, `format_output_day_label_from_serial()`: format tanggal tampilan.
- `format_percent()`, `floor_decimal()`, `format_compact_number()`, `format_bytes()`: format angka.
- `grid_header_column()`, `grid_value()`, `grid_cell()`, `cell()`, `cell_any()`: helper baca grid/row.
- `dedupe_grid_rows()`, `row_has_value()`, `trim_empty_grid()`: bersihkan data grid.
- `parse_number()`, `normalize()`, `sum_qty()`: normalisasi teks dan angka.
- `header_index()`, `column_letters_to_index()`: mapping header/kolom.

## View Dashboard Heat

### `web/application/views/dashboards/heat/index.php`

Fitur tampilan:

- Struktur HTML halaman Dashboard Heat.
- CSS layout dashboard, chart, modal, kalender, dan tabel.
- JavaScript untuk fetch API status, render chart, tabel, analytics, modal detail, kalender kerja, login kalender, dan tombol run download.

Mapping fungsi JavaScript:

- Format angka/tanggal: `floorDecimal()`, `numberValue()`, `shortDelivery()`, `compactChartValue()`.
- Chart: `renderGroupedChart()`, `renderCapacityChart()`, `renderDashboardCharts()`.
- Tabel dan modal detail: `tableCell()`, `metricValue()`, `renderCalcNote()`, `renderDetailTable()`, `showDetailModal()`, `closeDetailModal()`, `openAnalyticsDetail()`.
- Analytics: `detailKeyForMetric()`, `renderAnalytics()`, `renderBalanceBreakdown()`.
- Last update/status sumber: `renderLastUpdateList()`.
- Kalender: `calendarPeriods()`, `isoDate()`, `periodDateRange()`, `calendarWorkdayValue()`, `calendarWorkdaysForLabel()`, `renderCalendar()`, `renderCalendarSummary()`, `cycleCalendarDay()`.
- Login/edit kalender: `openWorkdayModal()`, `closeWorkdayModal()`, `closeWorkdayModalAndLogout()`, `openCalendarLoginModal()`, `closeCalendarLoginModal()`.
- Navigasi view dan render utama: `setActiveView()`, `render()`, `renderDeliveryToggle()`.

## RPA Master Scheduler

### `rpa/scheduler.py`

Fitur scheduler:

- `log()`: tulis log master scheduler.
- `acquire_process_lock()`, `release_process_lock()`: cegah scheduler dobel jalan.
- `run_rpa_script()`: jalankan satu RPA Python dan teruskan log stdout/stderr.
- `run_all_rpa_once()`: jalankan Accessories, Engage, APS secara berurutan.
- `get_next_run_time()`: hitung jadwal berikutnya, default tiap 2 jam dari 07:00 sampai 23:00.
- `run_scheduler()`: mode scheduler terus berjalan.
- `main()`: CLI `--once` atau mode scheduler.

## APS RPA

### `rpa/aps-rpa/config.json`

Fitur konfigurasi langkah IOS-APS:

- `shortcut_path`: shortcut aplikasi IOS-APS.
- `window_title_contains`: title window yang dianggap APS aktif.
- `security_warning`: handler popup Windows security warning.
- `already_running_warning`: handler popup aplikasi sudah berjalan.
- `steps`: urutan klik/keyboard untuk login, buka JO Tracking Report, isi filter, refresh, export Excel, simpan `JO.xlsx`, dan tutup APS.
- `copy_downloads`: opsi salin file download ke archive.

### `rpa/aps-rpa/main.py`

Fitur otomasi Windows GUI:

- Config/env/logging: `load_config()`, `load_env_file()`, `setup_logging()`.
- Window detection: `window_title()`, `visible_windows()`, `title_matches()`, `find_window()`, `wait_for_window()`, `wait_for_any_window()`, `focus_window()`.
- Keyboard/mouse: `press_key()`, `press_hotkey()`, `click()`, `type_text()`, `paste_text()`, `set_clipboard_text()`.
- Tanggal/filename: `add_months()`, `month_date()`, `format_filename()`.
- Save/download: `save_as_file()`, `wait_for_file_stable()`, `copy_downloads()`, `wait_for_downloads()`.
- Observability: `screenshot()`.
- Step runner: `run_step()`, `focus_step_window()`.
- Popup/launch: `handle_security_warning()`, `handle_already_running_warning()`, `start_shortcut()`.
- CLI utama: `main()`.

### `rpa/aps-rpa/mouse_position.py`

Fitur utilitas mapping koordinat:

- `current_position()`: baca posisi mouse.
- `main()`: tampilkan koordinat mouse untuk membantu isi step klik di `config.json`.

## Engage RPA

### `rpa/engage-rpa/main.py`

Fitur download report warehouse Engage:

- Login/session/report page: `open_report()`, `has_csrf_error()`, `close_visible_message_dialog()`.
- Filter form: `set_input_value()`, `set_date_filter()`, `wait_for_loading_done()`.
- Row count/pagination: `get_displayed_row_count()`, `click_display_and_get_row_count()`, `fetch_report_page()`, `fetch_report_rows()`.
- Validasi pesan: `normalize_dialog_text()`, `is_date_filter_error()`.
- Periode dan tanggal: `add_months()`, `format_report_date()`, `get_month_periods()`, `parse_reference_month()`.
- File output: `get_archive_path()`, `save_download()`, `build_report_html()`, `save_report_rows()`.
- Parsing angka/data: `count_response_rows()`, `parse_float()`.
- Lock/log: `log()`, `acquire_process_lock()`, `release_process_lock()`.
- Job runner: `download_reports()`, `run_async_job()`, `run_download_once()`.
- Scheduler bawaan Engage: `get_next_run_time()`, `run_scheduler()`.
- CLI utama: `main()`.

## Accessories RPA

### `rpa/accessories-rpa/config.json`

Fitur konfigurasi langkah CIUROX Accessories:

- Buka halaman login CIUROX.
- Isi username/password dari environment.
- Buka halaman Accessories GM.
- Isi tanggal awal bulan sampai hari ini.
- Pilih status `COMPLETED`.
- Download `CONTROLIST.xlsx`.

### `rpa/accessories-rpa/main.py`

Fitur Playwright Accessories:

- Setup/config: `load_env()`, `load_config()`, `project_path()`.
- Log/env/date: `log()`, `env_value()`, `date_value()`, `format_filename()`.
- Step runner: `run_step()` untuk `goto`, `wait`, `fill`, `select`, `download`, dan action lain dari config.
- Browser runner: `run()`.
- Validasi config: `check_config()`.
- CLI utama: `main()`.

## Autostart dan Script Pendukung

| Path | Fitur |
| --- | --- |
| `rpa/run_scheduler.vbs` | Menjalankan master scheduler secara hidden |
| `rpa/autostart/admin/install_scheduler.bat` | Install scheduler ke startup/admin |
| `rpa/autostart/admin/uninstall_scheduler.bat` | Uninstall scheduler startup/admin |
| `rpa/autostart/user/install_scheduler_user.bat` | Install scheduler ke startup user |
| `rpa/autostart/user/uninstall_scheduler_user.bat` | Uninstall scheduler startup user |
| `rpa/engage-rpa/run_hidden.vbs` | Menjalankan Engage RPA hidden |
| `rpa/engage-rpa/autostart/*` | Install/uninstall autostart khusus Engage RPA |

## File Data, Cache, dan Log

| Path | Fitur |
| --- | --- |
| `rpa/logs/scheduler.log` | Log master scheduler dan output RPA |
| `rpa/logs/scheduler.lock` | Lock file agar scheduler tidak dobel |
| `rpa/aps-rpa/logs/` | Log detail APS RPA |
| `rpa/aps-rpa/screenshots/` | Screenshot debug APS RPA |
| `rpa/aps-rpa/downloads/JO.xlsx` | Data JO APS terbaru |
| `rpa/engage-rpa/downloads/*.xlsx` | Data Engage terbaru |
| `rpa/accessories-rpa/downloads/CONTROLIST.xlsx` | Data Accessories terbaru |
| `web/application/cache/dashboard_heat_data.json` | Cache data dashboard Heat |
| `web/application/cache/dashboard_heat_holidays.json` | Setting kalender Heat |
| `web/application/cache/dashboard_heat_capacity_history.json` | History kapasitas Heat |

## Cara Membaca Mapping Ini

- Kalau ingin ubah tampilan, mulai dari `web/application/views/dashboards/heat/index.php`.
- Kalau ingin ubah rumus dashboard, mulai dari `web/application/models/Dashboard_model.php`.
- Kalau ingin ubah endpoint/API, mulai dari `web/application/controllers/Dashboard_heat.php`.
- Kalau ingin ubah urutan klik APS, mulai dari `rpa/aps-rpa/config.json`.
- Kalau ingin ubah jadwal download, mulai dari `rpa/scheduler.py`.
- Kalau ingin ubah sumber data Excel, cek `heat_rpa_sources()` dan fungsi pembaca Excel di `Dashboard_model.php`.
