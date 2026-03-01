<?php
// admin/admin_delete.php
require_once __DIR__.'/_auth.php';

checkAdminPermission('superadmin');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Неверный ID');
}

$stmt = $pdo->prepare("SELECT username, full_name, email, role FROM admins WHERE id = :id");
$stmt->execute([':id'=>$id]);
$admin = $stmt->fetch();

if (!$admin) {
    die('Администратор не найден');
}

// Проверка на удаление себя
if ($id == $_SESSION['admin_id']) {
    $_SESSION['error'] = 'Нельзя удалить свой собственный аккаунт';
    header('Location: admins.php');
    exit;
}

// Проверка на последнего суперадминистратора
if ($admin['role'] == 'superadmin') {
    $superadminCount = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'superadmin'")->fetchColumn();
    if ($superadminCount <= 1) {
        $_SESSION['error'] = 'Нельзя удалить последнего главного администратора';
        header('Location: admins.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $_POST['confirm'] ?? '';
    
    if ($confirm === 'yes') {
        $pdo->beginTransaction();
        try {
            $deleteStmt = $pdo->prepare("DELETE FROM admins WHERE id = :id");
            $deleteStmt->execute([':id' => $id]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Администратор успешно удален';
            header('Location: admins.php');
            exit;
            
        } catch (Exception $ex) {
            $pdo->rollBack();
            $error = "Ошибка удаления: " . $ex->getMessage();
        }
    } else {
        header('Location: admins.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Удаление администратора - TeacherPage</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php require_once 'menu.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-welcome">Удаление администратора</h1>
            <div>
                <a href="admins.php" class="btn btn-secondary btn-sm">← Назад к списку</a>
            </div>
        </div>

        <div class="card">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <div class="alert alert-warning">
                <h3 style="color: #856404; margin-bottom: 1rem;"><i class="fa-solid fa-triangle-exclamation"></i> Внимание!</h3>
                <p>Вы собираетесь удалить администратора:</p>
                <div style="background: white; padding: 1.5rem; border-radius: var(--radius); margin: 1rem 0; border-left: 4px solid var(--danger);">
                    <h4 style="color: var(--danger); margin-bottom: 0.5rem;"><?= e($admin['username']) ?></h4>
                    <div style="color: #666;">
                        <?php if ($admin['full_name']): ?>
                            <p style="margin: 0.25rem 0;">Полное имя: <?= e($admin['full_name']) ?></p>
                        <?php endif; ?>
                        <?php if ($admin['email']): ?>
                            <p style="margin: 0.25rem 0;">Email: <?= e($admin['email']) ?></p>
                        <?php endif; ?>
                        <p style="margin: 0.25rem 0;">
                            Роль: <span class="badge <?= $admin['role'] == 'superadmin' ? 'badge-danger' : 'badge-primary' ?>">
                                <?= e($admin['role'] == 'superadmin' ? 'Главный администратор' : 'Администратор') ?>
                            </span>
                        </p>
                        <p style="margin: 0.25rem 0;">ID: <?= e($id) ?></p>
                    </div>
                </div>
                <p><strong>Это действие невозможно отменить.</strong> Все данные администратора будут удалены безвозвратно.</p>
            </div>

            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: var(--radius); margin: 2rem 0;">
                <h4>Что будет удалено:</h4>
                <ul style="color: #666; margin: 1rem 0;">
                    <li>Учетная запись администратора</li>
                    <li>Логин и пароль</li>
                    <li>Информация о последних входах</li>
                    <li>Все права доступа и настройки</li>
                </ul>
                
                <?php if ($admin['role'] == 'superadmin'): ?>
                <div style="background: #fff3cd; padding: 1rem; border-radius: var(--radius); margin-top: 1rem; border-left: 4px solid #ffc107;">
                    <strong><i class="fas fa-exclamation-triangle"></i> Особое внимание!</strong>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                        Этот администратор имеет роль <strong>Главного администратора</strong>. 
                        Убедитесь, что в системе останется хотя бы один главный администратор.
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <form method="post">
                <div class="modal-actions" style="justify-content: center; border-top: none;">
                    <button type="submit" name="confirm" value="yes" class="btn btn-danger btn-lg">
                        <i class="fas fa-trash"></i> Да, удалить администратора
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