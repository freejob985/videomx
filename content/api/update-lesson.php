<?php
/**
 * API لتحديث بيانات الدرس
 * 
 * المدخلات:
 * - lesson_id: معرف الدرس (إجباري)
 * - section_id: معرف القسم (اختياري)
 * - status_id: معرف الحالة (اختياري)
 * - tags: التاجات مفصولة بفواصل (اختياري)
 * - is_theory: درس نظري (0 أو 1)
 * - is_important: درس مهم (0 أو 1)
 * 
 * المخرجات:
 * JSON response يحتوي على:
 * - success: true/false
 * - message: رسالة النجاح أو الخطأ
 * - lesson: بيانات الدرس المحدثة (في حالة النجاح)
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');

// التحقق من نوع الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'طريقة طلب غير صحيحة'
    ]);
    exit;
}

try {
    // جمع وتنظيف البيانات
    $lesson_id = filter_input(INPUT_POST, 'lesson_id', FILTER_VALIDATE_INT);
    $section_id = filter_input(INPUT_POST, 'section_id', FILTER_VALIDATE_INT);
    $status_id = filter_input(INPUT_POST, 'status_id', FILTER_VALIDATE_INT);
    $tags = trim($_POST['tags'] ?? '');
    $is_theory = isset($_POST['is_theory']) && $_POST['is_theory'] == 1 ? 1 : 0;
    $is_important = isset($_POST['is_important']) && $_POST['is_important'] == 1 ? 1 : 0;
    
    // التحقق من البيانات المطلوبة
    if (!$lesson_id) {
        throw new Exception('معرف الدرس مطلوب وغير صالح');
    }
    
    $db = connectDB();
    
    // التحقق من وجود الدرس
    $checkStmt = $db->prepare("SELECT id FROM lessons WHERE id = ?");
    $checkStmt->execute([$lesson_id]);
    if (!$checkStmt->fetch()) {
        throw new Exception('الدرس غير موجود');
    }
    
    // التحقق من وجود الحالة
    $checkStmt = $db->prepare("SELECT id FROM statuses WHERE id = ?");
    $checkStmt->execute([$status_id]);
    if (!$checkStmt->fetch()) {
        throw new Exception('الحالة غير موجودة');
    }
    
    // تحديث بيانات الدرس
    $updateStmt = $db->prepare("
        UPDATE lessons 
        SET section_id = ?,
            status_id = ?,
            tags = ?,
            is_theory = ?,
            is_important = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $updateResult = $updateStmt->execute([
        $section_id,
        $status_id,
        $tags,
        $is_theory,
        $is_important,
        $lesson_id
    ]);
    
    if (!$updateResult) {
        throw new Exception('فشل تحديث بيانات الدرس');
    }
    
    // جلب بيانات الدرس المحدثة مع معلومات الحالة
    $stmt = $db->prepare("
        SELECT 
            l.*,
            s.name as status_name,
            s.color as status_color,
            s.text_color as status_text_color,
            sec.name as section_name
        FROM lessons l
        LEFT JOIN statuses s ON l.status_id = s.id
        LEFT JOIN sections sec ON l.section_id = sec.id
        WHERE l.id = ?
    ");
    
    $stmt->execute([$lesson_id]);
    $updatedLesson = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$updatedLesson) {
        throw new Exception('فشل جلب بيانات الدرس المحدث');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث بيانات الدرس بنجاح',
        'lesson' => $updatedLesson
    ]);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 