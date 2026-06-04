<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model
{
    private $reports = array(
        array('key' => '32_inflow', 'label' => '32 Inflow', 'storage' => '32', 'direction' => '1', 'filename' => '32_inflow.xlsx'),
        array('key' => '32a_inflow', 'label' => '32a Inflow', 'storage' => '32a', 'direction' => '1', 'filename' => '32a_inflow.xlsx'),
        array('key' => '32a_outflow', 'label' => '32a Outflow', 'storage' => '32a', 'direction' => '2', 'filename' => '32a_outflow.xlsx'),
    );

    private $months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec');

    private function root_path()
    {
        $root = realpath(APPPATH . '..' . DIRECTORY_SEPARATOR . '..');
        return $root ?: realpath(APPPATH . '..');
    }

    private function data_dir()
    {
        foreach ($this->data_dir_candidates() as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        return $this->data_dir_candidates()[0];
    }

    private function data_file()
    {
        foreach ($this->data_file_candidates() as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return NULL;
    }

    private function data_file_diagnostics()
    {
        $items = array();

        foreach ($this->data_file_candidates() as $path) {
            $items[] = array(
                'path' => $path,
                'exists' => is_file($path),
                'readable' => is_readable($path),
            );
        }

        return $items;
    }

    private function data_file_candidates()
    {
        $dashboard_config = $this->dashboard_config();
        $paths = array();

        if (getenv('DASHBOARD_HEAT_EXCEL_FILE')) {
            $paths[] = getenv('DASHBOARD_HEAT_EXCEL_FILE');
        }

        if (!empty($dashboard_config['dashboard_heat_excel_file'])) {
            $paths[] = $dashboard_config['dashboard_heat_excel_file'];
        }
        if (!empty($dashboard_config['dashboard_heat_unc_excel_file'])) {
            $paths[] = $dashboard_config['dashboard_heat_unc_excel_file'];
        }

        return array_values(array_unique($paths));
    }

    private function data_dir_candidates()
    {
        $env_path = getenv('DASHBOARD_HEAT_DATA_DIR');
        $paths = array();

        if ($env_path) {
            $paths[] = rtrim($env_path, "\\/");
        }

        $dashboard_config = $this->dashboard_config();
        if (!empty($dashboard_config['dashboard_heat_data_dir'])) {
            $paths[] = rtrim($dashboard_config['dashboard_heat_data_dir'], "\\/");
        }
        if (!empty($dashboard_config['dashboard_heat_unc_dir'])) {
            $paths[] = rtrim($dashboard_config['dashboard_heat_unc_dir'], "\\/");
        }

        if (getenv('DASHBOARD_HEAT_LOCAL_FALLBACK')) {
            $paths[] = $this->root_path() . DIRECTORY_SEPARATOR . 'rpa' . DIRECTORY_SEPARATOR . 'engage-rpa' . DIRECTORY_SEPARATOR . 'downloads';
        }

        return array_values(array_unique($paths));
    }

    private function dashboard_config()
    {
        $config = array();
        $path = APPPATH . 'config' . DIRECTORY_SEPARATOR . 'dashboard.php';

        if (is_file($path)) {
            include $path;
        }

        return is_array($config) ? $config : array();
    }

    private function log_path()
    {
        return $this->root_path() . DIRECTORY_SEPARATOR . 'rpa' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'scheduler.log';
    }

    private function heat_dashboard_cache_path()
    {
        return APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'dashboard_heat_data.json';
    }

    private function heat_capacity_history_path()
    {
        return APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'dashboard_heat_capacity_history.json';
    }

    private function heat_holidays_path()
    {
        return APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'dashboard_heat_holidays.json';
    }

    public function get_heat_holiday_settings()
    {
        $path = $this->heat_holidays_path();
        if (!is_file($path) || !is_readable($path)) {
            return array('holidays' => array(), 'half_days' => array(), 'work_days' => array());
        }

        $payload = json_decode(file_get_contents($path), TRUE);
        if (!is_array($payload)) {
            return array('holidays' => array(), 'half_days' => array(), 'work_days' => array());
        }

        $holidays = isset($payload['holidays']) && is_array($payload['holidays']) ? $payload['holidays'] : array();
        $half_days = isset($payload['half_days']) && is_array($payload['half_days']) ? $payload['half_days'] : array();
        $work_days = isset($payload['work_days']) && is_array($payload['work_days']) ? $payload['work_days'] : array();

        return array(
            'holidays' => $this->normalize_calendar_dates($holidays),
            'half_days' => $this->normalize_calendar_dates($half_days),
            'work_days' => $this->normalize_calendar_dates($work_days),
        );
    }

    private function normalize_calendar_dates($dates)
    {
        $items = array_values(array_unique(array_filter(array_map(function ($date) {
            $timestamp = strtotime($date);
            return $timestamp ? date('Y-m-d', $timestamp) : NULL;
        }, $dates))));
        sort($items);

        return $items;
    }

    public function save_heat_holiday_settings($calendar)
    {
        if (!is_array($calendar)) {
            return array('ok' => FALSE, 'message' => 'Data kalender tidak valid.');
        }

        $holidays = isset($calendar['holidays']) && is_array($calendar['holidays']) ? $calendar['holidays'] : $calendar;
        $half_days = isset($calendar['half_days']) && is_array($calendar['half_days']) ? $calendar['half_days'] : array();
        $work_days = isset($calendar['work_days']) && is_array($calendar['work_days']) ? $calendar['work_days'] : array();
        $clean_holidays = $this->normalize_calendar_dates($holidays);
        $clean_half_days = array_values(array_diff($this->normalize_calendar_dates($half_days), $clean_holidays));
        $clean_work_days = array_values(array_diff($this->normalize_calendar_dates($work_days), array_merge($clean_holidays, $clean_half_days)));

        $path = $this->heat_holidays_path();
        $dir = dirname($path);
        if (!is_dir($dir) || !is_writable($dir)) {
            return array('ok' => FALSE, 'message' => 'Folder cache tidak bisa ditulis.');
        }

        $payload = array(
            'updated_at' => date('c'),
            'holidays' => $clean_holidays,
            'half_days' => $clean_half_days,
            'work_days' => $clean_work_days,
        );

        if (file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === FALSE) {
            return array('ok' => FALSE, 'message' => 'Gagal menyimpan kalender libur.');
        }

        return array('ok' => TRUE, 'message' => 'Kalender kerja tersimpan.', 'calendar' => $payload);
    }

    private function read_heat_dashboard_cache($source_path)
    {
        $cache_path = $this->heat_dashboard_cache_path();
        if (!is_file($cache_path) || !is_readable($cache_path)) {
            return NULL;
        }

        $payload = json_decode(file_get_contents($cache_path), TRUE);
        if (!is_array($payload) || !isset($payload['data'])) {
            return NULL;
        }

        $source_mtime = is_file($source_path) ? filemtime($source_path) : 0;
        $source_size = is_file($source_path) ? filesize($source_path) : 0;
        $calendar_signature = $this->dashboard_calendar_signature();
        if (
            !isset($payload['cache_version'], $payload['source_path'], $payload['source_mtime'], $payload['source_size'], $payload['calendar_signature']) ||
            $payload['cache_version'] !== 27 ||
            $payload['source_path'] !== $source_path ||
            (int) $payload['source_mtime'] !== (int) $source_mtime ||
            (int) $payload['source_size'] !== (int) $source_size ||
            $payload['calendar_signature'] !== $calendar_signature
        ) {
            return NULL;
        }

        return $payload['data'];
    }

    private function write_heat_dashboard_cache($source_path, $data)
    {
        $cache_path = $this->heat_dashboard_cache_path();
        $dir = dirname($cache_path);
        if (!is_dir($dir) || !is_writable($dir)) {
            return;
        }

        $payload = array(
            'cache_version' => 27,
            'source_path' => $source_path,
            'source_mtime' => filemtime($source_path),
            'source_size' => filesize($source_path),
            'calendar_signature' => $this->dashboard_calendar_signature(),
            'cached_at' => date('c'),
            'data' => $data,
        );

        file_put_contents($cache_path, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function dashboard_calendar_signature()
    {
        return md5(json_encode($this->dashboard_calendar_days(), JSON_UNESCAPED_UNICODE));
    }

    private function read_heat_capacity_history()
    {
        $path = $this->heat_capacity_history_path();
        if (!is_file($path) || !is_readable($path)) {
            return array();
        }

        $payload = json_decode(file_get_contents($path), TRUE);
        if (!is_array($payload) || empty($payload['items']) || !is_array($payload['items'])) {
            return array();
        }

        $items = array();
        foreach ($payload['items'] as $date => $item) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }

            if (is_numeric($item)) {
                $items[$date] = array('capacity' => max(0, (float) $item), 'breakdown' => array());
                continue;
            }

            if (is_array($item) && isset($item['capacity']) && is_numeric($item['capacity'])) {
                $items[$date] = $item;
                $items[$date]['capacity'] = max(0, (float) $item['capacity']);
                $items[$date]['breakdown'] = isset($item['breakdown']) && is_array($item['breakdown']) ? $item['breakdown'] : array();
            }
        }

        return $items;
    }

    private function write_heat_capacity_history($items)
    {
        $path = $this->heat_capacity_history_path();
        $dir = dirname($path);
        if (!is_dir($dir) || !is_writable($dir)) {
            return;
        }

        ksort($items);
        $payload = array(
            'updated_at' => date('c'),
            'items' => $items,
        );

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function capacity_for_output_day($date, $daily_capacity, $capacity_breakdown, &$history, &$history_changed)
    {
        $capacity = max(0, (float) $daily_capacity);
        $today = date('Y-m-d');

        if ($date < $today && isset($history[$date])) {
            return $history[$date];
        }

        $entry = array(
            'capacity' => $capacity,
            'captured_at' => date('c'),
            'calendar_signature' => $this->dashboard_calendar_signature(),
            'breakdown' => $capacity_breakdown,
        );

        if (!isset($history[$date]) || $history[$date]['capacity'] != $capacity || $history[$date]['breakdown'] !== $capacity_breakdown) {
            $history[$date] = $entry;
            $history_changed = TRUE;
        }

        return $entry;
    }

    public function get_report_status()
    {
        $rows = array();

        foreach ($this->reports as $report) {
            $path = $this->latest_report_path($report['filename']);
            $exists = is_file($path);
            $rows[] = $report + array(
                'exists' => $exists,
                'path' => $path,
                'size' => $exists ? filesize($path) : NULL,
                'size_label' => $exists ? $this->format_bytes(filesize($path)) : '-',
                'updated_at' => $exists ? date('c', filemtime($path)) : NULL,
                'rows' => $exists ? $this->count_report_rows($path) : NULL,
            );
        }

        return $rows;
    }

    public function get_dashboard_sheet()
    {
        $path = $this->data_file();
        if (!$path || !is_file($path)) {
            return array(
                'available' => FALSE,
                'message' => 'File Excel dashboard belum bisa diakses. Pastikan Apache/XAMPP punya akses ke drive X: atau UNC share.',
                'path' => $path,
                'diagnostics' => $this->data_file_diagnostics(),
                'rows' => array(),
            );
        }

        if (!$this->is_xlsx_zip($path)) {
            return array(
                'available' => FALSE,
                'message' => 'File dashboard bukan format Excel .xlsx yang valid.',
                'path' => $path,
                'rows' => array(),
            );
        }

        $sheet = $this->read_xlsx_sheet_grid($path, 'Dashboard');
        if (!$sheet['rows']) {
            return array(
                'available' => FALSE,
                'message' => 'Sheet Dashboard tidak ditemukan atau kosong.',
                'path' => $path,
                'sheet' => 'Dashboard',
                'rows' => array(),
            );
        }

        return array(
            'available' => TRUE,
            'path' => $path,
            'sheet' => 'Dashboard',
            'rows' => $sheet['rows'],
            'max_columns' => $sheet['max_columns'],
        );
    }

    public function get_heat_dashboard_data()
    {
        $rpa_data = $this->get_heat_dashboard_data_from_rpa();
        if ($rpa_data && !empty($rpa_data['available'])) {
            return $rpa_data;
        }

        return array(
            'available' => FALSE,
            'message' => $rpa_data && !empty($rpa_data['message'])
                ? $rpa_data['message']
                : 'Data RPA belum lengkap untuk dashboard Heat Transfer.',
        );
    }

    private function get_heat_dashboard_data_from_rpa()
    {
        $sources = $this->heat_rpa_sources();
        $required = array('aps', 'engage_32a_inflow', 'engage_32a_outflow');
        foreach ($required as $key) {
            if (empty($sources[$key]) || !is_file($sources[$key])) {
                return array('available' => FALSE, 'message' => 'Data RPA belum lengkap untuk dashboard Heat Transfer.');
            }
        }

        $aps = $this->read_html_report($sources['aps']);
        $inflow_32a = $this->read_html_report($sources['engage_32a_inflow']);
        $outflow_32a = $this->read_combined_engage_outflow_report($sources['engage_32a_outflow']);
        $accessories = !empty($sources['accessories']) ? $this->read_html_report($sources['accessories']) : array('headers' => array(), 'rows' => array());

        if (!$aps['headers'] || !$inflow_32a['headers'] || !$outflow_32a['headers']) {
            return array('available' => FALSE, 'message' => 'Header data RPA APS atau Engage belum bisa dibaca.');
        }

        $source_data = $this->build_heat_data_from_rpa_sources($aps, $inflow_32a, $outflow_32a, $accessories);
        if (!$source_data['qty_pdk_vs_output'] && !$source_data['ready_to_load']) {
            return array('available' => FALSE, 'message' => 'Data RPA terbaca, tetapi belum ada order Heat Transfer yang bisa ditampilkan.');
        }

        $total_pdk = $source_data['total_pdk'];
        $total_output = $source_data['total_output'];
        $balance_qty = $source_data['balance_qty'];
        $prod_days_left = $source_data['prod_days_left'];
        $qty_pdk_vs_output = $source_data['qty_pdk_vs_output'];
        $ready_to_load = $source_data['ready_to_load'];
        $output_vs_capacity = $source_data['output_vs_capacity'];
        $top_priority_orders = $source_data['top_priority_orders'];

        return array(
            'available' => TRUE,
            'source' => 'RPA: APS + Engage + Accessories',
            'source_updated_at' => $this->latest_mtime_iso($sources),
            'sources' => $this->source_status_rows($sources),
            'kpis' => array(
                'total_output' => $total_output,
                'balance_qty' => $balance_qty,
                'prod_days_left' => $prod_days_left,
            ),
            'holiday_settings' => $this->get_heat_holiday_settings(),
            'delivery_workdays' => $this->build_delivery_workdays($qty_pdk_vs_output, $ready_to_load),
            'qty_pdk_vs_output' => $qty_pdk_vs_output,
            'ready_to_load' => $ready_to_load,
            'output_vs_capacity' => $output_vs_capacity,
            'top_priority_orders' => $top_priority_orders,
            'management_analytics' => $this->build_management_analytics(
                $total_pdk,
                $total_output,
                $balance_qty,
                $prod_days_left,
                $qty_pdk_vs_output,
                $ready_to_load,
                $output_vs_capacity,
                $top_priority_orders
            ),
        );
    }

    public function summarize_32a_material()
    {
        $inflow = $this->read_html_report($this->latest_report_path('32a_inflow.xlsx'));
        $outflow = $this->read_html_report($this->latest_report_path('32a_outflow.xlsx'));

        if (!$inflow['headers']) {
            return array('available' => FALSE, 'message' => 'File 32a_inflow belum tersedia atau tidak bisa dibaca.');
        }

        $in_index = $this->header_index($inflow['headers']);
        $out_index = $this->header_index($outflow['headers']);
        $groups = array();

        foreach ($inflow['rows'] as $row) {
            $udef4 = $this->cell($row, $in_index, 'Udef 4');
            if ($udef4 === '') {
                continue;
            }
            if (!isset($groups[$udef4])) {
                $groups[$udef4] = $this->empty_group($udef4);
            }
            $groups[$udef4]['item'] = $groups[$udef4]['item'] ?: $this->cell($row, $in_index, 'Item Nr');
            $groups[$udef4]['prod'] = $groups[$udef4]['prod'] ?: $this->cell($row, $in_index, 'Prod. Nr');
            $groups[$udef4]['in_qty'] += $this->parse_number($this->cell($row, $in_index, 'Qty'));
            $groups[$udef4]['in_rows']++;
        }

        foreach ($outflow['rows'] as $row) {
            $udef4 = $this->cell($row, $out_index, 'Udef 4');
            if ($udef4 === '') {
                continue;
            }
            if (!isset($groups[$udef4])) {
                $groups[$udef4] = $this->empty_group($udef4);
            }
            $groups[$udef4]['item'] = $groups[$udef4]['item'] ?: $this->cell($row, $out_index, 'Item Nr');
            $groups[$udef4]['prod'] = $groups[$udef4]['prod'] ?: $this->cell($row, $out_index, 'Prod. Nr');
            $groups[$udef4]['out_qty'] += abs($this->parse_number($this->cell($row, $out_index, 'Qty')));
            $groups[$udef4]['out_rows']++;
        }

        $details = array();
        $capacity = array('panel_1' => 0, 'panel_2' => 0, 'panel_3' => 0, 'panel_more' => 0);
        $capacity_qty = array('panel_1' => 0, 'panel_2' => 0, 'panel_3' => 0, 'panel_more' => 0);

        foreach ($groups as $group) {
            $ready_qty = $group['in_qty'] - $group['out_qty'];
            if ($ready_qty <= 0) {
                continue;
            }

            $panel_count = max(0, $group['in_rows'] - $group['out_rows']);
            $group['ready_qty'] = $ready_qty;
            $group['panel_count'] = $panel_count;
            $details[] = $group;

            $bucket = $panel_count <= 1 ? 'panel_1' : ($panel_count == 2 ? 'panel_2' : ($panel_count == 3 ? 'panel_3' : 'panel_more'));
            $capacity[$bucket]++;
            $capacity_qty[$bucket] += $ready_qty;
        }

        usort($details, function ($a, $b) {
            return $b['ready_qty'] <=> $a['ready_qty'];
        });

        $ready_qty = array_sum(array_column($details, 'ready_qty'));
        $ready_pdk = count($details);
        $ready_panels = array_sum(array_column($details, 'panel_count'));

        return array(
            'available' => TRUE,
            'in_qty' => $this->sum_qty($inflow['rows'], $in_index),
            'out_qty' => abs($this->sum_qty($outflow['rows'], $out_index)),
            'ready_qty' => $ready_qty,
            'ready_pdk' => $ready_pdk,
            'ready_panels' => $ready_panels,
            'qty_per_pdk' => $ready_pdk ? $ready_qty / $ready_pdk : 0,
            'panels_per_pdk' => $ready_pdk ? $ready_panels / $ready_pdk : 0,
            'capacity_buckets' => $capacity,
            'capacity_qty_buckets' => $capacity_qty,
            'periods' => $this->summarize_monthly_periods(),
            'ready_to_load_periods' => $this->summarize_monthly_periods(),
            'top_ready' => array_slice($details, 0, 12),
        );
    }

    public function read_recent_logs($limit = 80)
    {
        $path = $this->log_path();
        if (!is_file($path)) {
            return array();
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        return array_slice($lines ?: array(), -$limit);
    }

    public function run_download_once()
    {
        $root = $this->root_path();
        $rpa_root = $root . DIRECTORY_SEPARATOR . 'rpa';
        $command = 'cd /d ' . escapeshellarg($rpa_root) . ' && start /B python scheduler.py --once';
        pclose(popen('cmd /c ' . $command, 'r'));

        return array('ok' => TRUE, 'message' => 'Download dijalankan di background. Refresh status beberapa saat lagi.');
    }

    public function get_download_path($filename)
    {
        $allowed = array_column($this->reports, 'filename');
        if (!in_array($filename, $allowed, TRUE)) {
            return NULL;
        }

        $path = $this->latest_report_path($filename);
        return is_file($path) ? $path : NULL;
    }

    private function latest_report_path($filename)
    {
        $data_file = $this->data_file();
        if ($data_file) {
            return $data_file . '#' . $filename;
        }

        $paths = array();
        $root_file = $this->data_dir() . DIRECTORY_SEPARATOR . $filename;

        foreach ($this->report_filename_candidates($filename) as $candidate) {
            $path = $this->data_dir() . DIRECTORY_SEPARATOR . $candidate;
            if (is_file($path)) {
                $paths[] = $path;
            }
        }

        if (!$paths) {
            return $root_file;
        }

        usort($paths, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        return $paths[0];
    }

    private function report_filename_candidates($filename)
    {
        $info = pathinfo($filename);
        $dirname = isset($info['dirname']) && $info['dirname'] !== '.' ? $info['dirname'] . DIRECTORY_SEPARATOR : '';
        $name = isset($info['filename']) ? $info['filename'] : $filename;
        $extension = isset($info['extension']) ? strtolower($info['extension']) : '';
        $extensions = in_array($extension, array('xlsx', 'xls'), TRUE)
            ? array($extension === 'xlsx' ? 'xlsx' : 'xls', $extension === 'xlsx' ? 'xls' : 'xlsx')
            : array('xlsx', 'xls');

        $items = array();
        foreach ($extensions as $ext) {
            $items[] = $dirname . $name . '.' . $ext;
        }

        return array_values(array_unique($items));
    }

    private function read_html_report($path)
    {
        $sheet_hint = NULL;
        if (strpos($path, '#') !== FALSE) {
            list($path, $sheet_hint) = explode('#', $path, 2);
        }

        if (!is_file($path)) {
            return array('headers' => array(), 'rows' => array());
        }

        if ($this->is_xlsx_zip($path)) {
            return $this->read_xlsx_report($path, $sheet_hint);
        }

        $html = file_get_contents($path);
        $headers = array();
        if (preg_match('/<thead\b[^>]*>(.*?)<\/thead>/is', $html, $thead)) {
            preg_match_all('/<th\b[^>]*>(.*?)<\/th>/is', $thead[1], $matches);
            foreach ($matches[1] as $cell) {
                $headers[] = $this->normalize(html_entity_decode(strip_tags($cell), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        $rows = array();
        if (preg_match('/<tbody\b[^>]*>(.*?)<\/tbody>/is', $html, $tbody)) {
            $rows = $this->parse_table_rows($tbody[1]);
        } else {
            $rows = $this->parse_table_rows($html);
        }

        if (!$headers) {
            preg_match_all('/<th\b[^>]*>(.*?)<\/th>/is', $html, $matches);
            foreach ($matches[1] as $cell) {
                $headers[] = $this->normalize(html_entity_decode(strip_tags($cell), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        return array('headers' => $headers, 'rows' => $rows);
    }

    private function is_xlsx_zip($path)
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return FALSE;
        }

        $signature = fread($handle, 2);
        fclose($handle);

        return $signature === 'PK';
    }

    private function read_xlsx_report($path, $sheet_hint = NULL)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== TRUE) {
            return array('headers' => array(), 'rows' => array());
        }

        $shared_strings = $this->read_xlsx_shared_strings($zip);
        $sheet_name = $this->xlsx_sheet_name_for_hint($zip, $sheet_hint);
        if (!$sheet_name) {
            $sheet_name = $this->first_xlsx_sheet_name($zip);
        }
        $sheet_xml = $sheet_name ? $zip->getFromName($sheet_name) : FALSE;
        $zip->close();

        if ($sheet_xml === FALSE) {
            return array('headers' => array(), 'rows' => array());
        }

        $xml = simplexml_load_string($sheet_xml);
        if (!$xml) {
            return array('headers' => array(), 'rows' => array());
        }

        $rows = array();
        foreach ($xml->sheetData->row as $row_node) {
            $cells = array();
            foreach ($row_node->c as $cell_node) {
                $ref = (string) $cell_node['r'];
                $index = $this->xlsx_column_index($ref);
                $cells[$index] = $this->xlsx_cell_value($cell_node, $shared_strings);
            }

            if (!$cells) {
                continue;
            }

            ksort($cells);
            $max = max(array_keys($cells));
            $row = array();
            for ($i = 0; $i <= $max; $i++) {
                $row[] = isset($cells[$i]) ? $this->normalize($cells[$i]) : '';
            }
            $rows[] = $row;
        }

        if (!$rows) {
            return array('headers' => array(), 'rows' => array());
        }

        return array(
            'headers' => array_shift($rows),
            'rows' => $rows,
        );
    }

    private function read_xlsx_sheet_grid($path, $sheet_name)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== TRUE) {
            return array('rows' => array(), 'max_columns' => 0);
        }

        $shared_strings = $this->read_xlsx_shared_strings($zip);
        $sheet_path = $this->xlsx_sheet_path_by_name($zip, $sheet_name);
        $sheet_xml = $sheet_path ? $zip->getFromName($sheet_path) : FALSE;
        $zip->close();

        if ($sheet_xml === FALSE) {
            return array('rows' => array(), 'max_columns' => 0);
        }

        $xml = simplexml_load_string($sheet_xml);
        if (!$xml) {
            return array('rows' => array(), 'max_columns' => 0);
        }

        $rows = array();
        $max_columns = 0;

        foreach ($xml->sheetData->row as $row_node) {
            $cells = array();
            foreach ($row_node->c as $cell_node) {
                $ref = (string) $cell_node['r'];
                $index = $this->xlsx_column_index($ref);
                $cells[$index] = $this->normalize($this->xlsx_cell_value($cell_node, $shared_strings));
            }

            if (!$cells) {
                $rows[] = array();
                continue;
            }

            ksort($cells);
            $row_max = max(array_keys($cells));
            $max_columns = max($max_columns, $row_max + 1);
            $row = array();
            for ($i = 0; $i <= $row_max; $i++) {
                $row[] = isset($cells[$i]) ? $cells[$i] : '';
            }
            $rows[] = $row;
        }

        $rows = $this->trim_empty_grid($rows, $max_columns);
        return array('rows' => $rows, 'max_columns' => $max_columns);
    }

    private function xlsx_sheet_path_by_name($zip, $sheet_name)
    {
        foreach ($this->xlsx_sheet_map($zip) as $title => $path) {
            if (strcasecmp(trim($title), trim($sheet_name)) === 0) {
                return $path;
            }
        }

        return NULL;
    }

    private function trim_empty_grid($rows, &$max_columns)
    {
        while ($rows && !$this->row_has_value($rows[0])) {
            array_shift($rows);
        }

        while ($rows && !$this->row_has_value($rows[count($rows) - 1])) {
            array_pop($rows);
        }

        $max_columns = 0;
        foreach ($rows as $row) {
            $max_columns = max($max_columns, count($row));
        }

        return array_map(function ($row) use ($max_columns) {
            for ($i = count($row); $i < $max_columns; $i++) {
                $row[] = '';
            }
            return $row;
        }, $rows);
    }

    private function row_has_value($row)
    {
        foreach ($row as $cell) {
            if ($this->normalize($cell) !== '') {
                return TRUE;
            }
        }

        return FALSE;
    }

    private function extract_qty_pdk_vs_output($rows)
    {
        $items = array();

        for ($row = 5; $row <= 8; $row++) {
            $label = $this->grid_cell($rows, $row, 'H') ?: $this->grid_cell($rows, $row, 'A');
            if ($label === '' || stripos($label, 'grand') !== FALSE) {
                continue;
            }

            $items[] = array(
                'label' => $label,
                'pdk' => $this->parse_number($this->grid_cell($rows, $row, 'I') ?: $this->grid_cell($rows, $row, 'B')),
                'output' => $this->parse_number($this->grid_cell($rows, $row, 'J') ?: $this->grid_cell($rows, $row, 'F')),
            );
        }

        return $items;
    }

    private function extract_ready_to_load($rows)
    {
        $items = array();

        for ($row = 6; $row <= 12; $row++) {
            $label = $this->grid_cell($rows, $row, 'Q');
            $ready = $this->parse_number($this->grid_cell($rows, $row, 'R'));
            if ($label === '' || $ready <= 0) {
                continue;
            }

            $items[] = array(
                'label' => $label,
                'ready' => $ready,
            );
        }

        return $items;
    }

    private function extract_output_vs_capacity($rows)
    {
        $items = array();

        for ($row = 3; $row <= 40; $row++) {
            $day = $this->grid_cell($rows, $row, 'AR');
            $output = $this->parse_number($this->grid_cell($rows, $row, 'AS'));
            $capacity = $this->parse_number($this->grid_cell($rows, $row, 'AT'));

            if ($day === '' || ($output <= 0 && $capacity <= 0)) {
                continue;
            }

            $items[] = array(
                'label' => $this->format_output_day_label($day, $rows),
                'output' => $output,
                'capacity' => $capacity,
            );
        }

        return $items;
    }

    private function format_output_day_label($day, $rows)
    {
        $month_number = $this->parse_number($this->grid_cell($rows, 3, 'B'));
        if ($month_number <= 0) {
            foreach (array('F', 'J', 'N', 'R', 'V') as $column) {
                $month_number = $this->parse_number($this->grid_cell($rows, 3, $column));
                if ($month_number > 0) {
                    break;
                }
            }
        }

        $month = $month_number > 0 && isset($this->months[(int) $month_number - 1])
            ? $this->months[(int) $month_number - 1]
            : '';

        return trim($day . ' ' . $month . ' ' . date('Y'));
    }

    private function extract_top_priority_orders($rows)
    {
        $items = array();

        for ($row = 7; $row <= 80 && count($items) < 10; $row++) {
            $order = $this->grid_cell($rows, $row, 'Y');
            if ($order === '') {
                continue;
            }

            $items[] = array(
                'order' => $order,
                'style' => $this->grid_cell($rows, $row, 'Z'),
                'delivery' => $this->format_excel_date($this->grid_cell($rows, $row, 'AA')),
                'qty_pdk' => $this->parse_number($this->grid_cell($rows, $row, 'AB')),
                'qty_ready' => $this->parse_number($this->grid_cell($rows, $row, 'AC')),
                'qty_out_aps' => $this->parse_number($this->grid_cell($rows, $row, 'AD')),
                'qty_out_engage' => $this->parse_number($this->grid_cell($rows, $row, 'AE')),
            );
        }

        return $items;
    }

    private function heat_rpa_sources()
    {
        $root = $this->root_path();
        $aps_dir = $root . DIRECTORY_SEPARATOR . 'rpa' . DIRECTORY_SEPARATOR . 'aps-rpa' . DIRECTORY_SEPARATOR . 'downloads';
        $accessories_dir = $root . DIRECTORY_SEPARATOR . 'rpa' . DIRECTORY_SEPARATOR . 'accessories-rpa' . DIRECTORY_SEPARATOR . 'downloads';
        $engage_dir = $root . DIRECTORY_SEPARATOR . 'rpa' . DIRECTORY_SEPARATOR . 'engage-rpa' . DIRECTORY_SEPARATOR . 'downloads';

        return array(
            'aps' => $this->latest_matching_file(array(
                $aps_dir . DIRECTORY_SEPARATOR . 'JO_*.xlsx',
                $aps_dir . DIRECTORY_SEPARATOR . 'JO_*.xls',
            )),
            'accessories' => $this->latest_matching_file(array(
                $accessories_dir . DIRECTORY_SEPARATOR . 'CONTROLIST_*.xlsx',
                $accessories_dir . DIRECTORY_SEPARATOR . 'CONTROLIST_*.xls',
            )),
            'engage_32_inflow' => $this->latest_existing_file(array(
                $engage_dir . DIRECTORY_SEPARATOR . '32_inflow.xlsx',
                $engage_dir . DIRECTORY_SEPARATOR . '32_inflow.xls',
            )),
            'engage_32a_inflow' => $this->latest_existing_file(array(
                $engage_dir . DIRECTORY_SEPARATOR . '32a_inflow.xlsx',
                $engage_dir . DIRECTORY_SEPARATOR . '32a_inflow.xls',
            )),
            'engage_32a_outflow' => $this->latest_existing_file(array(
                $engage_dir . DIRECTORY_SEPARATOR . '32a_outflow.xlsx',
                $engage_dir . DIRECTORY_SEPARATOR . '32a_outflow.xls',
            )),
        );
    }

    private function latest_matching_file($pattern)
    {
        $patterns = is_array($pattern) ? $pattern : array($pattern);
        $files = array();
        foreach ($patterns as $item) {
            $files = array_merge($files, glob($item) ?: array());
        }
        $files = array_filter($files, 'is_file');
        if (!$files) {
            return NULL;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        return $files[0];
    }

    private function latest_existing_file($paths)
    {
        $files = array_filter($paths, 'is_file');
        if (!$files) {
            return isset($paths[0]) ? $paths[0] : NULL;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        return $files[0];
    }

    private function latest_mtime_iso($sources)
    {
        $mtime = 0;
        foreach ($sources as $path) {
            if ($path && is_file($path)) {
                $mtime = max($mtime, filemtime($path));
            }
        }

        return $mtime ? date('c', $mtime) : date('c');
    }

    private function source_status_rows($sources)
    {
        $rows = array();
        foreach ($sources as $key => $path) {
            $rows[] = array(
                'key' => $key,
                'path' => $path,
                'exists' => $path && is_file($path),
                'updated_at' => $path && is_file($path) ? date('c', filemtime($path)) : NULL,
            );
        }
        return $rows;
    }

    private function read_combined_engage_outflow_report($current_path)
    {
        $current = $this->read_html_report($current_path);
        if (!$current['headers']) {
            return $current;
        }

        $patterns = array();
        foreach ($this->engage_rpa_history_roots() as $engage_root) {
            $patterns[] = $engage_root . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '32a_outflow*.xlsx';
            $patterns[] = $engage_root . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '32a_outflow*.xls';
            $patterns[] = $engage_root . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '32a_outflow*.xlsx';
            $patterns[] = $engage_root . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '32a_outflow*.xls';
            $patterns[] = $engage_root . DIRECTORY_SEPARATOR . 'downloads' . DIRECTORY_SEPARATOR . 'periods' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '32a_outflow.xlsx';
            $patterns[] = $engage_root . DIRECTORY_SEPARATOR . 'downloads' . DIRECTORY_SEPARATOR . 'periods' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '32a_outflow.xls';
        }

        $rows = $current['rows'];
        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: array() as $path) {
                if (!is_file($path)) {
                    continue;
                }
                $report = $this->read_html_report($path);
                if ($report['headers']) {
                    $rows = array_merge($rows, $report['rows']);
                }
            }
        }

        return array('headers' => $current['headers'], 'rows' => $rows);
    }

    private function engage_rpa_history_roots()
    {
        $root = $this->root_path();
        $roots = array(
            $root . DIRECTORY_SEPARATOR . 'rpa' . DIRECTORY_SEPARATOR . 'engage-rpa',
        );

        return array_values(array_unique(array_filter($roots, 'is_dir')));
    }

    private function build_heat_data_from_rpa_sources($aps, $inflow_32a, $outflow_32a, $accessories)
    {
        $aps_index = $this->header_index($aps['headers']);
        $orders = array();
        $periods = array();
        $pdk_by_period = array();
        $output_by_period = array();

        foreach ($aps['rows'] as $row) {
            $route = strtoupper($this->cell($row, $aps_index, 'Process Route'));
            if (!$this->is_heat_rpa_route($route)) {
                continue;
            }

            $jo = $this->cell($row, $aps_index, 'JO');
            $order = $this->normalize_order_number($jo);
            if ($order === '') {
                continue;
            }

            $qty = $this->parse_number($this->cell($row, $aps_index, 'Plan Qty'));
            if ($qty <= 0) {
                $qty = $this->parse_number($this->cell($row, $aps_index, 'Qty'));
            }
            if ($qty <= 0) {
                continue;
            }

            $delivery = $this->cell($row, $aps_index, 'Delivery Date');
            $period = $this->period_label_from_date_value($delivery);
            if ($period === '') {
                continue;
            }

            $finished = $this->parse_number($this->cell($row, $aps_index, 'Finished Qty'));
            $style = $this->cell($row, $aps_index, 'Factory Style');
            if ($style === '') {
                $style = $this->cell($row, $aps_index, 'Cust. Style');
            }

            $this->ensure_period_bucket($periods, $period);
            $pdk_by_period[$period] = isset($pdk_by_period[$period]) ? $pdk_by_period[$period] + $qty : $qty;
            $output_by_period[$period] = isset($output_by_period[$period]) ? $output_by_period[$period] + $finished : $finished;

            if (!isset($orders[$order])) {
                $orders[$order] = array(
                    'order' => $order,
                    'style' => $style,
                    'delivery' => $delivery,
                    'period' => $period,
                    'qty_pdk' => 0,
                    'qty_out_aps' => 0,
                );
            }
            $orders[$order]['qty_pdk'] += $qty;
            $orders[$order]['qty_out_aps'] += $finished;
        }

        $in_summary = $this->summarize_engage_rows_by_order($inflow_32a);
        $out_summary = $this->summarize_engage_rows_by_order($outflow_32a);
        $accessories_ready = $this->summarize_accessories_completed_orders($accessories);

        $ready_by_period = array();
        $ready_by_order = array();
        foreach ($in_summary['orders'] as $order => $in) {
            $out_qty = isset($out_summary['orders'][$order]) ? $out_summary['orders'][$order]['qty'] : 0;
            $ready_qty = $in['qty'] - $out_qty;
            if ($ready_qty <= 0) {
                continue;
            }

            $period = isset($orders[$order]) ? $orders[$order]['period'] : $this->period_label_from_date_value($in['date']);
            if ($period === '') {
                continue;
            }

            $this->ensure_period_bucket($periods, $period);
            $ready_by_period[$period] = isset($ready_by_period[$period]) ? $ready_by_period[$period] + $ready_qty : $ready_qty;
            $ready_by_order[$order] = array(
                'order' => $order,
                'style' => isset($orders[$order]) && $orders[$order]['style'] !== '' ? $orders[$order]['style'] : $in['style'],
                'delivery' => isset($orders[$order]) ? $orders[$order]['delivery'] : $in['date'],
                'period' => $period,
                'qty' => $ready_qty,
                'accessories_completed' => isset($accessories_ready[$order]) ? $accessories_ready[$order] : 0,
            );
        }

        $period_labels = array_keys($periods);
        usort($period_labels, array($this, 'compare_period_labels'));

        $all_qty_rows = array();
        foreach ($period_labels as $period) {
            $pdk = isset($pdk_by_period[$period]) ? $pdk_by_period[$period] : 0;
            $output = isset($output_by_period[$period]) ? $output_by_period[$period] : 0;
            if ($pdk <= 0 && $output <= 0) {
                continue;
            }
            $all_qty_rows[] = array('label' => $period, 'pdk' => $pdk, 'output' => $output);
        }

        $current_index = 0;
        foreach ($all_qty_rows as $index => $row) {
            $ready = isset($ready_by_period[$row['label']]) ? $ready_by_period[$row['label']] : 0;
            if (($row['pdk'] - $row['output']) > 0 || $ready > 0) {
                $current_index = $index;
                break;
            }
        }
        $qty_pdk_vs_output = array_slice($all_qty_rows, $current_index, 4);

        $ready_to_load = array();
        foreach ($period_labels as $period) {
            $ready = isset($ready_by_period[$period]) ? $ready_by_period[$period] : 0;
            if ($ready > 0 || $this->period_is_near_slice($period, $qty_pdk_vs_output)) {
                $ready_to_load[] = array('label' => $period, 'ready' => $ready);
            }
        }
        $ready_to_load = array_slice($ready_to_load, 0, 7);

        $total_pdk = array_sum(array_column($qty_pdk_vs_output, 'pdk'));
        $total_output = array_sum(array_column($qty_pdk_vs_output, 'output'));
        $balance_qty = max(0, $total_pdk - $total_output);
        $prod_days_left = $this->source_prod_days_left($qty_pdk_vs_output);
        $daily_capacity = $this->source_daily_capacity_detail($qty_pdk_vs_output);
        $output_vs_capacity = $this->build_output_vs_capacity_from_engage_daily($out_summary['daily'], $daily_capacity, $in_summary['daily']);

        return array(
            'total_pdk' => $total_pdk,
            'total_output' => $total_output,
            'balance_qty' => $balance_qty,
            'prod_days_left' => $prod_days_left,
            'qty_pdk_vs_output' => $qty_pdk_vs_output,
            'ready_to_load' => $ready_to_load,
            'output_vs_capacity' => $output_vs_capacity,
            'top_priority_orders' => $this->build_priority_orders_from_rpa($ready_by_order, $orders, $out_summary['orders']),
        );
    }

    private function is_heat_rpa_route($route)
    {
        return strpos($route, 'HT') !== FALSE;
    }

    private function normalize_order_number($value)
    {
        $value = $this->normalize($value);
        if (preg_match('/\d{10}-\d+/', $value, $match)) {
            return $match[0];
        }
        return $value;
    }

    private function period_label_from_date_value($value)
    {
        $timestamp = $this->parse_date_timestamp($value);
        if (!$timestamp) {
            return '';
        }
        $month = (int) date('n', $timestamp);
        $label = isset($this->months[$month - 1]) ? $this->months[$month - 1] : date('M', $timestamp);
        return ((int) date('j', $timestamp) <= 15 ? 'MID ' : 'END ') . $label;
    }

    private function parse_date_timestamp($value)
    {
        $value = $this->normalize($value);
        if ($value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            return ((float) $value - 25569) * 86400;
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $match)) {
            return strtotime($match[3] . '-' . $match[2] . '-' . $match[1]);
        }
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $value, $match)) {
            return strtotime($match[1] . '-' . $match[2] . '-' . $match[3]);
        }
        return strtotime($value) ?: 0;
    }

    private function summarize_engage_rows_by_order($report)
    {
        $index = $this->header_index($report['headers']);
        $orders = array();
        $daily = array();
        $seen = array();
        $calendar_days = $this->dashboard_calendar_days();

        foreach ($report['rows'] as $row) {
            if ($this->normalize($this->cell($row, $index, 'Text')) !== '[CSDB]-Transfer ~bundle_receive') {
                continue;
            }

            $date = $this->cell($row, $index, 'Date');
            $timestamp = $this->parse_date_timestamp($date);
            $order = $this->normalize_order_number($this->cell($row, $index, 'Prod. Nr'));
            if ($order === '') {
                $order = $this->normalize_order_number($this->cell($row, $index, 'Cost Center'));
            }
            if ($order === '') {
                $order = $this->normalize_order_number($this->cell($row, $index, 'Udef 8'));
            }
            $qty = abs($this->parse_number($this->cell($row, $index, 'Qty')));
            if ($order === '' || $qty <= 0) {
                continue;
            }

            $item = $this->cell($row, $index, 'Item Nr');
            $identity = $date . "\n" . $order . "\n" . $item . "\n" . $qty . "\n" . $this->cell($row, $index, 'Udef 10');
            if (isset($seen[$identity])) {
                continue;
            }
            $seen[$identity] = TRUE;

            if (!isset($orders[$order])) {
                $orders[$order] = array(
                    'qty' => 0,
                    'style' => $this->cell($row, $index, 'Udef 1'),
                    'date' => $date,
                );
            }
            $orders[$order]['qty'] += $qty;

            if ($timestamp) {
                $day = date('Y-m-d', $timestamp);
                $is_holiday = in_array($day, $calendar_days['holidays'], TRUE);
                $is_sunday = (int) date('w', $timestamp) === 0;
                $is_scheduled_sunday = in_array($day, $calendar_days['work_days'], TRUE) || in_array($day, $calendar_days['half_days'], TRUE);
                if (!$is_holiday && (!$is_sunday || $is_scheduled_sunday)) {
                    $daily[$day] = isset($daily[$day]) ? $daily[$day] + $qty : $qty;
                }
            }
        }

        return array('orders' => $orders, 'daily' => $daily);
    }

    private function summarize_accessories_completed_orders($report)
    {
        if (empty($report['headers'])) {
            return array();
        }

        $index = $this->header_index($report['headers']);
        $orders = array();
        foreach ($report['rows'] as $row) {
            $order = $this->normalize_order_number($this->cell($row, $index, 'Order'));
            $status = strtoupper($this->cell($row, $index, 'Status Pesanan'));
            if ($order === '' || strpos($status, 'COMPLETED') === FALSE) {
                continue;
            }
            $orders[$order] = isset($orders[$order]) ? $orders[$order] + 1 : 1;
        }
        return $orders;
    }

    private function build_output_vs_capacity_from_engage_daily($daily, $daily_capacity, $daily_input = array())
    {
        ksort($daily);
        $latest_day = $daily ? max(array_keys($daily)) : date('Y-m-d');
        $calendar_days = $this->dashboard_calendar_days();
        $days = array();
        $cursor = strtotime($latest_day);
        while (count($days) < 6 && $cursor) {
            $day = date('Y-m-d', $cursor);
            if ($this->calendar_workday_value($day, $calendar_days) > 0) {
                array_unshift($days, $day);
            }
            $cursor = strtotime('-1 day', $cursor);
        }

        $capacity_history = $this->read_heat_capacity_history();
        $capacity_history_changed = FALSE;
        $capacity_value = is_array($daily_capacity) && isset($daily_capacity['capacity']) ? $daily_capacity['capacity'] : $daily_capacity;
        $capacity_breakdown = is_array($daily_capacity) && isset($daily_capacity['breakdown']) ? $daily_capacity['breakdown'] : array();
        $items = array();

        foreach ($days as $day) {
            $capacity_entry = $this->capacity_for_output_day($day, $capacity_value, $capacity_breakdown, $capacity_history, $capacity_history_changed);
            $items[] = array(
                'label' => date('d M Y', strtotime($day)),
                'output' => isset($daily[$day]) ? $daily[$day] : 0,
                'input' => isset($daily_input[$day]) ? $daily_input[$day] : 0,
                'capacity' => $capacity_entry['capacity'],
                'capacity_captured_at' => isset($capacity_entry['captured_at']) ? $capacity_entry['captured_at'] : NULL,
                'capacity_breakdown' => isset($capacity_entry['breakdown']) ? $capacity_entry['breakdown'] : array(),
            );
        }

        if ($capacity_history_changed) {
            $this->write_heat_capacity_history($capacity_history);
        }

        return $items;
    }

    private function build_output_vs_capacity_from_rpa($balance_qty, $prod_days_left, $fallback)
    {
        $sources = $this->heat_rpa_sources();
        if (empty($sources['engage_32a_outflow']) || !is_file($sources['engage_32a_outflow'])) {
            return $fallback;
        }

        $outflow = $this->read_combined_engage_outflow_report($sources['engage_32a_outflow']);
        if (!$outflow['headers']) {
            return $fallback;
        }

        $summary = $this->summarize_engage_rows_by_order($outflow);
        $daily_capacity = $prod_days_left > 0 ? array(
            'capacity' => $balance_qty / $prod_days_left,
            'breakdown' => array(array(
                'label' => 'Total',
                'pdk' => $balance_qty,
                'output' => 0,
                'balance' => $balance_qty,
                'days_left' => $prod_days_left,
                'calendar_days_left' => $prod_days_left,
                'daily_capacity' => $balance_qty / $prod_days_left,
            )),
        ) : array('capacity' => 0, 'breakdown' => array());
        $input_summary = array('daily' => array());
        if (!empty($sources['engage_32a_inflow']) && is_file($sources['engage_32a_inflow'])) {
            $inflow = $this->read_html_report($sources['engage_32a_inflow']);
            if ($inflow['headers']) {
                $input_summary = $this->summarize_engage_rows_by_order($inflow);
            }
        }

        $items = $this->build_output_vs_capacity_from_engage_daily($summary['daily'], $daily_capacity, $input_summary['daily']);
        return $items ? $items : $fallback;
    }

    private function build_priority_orders_from_rpa($ready_by_order, $orders, $out_orders)
    {
        $items = array();
        foreach ($ready_by_order as $order => $ready) {
            $order_data = isset($orders[$order]) ? $orders[$order] : array();
            $items[] = array(
                'order' => $order,
                'style' => isset($order_data['style']) && $order_data['style'] !== '' ? $order_data['style'] : $ready['style'],
                'delivery' => $this->format_display_date(isset($order_data['delivery']) ? $order_data['delivery'] : $ready['delivery']),
                'qty_pdk' => isset($order_data['qty_pdk']) ? $order_data['qty_pdk'] : 0,
                'qty_ready' => $ready['qty'],
                'qty_out_aps' => isset($order_data['qty_out_aps']) ? $order_data['qty_out_aps'] : 0,
                'qty_out_engage' => isset($out_orders[$order]) ? $out_orders[$order]['qty'] : 0,
                'accessories_completed' => isset($ready['accessories_completed']) ? $ready['accessories_completed'] : 0,
                '_sort_delivery' => $this->parse_date_timestamp(isset($order_data['delivery']) ? $order_data['delivery'] : $ready['delivery']),
            );
        }

        usort($items, function ($a, $b) {
            if ($a['_sort_delivery'] == $b['_sort_delivery']) {
                return $b['qty_ready'] <=> $a['qty_ready'];
            }
            return $a['_sort_delivery'] <=> $b['_sort_delivery'];
        });

        $items = array_slice($items, 0, 10);
        foreach ($items as &$item) {
            unset($item['_sort_delivery']);
        }
        unset($item);

        return $items;
    }

    private function format_display_date($value)
    {
        $timestamp = $this->parse_date_timestamp($value);
        return $timestamp ? date('d M Y', $timestamp) : $value;
    }

    private function build_heat_data_from_database_delivery($database_rows, $delivery_rows)
    {
        $delivery_cols = array(
            'order' => $this->column_letters_to_index('G'),
            'style' => $this->column_letters_to_index('E'),
            'qty' => $this->grid_header_column($delivery_rows, 'Qty', 'K'),
            'delivery_date' => $this->grid_header_column($delivery_rows, 'Delivery Date', 'O'),
            'route' => $this->grid_header_column($delivery_rows, 'Process Route', 'R'),
            'period' => $this->grid_header_column($delivery_rows, 'DELIVERY', 'V'),
            'output' => $this->grid_header_column($delivery_rows, 'QTY OUTPUT', 'W'),
            'status' => $this->grid_header_column($delivery_rows, 'STATUS', 'X'),
        );
        $database_cols = array(
            'key' => $this->grid_header_column($database_rows, 'KEY', 'A'),
            'order' => $this->grid_header_column($database_rows, 'ORDER', 'I'),
            'style' => $this->grid_header_column($database_rows, 'STYLE', 'J'),
            'status_heat' => $this->grid_header_column($database_rows, 'STATUS HEAT', 'O'),
            'qty_pcs' => $this->grid_header_column($database_rows, 'QTY PCS', 'V'),
            'out' => $this->grid_header_column($database_rows, 'OUT PENGIRIMAN', 'Y'),
            'delivery_date' => $this->grid_header_column($database_rows, 'TGL DELIVERY', 'Z'),
            'period' => $this->grid_header_column($database_rows, 'DELIVERY', 'AC'),
        );

        $delivery_by_order = array();
        $periods = array();
        $pdk_by_period = array();
        $output_by_period = array();

        foreach (array_slice($delivery_rows, 1) as $row) {
            $order = $this->grid_value($row, $delivery_cols['order']);
            $qty = $this->parse_number($this->grid_value($row, $delivery_cols['qty']));
            $period = $this->normalize_period_label($this->grid_value($row, $delivery_cols['period']));
            $route = strtoupper($this->grid_value($row, $delivery_cols['route']));
            $status = strtoupper($this->grid_value($row, $delivery_cols['status']));

            if ($period === '') {
                $period = $this->period_label_from_excel_date($this->grid_value($row, $delivery_cols['delivery_date']));
            }
            if ($period === '' || $qty <= 0 || !$this->is_heat_transfer_delivery($route, $status)) {
                continue;
            }

            $this->ensure_period_bucket($periods, $period);
            $pdk_by_period[$period] = isset($pdk_by_period[$period]) ? $pdk_by_period[$period] + $qty : $qty;
            $output = $this->parse_number($this->grid_value($row, $delivery_cols['output']));
            $output_by_period[$period] = isset($output_by_period[$period]) ? $output_by_period[$period] + $output : $output;

            if ($order !== '' && !isset($delivery_by_order[$order])) {
                $delivery_by_order[$order] = array(
                    'order' => $order,
                    'style' => $this->grid_value($row, $delivery_cols['style']),
                    'delivery' => $this->grid_value($row, $delivery_cols['delivery_date']),
                    'period' => $period,
                    'qty_pdk' => $qty,
                    'qty_out_aps' => $output,
                );
            } elseif ($order !== '') {
                $delivery_by_order[$order]['qty_pdk'] += $qty;
                $delivery_by_order[$order]['qty_out_aps'] += $output;
            }
        }

        $ready_keys = array();
        $output_keys = array();
        $daily_output_keys = array();
        foreach (array_slice($database_rows, 2) as $row) {
            $key = $this->grid_value($row, $database_cols['key']);
            $order = $this->grid_value($row, $database_cols['order']);
            $period = $this->normalize_period_label($this->grid_value($row, $database_cols['period']));
            $qty = $this->parse_number($this->grid_value($row, $database_cols['qty_pcs']));
            $out_value = $this->grid_value($row, $database_cols['out']);
            $status_heat = strtoupper($this->grid_value($row, $database_cols['status_heat']));

            if ($key === '' || $order === '' || $period === '' || $qty <= 0) {
                continue;
            }

            $this->ensure_period_bucket($periods, $period);
            if ($this->is_database_out($out_value)) {
                $output_key = $order . "\n" . $key;
                $output_keys[$output_key] = array(
                    'order' => $order,
                    'qty' => max(isset($output_keys[$output_key]['qty']) ? $output_keys[$output_key]['qty'] : 0, $qty),
                );

                $day_serial = $this->excel_date_serial($out_value);
                if ($day_serial > 0) {
                    $daily_key = $day_serial . "\n" . $key;
                    $daily_output_keys[$daily_key] = array(
                        'day' => $day_serial,
                        'qty' => max(isset($daily_output_keys[$daily_key]['qty']) ? $daily_output_keys[$daily_key]['qty'] : 0, $qty),
                    );
                }
            } elseif (strpos($status_heat, 'INCOMPLITED') === FALSE) {
                $ready_key = $period . "\n" . $key;
                $ready_keys[$ready_key] = array(
                    'period' => $period,
                    'order' => $order,
                    'style' => $this->grid_value($row, $database_cols['style']),
                    'delivery' => $this->grid_value($row, $database_cols['delivery_date']),
                    'qty' => max(isset($ready_keys[$ready_key]['qty']) ? $ready_keys[$ready_key]['qty'] : 0, $qty),
                );
            }
        }

        if (!array_filter($output_by_period)) {
            foreach ($output_keys as $item) {
                $order = $item['order'];
                if (!isset($delivery_by_order[$order])) {
                    continue;
                }
                $period = $delivery_by_order[$order]['period'];
                $output_by_period[$period] = isset($output_by_period[$period]) ? $output_by_period[$period] + $item['qty'] : $item['qty'];
            }
        }

        $ready_by_period = array();
        $ready_by_order = array();
        foreach ($ready_keys as $item) {
            $period = $item['period'];
            $ready_by_period[$period] = isset($ready_by_period[$period]) ? $ready_by_period[$period] + $item['qty'] : $item['qty'];
            $order = $item['order'];
            if (!isset($ready_by_order[$order])) {
                $ready_by_order[$order] = $item;
            } else {
                $ready_by_order[$order]['qty'] += $item['qty'];
            }
        }

        $period_labels = array_keys($periods);
        usort($period_labels, array($this, 'compare_period_labels'));

        $all_qty_rows = array();
        foreach ($period_labels as $period) {
            $pdk = isset($pdk_by_period[$period]) ? $pdk_by_period[$period] : 0;
            $output = isset($output_by_period[$period]) ? $output_by_period[$period] : 0;
            if ($pdk <= 0 && $output <= 0) {
                continue;
            }
            $all_qty_rows[] = array('label' => $period, 'pdk' => $pdk, 'output' => $output);
        }

        $current_index = 0;
        foreach ($all_qty_rows as $index => $row) {
            $ready = isset($ready_by_period[$row['label']]) ? $ready_by_period[$row['label']] : 0;
            if (($row['pdk'] - $row['output']) > 0 || $ready > 0) {
                $current_index = $index;
                break;
            }
        }
        $qty_pdk_vs_output = array_slice($all_qty_rows, $current_index, 4);

        $ready_to_load = array();
        foreach ($period_labels as $period) {
            $ready = isset($ready_by_period[$period]) ? $ready_by_period[$period] : 0;
            if ($ready > 0 || $this->period_is_near_slice($period, $qty_pdk_vs_output)) {
                $ready_to_load[] = array('label' => $period, 'ready' => $ready);
            }
        }
        $ready_to_load = array_slice($ready_to_load, 0, 7);

        $top_priority_orders = $this->build_priority_orders_from_source($ready_by_order, $delivery_by_order, $output_keys);
        $total_pdk = array_sum(array_column($qty_pdk_vs_output, 'pdk'));
        $total_output = array_sum(array_column($qty_pdk_vs_output, 'output'));
        $balance_qty = max(0, $total_pdk - $total_output);

        return array(
            'total_pdk' => $total_pdk,
            'total_output' => $total_output,
            'total_output_balance' => $total_output,
            'balance_qty' => $balance_qty,
            'prod_days_left' => $this->source_prod_days_left($qty_pdk_vs_output),
            'qty_pdk_vs_output' => $qty_pdk_vs_output,
            'ready_to_load' => $ready_to_load,
            'output_vs_capacity' => $this->build_output_vs_capacity_from_source($daily_output_keys, $this->source_daily_capacity_detail($qty_pdk_vs_output)),
            'top_priority_orders' => $top_priority_orders,
        );
    }

    private function grid_header_column($rows, $header, $fallback_column)
    {
        $fallback = $this->column_letters_to_index($fallback_column);
        $needle = strtolower($this->normalize($header));

        for ($row = 0; $row < min(3, count($rows)); $row++) {
            foreach ($rows[$row] as $index => $value) {
                if (strtolower($this->normalize($value)) === $needle) {
                    return $index;
                }
            }
        }

        return $fallback;
    }

    private function grid_value($row, $index)
    {
        return isset($row[$index]) ? $this->normalize($row[$index]) : '';
    }

    private function dedupe_grid_rows($rows)
    {
        if (count($rows) <= 2) {
            return $rows;
        }

        $result = array();
        $seen = array();

        foreach ($rows as $row_index => $row) {
            if ($row_index === 0) {
                $result[] = $row;
                continue;
            }

            $normalized = array();
            $last_value_index = -1;
            foreach ($row as $index => $value) {
                $cell = $this->normalize($value);
                $normalized[$index] = $cell;
                if ($cell !== '') {
                    $last_value_index = max($last_value_index, (int) $index);
                }
            }

            if ($last_value_index < 0) {
                continue;
            }

            $key_parts = array();
            for ($index = 0; $index <= $last_value_index; $index++) {
                $key_parts[] = isset($normalized[$index]) ? $normalized[$index] : '';
            }
            $key = md5(json_encode($key_parts));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = TRUE;
            $result[] = $row;
        }

        return $result;
    }

    private function ensure_period_bucket(&$periods, $period)
    {
        if ($period !== '' && !isset($periods[$period])) {
            $periods[$period] = TRUE;
        }
    }

    private function normalize_period_label($label)
    {
        $label = trim(preg_replace('/\s+/', ' ', $this->normalize($label)));
        if ($label === '') {
            return '';
        }

        $label = str_ireplace('Mei', 'May', $label);
        if (preg_match('/^(MID|END)\s+([A-Za-z]+)$/i', $label, $match)) {
            $month_index = $this->month_label_to_index($match[2]);
            if ($month_index !== NULL) {
                return strtoupper($match[1]) . ' ' . $this->months[$month_index];
            }
        }

        return $label;
    }

    private function is_heat_transfer_delivery($route, $status)
    {
        if ($status === 'HEAT TRANSFER') {
            return TRUE;
        }

        return strpos($route, 'HT') !== FALSE || strpos($route, 'HEAT') !== FALSE;
    }

    private function is_database_out($value)
    {
        $value = $this->normalize($value);
        return $value !== '' && strcasecmp($value, 'Belum Out') !== 0;
    }

    private function excel_date_serial($value)
    {
        if (!is_numeric($value)) {
            return 0;
        }

        return (int) floor((float) $value);
    }

    private function period_label_from_excel_date($value)
    {
        if (!is_numeric($value)) {
            return '';
        }

        $timestamp = (((float) $value) - 25569) * 86400;
        $day = (int) gmdate('j', (int) $timestamp);
        $month = (int) gmdate('n', (int) $timestamp);
        if ($month < 1 || $month > 12) {
            return '';
        }

        return ($day <= 15 ? 'MID ' : 'END ') . $this->months[$month - 1];
    }

    private function compare_period_labels($a, $b)
    {
        return $this->period_sort_key($a) <=> $this->period_sort_key($b);
    }

    private function period_sort_key($label)
    {
        $label = $this->normalize_period_label($label);
        if (!preg_match('/^(MID|END)\s+([A-Za-z]+)$/i', $label, $match)) {
            return PHP_INT_MAX;
        }

        $month_index = $this->month_label_to_index($match[2]);
        if ($month_index === NULL) {
            return PHP_INT_MAX;
        }

        $current_month = (int) date('n') - 1;
        $offset = ($month_index - $current_month + 12) % 12;
        return ($offset * 2) + (strtoupper($match[1]) === 'END' ? 1 : 0);
    }

    private function period_is_near_slice($period, $rows)
    {
        foreach ($rows as $row) {
            if (strcasecmp($period, $row['label']) === 0) {
                return TRUE;
            }
        }

        return FALSE;
    }

    private function source_prod_days_left($qty_rows)
    {
        if (!$qty_rows) {
            return 0;
        }

        $current = $this->current_running_period(array_map(function ($row) {
            return array(
                'label' => $row['label'],
                'pdk' => $row['pdk'],
                'output' => $row['output'],
                'balance' => max(0, $row['pdk'] - $row['output']),
                'ready' => 0,
            );
        }, $qty_rows));

        if ($current) {
            return $this->auto_remaining_workdays($current['label']);
        }

        return 0;
    }

    private function source_daily_capacity($qty_rows)
    {
        $detail = $this->source_daily_capacity_detail($qty_rows);
        return $detail['capacity'];
    }

    private function source_daily_capacity_detail($qty_rows)
    {
        $capacity = 0;
        $breakdown = array();

        foreach ($qty_rows ?: array() as $row) {
            $balance = max(0, (isset($row['pdk']) ? $row['pdk'] : 0) - (isset($row['output']) ? $row['output'] : 0));
            if ($balance <= 0 || empty($row['label'])) {
                continue;
            }

            $calendar = $this->build_period_calendar($row['label']);
            $days_left = isset($calendar['export_remaining_workdays'])
                ? $calendar['export_remaining_workdays']
                : (isset($calendar['remaining_workdays']) ? $calendar['remaining_workdays'] : 0);

            if ($days_left > 0) {
                $daily = $balance / $days_left;
                $capacity += $daily;
                $breakdown[] = array(
                    'label' => $row['label'],
                    'pdk' => isset($row['pdk']) ? $row['pdk'] : 0,
                    'output' => isset($row['output']) ? $row['output'] : 0,
                    'balance' => $balance,
                    'days_left' => $days_left,
                    'calendar_days_left' => isset($calendar['remaining_workdays']) ? $calendar['remaining_workdays'] : $days_left,
                    'daily_capacity' => $daily,
                );
            }
        }

        return array('capacity' => $capacity, 'breakdown' => $breakdown);
    }

    private function auto_remaining_workdays($label)
    {
        $range = $this->period_date_range($label);
        if (!$range) {
            return 0;
        }

        $today = date('Y-m-d');
        $start = max($today, $range['start']);
        if ($start > $range['end']) {
            return 0;
        }

        return $this->export_workdays_from_remaining($this->count_workdays($start, $range['end'], $this->dashboard_calendar_days()));
    }

    private function build_output_vs_capacity_from_source($daily_output_keys, $daily_capacity, $daily_input_keys = array())
    {
        $daily = array();
        $calendar_days = $this->dashboard_calendar_days();
        $capacity_value = is_array($daily_capacity) && isset($daily_capacity['capacity']) ? $daily_capacity['capacity'] : $daily_capacity;
        $capacity_breakdown = is_array($daily_capacity) && isset($daily_capacity['breakdown']) ? $daily_capacity['breakdown'] : array();
        foreach ($daily_output_keys as $item) {
            $day = $item['day'];
            if ($this->calendar_workday_value($this->excel_serial_to_date($day), $calendar_days) > 0) {
                $daily[$day] = isset($daily[$day]) ? $daily[$day] + $item['qty'] : $item['qty'];
            }
        }

        $daily_input = array();
        foreach ($daily_input_keys as $item) {
            $day = $item['day'];
            if ($this->calendar_workday_value($this->excel_serial_to_date($day), $calendar_days) > 0) {
                $daily_input[$day] = isset($daily_input[$day]) ? $daily_input[$day] + $item['qty'] : $item['qty'];
            }
        }

        ksort($daily);
        $daily = array_slice($daily, -6, NULL, TRUE);
        $capacity_history = $this->read_heat_capacity_history();
        $capacity_history_changed = FALSE;
        $items = array();

        foreach ($daily as $serial => $output) {
            $date = $this->excel_serial_to_date($serial);
            $capacity_entry = $this->capacity_for_output_day($date, $capacity_value, $capacity_breakdown, $capacity_history, $capacity_history_changed);
            $items[] = array(
                'label' => $this->format_output_day_label_from_serial($serial),
                'output' => $output,
                'input' => isset($daily_input[$serial]) ? $daily_input[$serial] : 0,
                'capacity' => $capacity_entry['capacity'],
                'capacity_captured_at' => isset($capacity_entry['captured_at']) ? $capacity_entry['captured_at'] : NULL,
                'capacity_breakdown' => isset($capacity_entry['breakdown']) ? $capacity_entry['breakdown'] : array(),
            );
        }

        if ($capacity_history_changed) {
            $this->write_heat_capacity_history($capacity_history);
        }

        return $items;
    }

    private function format_output_day_label_from_serial($serial)
    {
        $timestamp = $this->excel_serial_to_timestamp($serial);
        $month = (int) gmdate('n', $timestamp);
        $month_label = $month >= 1 && $month <= 12 ? $this->months[$month - 1] : '';

        return trim(gmdate('j', $timestamp) . ' ' . $month_label . ' ' . gmdate('Y', $timestamp));
    }

    private function excel_serial_to_date($serial)
    {
        return gmdate('Y-m-d', $this->excel_serial_to_timestamp($serial));
    }

    private function excel_serial_to_timestamp($serial)
    {
        return ((int) $serial - 25569) * 86400;
    }

    private function build_priority_orders_from_source($ready_by_order, $delivery_by_order, $output_keys)
    {
        $engage_output = array();
        foreach ($output_keys as $item) {
            $order = $item['order'];
            $engage_output[$order] = isset($engage_output[$order]) ? $engage_output[$order] + $item['qty'] : $item['qty'];
        }

        $items = array();
        foreach ($ready_by_order as $order => $ready) {
            $delivery = isset($delivery_by_order[$order]) ? $delivery_by_order[$order] : array();
            $items[] = array(
                'order' => $order,
                'style' => isset($delivery['style']) && $delivery['style'] !== '' ? $delivery['style'] : $ready['style'],
                'delivery' => $this->format_excel_date(isset($delivery['delivery']) ? $delivery['delivery'] : $ready['delivery']),
                'qty_pdk' => isset($delivery['qty_pdk']) ? $delivery['qty_pdk'] : 0,
                'qty_ready' => $ready['qty'],
                'qty_out_aps' => isset($delivery['qty_out_aps']) ? $delivery['qty_out_aps'] : 0,
                'qty_out_engage' => isset($engage_output[$order]) ? $engage_output[$order] : 0,
                '_sort_delivery' => $this->parse_number(isset($delivery['delivery']) ? $delivery['delivery'] : $ready['delivery']),
            );
        }

        usort($items, function ($a, $b) {
            if ($a['_sort_delivery'] == $b['_sort_delivery']) {
                return $b['qty_ready'] <=> $a['qty_ready'];
            }
            return $a['_sort_delivery'] <=> $b['_sort_delivery'];
        });

        $items = array_slice($items, 0, 10);
        foreach ($items as &$item) {
            unset($item['_sort_delivery']);
        }

        return $items;
    }

    private function extract_latest_database_rows($rows, $limit = 5)
    {
        if (count($rows) < 3) {
            return array();
        }

        $headers = isset($rows[1]) ? $this->header_index($rows[1]) : array();
        $items = array();

        for ($i = count($rows) - 1; $i >= 2 && count($items) < $limit; $i--) {
            $row = $rows[$i];
            $order = isset($row[8]) ? $row[8] : '';
            $style = isset($row[9]) ? $row[9] : '';
            $item = isset($row[3]) ? $row[3] : '';

            if ($order === '' && $style === '' && $item === '') {
                continue;
            }

            $items[] = array(
                'date' => $this->format_excel_datetime(isset($row[1]) ? $row[1] : ''),
                'order' => $order,
                'style' => $style,
                'item' => $item,
                'color' => isset($row[4]) ? $row[4] : '',
                'qty' => $this->parse_number(isset($row[6]) ? $row[6] : ''),
                'qty_pcs' => $this->parse_number(isset($row[21]) ? $row[21] : ''),
                'pot' => isset($row[11]) ? $row[11] : '',
                'bundle' => isset($row[12]) ? $row[12] : '',
                'status_heat' => isset($row[14]) ? $row[14] : '',
                'scan_in' => $this->format_excel_datetime(isset($row[22]) ? $row[22] : ''),
                'out_pengiriman' => $this->format_excel_datetime(isset($row[24]) ? $row[24] : ''),
                'delivery' => $this->format_excel_date(isset($row[25]) ? $row[25] : ''),
                'period' => isset($row[28]) ? $row[28] : '',
                'pengambil' => isset($row[30]) ? $row[30] : '',
            );
        }

        return $items;
    }

    private function extract_latest_database_rows_from_file($path, $limit = 5)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== TRUE) {
            return array();
        }

        $shared_strings = $this->read_xlsx_shared_strings($zip);
        $sheet_path = $this->xlsx_sheet_path_by_name($zip, 'DATABASE');
        $sheet_xml = $sheet_path ? $zip->getFromName($sheet_path) : FALSE;
        $zip->close();

        if ($sheet_xml === FALSE) {
            return array();
        }

        $xml = simplexml_load_string($sheet_xml);
        if (!$xml) {
            return array();
        }

        $latest_rows = array();
        foreach ($xml->sheetData->row as $row_node) {
            $row_number = (int) $row_node['r'];
            if ($row_number <= 2) {
                continue;
            }

            $row = array();
            foreach ($row_node->c as $cell_node) {
                $ref = (string) $cell_node['r'];
                $index = $this->xlsx_column_index($ref);
                $row[$index] = $this->normalize($this->xlsx_cell_value($cell_node, $shared_strings));
            }

            if (!$this->row_has_value($row)) {
                continue;
            }

            $latest_rows[] = $this->database_row_summary($row);
            if (count($latest_rows) > $limit) {
                array_shift($latest_rows);
            }
        }

        return array_reverse($latest_rows);
    }

    private function database_row_summary($row)
    {
        return array(
            'date' => $this->format_excel_datetime(isset($row[1]) ? $row[1] : ''),
            'order' => isset($row[8]) ? $row[8] : '',
            'style' => isset($row[9]) ? $row[9] : '',
            'item' => isset($row[3]) ? $row[3] : '',
            'color' => isset($row[4]) ? $row[4] : '',
            'qty' => $this->parse_number(isset($row[6]) ? $row[6] : ''),
            'qty_pcs' => $this->parse_number(isset($row[21]) ? $row[21] : ''),
            'pot' => isset($row[11]) ? $row[11] : '',
            'bundle' => isset($row[12]) ? $row[12] : '',
            'status_heat' => isset($row[14]) ? $row[14] : '',
            'scan_in' => $this->format_excel_datetime(isset($row[22]) ? $row[22] : ''),
            'out_pengiriman' => $this->format_excel_datetime(isset($row[24]) ? $row[24] : ''),
            'delivery' => $this->format_excel_date(isset($row[25]) ? $row[25] : ''),
            'period' => isset($row[28]) ? $row[28] : '',
            'pengambil' => isset($row[30]) ? $row[30] : '',
        );
    }

    private function build_management_analytics($total_pdk, $total_output_balance, $balance_qty, $prod_days_left, $qty_rows, $ready_rows, $capacity_rows, $priority_orders)
    {
        $data_accuracy = $this->build_data_accuracy($qty_rows, $ready_rows);
        $current_period = isset($data_accuracy['current_period']) ? $data_accuracy['current_period'] : NULL;
        $total_chart_pdk = array_sum(array_column($qty_rows, 'pdk'));
        $total_chart_output = array_sum(array_column($qty_rows, 'output'));
        $pdk_base = $current_period && $current_period['pdk'] > 0 ? $current_period['pdk'] : ($total_pdk > 0 ? $total_pdk : $total_chart_pdk);
        $output_base = $current_period ? $current_period['output'] : ($total_output_balance > 0 ? $total_output_balance : $total_chart_output);
        $achievement_rate = $pdk_base > 0 ? ($output_base / $pdk_base) * 100 : 0;

        $total_ready = array_sum(array_column($ready_rows, 'ready'));
        $total_capacity = array_sum(array_column($capacity_rows, 'capacity'));
        $total_daily_output = array_sum(array_column($capacity_rows, 'output'));
        $capacity_days = count(array_filter($capacity_rows, function ($row) {
            return $row['capacity'] > 0 || $row['output'] > 0;
        }));
        $avg_daily_output = $capacity_days ? $total_daily_output / $capacity_days : 0;
        $avg_daily_capacity = $capacity_days ? $total_capacity / $capacity_days : 0;
        $capacity_utilization = $total_capacity > 0 ? ($total_daily_output / $total_capacity) * 100 : 0;
        $capacity_gap = $total_capacity - $total_daily_output;
        $ready_coverage_days = $avg_daily_capacity > 0 ? $total_ready / $avg_daily_capacity : 0;
        $period_calendar = $this->build_current_period_calendar($current_period);
        $current_balance_qty = $current_period ? $current_period['balance'] : $balance_qty;
        $effective_days_left = isset($period_calendar['export_remaining_workdays']) ? $period_calendar['export_remaining_workdays'] : $prod_days_left;
        $period_required_daily_output = $effective_days_left > 0 ? ceil($current_balance_qty / $effective_days_left) : 0;
        $required_daily_output = $period_required_daily_output;

        $critical_orders = 0;
        foreach ($priority_orders as $order) {
            if ($this->delivery_workdays_left($order['delivery']) <= 5) {
                $critical_orders++;
            }
        }

        $insights = array();
        $capacity_gap_text = $capacity_gap >= 0
            ? 'gap ' . $this->format_compact_number($capacity_gap) . ' pcs'
            : 'surplus ' . $this->format_compact_number(abs($capacity_gap)) . ' pcs';

        $insights[] = $this->analytics_insight(
            $achievement_rate >= 90 ? 'good' : ($achievement_rate >= 75 ? 'watch' : 'risk'),
            'Achievement output',
            'Output sudah mencapai ' . $this->format_percent($achievement_rate) . ' dari total PDK.'
        );
        $insights[] = $this->analytics_insight(
            $ready_coverage_days >= 10 ? 'good' : ($ready_coverage_days >= 5 ? 'watch' : 'risk'),
            'Coverage ready load',
            'Stok ready setara ' . number_format($ready_coverage_days, 1) . ' hari kapasitas.'
        );
        $insights[] = $this->analytics_insight(
            $critical_orders > 0 ? 'risk' : 'good',
            'Critical orders',
            $critical_orders > 0 ? $critical_orders . ' order prioritas berada di horizon 5 hari.' : 'Tidak ada order prioritas dalam horizon 5 hari.'
        );
        $insights[] = $this->analytics_insight(
            $data_accuracy['status'],
            'Akurasi urutan data',
            $data_accuracy['message']
        );
        $action_plan = $this->build_management_action_plan(
            $achievement_rate,
            $ready_coverage_days,
            $required_daily_output,
            $avg_daily_output,
            $critical_orders,
            $data_accuracy
        );
        $overall_condition = $this->build_overall_condition(
            $achievement_rate,
            $ready_coverage_days,
            $period_required_daily_output,
            $avg_daily_output,
            $avg_daily_capacity,
            $critical_orders,
            $data_accuracy,
            $current_period,
            $period_calendar
        );

        return array(
            'overall_condition' => $overall_condition,
            'metrics' => array(
                array('label' => 'Output Achievement', 'value' => $this->floor_decimal($achievement_rate, 2), 'suffix' => '%', 'status' => $achievement_rate >= 90 ? 'good' : ($achievement_rate >= 75 ? 'watch' : 'risk')),
                array('label' => 'Ready Coverage', 'value' => round($ready_coverage_days, 1), 'suffix' => 'Days', 'status' => $ready_coverage_days >= 10 ? 'good' : ($ready_coverage_days >= 5 ? 'watch' : 'risk')),
                array('label' => 'Req. Daily Output', 'value' => round($required_daily_output), 'suffix' => 'Pcs/Day', 'status' => $avg_daily_output >= $required_daily_output ? 'good' : ($avg_daily_output >= ($required_daily_output * 0.9) ? 'watch' : 'risk')),
                array('label' => 'Data Accuracy', 'value' => $data_accuracy['score'], 'suffix' => '%', 'status' => $data_accuracy['status']),
            ),
            'summary' => array(
                'total_ready' => $total_ready,
                'avg_daily_output' => $avg_daily_output,
                'avg_daily_capacity' => $avg_daily_capacity,
                'capacity_gap' => $capacity_gap,
                'critical_orders' => $critical_orders,
                'data_accuracy_score' => $data_accuracy['score'],
                'sequence_issues' => count($data_accuracy['issues']),
            ),
            'details' => array(
                'output' => array(
                    'total_pdk' => $pdk_base,
                    'total_output' => $output_base,
                    'balance_qty' => $current_period ? $current_period['balance'] : $balance_qty,
                    'achievement_rate' => $achievement_rate,
                    'current_period' => $current_period,
                ),
                'ready' => array(
                    'total_ready' => $total_ready,
                    'avg_daily_capacity' => $avg_daily_capacity,
                    'coverage_days' => $ready_coverage_days,
                    'periods' => $ready_rows,
                ),
                'daily_requirement' => array(
                    'balance_qty' => $balance_qty,
                    'prod_days_left' => $prod_days_left,
                    'required_daily_output' => $required_daily_output,
                    'avg_daily_output' => $avg_daily_output,
                    'avg_daily_capacity' => $avg_daily_capacity,
                    'period_balance_qty' => $current_balance_qty,
                    'period_days_left' => $effective_days_left,
                    'period_required_daily_output' => $period_required_daily_output,
                    'period_calendar' => $period_calendar,
                ),
                'priority' => array(
                    'critical_orders' => $critical_orders,
                    'orders' => array_slice($priority_orders, 0, 5),
                ),
            ),
            'insights' => $insights,
            'data_accuracy' => $data_accuracy,
            'action_plan' => $action_plan,
        );
    }

    private function build_overall_condition($achievement_rate, $ready_coverage_days, $required_daily_output, $avg_daily_output, $avg_daily_capacity, $critical_orders, $data_accuracy, $current_period, $period_calendar)
    {
        $risk_points = 0;
        $watch_points = 0;
        $drivers = array();
        $current_label = $current_period && isset($current_period['label']) ? $current_period['label'] : '-';
        $current_balance = $current_period && isset($current_period['balance']) ? $current_period['balance'] : 0;
        $remaining_days = isset($period_calendar['export_remaining_workdays']) ? $period_calendar['export_remaining_workdays'] : 0;
        $total_days = isset($period_calendar['export_total_workdays']) ? $period_calendar['export_total_workdays'] : 0;
        $calendar_remaining_days = isset($period_calendar['remaining_workdays']) ? $period_calendar['remaining_workdays'] : 0;
        $export_prep_days = isset($period_calendar['export_prep_days']) ? $period_calendar['export_prep_days'] : $this->export_prep_workdays();

        if ($achievement_rate < 75) {
            $risk_points++;
            $drivers[] = 'output achievement rendah';
        } elseif ($achievement_rate < 90) {
            $watch_points++;
            $drivers[] = 'output achievement perlu dipantau';
        }

        if ($ready_coverage_days < 5) {
            $risk_points++;
            $drivers[] = 'ready load rendah';
        } elseif ($ready_coverage_days < 10) {
            $watch_points++;
            $drivers[] = 'ready load perlu dijaga';
        }

        if ($remaining_days > 11) {
            $risk_points += 3;
            $drivers[] = 'sisa hari kerja lebih dari 11 hari';
        } elseif ($remaining_days >= 5) {
            $watch_points += 2;
            $drivers[] = 'sisa hari kerja 5-11 hari';
        } elseif ($remaining_days < 4) {
            $drivers[] = 'sisa hari kerja aman';
        }

        if ($remaining_days <= 0 && $current_balance > 0) {
            $risk_points++;
            $drivers[] = 'sisa hari kerja periode sudah habis';
        } elseif ($avg_daily_capacity < $required_daily_output) {
            $risk_points++;
            $drivers[] = 'kapasitas harian tidak cukup mengejar export';
        } elseif ($avg_daily_output < $required_daily_output) {
            $watch_points++;
            $drivers[] = 'output harian perlu dikejar';
        } elseif ($avg_daily_output < ($required_daily_output * 1.1)) {
            $watch_points++;
            $drivers[] = 'margin output harian tipis';
        }

        if ($critical_orders > 0) {
            $risk_points++;
            $drivers[] = 'ada order delivery kritis';
        }

        if ($data_accuracy['score'] < 75) {
            $risk_points++;
            $drivers[] = 'akurasi urutan data rendah';
        } elseif ($data_accuracy['score'] < 90) {
            $watch_points++;
            $drivers[] = 'akurasi urutan data perlu dipantau';
        }

        if ($remaining_days > 11) {
            $status = 'risk';
            $level = 'High Risk';
        } elseif ($remaining_days >= 5) {
            $status = 'watch';
            $level = 'Medium Risk';
        } elseif ($remaining_days < 4) {
            $status = 'good';
            $level = 'Low Risk';
        } elseif ($risk_points >= 3) {
            $status = 'risk';
            $level = 'High Risk';
        } elseif ($risk_points > 0 || $watch_points >= 2) {
            $status = 'watch';
            $level = 'Medium Risk';
        } elseif ($watch_points > 0) {
            $status = 'watch';
            $level = 'Medium Risk';
        } else {
            $status = 'good';
            $level = 'Low Risk';
        }

        return array(
            'status' => $status,
            'level' => $level,
            'title' => 'Status Export ' . $current_label . ' :',
            'delivery' => $current_label,
            'summary' => 'Periode ' . $current_label . ': kurang ' . $this->format_compact_number($current_balance) . ' pcs, sisa export ' . number_format($remaining_days, 1) . ' dari ' . number_format($total_days, 1) . ' hari kerja. Kalender masih ' . number_format($calendar_remaining_days, 1) . ' hari, dengan buffer export ' . number_format($export_prep_days, 1) . ' hari. Butuh ' . $this->format_compact_number($required_daily_output) . ' pcs/hari; avg output ' . $this->format_compact_number($avg_daily_output) . ' pcs/hari, kapasitas ' . $this->format_compact_number($avg_daily_capacity) . ' pcs/hari. ' . ($drivers ? 'Faktor utama: ' . implode(', ', array_slice($drivers, 0, 2)) . '.' : 'Export periode berjalan masih terkejar.'),
            'risk_points' => $risk_points,
            'watch_points' => $watch_points,
        );
    }

    private function build_delivery_workdays($qty_rows, $ready_rows)
    {
        $labels = array();
        foreach (array_merge($qty_rows ?: array(), $ready_rows ?: array()) as $row) {
            if (!empty($row['label'])) {
                $labels[] = $row['label'];
            }
        }

        $labels = array_values(array_unique($labels));
        usort($labels, array($this, 'compare_period_labels'));

        $items = array();
        foreach ($labels as $label) {
            $items[] = $this->build_period_calendar($label);
        }

        return $items;
    }

    private function build_current_period_calendar($current_period)
    {
        if (!$current_period || empty($current_period['label'])) {
            return $this->empty_period_calendar();
        }

        return $this->build_period_calendar($current_period['label']);
    }

    private function empty_period_calendar()
    {
        return array(
            'label' => '',
            'start_date' => '',
            'end_date' => '',
            'total_workdays' => 0,
            'elapsed_workdays' => 0,
            'remaining_workdays' => 0,
            'export_prep_days' => $this->export_prep_workdays(),
            'export_total_workdays' => 0,
            'export_remaining_workdays' => 0,
            'holidays' => array(),
            'half_days' => array(),
            'work_days' => array(),
            'manual_remaining' => FALSE,
        );
    }

    private function build_period_calendar($label)
    {
        if (trim((string) $label) === '') {
            return $this->empty_period_calendar();
        }

        $range = $this->period_date_range($label);
        $calendar_days = $this->dashboard_calendar_days();
        $today = date('Y-m-d');
        $start = $range ? max($range['start'], $today) : '';
        $remaining = $range && $start <= $range['end'] ? $this->count_workdays($start, $range['end'], $calendar_days) : 0;
        $total = $range ? $this->count_workdays($range['start'], $range['end'], $calendar_days) : 0;
        $export_prep_days = $this->export_prep_workdays();
        $export_total = $this->export_workdays_from_remaining($total);
        $export_remaining = $this->export_workdays_from_remaining($remaining);
        $elapsed = max(0, $total - $remaining);

        return array(
            'label' => $label,
            'start_date' => $range ? $range['start'] : '',
            'end_date' => $range ? $range['end'] : '',
            'total_workdays' => $total,
            'elapsed_workdays' => $elapsed,
            'remaining_workdays' => $remaining,
            'export_prep_days' => $export_prep_days,
            'export_total_workdays' => $export_total,
            'export_remaining_workdays' => $export_remaining,
            'holidays' => $calendar_days['holidays'],
            'half_days' => $calendar_days['half_days'],
            'work_days' => $calendar_days['work_days'],
            'manual_remaining' => FALSE,
        );
    }

    private function export_prep_workdays()
    {
        return 4;
    }

    private function export_workdays_from_remaining($workdays)
    {
        return max(0, $workdays - $this->export_prep_workdays());
    }

    private function period_date_range($label)
    {
        if (preg_match('/^([A-Za-z]+)\s+(\d{4})$/i', trim($label), $month_match)) {
            $month_index = $this->month_label_to_index($month_match[1]);
            if ($month_index === NULL) {
                return NULL;
            }

            $year = (int) $month_match[2];
            $month = $month_index + 1;

            return array(
                'start' => sprintf('%04d-%02d-01', $year, $month),
                'end' => sprintf('%04d-%02d-%02d', $year, $month, (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)))),
            );
        }

        if (!preg_match('/^(MID|END)\s+([A-Za-z]+)$/i', trim($label), $match)) {
            return NULL;
        }

        $month_index = $this->month_label_to_index($match[2]);
        if ($month_index === NULL) {
            return NULL;
        }

        $year = (int) date('Y');
        $month = $month_index + 1;
        $start_day = strtoupper($match[1]) === 'MID' ? 1 : 16;
        $end_day = strtoupper($match[1]) === 'MID' ? 15 : (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));

        return array(
            'start' => sprintf('%04d-%02d-%02d', $year, $month, $start_day),
            'end' => sprintf('%04d-%02d-%02d', $year, $month, $end_day),
        );
    }

    private function count_workdays($start, $end, $calendar_days)
    {
        if ($start > $end) {
            return 0;
        }

        $count = 0.0;
        foreach ($this->date_range($start, $end) as $date) {
            $count += $this->calendar_workday_value($date, $calendar_days);
        }

        return $count;
    }

    private function calendar_workday_value($date, $calendar_days)
    {
        $holidays = isset($calendar_days['holidays']) && is_array($calendar_days['holidays']) ? $calendar_days['holidays'] : array();
        $half_days = isset($calendar_days['half_days']) && is_array($calendar_days['half_days']) ? $calendar_days['half_days'] : array();
        $work_days = isset($calendar_days['work_days']) && is_array($calendar_days['work_days']) ? $calendar_days['work_days'] : array();

        if (in_array($date, $holidays, TRUE)) {
            return 0;
        }

        if (in_array($date, $half_days, TRUE)) {
            return 0.5;
        }

        $day_of_week = (int) date('w', strtotime($date));
        if ($day_of_week === 0 && !in_array($date, $work_days, TRUE)) {
            return 0;
        }

        return 1;
    }

    private function date_range($start, $end)
    {
        $dates = array();
        $current = strtotime($start);
        $last = strtotime($end);
        while ($current !== FALSE && $current <= $last) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        return $dates;
    }

    private function dashboard_holidays()
    {
        return $this->dashboard_calendar_days()['holidays'];
    }

    private function dashboard_calendar_days()
    {
        $config = $this->dashboard_config();
        $holidays = isset($config['dashboard_heat_holidays']) && is_array($config['dashboard_heat_holidays'])
            ? $config['dashboard_heat_holidays']
            : array();
        $calendar = $this->get_heat_holiday_settings();
        $holidays = array_merge($holidays, $calendar['holidays']);

        $holidays = array_values(array_unique(array_filter(array_map(function ($date) {
            $timestamp = strtotime($date);
            return $timestamp ? date('Y-m-d', $timestamp) : NULL;
        }, $holidays))));
        $half_days = array_values(array_diff($calendar['half_days'], $holidays));
        $work_days = array_values(array_diff($calendar['work_days'], array_merge($holidays, $half_days)));
        sort($holidays);
        sort($half_days);
        sort($work_days);

        return array(
            'holidays' => $holidays,
            'half_days' => $half_days,
            'work_days' => $work_days,
        );
    }

    private function build_management_action_plan($achievement_rate, $ready_coverage_days, $required_daily_output, $avg_daily_output, $critical_orders, $data_accuracy)
    {
        $items = array();

        if ($data_accuracy['score'] < 90) {
            $items[] = array(
                'status' => $data_accuracy['status'],
                'title' => 'Akurasi urutan produksi',
                'result' => 'Ada indikasi periode baru sudah diproses saat periode sebelumnya belum clear.',
                'prevention' => 'Kunci rule FIFO/periode saat load PDK dan wajibkan validasi sisa END/MID sebelum menjalankan periode berikutnya.',
                'handling' => 'Review issue yang muncul, tahan proses periode berikutnya bila perlu, lalu selesaikan atau koreksi data periode yang masih tersisa.',
            );
        }

        if ($achievement_rate < 90) {
            $items[] = array(
                'status' => $achievement_rate >= 75 ? 'watch' : 'risk',
                'title' => 'Pencapaian output',
                'result' => 'Output baru mencapai ' . $this->format_percent($achievement_rate) . ' dari total PDK.',
                'prevention' => 'Pantau target harian per shift dan update progress minimal setiap akhir shift.',
                'handling' => 'Fokuskan kapasitas ke balance terbesar, pecah bottleneck material/loading, dan tambah jam kerja bila target harian tidak tercapai.',
            );
        }

        if ($ready_coverage_days < 10) {
            $items[] = array(
                'status' => $ready_coverage_days >= 5 ? 'watch' : 'risk',
                'title' => 'Coverage ready load',
                'result' => 'Ready load hanya cukup untuk ' . number_format($ready_coverage_days, 1) . ' hari kapasitas.',
                'prevention' => 'Jaga buffer ready load minimal 10 hari kapasitas untuk mengurangi risiko line stop.',
                'handling' => 'Prioritaskan picking/material untuk order delivery terdekat dan order dengan balance terbesar.',
            );
        }

        if ($avg_daily_output < $required_daily_output) {
            $items[] = array(
                'status' => $avg_daily_output >= ($required_daily_output * 0.9) ? 'watch' : 'risk',
                'title' => 'Kebutuhan output harian',
                'result' => 'Rata-rata output harian belum memenuhi kebutuhan ' . $this->format_compact_number($required_daily_output) . ' pcs/hari.',
                'prevention' => 'Bandingkan required daily output dengan plan kapasitas sebelum mengunci komitmen delivery.',
                'handling' => 'Naikkan output harian lewat tambahan slot produksi, penyesuaian prioritas, atau negosiasi delivery bila gap tidak tertutup.',
            );
        }

        if ($critical_orders > 0) {
            $items[] = array(
                'status' => 'risk',
                'title' => 'Order delivery kritis',
                'result' => $critical_orders . ' order prioritas berada dalam horizon delivery 5 hari.',
                'prevention' => 'Gunakan aging delivery harian agar order mendekati due date tidak tertinggal di queue.',
                'handling' => 'Tarik order kritis ke prioritas pertama, pastikan material ready, dan monitor output order tersebut per shift.',
            );
        }

        if (!$items) {
            $items[] = array(
                'status' => 'good',
                'title' => 'Kondisi terkendali',
                'result' => 'Tidak ada risiko mayor dari indikator management analytics saat ini.',
                'prevention' => 'Pertahankan validasi FIFO/periode, buffer ready load minimal 10 hari, dan monitoring output harian.',
                'handling' => 'Lanjutkan produksi sesuai prioritas berjalan dan review ulang saat data dashboard berikutnya masuk.',
            );
        }

        return $items;
    }

    private function build_data_accuracy($qty_rows, $ready_rows)
    {
        $ready_by_label = array();
        foreach ($ready_rows as $row) {
            $ready_by_label[strtolower($row['label'])] = $row['ready'];
        }

        $periods = array();
        foreach ($qty_rows as $row) {
            $label = $row['label'];
            $pdk = isset($row['pdk']) ? $row['pdk'] : 0;
            $output = isset($row['output']) ? $row['output'] : 0;
            $periods[] = array(
                'label' => $label,
                'pdk' => $pdk,
                'output' => $output,
                'balance' => max(0, $pdk - $output),
                'ready' => isset($ready_by_label[strtolower($label)]) ? $ready_by_label[strtolower($label)] : 0,
            );
        }

        $issues = array();
        $current_period = $this->current_running_period($periods);
        $previous_labels = $current_period ? $this->previous_period_labels($current_period['label'], 2) : array();

        foreach ($previous_labels as $previous_label) {
            $previous = $this->find_period_by_label($periods, $previous_label);
            if (!$previous) {
                continue;
            }

            $blocking_qty = $previous['balance'] + $previous['ready'];
            if ($blocking_qty <= 0) {
                continue;
            }

            $issues[] = array(
                'status' => 'risk',
                'title' => $previous['label'] . ' belum clear',
                'text' => 'Periode berjalan ' . $current_period['label'] . ', sementara ' . $previous['label'] . ' masih punya sisa/ready ' . $this->format_compact_number($blocking_qty) . ' pcs.',
            );
        }

        $score = max(0, 100 - (count($issues) * 20));
        $status = $score >= 90 ? 'good' : ($score >= 75 ? 'watch' : 'risk');
        $message = count($issues)
            ? count($issues) . ' potensi periode sebelumnya belum clear ditemukan.'
            : ($current_period ? 'Periode berjalan ' . $current_period['label'] . ' sudah konsisten terhadap periode sebelumnya.' : 'Urutan data antar periode sudah konsisten.');

        return array(
            'score' => $score,
            'status' => $status,
            'message' => $message,
            'issues' => $issues,
            'periods' => $periods,
            'current_period' => $current_period,
            'checked_periods' => $previous_labels,
        );
    }

    private function current_running_period($periods)
    {
        foreach ($periods as $period) {
            if ($period['balance'] > 0 || $period['ready'] > 0) {
                return $period;
            }
        }

        return isset($periods[0]) ? $periods[0] : NULL;
    }

    private function previous_period_labels($label, $count = 2)
    {
        if (preg_match('/^([A-Za-z]+)\s+(\d{4})$/i', trim($label), $match)) {
            $month_index = $this->month_label_to_index($match[1]);
            if ($month_index === NULL) {
                return array();
            }

            $year = (int) $match[2];
            $labels = array();
            while (count($labels) < $count) {
                $month_index--;
                if ($month_index < 0) {
                    $month_index = 11;
                    $year--;
                }

                $labels[] = $this->months[$month_index] . ' ' . $year;
            }

            return $labels;
        }

        if (!preg_match('/^(MID|END)\s+([A-Za-z]+)$/i', trim($label), $match)) {
            return array();
        }

        $type = strtoupper($match[1]);
        $month_index = $this->month_label_to_index($match[2]);
        if ($month_index === NULL) {
            return array();
        }

        $labels = array();
        while (count($labels) < $count) {
            if ($type === 'END') {
                $type = 'MID';
            } else {
                $type = 'END';
                $month_index--;
                if ($month_index < 0) {
                    $month_index = 11;
                }
            }

            $labels[] = $type . ' ' . $this->months[$month_index];
        }

        return $labels;
    }

    private function find_period_by_label($periods, $label)
    {
        foreach ($periods as $period) {
            if (strcasecmp($period['label'], $label) === 0) {
                return $period;
            }
        }

        return NULL;
    }

    private function month_label_to_index($label)
    {
        foreach ($this->months as $index => $month) {
            if (strcasecmp($month, $label) === 0) {
                return $index;
            }
        }

        return NULL;
    }

    private function analytics_insight($status, $title, $text)
    {
        return array('status' => $status, 'title' => $title, 'text' => $text);
    }

    private function delivery_workdays_left($delivery)
    {
        $timestamp = strtotime($delivery);
        if (!$timestamp) {
            return PHP_INT_MAX;
        }

        $today = date('Y-m-d');
        $delivery_date = date('Y-m-d', $timestamp);
        if ($delivery_date < $today) {
            return 0;
        }

        return $this->count_workdays($today, $delivery_date, $this->dashboard_calendar_days());
    }

    private function format_percent($value)
    {
        return number_format($value, 1) . '%';
    }

    private function floor_decimal($value, $decimals)
    {
        $factor = pow(10, $decimals);
        return floor($value * $factor) / $factor;
    }

    private function format_compact_number($value)
    {
        return number_format($value, 0, '.', ',');
    }

    private function grid_cell($rows, $row_number, $column)
    {
        $row_index = $row_number - 1;
        $column_index = $this->column_letters_to_index($column);

        return isset($rows[$row_index][$column_index]) ? $rows[$row_index][$column_index] : '';
    }

    private function column_letters_to_index($letters)
    {
        $letters = strtoupper($letters);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function format_excel_date($value)
    {
        if (!is_numeric($value)) {
            return $value;
        }

        $timestamp = ((int) $value - 25569) * 86400;
        return gmdate('d M Y', $timestamp);
    }

    private function format_excel_datetime($value)
    {
        if (!is_numeric($value)) {
            return $value;
        }

        $timestamp = ((float) $value - 25569) * 86400;
        return gmdate('d M Y H:i', (int) round($timestamp));
    }

    private function xlsx_sheet_name_for_hint($zip, $sheet_hint)
    {
        if (!$sheet_hint) {
            return NULL;
        }

        $sheet_map = $this->xlsx_sheet_map($zip);
        if (!$sheet_map) {
            return NULL;
        }

        $hint = strtolower(pathinfo($sheet_hint, PATHINFO_FILENAME));
        foreach ($sheet_map as $title => $path) {
            $normalized_title = strtolower(str_replace(array(' ', '-'), '_', $title));
            if ($normalized_title === $hint || strpos($normalized_title, $hint) !== FALSE) {
                return $path;
            }
        }

        if (strpos($hint, 'outflow') !== FALSE) {
            foreach ($sheet_map as $title => $path) {
                if (strpos(strtolower($title), 'out') !== FALSE) {
                    return $path;
                }
            }
        }

        if (strpos($hint, 'inflow') !== FALSE) {
            foreach ($sheet_map as $title => $path) {
                if (strpos(strtolower($title), 'in') !== FALSE) {
                    return $path;
                }
            }
        }

        return NULL;
    }

    private function xlsx_sheet_map($zip)
    {
        $workbook_xml = $zip->getFromName('xl/workbook.xml');
        $rels_xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook_xml === FALSE || $rels_xml === FALSE) {
            return array();
        }

        $workbook = simplexml_load_string($workbook_xml);
        $rels = simplexml_load_string($rels_xml);
        if (!$workbook || !$rels) {
            return array();
        }

        $relations = array();
        foreach ($rels->Relationship as $relation) {
            $target = (string) $relation['Target'];
            $relations[(string) $relation['Id']] = 'xl/' . ltrim($target, '/');
        }

        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $map = array();
        foreach ($workbook->sheets->sheet as $sheet) {
            $attributes = $sheet->attributes('r', TRUE);
            $relation_id = (string) $attributes['id'];
            if (isset($relations[$relation_id])) {
                $map[(string) $sheet['name']] = $relations[$relation_id];
            }
        }

        return $map;
    }

    private function read_xlsx_shared_strings($zip)
    {
        $xml_string = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml_string === FALSE) {
            return array();
        }

        $xml = simplexml_load_string($xml_string);
        if (!$xml) {
            return array();
        }

        $strings = array();
        foreach ($xml->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;
                continue;
            }

            $parts = array();
            foreach ($item->r as $run) {
                $parts[] = (string) $run->t;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function first_xlsx_sheet_name($zip)
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/^xl\/worksheets\/sheet\d+\.xml$/', $name)) {
                return $name;
            }
        }

        return NULL;
    }

    private function xlsx_cell_value($cell_node, $shared_strings)
    {
        $type = (string) $cell_node['t'];

        if ($type === 's') {
            $index = (int) $cell_node->v;
            return isset($shared_strings[$index]) ? $shared_strings[$index] : '';
        }

        if ($type === 'inlineStr') {
            return isset($cell_node->is->t) ? (string) $cell_node->is->t : '';
        }

        return isset($cell_node->v) ? (string) $cell_node->v : '';
    }

    private function xlsx_column_index($ref)
    {
        preg_match('/^[A-Z]+/i', $ref, $match);
        $letters = strtoupper($match ? $match[0] : 'A');
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function parse_table_rows($html)
    {
        preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $html, $row_matches);
        $rows = array();

        foreach ($row_matches[1] as $row_html) {
            preg_match_all('/<td\b[^>]*>(.*?)<\/td>/is', $row_html, $cell_matches);
            $cells = array();
            foreach ($cell_matches[1] as $cell) {
                $cells[] = $this->normalize(html_entity_decode(strip_tags($cell), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            if (count($cells) > 2) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    private function count_report_rows($path)
    {
        return count($this->read_html_report($path)['rows']);
    }

    private function summarize_32a_inflow_periods()
    {
        $periods = array();
        $paths = array_merge(
            glob($this->data_dir() . DIRECTORY_SEPARATOR . '????-??' . DIRECTORY_SEPARATOR . '32a_inflow.xlsx') ?: array(),
            glob($this->data_dir() . DIRECTORY_SEPARATOR . '????-??' . DIRECTORY_SEPARATOR . '32a_inflow.xls') ?: array()
        );
        foreach ($paths as $path) {
            $report = $this->read_html_report($path);
            $index = $this->header_index($report['headers']);
            $periods[] = array(
                'key' => basename(dirname($path)),
                'label' => $this->format_period_label(basename(dirname($path))),
                'qty' => $this->sum_qty($report['rows'], $index),
                'rows' => count($report['rows']),
            );
        }

        return $periods;
    }

    private function summarize_monthly_periods()
    {
        $month_dirs = glob($this->data_dir() . DIRECTORY_SEPARATOR . '????-??', GLOB_ONLYDIR) ?: array();
        $month_keys = array();

        foreach ($month_dirs as $dir) {
            $key = basename($dir);
            if (preg_match('/^\d{4}-\d{2}$/', $key)) {
                $month_keys[] = $key;
            }
        }

        if (!$month_keys) {
            $month_keys[] = date('Y-m', strtotime('-1 month'));
            $month_keys[] = date('Y-m');
        }

        $month_keys = array_values(array_unique($month_keys));
        sort($month_keys);
        $month_keys = array_slice($month_keys, -2);

        while (count($month_keys) < 2) {
            array_unshift($month_keys, date('Y-m', strtotime($month_keys[0] . '-01 -1 month')));
            $month_keys = array_values(array_unique($month_keys));
        }

        $periods = array();

        foreach ($month_keys as $month_key) {
            $data_file = $this->data_file();
            if ($data_file) {
                $inflow = $this->read_html_report($data_file . '#' . $month_key . '_32a_inflow');
                $outflow = $this->read_html_report($data_file . '#' . $month_key . '_32a_outflow');
            } else {
                $dir = $this->data_dir() . DIRECTORY_SEPARATOR . $month_key;
                $inflow = $this->read_html_report($this->latest_existing_file(array(
                    $dir . DIRECTORY_SEPARATOR . '32a_inflow.xlsx',
                    $dir . DIRECTORY_SEPARATOR . '32a_inflow.xls',
                )));
                $outflow = $this->read_html_report($this->latest_existing_file(array(
                    $dir . DIRECTORY_SEPARATOR . '32a_outflow.xlsx',
                    $dir . DIRECTORY_SEPARATOR . '32a_outflow.xls',
                )));
            }
            $summary = $this->summarize_rows_ready($inflow, $outflow);

            $periods[] = array(
                'key' => $month_key,
                'label' => $this->format_period_label($month_key),
                'ready_qty' => $summary['ready_qty'],
                'ready_pdk' => $summary['ready_pdk'],
                'ready_panels' => $summary['ready_panels'],
                'in_qty' => $summary['in_qty'],
                'out_qty' => $summary['out_qty'],
            );
        }

        return $periods;
    }

    private function summarize_rows_ready($inflow, $outflow)
    {
        if (!$inflow['headers']) {
            return array('ready_qty' => 0, 'ready_pdk' => 0, 'ready_panels' => 0, 'in_qty' => 0, 'out_qty' => 0);
        }

        $in_index = $this->header_index($inflow['headers']);
        $out_index = $this->header_index($outflow['headers']);
        $groups = array();

        foreach ($inflow['rows'] as $row) {
            $udef4 = $this->cell($row, $in_index, 'Udef 4');
            if ($udef4 === '') {
                continue;
            }
            if (!isset($groups[$udef4])) {
                $groups[$udef4] = array('in_qty' => 0, 'out_qty' => 0, 'in_rows' => 0, 'out_rows' => 0);
            }
            $groups[$udef4]['in_qty'] += $this->parse_number($this->cell($row, $in_index, 'Qty'));
            $groups[$udef4]['in_rows']++;
        }

        foreach ($outflow['rows'] as $row) {
            $udef4 = $this->cell($row, $out_index, 'Udef 4');
            if ($udef4 === '') {
                continue;
            }
            if (!isset($groups[$udef4])) {
                $groups[$udef4] = array('in_qty' => 0, 'out_qty' => 0, 'in_rows' => 0, 'out_rows' => 0);
            }
            $groups[$udef4]['out_qty'] += abs($this->parse_number($this->cell($row, $out_index, 'Qty')));
            $groups[$udef4]['out_rows']++;
        }

        $ready_qty = 0;
        $ready_pdk = 0;
        $ready_panels = 0;

        foreach ($groups as $group) {
            $qty = $group['in_qty'] - $group['out_qty'];
            if ($qty <= 0) {
                continue;
            }
            $ready_qty += $qty;
            $ready_pdk++;
            $ready_panels += max(0, $group['in_rows'] - $group['out_rows']);
        }

        return array(
            'ready_qty' => $ready_qty,
            'ready_pdk' => $ready_pdk,
            'ready_panels' => $ready_panels,
            'in_qty' => $this->sum_qty($inflow['rows'], $in_index),
            'out_qty' => abs($this->sum_qty($outflow['rows'], $out_index)),
        );
    }

    private function format_period_label($key)
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $key, $month_match)) {
            $month = (int) $month_match[2];
            $label = isset($this->months[$month - 1]) ? $this->months[$month - 1] : $month_match[2];
            return $label . ' ' . $month_match[1];
        }

        if (!preg_match('/^(\d{4})-(\d{2})_(mid|end)$/i', $key, $match)) {
            return $key;
        }

        $month = (int) $match[2];
        $label = isset($this->months[$month - 1]) ? $this->months[$month - 1] : $match[2];
        return strtoupper($match[3]) . ' ' . $label;
    }

    private function empty_group($udef4)
    {
        return array('udef4' => $udef4, 'item' => '', 'prod' => '', 'in_qty' => 0, 'out_qty' => 0, 'in_rows' => 0, 'out_rows' => 0);
    }

    private function header_index($headers)
    {
        $index = array();
        foreach ($headers as $key => $header) {
            $index[strtolower($header)] = $key;
        }
        return $index;
    }

    private function cell($row, $index, $name)
    {
        $key = strtolower($name);
        return isset($index[$key], $row[$index[$key]]) ? $row[$index[$key]] : '';
    }

    private function sum_qty($rows, $index)
    {
        $sum = 0;
        foreach ($rows as $row) {
            $sum += $this->parse_number($this->cell($row, $index, 'Qty'));
        }
        return $sum;
    }

    private function parse_number($value)
    {
        $clean = str_replace(',', '', $this->normalize($value));
        return preg_match('/-?\d+(?:\.\d+)?/', $clean, $match) ? (float) $match[0] : 0;
    }

    private function normalize($value)
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value));
    }

    private function format_bytes($size)
    {
        $units = array('B', 'KB', 'MB', 'GB');
        $value = (float) $size;
        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'GB') {
                return $unit === 'B' ? (int) $value . ' ' . $unit : number_format($value, 1) . ' ' . $unit;
            }
            $value /= 1024;
        }
        return number_format($value, 1) . ' GB';
    }
}
