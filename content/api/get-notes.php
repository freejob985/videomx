<?php
/**
 * API لجلب الملاحظات النصية للدرس
 * 
 * المدخلات:
 * - lesson_id: معرف الدرس (إجباري)
 * - type: نوع الملاحظة (text)
 * 
 * المخرجات:
 * JSON response يحتوي على:
 * - success: true/false
 * - message: رسالة النجاح أو الخطأ
 * - notes: مصفوفة تحتوي على الملاحظات
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // التحقق من المدخلات
    $lesson_id = filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT);
    $type = filter_input(INPUT_GET, 'type');
    
    if (!$lesson_id) {
        throw new Exception('معرف الدرس مطلوب');
    }
    
    if ($type !== 'text') {
        throw new Exception('نوع الملاحظة غير صالح');
    }
    
    $db = connectDB();
    
    // جلب الملاحظات النصية
    $stmt = $db->prepare("
        SELECT id, title, content, created_at
        FROM notes
        WHERE lesson_id = ? 
        AND type = 'text'
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$lesson_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'notes' => $notes
    ]);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 