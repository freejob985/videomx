<?php
require_once '../includes/functions.php';

$language_id = $_GET['language_id'] ?? null;

if (!$language_id) {
    http_response_code(400);
    exit('معرف اللغة مطلوب');
}

try {
    $db = connectDB();
    // جلب إحصائيات اللغة
    $stmt = $db->prepare("
        SELECT 
            l.name,
            COUNT(DISTINCT c.id) as courses_count,
            COUNT(DISTINCT les.id) as lessons_count,
            SUM(COALESCE(les.duration, 0)) as total_duration,
            (
                SELECT COUNT(DISTINCT les2.id)
                FROM lessons les2
                INNER JOIN courses c2 ON les2.course_id = c2.id
                WHERE c2.language_id = l.id AND les2.status_id = 1
            ) as completed_lessons
        FROM languages l
        LEFT JOIN courses c ON c.language_id = l.id
        LEFT JOIN lessons les ON les.course_id = c.id
        WHERE l.id = ?
        GROUP BY l.id, l.name
    ");
    
    $stmt->execute([$language_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stats) {
        http_response_code(404);
        exit('لم يتم العثور على اللغة');
    }

    // تنسيق المدة الزمنية
    $hours = floor($stats['total_duration'] / 3600);
    $minutes = floor(($stats['total_duration'] % 3600) / 60);
    
    // إنشاء HTML للإحصائيات
    $html = "
        <div class='stats-details'>
            <h3 class='text-center mb-4'>{$stats['name']}</h3>
            
            <div class='row'>
                <div class='col-md-6 mb-3'>
                    <div class='card bg-primary text-white'>
                        <div class='card-body text-center'>
                            <h3>{$stats['courses_count']}</h3>
                            <p class='mb-0'>الدورات</p>
                        </div>
                    </div>
                </div>
                <div class='col-md-6 mb-3'>
                    <div class='card bg-success text-white'>
                        <div class='card-body text-center'>
                            <h3>{$stats['lessons_count']}</h3>
                            <p class='mb-0'>الدروس</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class='chart-container'>
                <canvas id='statsChart'></canvas>
            </div>

            <script>
                new Chart(document.getElementById('statsChart'), {
                    type: 'doughnut',
                    data: {
                        labels: ['الدروس المكتملة', 'الدروس المتبقية'],
                        datasets: [{
                            data: [
                                {$stats['completed_lessons']},
                                {$stats['lessons_count']} - {$stats['completed_lessons']}
                            ],
                            backgroundColor: ['#4caf50', '#f44336']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            title: {
                                display: true,
                                text: 'نسبة إكمال الدروس'
                            }
                        }
                    }
                });
            </script>
        </div>
    ";
    
    echo $html;
} catch (Exception $e) {
    http_response_code(500);
    exit('حدث خطأ أثناء جلب الإحصائيات');
} 