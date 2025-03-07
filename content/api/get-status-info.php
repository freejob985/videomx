<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

$status_id = $_GET['status_id'] ?? null;

if (!$status_id) {
    echo json_encode([
        'success' => false,
        'message' => 'معرف الحالة مطلوب'
    ]);
    exit;
}

try {
    $status = getStatusInfo($status_id);
    
    if ($status) {
        echo json_encode([
            'success' => true,
            'status' => $status
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'الحالة غير موجودة'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 