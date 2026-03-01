<?php
// admin/teacher_delete.php
require_once __DIR__.'/_auth.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Неверный ID');
}

$stmt = $pdo->prepare("SELECT fio, photo_path FROM teacher_pages WHERE id = :id");
$stmt->execute([':id'=>$id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    die('Преподаватель не найден');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $_POST['confirm'] ?? '';
    
    if ($confirm === 'yes') {
        $pdo->beginTransaction();
        try {
            $stmt1 = $pdo->prepare("DELETE FROM social_links WHERE teacher_id = :id");
            $stmt1->execute([':id' => $id]);
            
            $stmt2 = $pdo->prepare("DELETE FROM teacher_pages WHERE id = :id");
            $stmt2->execute([':id' => $id]);
            
            if ($teacher['photo_path'] && file_exists(__DIR__ . '/../' . $teacher['photo_path'])) {
                unlink(__DIR__ . '/../' . $teacher['photo_path']);
            }
            
            $pdo->commit();
            
            header('Location: teachers.php');
            exit;
            
        } catch (Exception $ex) {
            $pdo->rollBack();
            $error = "Ошибка удаления: " . $ex->getMessage();
        }
    } else {
        header('Location: teachers.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Удаление преподавателя - TeacherPage</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php require_once 'menu.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-welcome">Удаление преподавателя</h1>
            <div>
                <a href="teachers.php" class="btn btn-secondary btn-sm">← Назад к списку</a>
            </div>
        </div>

        <div class="card">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <div class="alert alert-warning">
                <h3 style="color: #856404; margin-bottom: 1rem;"><i class="fa-solid fa-triangle-exclamation"></i> Внимание!</h3>
                <p>Вы собираетесь удалить страницу преподавателя:</p>
                <div style="background: white; padding: 1.5rem; border-radius: var(--radius); margin: 1rem 0; border-left: 4px solid var(--danger);">
                    <h4 style="color: var(--danger); margin-bottom: 0.5rem;"><?= e($teacher['fio']) ?></h4>
                    <p style="color: #666; margin: 0;">ID: <?= e($id) ?></p>
                </div>
                <p><strong>Это действие невозможно отменить.</strong> Все данные будут удалены безвозвратно.</p>
            </div>

            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: var(--radius); margin: 2rem 0;">
                <h4>Что будет удалено:</h4>
                <ul style="color: #666; margin: 1rem 0;">
                    <li>Основная информация о преподавателе</li>
                    <li>Все социальные сети и ссылки</li>
                    <?php if ($teacher['photo_path']): ?>
                    <li>Фотография преподавателя</li>
                    <?php endif; ?>
                    <li>Все связанные данные</li>
                </ul>
            </div>

            <form method="post">
                <div class="modal-actions" style="justify-content: center; border-top: none;">
                    <button type="submit" name="confirm" value="yes" class="btn btn-danger btn-lg">
                        <i class="fas fa-trash"></i> Да, удалить преподавателя
                    </button>
                    <button type="submit" name="confirm" value="no" class="btn btn-secondary btn-lg">
                        <i class="fa-solid fa-rotate-left"></i> Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>