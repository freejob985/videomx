<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['status_id']) || !isset($data['color']) || !isset($data['text_color'])) {
    echo json_encode([
        'success' => false,
        'message' => 'بيانات غير مكتملة'
    ]);
    exit;
}

$status_id = $data['status_id'];
$color = $data['color'];
$text_color = $data['text_color'];

// التحقق من صحة الألوان
if (!preg_match('/^#[a-f0-9]{6}$/i', $color) || !preg_match('/^#[a-f0-9]{6}$/i', $text_color)) {
    echo json_encode([
        'success' => false,
        'message' => 'صيغة اللون غير صحيحة'
    ]);
    exit;
}

$success = updateStatusColor($status_id, $color) && updateStatusTextColor($status_id, $text_color);

echo json_encode([
    'success' => $success,
    'message' => $success ? 'تم تحديث الألوان بنجاح' : 'فشل تحديث الألوان'
]); 