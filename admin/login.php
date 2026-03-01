<?php
// admin/login.php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../inc/functions.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, is_active FROM admins WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            if (!$admin['is_active']) {
                $error = 'Аккаунт деактивирован';
            } elseif (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                
                $updateStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
                $updateStmt->execute([':id' => $admin['id']]);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Неверный логин или пароль';
            }
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в панель управления - TeacherPage</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <div class="login-container">
        <div class="login-card">
            <h1 class="login-title">
                <i class="fas fa-graduation-cap"></i> TeacherPage
            </h1>
            <p style="text-align: center; color: #666; margin-bottom: 2rem;">Панель управления</p>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-user"></i> Логин</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-lock"></i> Пароль</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Войти в систему
                </button>
            </form>
            
            <div style="margin-top: 2rem; text-align: center; font-size: 0.9rem; color: #666;">
                <p>Система управления TeacherPage</p>
            </div>
        </div>
    </div>
</body>
</html>