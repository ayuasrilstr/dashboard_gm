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
        $this->load->view('dashboards/heat/index', array(
            'title' => 'Dashboard Heat Transfer',
            'status_url' => site_url('dashboard_heat/api/status'),
            'save_workdays_url' => site_url('dashboard_heat/api/save-workdays'),
            'calendar_login_url' => site_url('dashboard_heat/api/calendar-login'),
            'calendar_logout_url' => site_url('dashboard_heat/api/calendar-logout'),
            'run_url' => site_url('dashboard_heat/api/run-download'),
            'download_url' => site_url('dashboard_heat/download'),
        ));
    }

    public function api($action = NULL)
    {
        if ($action === 'status') {
            return $this->api_status();
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

    public function api_status()
    {
        return $this->json(array(
            'dashboard_data' => $this->dashboard->get_heat_dashboard_data(),
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
}
