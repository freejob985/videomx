<?php
require_once '../../includes/functions.php';
require_once '../../includes/sections_functions.php';

// التحقق من الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'طريقة طلب غير صحيحة']);
    exit;
}

// قراءة البيانات
$data = json_decode(file_get_contents('php://input'), true);
$section_id = $data['section_id'] ?? null;
$description = $data['description'] ?? '';

// التحقق من البيانات
if (!$section_id || !sectionExists($section_id)) {
    echo json_encode(['success' => false, 'error' => 'القسم غير موجود']);
    exit;
}

// تحديث وصف القسم
try {
    updateSectionDescription($section_id, $description);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 