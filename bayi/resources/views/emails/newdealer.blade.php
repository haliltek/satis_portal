<style>
    body, table, tr, td {
        font-family: Arial;
    }
    body {background-color:#eee;}
    table {background-color:#ffffff;}
    .button {color: #fff;
        background-color: #005596;
        border-color: #00477d;
        font-size: 14px;
        display: inline-block;
        margin-bottom: 0;
        font-weight: normal;
        text-align: center;
        vertical-align: middle;
        touch-action: manipulation;
        cursor: pointer;
        background-image: none;
        border: 1px solid transparent;
        white-space: nowrap;
        padding: 10px 20px;
        font-size: 14px;
        line-height: 1.42857;
        border-radius: 5px;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;}
</style>
<body>
<table width="70%" bgcolor="#ffffff" align="center">
    <tbody>

    <!-- Mavi Zemin -->
    <!-- <tr>
        <td align="center" valign="middle" height="170" bgcolor="#74b8e4"><img src="admin_mail_logo_terbisiloji_w.png" alt="Terbisiloji"/></td>
    </tr> -->

    <!-- Beyaz Zemin -->
    <tr style="background-color:#2a3042;">
        <td align="center" valign="middle" height="170"><img src="http://b2b.acarhortum.com/uploads/ayarlar/{{setting_info('logo')}}" alt="Acar Hortum"/></td>
    </tr>

    <!-- Burası Değişken -->

    <tr>
        <td align="center" valign="middle" bgcolor="#eaf4f5" height="400">
            Bayi kaydınız gerçekleştirilmiştir. E-posta ve şifre bilgilerinizle giriş açabilirsiniz.<br><br>
            <a href="http://b2b.acarhortum.com/" class="button">Giriş Yap</a>
        </td>
    </tr>
    <!-- /Burası Değişken -->

    <tr style="background-color:#2a3042;">
        <td align="center" valign="middle" height="170">
            <font size="2" color="#fff">&copy; 2020 {{setting_info('site_adi')}}</font><br><br>
            <img src="http://b2b.acarhortum.com/uploads/ayarlar/{{setting_info('logo')}}" /><br><br>
            <font size="3" color="#fff">{{setting_info('adres')}}<br>
                Tel: {{setting_info('site_tel')}} | Faks: {{setting_info('fax')}}<br>
                <a href="mailto:info@acarhortum.com">info@acarhortum.com</a><br></font>
        </td>
    </tr>
    </tbody>
</table>
</body>
