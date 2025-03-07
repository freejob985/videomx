<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'طريقة طلب غير صحيحة']);
    exit;
}

$note_id = $_GET['note_id'] ?? null;

if (!$note_id) {
    echo json_encode(['success' => false, 'error' => 'معرف الملاحظة مطلوب']);
    exit;
}

try {
    $note = getNoteById($note_id);
    
    if ($note) {
        echo json_encode(['success' => true, 'note' => $note]);
    } else {
        echo json_encode(['success' => false, 'error' => 'الملاحظة غير موجودة']);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'حدث خطأ أثناء جلب الملاحظة']);
} 