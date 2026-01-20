<?php include "fonk.php";
oturumkontrol();
$unvanim = $yoneticisorgula["unvan"];  ?>
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
</head>

<body data-layout="horizontal" data-topbar="colored">
  <!-- Begin page -->
  <div id="layout-wrapper">
    <header id="page-topbar">
      <?php include "menuler/ustmenu.php"; ?>
      <?php include "menuler/solmenu.php"; ?>
    </header>
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">
      <div class="page-content">
        <div class="container-fluid">
          <!-- start page title -->
          <div class="row">
            <div class="col-12">
              <div class="page-title-box d-flex align-items-center justify-content-between">
                <div class="page-title-right">
                  <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">Anasayfa</a></li>
                    <li class="breadcrumb-item active">E-Posta Gönderin</li>
                  </ol>
                </div>
              </div>
            </div>
          </div>
          <!-- end page title -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title mb-4">E-Posta Gönderin</h4>
                  <div class="container-fluid">
                    <div class="row">
                      <div class="col-md-4"></div> <!-- 4 sütun Sol Tarafa Boş Verdik -->
                      <div class="col-md-12"> <!-- 4 Sütun Ortaladık Başlangıcı -->
                        <form action="tummailgonderin.php" method="post"> <!-- Form Başlangıcı -->
                          <div class="form-group"> <!-- Ad Soyad Text Alanı -->
                            <small class="form-text text-muted">Lütfen müşterinizin adı ve soyadınızı girin.</small>
                            <input required="required" name="AdiSoyadi" type="text" class="form-control" placeholder="müşterinizin adı ve soyadınızı ">
                          </div> <!-- Ad Soyad Text Alanı Bitti -->
                          <br>
                          <div class="form-group"> <!-- Mail Adresi Text Alanı -->
                            <small class="form-text text-muted">Lütfen gönderilecek mail adresini Seçiniz.</small>
                            <select name="MailAdresi" class="form-control">
                              <option value="a.aydemir@gemas.com">Ahmet Aydemir (a.aydemir)</option>
                              <option value="b2b@gemas.com">Ali İhsan Dişli (b2b)</option>
                              <option value="bilgi@gemas.com">Azize Alanay (bilgi)</option>
                              <option value="info@gemas.com">Azize Alanay (info)</option>
                              <option value="bulten@gemas.com">bulten@gemas.com</option>
                              <option value="d.alyaprak@gemas.com">Demet Alyaprak (d.alyaprak)</option>
                              <option value="depo@gemas.com">Hatice Öncü Eken (depo)</option>
                              <option value="egemasr@gemas.com">Erkan AK (eygenler)</option>
                              <option value="fatura@gemas.com">Raziye Özdemir (Fatura)</option>
                              <option value="faturakontrol@gemas.com">Fatma Başyiğit (faturakontrol)</option>
                              <option value="h.erdem@gemas.com">Halil İbrahim Erdem (h.erdem)</option>
                              <option value="h.gokce@gemas.com">Hakan Gökçe (h.gokce)</option>
                              <option value="tahsilat@gemas.com">Hakan Gökçe (tahsilat)</option>
                              <option value="h.kocalay@gemas.com">Hakan Kocalay (h.kocalay)</option>
                              <option value="huseyinavsaroglu@gemas.com">Hüseyin Avşaroğlu (huseinavsaroglu)</option>
                              <option value="i.karayel@gemas.com">İzzet Sami Karayel (i.karayel)</option>

                              <option value="lojistik@gemas.com">Tuncay Durak (Lojistik)</option>
                              <option value="t.durak@gemas.com">Tuncay Durak (t.durak)</option>
                              <option value="m.bakim@gemas.com">Muammer Bakım (m.bakim)</option>
                              <option value="muhasebe@gemas.com">Fatma Toy (Muhasebe)</option>
                              <option value="nezihe@gemas.com">Nezihe Avşaroğlu (nezihe)</option>
                              <option value="o.avsaroglu@gemas.com">Osman Avşaroğlu (o.avsaroglu)</option>
                              <option value="proje@gemas.com">Demet Alyaprak (proje)</option>
                              <option value="satis@gemas.com">Osman Avşaroğlu (Satış)</option>
                              <option value="sevkiyat@gemas.com">Nabi Ata (Sevkiyat)</option>
                              <option value="siparistakip@gemas.com">Derya Ata (Sipariş Takip)</option>


                              <option value="web@gemas.com">web@gemas.com</option>
                              <option value="gemasr@gemas.com">Fatma Başyiğit (gemasr)</option>

                            </select>
                          </div> <!-- Mail Adresi Text Alanı Bitti -->
                          <br>
                          <div class="form-group"> <!-- Mesaj Konusu Text Alanı -->
                            <small class="form-text text-muted">Lütfen mesajınızın konusunu girin.</small>
                            <input required="required" name="MesajKonusu" type="text" class="form-control" placeholder="Mesajınızın Konusu">
                          </div> <!-- Mesaj Konusu Text Alanı Bitti -->
                          <br>
                          <div class="form-group"> <!-- Mesaj Text Alanı -->
                            <textarea rows="6" cols="10" name="Mesaj" required="required" class="form-control" placeholder="Mesajınızını Yazın"></textarea>
                          </div> <!-- Mesaj Text Alanı Bitti -->
                          <button type="reset" class="btn btn-success">Temizle</button> <!-- Form Temizleme Butonu -->
                          <button type="submit" class="btn btn-primary">Gönder</button> <!-- Form Gönderme Butonu -->
                        </form> <!-- Form Bitiş -->
                      </div> <!-- 6 Sütun Ortaladık Tamamlandı -->
                      <div class="col-md-4"></div> <!-- 4 sütun Sol Tarafa Boş Verdik -->
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php
            error_reporting(0); //Hataları Gizle
            //Form'dan Bütün Değerler Post Methodu ile Çekiliyor
            $AdiSoyadi = trim(strip_tags($_POST['AdiSoyadi']));
            $MailAdresi = trim(strip_tags($_POST['MailAdresi']));
            $MesajKonusu = trim(strip_tags($_POST['MesajKonusu']));
            $Mesaj = trim(strip_tags($_POST['Mesaj']));
            //Form'dan Bütün Değerler Post Methodu ile Çekiliyor Tamamlandı
            if ($AdiSoyadi and $MailAdresi and $MesajKonusu and $Mesaj) { //Form'dan bütün değerler geliyorsa mail gönderme işlemini başlatıyoruz.
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
<h1 style='color:#FFFFFF;line-height:100%;font-family:Helvetica,Arial,sans-serif;font-size:35px;font-weight:normal;margin-bottom:5px;text-align:center;'><center><img src='http://www.gemas.com/wp-content/uploads/2018/05/gemasrLogo.png'></center></h1>
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
<div style='text-align:left;font-family:Helvetica,Arial,sans-serif;font-size:15px;margin-bottom:0;margin-top:3px;color:#fff;line-height:135%;'><b>Sayın: " . $AdiSoyadi . "</b>;<br> " . $Mesaj . " </div>
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
<p style='color:black; font-size:14px;'><b>" . $yoneticisorgula["adsoyad"] . "</b> / 
<i style='color:black'>" . $yoneticisorgula["unvan"] . "</i><br>
<i style='color:black'>" . $yoneticisorgula["telefon"] . "</i><br>
<i style='color:black'>" . $yoneticisorgula["mailposta"] . "</i>
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
<div>Copyright &#169; 2022. Tüm Hakları Saklıdır.</div>
<div>Bu e-posta sizlere yanlışlıkla ulaştıysa <b>info@gemas.com</b> adresini bilgilendirmenizi rica ederiz.</div>
<div><hr>
<button style='background: green; border-radius: 2%; border:1px solid #0063A8 '><a style='text-decoration: none; color:white; padding:3%' href='https://gemas.com' target='_blank'>Gemaş Web Sitesi</a></button>
<button style='background: green; border-radius: 2%; border:1px solid  #0063A8'><a style='text-decoration: none; color:white; padding:3%' href='https://bayi.gemas.com' target='_blank'>Gemaş Bayi Portalı</a></button></div>
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
              //Php Smtp Mailler Sınıfını Sayfaya Dahil Ediyoruz
              include('phpmail/class.phpmailer.php');
              include('phpmail/class.smtp.php');
              //Php Smtp Mailler Sınıfını Sayfaya Dahil Ediyoruz Tamamlandı
              //Mail Bağlantı Ayarları 
              //Mail Hangi Hesaptan Gönderilecek ise onun bilgilerini yazın.
              $MailSmtpHost = "furina.alastyr.com";
              $MailUserName = $yoneticisorgula["mailposta"];
              $MailPassword = $yoneticisorgula["mailparola"];
              //Mail Bağlantı Ayarları Tamamlandı
              $unvanim = $yoneticisorgula["unvan"];
              //Doldurulan Form Mail Olarak Kime Gidecek?
              $MailKimeGidecek = $MailAdresi;
              //Doldurulan Form Mail Olarak Kime Gidecek Tamamlandı
              $mail = new PHPMailer();
              $mail->IsSMTP();
              $mail->SMTPAuth = true;
              $mail->Host = $MailSmtpHost; //Smtp Host
              $mail->SMTPSecure = 'ssl';  //yada tls
              $mail->Port = 465;  //SSL kullanacaksanız portu 465 olarak değiştiriniz - TLS Portu 587
              $mail->Username = $MailUserName; //Smtp Kullanıcı Adı
              $mail->Password = $MailPassword; //Smtp Parola
              $mail->SetFrom($mail->Username, $unvanim);
              $mail->AddAddress($MailKimeGidecek, $AdiSoyadi); //Mailin Gideceği Adres ve Alıcı Adı
              $mail->CharSet = 'UTF-8'; //Mail Karakter Seti
              $mail->Subject = $MesajKonusu; //Mail Konu Başlığı
              $mail->MsgHTML("$Mesaj"); //Mail Mesaj İçeriği
              if ($mail->Send()) {
                echo '<script>alert("Mail gönderildi!");</script>';
                echo '<script>document.location="tummailgonderin.php"</script>';
              } else {
                echo 'Mail gönderilirken bir hata oluştu: ' . $mail->ErrorInfo;
              }
            } //Mail gönderme işlemi tamamlandı end.if
            ?>
          </div>
        </div> <!-- container-fluid -->
      </div>
      <!-- End Page-content -->
      <?php include "menuler/footer.php"; ?>
    </div>
    <!-- end main content-->
  </div>
  <!-- END layout-wrapper -->
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
</body>

</html>