<?php
// cleanup_captcha.php
require_once __DIR__.'/config/db.php';

$expTime = time() - 3600;
$stmt = $pdo->prepare("DELETE FROM captcha WHERE UNIX_TIMESTAMP(captcha_time) < ?");
$stmt->execute([$expTime]);

$tmpDir = __DIR__.'/tmp';
if (is_dir($tmpDir)) {
    $files = glob($tmpDir . '/*.png');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 3600) {
            unlink($file);
        }
    }
}

echo "Очистка завершена\n";
?>