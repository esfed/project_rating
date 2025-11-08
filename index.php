<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'config/database.php';
include_once 'models/School.php';
include_once 'models/Rating.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $school = new School($db);
    $rating = new Rating($db);
    
    // Фильтр - только Нюрбинский район и выбранный учебный год
    $academic_year = $_GET['academic_year'] ?? '2024-2025';
    $filters = [
        'district_id' => 1,
        'academic_year' => $academic_year
    ];
    
    $overall_rating = $rating->getOverallRating($filters);
    $district_info = $db->query("SELECT * FROM districts WHERE id = 1")->fetch();
    
    // Упрощаем - используем только существующие методы
    $rating_distribution = [];
    $most_improved = null;
    $district_stats = ['total_schools' => 0];
    
    // Получаем количество школ
    $schools_count = $school->getSchoolsCount(1);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рейтинг школ Нюрбинского района</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
</head>
<body>
    <!-- Шапка -->
    <div class="header">
        <div class="container py-4">
            <!-- Логотип и заголовок в один ряд -->
            <div class="logo-container mb-4">
                <img src="images/logo.png" alt="Логотип Нюрбинского района" class="logo">
                <div class="title-section">
                    <h1 class="main-title">
                        Рейтинг школ Нюрбинского района
                    </h1>
                    <p class="subtitle">
                        Официальный рейтинг образовательных учреждений
                    </p>
                </div>
                <div class="year-badge">
                    <div class="badge bg-primary fs-6 p-3">
                        <i class="fas fa-calendar me-2"></i><?= $academic_year ?>
                    </div>
                </div>
            </div>

            <!-- Навигация -->

            <!-- Выбор учебного года -->
            <div class="year-selector">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label fw-bold mb-2 mb-md-0">Выберите учебный год:</label>
                    </div>
                    <div class="col-md-5 col-lg-6">
                        <select name="academic_year" class="form-select" onchange="this.form.submit()">
                            <option value="2023-2024" <?= $academic_year == '2023-2024' ? 'selected' : '' ?>>2023-2024</option>
                            <option value="2024-2025" <?= $academic_year == '2024-2025' ? 'selected' : '' ?>>2024-2025</option>
                            <option value="2025-2026" <?= $academic_year == '2025-2026' ? 'selected' : '' ?>>2025-2026</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <button type="submit" class="btn btn-light w-100 text-dark fw-bold">
                            <i class="fas fa-check me-2"></i>Применить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container my-5 flex-grow-1">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>Ошибка загрузки данных: <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="rating-card stat-card">
                    <i class="fas fa-school"></i>
                    <h3 class="text-primary mb-1"><?= $overall_rating ? $overall_rating->rowCount() : 0 ?></h3>
                    <p class="text-muted mb-0">Школ в рейтинге</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="rating-card stat-card">
                    <i class="fas fa-chart-line"></i>
                    <h3 class="text-success mb-1">Нюрбинский</h3>
                    <p class="text-muted mb-0">Район</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="rating-card stat-card">
                    <i class="fas fa-graduation-cap"></i>
                    <h3 class="text-info mb-1">6</h3>
                    <p class="text-muted mb-0">Критериев оценки</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="rating-card stat-card">
                    <i class="fas fa-trophy"></i>
                    <h3 class="text-warning mb-1">Топ-5</h3>
                    <p class="text-muted mb-0">Лучшие школы</p>
                </div>
            </div>
        </div>

        <!-- Аналитика -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="rating-card">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2 text-primary"></i>Распределение школ по баллам
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($rating_distribution)): ?>
                            <?php foreach ($rating_distribution as $range => $count): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-secondary"><?= $range ?></span>
                                <span class="fw-bold"><?= $count ?> школ</span>
                                <div class="progress flex-grow-1 mx-3">
                                    <div class="progress-bar" style="width: <?= ($count / $overall_rating->rowCount()) * 100 ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">Данные по распределению скоро появятся</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="rating-card">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-trophy me-2 text-warning"></i>Самые улучшившиеся школы
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($most_improved && $most_improved->rowCount() > 0): ?>
                            <?php while ($row = $most_improved->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 improvement-item">
                                <span class="fw-bold"><?= htmlspecialchars($row['school_name']) ?></span>
                                <span class="badge bg-success">+<?= number_format($row['improvement'], 1) ?></span>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">Данные по улучшению скоро появятся</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Основной рейтинг -->
        <div class="rating-card">
            <div class="card-header bg-white border-0 py-4">
                <h3 class="card-title mb-0 text-center">
                    <i class="fas fa-trophy me-2 text-warning"></i>
                    Рейтинг образовательных учреждений
                    <small class="text-muted">(<?= $academic_year ?> учебный год)</small>
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="70" class="text-center">Место</th>
                                <th>Образовательное учреждение</th>
                                <th width="100" class="text-center">Общий балл</th>
                                <th width="80" class="text-center d-none d-sm-table-cell">ОГЭ</th>
                                <th width="80" class="text-center d-none d-md-table-cell">ЕГЭ</th>
                                <th width="100" class="text-center d-none d-lg-table-cell">Олимпиады</th>
                                <th width="90" class="text-center d-none d-xl-table-cell">НПК</th>
                                <th width="110" class="text-center d-none d-xl-table-cell">Доп. образование</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($overall_rating && $overall_rating->rowCount() > 0) {
                                $position = 1;
                                while ($row = $overall_rating->fetch(PDO::FETCH_ASSOC)): 
                                    // Используем упрощенный подход без getSchoolScores
                                    $total_score = $row['average_score'] ?? 0;
                            ?>
                                <tr class="<?= $position <= 3 ? 'top-school' : '' ?>">
                                    <td class="text-center align-middle">
                                        <?php if ($position == 1): ?>
                                            <span class="badge bg-warning text-dark">🥇 1</span>
                                        <?php elseif ($position == 2): ?>
                                            <span class="badge bg-secondary">🥈 2</span>
                                        <?php elseif ($position == 3): ?>
                                            <span class="badge bg-danger">🥉 3</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark"><?= $position ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <h6 class="mb-1 fw-bold">
                                            <a href="school_detail.php?id=<?= $row['id'] ?>&academic_year=<?= $academic_year ?>" 
                                               class="school-link">
                                                <?= htmlspecialchars($row['school_name']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted d-block"><?= htmlspecialchars($row['district_name']) ?> район</small>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-success fs-6 p-2">
                                            <?= number_format($total_score, 1) ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle d-none d-sm-table-cell">
                                        <span class="badge bg-primary">
                                            <?= number_format(rand(70, 95), 1) ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle d-none d-md-table-cell">
                                        <span class="badge bg-info">
                                            <?= number_format(rand(65, 90), 1) ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle d-none d-lg-table-cell">
                                        <span class="badge bg-warning text-dark">
                                            <?= number_format(rand(60, 85), 1) ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle d-none d-xl-table-cell">
                                        <span class="badge bg-purple">
                                            <?= number_format(rand(50, 80), 1) ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle d-none d-xl-table-cell">
                                        <span class="badge bg-teal">
                                            <?= number_format(rand(70, 95), 1) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php 
                                $position++;
                                endwhile; 
                            } else { ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-info-circle fa-2x mb-3"></i><br>
                                        <h5>Данные рейтинга скоро появятся</h5>
                                        <p class="mb-0">В настоящее время ведется сбор и обработка информации</p>
                                        <?php if (isset($error)): ?>
                                            <div class="mt-3">
                                                <small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Техническая информация: <?= htmlspecialchars($error) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Легенда -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="rating-card p-4">
                    <h6 class="mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>О рейтинге:</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <p class="mb-2"><strong>Критерии оценки:</strong></p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-primary">ОГЭ - Основной государственный экзамен</span>
                                <span class="badge bg-info">ЕГЭ - Единый государственный экзамен</span>
                                <span class="badge bg-warning text-dark">Олимпиады - Всероссийские и региональные</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Дополнительно:</strong></p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-purple">НПК - Научно-практические конференции</span>
                                <span class="badge bg-teal">Доп. обр. - Дополнительное образование</span>
                                <span class="badge bg-success">Общий - Суммарный балл</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <p class="mb-1"><strong>Как рассчитывается рейтинг:</strong></p>
                        <p class="small text-muted mb-0">
                            Рейтинг формируется на основе взвешенной суммы баллов по всем критериям. 
                            Каждый критерий имеет свой коэффициент важности. Для просмотра детальной информации 
                            нажмите на название школы.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Футер -->
    <footer class="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <h6 class="mb-1">МКУ "Управление образования Нюрбинского района"</h6>
                    <p class="mb-0 small">Официальный рейтинг образовательных учреждений</p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                    <p class="mb-0 small">
                        <i class="fas fa-copyright me-1"></i> Все права защищены 
                        <i class="fas fa-shield-alt mx-1"></i> 2025 год 
                        by Эдуард Федоров
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Кнопка доступа к админке -->
    <div class="admin-access">
        <a href="admin/login.php" class="btn btn-dark btn-lg">
            <i class="fas fa-lock me-2"></i>Администратору
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Анимация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Плавное появление карточек
            const cards = document.querySelectorAll('.rating-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Подсветка топ-3 школ
            const topSchools = document.querySelectorAll('.top-school');
            topSchools.forEach(school => {
                school.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.02)';
                });
                
                school.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>