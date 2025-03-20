<?php
/**
 * API لجلب إحصائيات الكورس
 * 
 * هذا الملف يقوم بجلب إحصائيات الكورس من قاعدة البيانات وإرجاعها بتنسيق JSON
 * 
 * المدخلات:
 * - course_id: معرف الكورس (GET)
 * 
 * المخرجات:
 * - totalLessons: إجمالي عدد الدروس
 * - completedLessons: عدد الدروس المكتملة
 * - totalDuration: إجمالي وقت الدراسة (بتنسيق HH:MM:SS)
 * - remainingDuration: وقت الدراسة المتبقي (بتنسيق HH:MM:SS)
 * - completedDuration: وقت الدراسة المكتمل (بتنسيق HH:MM:SS)
 * - progressPercentage: نسبة التقدم (0-100)
 */

// استيراد ملفات الاتصال بقاعدة البيانات والوظائف المساعدة
require_once '../includes/db.php';
require_once '../includes/functions.php';

// التحقق من وجود معرف الكورس
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'معرف الكورس مطلوب']);
    exit;
}

$courseId = (int)$_GET['course_id'];

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
    $remainingDuration = 0;
    $completedDuration = 0;
    
    foreach ($lessons as $lesson) {
        $duration = (int)$lesson['duration']; // المدة بالثواني
        $totalDuration += $duration;
        
        if ($lesson['completed']) {
            $completedLessons++;
            $completedDuration += $duration;
        } else {
            $remainingDuration += $duration;
        }
    }
    
    // حساب نسبة التقدم
    $progressPercentage = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;
    
    // تنسيق الأوقات
    $totalDurationFormatted = formatDuration($totalDuration);
    $remainingDurationFormatted = formatDuration($remainingDuration);
    $completedDurationFormatted = formatDuration($completedDuration);
    
    // إرجاع البيانات بتنسيق JSON
    echo json_encode([
        'totalLessons' => $totalLessons,
        'completedLessons' => $completedLessons,
        'totalDuration' => $totalDurationFormatted,
        'remainingDuration' => $remainingDurationFormatted,
        'completedDuration' => $completedDurationFormatted,
        'progressPercentage' => $progressPercentage
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    exit;
}

/**
 * تنسيق المدة الزمنية من ثواني إلى تنسيق HH:MM:SS
 * 
 * @param int $seconds المدة بالثواني
 * @return string المدة بتنسيق HH:MM:SS
 */
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}
?> 