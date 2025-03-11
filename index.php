<?php
// إعداد الاتصال بقاعدة البيانات
$host = 'localhost';
$dbname = 'courses_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

// تعديل استعلام جلب اللغات ليشمل فقط اللغات التي تحتوي على دروس مع عددها
$languagesQuery = "
    SELECT 
        l.*,
        COUNT(DISTINCT c.id) as courses_count,
        COUNT(les.id) as lessons_count
    FROM languages l
    INNER JOIN courses c ON l.id = c.language_id
    INNER JOIN lessons les ON c.id = les.course_id
    GROUP BY l.id
    HAVING courses_count > 0
    ORDER BY l.name
";
$languagesStmt = $pdo->prepare($languagesQuery);
$languagesStmt->execute();
$languages = $languagesStmt->fetchAll(PDO::FETCH_ASSOC);

// إضافة استعلام للتحقق من الكورسات المفضلة
$query = "SELECT 
    c.*, 
    l.name as language_name,
    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id AND completed = 1) as completed_lessons,
    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id AND is_important = 1) as important_lessons,
    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id AND is_theory = 1) as theory_lessons,
    SEC_TO_TIME(SUM(IFNULL(l2.duration, 0))) as total_duration,
    SEC_TO_TIME(SUM(CASE WHEN l2.completed = 1 THEN IFNULL(l2.duration, 0) ELSE 0 END)) as completed_duration,
    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id AND status_id = 1) as status_1_count,
    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id AND status_id = 2) as status_2_count,
    CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END as is_favorite
    FROM courses c 
    LEFT JOIN languages l ON c.language_id = l.id 
    LEFT JOIN lessons l2 ON c.id = l2.course_id
    LEFT JOIN favorites f ON c.id = f.course_id
    WHERE 1=1 " . 
    (isset($_GET['language_id']) && !empty($_GET['language_id']) ? 
    "AND c.language_id = :language_id " : "") .
    (isset($_GET['favorites']) && $_GET['favorites'] == '1' ? 
    "AND f.id IS NOT NULL " : "") .
    "GROUP BY c.id, c.title, c.playlist_url, c.language_id, c.thumbnail, c.description, c.created_at, c.updated_at, c.processing_status, l.name
    ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($query);
