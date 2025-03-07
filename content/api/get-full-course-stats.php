<?php
require_once '../includes/functions.php';

$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    echo json_encode([
        'success' => false,
        'error' => 'معرف الكورس مطلوب'
    ]);
    exit;
}

$stats = getFullCourseStats($course_id);

echo json_encode([
    'success' => true,
    'stats' => $stats
]); 