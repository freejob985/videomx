<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

if (!isset($_GET['lesson_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'معرف الدرس مطلوب']);
    exit;
}

try {
    $lesson_id = intval($_GET['lesson_id']);
    
    // استعلام للحصول على تفاصيل الدرس مع معرف اللغة
    $query = "SELECT l.*, c.language_id 
              FROM lessons l 
              LEFT JOIN courses c ON l.course_id = c.id 
              WHERE l.id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $lesson_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // تحويل البيانات النصية إلى مصفوفات إذا كانت موجودة
        if ($row['tags']) {
            $row['tags'] = explode(',', $row['tags']);
        }
        
        echo json_encode([
            'success' => true,
            'lesson' => $row
        ]);
    } else {
        throw new Exception('الدرس غير موجود');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();

/**
 * Helper function to format duration
 */
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
}
?> 