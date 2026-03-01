<?php
// admin/admins.php
require_once __DIR__.'/_auth.php';

checkAdminPermission('admin');

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM admins ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$admins = $stmt->fetchAll();

$totalCount = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$totalPages = ceil($totalCount / $limit);

if (isset($_GET['toggle_active']) && is_numeric($_GET['toggle_active'])) {
    $adminId = intval($_GET['toggle_active']);
    
    if ($adminId == $_SESSION['admin_id']) {
        $_SESSION['error'] = 'Нельзя деактивировать свой собственный аккаунт';
        header('Location: admins.php');
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT is_active FROM admins WHERE id = :id");
    $stmt->execute([':id' => $adminId]);
    $adminData = $stmt->fetch();
    
    if ($adminData) {
        $newStatus = $adminData['is_active'] ? 0 : 1;
        $updateStmt = $pdo->prepare("UPDATE admins SET is_active = :status WHERE id = :id");
        $updateStmt->execute([':status' => $newStatus, ':id' => $adminId]);
        
        $_SESSION['success'] = $newStatus ? 'Аккаунт активирован' : 'Аккаунт деактивирован';
    }
    
    header('Location: admins.php');
    exit;
}

if (isset($_POST['delete_admin'])) {
    $adminId = intval($_POST['admin_id']);
    
    if ($adminId == $_SESSION['admin_id']) {
        $_SESSION['error'] = 'Нельзя удалить свой собственный аккаунт';
        header('Location: admins.php');
        exit;
    }
    
    $superadminCount = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'superadmin'")->fetchColumn();
    $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = :id");
    $stmt->execute([':id' => $adminId]);
    $adminRole = $stmt->fetchColumn();
    
    if ($adminRole == 'superadmin' && $superadminCount <= 1) {
        $_SESSION['error'] = 'Нельзя удалить последнего суперадминистратора';
        header('Location: admins.php');
        exit;
    }
    
    $deleteStmt = $pdo->prepare("DELETE FROM admins WHERE id = :id");
    $deleteStmt->execute([':id' => $adminId]);
    
    $_SESSION['success'] = 'Администратор удален';
    header('Location: admins.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление администраторами - TeacherPage</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php require_once 'menu.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-welcome">Управление администраторами</h1>
            <div>
                <a href="dashboard.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Назад</a>
                <a href="admin_edit.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Добавить администратора</a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= e($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= e($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>Список администраторов</h2>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span class="badge badge-info">
                        <i class="fas fa-users"></i> <?= e($totalCount) ?> администраторов
                    </span>
                </div>
            </div>

            <?php if ($admins): ?>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логин</th>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Последний вход</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($admins as $admin): ?>
                        <tr>
                            <td><?= e($admin['id']) ?></td>
                            <td>
                                <strong><?= e($admin['username']) ?></strong>
                                <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                <br><small style="color: var(--primary);">(Вы)</small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($admin['full_name'] ?? 'Не указано') ?></td>
                            <td><?= e($admin['email'] ?? 'Не указан') ?></td>
                            <td>
                                <?php 
                                $roleBadges = [
                                    'superadmin' => 'badge-danger',
                                    'admin' => 'badge-primary',
                                    'moderator' => 'badge-info'
                                ];
                                $roleNames = [
                                    'superadmin' => 'Главный администратор',
                                    'admin' => 'Администратор',
                                    'moderator' => 'Модератор'
                                ];
                                ?>
                                <span class="badge <?= $roleBadges[$admin['role']] ?? 'badge-secondary' ?>">
                                    <?= e($roleNames[$admin['role']] ?? $admin['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($admin['is_active']): ?>
                                    <span class="badge badge-success">Активен</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Неактивен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($admin['last_login']): ?>
                                    <?= e(date('d.m.Y H:i', strtotime($admin['last_login']))) ?>
                                <?php else: ?>
                                    <span style="color: #666;">Никогда</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="admin_edit.php?id=<?= e($admin['id']) ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Редактировать
                                    </a>

                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                        <?php if ($admin['is_active']): ?>
                                            <a href="?toggle_active=<?= e($admin['id']) ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Деактивировать администратора <?= e($admin['username']) ?>?')">
                                                <i class="fas fa-user-slash"></i> Деактивировать
                                            </a>
                                        <?php else: ?>
                                            <a href="?toggle_active=<?= e($admin['id']) ?>" 
                                               class="btn btn-success btn-sm"
                                               onclick="return confirm('Активировать администратора <?= e($admin['username']) ?>?')">
                                                <i class="fas fa-user-check"></i> Активировать
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($admin['role'] != 'superadmin' || $admin['id'] != $_SESSION['admin_id']): ?>
                                            <a href="admin_delete.php?id=<?= e($admin['id']) ?>" 
                                               class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Удалить
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border); flex-wrap: wrap; gap: 1rem;">
                <div style="color: #666; font-size: 0.9rem;">
                    <i class="fas fa-file-alt"></i> Страница <?= e($page) ?> из <?= e($totalPages) ?>
                </div>
                        
                <div class="btn-group">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="btn btn-outline btn-sm">
                            <i class="fas fa-angle-double-left"></i> Первая
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-outline btn-sm">
                            <i class="fas fa-angle-left"></i> Назад
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): 
                    ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                           class="btn <?= $i == $page ? 'btn-primary btn-sm' : 'btn-outline btn-sm' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-outline btn-sm">
                            Вперед <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="btn btn-outline btn-sm">
                            Последняя <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #666;">
                <div style="font-size: 4rem; margin-bottom: 1rem; color: #6c757d;">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Нет администраторов</h3>
                <p>Добавьте первого администратора</p>
                <a href="admin_edit.php" class="btn btn-primary btn-lg" style="margin-top: 1rem;">
                    <i class="fas fa-user-plus"></i> Добавить администратора
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>