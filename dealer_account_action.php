<?php
include "fonk.php";
require_once __DIR__ . '/classes/DatabaseManager.php';
require_once __DIR__ . '/services/LoggerService.php';
require_once __DIR__ . '/services/MailService.php';

use Proje\DatabaseManager;

$dbConfig = [
    'host' => env('DB_HOST'),
    'port' => env('DB_PORT'),
    'user' => env('DB_USER'),
    'pass' => env('DB_PASS'),
    'name' => env('DB_NAME'),
];

$logger = new LoggerService(__DIR__ . '/mail_log.txt');
$mailService = new MailService('mail.gemas.com.tr', 465, 'ssl', 'bilgi@b2b.gemas.com.tr', 'Asdas123456!', $logger);

$dbManager = new DatabaseManager($dbConfig);

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
$user = $dbManager->getB2bUserById($id);
if (!$user) {
    die('Kullanıcı bulunamadı');
}

if ($action === 'approve') {
    $dbManager->updateB2bUser($id, ['status' => 1]);
    $subject = 'Bayi Hesabınız Onaylandı';
    $body = '<p>Hesabınız onaylanmıştır. Sisteme giriş yapabilirsiniz.</p>';
} elseif ($action === 'reject') {
    $dbManager->updateB2bUser($id, ['status' => 2]);
    $subject = 'Bayi Hesabınız Reddedildi';
    $body = '<p>Hesap talebiniz reddedildi.</p>';
} else {
    die('Geçersiz işlem');
}

$mailService->sendMail($user['email'], '', $subject, $body);
header('Location: pending_dealers.php');
exit;
