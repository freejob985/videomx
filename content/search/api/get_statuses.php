<?php
// إنشاء اتصال مباشر بقاعدة البيانات
$conn = new mysqli('localhost', 'root', '', 'courses_db');
$conn->set_charset('utf8mb4');

// التحقق من الاتصال
if ($conn->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error
    ]));
}

header('Content-Type: application/json; charset=utf-8');

try {
    // التحقق من وجود معرف اللغة
    $languageId = isset($_GET['language_id']) ? intval($_GET['language_id']) : null;

    if ($languageId) {
        // استعلام لجلب الحالات الخاصة باللغة المحددة
        $query = "SELECT DISTINCT s.id, s.name 
                  FROM statuses s 
                  WHERE s.language_id = ? 
                  ORDER BY s.name";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $languageId);
    } else {
        // استعلام لجلب كل الحالات إذا لم يتم تحديد لغة
        $query = "SELECT id, name FROM statuses ORDER BY name";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $statuses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // إرجاع النتائج
    echo json_encode([
        'status' => 'success',
        'data' => $statuses
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'خطأ في النظام: ' . $e->getMessage()
    ]);
}
?> 