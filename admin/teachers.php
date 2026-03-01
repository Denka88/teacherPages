<?php
// admin/teachers.php
require_once __DIR__.'/_auth.php';

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$where = '';
$params = [];

if (!empty($search)) {
    $where = "WHERE fio LIKE :search OR position LIKE :search OR organization LIKE :search";
    $params[':search'] = "%$search%";
}

$sql = "SELECT * FROM teacher_pages $where ORDER BY published_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$countSql = "SELECT COUNT(*) FROM teacher_pages" . ($where ? " $where" : "");
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalCount = $countStmt->fetchColumn();

$totalPages = ceil($totalCount / $limit);

if (!empty($search)) {
    $filteredCount = count($rows);
} else {
    $filteredCount = $totalCount;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление преподавателями - TeacherPage</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php require_once 'menu.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-welcome">Управление преподавателями</h1>
            <div>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">← Назад</a>
                <a href="teacher_edit.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Создать преподавателя</a>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <h2>Опубликованные страницы</h2>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if (!empty($search)): ?>
                        <span class="badge badge-info">Найдено: <?= e($filteredCount) ?> из <?= e($totalCount) ?></span>
                    <?php else: ?>
                        <span class="badge badge-info"><?= e($totalCount) ?> записей</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="search-box" style="max-width: 400px; margin-bottom: 2rem;">
                <form method="get" action="teachers.php" style="display: flex; gap: 0.5rem;">
                    <div style="position: relative; flex: 1;">
                        <input type="text" 
                               name="search" 
                               class="search-input" 
                               placeholder="Поиск по ФИО, должности, месту работы..."
                               value="<?= e($search) ?>">
                        <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Найти</button>
                    <?php if (!empty($search)): ?>
                        <a href="teachers.php" class="btn btn-secondary btn-sm">Сброс</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($rows): ?>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Должность</th>
                            <th>Дата публикации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rows as $r): ?>
                        <tr>
                            <td><?= e($r['id']) ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if ($r['photo_path']): ?>
                                    <img src="../<?= e($r['photo_path']) ?>" alt="" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <?php endif; ?>
                                    <strong><?= e($r['fio']) ?></strong>
                                </div>
                            </td>
                            <td><?= e($r['position']) ?></td>
                            <td><?= e(date('d.m.Y H:i', strtotime($r['published_at']))) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="../view.php?id=<?= e($r['id']) ?>" target="_blank" class="btn btn-outline btn-sm">
                                        <i class="fas fa-eye"></i> Просмотр
                                    </a>
                                    <a href="teacher_edit.php?id=<?= e($r['id']) ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Редактировать
                                    </a>
                                    <a href="teacher_delete.php?id=<?= e($r['id']) ?>" 
                                       class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Удалить
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
                    (показано <?= count($rows) ?> из <?= e($totalCount) ?>)
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
            <div style="text-align: center; padding: 3rem; color: #666;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">
                    <?php if (!empty($search)): ?><i class="fa-solid fa-magnifying-glass"></i><?php else: ?><i class="fa-solid fa-glasses"></i><?php endif; ?>
                </div>
                <h3>
                    <?php if (!empty($search)): ?>
                        Ничего не найдено
                    <?php else: ?>
                        Нет опубликованных преподавателей
                    <?php endif; ?>
                </h3>
                <p style="margin-bottom: 2rem;">
                    <?php if (!empty($search)): ?>
                        Попробуйте изменить поисковый запрос
                    <?php else: ?>
                        Создайте первого преподавателя или утвердите заявки из модерации
                    <?php endif; ?>
                </p>
                <?php if (!empty($search)): ?>
                    <a href="teachers.php" class="btn btn-primary btn-sm">Показать всех</a>
                <?php else: ?>
                    <a href="teacher_edit.php" class="btn btn-primary btn-lg">Создать преподавателя</a>
                    <a href="dashboard.php" class="btn btn-outline btn-lg">К модерации</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>