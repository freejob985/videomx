<?php
// التأكد من اتصال قاعدة البيانات
require_once 'config/database.php';
require_once 'includes/functions.php';

// جلب اللغات المتاحة مع عدد الدروس والكورسات
$languages_query = "SELECT 
    l.*,
    COUNT(DISTINCT les.id) as lessons_count,
    COUNT(DISTINCT c.id) as courses_count
    FROM languages l
    LEFT JOIN courses c ON c.language_id = l.id
    LEFT JOIN lessons les ON les.course_id = c.id AND les.status_id = 1
    GROUP BY l.id
    ORDER BY l.name ASC";

$languages_result = $conn->query($languages_query);
$available_languages = [];
$selected_language_id = isset($_GET['language_id']) ? (int)$_GET['language_id'] : 0;

if ($languages_result) {
    while ($row = $languages_result->fetch_assoc()) {
        $available_languages[] = $row;
    }
    $languages_result->close();
}

/**
 * جلب الكورسات التي تحتوي على دروس مراجعة
 * @return array الكورسات المراجعة
 */
function getReviewedCourses() {
    global $conn;
    
    $sql = "SELECT DISTINCT c.*, 
            (SELECT COUNT(*) FROM lessons WHERE course_id = c.id AND is_reviewed = 1) as reviewed_lessons_count,
            (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons_count
            FROM courses c
            INNER JOIN lessons l ON c.id = l.course_id
            WHERE l.is_reviewed = 1
            ORDER BY c.title";
            
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * جلب إحصائيات الكورسات المراجعة
 * @return array الإحصائيات
 */
function getCoursesStatistics() {
    global $conn;
    
    $stats = [
        'total_lessons' => 0,
        'completed_lessons' => 0,
        'remaining_lessons' => 0,
        'total_duration' => 0,
        'completed_duration' => 0,
        'remaining_duration' => 0
    ];
    
    $sql = "SELECT 
            COUNT(*) as total_lessons,
            SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_lessons,
            SUM(duration) as total_duration,
            SUM(CASE WHEN completed = 1 THEN duration ELSE 0 END) as completed_duration
            FROM lessons 
            WHERE is_reviewed = 1";
            
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $stats['total_lessons'] = $row['total_lessons'];
        $stats['completed_lessons'] = $row['completed_lessons'];
        $stats['remaining_lessons'] = $row['total_lessons'] - $row['completed_lessons'];
        $stats['total_duration'] = $row['total_duration'];
        $stats['completed_duration'] = $row['completed_duration'];
        $stats['remaining_duration'] = $row['total_duration'] - $row['completed_duration'];
    }
    
    return $stats;
}

$courses = getReviewedCourses();
$statistics = getCoursesStatistics();

// تحويل الوقت من دقائق إلى ساعات ودقائق
function formatDuration($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf("%d ساعة و %d دقيقة", $hours, $mins);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الكورسات المراجعة</title>
    
    <!-- المكتبات المطلوبة -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <!-- إضافة قسم الإحصائيات -->
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">إحصائيات الدروس المراجعة</h3>
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="border-end">
                                    <h4 class="text-primary"><?php echo $statistics['total_lessons']; ?></h4>
                                    <p class="text-muted mb-0">إجمالي الدروس</p>
                                    <small class="text-muted"><?php echo formatDuration($statistics['total_duration']); ?></small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="border-end">
                                    <h4 class="text-success"><?php echo $statistics['completed_lessons']; ?></h4>
                                    <p class="text-muted mb-0">الدروس المكتملة</p>
                                    <small class="text-muted"><?php echo formatDuration($statistics['completed_duration']); ?></small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div>
                                    <h4 class="text-warning"><?php echo $statistics['remaining_lessons']; ?></h4>
                                    <p class="text-muted mb-0">الدروس المتبقية</p>
                                    <small class="text-muted"><?php echo formatDuration($statistics['remaining_duration']); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <h2 class="mb-4">الكورسات المراجعة</h2>
        
        <div class="row">
            <?php foreach ($courses as $course): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if ($course['thumbnail']): ?>
                            <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                            <p class="card-text">
                                <?php echo nl2br(htmlspecialchars(substr($course['description'], 0, 150)) . '...'); ?>
                            </p>
                            
                            <!-- بروجرس بار للدروس المراجعة -->
                            <div class="progress mb-3" style="height: 20px;">
                                <?php 
                                $percentage = ($course['reviewed_lessons_count'] / $course['total_lessons_count']) * 100;
                                ?>
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo $percentage; ?>%"
                                     aria-valuenow="<?php echo $percentage; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo round($percentage); ?>%
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-primary">
                                    <?php echo $course['reviewed_lessons_count']; ?> من <?php echo $course['total_lessons_count']; ?> درس
                                </span>
                                <a href="lessons.php?course_id=<?php echo $course['id']; ?>" 
                                   class="btn btn-primary">
                                    عرض الدروس
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- المكتبات JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>

    <style>
    /* تنسيقات قسم اللغات */
    .languages-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 30px 0;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        margin-bottom: 3rem;
    }

    .languages-container {
        transition: all 0.3s ease;
        max-height: 2000px; /* زيادة القيمة */
        opacity: 1;
        overflow: hidden;
        padding: 15px;
        transform-origin: top;
        transform: scaleY(1);
    }

    .languages-container.collapsed {
        max-height: 0;
        opacity: 0;
        padding: 0;
        margin: 0;
        transform: scaleY(0);
    }

    .btn-toggle-languages {
        width: 35px;
        height: 35px;
        border: none;
        border-radius: 50%;
        background: #0d6efd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-toggle-languages i {
        transition: transform 0.3s ease;
    }

    .btn-toggle-languages.collapsed i {
        transform: rotate(180deg);
    }

    /* تنسيقات حاوية اللغات */
    .languages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
        padding: 10px 0;
    }

    .language-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        text-decoration: none;
        color: #2c3e50;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .language-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        text-decoration: none;
        color: #0d6efd;
    }

    .language-card.active {
        background: #0d6efd;
        color: white;
    }

    .language-icon {
        width: 50px;
        height: 50px;
        background: #f8f9fa;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5em;
        transition: all 0.3s ease;
    }

    .language-card.active .language-icon {
        background: rgba(255,255,255,0.2);
        color: white;
    }

    .language-info {
        flex: 1;
    }

    .language-info h6 {
        margin: 0 0 5px 0;
        font-weight: 600;
    }

    .language-stats {
        display: flex;
        gap: 15px;
        font-size: 0.85em;
        color: #6c757d;
    }

    .language-card.active .language-stats {
        color: rgba(255,255,255,0.9);
    }

    /* تحسين التجاوب */
    @media (max-width: 768px) {
        .languages-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
        
        .language-card {
            padding: 12px;
        }
        
        .language-icon {
            width: 40px;
            height: 40px;
            font-size: 1.2em;
        }
        
        .language-stats {
            flex-direction: column;
            gap: 5px;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleLanguages');
        const container = document.getElementById('languagesContainer');
        const section = document.getElementById('languagesSection');
        const storageKey = 'coursesLanguagesSectionCollapsed';
        
        // تحقق من وجود العناصر
        if (!toggleBtn || !container || !section) {
            console.error('لم يتم العثور على أحد العناصر المطلوبة');
            return;
        }

        function toggleSection(collapse) {
            if (collapse) {
                container.classList.add('collapsed');
                toggleBtn.classList.add('collapsed');
                section.style.marginBottom = '0';
            } else {
                container.classList.remove('collapsed');
                toggleBtn.classList.remove('collapsed');
                section.style.marginBottom = '3rem';
            }
        }
        
        // استرجاع الحالة المحفوظة
        const isCollapsed = localStorage.getItem(storageKey) === 'true';
        toggleSection(isCollapsed);
        
        // معالجة النقر على زر الإخفاء/الإظهار
        toggleBtn.addEventListener('click', function() {
            const willCollapse = !container.classList.contains('collapsed');
            toggleSection(willCollapse);
            localStorage.setItem(storageKey, willCollapse);
            
            // تأثير حركي للزر
            this.style.transform = 'scale(0.9)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
    </script>

    <?php
    // إضافة الفوتر
    require_once 'includes/footer.php';
    ?>
</body>
</html> 