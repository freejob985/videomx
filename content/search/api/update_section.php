<?php
// التحقق من أن الطلب هو POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'طريقة الطلب غير مسموح بها']);
    exit;
}

require_once '../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من وجود البيانات المطلوبة
    if (!isset($data['lesson_id']) || !isset($data['section_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'البيانات المطلوبة غير مكتملة']);
        exit;
    }
    
    $lesson_id = intval($data['lesson_id']);
    $section_id = intval($data['section_id']);
    
    // تحديث القسم للدرس
    $query = "UPDATE lessons SET section_id = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $section_id, $lesson_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث القسم بنجاح'
        ]);
    } else {
        throw new Exception('فشل تحديث القسم');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?> 