<?php
// submit.php
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/inc/functions.php';
require_once __DIR__.'/inc/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add.php');
    exit;
}

$_SESSION['form_errors'] = [];
$_SESSION['form_data'] = $_POST;

if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['form_errors'][] = 'Неверный CSRF токен. Пожалуйста, обновите страницу.';
    header('Location: add.php');
    exit;
}

if (!captcha_check($_POST['captcha'] ?? '', $_POST['captcha_id'] ?? null)) {
    $_SESSION['form_errors'][] = 'Неверный код капчи. Пожалуйста, попробуйте снова.';
    header('Location: add.php');
    exit;
}

$fio = trim($_POST['fio'] ?? '');
if ($fio === '') {
    $_SESSION['form_errors'][] = 'ФИО обязательно.';
}

if (!empty($_SESSION['form_errors'])) {
    header('Location: add.php');
    exit;
}

$birth_date = $_POST['birth_date'] ?: null;
$position = $_POST['position'] ?: null;
$organization = $_POST['organization'] ?: null;
$work_experience_years = is_numeric($_POST['work_experience_years']) ? intval($_POST['work_experience_years']) : null;
$phone = $_POST['phone'] ?: null;
$email = $_POST['email'] ?: null;
$personal_site = $_POST['personal_site'] ?: null;
$about = $_POST['about'] ?: null;

$links = [];
if (!empty($_POST['social_type']) && is_array($_POST['social_type'])) {
    for ($i = 0; $i < count($_POST['social_type']); $i++) {
        $type = trim($_POST['social_type'][$i] ?? '');
        $url  = trim($_POST['social_url'][$i] ?? '');

        if ($type !== "" && $url !== "") {
            $links[] = [
                "type" => $type,
                "url"  => $url
            ];
        }
    }
}

$socialJson = json_encode($links, JSON_UNESCAPED_UNICODE);

try {
    $photo_path = handle_photo_upload($_FILES['photo'] ?? null);
} catch (RuntimeException $e) {
    $_SESSION['form_errors'][] = 'Ошибка загрузки фото: ' . $e->getMessage();
    header('Location: add.php');
    exit;
}

$stmt = $pdo->prepare("INSERT INTO submissions
  (fio, birth_date, photo_path, position, organization, work_experience_years, phone, email, personal_site, about, social_links)
  VALUES (:fio, :birth_date, :photo_path, :position, :organization, :we, :phone, :email, :site, :about, :social_links)");
$stmt->execute([
    ':fio' => $fio,
    ':birth_date' => $birth_date,
    ':photo_path' => $photo_path,
    ':position' => $position,
    ':organization' => $organization,
    ':we' => $work_experience_years,
    ':phone' => $phone,
    ':email' => $email,
    ':site' => $personal_site,
    ':about' => $about,
    ':social_links' => $socialJson
]);

unset($_SESSION['form_data']);
unset($_SESSION['form_errors']);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявка отправлена - TeacherPage</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <main class="main">
        <div class="container">
            <div class="card" style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;"><i class="fa-solid fa-envelope-circle-check"></i></div>
                <h1>Заявка отправлена на модерацию!</h1>
                <p style="font-size: 1.2rem; color: #666; margin: 1rem 0;">
                    Спасибо за вашу заявку. Мы рассмотрим ее в ближайшее время и уведомим вас о результате.
                </p>
                
                <div style="background: #f0fff4; padding: 1.5rem; border-radius: var(--radius); margin: 2rem 0; border-left: 4px solid #38a169;">
                    <h3 style="color: #2d774a; margin-bottom: 1rem;">Что дальше?</h3>
                    <ul style="text-align: left; color: #2d774a;">
                        <li>Наш администратор проверит вашу заявку</li>
                        <li>После одобрения ваша страница будет опубликована</li>
                        <li>Вы получите уведомление по email</li>
                    </ul>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="index.php" class="btn btn-primary">На главную страницу</a>
                    <a href="add.php" class="btn btn-outline">Добавить еще одну страницу</a>
                </div>
            </div>
        </div>
    </main>

    <?php require_once 'site_footer.php'; ?>

    <script src="js/main.js"></script>
</body>
</html>
<?php
exit;
?>