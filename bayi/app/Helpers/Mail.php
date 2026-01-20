<?php

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\PDF;
use Barryvdh\Snappy;

function setting_info($val) {
    $id=1;
    $ayar = DB::table('ayarlar')->find($id);
    return $ayar->$val;
}
function lastorder($value,$sid) {
    $userid = Auth::user()->id;
    $query = DB::table('uye_siparisler')
        ->leftJoin('kargolar','kargolar.id','=','uye_siparisler.kargo')
        ->leftJoin('odeme_yontemleri','odeme_yontemleri.id','=','uye_siparisler.odeme')
        ->where('uye_siparisler.sip_id',$sid)
        ->where('uye_siparisler.uye',$userid)
        ->first();
    return $query->$value;
}
function orderproduct($sid){
    $query = DB::table('uye_siparis_urunler')
        ->leftJoin('urunler','urunler.id','=','uye_siparis_urunler.urun')
        ->where('uye_siparis_urunler.sipid',$sid)
        ->get();
    return $query;
}
function newProductList(){
    $products = DB::table('yeniurun')
        ->leftJoin('urunler','urunler.id','=','yeniurun.uid')
        ->get();
    return $products;
}
function address_content($id) {
    $ad = DB::table('uye_adresler')->where('adres_id',$id)->first();
    return $ad->adres;
}
function sendmail() {
    function newdealer($mail){
        $data=[
            'mail_address'=> $mail,
            'name'=>'Test B2B',
        ];
        Mail::send('emails/newdealer',$data,function($mail) use ($data) {
            $mail->subject('Bayi kaydınız hakkında');
            $mail->from('no-reply@sender.sitenmail.com','Test B2B');
            $mail->to($data['mail_address']);
        });
    }

    function neworder(){
        $data=[
            'mail_address'=> setting_info('siparis_mail'),
            'name'=>'Test B2B',
        ];
        Mail::send('emails/neworder',$data,function($mail) use ($data) {
            $mail->subject('Yeni sipariş');
            $mail->from('no-reply@sender.testmail.com','Test B2B');
            $mail->to($data['mail_address']);
        });
    }
    function newProductMail($mail){
        $data=[
            'mail_address'=> setting_info('siparis_mail'),
            'name'=>'Test B2B',
        ];
        Mail::send('emails/yeniurun',$data,function($mail) use ($data) {
            $mail->subject('Yeni Eklenen Ürünler');
            $mail->from('no-reply@sender.testmail.com','Test B2B');
            $mail->to($mail);
        });
    }

    function mailContract() {
        $data=[
            'mail_address'=> setting_info('siparis_mail'),
            'mail_address2' =>Auth::user()->email,
            'name'=>'Test B2B',
        ];
        Mail::send('emails/contract',$data,function($mail) use ($data) {
            $mail->subject('Sözleşme onayı');
            $mail->from('no-reply@sender.testmail.com','Test B2B');
            $mail->to($data['mail_address']);
        });
        Mail::send('emails/contract',$data,function($mail) use ($data) {
            $mail->subject('Sözleşme onayı');
            $mail->from('no-reply@sender.testmail.com','Test B2B');
            $mail->to($data['mail_address2']);
        });
    }

}
function mailer() {
    function sender($mail,$message,$button,$link){
        $details = [
            'message' => $message,
            'button' => $button,
            'link' => $link,
            'logo' => setting_info('logo')
        ];

        Mail::to($mail)->send(new \App\Mail\Mailgun($details));
    }
    function pdf($sip){
        createPdf($sip);
        $details = [
            'message' => 'Siparişiniz oluşturuldu.',
            'button' => 'Siparişlerime Git',
            'link' => 'https://admiring-bassi.85-95-239-101.plesk.page//siparisler',
            'sip' => $sip,
            'logo' => setting_info('logo')
        ];
        $mail = Auth::user()->email;
        Mail::to(setting_info('siparis_mail'))->send(new \App\Mail\Mailgun($details));
        Mail::to($mail)->send(new \App\Mail\Mailgun($details));
    }

    function newProductMail2($mail) {
        $products = DB::table('yeniurun')
            ->leftJoin('urunler','urunler.id','=','yeniurun.uid')
            ->get();
        $details = [
            'products' => $products
        ];
        Mail::to($mail)->send(new \App\Mail\Yeniurun($details));
    }
}

function createPdf($sip) {
    $userid = Auth::user()->id;
    $snappy = App::make('snappy.pdf');
    $snappy->generateFromHtml(pdficerik($sip),public_path().'/pdf/'.$sip.'-'.$userid.'-'); // mesafeli-satis-sozlesmesi.pdf buraya dosyasnızı yüklemelisiniz
}

function sendSms($sip,$takip){

    $username = ""; //
    $password = urlencode(""); //
    $gsm = Auth::user()->cep_telefonu;
    $message = $sip.'numaralı siparişiniz kargoya verildi. Kargo takip numaranız : '.$takip;

    $url= "https://api.netgsm.com.tr/sms/send/get/?usercode='.$username.'&password='.$password.'&gsmno='.$gsm.'&message='.$message.'&msgheader=TestB2B";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $http_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($http_code != 200){
        echo "$http_code $http_response\n";
        return false;
    }
    $balanceInfo = $http_response;
    echo "MesajID : $balanceInfo";
    return $balanceInfo;
}


