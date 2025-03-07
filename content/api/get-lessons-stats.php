<?php
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    echo json_encode([
        'success' => false,
        'message' => 'معرف الكورس مطلوب'
    ]);
    exit;
}

try {
    $stats = getCourseLessonsStats($course_id);
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في جلب الإحصائيات'
    ]);
} 