if (isset($_GET['language_id']) && !empty($_GET['language_id'])) {
    $stmt->bindParam(':language_id', $_GET['language_id'], PDO::PARAM_INT);
}
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منصة الكورسات التعليمية</title>
    
    <!-- Bootstrap RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    
    <!-- Material Design Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Toastify -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(45deg, #1e3c72 0%, #2a5298 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .card {
            transition: transform 0.3s ease-in-out;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        
        .course-title {
            color: #2c3e50;
            font-weight: 700;
        }
        
        .language-badge {
            background: linear-gradient(45deg, #11998e, #38ef7d);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .footer {
            background: linear-gradient(45deg, #1a2a6c, #b21f1f, #fdbb2d);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
            position: relative;
            overflow: hidden;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, #00f260, #0575e6);
        }
        
        .lessons-count {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(45deg, #FF512F, #DD2476);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            z-index: 1;
        }
        
        .social-links {
            margin-top: 20px;
        }
        
        .social-links a {
            color: white;
            margin: 0 10px;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .social-links a:hover {
            transform: translateY(-3px);
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .social-links {
                margin-top: 15px;
            }
        }

        .stats-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .stat-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-left: 8px;
            color: white;
        }

        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(to right, #11998e, #38ef7d);
        }

        .important-badge {
            background: linear-gradient(45deg, #FF416C, #FF4B2B);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-right: 5px;
        }

        .footer-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: white;
            text-decoration: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .ai-link:hover {
            color: #38ef7d !important;
        }

        /* تحسين المسافات بين الكورسات */
        .row {
            margin: 0 -15px; /* إضافة هوامش سالبة للصف */
        }
        
        .col-md-4 {
            padding: 15px; /* إضافة تباعد متساوي للأعمدة */
        }
        
        .card {
            height: 100%; /* ضمان ارتفاع متساوي للبطاقات */
            margin-bottom: 0; /* إزالة الهامش السفلي للبطاقة لأن التباعد سيتم من خلال العمود */
            display: flex;
            flex-direction: column;
        }
        
        .card-body {
            flex: 1; /* جعل جسم البطاقة يمتد ليملأ المساحة المتاحة */
        }

        /* تحسين تناسق العرض على الشاشات الصغيرة */
        @media (max-width: 768px) {
            .col-md-4 {
                padding: 10px; /* تقليل التباعد على الشاشات الصغيرة */
            }
        }

        .language-filter {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            width: 100%;
            margin-bottom: 20px;
            font-family: 'Tajawal', sans-serif;
        }

        .language-filter:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.25);
        }

        /* تحسين عرض الوقت */
        .time-stats {
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-size: 0.9rem;
        }

        .time-stats div {
            display: flex;
            justify-content: space-between;
        }

        .language-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .language-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            color: #495057;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .language-btn:hover {
            background: #e9ecef;
            color: #212529;
            transform: translateY(-2px);
        }

        .language-btn.active {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            color: white;
            border-color: #1e3c72;
        }

        .count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            margin-right: 8px;
            background: rgba(255,255,255,0.9);
            color: #1e3c72;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .language-btn.active .count-badge {
            background: rgba(255,255,255,0.9);
            color: #1e3c72;
        }

        @media (max-width: 768px) {
            .language-filters {
                padding: 10px;
            }

            .language-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }

        .btn-favorite {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-favorite:hover {
            transform: scale(1.1);
        }

        .btn-favorite i {
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .btn-favorite:hover i.text-secondary {
            color: #dc3545 !important;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap me-2"></i>
                منصة الكورسات التعليمية
            </a>
        </div>
    </nav>

    <!-- بعد navbar مباشرة -->
    <div class="container mb-4">
        <div class="row">
            <div class="col-12">
                <div class="language-filters">
                    <a href="index.php" class="language-btn <?php echo !isset($_GET['language_id']) && !isset($_GET['favorites']) ? 'active' : ''; ?>">
                        <span class="btn-label">جميع الكورسات</span>
                        <span class="count-badge"><?php 
                            $totalLessons = array_sum(array_column($languages, 'lessons_count')); 
                            echo $totalLessons;
                        ?></span>
                    </a>
                    <a href="?favorites=1" class="language-btn <?php echo isset($_GET['favorites']) && $_GET['favorites'] == '1' ? 'active' : ''; ?>">
                        <i class="fas fa-heart me-1"></i>
                        <span class="btn-label">المفضلة</span>
                    </a>
                    <?php foreach($languages as $language): ?>
                        <a href="?language_id=<?php echo $language['id']; ?>" 
                           class="language-btn <?php echo (isset($_GET['language_id']) && $_GET['language_id'] == $language['id']) ? 'active' : ''; ?>">
                            <span class="btn-label"><?php echo htmlspecialchars($language['name']); ?></span>
                            <span class="count-badge"><?php echo $language['lessons_count']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <?php foreach($courses as $course): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <button class="btn btn-favorite position-absolute" 
                                style="left: 10px; top: 10px; z-index: 2;"
                                onclick="toggleFavorite(<?php echo $course['id']; ?>, this)"
                                data-favorite="<?php echo $course['is_favorite']; ?>">
                            <i class="fas fa-heart <?php echo $course['is_favorite'] ? 'text-danger' : 'text-secondary'; ?>"></i>
                        </button>
                        <div class="lessons-count">
                            <i class="fas fa-book-open me-1"></i>
                            <?php echo htmlspecialchars($course['total_lessons']); ?> درس
                        </div>
                        <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <div class="card-body">
                            <h5 class="course-title">
                                <?php echo htmlspecialchars($course['title']); ?>
                                <?php if($course['important_lessons'] > 0): ?>
                                    <span class="important-badge">
                                        <i class="fas fa-star me-1"></i>
                                        <?php echo $course['important_lessons']; ?> دروس مهمة
                                    </span>
                                <?php endif; ?>
                            </h5>
                            
                            <div class="stats-container">
                                <div class="stat-item">
                                    <div class="stat-icon bg-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>الدروس المكتملة</span>
                                            <span><?php echo $course['completed_lessons']; ?>/<?php echo $course['total_lessons']; ?></span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-custom" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($course['total_lessons'] > 0 ? ($course['completed_lessons'] / $course['total_lessons'] * 100) : 0); ?>%">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-icon" style="background: #6c5ce7;">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <div>الوقت الإجمالي: <?php echo $course['total_duration']; ?></div>
                                        <div>الوقت المكتمل: <?php echo $course['completed_duration']; ?></div>
                                    </div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-icon" style="background: #00b894;">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <span>دروس نظرية: <?php echo $course['theory_lessons']; ?></span>
                                </div>
                            </div>

                            <p class="card-text">
                                <?php echo mb_substr(htmlspecialchars($course['description']), 0, 100) . '...'; ?>
                            </p>
                            <span class="language-badge">
                                <i class="fas fa-language me-1"></i>
                                <?php echo htmlspecialchars($course['language_name']); ?>
                            </span>
                        </div>
                        <div class="card-footer bg-white border-0">
                            <a href="http://videomx.com/content/lessons.php?course_id=<?php echo $course['id']; ?>" 
                               class="btn btn-primary w-100">
                                <i class="fas fa-play-circle me-1"></i>
                                مشاهدة الدروس
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row py-5">
                <!-- قسم روابط الدروس -->
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-4">عرض الدروس</h5>
                    <ul class="list-unstyled footer-links">
                        <?php if (isset($course_id)): ?>
                            <li class="mb-2">
                                <a href="views/lessons-cards.php?course_id=<?php echo $course_id; ?>" class="text-white">
                                    <i class="fas fa-th-large me-2"></i>
                                    عرض البطاقات
                                </a>
                            </li>
                            <li>
                                <a href="lessons.php?course_id=<?php echo $course_id; ?>" class="text-white">
                                    <i class="fas fa-list me-2"></i>
                                    عرض القائمة
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- قسم الروابط الرئيسية -->
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-4">روابط رئيسية</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2">
                            <a href="http://videomx.com/content/languages.php" class="text-white">
                                <i class="fas fa-globe me-2"></i>
                                اللغات
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="index.php" class="text-white">
                                <i class="fas fa-home me-2"></i>
                                الرئيسية
                            </a>
                        </li>
                        <li>
                            <a href="http://videomx.com/content/index.php" class="text-white">
                                <i class="fas fa-graduation-cap me-2"></i>
                                الدورات
                            </a>
                        </li>
                        <li>
                            <a href="http://videomx.com/review/" class="text-white">
                                <i class="fas fa-star me-2"></i>
                                المراجعة
                            </a>
                        </li>
                        <li>
                            <a href="http://videomx.com/content/search/" class="text-white">
                                <i class="fas fa-search me-2"></i>
                                البحث
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- قسم الروابط الإضافية -->
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-4">روابط إضافية</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2">
                            <a href="http://videomx.com/add/add.php" class="text-white">
                                <i class="fas fa-cog me-2"></i>
                                الإعدادات
                            </a>
                        </li>
                        <li>
                            <a href="http://videomx.com/GBT/" class="text-white ai-link">
                                <i class="fas fa-robot me-2"></i>
                                المساعد الذكي
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            
            <!-- حقوق النشر -->
            <div class="text-center">
                <div class="social-links mb-3">
                    <a href="#" title="فيسبوك"><i class="fab fa-facebook"></i></a>
                    <a href="#" title="تويتر"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="يوتيوب"><i class="fab fa-youtube"></i></a>
                    <a href="#" title="انستغرام"><i class="fab fa-instagram"></i></a>
                </div>
                <p class="mb-0 text-white">جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> VideoMX</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <script>
        // مثال على استخدام Toastify لعرض رسالة ترحيب
        Toastify({
            text: "مرحباً بك في منصة الكورسات التعليمية",
            duration: 3000,
            gravity: "top",
            position: "center",
            style: {
                background: "linear-gradient(to right, #3494E6, #EC6EAD)",
            }
        }).showToast();
    </script>

    <!-- إضافة سكريبت AJAX قبل نهاية body -->
    <script>
    document.querySelectorAll('.language-filter').forEach(button => {
        button.addEventListener('click', function() {
            const languageId = this.getAttribute('data-language-id');
            const url = new URL(window.location.href);
            
            if (languageId) {
                url.searchParams.set('language_id', languageId);
            } else {
                url.searchParams.delete('language_id');
            }
            
            // تحديث عنوان URL وإعادة تحميل الصفحة
            window.location.href = url.toString();
        });
    });

    // تنسيق عرض الوقت
    function formatDuration(duration) {
        if (!duration) return '00:00:00';
        return duration;
    }

    function toggleFavorite(courseId, button) {
        fetch('toggle_favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ course_id: courseId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const icon = button.querySelector('i');
                if (data.is_favorite) {
                    icon.classList.remove('text-secondary');
                    icon.classList.add('text-danger');
                    showToast('تمت إضافة الكورس إلى المفضلة');
                } else {
                    icon.classList.remove('text-danger');
                    icon.classList.add('text-secondary');
                    showToast('تمت إزالة الكورس من المفضلة');
                }
                button.dataset.favorite = data.is_favorite ? '1' : '0';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ أثناء تحديث المفضلة');
        });
    }

    function showToast(message) {
        Toastify({
            text: message,
            duration: 3000,
            gravity: "top",
            position: "center",
            style: {
                background: "linear-gradient(to right, #3494E6, #EC6EAD)",
            }
        }).showToast();
    }
    </script>
</body>
</html>
