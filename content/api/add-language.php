<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // التحقق من البيانات المرسلة
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name'])) {
        throw new Exception('اسم اللغة مطلوب');
    }
    
    $name = trim($data['name']);
    
    if (empty($name)) {
        throw new Exception('اسم اللغة مطلوب');
    }
    
    // التحقق من عدم تكرار اسم اللغة
    if (languageNameExists($name)) {
        throw new Exception('اسم اللغة موجود مسبقاً');
    }
    
    // إضافة اللغة
    $language_id = addLanguage($name);
    
    if (!$language_id) {
        throw new Exception('فشل إضافة اللغة');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إضافة اللغة بنجاح',
        'language_id' => $language_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 