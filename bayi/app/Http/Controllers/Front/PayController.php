<?php

namespace App\Http\Controllers\Front;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\CheckoutForm;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Request\RetrieveCheckoutFormRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
class PayController extends Controller
{
    public function index(Request $req){


        $userid =Auth::user()->id;
        $siteadi = baslik();
        $user = DB::table('b2b_users')->where('id',$userid)->first();
        $yontem = DB::table('odeme_yontemleri')->where('id','61')->first();
        $fiyat = $req->fiyat;
        $sid = $req->sid;
        // b2b_users tablosunda name kolonu yok, username veya email kullan
        $userName = $user->username ?? $user->email ?? 'Kullanıcı';
        $name = explode(' ', $userName);
        if (count($name) < 2) {
            $name = [$userName, ''];
        }

        $options = new Options();
        $options->setApiKey($yontem->secret_id);
        $options->setSecretKey($yontem->secret_key);
        $options->setBaseUrl($yontem->extra_alan);

        $request = new CreateCheckoutFormInitializeRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId("123456789");
        $request->setPrice("1");
        $request->setPaidPrice($fiyat);
        $request->setCurrency(Currency::TL);
        $request->setBasketId($sid);
        $request->setPaymentGroup(PaymentGroup::PRODUCT);
        $request->setCallbackUrl($yontem->back_url);
        $request->setEnabledInstallments(array(2, 3, 6, 9));

        $buyer = new Buyer();
        $buyer->setId("BY789");
        $buyer->setName($name["0"]);
        $buyer->setSurname($name["1"]);
        $buyer->setGsmNumber($user->cep_telefonu);
        $buyer->setEmail($user->email);
        $buyer->setIdentityNumber("74300864791");
        $buyer->setLastLoginDate("2015-10-05 12:43:35");
        $buyer->setRegistrationDate("2013-04-21 15:12:09");
        $buyer->setRegistrationAddress($user->sirket_adres);
        $buyer->setIp("85.34.78.112");
        $buyer->setCity($user->sehir);
        $buyer->setCountry("Turkey");
        $buyer->setZipCode("34732");
        $request->setBuyer($buyer);

        $shippingAddress = new Address();
        $shippingAddress->setContactName("Jane Doe");
        $shippingAddress->setCity("Istanbul");
        $shippingAddress->setCountry("Turkey");
        $shippingAddress->setAddress("Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
        $shippingAddress->setZipCode("34742");
        $request->setShippingAddress($shippingAddress);

        $billingAddress = new Address();
        $billingAddress->setContactName("Jane Doe");
        $billingAddress->setCity("Istanbul");
        $billingAddress->setCountry("Turkey");
        $billingAddress->setAddress("Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1");
        $billingAddress->setZipCode("34742");
        $request->setBillingAddress($billingAddress);

        $basketItems = array();
        $firstBasketItem = new BasketItem();
        $firstBasketItem->setId("BI0101");
        $firstBasketItem->setName("Binocular");
        $firstBasketItem->setCategory1("Collectibles");
        $firstBasketItem->setCategory2("Accessories");
        $firstBasketItem->setItemType(BasketItemType::PHYSICAL);
        $firstBasketItem->setPrice("0.3");
        $basketItems[0] = $firstBasketItem;

        $secondBasketItem = new BasketItem();
        $secondBasketItem->setId("BI102");
        $secondBasketItem->setName("Game code");
        $secondBasketItem->setCategory1("Game");
        $secondBasketItem->setCategory2("Online Game Items");
        $secondBasketItem->setItemType(BasketItemType::VIRTUAL);
        $secondBasketItem->setPrice("0.5");
        $basketItems[1] = $secondBasketItem;

        $thirdBasketItem = new BasketItem();
        $thirdBasketItem->setId("BI103");
        $thirdBasketItem->setName("Usb");
        $thirdBasketItem->setCategory1("Electronics");
        $thirdBasketItem->setCategory2("Usb / Cable");
        $thirdBasketItem->setItemType(BasketItemType::PHYSICAL);
        $thirdBasketItem->setPrice("0.2");
        $basketItems[2] = $thirdBasketItem;
        $request->setBasketItems($basketItems);

        $checkoutFormInitialize = CheckoutFormInitialize::create($request, $options);
        $paymentinput = $checkoutFormInitialize->getCheckoutFormContent();


        return $paymentinput;
    }
    public function payresult(Request $req) {
        $options = new Options();
        $options->setApiKey('');
        $options->setSecretKey('');
        $options->setBaseUrl('https://api.iyzipay.com');

        $token = $req->token;
        $request = new RetrieveCheckoutFormRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId("123456789");
        $request->setToken($token);


        $checkoutForm = CheckoutForm::retrieve($request, $options);
        $sonuc = $checkoutForm->getPaymentStatus();
        $sonuc2 = $checkoutForm->getErrorMessage();
        $sipid = $checkoutForm->getBasketId();
        $paidprice = $checkoutForm->getPaidPrice();
        $siparis = DB::table('uye_siparisler')->where('sip_id',$sipid)->first();
        $bakiye = Auth::user()->bakiye;
        $userid = Auth::user()->id;
        $guncelbakiye = $bakiye - $paidprice;

        if($sonuc=='SUCCESS') {
                $tarih = date('Y-m-d');

                $data = (
                [
                    'uye' => $siparis->uye,
                    'yontem' => $siparis->odeme,
                    'alacak' => $paidprice,
                    'tarih' => $tarih,
                    'guncelbakiye' => $guncelbakiye
                ]
                );
                DB::table('uye_cari_extre')->insert($data);
                DB::table('b2b_users')->where('id',$userid)->update(
                [
                  'bakiye' => $guncelbakiye
                ]
                );
                DB::table('uye_siparisler')->where('sip_id',$sipid)->update(
                    [
                        'durum' => '2'
                    ]
                );
                bildirim('Yeni ödeme',$sipid,'Kredi kartı ile ödeme','');

        }



        //var_dump($checkoutForm);

        return view('front.payresult',compact('sonuc','sonuc2'));

    }
}
