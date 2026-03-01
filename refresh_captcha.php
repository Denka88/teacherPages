<?php
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/inc/functions.php';

session_start();

$captchaData = captcha_generate_image();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'id' => $captchaData['id'],
    'image_url' => $captchaData['image_url']
]);
?>