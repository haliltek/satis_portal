<?php
// Mock $_GET for DataTables
$_GET = [
    'draw' => 1,
    'start' => 0,
    'length' => 10,
    'search' => ['value' => '0111STRN50M', 'regex' => 'false'],
    'order' => [['column' => 0, 'dir' => 'asc']],
    'columns' => [
        ['data' => 0, 'name' => '', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => 'false']],
        ['data' => 1, 'name' => '', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => 'false']],
        // ... enough columns
    ]
];

// Capture output
ob_start();
try {
    include 'urun_fiyat_onerisi_datatable.php';
} catch (Exception $e) {
    echo $e->getMessage();
}
$output = ob_get_clean();

file_put_contents('datatable_output.json', $output);
echo "Output captured.";
