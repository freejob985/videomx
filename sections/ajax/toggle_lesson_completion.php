<?php
require_once '../../includes/functions.php';
require_once '../../includes/sections_functions.php';

// التحقق من الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// التحقق من البيانات المطلوبة
$lesson_id = $_POST['lesson_id'] ?? null;
$completed = $_POST['completed'] ?? null;

if (!$lesson_id || !isset($completed)) {
    http_response_code(400);
    exit(json_encode(['error' => 'البيانات غير مكتملة']));
}

// تحديث حالة الدرس
try {
    $result = updateLessonCompletion($lesson_id, $completed == 'true');
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => $completed == 'true' ? 'تم إكمال الدرس' : 'تم إلغاء إكمال الدرس'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'فشل تحديث حالة الدرس']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 