<?php
/**
 * API لتحديث حالة الدرس
 * 
 * المدخلات:
 * - lesson_id: معرف الدرس (إجباري)
 * - status_id: معرف الحالة (إجباري)
 * 
 * المخرجات:
 * JSON response يحتوي على:
 * - success: true/false
 * - message: رسالة النجاح أو الخطأ
 * - status: معلومات الحالة الجديدة
 * - completed: حالة اكتمال الدرس
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // قراءة البيانات المرسلة بتنسيق JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // التحقق من البيانات المطلوبة
    if (!isset($data['lesson_id']) || !isset($data['status_id'])) {
        throw new Exception('البيانات المطلوبة غير متوفرة');
    }

    $lesson_id = filter_var($data['lesson_id'], FILTER_VALIDATE_INT);
    $status_id = filter_var($data['status_id'], FILTER_VALIDATE_INT);

    if (!$lesson_id || !$status_id) {
        throw new Exception('قيم غير صالحة');
    }

    $db = connectDB();
    
    // التحقق من وجود الحالة
    $stmt = $db->prepare("SELECT id, name, color, text_color FROM statuses WHERE id = ?");
    $stmt->execute([$status_id]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$status) {
        throw new Exception('الحالة غير موجودة');
    }

    // تحديد ما إذا كانت الحالة تعني اكتمال الدرس
    $completed = (strtolower($status['name']) === 'مكتمل' || 
                 strtolower($status['name']) === 'completed') ? 1 : 0;

    // تحديث حالة الدرس مع تحديث حقل completed
    $stmt = $db->prepare("
        UPDATE lessons 
        SET status_id = ?,
            completed = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$status_id, $completed, $lesson_id]);

    if (!$result) {
        throw new Exception('فشل تحديث حالة الدرس');
    }

    // تحضير رسالة النجاح
    $message = 'تم تحديث حالة الدرس بنجاح';
    if ($completed) {
        $message .= ' وتم تحديده كمكتمل';
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'status' => $status,
        'completed' => $completed
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 