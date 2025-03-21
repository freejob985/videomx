<?php
require_once '../../includes/functions.php';
require_once '../../includes/sections_functions.php';

// التحقق من الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// التحقق من البيانات المطلوبة
$lesson_id = $_POST['lesson_id'] ?? null;
$section_id = $_POST['section_id'] ?? null;

if (!$lesson_id || !$section_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'البيانات غير مكتملة']));
}

// تحديث القسم
try {
    $result = updateLessonSection($lesson_id, $section_id);
    if ($result) {
        // جلب معلومات القسم الجديد
        $section = getSectionInfo($section_id);
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث القسم بنجاح',
            'section' => [
                'name' => $section['name'],
                'id' => $section['id']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'فشل تحديث القسم']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 