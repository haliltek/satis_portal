<?php
/*VeriTabanı İşlemleri Başlangıç*/
ob_start();
// Session zaten başlamışsa tekrar başlatma
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function gemas_web_database(): void
{
	global $gemas_web_db;
	$hostname = "89.43.31.214";
	$username = "gemas_mehmet";
	$password = "2261686Me!";
	$dbname = "gemas_pool_technology";
	$port = 3306;

	try {
		// Switch to mysqli_init + real_connect to handle timeouts
		$gemas_web_db = mysqli_init();
		$gemas_web_db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2); // 2 second timeout
		
		try {
			if (!@$gemas_web_db->real_connect($hostname, $username, $password, $dbname, $port)) {
				error_log("Uzak veritabanı bağlantı hatası: " . mysqli_connect_error());
				$gemas_web_db = null;
				return;
			}
		} catch (Throwable $t) {
			error_log("Uzak veritabanı fatal hatası: " . $t->getMessage());
			$gemas_web_db = null;
			return;
		}

		$gemas_web_db->set_charset("utf8");
		$gemas_web_db->query("SET time_zone = '+03:00'");
		$gemas_web_db->query("SET SESSION wait_timeout = 20"); // Reduced wait timeout
	} catch (Exception $e) {
		error_log("Uzak veritabanı bağlantı hatası: " . $e->getMessage());
		$gemas_web_db = null;
		return;
	}
}

function local_database(): void
{
	global $db;
	// Docker ortamında environment variable'ları kullan
	$db_host = getenv('DB_HOST') ?: 'localhost';
	$db_user = getenv('DB_USER') ?: 'root';
	$db_password = getenv('DB_PASSWORD') ?: '';
	$db_name = getenv('DB_NAME') ?: 'b2bgemascom_teklif';
	$db_port = (int)(getenv('DB_PORT') ?: 3306);

	// Bağlantıyı port numarası ile yapıyoruz.
	$db =  mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

	if (!$db) {
		die("Connection Failed: " . mysqli_connect_error());
	}
	$db->set_charset("utf8");
}


function gemas_logo_veritabani()
{
	global $gemas_logo_db;
	$mssql_hostname = "192.168.5.253,1433";
	$mssql_dbname = "GEMAS2026";
	$mssql_username = "halil";
	$mssql_password = "12621262";

	try {
		$dsn = "sqlsrv:Server=$mssql_hostname;Database=$mssql_dbname";
		// Add timeout
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
			$options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
		}

		$gemas_logo_db = new PDO($dsn, $mssql_username, $mssql_password, $options);
	} catch (PDOException $e) {
		error_log("MSSQL bağlantısı başarısız: " . $e->getMessage());
		$gemas_logo_db = null; // Ensure null on failure
	}
}

function gempa_logo_veritabani()
{
	global $gempa_logo_db;
	$mssql_hostname = "192.168.5.253,1433";
	$mssql_dbname = "GEMPA2026";
	$mssql_username = "halil";
	$mssql_password = "12621262";

	try {
		$dsn = "sqlsrv:Server=$mssql_hostname;Database=$mssql_dbname";
		// Add timeout
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
			$options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
		}

		$gempa_logo_db = new PDO($dsn, $mssql_username, $mssql_password, $options);
	} catch (PDOException $e) {
		error_log("MSSQL bağlantısı başarısız: " . $e->getMessage());
		$gempa_logo_db = null; // Ensure null on failure
	}
}

function merve2_veritabani()
{
	global $merve2_db;
	$mssql_hostname = "192.168.5.253,1433";
	$mssql_dbname = "MERVE2";
	$mssql_username = "halil";
	$mssql_password = "12621262";

	try {
		$dsn = "sqlsrv:Server=$mssql_hostname;Database=$mssql_dbname";
		// Add timeout
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
			$options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
		}

		$merve2_db = new PDO($dsn, $mssql_username, $mssql_password, $options);
	} catch (PDOException $e) {
		error_log("MERVE2 MSSQL bağlantısı başarısız: " . $e->getMessage());
		$merve2_db = null; 
	}
}


function girisyapildiysaYonlendir()
{
    if (isset($_SESSION['yonetici_id'])) {
        header('Location: anasayfa.php');
        exit;
    }
}

function oturumkontrol()
{
	local_database();
	// Veritabanı bağlantısını kontrol et
	global $gemas_logo_db, $gempa_logo_db;

	gemas_logo_veritabani(); // Updated function call
	gempa_logo_veritabani(); // Updated function call

	global $db;
	// Log başlangıcı
	// Oturum değişkeni kontrolü
        if (!isset($_SESSION['yonetici_id'])) {
                header("Location: index.php");
                exit;
        }
        $personelid = $_SESSION['yonetici_id'];
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType === 'Bayi') {
                $stmt = $db->prepare("SELECT id FROM b2b_users WHERE id = ? AND status = 1");
                $stmt->bind_param('i', $personelid);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows === 0) {
                        header("Location: index.php");
                        exit;
                }
                $stmt->close();

                $allowed = [
                    'anasayfa.php',
                    'siparis-olustur.php',
                    'sipariskontrol.php',
                    'dealer_orders.php',
                    'dealer_profile.php',
                    'company_details.php',
                    'get_acikhesap.php',
                    'cart_actions.php',
                    'get_campaign_rate.php',
                    'offer_detail.php',
                    'cikisyap.php'
                ];
                $current = basename($_SERVER['PHP_SELF']);
                if (!in_array($current, $allowed, true)) {
                        header('Location: anasayfa.php');
                        exit;
                }
        } else {
                $sirketcekereksorgulama = mysqli_query($db, "SELECT * FROM yonetici WHERE yonetici_id='$personelid'");
                if (!$sirketcekereksorgulama || mysqli_num_rows($sirketcekereksorgulama) == 0) {
                        header("Location: index.php");
                        exit;
                }
        }
	// Fonksiyon sonu
	//error_log(message: "oturumkontrol() fonksiyonu başarıyla tamamlandı.\n", 3, "debug.log");
}

