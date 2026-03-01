<?php
// contact_submit.php
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/inc/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: about.php');
    exit;
}

$_SESSION['contact_errors'] = [];
$_SESSION['contact_data'] = $_POST;

if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['contact_errors'][] = 'Неверный CSRF токен. Пожалуйста, обновите страницу.';
    header('Location: about.php');
    exit;
}

if (!captcha_check($_POST['captcha'] ?? '', $_POST['captcha_id'] ?? null)) {
    $_SESSION['contact_errors'][] = 'Неверный код капчи. Пожалуйста, попробуйте снова.';
    header('Location: about.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '') {
    $_SESSION['contact_errors'][] = 'Имя обязательно для заполнения';
}

if ($email === '') {
    $_SESSION['contact_errors'][] = 'Email обязателен для заполнения';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['contact_errors'][] = 'Введите корректный email адрес';
}

if ($message === '') {
    $_SESSION['contact_errors'][] = 'Сообщение обязательно для заполнения';
}

if (!empty($_SESSION['contact_errors'])) {
    header('Location: about.php');
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO contacts (name, email, message) VALUES (:n, :e, :m)");
    $stmt->execute([':n'=>$name, ':e'=>$email, ':m'=>$message]);
    
    unset($_SESSION['contact_data']);
    unset($_SESSION['contact_errors']);
    
    $_SESSION['contact_success'] = 'Ваше сообщение успешно отправлено. Мы свяжемся с вами в ближайшее время.';
    
    header('Location: about.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['contact_errors'][] = 'Произошла ошибка при отправке сообщения. Пожалуйста, попробуйте позже.';
    header('Location: about.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сообщение отправлено - TeacherPage</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="minimal-page index-page">
    <?php require_once 'menu.php'; ?>

    <main class="main">
        <div class="container">
            <div class="card" style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;"><i class="fa-solid fa-check"></i></div>
                <h1>Сообщение отправлено!</h1>
                <p style="font-size: 1.2rem; color: #666; margin: 1rem 0 2rem 0;">
                    Спасибо за ваше сообщение. Мы свяжемся с вами в ближайшее время.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="about.php" class="btn btn-primary">Вернуться на страницу контактов</a>
                    <a href="index.php" class="btn btn-outline">На главную</a>
                </div>
            </div>
        </div>
    </main>

    <?php require_once 'site_footer.php'; ?>

    <script src="js/main.js"></script>
</body>
</html>