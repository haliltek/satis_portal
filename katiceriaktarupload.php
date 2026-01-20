<?php include "fonk.php";
oturumkontrol();  ?>
<?php
$klasor = "kategori_excel/"; // dosyaların yükleneceği klasör.
$dosya_adini_koru = false; // (false) 15 haneli random karakter adında kaydeder
// ajax geri arama durumunu kontrol etmek ve progresi durdurmak için uyarı kodlarına <wbr> ekledik 
/// upload kodları
try {
	$files = @$_FILES['file'];
	if (!$files) {
		throw new RuntimeException('<wbr>Yüklenecek dosya seçmediniz.');
	}
	foreach ($files['name'] as $i => $file) {
		if (!isset($files['error'][$i]) || is_array($files['error'][$i])) {
			throw new RuntimeException('<#>Geçersiz parametreler.');
		}
		switch ($files['error'][$i]) {
			case UPLOAD_ERR_OK:
				//Değeri: 0; Hata yoktur, dosya yükleme başarılıdır.
				break;
			case UPLOAD_ERR_NO_FILE:
				throw new RuntimeException('<wbr>Dosya gönderilmedi.');
			case UPLOAD_ERR_INI_SIZE:
				throw new RuntimeException('<wbr>Yüklenen dosya php.ini içindeki upload_max_filesize değerini aşmakta.');
			case UPLOAD_ERR_FORM_SIZE:
				throw new RuntimeException('<wbr>Yüklenen dosya, HTML formunda belirtilen MAX_FILE_SIZE direktifini aşıyor.');
			case UPLOAD_ERR_CANT_WRITE:
				throw new RuntimeException('<wbr>Dosya diske yazılamadı.');
			default:
				throw new RuntimeException('<wbr>Bilinmeyen yükleme hatası.');
		}
		if (!$dosya_adini_koru) {
			$ilk_ad = $files['name'][$i];
			$uzanti = '.' . strtolower(pathinfo($ilk_ad, PATHINFO_EXTENSION));
			$rnd = substr(uniqid(md5(rand())), 0, 15); // 15 haneli random karakter
			$yeni_adi = basename($rnd . $uzanti);
		} else {
			$yeni_adi = basename($files['name'][$i]);
		}
		if (file_exists($klasor . $yeni_adi)) {
			throw new RuntimeException('<wbr>' . $yeni_adi . ' adında bir dosya daha önce yüklenmiş (yeniden adlandır).');
		}
		if (move_uploaded_file($files['tmp_name'][$i], $klasor . $yeni_adi)) {

			set_time_limit(0);
			date_default_timezone_set('Europe/London');
			set_include_path(get_include_path() . PATH_SEPARATOR . 'Classes/');
			include 'PHPExcel/IOFactory.php';
			$inputFileName = "kategori_excel/" . $yeni_adi;
			$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
			$sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
			foreach ($sheetData as $key => $value) {
				$marka = $value["B"];
				$kat1 = $value["B"];
				$aciklama = $value["B"] . ' Ürünleri';
				$stokkodu = $value["A"];
				$kategoriduzenleme = "UPDATE urunler SET marka = '$marka',kat1 = '$kat1',aciklama = '$aciklama' WHERE stokkodu= '$stokkodu'";
				$add = mysqli_query($db, $kategoriduzenleme);
			}
			if ($add) {
				echo "<p style='color:green; font-size:25px'> Kırılımlar Başarıyla  Yüklendi.</p>";
			} else {
				echo "<p style='color:red'>Başarısız</p><br>";
			}
		}
	}
} catch (RuntimeException $e) {
	echo $e->getMessage();
}
?>