<?php
/**
 * API لإضافة ملاحظة جديدة
 * 
 * المدخلات المطلوبة:
 * - lesson_id: معرف الدرس
 * - type: نوع الملاحظة (text/code/link)
 * - title: عنوان الملاحظة
 * - content: محتوى الملاحظة
 * - code_language: لغة البرمجة (مطلوب فقط لنوع code)
 * - link_url: الرابط (مطلوب فقط لنوع link)
 */

// تعريف المتغير للإشارة إلى أن هذا ملف API
$isApi = true;

// الاتصال المباشر بقاعدة البيانات
$dsn = 'mysql:host=localhost;dbname=courses_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection error: ' . $e->getMessage()
    ]);
    exit;
}

// التحقق من الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// استلام البيانات
$data = json_decode(file_get_contents('php://input'), true);

// التحقق من الحقول المطلوبة
$requiredFields = ['lesson_id', 'type', 'title', 'content'];
$missingFields = array_filter($requiredFields, function($field) use ($data) {
    return empty($data[$field]);
});

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]);
    exit;
}

// التحقق من النوع والحقول الإضافية
switch ($data['type']) {
    case 'code':
        if (empty($data['code_language'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'code_language is required for code notes'
            ]);
            exit;
        }
        break;
    case 'link':
        if (empty($data['link_url'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'link_url is required for link notes'
            ]);
            exit;
        }
        break;
}

try {
    // إعداد الاستعلام
    $sql = "INSERT INTO notes (lesson_id, type, title, content, code_language, link_url, created_at) 
            VALUES (:lesson_id, :type, :title, :content, :code_language, :link_url, NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    // تنفيذ الاستعلام
    $result = $stmt->execute([
        'lesson_id' => $data['lesson_id'],
        'type' => $data['type'],
        'title' => $data['title'],
        'content' => $data['content'],
        'code_language' => $data['type'] === 'code' ? $data['code_language'] : null,
        'link_url' => $data['type'] === 'link' ? $data['link_url'] : null
    ]);
    
    if ($result) {
        // جلب الملاحظة المضافة
        $noteId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
        $stmt->execute([$noteId]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Note added successfully',
            'note' => $note
        ]);
    } else {
        throw new Exception('Failed to add note');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} 