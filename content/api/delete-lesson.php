<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// التحقق من الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// استلام البيانات
$data = json_decode(file_get_contents('php://input'), true);
$lessonId = $data['lesson_id'] ?? null;

if (!$lessonId) {
    echo json_encode(['success' => false, 'message' => 'معرف الدرس مطلوب']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    // حذف الدرس
    $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
    $success = $stmt->execute([$lessonId]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف الدرس بنجاح'
        ]);
    } else {
        throw new Exception('فشل حذف الدرس');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء حذف الدرس'
    ]);
} 