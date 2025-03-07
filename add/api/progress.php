<?php
require_once '../config.php';
require_once '../helper_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo jsonResponse(false, 'طريقة الطلب غير مدعومة');
    exit;
}

try {
    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    
    if ($courseId <= 0) {
        echo jsonResponse(false, 'معرف الكورس غير صالح');
        exit;
    }

    // التحقق من وجود الكورس
    $stmt = $db->prepare('SELECT id, title FROM courses WHERE id = ?');
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();

    if (!$course) {
        echo jsonResponse(false, 'الكورس غير موجود');
        exit;
    }

    // قراءة ملف التقدم
    $progressFile = "../temp/course_{$courseId}_progress.json";
    
    if (!file_exists($progressFile)) {
        echo jsonResponse(true, 'لم تبدأ معالجة الكورس بعد', [
            'status' => 'not_started',
            'progress' => 0,
            'message' => 'لم تبدأ المعالجة بعد'
        ]);
        exit;
    }

    $progress = json_decode(file_get_contents($progressFile), true);
    
    // إضافة معلومات إضافية عن الكورس
    $progress['course'] = [
        'id' => $course['id'],
        'title' => $course['title']
    ];

    // جلب إحصائيات الدروس
    $stmt = $db->prepare('
        SELECT 
            COUNT(*) as total_lessons,
            SUM(duration) as total_duration
        FROM lessons 
        WHERE course_id = ?
    ');
    $stmt->execute([$courseId]);
    $stats = $stmt->fetch();

    $progress['stats'] = [
        'total_lessons' => (int)$stats['total_lessons'],
        'total_duration' => (int)$stats['total_duration'],
        'total_duration_formatted' => gmdate("H:i:s", $stats['total_duration'])
    ];

    // إذا اكتملت المعالجة وتم جلب الإحصائيات، نحذف ملف التقدم
    if ($progress['status'] === 'completed') {
        unlink($progressFile);
    }

    echo jsonResponse(true, 'تم جلب حالة التقدم بنجاح', $progress);

} catch (Exception $e) {
    echo jsonResponse(false, 'خطأ في جلب حالة التقدم: ' . $e->getMessage());
} 