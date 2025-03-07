<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "courses_db";

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
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // جلب الحالة الحالية والعنوان
    $stmt = $conn->prepare("SELECT is_important, title FROM lessons WHERE id = ?");
    $stmt->execute([$lessonId]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lesson) {
        throw new Exception('الدرس غير موجود');
    }
    
    // تبديل الحالة
    $newState = !$lesson['is_important'];
    
    // تحديث قاعدة البيانات
    $stmt = $conn->prepare("UPDATE lessons SET is_important = ?, updated_at = NOW() WHERE id = ?");
    $success = $stmt->execute([$newState, $lessonId]);
    
    if ($success) {
        // تحضير رسالة مخصصة تتضمن عنوان الدرس
        $message = $newState ? 
            sprintf('تم تحديد الدرس "%s" كدرس مهم', $lesson['title']) :
            sprintf('تم إلغاء تحديد الدرس "%s" كدرس مهم', $lesson['title']);
            
        echo json_encode([
            'success' => true,
            'message' => $message,
            'new_state' => $newState,
            'title' => $lesson['title']
        ]);
    } else {
        throw new Exception('فشل تحديث حالة الدرس');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء تحديث حالة الدرس'
    ]);
} 