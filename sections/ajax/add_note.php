<?php
require_once '../../includes/functions.php';
require_once '../../includes/sections_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method Not Allowed']));
}

$lesson_id = $_POST['lesson_id'] ?? null;
$content = $_POST['content'] ?? null;

if (!$lesson_id || !$content) {
    http_response_code(400);
    exit(json_encode(['error' => 'البيانات غير مكتملة']));
}

try {
    // تخزين الملاحظة في LocalStorage عن طريق JavaScript
    echo json_encode([
        'success' => true,
        'message' => 'تم إضافة الملاحظة بنجاح',
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 