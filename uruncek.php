<?php
require "ssp.php";
$table = 'urunler';
$primaryKey = 'urun_id';

// GET parametresinden teklif ID'sini alalım
$teklifid = isset($_GET['teklifid']) ? (int)$_GET['teklifid'] : 0;

$columns = array(
    array('db' => 'stokkodu', 'dt' => 0),
    array('db' => 'stokadi', 'dt' => 1),
    array('db' => 'olcubirimi', 'dt' => 2),
    array('db' => 'doviz', 'dt' => 3),
    array('db' => 'fiyat', 'dt' => 4),
    array('db' => 'marka', 'dt' => 5),
    array('db' => 'miktar', 'dt' => 6),
    array('db' => 'urun_id', 'dt' => 7, 'formatter' => function ($data) use ($teklifid) {
        // Ekle butonuna teklif ID'sini ekleyelim
        return "<a href='teklifsiparisler-duzenle.php?ekle={$data}&te={$teklifid}' class='btn btn-success btn-sm btn-xs'>Seç</a>";
    }),
);

// SQL server connection information
include "include/vt.php";

echo json_encode(
    SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
);
