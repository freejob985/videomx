<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$lessonId = $_POST['lesson_id'] ?? null;
$sectionName = $_POST['section_name'] ?? null;
$isCollapsed = $_POST['is_collapsed'] ?? null;

if (!$lessonId || !$sectionName) {
    echo json_encode(['error' => 'معلومات غير كاملة']);
    exit;
}

try {
    $db = connectDB();
    $sql = "INSERT INTO lesson_section_states (lesson_id, section_name, is_collapsed) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE is_collapsed = VALUES(is_collapsed)";
    
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([$lessonId, $sectionName, $isCollapsed]);
    
    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'حدث خطأ في حفظ الحالة']);
} 