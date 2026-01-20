<?php
include "fonk.php";
oturumkontrol();

function resolveCompanyName(mysqli $db, array $row): string
{
    $sirketArp = trim($row['sirket_arp_code'] ?? '');
    if ($sirketArp !== '') {
        $stmt = $db->prepare("SELECT s_adi FROM sirket WHERE s_arp_code=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $sirketArp);
            $stmt->execute();
            $sir = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($sir) {
                $name = trim($sir['s_adi'] ?? '');
                if ($name !== '') {
                    return $name;
                }
            }
        }
    }
    $name = trim($row['musteriadi'] ?? '');
    return $name;
}

function getWhatsappMessage(mysqli $db, int $teklifId): string
{
    include "include/url.php";
    $stmt = $db->prepare("SELECT teklifkodu, musteriadi, kime, hazirlayanid, sirket_arp_code FROM ogteklif2 WHERE id=?");
    $stmt->bind_param('i', $teklifId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $adiSoyadi  = resolveCompanyName($db, $row);
    $teklifNo   = $row['teklifkodu'] ?? '';
    $hazirlayan = (int)($row['hazirlayanid'] ?? 0);

    $yonMail = '';
    if ($hazirlayan) {
        $s = $db->prepare("SELECT mailposta FROM yonetici WHERE yonetici_id=?");
        $s->bind_param('i', $hazirlayan);
        $s->execute();
        $yonRow = $s->get_result()->fetch_assoc();
        $yonMail = $yonRow['mailposta'] ?? '';
        $s->close();
    }

    $urlTeklif = $url . '/offer_detail.php?te=' . $teklifId . '&sta=Teklif';

    return 'Sayın ' . $adiSoyadi . ', ' . $teklifNo .
        ' numaralı teklifinizi onaylamak, reddetmek veya revize etmek için ' .
        $urlTeklif . ' adresini ziyaret edebilirsiniz. Sorularınız için ' . $yonMail . '.';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $sistemayar["description"]; ?>" name="description" />
    <meta content="<?php echo $sistemayar["keywords"]; ?>" name="keywords" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <!-- Bootstrap Css -->
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/custom.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <style type="text/css">
        a {
            text-decoration: none;
        }

        .altbos {
            margin-bottom: 2%;
            margin: 1%;
        }

        .numara {
            font-size: 25px;
            font-weight: 700;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <!-- Begin page -->
    <div id="layout-wrapper">
        <header id="page-topbar">
            <?php include "menuler/ustmenu.php"; ?>
            <?php include "menuler/solmenu.php";
            if ($tanimlar == 'Hayır') {
                echo '<script language="javascript">window.location="anasayfa.php";</script>';
                die();
            } ?>
        </header>
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <?php
                            if (isset($_POST['duzenleme'])) {
                                $departman = $_POST["departman"];
                                $icerikid = $_POST["icerikid"];
                                $kategoriduzenleme = "UPDATE ogteklif2 SET atama = '$departman' WHERE id= '$icerikid'";
                                $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                if ($duzenleme) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Siparişe Departman Ataması','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Departman Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=teklifsiparisler.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Departman Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Departman Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=teklifsiparisler.php"> ';
                                }
                            } else  if (isset($_POST['duzenleme2'])) {
                                $durum = $_POST["durum"];
                                $statu = $_POST["statu"];
                                $odemenot = $_POST["odemenot"];
                                $icerikid = $_POST["icerikid"];
                                $kategoriduzenleme = "UPDATE ogteklif2 SET durum = '$durum',statu = '$statu',odemetipi = '$odemenot' WHERE id= '$icerikid'";
                                $duzenleme = mysqli_query($db, $kategoriduzenleme);
                                if ($duzenleme) {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Siparişe Durum - Statü Ataması','$yonetici_id_sabit','$zaman','Başarılı')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-success" role="alert">  Sayın ' . $adsoyad . ' <br> Durum - Statü Başarıyla Kaydedilmiştir. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=teklifsiparisler.php"> ';
                                } else {
                                    $logbaglanti = "INSERT INTO log_yonetim(islem,personel,tarih,durum) VALUES('Durum - Statü Güncelleme','$yonetici_id_sabit','$zaman','Başarısız')";
                                    $logislem = mysqli_query($db, $logbaglanti);
                                    echo '<div class="alert alert-danger" role="alert">  Sayın ' . $adsoyad . ' <br> Durum - Statü Malesef Kaydedilemedi. Lütfen Bekleyiniz...</div>  ';
                                    echo '<meta http-equiv="refresh" content="2; url=teklifsiparisler.php"> ';
                                }
                            } else  if (isset($_POST['gondereposta'])) {
                                $metin = addslashes($_POST["metin"]);
                                $eposta = addslashes($_POST["eposta"]);
                                $adsoyad = addslashes($_POST["adsoyad"]);
                                $notu = addslashes($_POST["notu"]);
                                $url = addslashes($_POST["url"]);
                                $icerikid = $_POST["icerikid"];
                                $AdiSoyadi = trim(strip_tags($adsoyad));
                                $MailAdresi = trim(strip_tags($eposta));
                                $MesajKonusu = trim(strip_tags('Gemas Teklif Hk.'));
                                $Mesaj = trim(strip_tags('Gemas Teklif Hk.'));
                                $Mesaj = "
            <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
            <html xmlns='http://www.w3.org/1999/xhtml'>
            <head>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'>
            <title>New Assignment</title>
            <style type='text/css'>
            /* reset */
            article,
            aside,
            details,
            figcaption,
            figure,
            footer,
            header,
            hgroup,
            nav,
            section,
            summary {
              display: block
          }
          audio,
          canvas,
          video {
              display: inline-block;
      *display: inline;
      *zoom: 1
          }
          audio:not([controls]) {
              display: none;
              height: 0
          }
          [hidden] {
              display: none
          }
          html {
              font-size: 100%;
              -webkit-text-size-adjust: 100%;
              -ms-text-size-adjust: 100%
          }
          html,
          button,
          input,
          select,
          textarea {
              font-family: sans-serif
          }
          body {
              margin: 0
          }
          a:focus {
              outline: thin dotted
          }
          a:active,
          a:hover {
              outline: 0
          }
          h1 {
              font-size: 2em;
              margin: 0 0.67em 0
          }
          h2 {
              font-size: 1.5em;
              margin: 0 0 .83em 0
          }
          h3 {
              font-size: 1.17em;
              margin: 1em 0
          }
          h4 {
              font-size: 1em;
              margin: 1.33em 0
          }
          h5 {
              font-size: .83em;
              margin: 1.67em 0
          }
          h6 {
              font-size: .75em;
              margin: 2.33em 0
          }
          abbr[title] {
              border-bottom: 1px dotted
          }
          b,
          strong {
              font-weight: bold
          }
          blockquote {
              margin: 1em 40px
          }
          dfn {
              font-style: italic
          }
          mark {
              background: #ff0;
              color: #000
          }
          p,
          pre {
              margin: 1em 0
          }
          code,
          kbd,
          pre,
          samp {
              font-family: monospace, serif;
              _font-family: 'courier new', monospace;
              font-size: 1em
          }
          pre {
              white-space: pre;
              white-space: pre-wrap;
              word-wrap: break-word
          }
          q {
              quotes: none
          }
          q:before,
          q:after {
              content: '';
              content: none
          }
          small {
              font-size: 75%
          }
          sub,
          sup {
              font-size: 75%;
              line-height: 0;
              position: relative;
              vertical-align: baseline
          }
          sup {
              top: -0.5em
          }
          sub {
              bottom: -0.25em
          }
          dl,
          menu,
          ol,
          ul {
              margin: 1em 0
          }
          dd {
              margin: 0 0 0 40px
          }
          menu,
          ol,
          ul {
              padding: 0 0 0 40px
          }
          nav ul,
          nav ol {
              list-style: none;
              list-style-image: none
          }
          img {
              border: 0;
              -ms-interpolation-mode: bicubic
          }
          svg:not(:root) {
              overflow: hidden
          }
          figure {
              margin: 0
          }
          form {
              margin: 0
          }
          fieldset {
              border: 1px solid #c0c0c0;
              margin: 0 2px;
              padding: .35em .625em .75em
          }
          legend {
              border: 0;
              padding: 0;
              white-space: normal;
      *margin-left: -7px
          }
          button,
          input,
          select,
          textarea {
              font-size: 100%;
              margin: 0;
              vertical-align: baseline;
      *vertical-align: middle
          }
          button,
          input {
              line-height: normal
          }
          button,
          html input[type='button'],
          input[type='reset'],
          input[type='submit'] {
              -webkit-appearance: button;
              cursor: pointer;
      *overflow: visible
          }
          button[disabled],
          input[disabled] {
              cursor: default
          }
          input[type='checkbox'],
          input[type='radio'] {
              box-sizing: border-box;
              padding: 0;
      *height: 13px;
      *width: 13px
          }
          input[type='search'] {
              -webkit-appearance: textfield;
              -moz-box-sizing: content-box;
              -webkit-box-sizing: content-box;
              box-sizing: content-box
          }
          input[type='search']::-webkit-search-cancel-button,
          input[type='search']::-webkit-search-decoration {
              -webkit-appearance: none
          }
          button::-moz-focus-inner,
          input::-moz-focus-inner {
              border: 0;
              padding: 0
          }
          textarea {
              overflow: auto;
              vertical-align: top
          }
          table {
              border-collapse: collapse;
              border-spacing: 0
          }
          /* custom client-specific styles including styles for different online clients */
          .ReadMsgBody {
              width: 100%;
          }
          .ExternalClass {
              width: 100%;
          }
          /* hotmail / outlook.com */
          .ExternalClass,
          .ExternalClass p,
          .ExternalClass span,
          .ExternalClass font,
          .ExternalClass td,
          .ExternalClass div {
              line-height: 100%;
          }
          /* hotmail / outlook.com */
          table,
          td {
              mso-table-lspace: 0pt;
              mso-table-rspace: 0pt;
          }
          /* Outlook */
    #outlook a {
          padding: 0;
      }
      /* Outlook */
      img {
        -ms-interpolation-mode: bicubic;
        display: block;
        outline: none;
        text-decoration: none;
    }
    /* IExplorer */
    body,
    table,
    td,
    p,
    a,
    li,
    blockquote {
        -ms-text-size-adjust: 100%;
        -webkit-text-size-adjust: 100%;
        font-weight: normal !important;
    }
    .ExternalClass td[class='ecxflexibleContainerBox'] h3 {
        padding-top: 10px !important;
    }
    /* hotmail */
    /* email template styles */
    h1 {
        display: block;
        font-size: 26px;
        font-style: normal;
        font-weight: normal;
        line-height: 100%;
    }
    h2 {
        display: block;
        font-size: 20px;
        font-style: normal;
        font-weight: normal;
        line-height: 120%;
    }
    h3 {
        display: block;
        font-size: 17px;
        font-style: normal;
        font-weight: normal;
        line-height: 110%;
    }
    h4 {
        display: block;
        font-size: 18px;
        font-style: italic;
        font-weight: normal;
        line-height: 100%;
    }
    .flexibleImage {
        height: auto;
    }
    table[class=flexibleContainerCellDivider] {
        padding-bottom: 0 !important;
        padding-top: 0 !important;
    }
    body,
    #bodyTbl {
    background-color: #E1E1E1;
}
    #emailHeader {
background-color: #E1E1E1;
}
    #emailBody {
