<?php
// admin/menu.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="admin-sidebar">
    <div class="admin-logo">
        <h1>TeacherPage</h1>
        <small>Панель управления</small>
    </div>
    
    <ul class="admin-nav">
        <li><a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i> Дашборд</a></li>
        <li><a href="teachers.php" class="<?= $current_page === 'teachers.php' ? 'active' : '' ?>"><i class="fas fa-chalkboard-teacher"></i> Преподаватели</a></li>
        <li><a href="contacts.php" class="<?= $current_page === 'contacts.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Сообщения</a></li>
        <?php if (isset($admin) && $admin['role'] == 'superadmin'): ?>
        <li><a href="admins.php" class="<?= $current_page === 'admins.php' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> Администраторы</a></li>
        <?php endif; ?>
        <li><a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Сайт</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
    </ul>
</div>