<?php
// admin/contacts.php
require_once __DIR__.'/_auth.php';

$read_filter = $_GET['read'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];
$count_params = [];

if ($read_filter !== 'all') {
    if ($read_filter == 'unread') {
        $where_conditions[] = "is_read = FALSE";
    } elseif ($read_filter == 'read') {
        $where_conditions[] = "is_read = TRUE";
    }
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE :search OR email LIKE :search OR message LIKE :search)";
    $params[':search'] = $count_params[':search'] = "%$search%";
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

$sql = "SELECT * FROM contacts $where_sql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$contacts = $stmt->fetchAll();

$count_sql = "SELECT COUNT(*) FROM contacts" . ($where_sql ? " $where_sql" : "");
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$totalCount = $count_stmt->fetchColumn();

$totalPages = ceil($totalCount / $limit);

$read_stats = [
    'all' => $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn(),
    'unread' => $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read = FALSE")->fetchColumn(),
    'read' => $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read = TRUE")->fetchColumn()
];

$unreadCount = $read_stats['unread'];

function buildUrlWithParams($baseParams = [], $additionalParams = []) {
    $base = [];
    
    if (isset($_GET['read']) && $_GET['read'] !== '') {
        $base['read'] = $_GET['read'];
    }
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $base['search'] = $_GET['search'];
    }
    if (isset($_GET['page']) && intval($_GET['page']) > 0) {
        $base['page'] = intval($_GET['page']);
    }
    
    $params = array_merge($base, $baseParams, $additionalParams);
    
    $params = array_filter($params, function($value) {
        return $value !== '' && $value !== null;
    });
    
    if (empty($params)) {
        return 'contacts.php';
    }
    
    return 'contacts.php?' . http_build_query($params);
}

if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $contactId = intval($_GET['mark_read']);
    
    $updateStmt = $pdo->prepare("UPDATE contacts SET is_read = TRUE WHERE id = :id");
    $updateStmt->execute([':id' => $contactId]);
    $_SESSION['success'] = 'Сообщение помечено как прочитанное';
    
    $newPage = $page;
    if ($read_filter == 'unread' && count($contacts) <= 1 && $page > 1) {
        $newPage = max(1, $page - 1);
    }
    
    $redirectParams = [];
    if ($read_filter !== 'all') {
        $redirectParams['read'] = $read_filter;
    }
    if (!empty($search)) {
        $redirectParams['search'] = $search;
    }
    if ($newPage > 1) {
        $redirectParams['page'] = $newPage;
    }
    
    header('Location: contacts.php' . (empty($redirectParams) ? '' : '?' . http_build_query($redirectParams)));
    exit;
}

