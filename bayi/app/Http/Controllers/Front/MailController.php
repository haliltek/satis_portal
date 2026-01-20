<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class MailController extends Authenticatable implements MustVerifyEmail
{
    function accessmail($mail) {
        $data=[
            'mail_address'=> $mail,
            'name'=>'Test B2B',
        ];
        Mail::send('front/noticemail',$data,function($mail) use ($data) {
            $mail->subject('Test B2B');
            $mail->from('no-reply@mailadresi.com','Test B2B');
            $mail->to($data['mail_address']);
        });
    }
}




