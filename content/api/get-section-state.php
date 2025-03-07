<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$lessonId = $_GET['lesson_id'] ?? null;
$sectionName = $_GET['section_name'] ?? null;

if (!$lessonId || !$sectionName) {
    echo json_encode(['error' => 'معلومات غير كاملة']);
    exit;
}

try {
    $db = connectDB();
    $sql = "SELECT is_collapsed FROM lesson_section_states 
            WHERE lesson_id = ? AND section_name = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$lessonId, $sectionName]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'is_collapsed' => (bool)($result['is_collapsed'] ?? false)
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'حدث خطأ في استرجاع الحالة']);
} 