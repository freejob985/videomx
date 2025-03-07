<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

$course_id = $_GET['course_id'] ?? null;
$type = $_GET['type'] ?? 'all';

if (!$course_id) {
    echo json_encode([
        'success' => false,
        'message' => 'معرف الكورس مطلوب'
    ]);
    exit;
}

try {
    $details = getFormattedCourseDetails($course_id, $type);
    
    echo json_encode([
        'success' => true,
        'text' => $details['text'],
        'course' => $details['course'],
        'lessons' => $details['lessons']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 