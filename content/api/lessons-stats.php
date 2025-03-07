<?php
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
    $stats = getUpdatedLessonsStats($course_id);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 