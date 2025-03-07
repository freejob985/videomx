<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'طريقة طلب غير صحيحة']);
    exit;
}

$note_id = $_POST['note_id'] ?? null;
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';

if (!$note_id || !$title) {
    echo json_encode(['success' => false, 'error' => 'جميع الحقول مطلوبة']);
    exit;
}

try {
    $db = connectDB();
    
    // جلب نوع الملاحظة الحالي
    $stmt = $db->prepare("SELECT type FROM notes WHERE id = ?");
    $stmt->execute([$note_id]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$note) {
        echo json_encode(['success' => false, 'error' => 'الملاحظة غير موجودة']);
        exit;
    }
    
    // تحديث الملاحظة حسب نوعها
    $sql = "UPDATE notes SET title = ?, content = ?";
    $params = [$title, $content];
    
    if ($note['type'] === 'code' && isset($_POST['code_language'])) {
        $sql .= ", code_language = ?";
        $params[] = $_POST['code_language'];
    } elseif ($note['type'] === 'link') {
        $sql .= ", link_url = ?, link_description = ?";
        $params[] = $_POST['link_url'] ?? '';
        $params[] = $_POST['link_description'] ?? '';
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $note_id;
    
    $stmt = $db->prepare($sql);
    $success = $stmt->execute($params);
    
    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'حدث خطأ أثناء تحديث الملاحظة']);
} 