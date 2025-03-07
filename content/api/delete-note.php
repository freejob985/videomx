<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'طريقة طلب غير صحيحة']);
    exit;
}

$note_id = $_POST['note_id'] ?? null;

if (!$note_id) {
    echo json_encode(['success' => false, 'error' => 'معرف الملاحظة مطلوب']);
    exit;
}

try {
    $db = connectDB();
    $stmt = $db->prepare("DELETE FROM notes WHERE id = ?");
    $success = $stmt->execute([$note_id]);
    
    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'حدث خطأ أثناء حذف الملاحظة']);
} 