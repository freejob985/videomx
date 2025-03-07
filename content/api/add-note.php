<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // التحقق من البيانات المطلوبة الأساسية
    $required_fields = ['lesson_id', 'type', 'title'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    // التحقق من البيانات الإضافية حسب نوع الملاحظة
    $type = $_POST['type'] ?? '';
    
    switch ($type) {
        case 'text':
            if (empty($_POST['content'])) {
                $missing_fields[] = 'content';
            }
            break;
            
        case 'code':
            if (empty($_POST['code_content'])) {
                $missing_fields[] = 'code_content';
            }
            if (empty($_POST['code_language'])) {
                $missing_fields[] = 'code_language';
            }
            break;
            
        case 'link':
            if (empty($_POST['link_url'])) {
                $missing_fields[] = 'link_url';
            }
            break;
    }
    
    if (!empty($missing_fields)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing_fields));
    }
    
    // تحضير بيانات الملاحظة
    $note = [
        'lesson_id' => filter_input(INPUT_POST, 'lesson_id', FILTER_SANITIZE_NUMBER_INT),
        'type' => $type,
        'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING)
    ];
    
    // إضافة المحتوى حسب النوع
    switch ($type) {
        case 'text':
            $note['content'] = $_POST['content']; // نحتفظ بالـ HTML للمحتوى المنسق
            break;
            
        case 'code':
            $note['content'] = $_POST['code_content'];
            $note['code_language'] = filter_input(INPUT_POST, 'code_language', FILTER_SANITIZE_STRING);
            break;
            
        case 'link':
            $note['content'] = ''; // محتوى فارغ للروابط
            $note['link_url'] = filter_input(INPUT_POST, 'link_url', FILTER_SANITIZE_URL);
            $note['link_description'] = filter_input(INPUT_POST, 'link_description', FILTER_SANITIZE_STRING);
            break;
    }
    
    // إضافة الملاحظة إلى قاعدة البيانات
    $note_id = addNote($note);
    
    if (!$note_id) {
        throw new Exception('Failed to add note');
    }
    
    // جلب الملاحظة المضافة
    $added_note = getNoteById($note_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Note added successfully',
        'note' => $added_note
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 