<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// معلومات الاتصال بقاعدة البيانات
$host = 'localhost';
$dbname = 'courses_db';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

try {
    // إنشاء اتصال PDO مباشر
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);

    // التحقق من أن الطلب POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('طريقة طلب غير صحيحة');
    }

    // قراءة البيانات المرسلة
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['lesson_id']) || !isset($data['is_reviewed'])) {
        throw new Exception('البيانات المطلوبة غير مكتملة');
    }

    $lesson_id = (int)$data['lesson_id'];
    $is_reviewed = (int)$data['is_reviewed'];

    // تحديث حالة المراجعة في قاعدة البيانات
    $stmt = $pdo->prepare("UPDATE lessons SET is_reviewed = ? WHERE id = ?");
    $result = $stmt->execute([$is_reviewed, $lesson_id]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('فشل تحديث حالة المراجعة');
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'خطأ في الاتصال بقاعدة البيانات'
    ]);
} 