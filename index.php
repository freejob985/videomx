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
    
    <!-- إضافة ملف الإحصائيات -->
    <script src="js/statistics.js" defer></script>
    
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

        .statistics-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .statistics-title {
            color: #2c3e50;
            font-weight: 700;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            color: white;
            font-size: 1.2rem;
        }

        .stat-details {
            flex: 1;
        }

        .stat-details h6 {
            margin: 0;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .stat-numbers {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        /* تلوين الأيقونات */
        .stat-card.completed .stat-icon { background: #2ecc71; }
        .stat-card.remaining .stat-icon { background: #e74c3c; }
        .stat-card.duration .stat-icon { background: #3498db; }
        .stat-card.total-time .stat-icon { background: #9b59b6; }
        .stat-card.review .stat-icon { background: #f1c40f; }
        .stat-card.theory .stat-icon { background: #1abc9c; }
        .stat-card.important .stat-icon { background: #e67e22; }
        .stat-card.remaining-time .stat-icon { background: #95a5a6; }

        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 15px;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .stat-numbers {
                font-size: 1rem;
            }
        }

        /* أنماط حالة التحميل */
        .stat-numbers.loading {
            opacity: 0.5;
            position: relative;
        }

        .stat-numbers.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* تحديث أنماط القائمة السياقية */
        .context-menu {
            position: fixed;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 8px;
            padding: 10px 0;
            min-width: 200px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            transform: scale(0.8);
        }

        .context-menu.show {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        .context-menu-item {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .context-menu-item:hover {
            background: rgba(255,255,255,0.1);
            padding-right: 25px;
        }

        .context-menu-item i {
            margin-left: 10px;
            width: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .context-menu-item:hover i {
            transform: scale(1.2);
        }

        .context-menu-header {
            padding: 10px 20px;
            color: #fff;
            font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 5px;
        }

        .context-menu-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 5px 0;
        }

        /* تنسيق روابط القائمة */
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        /* تنسيق الأيقونات */
        .navbar-nav .nav-link i {
            transition: transform 0.3s ease;
        }

        .navbar-nav .nav-link:hover i {
            transform: scale(1.2);
        }

        /* تنسيق زر التبديل */
        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            border-radius: 5px;
            background: rgba(255,255,255,0.1);
        }

        .navbar-toggler:focus {
            box-shadow: none;
            outline: none;
        }

        /* تحسينات للشاشات الصغيرة */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: rgba(255,255,255,0.1);
                padding: 1rem;
                border-radius: 10px;
                margin-top: 1rem;
            }
            
            .navbar-nav .nav-link {
                padding: 0.75rem 1rem;
                margin: 0.25rem 0;
            }
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
            
            <!-- إضافة زر التبديل للقائمة في الشاشات الصغيرة -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- محتوى القائمة -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- القائمة الرئيسية -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- روابط رئيسية -->
                    <li class="nav-item">
                        <a class="nav-link" href="http://videomx.com/content/languages.php">
                            <i class="fas fa-globe ms-1"></i>
                            اللغات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home ms-1"></i>
                            الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="http://videomx.com/content/index.php">
                            <i class="fas fa-graduation-cap ms-1"></i>
                            الدورات
                        </a>
                    </li>

                    <!-- روابط إضافية -->
                    <li class="nav-item">
                        <a class="nav-link" href="http://videomx.com/add/add.php">
                            <i class="fas fa-cog ms-1"></i>
                            الإعدادات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="http://videomx.com/GBT/">
                            <i class="fas fa-robot ms-1"></i>
                            المساعد الذكي
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- إضافة فلتر اللغات -->
    <div class="container mb-4">
        <div class="language-filters">
            <a href="?favorites=1" class="language-btn <?php echo (isset($_GET['favorites']) && $_GET['favorites'] == '1') ? 'active' : ''; ?>">
                <i class="fas fa-heart me-2"></i>
                المفضلة
            </a>
            
            <a href="?" class="language-btn <?php echo (!isset($_GET['language_id']) && !isset($_GET['favorites'])) ? 'active' : ''; ?>">
                <i class="fas fa-globe me-2"></i>
                جميع اللغات
                <span class="count-badge"><?php echo count($courses); ?></span>
            </a>
            
            <?php foreach($languages as $language): ?>
                <a href="?language_id=<?php echo $language['id']; ?>" 
                   class="language-btn <?php echo (isset($_GET['language_id']) && $_GET['language_id'] == $language['id']) ? 'active' : ''; ?>">
                    <i class="fas fa-code me-2"></i>
                    <?php echo htmlspecialchars($language['name']); ?>
                    <span class="count-badge"><?php echo $language['courses_count']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- بعد قسم language-filters مباشرة -->
    <div class="container mb-4">
        <div class="row">
            <div class="col-12">
                <div class="statistics-container">
                    <h4 class="statistics-title mb-3">
                        <i class="fas fa-chart-bar me-2"></i>
                        إحصائيات الدروس
                    </h4>
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card completed">
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-details">
                                    <h6>الدروس المكتملة</h6>
                                    <div class="stat-numbers">
                                        <span id="completedLessons">0</span>
                                        <span class="text-muted">/</span>
                                        <span id="totalLessons">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card remaining">
                                <div class="stat-icon">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div class="stat-details">
                                    <h6>الدروس المتبقية</h6>
                                    <div class="stat-numbers" id="remainingLessons">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card duration">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-details">
                                    <h6>الوقت المكتمل</h6>
                                    <div class="stat-numbers" id="completedDuration">00:00:00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card total-time">
                                <div class="stat-icon">
                                    <i class="fas fa-stopwatch"></i>
                                </div>
                                <div class="stat-details">
                                    <h6>الوقت الكلي</h6>
                                    <div class="stat-numbers" id="totalDuration">00:00:00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card review">
                                <div class="stat-icon">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                                <div class="stat-details">
                                    <h6>دروس المراجعة</h6>
                                    <div class="stat-numbers" id="reviewLessons">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card theory">
                                <div class="stat-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="stat-details">
                                    <h6>دروس نظرية</h6>
                                    <div class="stat-numbers" id="theoryLessons">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card important">
                                <div class="stat-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="stat-details">
                                    <h6>دروس مهمة</h6>
                                    <div class="stat-numbers" id="importantLessons">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card remaining-time">
                                <div class="stat-icon">
                                    <i class="fas fa-hourglass-end"></i>
                                </div>
                                <div class="stat-details">
                                    <h6>الوقت المتبقي</h6>
                                    <div class="stat-numbers" id="remainingDuration">00:00:00</div>
                                </div>
                            </div>
                        </div>
                    </div>
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

                <!-- تحديث قسم الروابط الرئيسية في Footer -->
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-4">روابط رئيسية</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="position-relative mb-2">
                            <a href="http://videomx.com/content/languages.php" class="text-white">
                                <i class="fas fa-globe me-2"></i>
                                اللغات
                            </a>
                            <div class="context-menu">
                                <div class="context-menu-header">
                                    <i class="fas fa-globe me-2"></i>
                                    قائمة اللغات
                                </div>
                                <a href="http://videomx.com/content/languages.php?type=programming" class="context-menu-item">
                                    <i class="fas fa-code"></i>
                                    لغات البرمجة
                                </a>
                                <a href="http://videomx.com/content/languages.php?type=spoken" class="context-menu-item">
                                    <i class="fas fa-comments"></i>
                                    اللغات المحكية
                                </a>
                            </div>
                        </li>
                        
                        <li class="position-relative mb-2">
                            <a href="index.php" class="text-white">
                                <i class="fas fa-home me-2"></i>
                                الرئيسية
                            </a>
                            <div class="context-menu">
                                <div class="context-menu-header">
                                    <i class="fas fa-home me-2"></i>
                                    القائمة الرئيسية
                                </div>
                                <a href="index.php?view=grid" class="context-menu-item">
                                    <i class="fas fa-th-large"></i>
                                    عرض الشبكة
                                </a>
                                <a href="index.php?view=list" class="context-menu-item">
                                    <i class="fas fa-list"></i>
                                    عرض القائمة
                                </a>
                            </div>
                        </li>
                        
                        <li class="position-relative mb-2">
                            <a href="http://videomx.com/content/index.php" class="text-white">
                                <i class="fas fa-graduation-cap me-2"></i>
                                الدورات
                            </a>
                            <div class="context-menu">
                                <div class="context-menu-header">
                                    <i class="fas fa-graduation-cap me-2"></i>
                                    قائمة الدورات
                                </div>
                                <a href="http://videomx.com/content/index.php?type=latest" class="context-menu-item">
                                    <i class="fas fa-clock"></i>
                                    أحدث الدورات
                                </a>
                                <a href="http://videomx.com/content/index.php?type=popular" class="context-menu-item">
                                    <i class="fas fa-fire"></i>
                                    الدورات الشائعة
                                </a>
                                <div class="context-menu-divider"></div>
                                <a href="http://videomx.com/content/index.php?type=free" class="context-menu-item">
                                    <i class="fas fa-gift"></i>
                                    الدورات المجانية
                                </a>
                            </div>
                        </li>
                        
                        <li class="position-relative">
                            <a href="http://videomx.com/review/" class="text-white">
                                <i class="fas fa-star me-2"></i>
                                المراجعة
                            </a>
                            <div class="context-menu">
                                <div class="context-menu-header">
                                    <i class="fas fa-star me-2"></i>
                                    قائمة المراجعة
                                </div>
                                <a href="http://videomx.com/review/?type=daily" class="context-menu-item">
                                    <i class="fas fa-calendar-day"></i>
                                    المراجعة اليومية
                                </a>
                                <a href="http://videomx.com/review/?type=weekly" class="context-menu-item">
                                    <i class="fas fa-calendar-week"></i>
                                    المراجعة الأسبوعية
                                </a>
                            </div>
                        </li>
                        
                        <li class="position-relative">
                            <a href="http://videomx.com/content/search/" class="text-white">
                                <i class="fas fa-search me-2"></i>
                                البحث
                            </a>
                            <div class="context-menu">
                                <div class="context-menu-header">
                                    <i class="fas fa-search me-2"></i>
                                    خيارات البحث
                                </div>
                                <a href="http://videomx.com/content/search/?type=advanced" class="context-menu-item">
                                    <i class="fas fa-sliders-h"></i>
                                    بحث متقدم
                                </a>
                                <a href="http://videomx.com/content/search/tags.php" class="context-menu-item">
                                    <i class="fas fa-tags"></i>
                                    البحث بالوسوم
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- تحديث قسم الروابط الإضافية -->
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-4">روابط إضافية</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="position-relative mb-2">
                            <a href="http://videomx.com/add/add.php" class="text-white">
                                <i class="fas fa-cog me-2"></i>
                                الإعدادات
                            </a>
                            <div class="context-menu">
                                <div class="context-menu-header">
                                    <i class="fas fa-cog me-2"></i>
                                    الإعدادات
                                </div>
                                <a href="http://videomx.com/add/profile.php" class="context-menu-item">
                                    <i class="fas fa-user"></i>
                                    الملف الشخصي
                                </a>
                                <a href="http://videomx.com/add/preferences.php" class="context-menu-item">
                                    <i class="fas fa-sliders-h"></i>
                                    تفضيلات العرض
                                </a>
                                <div class="context-menu-divider"></div>
                                <a href="http://videomx.com/add/notifications.php" class="context-menu-item">
                                    <i class="fas fa-bell"></i>
                                    الإشعارات
                                </a>
                            </div>
                        </li>
                        
                        <li class="position-relative">
                            <a href="http://videomx.com/GBT/" class="text-white ai-link">
                                <i class="fas fa-robot me-2"></i>
                                المساعد الذكي
                            </a>
                            <div class="context-menu">
                                <div class="context-menu-header">
                                    <i class="fas fa-robot me-2"></i>
                                    المساعد الذكي
                                </div>
                                <a href="http://videomx.com/GBT/chat.php" class="context-menu-item">
                                    <i class="fas fa-comments"></i>
                                    المحادثة الذكية
                                </a>
                                <a href="http://videomx.com/GBT/help.php" class="context-menu-item">
                                    <i class="fas fa-question-circle"></i>
                                    المساعدة
                                </a>
                            </div>
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

    <!-- تحديث سكريبت JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuItems = document.querySelectorAll('.footer-links li');
        
        menuItems.forEach(item => {
            const link = item.querySelector('a');
            const menu = item.querySelector('.context-menu');
            
            if (menu) {
                // تحديث موقع القائمة عند تحريك المؤشر
                item.addEventListener('mouseenter', function(e) {
                    const rect = item.getBoundingClientRect();
                    const windowWidth = window.innerWidth;
                    const menuWidth = menu.offsetWidth;
                    
                    // حساب المساحة المتاحة
                    const spaceOnRight = windowWidth - (rect.right + menuWidth);
                    const spaceOnLeft = rect.left - menuWidth;
                    
                    // تحديد موقع القائمة
                    if (spaceOnRight > 0) {
                        // عرض القائمة على اليمين
                        menu.style.left = '100%';
                        menu.style.right = 'auto';
                        menu.style.top = '0';
                    } else if (spaceOnLeft > 0) {
                        // عرض القائمة على اليسار
                        menu.style.right = '100%';
                        menu.style.left = 'auto';
                        menu.style.top = '0';
                    } else {
                        // عرض القائمة أعلى العنصر
                        menu.style.left = '0';
                        menu.style.right = 'auto';
                        menu.style.top = '-100%';
                    }
                    
                    menu.classList.add('show');
                });
                
                // إخفاء القائمة
                item.addEventListener('mouseleave', function() {
                    menu.classList.remove('show');
                });
                
                // منع إخفاء القائمة عند النقر
                menu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                
                // تأثيرات حركية للروابط
                menu.querySelectorAll('.context-menu-item').forEach(menuItem => {
                    menuItem.addEventListener('mouseenter', function() {
                        this.querySelector('i').style.transform = 'scale(1.2)';
                    });
                    
                    menuItem.addEventListener('mouseleave', function() {
                        this.querySelector('i').style.transform = 'scale(1)';
                    });
                });
            }
        });
    });
    </script>

    <!-- إضافة قائمة السياق العامة في نهاية body قبل السكريبتات -->
    <div id="globalContextMenu" class="context-menu">
        <div class="context-menu-header">
            <i class="fas fa-bars me-2"></i>
            القائمة الرئيسية
        </div>
        <a href="http://videomx.com/content/languages.php" class="context-menu-item">
            <i class="fas fa-globe"></i>
            اللغات
        </a>
        <a href="/" class="context-menu-item">
            <i class="fas fa-home"></i>
            الرئيسية
        </a>
        <a href="http://videomx.com/content/index.php" class="context-menu-item">
            <i class="fas fa-graduation-cap"></i>
            الدورات
        </a>
        <a href="http://videomx.com/content/search/" class="context-menu-item">
            <i class="fas fa-search"></i>
            البحث
        </a>
        <div class="context-menu-divider"></div>
        <a href="http://videomx.com/add/add.php" class="context-menu-item">
            <i class="fas fa-cog"></i>
            الإعدادات
        </a>
        <a href="http://videomx.com/GBT/" class="context-menu-item">
            <i class="fas fa-robot"></i>
            المساعد الذكي
        </a>
    </div>

    <!-- إضافة JavaScript جديد -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const contextMenu = document.getElementById('globalContextMenu');
        
        // منع ظهور قائمة السياق الافتراضية للمتصفح
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            
            // تحديث موقع القائمة
            const x = e.clientX;
            const y = e.clientY;
            const windowWidth = window.innerWidth;
            const windowHeight = window.innerHeight;
            const menuWidth = contextMenu.offsetWidth;
            const menuHeight = contextMenu.offsetHeight;
            
            // التأكد من أن القائمة تظهر داخل حدود الشاشة
            let menuX = x;
            let menuY = y;
            
            if (x + menuWidth > windowWidth) {
                menuX = windowWidth - menuWidth;
            }
            
            if (y + menuHeight > windowHeight) {
                menuY = windowHeight - menuHeight;
            }
            
            // تعيين موقع القائمة
            contextMenu.style.left = `${menuX}px`;
            contextMenu.style.top = `${menuY}px`;
            
            // إظهار القائمة
            contextMenu.classList.add('show');
        });
        
        // إخفاء القائمة عند النقر في أي مكان
        document.addEventListener('click', function() {
            contextMenu.classList.remove('show');
        });
        
        // منع إخفاء القائمة عند النقر عليها
        contextMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // إضافة تأثيرات حركية للروابط
        contextMenu.querySelectorAll('.context-menu-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.querySelector('i').style.transform = 'scale(1.2)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.querySelector('i').style.transform = 'scale(1)';
            });
        });
        
        // إخفاء القائمة عند التمرير
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            contextMenu.classList.remove('show');
            
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                contextMenu.classList.remove('show');
            }, 100);
        });
        
        // إخفاء القائمة عند تغيير حجم النافذة
        window.addEventListener('resize', function() {
            contextMenu.classList.remove('show');
        });
    });
    </script>
</body>
</html>
