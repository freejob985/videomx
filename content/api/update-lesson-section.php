<?php
/**
 * API لتحديث قسم الدرس
 * 
 * المدخلات:
 * - lesson_id: معرف الدرس (إجباري)
 * - section_id: معرف القسم (إجباري)
 * 
 * المخرجات:
 * JSON response يحتوي على:
 * - success: true/false
 * - message: رسالة النجاح أو الخطأ
 * - lesson: بيانات الدرس المحدثة (في حالة النجاح)
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // التحقق من نوع الطلب
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('طريقة طلب غير صحيحة');
    }

    // جمع وتنظيف البيانات
    $data = json_decode(file_get_contents('php://input'), true);
    
    $lesson_id = isset($data['lesson_id']) ? (int)$data['lesson_id'] : null;
    $section_id = isset($data['section_id']) ? (int)$data['section_id'] : null;

    // التحقق من البيانات المطلوبة
    if (!$lesson_id) {
        throw new Exception('معرف الدرس مطلوب');
    }

    if (!$section_id) {
        throw new Exception('معرف القسم مطلوب');
    }

    $db = connectDB();
    
    // التحقق من وجود الدرس
    $stmt = $db->prepare("SELECT id FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    if (!$stmt->fetch()) {
        throw new Exception('الدرس غير موجود');
    }

    // التحقق من وجود القسم
    $stmt = $db->prepare("SELECT id, name FROM sections WHERE id = ?");
    $stmt->execute([$section_id]);
    $section = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$section) {
        throw new Exception('القسم غير موجود');
    }

    // تحديث قسم الدرس
    $stmt = $db->prepare("
        UPDATE lessons 
        SET section_id = ?,
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    
    $success = $stmt->execute([$section_id, $lesson_id]);

    if (!$success) {
        throw new Exception('فشل تحديث قسم الدرس');
    }

    // جلب بيانات الدرس المحدثة
    $stmt = $db->prepare("
        SELECT 
            l.*,
            s.name as section_name
        FROM lessons l
        LEFT JOIN sections s ON l.section_id = s.id
        WHERE l.id = ?
    ");
    
    $stmt->execute([$lesson_id]);
    $updatedLesson = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث قسم الدرس بنجاح',
        'lesson' => $updatedLesson,
        'section' => $section
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 