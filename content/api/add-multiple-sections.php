<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

try {
    // التحقق من البيانات المستلمة
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['language_id']) || !isset($data['names']) || !is_array($data['names'])) {
        throw new Exception('البيانات المرسلة غير صحيحة');
    }
    
    $languageId = (int)$data['language_id'];
    $names = array_map('trim', $data['names']);
    
    // التحقق من وجود اللغة
    $language = getLanguageById($languageId);
    if (!$language) {
        throw new Exception('اللغة غير موجودة');
    }
    
    // إضافة الأقسام
    $addedSections = [];
    foreach ($names as $name) {
        if (empty($name)) continue;
        
        $sectionId = addSection($languageId, $name);
        if ($sectionId) {
            $addedSections[] = [
                'id' => $sectionId,
                'name' => htmlspecialchars($name)
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'sections' => $addedSections
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 