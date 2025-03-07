<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // التحقق من البيانات المرسلة
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['language_id'])) {
        throw new Exception('معرف اللغة مطلوب');
    }
    
    $language_id = (int)$data['language_id'];
    
    // حذف اللغة وكل العناصر المرتبطة بها
    if (!deleteLanguageWithRelated($language_id)) {
        throw new Exception('فشل حذف اللغة');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم حذف اللغة وجميع العناصر المرتبطة بها بنجاح'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 