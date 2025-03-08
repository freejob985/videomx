<?php
require_once 'config/database.php';

/**
 * جلب معلومات الكورس
 * @param int $courseId معرف الكورس
 * @return array معلومات الكورس
 */
function getCourseInfo($courseId) {
    global $conn;
    $sql = "SELECT * FROM courses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * جلب الدروس المراجعة للكورس
 * @param int $courseId معرف الكورس
 * @return array الدروس المراجعة
 */
function getReviewedLessons($courseId) {
    global $conn;
    $sql = "SELECT l.*, s.name as section_name, st.name as status_name, 
            st.color as status_color, st.text_color as status_text_color,
            c.language_id
            FROM lessons l
            LEFT JOIN sections s ON l.section_id = s.id
            LEFT JOIN statuses st ON l.status_id = st.id
            LEFT JOIN courses c ON l.course_id = c.id
            WHERE l.course_id = ? AND l.is_reviewed = 1
            ORDER BY l.id ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * جلب الحالات حسب لغة الكورس
 * @param int $languageId معرف اللغة
 * @return array الحالات
 */
function getStatusesByLanguage($languageId) {
    global $conn;
    $sql = "SELECT * FROM statuses WHERE language_id = ? ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $languageId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * جلب الأقسام حسب لغة الكورس
 * @param int $languageId معرف اللغة
 * @return array الأقسام
 */
function getSectionsByLanguage($languageId) {
    global $conn;
    $sql = "SELECT * FROM sections WHERE language_id = ? ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $languageId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * تحديث حالة الدرس وجميع الحقول القابلة للتحديث
 * @param int $lessonId معرف الدرس
 * @param array $data البيانات المراد تحديثها
 * @return bool نجاح العملية
 */
function updateLessonStatus($lessonId, $data) {
    global $conn;
    
    // التحقق من وجود الدرس
    $checkSql = "SELECT id FROM lessons WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $lessonId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }

    // تحديث جميع الحقول القابلة للتحديث
    $sql = "UPDATE lessons SET 
            status_id = ?,
            section_id = ?,
            is_theory = ?,
            is_important = ?,
            completed = ?,
            is_reviewed = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
            
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iiiiiii",
            $data['status_id'],
            $data['section_id'],
            $data['is_theory'],
            $data['is_important'],
            $data['completed'],
            $data['is_reviewed'],
            $lessonId
        );
        
        $success = $stmt->execute();
        
        // تسجيل التحديث في سجل التغييرات إذا نجحت العملية
        if ($success) {
            logLessonUpdate($lessonId, $data);
        }
        
        return $success;
    } catch (Exception $e) {
        error_log("Error updating lesson status: " . $e->getMessage());
        return false;
    }
}

/**
 * تسجيل تحديثات الدرس في سجل التغييرات
 * @param int $lessonId معرف الدرس
 * @param array $data البيانات المحدثة
 */
function logLessonUpdate($lessonId, $data) {
    global $conn;
    
    $sql = "INSERT INTO lesson_updates (
                lesson_id,
                status_id,
                section_id,
                is_theory,
                is_important,
                completed,
                is_reviewed,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
            
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iiiiiii",
            $lessonId,
            $data['status_id'],
            $data['section_id'],
            $data['is_theory'],
            $data['is_important'],
            $data['completed'],
            $data['is_reviewed']
        );
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error logging lesson update: " . $e->getMessage());
    }
}

/**
 * جلب إحصائيات الدروس للكورس
 * @param int $courseId معرف الكورس
 * @return array الإحصائيات
 */
function getLessonsStatistics($courseId) {
    global $conn;
    
    $sql = "SELECT 
            COUNT(*) as total_lessons,
            SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_lessons,
            SUM(CASE WHEN is_theory = 1 THEN 1 ELSE 0 END) as theory_lessons,
            SUM(CASE WHEN is_important = 1 THEN 1 ELSE 0 END) as important_lessons,
            /* حساب إجمالي وقت الدروس والدروس المكتملة */
            SUM(duration) as total_duration,
            SUM(CASE WHEN completed = 1 THEN duration ELSE 0 END) as completed_duration
            FROM lessons 
            WHERE course_id = ? AND is_reviewed = 1";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    
    return [
        'total_lessons' => 0,
        'completed_lessons' => 0,
        'theory_lessons' => 0,
        'important_lessons' => 0,
        'total_duration' => 0,
        'completed_duration' => 0
    ];
}

/**
 * تحويل الوقت من دقائق إلى ساعات ودقائق
 * @param int $minutes عدد الدقائق
 * @return string الوقت بتنسيق ساعات ودقائق
 */
