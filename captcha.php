<?php
// captcha.php
require_once __DIR__.'/config/db.php';
session_start();

$expTime = time() - 300;
$cleanStmt = $pdo->prepare("DELETE FROM captcha WHERE UNIX_TIMESTAMP(captcha_time) < ?");
$cleanStmt->execute([$expTime]);

function randomText($len = 6) {
    $chars = '12345qwertasdfzxcvb';
    return substr(str_shuffle($chars), 0, $len);
}

$captcha_text = randomText();

$width  = 240;
$height = 80;
$image  = imagecreatetruecolor($width, $height);

$bgColor    = imagecolorallocate($image, 255, 255, 255);
$textColor  = imagecolorallocate($image, 0, 0, 0);

imagefilledrectangle($image, 0, 0, $width-1, $height-1, $bgColor);

for ($i = 0; $i < 1000; $i++) {
    $x = rand(0, $width);
    $y = rand(0, $height);
    $c = imagecolorallocate($image, rand(150, 255), rand(150, 255), rand(150, 255));
    imagesetpixel($image, $x, $y, $c);
}

for ($i = 0; $i < 5; $i++) {
    $color = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
    imageline($image, 
        rand(0, $width), rand(0, $height),
        rand(0, $width), rand(0, $height),
        $color
    );
}

$fontPath = __DIR__ . '/fonts/FiraCode.ttf';

$fontSize = 28;

if (!file_exists($fontPath)) {
    $font = 5;
    $x = ($width - imagefontwidth($font) * strlen($captcha_text)) / 2;
    $y = ($height - imagefontheight($font)) / 2;
    
    for ($i = 0; $i < strlen($captcha_text); $i++) {
        $char = $captcha_text[$i];
        $charX = $x + ($i * imagefontwidth($font) * 1.5);
        $charY = $y + rand(-5, 5);
        
        imagestring($image, $font, $charX, $charY, $char, $textColor);
    }
} else {
    $angle = 0;
    
    $bbox = imagettfbbox($fontSize, $angle, $fontPath, $captcha_text);
    $textWidth = $bbox[2] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];
    
    $x = ($width - $textWidth) / 2;
    $y = ($height + $textHeight) / 2;
    
    for ($i = 0; $i < strlen($captcha_text); $i++) {
        $char = $captcha_text[$i];
        $charAngle = rand(-15, 15);
        
        $charBox = imagettfbbox($fontSize, $charAngle, $fontPath, $char);
        
        $charX = $x;
        $charY = $y + rand(-10, 10);
        
        imagettftext($image, $fontSize, $charAngle, $charX, $charY, $textColor, $fontPath, $char);
        
        $shadowColor = imagecolorallocate($image, 150, 150, 150);
        imagettftext($image, $fontSize, $charAngle, $charX + 2, $charY + 2, $shadowColor, $fontPath, $char);
        
        $x += ($charBox[2] - $charBox[0]) * 0.8;
    }
}

$tmpDir = __DIR__.'/tmp';
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0755, true);
}

$picName = 'captcha_' . uniqid() . '.png';
$path = $tmpDir . '/' . $picName;
imagepng($image, $path);
imagedestroy($image);

$stmt = $pdo->prepare("
    INSERT INTO captcha (ip_address, captcha_text, pic_name)
    VALUES (:ip, :text, :pic)
");
$stmt->execute([
    ':ip'   => $_SERVER['REMOTE_ADDR'],
    ':text' => strtolower($captcha_text),
    ':pic'  => $picName,
]);

$captchaId = $pdo->lastInsertId();

$_SESSION['captcha_id'] = $captchaId;
$_SESSION['captcha_ip'] = $_SERVER['REMOTE_ADDR'];

header('Content-Type: image/png');
readfile($path);

register_shutdown_function(function() use ($path) {
    if (file_exists($path)) {
        unlink($path);
    }
});
?>