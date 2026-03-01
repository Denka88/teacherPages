<?php
// inc/upload.php
function handle_photo_upload($file, $uploadDir = __DIR__ . '/../uploads') {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Файл превышает максимальный размер',
            UPLOAD_ERR_FORM_SIZE => 'Файл превышает максимальный размер формы',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен только частично',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
            UPLOAD_ERR_EXTENSION => 'Расширение PHP остановило загрузку файла'
        ];
        
        $errorMessage = $errorMessages[$file['error']] ?? 'Неизвестная ошибка загрузки';
        throw new RuntimeException('Ошибка загрузки файла: ' . $errorMessage);
    }

    $maxBytes = 2 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('Файл больше 2MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    
    if (!array_key_exists($mime, $allowed)) {
        throw new RuntimeException('Недопустимый тип файла. Только JPG/PNG.');
    }

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new RuntimeException('Не удалось создать папку для загрузки.');
        }
    }

    if (!is_writable($uploadDir)) {
        throw new RuntimeException('Папка загрузки недоступна для записи.');
    }

    $basename = bin2hex(random_bytes(8));
    $filename = sprintf('%s.%s', $basename, $allowed[$mime]);
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Не удалось переместить загруженный файл.');
    }

    return 'uploads/' . $filename;
}