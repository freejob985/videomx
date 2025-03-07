<?php
header('Content-Type: application/json; charset=utf-8');

// التأكد من وجود ملف الاتصال بقاعدة البيانات
$config_file = __DIR__ . '/../config/database.php';
if (!file_exists($config_file)) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'ملف الاتصال بقاعدة البيانات غير موجود'
    ]));
}

require_once $config_file;

try {
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $language_id = isset($_GET['language_id']) ? (int)$_GET['language_id'] : 0;
    
    $data = [];
    
    switch ($type) {
        case 'languages':
            $query = "SELECT id, name FROM languages ORDER BY name ASC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        case 'statuses':
            $query = "SELECT id, name, color FROM statuses ORDER BY name ASC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        case 'sections':
            $query = "SELECT DISTINCT s.id, s.name 
                     FROM sections s
                     INNER JOIN lessons l ON l.section_id = s.id
                     INNER JOIN courses c ON l.course_id = c.id
                     WHERE 1=1";
            
            if ($language_id > 0) {
                $query .= " AND c.language_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $language_id);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($query);
            }
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        case 'courses':
            $query = "SELECT id, title as name FROM courses WHERE 1=1";
            
            if ($language_id > 0) {
                $query .= " AND language_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $language_id);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($query);
            }
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        default:
            throw new Exception("نوع الفلتر غير صالح");
    }
    
    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب الفلاتر',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?> 