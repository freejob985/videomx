<?php
/**
 * API لجلب إحصائيات الكورس
 * 
 * هذا الملف مسؤول عن:
 * - جلب العدد الكلي للدروس
 * - حساب الدروس المكتملة والمتبقية
 * - حساب إحصائيات الحالات
 * 
 * المدخلات:
 * - course_id: معرف الكورس (GET parameter)
 * 
 * المخرجات:
 * - JSON object يحتوي على الإحصائيات
 */

header('Content-Type: application/json');
require_once '../includes/functions.php';

// التحقق من وجود معرف الكورس
$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    echo json_encode(['error' => 'معرف الكورس مطلوب']);
    exit;
}

try {
    $db = connectDB();
    
    // جلب إحصائيات الكورس
    $stats = [
        'total' => 0,
        'completed' => 0,
        'remaining' => 0,
        'important' => 0,
        'theory' => 0,
        'total_duration' => 0,
        'completed_duration' => 0,
        'remaining_duration' => 0,
        'status_counts' => [],
        'completion_percentage' => 0
    ];

    // استعلام لجلب كافة الإحصائيات
    $query = "SELECT 
        COUNT(*) as total,
        SUM(completed = 1) as completed,
        SUM(is_important = 1) as important,
        SUM(is_theory = 1) as theory,
        SUM(duration) as total_duration,
        SUM(CASE WHEN completed = 1 THEN duration ELSE 0 END) as completed_duration
    FROM lessons 
    WHERE course_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$course_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // تحديث الإحصائيات
    $stats['total'] = (int)$result['total'];
    $stats['completed'] = (int)$result['completed'];
    $stats['remaining'] = $stats['total'] - $stats['completed'];
    $stats['important'] = (int)$result['important'];
    $stats['theory'] = (int)$result['theory'];
    $stats['total_duration'] = (int)$result['total_duration'];
    $stats['completed_duration'] = (int)$result['completed_duration'];
    $stats['remaining_duration'] = $stats['total_duration'] - $stats['completed_duration'];
    
    // حساب النسبة المئوية للإكمال
    $stats['completion_percentage'] = $stats['total'] > 0 
        ? round(($stats['completed'] / $stats['total']) * 100) 
        : 0;

    // جلب إحصائيات الحالات
    $status_query = "SELECT 
        s.id, 
        s.name, 
        COUNT(l.id) as count 
    FROM statuses s 
    LEFT JOIN lessons l ON l.status_id = s.id AND l.course_id = ?
    GROUP BY s.id";
    
    $stmt = $db->prepare($status_query);
    $stmt->execute([$course_id]);
    $stats['status_counts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تحسين التحقق من صحة الأرقام
    $validation = [
        'total_matches' => $stats['total'] === ($stats['completed'] + $stats['remaining']),
        'completed_valid' => $stats['completed'] >= 0,
        'remaining_valid' => $stats['remaining'] >= 0,
        'duration_matches' => $stats['total_duration'] === ($stats['completed_duration'] + $stats['remaining_duration'])
    ];
    
    if (!$validation['total_matches'] || !$validation['duration_matches']) {
        error_log("Stats validation failed for course {$course_id}: Data integrity check failed");
        // إضافة تفاصيل الخطأ للتسجيل
        error_log(json_encode([
            'total' => $stats['total'],
            'completed' => $stats['completed'],
            'remaining' => $stats['remaining'],
            'total_duration' => $stats['total_duration'],
            'completed_duration' => $stats['completed_duration'],
            'remaining_duration' => $stats['remaining_duration']
        ]));
    }
    
    // إضافة معلومات إضافية للتتبع
    $stats['timestamp'] = date('Y-m-d H:i:s');
    $stats['validation'] = $validation;
    $stats['query_info'] = [
        'course_id' => $course_id,
        'server_time' => time()
    ];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-course-stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'حدث خطأ أثناء جلب الإحصائيات',
        'details' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} 