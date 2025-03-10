<?php
// التأكد من وجود معرف اللغة في الطلب
if (!isset($_GET['language_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'معرف اللغة مطلوب']);
    exit;
}

require_once '../config/database.php';

try {
    $language_id = intval($_GET['language_id']);
    
    // استعلام للحصول على الأقسام الخاصة باللغة المحددة
    $query = "SELECT id, name FROM sections WHERE language_id = ? ORDER BY name ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $language_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
    
    // التحقق من وجود أقسام
    if (empty($sections)) {
        echo json_encode([
            'success' => true,
            'sections' => [],
            'message' => 'لا توجد أقسام لهذه اللغة'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'sections' => $sections
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'حدث خطأ أثناء جلب الأقسام: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
?> 