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
    
    // Получаем ID школы из URL
    $school_id = $_GET['id'] ?? null;
    $academic_year = $_GET['academic_year'] ?? '2024-2025';
    
    if (!$school_id) {
        header('Location: index.php');
        exit;
    }
    
    // Получаем данные школы
    $school_data = $school->getSchoolWithDistrict($school_id);
    if (!$school_data) {
        header('Location: index.php');
        exit;
    }
    
    // Получаем дополнительные данные
    $rating_history = $rating->getRatingHistory($school_id, 5);
    $current_scores = $rating->getSchoolScores($school_id, $academic_year);
    $position_trend = $rating->getPositionTrend($school_id);
    $district_averages = $rating->getDistrictAverageScores($school_data['district_id'], $academic_year);
    
    // Подготавливаем данные для графиков
    $history_data = [];
    
    while ($row = $rating_history->fetch(PDO::FETCH_ASSOC)) {
        $history_data[] = [
            'year' => $row['academic_year'],
            'score' => $row['average_score'] ?? 0, // Защита от null
            'position' => $row['position'] ?? 0
        ];
    }
    
    $history_data = array_reverse($history_data); // Для правильного порядка на графике
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($school_data['name']) ?> - Рейтинг школ Нюрбинского района</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Шапка -->
    <div class="header">
        <div class="container py-4">
            <!-- Навигация назад -->
            <div class="nav-buttons mb-3">
                <div class="row g-2">
                    <div class="col-auto">
                        <a href="index.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Назад к рейтингу
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="admin/login.php" class="btn btn-dark">
                            <i class="fas fa-lock me-2"></i>Панель управления
                        </a>
                    </div>
                </div>
            </div>

            <!-- Заголовок школы -->
            <div class="school-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="main-title mb-2">
                            <?= htmlspecialchars($school_data['name']) ?>
                        </h1>
                        <p class="subtitle mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?= htmlspecialchars($school_data['address'] ?? 'Адрес не указан') ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="year-badge">
                            <div class="badge bg-primary fs-6 p-3">
                                <i class="fas fa-calendar me-2"></i><?= $academic_year ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>Ошибка загрузки данных: <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Основная информация о школе -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="rating-card">
                    <div class="card-header bg-white border-0 py-3">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            Основная информация
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-user-graduate me-2"></i>Директор:</strong>
                                <p class="mb-0"><?= htmlspecialchars($school_data['director_name'] ?? 'Не указан') ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-phone me-2"></i>Телефон:</strong>
                                <p class="mb-0"><?= htmlspecialchars($school_data['phone'] ?? 'Не указан') ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-envelope me-2"></i>Email:</strong>
                                <p class="mb-0"><?= htmlspecialchars($school_data['email'] ?? 'Не указан') ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-calendar-alt me-2"></i>Год основания:</strong>
                                <p class="mb-0"><?= htmlspecialchars($school_data['established_year'] ?? 'Не указан') ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-users me-2"></i>Количество учащихся:</strong>
                                <p class="mb-0"><?= htmlspecialchars($school_data['student_count'] ?? 'Не указано') ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-globe me-2"></i>Веб-сайт:</strong>
                                <p class="mb-0">
                                    <?php if (!empty($school_data['website'])): ?>
                                        <a href="<?= htmlspecialchars($school_data['website']) ?>" target="_blank" class="school-link">
                                            Перейти на сайт
                                        </a>
                                    <?php else: ?>
                                        Не указан
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Боковая панель с рейтингом -->
            <div class="col-lg-4">
                <div class="rating-card text-center">
                    <h4 class="text-primary mb-3">Текущий рейтинг</h4>
                    
                    <?php if (!empty($history_data)): 
                        $current_rating = end($history_data);
                        $current_score = $current_rating['score'] ?? 0;
                        $current_position = $current_rating['position'] ?? 0;
                    ?>
                        <div class="rating-score mb-3">
                            <span class="display-4 fw-bold text-success">
                                <?= number_format($current_score, 1) ?>
                            </span>
                            <div class="text-muted">Общий балл</div>
                        </div>
                        
                        <div class="rating-position mb-3">
                            <span class="h2 fw-bold text-warning">
                                <?= $current_position ?>
                            </span>
                            <div class="text-muted">Место в рейтинге</div>
                        </div>
                        
                        <!-- Индикатор изменения позиции -->
                        <?php if (count($history_data) >= 2): 
                            $previous_rating = $history_data[count($history_data) - 2];
                            $previous_position = $previous_rating['position'] ?? 0;
                            $position_change = $previous_position - $current_position;
                        ?>
                            <div class="position-change mb-3">
                                <?php if ($position_change > 0): ?>
                                    <span class="badge bg-success fs-6">
                                        <i class="fas fa-arrow-up me-1"></i>+<?= $position_change ?>
                                    </span>
                                    <div class="text-muted small">Улучшение позиции</div>
                                <?php elseif ($position_change < 0): ?>
                                    <span class="badge bg-danger fs-6">
                                        <i class="fas fa-arrow-down me-1"></i><?= $position_change ?>
                                    </span>
                                    <div class="text-muted small">Снижение позиции</div>
                                <?php else: ?>
                                    <span class="badge bg-secondary fs-6">
                                        <i class="fas fa-minus me-1"></i>0
                                    </span>
                                    <div class="text-muted small">Позиция не изменилась</div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-muted py-4">
                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                            <p>Рейтинг школы пока не сформирован</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Детальные показатели -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="rating-card">
                    <div class="card-header bg-white border-0 py-3">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2 text-info"></i>
                            Детальные показатели
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Критерий оценки</th>
                                        <th width="120" class="text-center">Балл школы</th>
                                        <th width="120" class="text-center">Средний по району</th>
                                        <th width="100" class="text-center">Разница</th>
                                        <th width="80" class="text-center">Рейтинг</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $criteria_names = [
                                        'ОГЭ средний балл' => 'ОГЭ',
                                        'ЕГЭ средний балл' => 'ЕГЭ', 
                                        'Всероссийская олимпиада' => 'Всерос. олим.',
                                        'Региональные олимпиады' => 'Рег. олимпиады',
                                        'Научно-практические конференции' => 'НПК',
                                        'Дополнительное образование' => 'Доп. образование'
                                    ];
                                    
                                    foreach ($criteria_names as $full_name => $short_name): 
                                        $school_score = $current_scores[$full_name] ?? 0;
                                        $district_avg = $district_averages[$full_name]['average'] ?? 0;
                                        $difference = $school_score - $district_avg;
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?= $short_name ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary fs-6">
                                                    <?= number_format($school_score, 1) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">
                                                    <?= number_format($district_avg, 1) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($difference > 0): ?>
                                                    <span class="badge bg-success">
                                                        +<?= number_format($difference, 1) ?>
                                                    </span>
                                                <?php elseif ($difference < 0): ?>
                                                    <span class="badge bg-danger">
                                                        <?= number_format($difference, 1) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <?= number_format($difference, 1) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($school_score > $district_avg): ?>
                                                    <i class="fas fa-arrow-up text-success"></i>
                                                <?php elseif ($school_score < $district_avg): ?>
                                                    <i class="fas fa-arrow-down text-danger"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-equals text-secondary"></i>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Графики и аналитика -->
        <?php if (!empty($history_data)): ?>
        <div class="row">
            <!-- График динамики рейтинга -->
            <div class="col-lg-6 mb-4">
                <div class="rating-card">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2 text-success"></i>
                            Динамика общего балла
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="scoreTrendChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- График позиции в рейтинге -->
            <div class="col-lg-6 mb-4">
                <div class="rating-card">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-trophy me-2 text-warning"></i>
                            Динамика позиции в рейтинге
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="positionTrendChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Сравнение с средними показателями -->
        <div class="row">
            <div class="col-12">
                <div class="rating-card">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-balance-scale me-2 text-info"></i>
                            Сравнение со средними показателями района
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="comparisonChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($history_data)): ?>
    <script>
        // Данные для графиков
        const historyData = <?= json_encode($history_data) ?>;
        const currentScores = <?= json_encode($current_scores) ?>;
        const districtAverages = <?= json_encode($district_averages) ?>;

        // График динамики баллов
        if (historyData.length > 0) {
            const scoreCtx = document.getElementById('scoreTrendChart').getContext('2d');
            new Chart(scoreCtx, {
                type: 'line',
                data: {
                    labels: historyData.map(item => item.year),
                    datasets: [{
                        label: 'Общий балл',
                        data: historyData.map(item => item.score || 0),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Динамика общего балла за 5 лет'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: Math.min(...historyData.map(item => item.score || 0)) - 5,
                            max: Math.max(...historyData.map(item => item.score || 0)) + 5
                        }
                    }
                }
            });

            // График позиций
            const positionCtx = document.getElementById('positionTrendChart').getContext('2d');
            new Chart(positionCtx, {
                type: 'line',
                data: {
                    labels: historyData.map(item => item.year),
                    datasets: [{
                        label: 'Позиция в рейтинге',
                        data: historyData.map(item => item.position || 0),
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Динамика позиции в рейтинге'
                        }
                    },
                    scales: {
                        y: {
                            reverse: true,
                            beginAtZero: false
                        }
                    }
                }
            });
        }

        // График сравнения с средними показателями
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
        const criteriaLabels = ['ОГЭ', 'ЕГЭ', 'Всерос. олим.', 'Рег. олимпиады', 'НПК', 'Доп. образование'];
        
        // Безопасное получение данных для графиков
        const schoolScores = [
            currentScores['ОГЭ средний балл'] || 0,
            currentScores['ЕГЭ средний балл'] || 0,
            ((currentScores['Всероссийская олимпиада'] || 0) + (currentScores['Региональные олимпиады'] || 0)),
            currentScores['Научно-практические конференции'] || 0,
            currentScores['Дополнительное образование'] || 0
        ];
        
        const districtScores = [
            districtAverages['ОГЭ средний балл']?.average || 0,
            districtAverages['ЕГЭ средний балл']?.average || 0,
            ((districtAverages['Всероссийская олимпиада']?.average || 0) + (districtAverages['Региональные олимпиады']?.average || 0)),
            districtAverages['Научно-практические конференции']?.average || 0,
            districtAverages['Дополнительное образование']?.average || 0
        ];

        new Chart(comparisonCtx, {
            type: 'bar',
            data: {
                labels: criteriaLabels,
                datasets: [
                    {
                        label: 'Школа',
                        data: schoolScores,
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        borderColor: '#28a745',
                        borderWidth: 1
                    },
                    {
                        label: 'Среднее по району',
                        data: districtScores,
                        backgroundColor: 'rgba(108, 117, 125, 0.8)',
                        borderColor: '#6c757d',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Сравнение показателей школы со средними по району'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>