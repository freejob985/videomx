<?php
require_once '../includes/functions.php';

// التحقق من الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

// قراءة البيانات المرسلة
$data = json_decode(file_get_contents('php://input'), true);
$lesson_id = $data['lesson_id'] ?? null;

if (!$lesson_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing lesson ID']));
}

// تبديل حالة الأهمية
$result = toggleLessonImportance($lesson_id);
echo json_encode($result); 