function formatDuration($minutes) {
    if (!$minutes || !is_numeric($minutes)) {
        return "0 دقيقة";
    }
    
    $minutes = (int)$minutes;
    // التحقق من أن الوقت منطقي (أقل من 24 ساعة)
    if ($minutes > 1440) {
        $minutes = 0;
    }
    
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($hours > 0) {
        if ($mins > 0) {
            return sprintf("%d ساعة و %d دقيقة", $hours, $mins);
        }
        return sprintf("%d ساعة", $hours);
    }
    
    return sprintf("%d دقيقة", $mins);
}

// معالجة الطلب
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$course = getCourseInfo($courseId);

if (!$course) {
    die('الكورس غير موجود');
}

// معالجة تحديث حالة الدرس
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_id'])) {
    $updateData = [
        'status_id' => isset($_POST['status_id']) ? (int)$_POST['status_id'] : null,
        'section_id' => isset($_POST['section_id']) ? (int)$_POST['section_id'] : null,
        'is_theory' => isset($_POST['is_theory']) ? 1 : 0,
        'is_important' => isset($_POST['is_important']) ? 1 : 0,
        'completed' => isset($_POST['completed']) ? 1 : 0,
        'is_reviewed' => isset($_POST['is_reviewed']) ? 1 : 0
    ];
    
    // التحقق من صحة البيانات
    if (!validateUpdateData($updateData)) {
        die('بيانات غير صالحة');
    }
    
    if (updateLessonStatus($_POST['lesson_id'], $updateData)) {
        // تحديث ناجح
        if (!headers_sent()) {
            header("Location: lessons.php?course_id=" . $courseId . "&updated=1");
            exit;
        }
    } else {
        // فشل التحديث
        die('فشل تحديث حالة الدرس');
    }
}

/**
 * التحقق من صحة بيانات التحديث
 * @param array $data البيانات المراد التحقق منها
 * @return bool نتيجة التحقق
 */
function validateUpdateData($data) {
    // التحقق من وجود status_id
    if (!isset($data['status_id']) || !is_numeric($data['status_id'])) {
        return false;
    }
    
    // التحقق من section_id إذا تم تحديده
    if (isset($data['section_id']) && !is_numeric($data['section_id'])) {
        return false;
    }
    
    // التحقق من القيم المنطقية
    foreach (['is_theory', 'is_important', 'completed', 'is_reviewed'] as $field) {
        if (!isset($data[$field]) || !in_array($data[$field], [0, 1])) {
            return false;
        }
    }
    
    return true;
}

