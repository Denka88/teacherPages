<?php
// view.php
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/inc/functions.php';

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM teacher_pages WHERE id = :id AND published_at IS NOT NULL");
$stmt->execute([':id'=>$id]);
$teacher = $stmt->fetch();
if (!$teacher) {
    http_response_code(404);
    echo "Преподаватель не найден";
    exit;
}
$stmt2 = $pdo->prepare("SELECT type, url FROM social_links WHERE teacher_id = :id");
$stmt2->execute([':id'=>$id]);
$socials = $stmt2->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($teacher['fio']) ?> - TeacherPage</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .teacher-header {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
            align-items: start;
        }
        
        .teacher-photo {
            width: 300px;
            height: 300px;
            object-fit: cover;
            border-radius: var(--radius);
        }
        
        .teacher-info-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.5rem 1rem;
            margin: 1.5rem 0;
        }
        
        .contacts-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 1rem;
        }
        
        .social-links {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .teacher-header {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                text-align: center;
            }
            
            .teacher-photo {
                width: 250px;
                height: 250px;
                margin: 0 auto;
            }
            
            .teacher-info-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
                text-align: left;
            }
            
            .teacher-info-grid strong {
                display: block;
                margin-bottom: 0.25rem;
                color: var(--dark);
            }
            
            .contacts-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .contacts-container > div {
                text-align: center;
            }
            
            .social-links {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .teacher-photo {
                width: 200px;
                height: 200px;
            }
            
            .card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="minimal-page about-page">
    <?php require_once 'menu.php'; ?>

    <main class="main">
        <div class="container">
            <div class="card">
                <a href="index.php" class="btn btn-secondary" style="margin-bottom: 2rem;">← К списку преподавателей</a>
                
                <div class="teacher-header">
                    <?php if ($teacher['photo_path']): ?>
                    <div>
                        <img src="<?= e($teacher['photo_path']) ?>" alt="<?= e($teacher['fio']) ?>" class="teacher-photo">
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <h1><?= e($teacher['fio']) ?></h1>
                        
                        <div class="teacher-info-grid">
                            <?php if ($teacher['position']): ?>
                            <strong>Должность:</strong>
                            <div><?= e($teacher['position']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($teacher['organization']): ?>
                            <strong>Место работы:</strong>
                            <div><?= e($teacher['organization']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($teacher['work_experience_years']): ?>
                            <strong>Стаж:</strong>
                            <div><?= format_years($teacher['work_experience_years']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($teacher['birth_date']): ?>
                            <strong>Дата рождения:</strong>
                            <div><?= e(date('d.m.Y', strtotime($teacher['birth_date']))) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($teacher['phone'] || $teacher['email'] || $teacher['personal_site']): ?>
                <div style="margin: 2rem 0;">
                    <h3>Контакты</h3>
                    <div class="contacts-container">
                        <?php if ($teacher['phone']): ?>
                        <div>
                            <strong>Телефон:</strong><br>
                            <?= e($teacher['phone']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($teacher['email']): ?>
                        <div>
                            <strong>Email:</strong><br>
                            <a href="mailto:<?= e($teacher['email']) ?>"><?= e($teacher['email']) ?></a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($teacher['personal_site']): ?>
                        <div>
                            <strong>Сайт:</strong><br>
                            <a href="<?= e($teacher['personal_site']) ?>" target="_blank"><?= e($teacher['personal_site']) ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($socials): ?>
                <div style="margin: 2rem 0;">
                    <h3>Социальные сети</h3>
                    <div class="social-links">
                        <?php foreach ($socials as $s): ?>
                        <a href="<?= e($s['url']) ?>" target="_blank" class="btn btn-outline">
                            <?= e($s['type']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($teacher['about']): ?>
                <div style="margin: 2rem 0;">
                    <h3>О себе</h3>
                    <div style="background: var(--gray-light); padding: 1.5rem; border-radius: var(--radius); margin-top: 1rem;">
                        <?= nl2br(e($teacher['about'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require_once 'site_footer.php'; ?>

    <script src="js/main.js"></script>
</body>
</html>