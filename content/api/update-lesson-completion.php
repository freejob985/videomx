<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // قراءة البيانات المرسلة بتنسيق JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // التحقق من البيانات المطلوبة
    if (!isset($data['lesson_id']) || !isset($data['completed'])) {
        throw new Exception('البيانات المطلوبة غير متوفرة');
    }

    $lesson_id = filter_var($data['lesson_id'], FILTER_VALIDATE_INT);
    $completed = filter_var($data['completed'], FILTER_VALIDATE_BOOLEAN);

    if (!$lesson_id) {
        throw new Exception('معرف الدرس غير صالح');
    }

    $db = connectDB();
    
    // تحديث حالة اكتمال الدرس
    $stmt = $db->prepare("
        UPDATE lessons 
        SET completed = ?, 
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$completed ? 1 : 0, $lesson_id]);

    if (!$result) {
        throw new Exception('فشل تحديث حالة اكتمال الدرس');
    }

    echo json_encode([
        'success' => true,
        'message' => $completed ? 'تم تحديد الدرس كمكتمل' : 'تم إلغاء اكتمال الدرس',
        'completed' => $completed
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 