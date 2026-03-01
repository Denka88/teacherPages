<?php
// admin/dashboard.php
require_once __DIR__.'/_auth.php';

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];
$count_params = [];

if (!empty($search)) {
    $where_conditions[] = "(fio LIKE :search OR position LIKE :search OR organization LIKE :search)";
    $params[':search'] = $count_params[':search'] = "%$search%";
}

if ($status_filter !== 'all') {
    $where_conditions[] = "status = :status";
    $params[':status'] = $count_params[':status'] = strtoupper($status_filter);
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

$sql = "SELECT * FROM submissions $where_sql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$subs = $stmt->fetchAll();

$count_sql = "SELECT COUNT(*) FROM submissions" . ($where_sql ? " $where_sql" : "");
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$totalCount = $count_stmt->fetchColumn();

$totalPages = ceil($totalCount / $limit);

$status_stats = [
    'all' => $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM submissions WHERE status = 'PENDING'")->fetchColumn(),
    'approved' => $pdo->query("SELECT COUNT(*) FROM submissions WHERE status = 'APPROVED'")->fetchColumn(),
    'rejected' => $pdo->query("SELECT COUNT(*) FROM submissions WHERE status = 'REJECTED'")->fetchColumn()
];

$totalSubmissions = $pdo->query("SELECT COUNT(*) as total FROM submissions")->fetch()['total'];
$pendingSubmissions = $pdo->query("SELECT COUNT(*) as pending FROM submissions WHERE status = 'PENDING'")->fetch()['pending'];
$totalTeachers = $pdo->query("SELECT COUNT(*) as total FROM teacher_pages")->fetch()['total'];
$totalContacts = $pdo->query("SELECT COUNT(*) as total FROM contacts")->fetch()['total'];
$unreadContacts = $pdo->query("SELECT COUNT(*) as unread FROM contacts WHERE is_read = FALSE")->fetch()['unread'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - TeacherPage</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php require_once 'menu.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-welcome">
                Здравствуйте, <?= e($admin['username']) ?>
                <?php if ($admin['role'] == 'superadmin'): ?>
                    <span class="badge badge-danger" style="margin-left: 1rem; font-size: 0.8rem;">
                        <i class="fas fa-crown"></i> Главный администратор
                    </span>
                <?php else: ?>
                    <span class="badge badge-primary" style="margin-left: 1rem; font-size: 0.8rem;">
                        <i class="fas fa-user-shield"></i> Администратор
                    </span>
                <?php endif; ?>
            </h1>
            <div>
                <a href="logout.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check"></i> <?= e($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-xmark"></i> <?= e($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-number"><?= e($totalSubmissions) ?></div>
                <div class="stat-label">Всего заявок</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?= e($pendingSubmissions) ?></div>
                <div class="stat-label">Ожидают модерации</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-number"><?= e($totalTeachers) ?></div>
                <div class="stat-label">Опубликовано</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-envelope-open"></i>
                </div>
                <div class="stat-number"><?= e($totalContacts) ?></div>
                <div class="stat-label">Сообщений</div>
            </div>
            <?php if ($unreadContacts > 0): ?>
            <div class="stat-card" style="background: #fff3cd;">
                <div class="stat-icon">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="stat-number" style="color: #856404;"><?= e($unreadContacts) ?></div>
                <div class="stat-label" style="color: #856404;">Новых сообщений</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="admin-filters">
            <div class="filter-badges">
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'all', 'page' => 1])) ?>" 
                   class="filter-badge <?= $status_filter == 'all' ? 'active' : '' ?>">
                    <i class="fas fa-layer-group"></i>
                    Все заявки
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'pending', 'page' => 1])) ?>" 
                   class="filter-badge <?= $status_filter == 'pending' ? 'active' : '' ?>">
                    <i class="fas fa-clock"></i>
                    Ожидают
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'approved', 'page' => 1])) ?>" 
                   class="filter-badge <?= $status_filter == 'approved' ? 'active' : '' ?>">
                    <i class="fas fa-check-circle"></i>
                    Утверждены
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'rejected', 'page' => 1])) ?>" 
                   class="filter-badge <?= $status_filter == 'rejected' ? 'active' : '' ?>">
                    <i class="fas fa-times-circle"></i>
                    Отклонены
                </a>
            </div>
            
            <div class="filter-stats">
                <div class="filter-stat all">
                    <span class="filter-stat-number"><?= e($status_stats['all']) ?></span>
                    <span class="filter-stat-label">Всего</span>
                </div>
                <div class="filter-stat pending">
                    <span class="filter-stat-number"><?= e($status_stats['pending']) ?></span>
                    <span class="filter-stat-label">Ожидают</span>
                </div>
                <div class="filter-stat approved">
                    <span class="filter-stat-number"><?= e($status_stats['approved']) ?></span>
                    <span class="filter-stat-label">Утвержд.</span>
                </div>
                <div class="filter-stat rejected">
                    <span class="filter-stat-number"><?= e($status_stats['rejected']) ?></span>
                    <span class="filter-stat-label">Отклон.</span>
                </div>
            </div>
        </div>
        
        <div class="search-box" style="max-width: 400px; margin-bottom: 1rem;">
            <form method="get" action="dashboard.php" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="status" value="<?= e($status_filter) ?>">
                <div style="position: relative; flex: 1;">
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Поиск заявок по ФИО, должности..."
                           value="<?= e($search) ?>">
                    <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Найти</button>
                <?php if (!empty($search) || $status_filter != 'all'): ?>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">Сбросить</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                <h2>
                    <?php 
                    $status_titles = [
                        'all' => 'Все заявки',
                        'pending' => 'Заявки на модерации',
                        'approved' => 'Утвержденные заявки',
                        'rejected' => 'Отклоненные заявки'
                    ];
                    echo e($status_titles[$status_filter] ?? 'Все заявки');
                    ?>
                </h2>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if (!empty($search)): ?>
                        <span class="badge badge-info">Поиск: "<?= e($search) ?>"</span>
                    <?php endif; ?>
                    <?php if ($status_filter != 'all'): ?>
                        <span class="badge badge-<?= $status_filter == 'pending' ? 'warning' : ($status_filter == 'approved' ? 'success' : 'danger') ?>">
                            <?= e(ucfirst($status_filter)) ?>
                        </span>
                    <?php endif; ?>
                    <span class="badge badge-info"><?= e(count($subs)) ?> показано, <?= e($totalCount) ?> всего</span>
                </div>
            </div>

            <?php if ($subs): ?>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Статус</th>
                            <th>Дата подачи</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($subs as $s): ?>
                        <?php 
                        $existingTeacher = $pdo->prepare("SELECT id FROM teacher_pages WHERE fio = :fio AND position = :position AND organization = :organization");
                        $existingTeacher->execute([
                            ':fio' => $s['fio'],
                            ':position' => $s['position'],
                            ':organization' => $s['organization']
                        ]);
                        $teacherExists = $existingTeacher->fetch();
                        ?>
                        <tr>
                            <td><?= e($s['id']) ?></td>
                            <td>
                                <?= e($s['fio']) ?>
                                <?php if ($teacherExists && $s['status'] !== 'APPROVED'): ?>
                                <br><small style="color: #ff9800;"><i class="fa-solid fa-triangle-exclamation"></i> Преподаватель существует</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $statusClass = [
                                    'PENDING' => 'badge-warning',
                                    'APPROVED' => 'badge-success', 
                                    'REJECTED' => 'badge-danger'
                                ];
                                
                                $statusText = [
                                    'PENDING' => 'Ожидает',
                                    'APPROVED' => 'Утверждена', 
                                    'REJECTED' => 'Отклонена'
                                ];
                                ?>
                                <span class="badge <?= $statusClass[$s['status']] ?? 'badge-info' ?>">
                                    <?= e($statusText[$s['status']] ?? $s['status']) ?>
                                </span>
                            </td>
                            <td><?= e(date('d.m.Y H:i', strtotime($s['created_at']))) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="submission_view.php?id=<?= e($s['id']) ?>" class="btn btn-primary btn-sm">
                                        <?php if ($s['status'] === 'PENDING'): ?>
                                            <i class="fas fa-eye"></i> Рассмотреть
                                        <?php else: ?>
                                            <i class="fas fa-eye"></i> Просмотреть
                                        <?php endif; ?>
                                    </a>
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
                    Страница <?= e($page) ?> из <?= e($totalPages) ?> 
                    (показано <?= count($subs) ?> из <?= e($totalCount) ?>)
                </div>
                        
                <div class="btn-group">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="btn btn-outline btn-sm">
                            « Первая
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-outline btn-sm">
                            ‹ Назад
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
                            Вперед ›
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="btn btn-outline btn-sm">
                            Последняя »
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div style="text-align: center; padding: 2rem; color: #666;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">
                    <?php if (!empty($search)): ?><i class="fa-solid fa-magnifying-glass"></i><?php else: ?><i class="fa-solid fa-clipboard-list"></i><?php endif; ?>
                </div>
                <h3>
                    <?php if (!empty($search)): ?>
                        Заявки не найдены
                    <?php else: ?>
                        Нет заявок
                    <?php endif; ?>
                </h3>
                <p>
                    <?php if (!empty($search)): ?>
                        Попробуйте изменить поисковый запрос
                    <?php else: ?>
                        Заявки от пользователей появятся здесь
                    <?php endif; ?>
                </p>
                <?php if (!empty($search)): ?>
                    <a href="dashboard.php" class="btn btn-primary btn-sm" style="margin-top: 1rem;">Показать все заявки</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="admin-actions">
            <a href="teachers.php" class="btn btn-success">
                <i class="fas fa-users-cog"></i> Управление преподавателями
            </a>
            <a href="teacher_edit.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Создать преподавателя
            </a>
            <?php if ($unreadContacts > 0): ?>
                <a href="contacts.php" class="btn btn-warning">
                    <i class="fas fa-envelope"></i> Новые сообщения (<?= e($unreadContacts) ?>)
                </a>
            <?php else: ?>
                <a href="contacts.php" class="btn btn-outline">
                    <i class="fas fa-envelope-open"></i> Просмотреть сообщения
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterBadges = document.querySelectorAll('.filter-badge');
        filterBadges.forEach(badge => {
            badge.addEventListener('click', function(e) {
                if (this.classList.contains('active')) {
                    e.preventDefault();
                }
            });
        });
        
        filterBadges.forEach(badge => {
            badge.addEventListener('mousedown', function() {
                this.style.transform = 'scale(0.95)';
            });
            badge.addEventListener('mouseup', function() {
                this.style.transform = '';
            });
            badge.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
        
        if (window.location.search.includes('search=')) {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        const resetBtn = document.querySelector('a[href="dashboard.php"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                if (window.location.search === '') {
                    e.preventDefault();
                    showNotification('Фильтры уже сброшены', 'info');
                }
            });
        }
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
        }, 3000);
    }
    </script>
</body>
</html>