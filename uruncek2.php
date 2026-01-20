<?php
/*
 * DataTables example server-side processing script.
 *
 * Please note that this script is intentionally extremely simple to show how
 * server-side processing can be implemented, and probably shouldn't be used as
 * the basis for a large complex system. It is suitable for simple use cases as
 * for learning.
 *
 * See http://datatables.net/usage/server-side for full details on the server-
 * side processing requirements of DataTables.
 *
 * @license MIT - http://datatables.net/license_mit
 */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Easy set variables
 */
require "ssp.php";
// DB table to use
$table = 'urunler';
// Table's primary key
$primaryKey = 'urun_id';
// Array of database columns which should be read and sent back to DataTables.
// The `db` parameter represents the column name in the database, while the `dt`
// parameter represents the DataTables column identifier. In this case simple
// indexes
$columns = array(
    array('db' => 'stokkodu', 'dt' => 0),
    array('db' => 'stokadi',  'dt' => 1),
    array('db' => 'olcubirimi',     'dt' => 2),
    array('db' => 'doviz',     'dt' => 3),
    array('db' => 'fiyat',   'dt' => 4),
    array('db' => 'marka',   'dt' => 5),
    array('db' => 'miktar',   'dt' => 6),
    array('db' => 'urun_id',  'dt' => 7, 'formatter' => function ($data) {
        return "<a href = 'teklifsiparisler-duzenle.php?ekle={$data}' class = 'btn btn-success btn-sm btn-xs'>Seç  </a>";
    }),
);
// SQL server connection information
$sql_details = array(
    'user' => 'root',
    'pass' => '',
    'db'   => 'b2bgemascom_teklif',
    'host' => 'localhost'
);
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * If you just want to use the basic configuration for DataTables with PHP
 * server-side, there is no need to edit below this line.
 */
echo json_encode(
    SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
);
