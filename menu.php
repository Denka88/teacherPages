<?php
// menu.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="header">
    <div class="container">
        <nav class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i> TeacherPage
            </a>

            <ul class="nav-links">
                <li><a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Главная</a></li>
                <li><a href="about.php" class="<?= $current_page === 'about.php' ? 'active' : '' ?>">О сайте</a></li>
                <li><a href="add.php" class="<?= $current_page === 'add.php' ? 'active' : '' ?>">Добавить страницу</a></li>
            </ul>
        </nav>
    </div>
</header>