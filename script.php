<?php
/* 
require_once __DIR__.'/config/db.php';

$username = 'admin';
$password = 'adminpass'; // замените
$hash = password_hash($password, PASSWORD_DEFAULT);
$email = 'admin@example.com';
$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, email) VALUES (:u, :p, 'ADMIN', :e)");
$stmt->execute([':u'=>$username, ':p'=>$hash, ':e'=>$email]);
echo "Создан admin / пароль: $password"; */

// echo password_hash('adminpass', PASSWORD_DEFAULT);

?>