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
            ORDER BY l.order_number";
    
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
    
    $stats = [
        'total_lessons' => 0,
        'completed_lessons' => 0,
        'theory_lessons' => 0,
        'important_lessons' => 0,
        'total_duration' => 0,
        'completed_duration' => 0
    ];
    
    $sql = "SELECT 
            COUNT(*) as total_lessons,
            SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_lessons,
            SUM(CASE WHEN is_theory = 1 THEN 1 ELSE 0 END) as theory_lessons,
            SUM(CASE WHEN is_important = 1 THEN 1 ELSE 0 END) as important_lessons,
            SUM(duration) as total_duration,
            SUM(CASE WHEN completed = 1 THEN duration ELSE 0 END) as completed_duration
            FROM lessons 
            WHERE course_id = ? AND is_reviewed = 1";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stats = array_merge($stats, $row);
    }
    
    return $stats;
}

/**
 * تحويل الوقت من دقائق إلى ساعات ودقائق
 * @param int $minutes عدد الدقائق
 * @return string الوقت بتنسيق ساعات ودقائق
 */
function formatDuration($minutes) {
    if (!$minutes) return "0 دقيقة";
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($hours > 0) {
        return sprintf("%d ساعة و %d دقيقة", $hours, $mins);
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
                                    <h4 class="text-primary"><?php echo $statistics['completed_lessons']; ?> / <?php echo $statistics['total_lessons']; ?></h4>
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
                                    <h4 class="text-info"><?php echo $statistics['theory_lessons']; ?></h4>
                                    <p class="text-muted mb-0">الدروس النظرية</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border-end">
                                    <h4 class="text-danger"><?php echo $statistics['important_lessons']; ?></h4>
                                    <p class="text-muted mb-0">الدروس المهمة</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div>
                                    <h4 class="text-success"><?php echo formatDuration($statistics['completed_duration']); ?></h4>
                                    <p class="text-muted mb-0">الوقت المكتمل</p>
                                    <small class="text-muted">من <?php echo formatDuration($statistics['total_duration']); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- أزرار تبديل العرض -->
        <div class="view-toggle mb-4">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary view-btn active" data-view="cards">
                    <i class="fas fa-th-large"></i> بطاقات
                </button>
                <button type="button" class="btn btn-outline-primary view-btn" data-view="table">
                    <i class="fas fa-list"></i> جدول
                </button>
            </div>
        </div>

        <!-- عرض البطاقات -->
        <div class="lessons-cards">
            <div class="row">
                <?php foreach ($lessons as $lesson): ?>
                    <div class="col-md-6 mb-4">
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
                                    <span class="text-muted">
                                        <i class="far fa-clock"></i> 
                                        <?php echo $lesson['duration']; ?> دقيقة
                                    </span>
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
                                    <th>المدة</th>
                                    <th>الخصائص</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lessons as $lesson): ?>
                                <tr data-lesson-id="<?php echo $lesson['id']; ?>">
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
                                    <td><?php echo $lesson['duration']; ?> دقيقة</td>
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
                                <div class="col-md-6">
                                    <label class="form-label">الحالة</label>
                                    <div class="status-section">
                                        <select name="status_id" class="form-select custom-select status-select">
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">القسم</label>
                                    <div class="section-select">
                                        <select name="section_id" class="form-select custom-select section-select">
                                        </select>
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
    // تعريف المتغيرات العامة
    let currentLessonData = null;

    // دالة عرض الفيديو
    function showVideo(params) {
        try {
            const videoUrl = params.url;
            const lessonData = params.data;
            
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
            
            // تحديث القوائم المنسدلة
            updateSelects();
            
            // تحديث الخيارات
            document.querySelector('#modal_theory').checked = lessonData.is_theory == 1;
            document.querySelector('#modal_important').checked = lessonData.is_important == 1;
            document.querySelector('#modal_completed').checked = lessonData.completed == 1;
            document.querySelector('#modal_reviewed').checked = lessonData.is_reviewed == 1;
            
            // تحديث رابط التفاصيل
            const detailsLink = document.querySelector('.btn-details');
            if (detailsLink) {
                detailsLink.href = `http://videomx.com/content/views/lesson-details.php?id=${lessonData.id}`;
            }

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

            // تحديث قائمة الحالات
            const statusSelect = document.querySelector('.status-select');
            if (statusSelect) {
                statusSelect.innerHTML = `
                    <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo $status['id']; ?>" 
                            ${currentLessonData.status_id == <?php echo $status['id']; ?> ? 'selected' : ''}>
                        <?php echo addslashes($status['name']); ?>
                    </option>
                    <?php endforeach; ?>
                `;
            }

            // تحديث قائمة الأقسام
            const sectionSelect = document.querySelector('.section-select');
            if (sectionSelect) {
                sectionSelect.innerHTML = `
                    <option value="">اختر القسم</option>
                    <?php foreach ($sections as $section): ?>
                    <option value="<?php echo $section['id']; ?>" 
                            ${currentLessonData.section_id == <?php echo $section['id']; ?> ? 'selected' : ''}>
                        <?php echo addslashes($section['name']); ?>
                    </option>
                    <?php endforeach; ?>
                `;
            }
        } catch (error) {
            console.error('Error in updateSelects:', error);
        }
    }

    // دالة حفظ التغييرات
    function saveChanges() {
        try {
            if (!currentLessonData) {
                showToast("لا توجد بيانات للدرس", "error");
                return;
            }

            const statusId = document.querySelector('.status-select').value;
            const sectionId = document.querySelector('.section-select').value;
            
            if (!statusId) {
                showToast("يرجى اختيار الحالة", "error");
                return;
            }

            const formData = new FormData();
            formData.append('lesson_id', currentLessonData.id);
            formData.append('status_id', statusId);
            formData.append('section_id', sectionId || '');
            formData.append('is_theory', document.querySelector('#modal_theory').checked ? 1 : 0);
            formData.append('is_important', document.querySelector('#modal_important').checked ? 1 : 0);
            formData.append('completed', document.querySelector('#modal_completed').checked ? 1 : 0);
            formData.append('is_reviewed', document.querySelector('#modal_reviewed').checked ? 1 : 0);

            // إضافة تسجيل للبيانات المرسلة
            console.log('Sending data:', {
                lesson_id: currentLessonData.id,
                status_id: statusId,
                section_id: sectionId || '',
                is_theory: document.querySelector('#modal_theory').checked ? 1 : 0,
                is_important: document.querySelector('#modal_important').checked ? 1 : 0,
                completed: document.querySelector('#modal_completed').checked ? 1 : 0,
                is_reviewed: document.querySelector('#modal_reviewed').checked ? 1 : 0
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
                updateLessonDisplay(currentLessonData);
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

                // تحديث القسم والحالة
                const sectionElement = card.querySelector('.section-name');
                if (sectionElement) {
                    const selectedSection = document.querySelector(`.section-select option[value="${lessonData.section_id}"]`);
                    sectionElement.textContent = selectedSection ? selectedSection.textContent : 'غير محدد';
                }

                const statusElement = card.querySelector('.status-badge');
                if (statusElement) {
                    const selectedStatus = document.querySelector(`.status-select option[value="${lessonData.status_id}"]`);
                    statusElement.textContent = selectedStatus ? selectedStatus.textContent : 'غير محدد';
                }
            }

            // تحديث الصف في الجدول
            const tableRow = document.querySelector(`tr[data-lesson-id="${lessonData.id}"]`);
            if (tableRow) {
                // تحديث نفس العناصر في الجدول
                // ... تحديث عناصر الجدول المماثلة
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

    // إضافة مستمعات الأحداث
    document.addEventListener('DOMContentLoaded', function() {
        // مستمع لإغلاق الموديول
        const videoModal = document.getElementById('videoModal');
        if (videoModal) {
            videoModal.addEventListener('hidden.bs.modal', stopVideo);
        }
    });

    // إظهار رسالة نجاح بعد تحديث الحالة
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        showToast("تم تحديث حالة الدرس بنجاح");
    <?php endif; ?>

    // تهيئة Select2
    $(document).ready(function() {
        $('.status-select, .section-select').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });
    
    // التحكم في عرض الدروس
    document.addEventListener('DOMContentLoaded', function() {
        const viewButtons = document.querySelectorAll('.view-btn');
        const cardsView = document.querySelector('.lessons-cards');
        const tableView = document.querySelector('.lessons-table');
        
        // تعيين العرض الافتراضي
        setView('cards');
        
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;
                setView(view);
            });
        });
        
        function setView(view) {
            viewButtons.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            
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
    </script>
</body>
</html> 