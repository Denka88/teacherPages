<?php
// admin/_auth.php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../inc/functions.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

$adminId = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT id, username, email, full_name, role, is_active FROM admins WHERE id = :id AND is_active = TRUE");
$stmt->execute([':id'=>$adminId]);
$admin = $stmt->fetch();

if (!$admin) { 
    session_destroy();
    header('Location: login.php'); 
    exit; 
}

if (!isset($_SESSION['last_login_update']) || time() - $_SESSION['last_login_update'] > 3600) {
    $updateStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
    $updateStmt->execute([':id' => $adminId]);
    $_SESSION['last_login_update'] = time();
}

function checkAdminPermission($requiredRole = 'admin') {
    global $admin;
    
    if ($admin['role'] == 'admin' && $requiredRole == 'superadmin') {
        $_SESSION['error'] = 'У вас недостаточно прав для доступа к этому разделу';
        header('Location: dashboard.php');
        exit;
    }
    
    return true;
}

if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>