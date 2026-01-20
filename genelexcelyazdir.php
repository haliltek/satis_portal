<?php

include "include/vt2.php";

$DB_TBLName = "urunler";
$filename = date("i");

$con = mysqli_connect($db_server, $db_username, $db_password, $db_name);
mysqli_select_db($con, $DB_TBLName);

mysqli_set_charset($con, "utf8_turkish_ci");
$sql = "Select * from $DB_TBLName";


$result = @mysqli_query($con, $sql) or die("Couldn't execute query:<br>" . mysqli_error($con));
$file_ending = "xls";

header("Content-Type: application/xls");
header("Content-type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=$filename.xls");
header("Pragma: no-cache");
header("Expires: 0");
error_reporting(0);


$sep = "\t";

for ($i = 0; $i < mysqli_num_fields($result); $i++) {
    echo mysqli_fetch_field($result, $i) . "\t";
}
print("\n");


while ($row = mysqli_fetch_row($result)) {
    $schema_insert = "";
    for ($j = 0; $j < mysqli_num_fields($result); $j++) {
        if (!isset($row[$j]))
            $schema_insert .= "NULL" . $sep;
        elseif ($row[$j] != "")
            $schema_insert .= "$row[$j]" . $sep;
        else
            $schema_insert .= "" . $sep;
    }
    $schema_insert = str_replace($sep . "$", "", $schema_insert);
    $schema_insert = preg_replace("/\r\n|\n\r|\n|\r/", " ", $schema_insert);
    $schema_insert .= "\t";
    print(trim($schema_insert));
    print "\n";
}