background-color: #FFFFFF;
}
    #emailFooter {
background-color: #E1E1E1;
}
.textContent {
    color: #8B8B8B;
    font-family: Helvetica;
    font-size: 16px;
    line-height: 125%;
    text-align: Left;
}
.textContent a {
    color: #205478;
    text-decoration: underline;
}
.emailButton {
    background-color: #205478;
    border-collapse: separate;
}
.buttonContent {
    color: #FFFFFF;
    font-family: Helvetica;
    font-size: 18px;
    font-weight: bold;
    line-height: 100%;
    padding: 15px;
    text-align: center;
}
.buttonContent a {
    color: #FFFFFF;
    display: block;
    text-decoration: none !important;
    border: 0 !important;
}
    #invisibleIntroduction {
display: none;
display: none !important;
}
/* hide the introduction text */
/* other framework hacks and overrides */
span[class=ios-color-hack] a {
  color: #275100 !important;
  text-decoration: none !important;
}
/* Remove all link colors in IOS (below are duplicates based on the color preference) */
span[class=ios-color-hack2] a {
  color: #205478 !important;
  text-decoration: none !important;
}
span[class=ios-color-hack3] a {
  color: #8B8B8B !important;
  text-decoration: none !important;
}
/* phones and sms */
.a[href^='tel'],
a[href^='sms'] {
  text-decoration: none !important;
  color: #606060 !important;
  pointer-events: none !important;
  cursor: default !important;
}
.mobile_link a[href^='tel'],
.mobile_link a[href^='sms'] {
  text-decoration: none !important;
  color: #606060 !important;
  pointer-events: auto !important;
  cursor: default !important;
}
/* responsive styles */
@media only screen and (max-width: 480px) {
  body {
    width: 100% !important;
    min-width: 100% !important;
}
table[id='emailHeader'],
table[id='emailBody'],
table[id='emailFooter'],
table[class='flexibleContainer'] {
    width: 100% !important;
}
td[class='flexibleContainerBox'],
td[class='flexibleContainerBox'] table {
    display: block;
    width: 100%;
    text-align: left;
}
td[class='imageContent'] img {
    height: auto !important;
    width: 100% !important;
    max-width: 100% !important;
}
img[class='flexibleImage'] {
    height: auto !important;
    width: 100% !important;
    max-width: 100% !important;
}
img[class='flexibleImageSmall'] {
    height: auto !important;
    width: auto !important;
}
table[class='flexibleContainerBoxNext'] {
    padding-top: 10px !important;
}
table[class='emailButton'] {
    width: 100% !important;
}
td[class='buttonContent'] {
    padding: 0 !important;
}
td[class='buttonContent'] a {
    padding: 15px !important;
}
}
</style>
<!--
MS Outlook custom styles
-->
<!--[if mso 12]>
<style type='text/css'>
.flexibleContainer{display:block !important; width:100% !important;}
</style>
<![endif]-->
<!--[if mso 14]>
<style type='text/css'>
.flexibleContainer{display:block !important; width:100% !important;}
</style>
<![endif]-->
</head>
<body bgcolor='#E1E1E1' leftmargin='0' marginwidth='0' topmargin='0' marginheight='0' offset='0'>
<center style='background-color:#E1E1E1;'>
<table border='0' cellpadding='0' cellspacing='0' height='100%' width='100%' id='bodyTbl' style='table-layout: fixed;max-width:100% !important;width: 100% !important;min-width: 100% !important;'>
<tr>
<td align='center' valign='top' id='bodyCell'>
<table bgcolor='#FFFFFF' border='0' cellpadding='0' cellspacing='0' width='500' id='emailBody'>
<tr>
<td align='center' valign='top'>
<table border='0' cellpadding='0' cellspacing='0' width='100%' style='color:#FFFFFF;' bgcolor='#fff'>
<tr>
<td align='center' valign='top'>
<table border='0' cellpadding='0' cellspacing='0' width='500' class='flexibleContainer'>
<tr>
<td align='center' valign='top' width='500' class='flexibleContainerCell'>
<table border='0' cellpadding='30' cellspacing='0' width='100%'>
<tr>
<td align='center' valign='top' class='textContent'>
<h1 style='color:#FFFFFF;line-height:100%;font-family:Helvetica,Arial,sans-serif;font-size:35px;font-weight:normal;margin-bottom:5px;text-align:center;'><center> </center></h1>
<!--    <h2 style='text-align:center;font-weight:normal;font-family:Helvetica,Arial,sans-serif;font-size:23px;margin-bottom:10px;color:#C9BC20;line-height:135%;'>" . $MesajKonusu . "</h2> -->
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td align='center' valign='top'>
<table border='0' cellpadding='0' cellspacing='0' width='100%' bgcolor='#0063A8' bgcolor='#fff'>
<tr>
<td align='center' valign='top'>
<table border='0' cellpadding='0' cellspacing='0' width='500' class='flexibleContainer'>
<tr>
<td align='center' valign='top' width='500' class='flexibleContainerCell'>
<table border='0' cellpadding='30' cellspacing='0' width='100%'>
<tr>
<td align='center' valign='top'>
<table border='0' cellpadding='0' cellspacing='0' width='100%'>
<tr>
<td valign='top' class='textContent'>
<h3 style='color:#fff;line-height:125%;font-family:Helvetica,Arial,sans-serif;font-size:20px;font-weight:normal;margin-top:0;margin-bottom:3px;text-align:left;'>" . $MesajKonusu . "</h3><br>
<div style='text-align:left;font-family:Helvetica,Arial,sans-serif;font-size:15px;margin-bottom:0;margin-top:3px;color:#fff;line-height:135%;'><b>Sayın: " . $AdiSoyadi . "</b>;<br> 
" . $metin . " <br> <br><br>" . $notu . "<br><br>
<center>
<h3>URL: </h3></center>
<b><center><h2><a href='" . $url . "' class='btn btn-warning' style='background-color:yellow; color:black; padding:2%; '>TEKLİFİ BURADAN İNCELEYİN!</a></h2></center></b>
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td align='center' valign='top'>
<table border='0' cellpadding='0' cellspacing='0' width='100%' bgcolor='#F8F8F8'>
<tr>
<td align='center' valign='top'>
<table border='0' cellpadding='0' cellspacing='0' width='500' class='flexibleContainer' style='margin-top:-10px; margin-bottom:-20px'>
<tr>
<td align='center' valign='top' width='500' class='flexibleContainerCell'>
<table border='0' cellpadding='30' cellspacing='0' width='100%'>
<tr>
<td align='center' valign='top'>
<table border='0' cellpadding='0' cellspacing='0' width='50%' class='emasilButton' >
<tr>
<td align='left' valign='middle' class='buttonContent' style=''>
<p style='color:black; font-size:14px;'><b>" . $yoneticisorgula["unvan"] . "</b>  
<i style='color:black'>" . $yoneticisorgula["telefon"] . "</i><br>
<i style='color:black'>b2b@gemas.com</i>
</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
<!-- footer -->
<table bgcolor='#E1E1E1' border='0' cellpadding='0' cellspacing='0' width='500' id='emailFooter'>
<tr>
<td align='center' valign='top'>
<table border='0' cellpadding='0' cellspacing='0' width='100%'>
<tr>
<td align='center' valign='top'>
<table border='0' cellpadding='0' cellspacing='0' width='500' class='flexibleContainer'>
<tr>
<td align='center' valign='top' width='500' class='flexibleContainerCell'>
<table border='0' cellpadding='30' cellspacing='0' width='100%'>
<tr>
<td valign='top' bgcolor='#E1E1E1'>
<div style='font-family:Helvetica,Arial,sans-serif;font-size:13px;color:#828282;text-align:center;line-height:120%;'>
<div>Copyright &#169; 2024. Tüm Hakları Saklıdır.</div>
<div>Bu e-posta sizlere yanlışlıkla ulaştıysa <b>" . $yoneticisorgula["mailposta"] . "</b> adresini bilgilendirmenizi rica ederiz.</div>
<div><hr>
 </div>
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
<!-- // end of footer -->
</td>
</tr>
</table>
</center>
</body>
</html>
";

                                include 'phpmail/class.phpmailer.php';
                                $mail = new PHPMailer();
                                $mail->IsSMTP();
                                $mail->SMTPAuth = true;
                                $mail->Host = $yoneticisorgula["mailsmtp"];
                                $mail->Port = $yoneticisorgula["mailport"];
                                $mail->Username = $yoneticisorgula["mailposta"];
                                $mail->Password = $yoneticisorgula["mailparola"];
                                $mail->SetFrom($mail->Username, $adsoyad);
                                $mail->AddAddress($eposta, $adsoyad);
                                $mail->CharSet = 'UTF-8';
                                $mail->Subject = 'Teklifiniz Hk.';
                                $mail->MsgHTML("$Mesaj"); //Mail Mesaj İçeriği
                                if ($mail->Send()) {
                                    // 'Mail gönderildi!';
                                    echo '<script>alert("Teklifiniz Kullanıcıya Başarıyla İletilmiştir.");</script>';
                                    echo '<script language="javascript">window.location="teklifsiparisler.php";</script>';
                                } else {
                                    echo 'Mail gönderilirken bir hata oluştu: ' . $mail->ErrorInfo;
                                    exit;
                                }
                            } else if (isset($_POST['gonderwhatsapp'])) {
                                $phone   = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
                                $message = $_POST['wmessage'] ?? '';
                                $icerikid = $_POST['icerikid'] ?? 0;
                                if ($phone && $message && $icerikid) {
                                    mysqli_query($db, "UPDATE ogteklif2 SET durum='Teklif Gönderildi / Onay Bekleniyor' WHERE id='$icerikid'");
                                    $urlwa = 'https://wa.me/9' . $phone . '?text=' . urlencode($message);
                                    echo '<script language="javascript">window.location="' . $urlwa . '";</script>';
                                } else {
                                    echo '<script>alert("Eksik veri");</script>';
                                }
                            }
                            ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">SÜREÇLERİ DEVAM EDEN VE Teklif Birimi Tarafından Verilen Teklifler</h4>
                                    <a href="tumislemler.php" class="btn btn-info">Tüm İşlemler</a>
                                    <div class="table-responsive">
                                        <table id="datatable" class="table table-bordered table-responsive nsowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <!-- <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;"> -->
                                            <thead>
                                                <tr>
                                                    <th scope="row" class="tablobaslik">Atanan</th>
                                                    <th scope="row" class="tablobaslik">Teklif No</th>
                                                    <th scope="row" class="tablobaslik">Hazırlayan</th>
                                                    <th scope="row" class="tablobaslik">Teklif Verilen</th>
                                                    <th scope="row" class="tablobaslik">Cari Mi?</th>
                                                    <th scope="row" class="tablobaslik"> Müş. Telefon</th>
                                                    <th scope="row" class="tablobaslik"> Teklif Tarihi</th>
                                                    <th scope="row" class="tablobaslik"> Genel Toplam</th>
                                                    <th scope="row" class="tablobaslik"> Durum</th>
                                                    <th scope="row" class="tablobaslik"> İşlem</th>
                                                </tr>
                                            </thead>
                                            <tbody class="yazilar">
                                                <?php
                                                // where durum!='Sipariş' and durum!='Sipariş Ödemesi Bekleniyor'
                                                $benid = $_SESSION["yonetici_id"];
                                                $kontrolKullaniciAdi3 = mysqli_query($db, "SELECT * FROM  ogteklif2");
                                                while ($dev2 = mysqli_fetch_array($kontrolKullaniciAdi3)) {
                                                    $teklifid =  $dev2["id"];
                                                    $query = mysqli_query($db, "SELECT * FROM ogteklif2 where id='$teklifid'");
                                                    $row = mysqli_fetch_array($query);
                                                    $dolarkuru =  $row['dolarkur'];
                                                    $eurokuru =  $row['eurokur'];
                                                    $kurtarihi =  $row['kurtarih'];
                                                    $query1 = mysqli_query($db, "SELECT SUM(tutar) as toplam FROM ogteklifurun2 where teklifid='$teklifid' and doviz='TL'");
                                                    $row1 = mysqli_fetch_array($query1);
                                                    $tller =  $row1['toplam'];
                                                    $query2 = mysqli_query($db, "SELECT SUM(tutar) as toplam FROM ogteklifurun2 where teklifid='$teklifid' and doviz='EUR'");
                                                    $row2 = mysqli_fetch_array($query2);
                                                    $eurolar =  $row2['toplam'];
                                                    $query3 = mysqli_query($db, "SELECT SUM(tutar) as toplam FROM ogteklifurun2 where teklifid='$teklifid' and doviz='USD'");
                                                    $row3 = mysqli_fetch_array($query3);
                                                    $dolarlar =  $row3['toplam'];
                                                    $eurolu = $eurolar * $eurokuru;
                                                    $dolarli = $dolarlar * $dolarkuru;
                                                    $tops = $tller + $eurolu + $dolarli;
                                                    $dur = $dev2["teklifsiparis"];
                                                ?>
                                                    <tr>
                                                        <td data-bs-toggle="modal" data-bs-target=".yenikategori<?php echo $dev2["id"]; ?>" style="cursor:pointer;"><?php echo $dev2["atama"]; ?></td>
                                                        <td><?php echo $dev2["teklifkodu"]; ?></td>
                                                        <td><?php $hazx =  $dev2["hazirlayanid"];
                                                            $res = mysqli_query($db, "SELECT username FROM b2b_users WHERE id='$hazx'");
                                                            $bayi = mysqli_fetch_array($res);
                                                            $prepInfo = $dbManager->resolvePreparer($dev2["hazirlayanid"] ?? "");
                                                            $hazirlayanAd = $prepInfo["name"] ?: "";
                                                            $kaynak = $prepInfo["source"];
                                                            echo htmlspecialchars($hazirlayanAd);  ?><br><small style="font-size: 9px;">(<?= $kaynak ?>)</small></td>
                                                        <td><?php
                                                            $musteris = $dev2["musteriid"];
                                                            if ($musteris == '786') {
                                                                echo $kimehazir =  $dev2["musteriadi"];
                                                            } else {
                                                                $musteribag = mysqli_query($db, "SELECT * FROM  sirket where sirket_id='$musteris'");
                                                                $musteribilgi = mysqli_fetch_array($musteribag);
                                                                echo $kimehazir =  $musteribilgi["s_adi"];
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $musteris = $dev2["musteriid"];
                                                            if ($musteris == '786') {
                                                                echo '<b style="color:red">HYR</b>';
                                                            } else {
                                                                echo '<b style="color:green">EVT</b>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?php
                                                            $musteris = $dev2["musteriid"];
                                                            if ($musteris == '786') {

                                                                echo $cep =  trim($dev2["projeadi"]);
                                                            } else {
                                                                $musteribag = mysqli_query($db, "SELECT * FROM  sirket where sirket_id='$musteris'");
                                                                $musteribilgi = mysqli_fetch_array($musteribag);
                                                                $perids = $musteribilgi["yetkili"];
                                                                $musteribag3 = mysqli_query($db, "SELECT * FROM  personel where personel_id='$perids'");
                                                                $musteribilgi3 = mysqli_fetch_array($musteribag3);

                                                                echo  $cep =  trim($musteribilgi3["p_cep"]);
                                                            }
                                                            // Allow modal even when phone number is missing so
                                                            // the user can provide it before sending
                                                            $waAttr = 'data-bs-toggle="modal" data-bs-target=".whatsapp' . $dev2['id'] . '"';
                                                            ?> </td>
                                                        <td><?php echo $dev2["tekliftarihi"]; ?></td>
                                                        <td><?php echo  number_format($tops * 1.18, 2, ',', '.'); ?> ₺</td>
                                                        <td data-bs-toggle="modal" data-bs-target=".durum<?php echo $dev2["id"]; ?>" style="cursor:pointer;"><?php echo $durumu =  $dev2["durum"]; ?></td>
                                                        <td>
                                                            <?php
                                                            $musteris = $dev2["musteriid"];
                                                            if ($musteris == '786') {
                                                                $kimehazir =  $dev2["musteriadi"];
                                                                $cep =  trim($dev2["projeadi"]);
                                                            } else {
                                                                $musteribag = mysqli_query($db, "SELECT * FROM  sirket where sirket_id='$musteris'");
                                                                $musteribilgi = mysqli_fetch_array($musteribag);
                                                                $perids = $musteribilgi["yetkili"];
                                                                $musteribag3 = mysqli_query($db, "SELECT * FROM  personel where personel_id='$perids'");
                                                                $musteribilgi3 = mysqli_fetch_array($musteribag3);
                                                                $kimehazir =  $musteribilgi3["p_adi"] . ' ' . $musteribilgi3["p_soyadi"];
                                                                $cep =  trim($musteribilgi3["p_cep"]);
                                                            }
                                                            $tekkod = $dev2["teklifkodu"];
                                                            $durr = $dev2["durum"];
                                                            $metin2 = '  ' . $tekkod . ' numaralı teklifinizin durumu  ' . $durr . '  olarak belirtilmiştir. Teklifi incelemek için aşağıdaki url adresi aracılığı ile inceleyebilirsiniz.   ';
                                                            $metin =  $kimehazir . ' ' . $metin2;
                                                            include "include/url.php";
                                                            $urlsi =  $url . '/offer_detail.php?te=' . $dev2["id"] . '&sta=Teklif ';
                                                            ?>
                                                            <?php
                                                            if ($durumu == 'Sipariş Onay Bekleniyor') { ?>
                                                                <button type="button" class="btn btn-info btn-sm" style="margin-top:10px" data-bs-toggle="modal" data-bs-target=".gonder<?php echo $dev2["id"]; ?>">Müşteriye E-Posta Gönder</button>
                                                                <button type="button" class="btn btn-primary btn-sm waves-effect waves-light" <?= $waAttr ?>>Müşteriye Whatsapp Gönder</button>
                                                                <a target="_blank" href="offer_detail.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-success btn-sm">Siparişi İncele</a>
                                                                <a target="_blank" href="teklifsiparisler-duzenle.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-success btn-sm">Sipariş Düzenleyin</a>
                                                            <?php } else 
     if ($durumu == 'Sipariş Onay Bekleniyor') { ?>
                                                                <button type="button" class="btn btn-info btn-sm" style="margin-top:10px" data-bs-toggle="modal" data-bs-target=".gonder<?php echo $dev2["id"]; ?>">Müşteriye E-Posta Gönder</button>
                                                                <button type="button" class="btn btn-primary btn-sm waves-effect waves-light" <?= $waAttr ?>>Müşteriye Whatsapp Gönder</button>
                                                                <a target="_blank" href="offer_detail.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-success btn-sm">Siparişi İncele</a>
                                                                <a target="_blank" href="teklifsiparisler-duzenle.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-primary btn-sm">Sipariş Düzenleyin</a>
                                                            <?php } else 
        if ($durumu == 'Teklif Onay Bekleniyor') { ?>
                                                                <button type="button" class="btn btn-info btn-sm" style="margin-top:10px" data-bs-toggle="modal" data-bs-target=".gonder<?php echo $dev2["id"]; ?>">Müşteriye E-Posta Gönder</button>
                                                                <button type="button" class="btn btn-primary btn-sm waves-effect waves-light" <?= $waAttr ?>>Müşteriye Whatsapp Gönder</button>
                                                                <a target="_blank" href="offer_detail.php?te=<?php echo $dev2["id"]; ?>&sta=Teklif" class="btn btn-success btn-sm">Teklif İncele</a>
                                                                <a target="_blank" href="teklifsiparisler-duzenle.php?te=<?php echo $dev2["id"]; ?>&sta=Teklif" class="btn btn-primary btn-sm">Teklifi Düzenleyin</a>
                                                            <?php } else
         if ($durumu == 'Teklif İptal Edildi') { ?>
                                                                <button type="button" class="btn btn-info btn-sm" style="margin-top:10px" data-bs-toggle="modal" data-bs-target=".gonder<?php echo $dev2["id"]; ?>">Müşteriye E-Posta Gönder</button>
                                                                <button type="button" class="btn btn-primary btn-sm waves-effect waves-light" <?= $waAttr ?>>Müşteriye Whatsapp Gönder</button>
                                                                <a target="_blank" href="offer_detail.php?te=<?php echo $dev2["id"]; ?>&sta=Teklif" class="btn btn-success btn-sm">Teklifi İncele</a>
                                                            <?php } else 
      if ($durumu == 'Sipariş Ödemesi Bekleniyor') { ?>
                                                                <a target="_blank" href="offer_detail.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-success btn-sm">Siparişi İncele</a>
                                                                <a target="_blank" href="teklifsiparisler-duzenle.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-primary btn-sm">Siparişi Düzenleyin</a>
                                                            <?php } else  if ($durumu == 'Kontrol Aşamasında') { ?>
                                                                <button type="button" class="btn btn-info btn-sm" style="margin-top:10px" data-bs-toggle="modal" data-bs-target=".gonder<?php echo $dev2["id"]; ?>">Müşteriye E-Posta Gönder</button>
                                                                <button type="button" class="btn btn-primary btn-sm waves-effect waves-light" <?= $waAttr ?>>Müşteriye Whatsapp Gönder</button>
                                                                <a target="_blank" href="offer_detail.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-success btn-sm">Siparişi İncele</a>
                                                            <?php } else { ?>
                                                                <button type="button" class="btn btn-info btn-sm" style="margin-top:10px" data-bs-toggle="modal" data-bs-target=".gonder<?php echo $dev2["id"]; ?>">Müşteriye E-Posta Gönder</button>
                                                                <button type="button" class="btn btn-primary btn-sm waves-effect waves-light" <?= $waAttr ?>>Müşteriye Whatsapp Gönder</button>
                                                                <a target="_blank" href="offer_detail.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-success btn-sm">Siparişi İncele</a>
                                                                <a target="_blank" href="teklifsiparisler-duzenle.php?te=<?php echo $dev2["id"]; ?>&sta=Sipariş" class="btn btn-primary btn-sm">Sipariş Düzenleyin</a>
                                                            <?php }  ?>
                                                    </tr>
                                                <?php  } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div> <!-- Card-Body Bitişi -->
                            </div>
                        </div>
                    </div>
                </div> <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            <?php include "menuler/footer.php"; ?>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->
    <?php
    // where durum!='Sipariş' and durum!='Sipariş Ödemesi Bekleniyor'
    $kontrolKullaniciAdi3 = mysqli_query($db, "SELECT * FROM  ogteklif2 where  durum!='Tamamlandı'");
    while ($markalar = mysqli_fetch_array($kontrolKullaniciAdi3)) {
    ?>
        <div class="modal fade yenikategori<?php echo $markalar["id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $markalar["teklifkodu"]; ?></b></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="teklifsiparisler.php" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Hangi Departmana Atansın?</label>
                                        <select name="departman" class="form-control" id="validationCustom01" required>
                                            <option value="<?php echo $markalar["atama"]; ?>" selected><?php echo $markalar["atama"]; ?> Seçili Durumda</option>
                                            <?php $kontrolKullaniciAdi32 = mysqli_query($db, "SELECT * FROM  departmanlar");
                                            while ($departman = mysqli_fetch_array($kontrolKullaniciAdi32)) {  ?>
                                                <option value="<?php echo $departman["departman"] ?>"><?php echo $departman["departman"] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $markalar["id"]; ?>" hidden>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                            <button type="submit" name="duzenleme" class="btn btn-success">Düzenleyin!</button>
                        </div>
                    </form>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div>
    <?php } ?>
    <?php
    // where durum!='Sipariş' and durum!='Sipariş Ödemesi Bekleniyor'
    $kontrolKullaniciAdi3 = mysqli_query($db, "SELECT * FROM  ogteklif2 where  durum!='Tamamlandı'");
    while ($teklif = mysqli_fetch_array($kontrolKullaniciAdi3)) {
    ?>
        <div class="modal fade sonislem<?php echo $teklif["id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $teklif["teklifkodu"]; ?></b> Son Atanan: <?php echo $teklif["atama"]; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <?php
                            $siparishazir = $teklif["siparishazir"];
                            $faturaolustu = $teklif["faturaolustu"];
                            $satinalmayagonder = $teklif["satinalmayagonder"];
                            $satinalmanotu = $teklif["satinalmanotu"];
                            $eksikmalzeme = $teklif["eksikmalzeme"];
                            $depodabeklemede = $teklif["depodabeklemede"];
                            $aracayuklendi = $teklif["aracayuklendi"];
                            $aractasevkiyatta = $teklif["aractasevkiyatta"];
                            $islemtamamlandi = $teklif["islemtamamlandi"];


                            ?>
                            <div class="col-md-3 altbos" style="border:3px solid <?php if ($siparishazir == 'Evet') {
                                                                                        echo 'green';
                                                                                    } else {
                                                                                        echo 'red';
                                                                                    } ?>;">
                                <h3 class="numara">1. <small>Aşama</small></h3>
                                <img src="images/siparis.png" style="width:100%; height:100px">
                                <center><b>Sipariş Hazır Mı?</b></center>
                            </div>
                            <div class="col-md-3 altbos" style="border:3px solid <?php if ($faturaolustu == 'Evet') {
                                                                                        echo 'green';
                                                                                    } else {
                                                                                        echo 'red';
                                                                                    } ?>;">
                                <h3 class="numara">2. <small>Aşama</small></h3>
                                <img src="images/fatura.png" style="width:100%; height:100px">
                                <center><b>Fatura / İrsaliye Hazır Mı?</b></center>
                            </div>
                            <?php if ($satinalmayagonder == 'Evet') {  ?>
                                <div class="col-md-3 altbos" style="border:3px solid <?php if ($satinalmayagonder == 'Evet') {
                                                                                            echo 'green';
                                                                                        } else {
                                                                                            echo 'red';
                                                                                        } ?>;">
                                    <h3 class="numara">3. <small>Aşama</small></h3>
                                    <img src="images/satinalma.png" style="width:100%; height:100px">
                                    <center><b>Satınalmaya Gönderildi</b></center>
                                </div>
                                <div class="col-md-3 altbos" style="border:3px solid <?php if ($eksikmalzeme == 'Evet') {
                                                                                            echo 'green';
                                                                                        } else {
                                                                                            echo 'red';
                                                                                        } ?>;">
                                    <h3 class="numara">4. <small>Aşama</small></h3>
                                    <img src="images/eksikmalzeme.png" style="width:100%; height:100px">
                                    <center><b>Eksik Malzeme Bekleniyor</b></center>
                                </div>
                            <?php } ?>
                            <div class="col-md-3 altbos" style="border:3px solid <?php if ($depodabeklemede == 'Evet') {
                                                                                        echo 'green';
                                                                                    } else {
                                                                                        echo 'red';
                                                                                    } ?>;">
                                <h3 class="numara">5. <small>Aşama</small></h3>
                                <img src="images/beklemede.png" style="width:100%; height:100px">
                                <center><b>Depoda Beklemeye Alındı</b></center>
                            </div>
                            <div class="col-md-3 altbos" style="border:3px solid <?php if ($aracayuklendi == 'Evet') {
                                                                                        echo 'green';
                                                                                    } else {
                                                                                        echo 'red';
                                                                                    } ?>;">
                                <h3 class="numara">6. <small>Aşama</small></h3>
                                <img src="images/aracayuklendi.png" style="width:100%; height:100px">
                                <center><b>Araca Yüklendi</b></center>
                            </div>
                            <div class="col-md-3 altbos" style="border:3px solid <?php if ($aracsevkiyatta == 'Evet') {
                                                                                        echo 'green';
                                                                                    } else {
                                                                                        echo 'red';
                                                                                    } ?>;">
                                <h3 class="numara">7. <small>Aşama</small></h3>
                                <img src="images/sevkiyatta.png" style="width:100%; height:100px">
                                <center><b>Araç Sevkiyatta</b></center>
                            </div>
                            <div class="col-md-3 altbos" style="border:3px solid <?php if ($islemtamamlandi == 'Evet') {
                                                                                        echo 'green';
                                                                                    } else {
                                                                                        echo 'red';
                                                                                    } ?>;">
                                <h3 class="numara">8. <small>Aşama</small></h3>
                                <img src="images/teslimedildi.png" style="width:100%; height:100px">
                                <center><b>İşlem Tamamlandı</b></center>
                            </div>


                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Anladım, Kapat</button>

                    </div>

                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div>
    <?php } ?>
    <?php
    $kontrolKullaniciAdi3 = mysqli_query($db, "SELECT * FROM  ogteklif2 where  durum!='Tamamlandı'");
    while ($markalar = mysqli_fetch_array($kontrolKullaniciAdi3)) {
    ?>
        <div class="modal fade durum<?php echo $markalar["id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $markalar["teklifkodu"]; ?></b> <br> <b>Durumu: <?php echo $markalar["durum"]; ?></b> / <br> <b>Statu: <?php echo $markalar["statu"]; ?></b></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="teklifsiparisler.php" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">İşlem Durumu Nedir?</label>
                                        <select name="durum" class="form-control" id="validationCustom01" required>
                                            <option value="<?php echo $markalar["durum"]; ?>" selected><?php echo $markalar["durum"]; ?></option>
                                            <?php
                                            $durumsor = mysqli_query($db, "SELECT * FROM  siparissureci  ");
                                            while ($durumlar = mysqli_fetch_array($durumsor)) {
                                            ?>
                                                <option value="<?php echo $durumlar["surec"]; ?>"><?php echo $durumlar["surec"]; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">İşlem Statüsü Nedir?</label>
                                        <textarea class="form-control" name="statu" placeholder="İşlemin Nedenini Açıklayınız"> <?php echo $markalar["statu"]; ?> </textarea>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Ödeme Türü Nedir?</label>
                                        <select name="odemenot" class="form-control" id="validationCustom01" required>
                                            <option value="<?php echo $markalar["odemetipi"]; ?>" selected><?php echo $markalar["odemetipi"]; ?> ile Ödeyecek</option>
                                            <option value="Bilinmiyor">Bilinmiyor</option>
                                            <option value="Peşin Ödeme">Peşin Ödeme</option>
                                            <option value="Kredi Kartı Ödeme">Kredi Kartı Ödeme</option>
                                            <option value="30 Gün Vade">30 Gün Vade</option>
                                            <option value="60 Gün Vade">60 Gün Vade</option>
                                            <option value="90 Gün Vade">90 Gün Vade</option>
                                            <option value="%50 Peşinat ile Satış">%50 Peşinat ile Satış</option>
                                            <option value="Hak Edişe Göre Ödeme">Hak Edişe Göre Ödeme</option>
                                            <option value="7 Gün İçerisinde">7 Gün İçerisinde</option>
                                            <option value="15 Gün İçerisinde">15 Gün İçerisinde</option>
                                        </select>
                                    </div>
                                </div>
                                <input type="text" name="icerikid" value="<?php echo $markalar["id"]; ?>" hidden>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                            <button type="submit" name="duzenleme2" class="btn btn-success">Düzenleyin!</button>
                        </div>
                    </form>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div>
    <?php } ?>
    <?php
    // where durum!='Sipariş' and durum!='Sipariş Ödemesi Bekleniyor'
    $kontrolKullaniciAdi3 = mysqli_query($db, "SELECT * FROM  ogteklif2 where  durum!='Tamamlandı'");
    while ($markalar = mysqli_fetch_array($kontrolKullaniciAdi3)) {
    ?>
        <div class="modal fade gonder<?php echo $markalar["id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel"><b><?php echo $tekkod = $markalar["teklifkodu"]; ?></b> <br> <b>Durumu: <?php echo $durr = $markalar["durum"]; ?></b> / <br> <b>Statu: <?php echo $markalar["statu"]; ?></b><br> Müşteriye Teklifi Mail Olarak İletin.</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <form method="post" action="teklifsiparisler.php" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom01">Mail Adresi?</label>
                                        <input class="form-control email" name="eposta" placeholder="Müşteri Mail Adresi Nedir?" value="<?php
                                                                                                                                        $musteris = $markalar["musteriid"];
                                                                                                                                        if ($musteris == '786') {
                                                                                                                                        } else {
                                                                                                                                            $musteribag = mysqli_query($db, "SELECT * FROM  sirket where sirket_id='$musteris'");
                                                                                                                                            $musteribilgi = mysqli_fetch_array($musteribag);
                                                                                                                                            $perids = $musteribilgi["yetkili"];
                                                                                                                                            $musteribag3 = mysqli_query($db, "SELECT * FROM  personel where personel_id='$perids'");
                                                                                                                                            $musteribilgi3 = mysqli_fetch_array($musteribag3);
                                                                                                                                            echo $musteribilgi3["p_eposta"];
                                                                                                                                        }
                                                                                                                                        ?>" />
                                    </div>
                                </div>
                                <?php
                                $musteris = $markalar["musteriid"];
                                if ($musteris == '786') {
                                    $kimehazir =  $markalar["musteriadi"];
                                } else {
                                    $musteribag = mysqli_query($db, "SELECT * FROM  sirket where sirket_id='$musteris'");
                                    $musteribilgi = mysqli_fetch_array($musteribag);
                                    $kimehazir =  $musteribilgi["s_adi"];
                                }
                                $tekkod = $dev2["teklifkodu"];
                                $metin2 = ' </b><br>' . $tekkod . ' numaralı teklifinizin durumu  ' . $durr . '  olarak belirtilmiştir. Teklifi incelemek için aşağıdaki url adresi aracılığı ile inceleyebilirsiniz.   ';
                                $metin =  $kimehazir . ' ' . $metin2;
                                ?>
                                <textarea name="notu" class="form-control" placeholder="Eklemek İstediğiniz Not Var Mı?"> </textarea>
                                <textarea name="url" hidden><?php echo $url; ?>/offer_detail.php?te=<?php echo $markalar["id"]; ?>&sta=Teklif </textarea>
                                <textarea name="metin" hidden><?php echo $metin; ?></textarea>
                                <input type="text" name="icerikid" value="<?php echo $markalar["id"]; ?>" hidden>
                                <input type="text" name="adsoyad" value="<?php echo $kimehazir; ?>" hidden>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                            <button type="submit" name="gondereposta" class="btn btn-success">Gönderin!</button>
                        </div>
                    </form>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div>
    <?php } ?>
    <?php
    $kontrolKullaniciAdi3 = mysqli_query($db, "SELECT * FROM  ogteklif2 where durum!='Tamamlandı'");
    while ($teklif = mysqli_fetch_array($kontrolKullaniciAdi3)) {
        $musteris = $teklif["musteriid"];
        if ($musteris == '786') {
            $kimehazir = $teklif["musteriadi"];
            $cep = trim($teklif["projeadi"]);
        } else {
            $musteribag = mysqli_query($db, "SELECT * FROM  sirket where sirket_id='$musteris'");
            $musteribilgi = mysqli_fetch_array($musteribag);
            $perids = $musteribilgi["yetkili"];
            $musteribag3 = mysqli_query($db, "SELECT * FROM  personel where personel_id='$perids'");
            $musteribilgi3 = mysqli_fetch_array($musteribag3);
            $kimehazir = $musteribilgi3["p_adi"] . ' ' . $musteribilgi3["p_soyadi"];
            $cep = trim($musteribilgi3["p_cep"]);
        }
        $mesaj  = getWhatsappMessage($db, (int)$teklif["id"]);
    ?>
        <div class="modal fade whatsapp<?php echo $teklif['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><b><?php echo $teklif['teklifkodu']; ?></b> WhatsApp Mesajı</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="tumislemler.php" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($cep); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mesaj</label>
                                <textarea name="wmessage" class="form-control" rows="4" required><?php echo htmlspecialchars($mesaj); ?></textarea>
                            </div>
                            <input type="hidden" name="icerikid" value="<?php echo $teklif['id']; ?>">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Vazgeçtim, Kapat</button>
                            <button type="submit" name="gonderwhatsapp" class="btn btn-success">Gönderin!</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>
    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/waypoints/lib/jquery.waypoints.min.js"></script>
    <script src="assets/libs/jquery.counterup/jquery.counterup.min.js"></script>
    <!-- apexcharts -->
    <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="assets/js/pages/dashboard.init.js"></script>
    <!-- App js -->
    <script src="assets/js/app.js"></script>
    <!-- Responsive examples -->
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <!-- Datatable init js -->
    <script src="assets/js/pages/datatables.init.js"></script>
    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <!-- Buttons examples -->
    <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/libs/parsleyjs/parsley.min.js"></script>
    <script>
        $(document).on('click', '[data-bs-toggle="modal"]', function () {
            var target = $(this).data('bs-target');
            if (target) {
                var modalEl = document.querySelector(target);
                if (modalEl) {
                    var instance = bootstrap.Modal.getOrCreateInstance(modalEl);
                    instance.show();
                }
            }
        });
    </script>
</body>

</html>