function guvenlik($q)
{
	$q = stripslashes($q);
	$q = str_replace("script", "", $q);
	$q = str_replace("`", "", $q);
	$q = str_replace("'", "'", $q);
	$q = str_replace("-", "-", $q);
	$q = str_replace("&", "", $q);
	$q = str_replace("%", "", $q);
	$q = str_replace("<", "", $q);
	$q = str_replace(">", "", $q);
	$q = trim($q);
	return $q;
}
function xss($data)
{
	// Fix &entity\n;
	$data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
	$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
	$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
	$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
	// Remove any attribute starting with "on" or xmlns
	$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);
	// Remove javascript: and vbscript: protocols
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);
	// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);
	// Remove namespaced elements (we do not need them)
	$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);
	do {
		// Remove really unwanted tags
		$old_data = $data;
		$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
	} while ($old_data !== $data);
	// we are done...
	return $data;
}
function seo($s)
{
	$tr = array('ş', 'Ş', 'ı', 'I', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'Ç', 'ç', '(', ')', '/', ':', ',');
	$eng = array('s', 's', 'i', 'i', 'i', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c', '', '', '-', '-', '');
	$s = str_replace($tr, $eng, $s);
	$s = strtolower($s);
	$s = preg_replace('/&amp;amp;amp;amp;amp;amp;amp;amp;amp;.+?;/', '', $s);
	$s = preg_replace('/\s+/', '-', $s);
	$s = preg_replace('|-+|', '-', $s);
	$s = preg_replace('/#/', '', $s);
	$s = str_replace('.', '', $s);
	$s = trim($s, '-');
	return $s;
}
function seogorsel($s)
{
        $tr = array('ş', 'Ş', 'ı', 'I', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'Ç', 'ç', '(', ')', '/', ':', ',', '.');
        $eng = array('s', 's', 'i', 'i', 'i', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c', '', '', '-', '-', '', '.');
        $s = str_replace($tr, $eng, $s);
        $s = strtolower($s);
        $s = preg_replace('/&amp;amp;amp;amp;amp;amp;amp;amp;amp;.+?;/', '', $s);
        $s = preg_replace('/\s+/', '-', $s);
        $s = preg_replace('|-+|', '-', $s);
        $s = preg_replace('/#/', '', $s);
        $s = trim($s, '-');
        return $s;
}

/**
 * Ekleme veya güncelleme yapıldığında ilgili kaydı portal_urunler tablosuna
 * yansıtmak için kullanılır. Stokkoduna göre urunler tablosundaki verileri
 * okur ve uzaktaki veritabanında INSERT veya UPDATE yapar.
 */
function syncPortalProductImmediate(string $stokKodu): void
{
    global $db, $gemas_web_db;

    // Uzak veritabanı bağlantısı yoksa işlemi atla
    if (!$gemas_web_db || !($gemas_web_db instanceof mysqli)) {
        return;
    }

    $codeEsc = mysqli_real_escape_string($db, $stokKodu);
    $res = mysqli_query($db, "SELECT * FROM urunler WHERE stokkodu='$codeEsc' LIMIT 1");
    if (!$res) {
        return;
    }
    $row = mysqli_fetch_assoc($res);
    if (!$row) {
        return;
    }

    $colsRes = $gemas_web_db->query('SHOW COLUMNS FROM portal_urunler');
    if (!$colsRes) {
        return;
    }
    $portalCols = [];
    while ($c = $colsRes->fetch_assoc()) {
        $portalCols[] = $c['Field'];
    }

    $insertCols = array_filter($portalCols, fn($c) => $c !== 'urun_id');
    $updateCols = array_filter($insertCols, fn($c) => !in_array($c, ['stokkodu','durum','last_updated']));

    $values = [];
    foreach ($insertCols as $col) {
        if ($col === 'durum') {
            $values[] = '0';
        } elseif ($col === 'last_updated') {
            $values[] = 'NULL';
        } else {
            $val = $row[$col] ?? null;
            if ($val === null) {
                $values[] = 'NULL';
            } else {
                $values[] = "'" . $gemas_web_db->real_escape_string($val) . "'";
            }
        }
    }

    $insertSql = 'INSERT INTO portal_urunler (' . implode(',', $insertCols) . ') VALUES (' . implode(',', $values) . ')';

    $updateSet = [];
    foreach ($updateCols as $col) {
        $val = $row[$col] ?? null;
        if ($val === null) {
            $updateSet[] = "$col=NULL";
        } else {
            $updateSet[] = "$col='" . $gemas_web_db->real_escape_string($val) . "'";
        }
    }
    $codeRemote = $gemas_web_db->real_escape_string($stokKodu);
    $check = $gemas_web_db->query("SELECT 1 FROM portal_urunler WHERE stokkodu='$codeRemote' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $updateSql = 'UPDATE portal_urunler SET ' . implode(',', $updateSet) . " WHERE stokkodu='$codeRemote'";
        $gemas_web_db->query($updateSql);
    } else {
        $gemas_web_db->query($insertSql);
    }
}
?>
