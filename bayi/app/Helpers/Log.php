<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

function reg($val,$user,$name,$prod) {
    $log = new Logger('salter');
    $log->pushHandler(new StreamHandler('logs/'.$user.'.log', Logger::DEBUG));
    $log->info($val, ['kullanıcı' => $user, $name => $prod]);
}

function regupdate($message) {
    $log = new Logger('salter');
    $log->pushHandler(new StreamHandler('logs/update.log', Logger::DEBUG));
    $log->info($message, []);
}
function addedproduct($val,$prod) {
    $log = new Logger('salter');
    $log->pushHandler(new StreamHandler('logs/update.log', Logger::DEBUG));
    $log->info($val, ['Eklenen ürün' => $prod]);
}



