<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Dashboard_base.php';

class Dashboard extends Dashboard_base
{
    public function index()
    {
        redirect('dashboard_heat');
    }
}
