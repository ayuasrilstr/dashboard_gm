<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['dashboard_heat_excel_file'] = '';
$config['dashboard_heat_unc_excel_file'] = '';

$config['dashboard_heat_data_dir'] = '';
$config['dashboard_heat_unc_dir'] = '';

// Tanggal merah bawaan. Kalender dashboard tetap bisa menambah libur, 1/2 hari,
// dan Minggu kerja dari modal Kalender Libur.
$config['dashboard_heat_holidays'] = array();

// Login untuk mengubah Kalender Libur.
$config['dashboard_heat_calendar_user'] = 'admin';
$config['dashboard_heat_calendar_password'] = 'admin';
