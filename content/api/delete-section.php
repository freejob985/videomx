<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // التحقق من البيانات المرسلة
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['section_id'])) {
        throw new Exception('معرف القسم مطلوب');
    }
    
    $section_id = (int)$data['section_id'];
    
    // حذف القسم
    if (!deleteSection($section_id)) {
        throw new Exception('فشل حذف القسم');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم حذف القسم بنجاح'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 