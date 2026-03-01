<?php
// admin/submission_view.php
require_once __DIR__.'/_auth.php';
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = :id");
$stmt->execute([':id'=>$id]);
$s = $stmt->fetch();
if (!$s) { echo "Не найдено"; exit; }

$socials = json_decode($s['social_links'] ?? '[]', true);

$existingTeacher = $pdo->prepare("SELECT id FROM teacher_pages WHERE fio = :fio AND position = :position AND organization = :organization");
$existingTeacher->execute([
    ':fio' => $s['fio'],
    ':position' => $s['position'],
    ':organization' => $s['organization']
]);
$teacherExists = $existingTeacher->fetch();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр заявки #<?= e($s['id']) ?> - TeacherPage</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php require_once 'menu.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-welcome">Просмотр заявки #<?= e($s['id']) ?></h1>
            <div>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">← Назад к списку</a>
            </div>
        </div>

        <div class="card">
            <div style="display: grid; grid-template-columns: auto 1fr; gap: 2rem; align-items: start;">
                <?php if ($s['photo_path']): ?>
                <div>
                    <img src="../<?= e($s['photo_path']) ?>" alt="<?= e($s['fio']) ?>" 
                         style="width: 200px; height: 200px; object-fit: cover; border-radius: var(--radius);">
                </div>
                <?php endif; ?>
                
                <div>
                    <h2><?= e($s['fio']) ?></h2>
                    
                    <?php if ($teacherExists && $s['status'] !== 'APPROVED'): ?>
                    <div class="alert alert-warning" style="margin: 1rem 0;">
                        <i class="fa-solid fa-triangle-exclamation"></i> Преподаватель с такими данными уже существует в системе. 
                        Утверждение заявки обновит существующую запись.
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin: 1rem 0;">
                        <?php 
                        $statusText = [
                            'PENDING' => 'Ожидает модерации',
                            'APPROVED' => 'Утверждена', 
                            'REJECTED' => 'Отклонена'
                        ];
                        ?>
                        <span class="badge <?= $s['status'] == 'PENDING' ? 'badge-warning' : ($s['status'] == 'APPROVED' ? 'badge-success' : 'badge-danger') ?>">
                            Статус: <?= e($statusText[$s['status']] ?? $s['status']) ?>
                        </span>
                        <span style="color: #666; margin-left: 1rem;">
                            Подана: <?= e(date('d.m.Y H:i', strtotime($s['created_at']))) ?>
                        </span>
                        <?php if ($s['reviewed_at']): ?>
                        <span style="color: #666; margin-left: 1rem;">
                            Рассмотрена: <?= e(date('d.m.Y H:i', strtotime($s['reviewed_at']))) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;">
                <div>
                    <h3>Основная информация</h3>
                    <div style="background: var(--gray-light); padding: 1.5rem; border-radius: var(--radius);">
                        <div style="display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem;">
                            <?php if ($s['birth_date']): ?>
                            <strong>Дата рождения:</strong>
                            <div><?= e(date('d.m.Y', strtotime($s['birth_date']))) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($s['position']): ?>
                            <strong>Должность:</strong>
                            <div><?= e($s['position']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($s['organization']): ?>
                            <strong>Место работы:</strong>
                            <div><?= e($s['organization']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($s['work_experience_years']): ?>
                            <strong>Стаж:</strong>
                            <div><?= e($s['work_experience_years']) ?> лет</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <h3>Контакты</h3>
                    <div style="background: var(--gray-light); padding: 1.5rem; border-radius: var(--radius);">
                        <div style="display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem;">
                            <?php if ($s['phone']): ?>
                            <strong>Телефон:</strong>
                            <div><?= e($s['phone']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($s['email']): ?>
                            <strong>Email:</strong>
                            <div><?= e($s['email']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($s['personal_site']): ?>
                            <strong>Сайт:</strong>
                            <div><a href="<?= e($s['personal_site']) ?>" target="_blank"><?= e($s['personal_site']) ?></a></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($socials && is_array($socials)): ?>
            <div style="margin: 2rem 0;">
                <h3>Социальные сети</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem;">
                    <?php foreach ($socials as $social): ?>
                    <?php if (!empty($social['url'])): ?>
                    <a href="<?= e($social['url']) ?>" target="_blank" class="btn btn-outline btn-sm">
                        <?= e($social['type'] ?? 'Соцсеть') ?>
                    </a>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($s['about']): ?>
            <div style="margin: 2rem 0;">
                <h3>О себе</h3>
                <div style="background: var(--gray-light); padding: 1.5rem; border-radius: var(--radius); margin-top: 1rem;">
                    <?= nl2br(e($s['about'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($s['admin_comment']): ?>
            <div style="margin: 2rem 0;">
                <h3>Комментарий администратора</h3>
                <div style="background: #fff3cd; padding: 1.5rem; border-radius: var(--radius); margin-top: 1rem;">
                    <?= nl2br(e($s['admin_comment'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="submission-actions">
                <?php if ($s['status'] !== 'APPROVED'): ?>
                <form action="approve.php" method="post" class="submission-action-group">
                    <input type="hidden" name="id" value="<?= e($s['id']) ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> 
                        <span><?= $teacherExists ? 'Обновить данные преподавателя' : 'Утвердить заявку' ?></span>
                    </button>
                    <small style="color: #6c757d; text-align: center; font-size: 0.85rem;">
                        <i class="fas fa-info-circle"></i> 
                        <?= $teacherExists ? 'Обновит существующую запись преподавателя' : 'Создаст новую страницу преподавателя' ?>
                    </small>
                </form>
                <?php endif; ?>
                
                <?php if ($s['status'] !== 'REJECTED'): ?>
                <form action="reject.php" method="post" class="submission-action-group">
                    <input type="hidden" name="id" value="<?= e($s['id']) ?>">
                    <?php if ($s['status'] !== 'APPROVED'): ?>
                    <input type="text" name="comment" class="form-control" 
                           placeholder="Укажите причину отклонения (необязательно)"
                           style="margin-bottom: 0.5rem;">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times-circle"></i> 
                        <span><?= $s['status'] === 'APPROVED' ? 'Отклонить и удалить преподавателя' : 'Отклонить заявку' ?></span>
                    </button>
                    <?php if ($s['status'] === 'APPROVED'): ?>
                    <small style="color: #dc3545; text-align: center; font-size: 0.85rem;">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Удалит страницу преподавателя из системы
                    </small>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
            </div>
                    
            <div style="text-align: center; margin-top: 2rem;">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 
                    <span>Вернуться к списку заявок</span>
                </a>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>