if (isset($_POST['delete_contact'])) {
    $contactId = intval($_POST['contact_id']);
    
    $deleteStmt = $pdo->prepare("DELETE FROM contacts WHERE id = :id");
    $deleteStmt->execute([':id' => $contactId]);
    $_SESSION['success'] = 'Сообщение удалено';
    
    $newPage = $page;
    if (count($contacts) <= 1 && $page > 1) {
        $newPage = max(1, $page - 1);
    }
    
    $redirectParams = [];
    if ($read_filter !== 'all') {
        $redirectParams['read'] = $read_filter;
    }
    if (!empty($search)) {
        $redirectParams['search'] = $search;
    }
    if ($newPage > 1) {
        $redirectParams['page'] = $newPage;
    }
    
    header('Location: contacts.php' . (empty($redirectParams) ? '' : '?' . http_build_query($redirectParams)));
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сообщения от пользователей - TeacherPage</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php require_once 'menu.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-welcome">Сообщения от пользователей</h1>
            <div>
                <a href="dashboard.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Назад</a>
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

        <div class="admin-filters">
            <div class="filter-badges">
                <a href="<?= buildUrlWithParams(['read' => 'all', 'page' => 1]) ?>" 
                   class="filter-badge <?= $read_filter == 'all' ? 'active' : '' ?>">
                    <i class="fas fa-layer-group"></i>
                    Все сообщения
                </a>
                <a href="<?= buildUrlWithParams(['read' => 'unread', 'page' => 1]) ?>" 
                   class="filter-badge <?= $read_filter == 'unread' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i>
                    Непрочитанные
                </a>
                <a href="<?= buildUrlWithParams(['read' => 'read', 'page' => 1]) ?>" 
                   class="filter-badge <?= $read_filter == 'read' ? 'active' : '' ?>">
                    <i class="fas fa-envelope-open"></i>
                    Прочитанные
                </a>
            </div>
            
            <div class="filter-stats">
                <div class="filter-stat all">
                    <span class="filter-stat-number"><?= e($read_stats['all']) ?></span>
                    <span class="filter-stat-label">Всего</span>
                </div>
                <div class="filter-stat unread">
                    <span class="filter-stat-number"><?= e($read_stats['unread']) ?></span>
                    <span class="filter-stat-label">Новых</span>
                </div>
                <div class="filter-stat read">
                    <span class="filter-stat-number"><?= e($read_stats['read']) ?></span>
                    <span class="filter-stat-label">Прочит.</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                <h2>
                    <?php 
                    $filter_titles = [
                        'all' => 'Все сообщения',
                        'unread' => 'Непрочитанные сообщения',
                        'read' => 'Прочитанные сообщения'
                    ];
                    echo e($filter_titles[$read_filter] ?? 'Все сообщения');
                    ?>
                </h2>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if (!empty($search)): ?>
                        <span class="badge badge-info">Поиск: "<?= e($search) ?>"</span>
                    <?php endif; ?>
                    <?php if ($read_filter != 'all'): ?>
                        <span class="badge <?= $read_filter == 'unread' ? 'badge-warning' : 'badge-success' ?>">
                            <?= e(ucfirst($read_filter)) ?>
                        </span>
                    <?php endif; ?>
                    <span class="badge badge-info"><?= e(count($contacts)) ?> показано, <?= e($totalCount) ?> всего</span>
                </div>
            </div>
            
            <div class="search-box" style="max-width: 400px; margin-bottom: 1rem;">
                <form method="get" action="contacts.php" style="display: flex; gap: 0.5rem;">
                    <input type="hidden" name="read" value="<?= e($read_filter) ?>">
                    <div style="position: relative; flex: 1;">
                        <input type="text" 
                               name="search" 
                               class="search-input" 
                               placeholder="Поиск сообщений по имени, email или тексту..."
                               value="<?= e($search) ?>">
                        <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Найти</button>
                    <?php if (!empty($search) || $read_filter != 'all'): ?>
                        <a href="contacts.php" class="btn btn-secondary btn-sm">Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($contacts): ?>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Сообщение</th>
                            <th>Дата</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($contacts as $contact): ?>
                        <tr style="<?= !$contact['is_read'] ? 'background: #fff3cd;' : '' ?>">
                            <td><?= e($contact['id']) ?></td>
                            <td>
                                <strong><?= e($contact['name']) ?></strong>
                            </td>
                            <td>
                                <a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a>
                            </td>
                            <td style="max-width: 300px;">
                                <div style="
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                    max-width: 300px;
                                ">
                                    <?= e(substr($contact['message'], 0, 100)) . (strlen($contact['message']) > 100 ? '...' : '') ?>
                                </div>
                            </td>
                            <td>
                                <?= e(date('d.m.Y H:i', strtotime($contact['created_at']))) ?>
                            </td>
                            <td>
                                <?php if ($contact['is_read']): ?>
                                    <span class="badge badge-success">Прочитано</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Новое</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" 
                                            class="btn btn-primary btn-sm" 
                                            onclick="showMessage(<?= htmlspecialchars(json_encode($contact), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="fas fa-eye"></i> Просмотр
                                    </button>
                                    
                                    <?php if (!$contact['is_read']): ?>
                                    <a href="<?= buildUrlWithParams([], ['mark_read' => $contact['id']]) ?>" 
                                       class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Прочитано
                                    </a>
                                    <?php endif; ?>
                                    
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="contact_id" value="<?= e($contact['id']) ?>">
                                        <?php if ($read_filter != 'all'): ?>
                                            <input type="hidden" name="read_filter" value="<?= e($read_filter) ?>">
                                        <?php endif; ?>
                                        <?php if (!empty($search)): ?>
                                            <input type="hidden" name="search" value="<?= e($search) ?>">
                                        <?php endif; ?>
                                        <?php if ($page > 1): ?>
                                            <input type="hidden" name="page" value="<?= e($page) ?>">
                                        <?php endif; ?>
                                        <button type="submit" 
                                                name="delete_contact" 
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Удалить это сообщение?')">
                                            <i class="fas fa-trash"></i> Удалить
                                        </button>
                                    </form>
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
                    (показано <?= count($contacts) ?> из <?= e($totalCount) ?>)
                </div>
                        
                <div class="btn-group">
                    <?php if ($page > 1): ?>
                        <a href="<?= buildUrlWithParams(['page' => 1]) ?>" class="btn btn-outline btn-sm">
                            <i class="fas fa-angle-double-left"></i> Первая
                        </a>
                        <a href="<?= buildUrlWithParams(['page' => $page - 1]) ?>" class="btn btn-outline btn-sm">
                            <i class="fas fa-angle-left"></i> Назад
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): 
                    ?>
                        <a href="<?= buildUrlWithParams(['page' => $i]) ?>" 
                           class="btn <?= $i == $page ? 'btn-primary btn-sm' : 'btn-outline btn-sm' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= buildUrlWithParams(['page' => $page + 1]) ?>" class="btn btn-outline btn-sm">
                            Вперед <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="<?= buildUrlWithParams(['page' => $totalPages]) ?>" class="btn btn-outline btn-sm">
                            Последняя <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div style="text-align: center; padding: 2rem; color: #666;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">
                    <?php if (!empty($search)): ?>
                        <i class="fa-solid fa-magnifying-glass"></i>
                    <?php elseif ($read_filter == 'unread'): ?>
                        <i class="fas fa-inbox"></i>
                    <?php elseif ($read_filter == 'read'): ?>
                        <i class="fas fa-envelope-open"></i>
                    <?php else: ?>
                        <i class="fas fa-inbox"></i>
                    <?php endif; ?>
                </div>
                <h3>
                    <?php if (!empty($search)): ?>
                        Сообщения не найдены
                    <?php elseif ($read_filter == 'unread'): ?>
                        Нет непрочитанных сообщений
                    <?php elseif ($read_filter == 'read'): ?>
                        Нет прочитанных сообщений
                    <?php else: ?>
                        Нет сообщений
                    <?php endif; ?>
                </h3>
                <p>
                    <?php if (!empty($search)): ?>
                        Попробуйте изменить поисковый запрос
                    <?php elseif ($read_filter == 'unread'): ?>
                        Все сообщения прочитаны
                    <?php elseif ($read_filter == 'read'): ?>
                        Нет прочитанных сообщений в архиве
                    <?php else: ?>
                        Сообщения от пользователей появятся здесь
                    <?php endif; ?>
                </p>
                <?php if (!empty($search)): ?>
                    <a href="contacts.php" class="btn btn-primary btn-sm" style="margin-top: 1rem;">Показать все сообщения</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="cropper-modal" id="messageModal">
        <div class="cropper-container" style="max-width: 600px; max-height: 90vh;">
            <div class="cropper-header">
                <h3 class="cropper-title"><i class="fas fa-envelope"></i> Сообщение от пользователя</h3>
                <button type="button" class="cropper-close" onclick="hideMessage()">&times;</button>
            </div>
                
            <div class="cropper-content" style="max-height: calc(90vh - 120px); overflow-y: auto;">
                <div id="messageContent">
                </div>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
    function showMessage(contact) {
        const modal = document.getElementById('messageModal');
        const content = document.getElementById('messageContent');
        
        if (!modal || !content) {
            console.error('Modal elements not found');
            return;
        }
        
        content.innerHTML = `
            <div style="margin-bottom: 1.5rem;">
                <strong><i class="fas fa-user"></i> Имя:</strong><br>
                <div style="background: var(--gray-light); padding: 0.75rem; border-radius: var(--radius); margin-top: 0.5rem;">
                    ${escapeHtml(contact.name)}
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <strong><i class="fas fa-envelope"></i> Email:</strong><br>
                <div style="background: var(--gray-light); padding: 0.75rem; border-radius: var(--radius); margin-top: 0.5rem;">
                    <a href="mailto:${escapeHtml(contact.email)}">${escapeHtml(contact.email)}</a>
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <strong><i class="fas fa-calendar"></i> Дата отправки:</strong><br>
                <div style="background: var(--gray-light); padding: 0.75rem; border-radius: var(--radius); margin-top: 0.5rem;">
                    ${new Date(contact.created_at).toLocaleString('ru-RU')}
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <strong><i class="fas fa-comment"></i> Сообщение:</strong><br>
                <div style="
                    background: var(--gray-light); 
                    padding: 1rem; 
                    border-radius: var(--radius); 
                    margin-top: 0.5rem;
                    max-height: 200px;
                    overflow-y: auto;
                    word-wrap: break-word;
                    line-height: 1.5;
                ">
                    ${escapeHtml(contact.message)}
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <strong><i class="fas fa-info-circle"></i> Статус:</strong><br>
                <div style="padding: 0.75rem; margin-top: 0.5rem;">
                    ${contact.is_read ? 
                        '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Прочитано</span>' : 
                        '<span class="badge badge-warning"><i class="fas fa-clock"></i> Новое сообщение</span>'
                    }
                </div>
            </div>
        `;
        
        modal.classList.add('active');
        document.body.classList.add('modal-open');
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideMessage();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                hideMessage();
            }
        });
    }
    
    function hideMessage() {
        const modal = document.getElementById('messageModal');
        if (modal) {
            modal.classList.remove('active');
        }
        document.body.classList.remove('modal-open');
    }
    
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
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
        
        const unreadRows = document.querySelectorAll('tr[style*="background: #fff3cd"]');
        unreadRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 0 0 2px #ffc107';
            });
            row.addEventListener('mouseleave', function() {
                this.style.boxShadow = 'none';
            });
        });
        
        const unreadCount = <?= $unreadCount ?>;
        const pageTitle = document.querySelector('title');
        if (unreadCount > 0 && pageTitle) {
            const originalTitle = pageTitle.textContent;
            pageTitle.textContent = `(${unreadCount}) ${originalTitle}`;
        }
        
        <?php if (!empty($search)): ?>
            setTimeout(function() {
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }, 100);
        <?php endif; ?>
        
        const resetBtn = document.querySelector('a[href="contacts.php"]');
        if (resetBtn && resetBtn.textContent.includes('Сбросить')) {
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