<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Dashboard_base.php';

class Dashboard_heat extends Dashboard_base
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->load->model('Dashboard_model', 'dashboard');
    }

    public function index()
    {
        $calendar_authenticated = $this->calendar_authenticated();
        $cookie_delivery_count = isset($_COOKIE['heatDeliveryCount']) ? (int) $_COOKIE['heatDeliveryCount'] : 1;
        $selected_delivery_count = in_array($cookie_delivery_count, array(1, 2, 4), TRUE) ? $cookie_delivery_count : 1;
        $this->load->view('dashboards/heat/index', array(
            'title' => 'Dashboard Heat Transfer',
            'status_url' => site_url('dashboard_heat/api/status'),
            'qty_history_url' => site_url('dashboard_heat/api/qty-history'),
            'save_workdays_url' => site_url('dashboard_heat/api/save-workdays'),
            'calendar_login_url' => site_url('dashboard_heat/api/calendar-login'),
            'calendar_logout_url' => site_url('dashboard_heat/api/calendar-logout'),
            'run_url' => site_url('dashboard_heat/api/run-download'),
            'download_url' => site_url('dashboard_heat/download'),
            'material_to_load_download_url' => site_url('dashboard_heat/download_material_to_load'),
            'initial_delivery_count' => $selected_delivery_count,
            'initial_dashboard_payload' => array(
                'calendar_authenticated' => $calendar_authenticated,
                'dashboard_data' => $this->dashboard->get_heat_dashboard_data($selected_delivery_count),
                'server_time' => date('c'),
            ),
        ));
    }

    public function api($action = NULL)
    {
        if ($action === 'status') {
            return $this->api_status();
        }

        if ($action === 'qty-history') {
            return $this->api_qty_history();
        }

        if ($action === 'run-download') {
            return $this->run_download();
        }

        if ($action === 'save-workdays') {
            return $this->save_workdays();
        }

        if ($action === 'calendar-login') {
            return $this->calendar_login();
        }

        if ($action === 'calendar-logout') {
            return $this->calendar_logout();
        }

        show_404();
    }

    public function api_qty_history()
    {
        $delivery_count = (int) $this->input->get('delivery_count', TRUE);
        return $this->json($this->dashboard->get_heat_qty_history($delivery_count));
    }

    public function api_status()
    {
        $delivery_count = (int) $this->input->get('delivery_count', TRUE);
        return $this->json(array(
            'dashboard_data' => $this->dashboard->get_heat_dashboard_data($delivery_count),
            'server_time' => date('c'),
            'calendar_authenticated' => $this->calendar_authenticated(),
        ));
    }

    public function run_download()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        $result = $this->dashboard->run_download_once();
        return $this->json($result, $result['ok'] ? 202 : 500);
    }

    public function save_workdays()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        if (!$this->calendar_authenticated()) {
            return $this->json(array('ok' => FALSE, 'message' => 'Login diperlukan untuk mengubah kalender.'), 401);
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        if (!is_array($payload)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Payload tidak valid.'), 400);
        }

        $result = $this->dashboard->save_heat_holiday_settings(array(
            'holidays' => isset($payload['holidays']) ? $payload['holidays'] : array(),
            'half_days' => isset($payload['half_days']) ? $payload['half_days'] : array(),
            'quarter_days' => isset($payload['quarter_days']) ? $payload['quarter_days'] : array(),
            'work_days' => isset($payload['work_days']) ? $payload['work_days'] : array(),
        ));
        return $this->json($result, !empty($result['ok']) ? 200 : 500);
    }

    public function calendar_login()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        if (!is_array($payload)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Payload tidak valid.'), 400);
        }

        $config = $this->dashboard_config();
        $expected_user = isset($config['dashboard_heat_calendar_user']) ? (string) $config['dashboard_heat_calendar_user'] : 'admin';
        $expected_password = isset($config['dashboard_heat_calendar_password']) ? (string) $config['dashboard_heat_calendar_password'] : 'admin';
        $username = isset($payload['username']) ? trim((string) $payload['username']) : '';
        $password = isset($payload['password']) ? (string) $payload['password'] : '';

        if (!hash_equals($expected_user, $username) || !hash_equals($expected_password, $password)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Username atau password salah.'), 401);
        }

        $_SESSION['dashboard_heat_calendar_auth'] = TRUE;
        return $this->json(array('ok' => TRUE, 'message' => 'Login berhasil.'));
    }

    public function calendar_logout()
    {
        unset($_SESSION['dashboard_heat_calendar_auth']);
        return $this->json(array('ok' => TRUE, 'message' => 'Logout berhasil.'));
    }

    private function calendar_authenticated()
    {
        return !empty($_SESSION['dashboard_heat_calendar_auth']);
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

    public function download()
    {
        $filename = (string) $this->input->get('file', TRUE);
        $path = $this->dashboard->get_download_path($filename);

        if (!$path) {
            show_404();
            return;
        }

        $this->load->helper('download');
        force_download(basename($path), file_get_contents($path));
    }
    public function download_material_to_load()
    {
        $delivery_count = (int) $this->input->get('delivery_count', TRUE);
        $delivery_count = in_array($delivery_count, array(1, 2, 4), TRUE) ? $delivery_count : 4;
        $dashboard = $this->dashboard->get_heat_dashboard_data($delivery_count);

        if (empty($dashboard['available'])) {
            show_404();
            return;
        }

        $rows = isset($dashboard['material_to_load']) && is_array($dashboard['material_to_load'])
            ? $dashboard['material_to_load']
            : array();

        if (!$rows) {
            show_404();
            return;
        }

        $filename = 'material_to_load_' . date('Ymd_His') . '.xls';
        $html = $this->build_material_to_load_export($rows, $delivery_count);

        $this->output
            ->set_content_type('application/vnd.ms-excel', 'UTF-8')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate')
            ->set_header('Pragma: no-cache')
            ->set_header('Expires: 0')
            ->set_output($html);
    }

    private function build_material_to_load_export(array $rows, $delivery_count)
    {
        $title = 'Material To Load - ' . (in_array((int) $delivery_count, array(1, 2, 4), TRUE) ? (int) $delivery_count : 4) . ' Delivery';
        $generated_at = date('Y-m-d H:i:s');

        $html = array();
        $html[] = '<!doctype html>';
        $html[] = '<html>';
        $html[] = '<head>';
        $html[] = '<meta charset="utf-8">';
        $html[] = '<style>';
        $html[] = 'body{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#102033;}';
        $html[] = 'table{border-collapse:collapse;width:100%;}';
        $html[] = 'th,td{border:1px solid #dbe4ee;padding:6px 8px;}';
        $html[] = 'th{background:#176b87;color:#fff;text-align:left;}';
        $html[] = '.num{text-align:right;}';
        $html[] = '</style>';
        $html[] = '</head>';
        $html[] = '<body>';
        $html[] = '<h2>' . html_escape($title) . '</h2>';
        $html[] = '<div>Generated at: ' . html_escape($generated_at) . '</div>';
        $html[] = '<div>Source: dashboard Heat Transfer</div>';
        $html[] = '<br>';
        $html[] = '<table>';
        $html[] = '<thead><tr><th>No.</th><th>Order</th><th>Style</th><th>Item Nr</th><th>Tgl. Delivery</th><th class="num">Qty Ready</th><th>Source</th><th class="num">Qty PDK</th><th class="num">Qty Out APS</th><th class="num">Qty Out Engage</th></tr></thead>';
        $html[] = '<tbody>';

        for ($i = 0; $i < count($rows); $i++) {
            $row = $rows[$i];
            $html[] = '<tr>';
            $html[] = '<td class="num">' . ($i + 1) . '.</td>';
            $html[] = '<td>' . html_escape(isset($row['order']) ? $row['order'] : '') . '</td>';
            $html[] = '<td>' . html_escape(isset($row['style']) ? $row['style'] : '') . '</td>';
            $html[] = '<td>' . html_escape(isset($row['item']) ? $row['item'] : '') . '</td>';
            $html[] = '<td>' . html_escape(isset($row['delivery']) ? $row['delivery'] : '') . '</td>';
            $html[] = '<td class="num">' . number_format((float) (isset($row['qty_ready']) ? $row['qty_ready'] : 0), 0, ',', '.') . '</td>';
            $html[] = '<td>' . html_escape(isset($row['source']) ? $row['source'] : '-') . '</td>';
            $html[] = '<td class="num">' . number_format((float) (isset($row['qty_pdk']) ? $row['qty_pdk'] : 0), 0, ',', '.') . '</td>';
            $html[] = '<td class="num">' . number_format((float) (isset($row['qty_out_aps']) ? $row['qty_out_aps'] : 0), 0, ',', '.') . '</td>';
            $html[] = '<td class="num">' . number_format((float) (isset($row['qty_out_engage']) ? $row['qty_out_engage'] : 0), 0, ',', '.') . '</td>';
            $html[] = '</tr>';
        }

        $html[] = '</tbody>';
        $html[] = '</table>';
        $html[] = '</body>';
        $html[] = '</html>';

        return implode("
", $html);
    }

}
