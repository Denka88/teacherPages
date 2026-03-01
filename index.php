<?php
// index.php
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/inc/functions.php';

$search = $_GET['q'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 16;
$offset = ($page - 1) * $limit;

$sql = "SELECT id, fio, position, organization, photo_path, work_experience_years FROM teacher_pages WHERE published_at IS NOT NULL";
$countSql = "SELECT COUNT(*) FROM teacher_pages WHERE published_at IS NOT NULL";
$params = [];
$countParams = [];

if ($search !== '') {
    $sql .= " AND (fio LIKE :q OR position LIKE :q OR organization LIKE :q)";
    $countSql .= " AND (fio LIKE :q OR position LIKE :q OR organization LIKE :q)";
    $params[':q'] = $countParams[':q'] = "%$search%";
}

$sql .= " ORDER BY published_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    if ($key === ':limit' || $key === ':offset') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$teachers = $stmt->fetchAll();

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalTeachers = $countStmt->fetchColumn();

$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        AVG(work_experience_years) as avg_experience,
        COUNT(DISTINCT organization) as unique_organizations
    FROM teacher_pages 
    WHERE published_at IS NOT NULL
");
$stats = $statsStmt->fetch();

$totalPages = ceil($totalTeachers / $limit);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Преподаватели — Главная</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            border-radius: var(--radius);
            margin-bottom: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            padding: 1.5rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .search-container {
            max-width: 600px;
            margin: 0 auto 3rem;
        }
                
        .search-input {
            width: 100%;
            padding: 1rem 1.5rem 1rem 3rem;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .search-icon {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .teacher-card {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .teacher-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .teacher-card-img-container {
            height: 180px;
            overflow: hidden;
            position: relative;
        }
        
        .teacher-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .teacher-card:hover .teacher-card-img {
            transform: scale(1.05);
        }
        
        .teacher-card-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .teacher-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
            line-height: 1.3;
        }
        
        .teacher-card-text {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .teacher-card-experience {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            font-weight: 500;
            font-size: 0.9rem;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .teacher-card-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            transition: color 0.3s ease;
        }
        
        .teacher-card-link:hover {
            color: var(--primary-dark);
        }
        
        .features-section {
            margin: 4rem 0;
        }
        
        .section-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 3rem;
            color: var(--dark);
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: var(--primary);
            border-radius: 2px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .feature-description {
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        .cta-section {
            background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%);
            color: white;
            padding: 4rem 2rem;
            border-radius: var(--radius);
            text-align: center;
            margin: 4rem 0;
        }
        
        .cta-title {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .cta-description {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin: 3rem 0;
            flex-wrap: wrap;
        }
        
        .pagination-btn {
            padding: 0.75rem 1.25rem;
            border: 2px solid var(--border);
            background: white;
            color: var(--dark);
            text-decoration: none;
            border-radius: var(--radius);
            transition: all 0.3s ease;
            font-weight: 500;
            min-width: 45px;
            text-align: center;
        }
        
        .pagination-btn:hover {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .pagination-btn.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }
        
        .pagination-info {
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .cards-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .teacher-card-img-container {
                height: 150px;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            .hero-section {
                padding: 3rem 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        .search-container {
            max-width: 600px;
            margin: 0 auto 3rem;
        }
    </style>
</head>
<body class="minimal-page index-page">
    <?php require_once 'menu.php'; ?>

    <main class="main">
        <div class="container">
            <section class="hero-section">
                <div class="hero-content">
                    <h1 class="hero-title">Найдите лучших преподавателей</h1>
                    <p class="hero-subtitle">
                        Платформа для создания профессиональных портфолио преподавателей. 
                        Открывайте новые возможности для карьерного роста и сотрудничества.
                    </p>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= e($stats['total'] ?? 0) ?></div>
                            <div class="stat-label">Преподавателей</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= e(round($stats['avg_experience'] ?? 0, 1)) ?></div>
                            <div class="stat-label">
                                <?php 
                                $avgYears = round($stats['avg_experience'] ?? 0, 1);
                                echo plural_form($avgYears, ['Год', 'Года', 'Лет']) . ' опыта';
                                ?>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= e($stats['unique_organizations'] ?? 0) ?></div>
                            <div class="stat-label">Учебных заведений</div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="search-container">
                <form method="get" action="index.php" class="search-box">
                    <input type="text" name="q" class="search-input" placeholder="Поиск преподавателей по имени, должности или месту работы..." value="<?= e($search) ?>">
                    <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="hidden" name="page" value="1">
                </form>
            </div>

            <?php if (!empty($teachers)): ?>
            <section>
                <h2 class="section-title">Наши преподаватели</h2>
                <div class="cards-grid">
                    <?php foreach ($teachers as $t): ?>
                    <div class="teacher-card">
                        <div class="teacher-card-img-container">
                            <?php if ($t['photo_path']): ?>
                                <img src="<?= e($t['photo_path']) ?>" alt="<?= e($t['fio']) ?>" class="teacher-card-img">
                            <?php else: ?>
                                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-user-graduate fa-3x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="teacher-card-body">
                            <h3 class="teacher-card-title"><?= e($t['fio']) ?></h3>
                            <p class="teacher-card-text"><?= e($t['position']) ?></p>
                            <p class="teacher-card-text"><?= e($t['organization']) ?></p>
                            
                            <?php if ($t['work_experience_years']): ?>
                            <div class="teacher-card-experience">
                                <i class="fas fa-clock"></i>
                                <span><?= format_years($t['work_experience_years']) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <a href="view.php?id=<?= e($t['id']) ?>" class="teacher-card-link">
                                Подробнее
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (empty($teachers)): ?>
            <div class="card" style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem; color: #6c757d;">
                    <?php if ($search): ?>
                        <i class="fa-solid fa-magnifying-glass"></i>
                    <?php else: ?>
                        <i class="fas fa-user-graduate"></i>
                    <?php endif; ?>
                </div>
                <h3>
                    <?php if ($search): ?>
                        Преподаватели по запросу "<?= e($search) ?>" не найдены
                    <?php else: ?>
                        Преподаватели не найдены
                    <?php endif; ?>
                </h3>
                <p style="margin-bottom: 2rem; color: #666;">
                    <?php if ($search): ?>
                        Попробуйте изменить поисковый запрос
                    <?php else: ?>
                        Будьте первым, кто создаст свою страницу преподавателя!
                    <?php endif; ?>
                </p>
                <?php if ($search): ?>
                    <a href="index.php" class="btn btn-primary">Показать всех преподавателей</a>
                <?php else: ?>
                    <a href="add.php" class="btn btn-primary btn-lg">Создать свою страницу</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
            <div class="pagination-container" style='margin-top:3rem;'>
                <div class="card" style="background: #f8f9fa; border: none;">
                    <div class="pagination">
                        <div class="pagination-info">
                            Страница <?= e($page) ?> из <?= e($totalPages) ?>
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="pagination-btn">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-btn">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                   class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-btn">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="pagination-btn">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <section class="features-section">
                <h2 class="section-title">Почему выбирают нас</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Безопасность данных</h3>
                        <p class="feature-description">
                            Все ваши данные защищены и доступны только вам. Мы соблюдаем политику конфиденциальности.
                        </p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h3 class="feature-title">Быстрая публикация</h3>
                        <p class="feature-description">
                            Ваша страница будет опубликована в течение 24 часов после проверки модератором.
                        </p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Адаптивный дизайн</h3>
                        <p class="feature-description">
                            Страницы отлично выглядят на любых устройствах: компьютерах, планшетах и смартфонах.
                        </p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Профессиональный рост</h3>
                        <p class="feature-description">
                            Продемонстрируйте свои достижения и привлеките внимание потенциальных работодателей.
                        </p>
                    </div>
                </div>
            </section>

            <section class="cta-section">
                <h2 class="cta-title">Готовы создать свое профессиональное портфолио?</h2>
                <p class="cta-description">
                    Присоединяйтесь к сообществу преподавателей и создайте свою страницу уже сегодня. 
                    Это бесплатно и займет всего несколько минут.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="add.php" class="btn btn-light" style="background: white; color: var(--primary);">
                        <i class="fas fa-plus"></i> Создать страницу
                    </a>
                    <a href="about.php" class="btn btn-outline" style="border-color: white; color: white;">
                        <i class="fas fa-info-circle"></i> Узнать больше
                    </a>
                </div>
            </section>
        </div>
    </main>

    <?php require_once 'site_footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.teacher-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });
        
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });
        
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        });
        
        setTimeout(() => {
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        }, 500);
        
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            searchInput.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        }
    });
    </script>
</body>
</html>