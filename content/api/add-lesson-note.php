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
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$type = 'text'; // نوع ثابت للملاحظات النصية

if (!$lesson_id || !$title || !$content) {
    echo json_encode(['success' => false, 'error' => 'جميع الحقول مطلوبة']);
    exit;
}

try {
    $pdo = getPDO();
    
    // التحقق من وجود الدرس
    $checkStmt = $pdo->prepare("SELECT id FROM lessons WHERE id = ?");
    $checkStmt->execute([$lesson_id]);
    
    if (!$checkStmt->fetch()) {
        throw new Exception('الدرس غير موجود');
    }
    
    // إضافة الملاحظة في قاعدة البيانات
    $stmt = $pdo->prepare('
        INSERT INTO notes (
            lesson_id, 
            type, 
            title, 
            content, 
            created_at, 
            updated_at
        ) VALUES (
            :lesson_id,
            :type,
            :title,
            :content,
            NOW(),
            NOW()
        )
    ');
    
    $success = $stmt->execute([
        ':lesson_id' => $lesson_id,
        ':type' => $type,
        ':title' => $title,
        ':content' => $content
    ]);

    if ($success) {
        $noteId = $pdo->lastInsertId();
        
        // جلب الملاحظة المضافة
        $noteStmt = $pdo->prepare("
            SELECT * FROM notes 
            WHERE id = ?
        ");
        $noteStmt->execute([$noteId]);
        $note = $noteStmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'تمت إضافة الملاحظة بنجاح',
            'noteId' => $noteId,
            'note' => $note
        ]);
    } else {
        throw new Exception('فشل إضافة الملاحظة');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'حدث خطأ أثناء إضافة الملاحظة: ' . $e->getMessage()
    ]);
} 