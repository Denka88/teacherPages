<?php
// admin/admin_edit.php
require_once __DIR__.'/_auth.php';

checkAdminPermission('superadmin');

$id = intval($_GET['id'] ?? 0);
$isEdit = ($id > 0);
$adminData = null;

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $adminData = $stmt->fetch();
    
    if (!$adminData) {
        $_SESSION['error'] = 'Администратор не найден';
        header('Location: admins.php');
        exit;
    }
}

$error = '';
$success = '';

function validateUsername($username, $isEdit = false) {
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        return 'Логин может содержать только латинские буквы (a-z, A-Z), цифры (0-9), подчеркивание (_), точку (.) и дефис (-). Длина: 3-50 символов.';
    }
    
    if (preg_match('/^\d+$/', $username)) {
        return 'Логин не может состоять только из цифр';
    }
    
    if (preg_match('/^\d/', $username)) {
        return 'Логин не может начинаться с цифры';
    }
    
    if (!$isEdit) {
        $reservedNames = ['admin', 'administrator', 'root', 'superadmin', 'system', 'support', 'info', 'test'];
        if (in_array(strtolower($username), $reservedNames)) {
            return 'Этот логин зарезервирован системой';
        }
    }
    
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'admin';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($username)) {
        $error = 'Логин обязателен для заполнения';
    } elseif (($validationResult = validateUsername($username, $isEdit)) !== true) {
        $error = $validationResult;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
        $error = 'Некорректный email';
    } elseif ($isEdit && empty($password) && !empty($password_confirm)) {
        $error = 'Для изменения пароля заполните оба поля';
    } elseif (!$isEdit && empty($password)) {
        $error = 'Пароль обязателен для нового администратора';
    } elseif (!empty($password) && $password !== $password_confirm) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6 && !empty($password)) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        try {
            $pdo->beginTransaction();
            
            $checkStmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username" . ($isEdit ? " AND id != :id" : ""));
            $checkParams = [':username' => $username];
            if ($isEdit) {
                $checkParams[':id'] = $id;
            }
            $checkStmt->execute($checkParams);
            
            if ($checkStmt->fetch()) {
                throw new Exception('Логин уже занят');
            }
            
            $data = [
                ':username' => $username,
                ':email' => empty($email) ? null : $email,
                ':full_name' => empty($full_name) ? null : $full_name,
                ':role' => $role,
                ':is_active' => $is_active
            ];
            
            if (!empty($password)) {
                $data[':password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            if ($isEdit) {
                if ($adminData['role'] == 'superadmin' && $role == 'admin') {
                    $superadminCount = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'superadmin'")->fetchColumn();
                    if ($superadminCount <= 1) {
                        throw new Exception('Нельзя понизить роль последнего главного администратора');
                    }
                }
                
                $sql = "UPDATE admins SET 
                    username = :username,
                    email = :email,
                    full_name = :full_name,
                    role = :role,
                    is_active = :is_active,
                    updated_at = NOW()";
                
                if (!empty($password)) {
                    $sql .= ", password_hash = :password_hash";
                }
                
                $sql .= " WHERE id = :id";
                $data[':id'] = $id;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                
                $success = 'Администратор успешно обновлен';
            } else {
                $sql = "INSERT INTO admins (username, email, full_name, role, is_active, password_hash, created_at) 
                        VALUES (:username, :email, :full_name, :role, :is_active, :password_hash, NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                
                $success = 'Администратор успешно создан';
            }
            
            $pdo->commit();
            
            if (isset($_POST['save_and_close'])) {
                header('Location: admins.php');
                exit;
            }
            
            if ($isEdit && $id == $_SESSION['admin_id']) {
                $_SESSION['admin_username'] = $username;
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Ошибка сохранения: ' . $e->getMessage();
        }
    }
}

if ($isEdit && empty($adminData)) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $adminData = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Редактирование' : 'Создание' ?> администратора - TeacherPage</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php require_once 'menu.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-welcome"><?= $isEdit ? 'Редактирование' : 'Создание' ?> администратора</h1>
            <div>
                <a href="admins.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Назад к списку</a>
            </div>
        </div>

        <div class="card">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= e($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="adminForm">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <div class="form-group">
                            <label class="form-label">Логин *</label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?= e($adminData['username'] ?? '') ?>" required 
                                   placeholder="admin"
                                   pattern="[a-zA-Z0-9_.-]{3,50}"
                                   title="Только латинские буквы, цифры, ., _, -. Длина: 3-50 символов">
                            <small class="form-text">
                                <i class="fas fa-info-circle"></i> Допустимые символы: a-z, A-Z, 0-9, ., _, -<br>
                                <i class="fas fa-ruler"></i> Длина: 3-50 символов<br>
                                <i class="fas fa-ban"></i> Не может начинаться с цифры
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= e($adminData['email'] ?? '') ?>" 
                                   placeholder="admin@example.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Полное имя</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= e($adminData['full_name'] ?? '') ?>" 
                                   placeholder="Иванов Иван Иванович">
                        </div>
                    </div>

                    <div>
                        <div class="form-group">
                            <label class="form-label">Роль *</label>
                            <select name="role" class="form-control" required>
                                <option value="admin" <?= ($adminData['role'] ?? 'admin') == 'admin' ? 'selected' : '' ?>>Администратор</option>
                                <option value="superadmin" <?= ($adminData['role'] ?? '') == 'superadmin' ? 'selected' : '' ?>>Главный администратор</option>
                            </select>
                            <small class="form-text">
                                <strong>Администратор:</strong> полный доступ ко всем разделам сайта<br>
                                <strong>Главный администратор:</strong> полный доступ + управление администраторами
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Пароль <?= $isEdit ? '(оставьте пустым чтобы не менять)' : '*' ?></label>
                            <input type="password" name="password" class="form-control" 
                                   <?= !$isEdit ? 'required' : '' ?>
                                   placeholder="Минимум 6 символов"
                                   minlength="6">
                            <small class="form-text">Минимум 6 символов</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Подтверждение пароля <?= $isEdit ? '(оставьте пустым чтобы не менять)' : '*' ?></label>
                            <input type="password" name="password_confirm" class="form-control" 
                                   <?= !$isEdit ? 'required' : '' ?>
                                   placeholder="Повторите пароль"
                                   minlength="6">
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="is_active" value="1" 
                                    <?= ($adminData['is_active'] ?? 1) ? 'checked' : '' ?>>
                                Активный аккаунт
                            </label>
                            <small class="form-text">Неактивные аккаунты не могут войти в систему</small>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="save" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> <?= $isEdit ? 'Сохранить изменения' : 'Создать администратора' ?>
                    </button>
                    
                    <button type="submit" name="save_and_close" class="btn btn-save-close btn-lg">
                        <i class="fas fa-save"></i> <i class="fas fa-door-closed"></i> Сохранить и закрыть
                    </button>
                    
                    <a href="admins.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Отмена
                    </a>
                    
                    <?php if ($isEdit && $adminData['last_login']): ?>
                    <div style="margin-left: auto; color: #666; font-size: 0.9rem;">
                        <i class="fas fa-clock"></i> Последний вход: 
                        <?= e(date('d.m.Y H:i', strtotime($adminData['last_login']))) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('adminForm');
        const usernameInput = form.querySelector('input[name="username"]');
        const isEdit = <?= $isEdit ? 'true' : 'false'; ?>;
        
        form.addEventListener('submit', function(e) {
            const username = usernameInput.value.trim();
            
            const cyrillicRegex = /[а-яА-ЯёЁ]/;
            const allowedRegex = /^[a-zA-Z0-9_.-]{3,50}$/;
            
            if (cyrillicRegex.test(username)) {
                e.preventDefault();
                showNotification('Логин не должен содержать кириллицу', 'error');
                usernameInput.focus();
                return;
            }
            
            if (!allowedRegex.test(username)) {
                e.preventDefault();
                showNotification('Логин содержит запрещенные символы или не соответствует длине', 'error');
                usernameInput.focus();
                return;
            }
            
            if (/^\d/.test(username)) {
                e.preventDefault();
                showNotification('Логин не может начинаться с цифры', 'error');
                usernameInput.focus();
                return;
            }
            
            if (/^\d+$/.test(username)) {
                e.preventDefault();
                showNotification('Логин не может состоять только из цифр', 'error');
                usernameInput.focus();
                return;
            }
            
            if (!isEdit) {
                const reservedNames = ['admin', 'administrator', 'root', 'superadmin', 'system', 'support', 'info', 'test'];
                if (reservedNames.includes(username.toLowerCase())) {
                    e.preventDefault();
                    showNotification('Этот логин зарезервирован системой. Выберите другой логин.', 'error');
                    usernameInput.focus();
                    return;
                }
            }
        });
        
        usernameInput.addEventListener('input', function() {
            const username = this.value;
            const cyrillicRegex = /[а-яА-ЯёЁ]/;
            
            if (cyrillicRegex.test(username)) {
                this.value = username.replace(/[а-яА-ЯёЁ]/g, '');
                showNotification('Кириллица не допускается в логине', 'warning');
            }
            
            if (/\s/.test(username)) {
                this.value = username.replace(/\s/g, '');
            }
            
            if (username.length > 50) {
                this.value = username.substring(0, 50);
            }
        });

        usernameInput.addEventListener('focus', function() {
            const hint = document.createElement('div');
            hint.className = 'form-hint';
            hint.style.cssText = 'background: #e7f3ff; padding: 0.5rem; border-radius: var(--radius); margin-top: 0.5rem; border-left: 3px solid var(--primary); font-size: 0.85rem;';
            hint.innerHTML = `
                <strong>Примеры допустимых логинов:</strong><br>
                • john_doe<br>
                • admin.user<br>
                • support-2024<br>
                • user123
            `;
            
            const existingHint = this.parentElement.querySelector('.form-hint');
            if (!existingHint) {
                this.parentElement.appendChild(hint);
            }
        });
        
        usernameInput.addEventListener('blur', function() {
            const hint = this.parentElement.querySelector('.form-hint');
            if (hint) {
                hint.remove();
            }
        });
    });
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 300px;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    </script>
</body>
</html>