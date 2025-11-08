<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$backup_dir = 'backup/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

$backup_file = $backup_dir . 'school_rating_' . date('Y-m-d_H-i-s') . '.sql';
$config = include 'config/database.php';

$command = "mysqldump -u {$config->username} -p{$config->password} {$config->db_name} > " . $backup_file;

system($command, $output);

if ($output === 0) {
    $_SESSION['success'] = "Резервная копия создана: " . basename($backup_file);
} else {
    $_SESSION['error'] = "Ошибка создания резервной копии";
}

header("Location: dashboard.php");
exit;
?>