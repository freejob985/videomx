<?php
/**
 * API لجلب إحصائيات الكورس
 * 
 * هذا الملف يقوم بجلب إحصائيات الكورس من قاعدة البيانات وإرجاعها بتنسيق JSON
 * 
 * المدخلات:
 * - action: نوع العملية (get_stats, get_lessons)
 * - course_id: معرف الكورس
 * - show_completed: عرض الدروس المكتملة (للعملية get_lessons)
 * 
 * المخرجات:
 * للعملية get_stats:
 * - total_lessons: إجمالي عدد الدروس
 * - completed_lessons: عدد الدروس المكتملة
 * - total_duration: إجمالي وقت الدراسة (بالدقائق)
 * - completed_duration: وقت الدراسة المكتمل (بالدقائق)
 * - completion_percentage: نسبة التقدم (0-100)
 * 
 * للعملية get_lessons:
 * - مصفوفة من الدروس مع معلوماتها
 */

// تعيين نوع المحتوى إلى JSON
header('Content-Type: application/json');

// اتصال مباشر بقاعدة البيانات
$host = 'localhost';
$dbname = 'courses_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage()]);
    exit;
}

// التحقق من وجود العملية ومعرف الكورس
if (!isset($_POST['action']) || !isset($_POST['course_id']) || !is_numeric($_POST['course_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'معلومات غير كاملة']);
    exit;
}

$action = $_POST['action'];
$courseId = (int)$_POST['course_id'];

// التعامل مع العمليات المختلفة
switch ($action) {
    case 'get_stats':
        getStats($courseId);
        break;
    case 'get_lessons':
        $showCompleted = isset($_POST['show_completed']) ? filter_var($_POST['show_completed'], FILTER_VALIDATE_BOOLEAN) : true;
        getLessons($courseId, $showCompleted);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'عملية غير صالحة']);
        exit;
}

/**
 * جلب إحصائيات الكورس
 * 
 * @param int $courseId معرف الكورس
 * @return void
 */
function getStats($courseId) {
    global $pdo;
    
    try {
        // جلب جميع دروس الكورس
        $stmt = $pdo->prepare("
            SELECT id, title, duration, completed
            FROM lessons
            WHERE course_id = ?
            ORDER BY order_number ASC
        ");
        $stmt->execute([$courseId]);
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // حساب الإحصائيات
        $totalLessons = count($lessons);
        $completedLessons = 0;
        $totalDuration = 0;
        $completedDuration = 0;
        
        foreach ($lessons as $lesson) {
            $duration = (int)$lesson['duration']; // المدة بالثواني
            $totalDuration += $duration;
            
            if ($lesson['completed']) {
                $completedLessons++;
                $completedDuration += $duration;
            }
        }
        
        // تحويل الثواني إلى دقائق للعرض
        $totalDurationMinutes = $totalDuration / 60;
        $completedDurationMinutes = $completedDuration / 60;
        
        // حساب نسبة التقدم
        $completionPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
        
        // إرجاع البيانات بتنسيق JSON
        echo json_encode([
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'total_duration' => $totalDurationMinutes,
            'completed_duration' => $completedDurationMinutes,
            'completion_percentage' => $completionPercentage
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * جلب قائمة دروس الكورس
 * 
 * @param int $courseId معرف الكورس
 * @param bool $showCompleted عرض الدروس المكتملة
 * @return void
 */
function getLessons($courseId, $showCompleted) {
    global $pdo;
    
    try {
        // بناء الاستعلام
        $query = "
            SELECT l.id, l.title, l.duration, l.completed, l.is_reviewed, l.is_important, l.is_theory,
                   s.name as section_name
            FROM lessons l
            LEFT JOIN sections s ON l.section_id = s.id
            WHERE l.course_id = ?
        ";
        
        // إضافة شرط تصفية الدروس المكتملة إذا لزم الأمر
        if (!$showCompleted) {
            $query .= " AND l.completed = 0";
        }
        
        $query .= " ORDER BY l.order_number ASC, l.id ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$courseId]);
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // إرجاع البيانات بتنسيق JSON
        echo json_encode($lessons);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        exit;
    }
}
?> 