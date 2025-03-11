<?php
// إعداد الاتصال بقاعدة البيانات
$host = 'localhost';
$dbname = 'courses_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات']);
    exit;
}

// استلام البيانات
$data = json_decode(file_get_contents('php://input'), true);
$courseId = $data['course_id'] ?? null;

if (!$courseId) {
    echo json_encode(['success' => false, 'message' => 'معرف الكورس مطلوب']);
    exit;
}

try {
    // التحقق مما إذا كان الكورس مفضلاً بالفعل
    $checkStmt = $pdo->prepare("SELECT id FROM favorites WHERE course_id = ?");
    $checkStmt->execute([$courseId]);
    $favorite = $checkStmt->fetch();

    if ($favorite) {
        // إزالة من المفضلة
        $deleteStmt = $pdo->prepare("DELETE FROM favorites WHERE course_id = ?");
        $deleteStmt->execute([$courseId]);
        echo json_encode(['success' => true, 'is_favorite' => false]);
    } else {
        // إضافة إلى المفضلة
        $insertStmt = $pdo->prepare("INSERT INTO favorites (course_id) VALUES (?)");
        $insertStmt->execute([$courseId]);
        echo json_encode(['success' => true, 'is_favorite' => true]);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث المفضلة']);
} 