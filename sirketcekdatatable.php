<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
ob_start();
header('Content-Type: application/json; charset=utf-8');

// Gerekli dosyaları dahil edin
require "ssp.php"; // SSP kütüphanesinin doğru yolda olduğundan emin olun
require_once "fonk.php";
oturumkontrol();
include "include/vt.php"; // Veritabanı bağlantı bilgileri

$table = 'sirket';
$primaryKey = 'sirket_id';

// DataTables için sütunları tanımlayın
$columns = array(
    array(
        'db' => 'sirket_id',
        'dt' => 0,
        'formatter' => function ($data, $row) {
            return "<div class='d-flex justify-content-center gap-1'>"
                ."<a href='edit_company.php?id={$data}' class='text-info'"
                    ." data-bs-toggle='tooltip' title='Düzenle'>"
                    ."<i class='fa fa-edit'></i></a>"
                ."<a href='tumsirketpersonelleri.php?id={$data}' class='text-primary' data-bs-toggle='tooltip' title='Personeller'>"
                    ."<i class='fa fa-users'></i></a>"
                ."<a href='faturalar.php?id={$data}' class='text-warning' data-bs-toggle='tooltip' title='Fatura / İrsaliye'>"
                    ."<i class='fa fa-receipt'></i></a>"
                ."<a href='sirketeaitsiparisler.php?id={$data}' class='text-success' data-bs-toggle='tooltip' title='Siparişler'>"
                    ."<i class='fa fa-shopping-basket'></i></a>"
                ."<a href='sirketsil.php?id={$data}' class='text-danger' data-bs-toggle='tooltip' title='Kaldır'>"
                    ."<i class='fa fa-trash'></i></a>"
                ."</div>";
        }
    ),
    array('db' => 's_adi', 'dt' => 1),
    array('db' => 's_arp_code', 'dt' => 2),
    array('db' => 's_country_code', 'dt' => 3),
    array('db' => 's_country', 'dt' => 4),
    array('db' => 's_telefonu', 'dt' => 5),
    array('db' => 'acikhesap', 'dt' => 6),
    array('db' => 'payplan_code', 'dt' => 7),
    array('db' => 'payplan_def', 'dt' => 8),
    array('db' => 'trading_grp', 'dt' => 9),
    array('db' => 'internal_reference', 'dt' => 10),
    array('db' => 'risk_limit', 'dt' => 11)
);

$filter = $_GET['trading_filter'] ?? '';
switch ($filter) {
    case 'yurtdisi':
        $cond = "trading_grp LIKE '%yd%'";
        break;
    case 'yurtici':
        $cond = "(trading_grp NOT LIKE '%yd%' OR trading_grp IS NULL)";
        break;
    default:
        $cond = '1=1';
}

try {
    $result = SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null, $cond);
    if ($result === false) {
        throw new Exception("SSP::complex metodundan false döndü: " . json_last_error_msg());
    }
    $json = json_encode($result);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON encode failed: ' . json_last_error_msg());
    }
    echo $json;
} catch (Exception $e) {
    error_log("sirketcekdatatable.php hatası: " . $e->getMessage() . "\n", 3, __DIR__ . "/error_log.txt");
    echo json_encode(array("error" => "Veri getirme hatası."));
}
ob_end_flush();
?>
