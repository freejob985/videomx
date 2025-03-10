<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['lesson_id'])) {
        throw new Exception('معرف الدرس مطلوب');
    }
    
    $lesson_id = intval($data['lesson_id']);
    $updates = [];
    $types = '';
    $values = [];
    
    // Build update query based on provided fields
    if (isset($data['completed'])) {
        $updates[] = 'completed = ?';
        $types .= 'i';
        $values[] = $data['completed'] ? 1 : 0;
    }
    
    if (isset($data['tags'])) {
        $updates[] = 'tags = ?';
        $types .= 's';
        $values[] = $data['tags'];
    }
    
    if (isset($data['section_id'])) {
        $updates[] = 'section_id = ?';
        $types .= 'i';
        $values[] = intval($data['section_id']);
    }
    
    if (empty($updates)) {
        throw new Exception('لا توجد بيانات للتحديث');
    }
    
    // Add lesson_id to values array
    $values[] = $lesson_id;
    $types .= 'i';
    
    // Prepare and execute update query
    $query = "UPDATE lessons SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    // Bind parameters dynamically
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث الدرس بنجاح'
        ]);
    } else {
        throw new Exception('فشل تحديث الدرس');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
?> 