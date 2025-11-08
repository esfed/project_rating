<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аналитика</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Рейтинг школ</a>
        </div>
    </nav>
    <div class="container mt-4">
        <h1>Аналитика</h1>
        <p>Страница в разработке</p>
        <a href="dashboard.php" class="btn btn-secondary">Назад</a>
    </div>
</body>
</html>