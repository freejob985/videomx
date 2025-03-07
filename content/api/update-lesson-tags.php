<?php
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// التحقق من الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'طريقة طلب غير صالحة']);
    exit;
}

// التحقق من البيانات المطلوبة
$lesson_id = $_POST['lesson_id'] ?? null;
$tags = $_POST['tags'] ?? '';

if (!$lesson_id) {
    echo json_encode(['success' => false, 'error' => 'معرف الدرس مطلوب']);
    exit;
}

try {
    $db = connectDB();
    
    // التحقق من وجود الدرس
    $checkStmt = $db->prepare("SELECT id, title FROM lessons WHERE id = ?");
    $checkStmt->execute([$lesson_id]);
    $lesson = $checkStmt->fetch();
    
    if (!$lesson) {
        throw new Exception('الدرس غير موجود');
    }
    
    // تنظيف وتنسيق التاجات
    $tagsArray = array_map('trim', explode(',', $tags));
    $tagsArray = array_filter($tagsArray); // إزالة القيم الفارغة
    $tagsArray = array_unique($tagsArray); // إزالة التكرار
    $formattedTags = implode(', ', $tagsArray);
    
    // تحديث التاجات في قاعدة البيانات
    $stmt = $db->prepare('
        UPDATE lessons 
        SET tags = ?, 
            updated_at = NOW() 
        WHERE id = ?
    ');
    
    $success = $stmt->execute([$formattedTags, $lesson_id]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث التاجات بنجاح',
            'lessonId' => $lesson_id,
            'tags' => $formattedTags,
            'lesson_title' => $lesson['title']
        ]);
    } else {
        throw new Exception('فشل تحديث التاجات');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'حدث خطأ أثناء تحديث التاجات: ' . $e->getMessage()
    ]);
} 