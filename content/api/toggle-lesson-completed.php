<?php
/**
 * API لتبديل حالة اكتمال الدرس
 * 
 * المدخلات:
 * - lesson_id: معرف الدرس (إجباري)
 * - completed: الحالة الجديدة (0 أو 1)
 * 
 * المخرجات:
 * JSON response يحتوي على:
 * - success: true/false
 * - message: رسالة النجاح أو الخطأ
 * - completed: الحالة الجديدة
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // التحقق من نوع الطلب
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // قراءة البيانات المرسلة
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['lesson_id'])) {
        throw new Exception('معرف الدرس مطلوب');
    }

    $lesson_id = (int)$data['lesson_id'];
    $completed = isset($data['completed']) ? (int)$data['completed'] : null;

    if ($completed === null || !in_array($completed, [0, 1])) {
        throw new Exception('قيمة completed غير صالحة');
    }

    $db = connectDB();
    
    // التحقق من وجود الدرس
    $stmt = $db->prepare("SELECT title, completed FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lesson) {
        throw new Exception('الدرس غير موجود');
    }

    // تحديث حالة الاكتمال
    $stmt = $db->prepare("
        UPDATE lessons 
        SET completed = ?,
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    
    $success = $stmt->execute([$completed, $lesson_id]);

    if (!$success) {
        throw new Exception('فشل تحديث حالة الدرس');
    }

    $message = $completed ? 
        sprintf('تم تحديد الدرس "%s" كمكتمل', $lesson['title']) :
        sprintf('تم إلغاء تحديد الدرس "%s" كمكتمل', $lesson['title']);

    echo json_encode([
        'success' => true,
        'message' => $message,
        'completed' => $completed
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 