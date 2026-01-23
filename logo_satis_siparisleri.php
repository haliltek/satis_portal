<?php
include "fonk.php";
oturumkontrol();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Bayi') {
    header("Location: anasayfa.php");
    exit;
}

gempa_logo_veritabani();

$logoOrders = [];
$errorMsg = "";

if ($gempa_logo_db) {
    try {
        // LG_566_01_ORFICHE Schema Reference Updates:
        // TRNET -> İşlem Dövizi Tutarı (Gerçek Dövüzli Tutar)
        // TRCURR -> İşlem Dövizi Türü (1:USD, 20:EUR, 160:TL)
        // DOCTRACKINGNR -> Belge İzleme Numarası
        // WITHPAYTRANS -> Ödemeli (1) / Ödemesiz (0)
        // STATUS -> 1:Öneri, 2:Sevkedilemez, 4:Sevkedilebilir/Onaylı (Genelde 4 kullanılır)
        
        $sqlLogo = "
            SELECT TOP 100
                O.LOGICALREF,
                O.DATE_ AS SIPARIS_TARIHI,
                O.FICHENO AS FIS_NO,
                O.DOCODE AS BELGE_NO,
                O.GENEXP1 AS BELGE_NO_2,
                C.DEFINITION_ AS CARI_UNVANI,
                '' AS SATIS_ELEMANI,            -- Satış Elemanı tablosu bulunamadığı için boş geçiyoruz
                O.NETTOTAL AS TUTAR,            -- Net Toplam (TL)
                O.TRNET AS DOVIZLI_TUTAR,       -- İşlem Dövizi Tutarı
                O.TRCURR AS PARA_BIRIMI,        -- İşlem Dövizi Türü
                O.STATUS AS DURUM,
                O.SOURCEINDEX AS AMBAR,
                O.BRANCH AS FABRIKA,            -- Isyeri (Division) -> Fabrika Sütunu İçin
                O.DEPARTMENT AS BOLUM,          -- Bölüm
                O.DOCTRACKINGNR AS DOK_IZLEM,   -- Dok. İzleme
                O.WITHPAYTRANS AS ODEMELI_DURUM, -- 1: Ödemeli, 0: Ödemesiz
                0 AS E_FATURA_DURUM,            -- E-Fatura kolonu bulunamadığı için 0
                C.ISPERSCOMP AS SAHIS_SIRKETI,
                O.TRCODE
            FROM LG_566_01_ORFICHE O
            LEFT JOIN LG_566_CLCARD C ON O.CLIENTREF = C.LOGICALREF
            -- LEFT JOIN LG_566_SLSMAN S ON O.SALESMANREF = S.LOGICALREF (Tablo bulunamadı)
            WHERE O.TRCODE = 1 
            ORDER BY O.DATE_ DESC, O.LOGICALREF DESC
        ";
        $stmt = $gempa_logo_db->query($sqlLogo);
        if ($stmt) {
            $logoOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $errorMsg = "Sorgu Hatası: " . $e->getMessage();
    }
} else {
    $errorMsg = "Veritabanı bağlantısı kurulamadı.";
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Logo Satış Siparişleri</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <!-- ... (Assets) ... -->
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #e4e4e4; 
            font-family: 'Tahoma', 'Segoe UI', sans-serif;
            margin: 0; padding: 0;
            overflow: hidden;
        }
        .erp-header {
            background-color: #9370DB; /* Logo Moru */
            color: white;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            height: 30px;
        }
        .erp-header i {
            margin-right: 5px; cursor:pointer;
        }
        .erp-table-container {
            background-color: white;
            border: 1px solid #ccc;
            height: calc(100vh - 30px);
            overflow: auto;
            position: relative;
        }
        .table-erp {
            width: 100%;
            font-size: 11px;
            border-collapse: separate; 
            border-spacing: 0;
            white-space: nowrap;
            cursor: default;
        }
        .table-erp thead th {
            background-color: #f0f0f0;
            color: #000;
            font-weight: normal; 
            border-right: 1px solid #d0d0d0;
            border-bottom: 1px solid #d0d0d0;
            padding: 4px 6px;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table-erp tbody td {
            border-right: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
            padding: 2px 6px;
            vertical-align: middle;
            color: #000;
        }
        .table-erp tbody tr:nth-of-type(odd) { background-color: #ffffff; }
        .table-erp tbody tr:nth-of-type(even) { background-color: #f8f9fa; }
        .table-erp tbody tr:hover { background-color: #ffe6a0 !important; }

        /* Status Colors */
        .color-oneri { color: #000; }        /* 1: Öneri (Siyah) */
        .color-sevkedilebilir { color: #008000 !important; } /* 4: Sevkedilebilir (Yeşil) */
        .color-beklemede { color: #FF8C00 !important; } /* 2: Beklemede (Turuncu) */
        .color-faturalandi { color: #444 !important; } /* Kapalı (Gri) */

        .curr-align { text-align: right; }
        .center-align { text-align: center; }

        .status-box {
            display: inline-block;
            width: 16px; height: 16px;
            line-height: 16px;
            text-align: center;
            border: 1px solid #ccc;
            font-size: 10px;
            font-weight: bold;
            border-radius: 3px;
            margin-right: 5px;
        }
        .status-O { background: #fff; color: #000; border-color: #999; } /* Öneri */
        .status-S { background: #dff0d8; color: #3c763d; border-color: #d6e9c6; } /* Sevk */
        .status-B { background: #fcf8e3; color: #8a6d3b; border-color: #faebcc; } /* Beklemede */
        .status-K { background: #f2f2f2; color: #999; border-color: #ddd; } /* Kapalı/Faturalandı */

    </style>
</head>
<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <header id="page-topbar">
            <?php include "menuler/ustmenu.php"; ?>
            <?php include "menuler/solmenu.php"; ?>
        </header>
        
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- ERP Header -->
                    <div class="erp-header">
                        <i class="bx bx-arrow-back" onclick="location.href='anasayfa.php'" title="Geri"></i>
                        <span class="ms-2">L</span> <span class="ms-1">Satış Siparişleri</span>
                    </div>

<div class="erp-table-container">
    <table class="table-erp">
        <thead>
            <tr>
                <th style="width: 25px;" class="center-align"><i class="bx bx-star"></i></th>
                <th style="width: 30px;" class="center-align">D</th> <!-- Durum (Ö/S/B) -->
                <th>TARİH</th>
                <th>FİŞ NO.</th>
                <th>BELGE NO.</th>
                <th style="min-width: 250px;">CARİ HESAP UNVANI</th>
                <th>İŞ AKIŞ KODU</th>
                <th class="curr-align">DÖVİZLİ TUTAR</th>
                <th>DOK. İZLEM...</th>
                <th>E FATURA</th>
                <th class="curr-align">TUTAR</th>
                <th class="center-align">AMBAR</th>
                <th>SATIŞ ELEMANI ...</th>
                <th class="center-align">BÖLÜM</th>
                <th class="center-align">FABRİKA</th>
                <th>TİP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($errorMsg)): ?>
                <tr><td colspan="16" style="color:red; text-align:center; padding:10px;"><?= $errorMsg ?></td></tr>
            <?php elseif (empty($logoOrders)): ?>
                <tr><td colspan="16" style="text-align:center; padding:10px;">Kayıt bulunamadı.</td></tr>
            <?php else: ?>
                <?php foreach ($logoOrders as $ord): 
                    // ... (Para birimi, Tarih formatı aynı) ...
                    $currCode = (int)$ord['PARA_BIRIMI'];
                    $currSym = 'TL';
                    if ($currCode == 1) $currSym = '$';
                    if ($currCode == 20) $currSym = '€';
                    
                    $dateFmt = date('d.m.Y', strtotime($ord['SIPARIS_TARIHI']));
                    
                    // E-Fatura
                    $eFatura = ''; 
                    // (Sütunlar geçici kapatıldığı için boş)

                    // DURUM (Status) Logic
                    // 1: Öneri (Ö)
                    // 2: Beklemede (B)
                    // 4: Sevkedilebilir (S)
                    $st = (int)$ord['DURUM'];
                    $statusChar = '';
                    $statusClass = 'status-K'; // Default Kapalı/Bilinmeyen
                    $rowColorClass = '';

                    if ($st == 1) {
                        $statusChar = 'Ö';
                        $statusClass = 'status-O';
                        $rowColorClass = 'color-oneri';
                    } elseif ($st == 2) {
                        $statusChar = 'K'; // Kullanıcı Beklemede dedi ama screenshot 'K' (Belki?) - Hayır standartlara uyalım: 2->Beklemede
                        // Ancak screenshotta S ve Ö var. B de olabilir.
                        $statusChar = 'B';
                        $statusClass = 'status-B';
                        $rowColorClass = 'color-beklemede';
                    } elseif ($st == 4) {
                        $statusChar = 'S'; // Sevkedilebilir
                        $statusClass = 'status-S';
                        $rowColorClass = 'color-sevkedilebilir';
                    } else {
                        $statusChar = 'D'; // Diğer
                    }
                    
                    $tip = ($ord['ODEMELI_DURUM'] == 1) ? 'Ödemeli' : 'Ödemesiz';
                    $tutar = number_format((float)$ord['TUTAR'], 2, ',', '.');
                    $dovizli = number_format((float)$ord['DOVIZLI_TUTAR'], 2, ',', '.') . ' ' . $currSym;

                ?>
                <tr class="<?= $rowColorClass ?>">
                    <td class="center-align"><input type="checkbox" disabled></td>
                    <td class="center-align">
                        <span class="status-box <?= $statusClass ?>"><?= $statusChar ?></span>
                    </td>
                    <td><?= $dateFmt ?></td>
                    <td><?= htmlspecialchars($ord['FIS_NO']) ?></td>
                    <td><?= htmlspecialchars($ord['BELGE_NO']) ?></td>
                    <td><strong><?= htmlspecialchars($ord['CARI_UNVANI']) ?></strong></td>
                    <td></td>
                    <td class="curr-align fw-bold"><?= $dovizli ?></td>
                    <td><?= htmlspecialchars($ord['DOK_IZLEM']) ?></td>
                    <td><?= $eFatura ?></td>
                    <td class="curr-align"><?= $tutar ?></td>
                    <td class="center-align"><?= $ord['AMBAR'] ?></td>
                    <td><?= htmlspecialchars($ord['SATIS_ELEMANI']) ?></td>
                    <td class="center-align"><?= $ord['BOLUM'] ?></td>
                    <td class="center-align"><?= $ord['FABRIKA'] ?></td>
                    <td><?= $tip ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

                </div>
            </div>
            <?php include "menuler/footer.php"; ?>
        </div>
    </div>

<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/metismenu/metisMenu.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
