<?php
// admin/logout.php
require_once __DIR__.'/_auth.php';

$logStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
$logStmt->execute([':id' => $_SESSION['admin_id']]);

session_destroy();
header('Location: login.php');
exit;