$lessons = getReviewedLessons($courseId);
$languageId = $lessons[0]['language_id'] ?? 1; // استخدام اللغة الافتراضية إذا لم تكن محددة
$statuses = getStatusesByLanguage($languageId);
$sections = getSectionsByLanguage($languageId);
$statistics = getLessonsStatistics($courseId);

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - الدروس</title>
    
    <!-- المكتبات المطلوبة -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- Material Design Switches -->
    <link href="https://unpkg.com/@material-design-icons/font@1.0.0/index.css" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* تنسيق أزرار الأقسام */
        .sections-toggle {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .section-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: 2px solid #e0e0e0;
            background: white;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .section-btn:hover {
            border-color: #2196F3;
            color: #2196F3;
        }
        
        .section-btn.active {
            background: #2196F3;
            border-color: #2196F3;
            color: white;
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .section-btn i {
            font-size: 16px;
        }
        
        /* تحسين تنسيق النموذج */
        .lesson-controls {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .controls-header {
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .lesson-switches {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        /* تنسيق زر إخفاء الدروس المكتملة */
        .form-switch {
            padding-left: 2.5em;
        }
        
        .form-switch .form-check-input {
            width: 3em;
            margin-left: -2.5em;
            cursor: pointer;
        }
        
        .form-switch .form-check-label {
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        
        /* تنسيق الدروس المخفية */
        .col-md-6.mb-4.completed {
            display: none !important;
        }
        
        tr.completed {
            display: none !important;
        }

        /* تحسين ظهور زر التبديل */
        .form-switch {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .form-check.form-switch {
    margin-right: 22px !important;
    margin-left: 33px !important;
}

        /* تنسيق أزرار التنقل */
        .navigation-buttons {
            display: flex;
            gap: 10px;
        }
        
        .keyboard-shortcuts {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .keyboard-shortcuts i {
            margin-left: 5px;
        }
        
        .navigation-buttons .btn {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .navigation-buttons .btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .navigation-buttons .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* تنسيق أزرار الحالة */
        .statuses-toggle {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .status-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: 2px solid #e0e0e0;
            background: white;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        /* تأثير الموجة عند النقر */
        .status-btn::after,
        .section-btn::after {
            content: '';
            position: absolute;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            opacity: 1;
            pointer-events: none;
        }
        
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .status-btn:hover {
            opacity: 0.9;
        }
        
        .status-btn.active {
            color: white;
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .status-btn i {
            font-size: 16px;
        }

    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?php echo htmlspecialchars($course['title']); ?></h2>
            <a href="courses.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-right"></i> عودة للكورسات
            </a>
        </div>

        <!-- إحصائيات الكورس -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">إحصائيات الدروس</h4>
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="border-end">
                                    <h4 class="text-primary">
                                        <span class="completed-count">
                                            <?php echo $statistics['completed_lessons']; ?> / <?php echo $statistics['total_lessons']; ?>
                                        </span>
                                    </h4>
                                    <p class="text-muted mb-0">الدروس المكتملة</p>
                                    <div class="progress mt-2" style="height: 5px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo ($statistics['total_lessons'] > 0 ? ($statistics['completed_lessons'] / $statistics['total_lessons'] * 100) : 0); ?>%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border-end">
                                    <h4 class="text-info theory-count"><?php echo $statistics['theory_lessons']; ?></h4>
                                    <p class="text-muted mb-0">الدروس النظرية</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border-end">
                                    <h4 class="text-danger important-count"><?php echo $statistics['important_lessons']; ?></h4>
                                    <p class="text-muted mb-0">الدروس المهمة</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div>
                                    <h4 class="text-success completed-duration"><?php echo formatDuration($statistics['completed_duration']); ?></h4>
                                    <p class="text-muted mb-0">الوقت المكتمل</p>
                                    <small class="text-muted">من <span class="total-duration"><?php echo formatDuration($statistics['total_duration']); ?></span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- أزرار تبديل العرض -->
        <div class="view-toggle mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary view-btn active" data-view="cards">
                        <i class="fas fa-th-large"></i> بطاقات
                    </button>
                    <button type="button" class="btn btn-outline-primary view-btn" data-view="table">
                        <i class="fas fa-list"></i> جدول
                    </button>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="hideCompletedToggle">
                    <label class="form-check-label" for="hideCompletedToggle">
                        <i class="fas fa-check-circle text-success"></i>
                        إخفاء الدروس المكتملة
                    </label>
                </div>
            </div>
        </div>

        <!-- عرض البطاقات -->
        <div class="lessons-cards">
            <div class="row">
                <?php foreach ($lessons as $lesson): ?>
                    <div class="col-md-6 mb-4 <?php echo $lesson['completed'] ? 'completed' : ''; ?>">
                        <div class="card h-100" data-lesson-id="<?php echo $lesson['id']; ?>">
                            <?php if ($lesson['thumbnail']): ?>
                                <div class="lesson-thumbnail-container" onclick='showVideo(<?php echo json_encode([
                                    "url" => $lesson['video_url'],
                                    "data" => $lesson
                                ]); ?>)'>
                                    <img src="<?php echo htmlspecialchars($lesson['thumbnail']); ?>" 
                                         class="card-img-top lesson-thumbnail" 
                                         alt="<?php echo htmlspecialchars($lesson['title']); ?>">
                                    <div class="play-overlay">
                                        <i class="fas fa-play-circle"></i>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($lesson['title']); ?></h5>
                                <!-- شارات الدرس -->
                                <div class="lesson-badges mb-3">
                                    <?php if ($lesson['is_important']): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-exclamation-circle"></i> مهم
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($lesson['is_theory']): ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-book"></i> نظري
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($lesson['completed']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i> مكتمل
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($lesson['is_reviewed']): ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-eye"></i> مراجعة
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- معلومات الدرس -->
                                <div class="lesson-meta mb-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">
                                                <i class="fas fa-layer-group"></i> القسم:
                                                <?php echo htmlspecialchars($lesson['section_name'] ?: 'غير محدد'); ?>
                                            </small>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">
                                                <i class="fas fa-tag"></i> الحالة:
                                                <span class="status-badge" style="background-color: <?php echo $lesson['status_color']; ?>; color: <?php echo $lesson['status_text_color']; ?>">
                                                    <?php echo htmlspecialchars($lesson['status_name'] ?: 'غير محدد'); ?>
                                                </span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <p class="card-text">
                                    <?php echo nl2br(htmlspecialchars(substr($lesson['description'], 0, 150)) . '...'); ?>
                                </p>
                            </div>
                            
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span></span>
                                    <button class="btn btn-primary btn-sm" 
                                            onclick='showVideo(<?php echo json_encode([
                                                "url" => $lesson['video_url'],
                                                "data" => $lesson
                                            ]); ?>)'>
                                        <i class="fas fa-play"></i> مشاهدة
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- عرض الجدول -->
        <div class="lessons-table hidden">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>عنوان الدرس</th>
                                    <th>القسم</th>
                                    <th>الحالة</th>
                                    <th>الخصائص</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lessons as $lesson): ?>
                                <tr data-lesson-id="<?php echo $lesson['id']; ?>" 
                                    class="<?php echo $lesson['completed'] ? 'completed' : ''; ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($lesson['thumbnail']); ?>" 
                                                 class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div><?php echo htmlspecialchars($lesson['title']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($lesson['section_name'] ?: 'غير محدد'); ?></td>
                                    <td>
                                        <span class="status-badge" style="background-color: <?php echo $lesson['status_color']; ?>; color: <?php echo $lesson['status_text_color']; ?>">
                                            <?php echo htmlspecialchars($lesson['status_name'] ?: 'غير محدد'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="lesson-badges">
                                            <?php if ($lesson['is_important']): ?>
                                                <span class="badge bg-danger">مهم</span>
                                            <?php endif; ?>
                                            <?php if ($lesson['is_theory']): ?>
                                                <span class="badge bg-info">نظري</span>
                                            <?php endif; ?>
                                            <?php if ($lesson['completed']): ?>
                                                <span class="badge bg-success">مكتمل</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick='showVideo(<?php echo json_encode([
                                                    "url" => $lesson['video_url'],
                                                    "data" => $lesson
                                                ]); ?>)'>
                                            <i class="fas fa-play"></i> مشاهدة
                                        </button>
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

    <!-- نافذة عرض الفيديو -->
    <div class="modal fade" id="videoModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header video-modal-header">
                    <h5 class="modal-title lesson-title"></h5>
                    <div class="navigation-buttons ms-auto me-3">
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="showPreviousLesson()">
                            <i class="fas fa-chevron-right"></i> السابق (→)
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="showNextLesson()">
                            التالي (←) <i class="fas fa-chevron-left"></i>
                        </button>
                    </div>
                    <small class="keyboard-shortcuts text-light ms-2">
                        <i class="fas fa-keyboard"></i>
                        استخدم الأسهم للتنقل
                    </small>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopVideo()"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- قسم الفيديو -->
                    <div class="video-section">
                        <div id="videoContainer" class="video-container"></div>
                    </div>
                    
                    <!-- معلومات الدرس -->
                    <div class="lesson-info-section">
                        <div class="lesson-info-header">
                            <h6 class="mb-0">معلومات الدرس</h6>
                        </div>
                        <div class="lesson-description mb-4"></div>
                        
                        <!-- أزرار التحكم -->
                        <div class="lesson-controls">
                            <div class="controls-header">
                                <h6 class="mb-3">إعدادات الدرس</h6>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">الحالة</label>
                                    <div class="statuses-toggle" id="statuses_toggle">
                                        <!-- سيتم إضافة الأزرار ديناميكياً -->
                                    </div>
                                </div>
                                <div class="col-md-12 mt-4">
                                    <label class="form-label">القسم</label>
                                    <div class="sections-toggle" id="sections_toggle">
                                        <!-- سيتم إضافة الأزرار ديناميكياً -->
                                    </div>
                                </div>
                            </div>
                            
                            <div class="lesson-switches mt-4">
                                <h6 class="mb-3">خصائص الدرس</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="form-switch material-switch">
                                            <div class="switch-container">
                                                <input type="checkbox" class="form-check-input" id="modal_theory">
                                                <label class="form-check-label" for="modal_theory">نظري</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-switch material-switch">
                                            <div class="switch-container">
                                                <input type="checkbox" class="form-check-input" id="modal_important">
                                                <label class="form-check-label" for="modal_important">مهم</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-switch material-switch">
                                            <div class="switch-container">
                                                <input type="checkbox" class="form-check-input" id="modal_completed">
                                                <label class="form-check-label" for="modal_completed">مكتمل</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-switch material-switch">
                                            <div class="switch-container">
                                                <input type="checkbox" class="form-check-input" id="modal_reviewed">
                                                <label class="form-check-label" for="modal_reviewed">مراجعة</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- أزرار الإجراءات -->
                        <div class="modal-footer mt-4">
                            <a href="#" class="btn btn-outline-primary btn-details me-2" target="_blank">
                                <i class="fas fa-external-link-alt"></i> تفاصيل الدرس
                            </a>
                            <button type="button" class="btn btn-primary" onclick="saveChanges()">
                                <i class="fas fa-save"></i> حفظ التغييرات
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- المكتبات JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
    // تعريف البيانات العامة
    const courseId = <?php echo $courseId; ?>;
    const statuses = <?php echo json_encode($statuses); ?>;
    const sections = <?php echo json_encode($sections); ?>;
    let currentLessonData = null;
    let lessonsList = <?php echo json_encode($lessons); ?>.sort((a, b) => a.id - b.id);
    let currentLessonIndex = -1;

    // دالة عرض الفيديو
    function showVideo(params) {
        try {
            const videoUrl = params.url;
            const lessonData = params.data;
            
            // تحديث مؤشر الدرس الحالي
            currentLessonIndex = lessonsList.findIndex(lesson => lesson.id === lessonData.id);
            
            const videoId = videoUrl.match(/(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/watch\?.+&v=))([\w-]{11})/);
            if (!videoId) return;

            // حفظ بيانات الدرس الحالي
            currentLessonData = lessonData;
            
            // تحديث الفيديو
            const embedUrl = `https://www.youtube.com/embed/${videoId[1]}?autoplay=1&rel=0`;
            document.getElementById('videoContainer').innerHTML = `
                <div class="ratio ratio-16x9">
                    <iframe src="${embedUrl}" allowfullscreen allow="autoplay"></iframe>
                </div>
            `;

            // تحديث معلومات الدرس
            document.querySelector('.lesson-title').textContent = lessonData.title || '';
            document.querySelector('.lesson-description').textContent = lessonData.description || '';
            
            // تحديث رابط تفاصيل الدرس
            const detailsLink = document.querySelector('.btn-details');
            if (detailsLink) {
                detailsLink.href = `http://videomx.com/content/views/lesson-details.php?id=${lessonData.id}`;
            }
            
            // تحديث حالة أزرار التنقل
            updateNavigationButtons();
            
            // تحديث القوائم المنسدلة
            updateSelects();
            
            // تحديث الخيارات
            document.querySelector('#modal_theory').checked = lessonData.is_theory == 1;
            document.querySelector('#modal_important').checked = lessonData.is_important == 1;
            document.querySelector('#modal_completed').checked = lessonData.completed == 1;
            document.querySelector('#modal_reviewed').checked = lessonData.is_reviewed == 1;
            
            // عرض الموديول
            const modal = new bootstrap.Modal(document.getElementById('videoModal'));
            modal.show();
        } catch (error) {
            console.error('Error in showVideo:', error);
            console.log('Params:', params);
        }
    }

    // دالة تحديث القوائم المنسدلة
    function updateSelects() {
        try {
            if (!currentLessonData) return;

            // تحديث أزرار الحالات
            const statusesToggle = document.querySelector('#statuses_toggle');
            if (statusesToggle) {
                statusesToggle.innerHTML = statuses.map(status => `
                    <button type="button" 
                            class="status-btn ${currentLessonData.status_id == status.id ? 'active' : ''}"
                            data-status-id="${status.id}"
                            onclick="selectStatus(${status.id})"
                            style="background-color: ${status.color}; color: ${status.text_color}; border-color: ${status.color}">
                        <i class="fas fa-tag"></i>
                        ${status.name}
                    </button>
                `).join('');
            }

            // تحديث أزرار الأقسام
            const sectionsToggle = document.querySelector('#sections_toggle');
            if (sectionsToggle) {
                sectionsToggle.innerHTML = sections.map(section => `
                    <button type="button" 
                            class="section-btn ${currentLessonData.section_id == section.id ? 'active' : ''}"
                            data-section-id="${section.id}"
                            onclick="selectSection(${section.id})">
                        <i class="fas fa-layer-group"></i>
                        ${section.name}
                    </button>
                `).join('');
            }

            // تسجيل للتحقق
            console.log('Current lesson data:', currentLessonData);
            console.log('Available statuses:', statuses);
            console.log('Available sections:', sections);
            console.log('Status select:', statusesToggle?.innerHTML);
            console.log('Section select:', sectionsToggle?.innerHTML);
        } catch (error) {
            console.error('Error in updateSelects:', error);
            console.log('Current lesson data:', currentLessonData);
        }
    }

    // دالة اختيار القسم
    function selectSection(sectionId) {
        const button = event.currentTarget;
        addClickEffect(button);
        
        const buttons = document.querySelectorAll('.section-btn');
        buttons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.sectionId == sectionId);
        });
    }

    // دالة اختيار الحالة
    function selectStatus(statusId) {
        const button = event.currentTarget;
        addClickEffect(button);
        
        const buttons = document.querySelectorAll('.status-btn');
        buttons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.statusId == statusId);
        });
    }

    // دالة إضافة تأثير النقر
    function addClickEffect(button) {
        // إزالة التأثير السابق إن وجد
        button.classList.remove('ripple');
        
        // إضافة التأثير
        const rect = button.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        const ripple = document.createElement('div');
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;
        ripple.classList.add('ripple');
        
        button.appendChild(ripple);
        
        // إزالة عنصر التأثير بعد انتهاء الأنيميشن
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    // دالة حفظ التغييرات
    function saveChanges() {
        try {
            if (!currentLessonData) {
                showToast("لا توجد بيانات للدرس", "error");
                return;
            }

            const statusesToggle = document.querySelector('#statuses_toggle');
            const sectionsToggle = document.querySelector('#sections_toggle');
            
            if (!statusesToggle || !sectionsToggle) {
                showToast("لم يتم العثور على عناصر التحكم", "error");
                console.error('Select elements not found:', { statusesToggle, sectionsToggle });
                return;
            }
            
            const activeStatus = document.querySelector('.status-btn.active');
            const statusId = activeStatus ? activeStatus.dataset.statusId : null;
            const activeSection = document.querySelector('.section-btn.active');
            const sectionId = activeSection ? activeSection.dataset.sectionId : null;

            if (!statusId) {
                showToast("يرجى اختيار الحالة", "error");
                return;
            }

            const formData = new FormData();
            formData.append('lesson_id', currentLessonData.id);
            formData.append('status_id', statusId);
            if (sectionId) {
                formData.append('section_id', sectionId);
            }
            
            // التحقق من وجود عناصر التحكم قبل استخدامها
            const theoryCheckbox = document.querySelector('#modal_theory');
            const importantCheckbox = document.querySelector('#modal_important');
            const completedCheckbox = document.querySelector('#modal_completed');
            const reviewedCheckbox = document.querySelector('#modal_reviewed');
            
            if (!theoryCheckbox || !importantCheckbox || !completedCheckbox || !reviewedCheckbox) {
                showToast("لم يتم العثور على بعض عناصر التحكم", "error");
                return;
            }
            
            formData.append('is_theory', theoryCheckbox.checked ? 1 : 0);
            formData.append('is_important', importantCheckbox.checked ? 1 : 0);
            formData.append('completed', completedCheckbox.checked ? 1 : 0);
            formData.append('is_reviewed', reviewedCheckbox.checked ? 1 : 0);

            // تسجيل البيانات المرسلة للتحقق
            console.log('Form elements:', {
                statusesToggle,
                sectionsToggle,
                theoryCheckbox,
                importantCheckbox,
                completedCheckbox,
                reviewedCheckbox
            });
            
            console.log('Sending data:', {
                lesson_id: currentLessonData.id,
                status_id: statusId,
                section_id: sectionId,
                is_theory: theoryCheckbox.checked ? 1 : 0,
                is_important: importantCheckbox.checked ? 1 : 0,
                completed: completedCheckbox.checked ? 1 : 0,
                is_reviewed: reviewedCheckbox.checked ? 1 : 0
            });

            showToast("جاري تحديث البيانات...", "info");

            fetch('update_lesson.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    throw new Error(result.message);
                }
                
                // تحديث البيانات في الواجهة
                currentLessonData = result.data;
                
                showToast("تم تحديث حالة الدرس بنجاح");
                
                // تحديث عرض البطاقة والجدول
                updateLessonDisplay(currentLessonData);
                
                // تحديث الإحصائيات
                updateStatistics();
            })
            .catch(error => {
                console.error('Error saving changes:', error);
                showToast(error.message, "error");
            });
        } catch (error) {
            console.error('Error in saveChanges:', error);
            showToast(`خطأ غير متوقع: ${error.message}`, "error");
        }
    }

    // دالة تحديث عرض الدرس في الواجهة
    function updateLessonDisplay(lessonData) {
        try {
            // تحديث البطاقة
            const card = document.querySelector(`[data-lesson-id="${lessonData.id}"]`);
            if (card) {
                // تحديث الشارات
                const badgesContainer = card.querySelector('.lesson-badges');
                if (badgesContainer) {
                    badgesContainer.innerHTML = `
                        ${lessonData.is_important ? '<span class="badge bg-danger"><i class="fas fa-exclamation-circle"></i> مهم</span>' : ''}
                        ${lessonData.is_theory ? '<span class="badge bg-info"><i class="fas fa-book"></i> نظري</span>' : ''}
                        ${lessonData.completed ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> مكتمل</span>' : ''}
                        ${lessonData.is_reviewed ? '<span class="badge bg-warning"><i class="fas fa-eye"></i> مراجعة</span>' : ''}
                    `;
                }

                // تحديث القسم
                const sectionElement = card.querySelector('.text-muted i.fas.fa-layer-group').parentElement;
                if (sectionElement) {
                    sectionElement.innerHTML = `
                        <i class="fas fa-layer-group"></i> القسم:
                        ${lessonData.section_name || 'غير محدد'}
                    `;
                }

                // تحديث الحالة
                const statusElement = card.querySelector('.status-badge');
                if (statusElement) {
                    statusElement.style.backgroundColor = lessonData.status_color;
                    statusElement.style.color = lessonData.status_text_color;
                    statusElement.textContent = lessonData.status_name || 'غير محدد';
                }
            }

            // تحديث الصف في الجدول
            const tableRow = document.querySelector(`tr[data-lesson-id="${lessonData.id}"]`);
            if (tableRow) {
                // تحديث القسم
                const sectionCell = tableRow.querySelector('td:nth-child(2)');
                if (sectionCell) {
                    sectionCell.textContent = lessonData.section_name || 'غير محدد';
                }
                
                // تحديث الحالة
                const statusBadge = tableRow.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.style.backgroundColor = lessonData.status_color;
                    statusBadge.style.color = lessonData.status_text_color;
                    statusBadge.textContent = lessonData.status_name || 'غير محدد';
                }
                
                // تحديث الشارات
                const badgesContainer = tableRow.querySelector('.lesson-badges');
                if (badgesContainer) {
                    badgesContainer.innerHTML = `
                        ${lessonData.is_important ? '<span class="badge bg-danger">مهم</span>' : ''}
                        ${lessonData.is_theory ? '<span class="badge bg-info">نظري</span>' : ''}
                        ${lessonData.completed ? '<span class="badge bg-success">مكتمل</span>' : ''}
                        ${lessonData.is_reviewed ? '<span class="badge bg-warning">مراجعة</span>' : ''}
                    `;
                }
            }

            // تحديث حالة الإخفاء
            const hideCompleted = localStorage.getItem('hideCompletedLessons') === 'true';
            if (hideCompleted && lessonData.completed == 1) {
                // إخفاء البطاقة
                const cardElement = document.querySelector(`.col-md-6 .card[data-lesson-id="${lessonData.id}"]`);
                if (cardElement) {
                    cardElement.closest('.col-md-6').classList.add('completed');
                }
                
                // إخفاء الصف
                const rowElement = document.querySelector(`tr[data-lesson-id="${lessonData.id}"]`);
                if (rowElement) {
                    rowElement.classList.add('completed');
                }
            }
        } catch (error) {
            console.error('Error updating lesson display:', error);
        }
    }

    // دالة إظهار الإشعارات
    function showToast(message, type = 'success') {
        Toastify({
            text: message,
            duration: 3000,
            gravity: "top",
            position: 'right',
            backgroundColor: type === 'success' ? "#4CAF50" : "#f44336"
        }).showToast();
    }

    // دالة إيقاف الفيديو
    function stopVideo() {
        document.getElementById('videoContainer').innerHTML = '';
        currentLessonData = null;
    }

    // انتظار تحميل المستند
    document.addEventListener('DOMContentLoaded', function() {
        // تهيئة زر إخفاء الدروس المكتملة
        initializeHideCompleted();
        
        // مستمع لإغلاق الموديول
        const videoModal = document.getElementById('videoModal');
        if (videoModal) {
            videoModal.addEventListener('hidden.bs.modal', stopVideo);
        }

        // إضافة مستمع لاختصارات لوحة المفاتيح
        document.addEventListener('keydown', function(e) {
            // التحقق من أن الموديول مفتوح
            if (document.querySelector('#videoModal').classList.contains('show')) {
                switch(e.key) {
                    case 'ArrowRight':
                        showPreviousLesson();
                        break;
                    case 'ArrowLeft':
                        showNextLesson();
                        break;
                }
            }
        });
    });

    // دالة تهيئة إخفاء الدروس المكتملة
    function initializeHideCompleted() {
        const hideCompletedToggle = document.getElementById('hideCompletedToggle');
        if (!hideCompletedToggle) return;
        
        // استرجاع الحالة المحفوظة
        const hideCompleted = localStorage.getItem('hideCompletedLessons') === 'true';
        hideCompletedToggle.checked = hideCompleted;
        
        // تطبيق الحالة الأولية
        updateCompletedLessonsVisibility(hideCompleted);
        
        // إضافة مستمع الحدث
        hideCompletedToggle.addEventListener('change', function() {
            const shouldHide = this.checked;
            localStorage.setItem('hideCompletedLessons', shouldHide);
            updateCompletedLessonsVisibility(shouldHide);
        });
    }

    // دالة تحديث ظهور الدروس المكتملة
    function updateCompletedLessonsVisibility(hide) {
        try {
            console.log('Updating visibility, hide:', hide);
            
            // تحديث البطاقات
            document.querySelectorAll('.col-md-6.mb-4').forEach(card => {
                const completedBadge = card.querySelector('.badge.bg-success');
                console.log('Card:', card, 'Has completed badge:', !!completedBadge);
                if (completedBadge) {
                    card.classList.toggle('completed', hide);
                }
            });
            
            // تحديث صفوف الجدول
            document.querySelectorAll('tr[data-lesson-id]').forEach(row => {
                const completedBadge = row.querySelector('.badge.bg-success');
                console.log('Row:', row, 'Has completed badge:', !!completedBadge);
                if (completedBadge) {
                    row.classList.toggle('completed', hide);
                }
            });
        } catch (error) {
            console.error('Error updating visibility:', error);
        }
    }

    // إظهار رسالة نجاح بعد تحديث الحالة
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        showToast("تم تحديث حالة الدرس بنجاح");
    <?php endif; ?>

    // تهيئة Select2
    $(document).ready(function() {
        $('.status-select').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });
    
    // التحكم في عرض الدروس
    document.addEventListener('DOMContentLoaded', function() {
        const viewButtons = document.querySelectorAll('.view-btn');
        const cardsView = document.querySelector('.lessons-cards');
        const tableView = document.querySelector('.lessons-table');
        
        // استرجاع العرض المحفوظ أو استخدام العرض الافتراضي
        const savedView = localStorage.getItem('lessonsView') || 'cards';
        setView(savedView);
        
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;
                setView(view);
                // حفظ اختيار العرض
                localStorage.setItem('lessonsView', view);
            });
        });
        
        function setView(view) {
            // تحديث الأزرار
            viewButtons.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            
            // تحديث العرض
            if (view === 'cards') {
                cardsView.classList.add('active');
                cardsView.classList.remove('d-none');
                tableView.classList.remove('active');
                tableView.classList.add('d-none');
            } else {
                cardsView.classList.remove('active');
                cardsView.classList.add('d-none');
                tableView.classList.add('active');
                tableView.classList.remove('d-none');
            }
        }
    });

    // دالة تحديث الإحصائيات
    function updateStatistics() {
        fetch(`get_statistics.php?course_id=${courseId}`)
            .then(response => response.json())
            .then(stats => {
                // تحديث عدد الدروس المكتملة
                document.querySelector('.completed-count').textContent = 
                    `${stats.completed_lessons} / ${stats.total_lessons}`;
                
                // تحديث شريط التقدم
                const progressBar = document.querySelector('.progress-bar');
                const percentage = (stats.total_lessons > 0) ? 
                    (stats.completed_lessons / stats.total_lessons * 100) : 0;
                progressBar.style.width = `${percentage}%`;
                
                // تحديث الدروس النظرية
                document.querySelector('.theory-count').textContent = stats.theory_lessons;
                
                // تحديث الدروس المهمة
                document.querySelector('.important-count').textContent = stats.important_lessons;
                
                // تحديث الوقت المكتمل
                const completedDuration = document.querySelector('.completed-duration');
                const totalDuration = document.querySelector('.total-duration');
                
                if (completedDuration) {
                    completedDuration.textContent = formatDuration(stats.completed_duration);
                }
                if (totalDuration) {
                    totalDuration.textContent = formatDuration(stats.total_duration);
                }
            })
            .catch(error => {
                console.error('Error updating statistics:', error);
            });
    }

    // دالة تحديث حالة أزرار التنقل
    function updateNavigationButtons() {
        const prevButton = document.querySelector('.navigation-buttons button:first-child');
        const nextButton = document.querySelector('.navigation-buttons button:last-child');
        
        if (prevButton && nextButton) {
            prevButton.disabled = currentLessonIndex <= 0;
            nextButton.disabled = currentLessonIndex >= lessonsList.length - 1;
        }
    }

    // دالة عرض الدرس السابق
    function showPreviousLesson() {
        if (currentLessonIndex > 0) {
            const previousLesson = lessonsList[currentLessonIndex - 1];
            showVideo({
                url: previousLesson.video_url,
                data: previousLesson
            });
        }
    }

    // دالة عرض الدرس التالي
    function showNextLesson() {
        if (currentLessonIndex < lessonsList.length - 1) {
            const nextLesson = lessonsList[currentLessonIndex + 1];
            showVideo({
                url: nextLesson.video_url,
                data: nextLesson
            });
        }
    }
    </script>
</body>
</html> 