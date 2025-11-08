// Основные JavaScript функции
document.addEventListener('DOMContentLoaded', function() {
    // Автоматическое скрытие alert'ов через 5 секунд
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Подтверждение удаления
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Вы уверены, что хотите удалить эту запись?')) {
                e.preventDefault();
            }
        });
    });

    // Динамическое обновление максимального значения балла
    const criteriaSelect = document.querySelector('select[name="criteria_id"]');
    const scoreInput = document.querySelector('input[name="score"]');
    
    if (criteriaSelect && scoreInput) {
        criteriaSelect.addEventListener('change', function() {
            const maxScore = this.options[this.selectedIndex].getAttribute('data-max');
            scoreInput.max = maxScore;
            scoreInput.placeholder = `Максимум: ${maxScore}`;
        });
    }

    // Плавная прокрутка для якорных ссылок
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Автоматическое обновление данных каждые 30 секунд (опционально)
    setInterval(function() {
        const timeElements = document.querySelectorAll('.auto-update-time');
        timeElements.forEach(function(element) {
            const now = new Date();
            element.textContent = now.toLocaleTimeString();
        });
    }, 30000);
});

// Функции для работы с диаграммами
function initCharts() {
    // Инициализация всех Chart.js диаграмм на странице
    console.log('Charts initialized');
}

// Экспорт данных (заглушка для будущей реализации)
function exportToExcel() {
    alert('Функция экспорта в Excel будет реализована в будущем обновлении');
}

// Поиск и фильтрация таблиц
function filterTable(tableId, searchId) {
    const search = document.getElementById(searchId).value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
            const cellText = cells[j].textContent.toLowerCase();
            if (cellText.indexOf(search) > -1) {
                found = true;
                break;
            }
        }
        
        rows[i].style.display = found ? '' : 'none';
    }
}