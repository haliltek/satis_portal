<style>
    body, table, tr, td {
        font-family: Arial;
    }
    td { padding-left:10px; }
    body {background-color:#fff;}
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
    .siparis-detay .str { float:left; width:100%; font-size:14px; padding:8px 0 8px 0; }
    .siparis-detay td { border:1px solid #f2f2f2;}
    .siparis-detay { margin-bottom:20px; }
    .str2 { float:left; width:100%; font-size:14px; padding:8px 0 8px 0; text-align: center;}
    .urun-liste .str { float:left; width:100%; font-size:13px; padding:8px 0 8px 0; }
    .urun-liste td { border:1px solid #f2f2f2;}
    .urun-liste { margin-bottom:20px; }
</style>
<body>
@if(lastorder('durum',$details['sip']) == '1')
    @php($durum = 'Ödeme Bekliyor')
    @elseif(lastorder('durum') == '2')
    @php($durum = 'Ödeme Yapıldı')
@endif
@php($sid = lastorder('sip_id',$details['sip']))
<table width="100%" bgcolor="#ffffff" align="center" style="margin-bottom:30px; margin-top:30px;">
    <tbody>

    <tr style="background-color:#2a3042;">
        <td align="center" valign="middle" height="170"><img src="http://b2b.acarhortum.com/uploads/ayarlar/{{setting_info('logo')}}" alt="Acar Hortum"/></td>
    </tr>


    </tbody>
</table>
<table width="100%" bgcolor="#ffffff" align="center" style="margin-bottom:20px;">
    <tbody>

    <tr>
        <td>
            <span class="str2">Son eklenen ürünler.</span> <br>

        </td>
    </tr>


    </tbody>
</table>
<br>

<table width="100%" bgcolor="#ffffff" align="center" class="urun-liste">
    <tbody>
    <tr style="background-color:#f1f1f1; height:50px;" >
        <td>Ürün Kodu</td>
        <td>Ürün</td>
    </tr>
    @foreach(newProductList() as $show)
    <tr>
        <td><span class="str">{{$show->urun_kodu}}</span></td>
        <td><span class="str">{{$show->urun_adi}}</span></td>
    </tr>
    @endforeach
    <tr>
        <td colspan="3"></td>
        <td><span class="str"><b>Genel Toplam</b></span></td>
        <td><span class="str">{{@para(lastorder('geneltoplam',$details['sip']))}}₺</span></td>
    </tr>
    </tbody>
</table>

<br>
<table width="100%" bgcolor="#ffffff" align="center">
    <tbody>

    <tr style="background-color:#2a3042;">
        <td align="center" valign="middle" height="170">
            <font size="2" color="#fff">&copy; 2020 {{setting_info('site_adi')}}</font><br><br>
            <img src="http://b2b.acarhortum.com/uploads/ayarlar/{{setting_info('logo')}}" /><br><br>
            <font size="3" color="#fff">{{setting_info('adres')}}<br>
                Tel: {{setting_info('site_tel')}} | Faks: {{setting_info('fax')}}<br>
                <a href="mailto:info@acarhortum.com" style="color:#fff;">info@acarhortum.com</a><br></font>
        </td>
    </tr>
    </tbody>
</table>
</body>
