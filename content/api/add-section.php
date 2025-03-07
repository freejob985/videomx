<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // التحقق من البيانات المرسلة
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['language_id']) || !isset($data['name'])) {
        throw new Exception('البيانات المطلوبة غير مكتملة');
    }
    
    $language_id = (int)$data['language_id'];
    $name = trim($data['name']);
    
    if (empty($name)) {
        throw new Exception('اسم القسم مطلوب');
    }
    
    // التحقق من وجود اللغة
    if (!languageExists($language_id)) {
        throw new Exception('اللغة غير موجودة');
    }
    
    // إضافة القسم
    $section_id = addSection($language_id, $name);
    
    if (!$section_id) {
        throw new Exception('فشل إضافة القسم');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إضافة القسم بنجاح',
        'section_id' => $section_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 