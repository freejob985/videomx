<?php
require_once 'config/database.php';

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$courseId) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'معرف الكورس مطلوب']);
    exit;
}

try {
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
    $stats = $result->fetch_assoc();
    
    // تحويل القيم الفارغة إلى 0
    foreach ($stats as $key => $value) {
        $stats[$key] = $value ?? 0;
    }
    
    header('Content-Type: application/json');
    echo json_encode($stats);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
} 