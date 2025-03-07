<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // التحقق من طريقة الطلب
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('طريقة طلب غير صحيحة');
    }

    // قراءة البيانات المرسلة
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['lesson_id'], $data['preference_key'], $data['preference_value'])) {
        throw new Exception('بيانات غير مكتملة');
    }

    $lessonId = $data['lesson_id'];
    $key = $data['preference_key'];
    $value = $data['preference_value'];

    // حفظ التفضيل في قاعدة البيانات
    saveUserPreference($lessonId, $key, $value);

    echo json_encode([
        'success' => true,
        'message' => 'تم حفظ التفضيل بنجاح'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function saveUserPreference($lessonId, $key, $value) {
    $db = connectDB();
    
    // التحقق من وجود التفضيل مسبقاً
    $stmt = $db->prepare("
        SELECT id FROM user_preferences 
        WHERE lesson_id = ? AND preference_key = ?
    ");
    $stmt->execute([$lessonId, $key]);
    $existing = $stmt->fetch();

    if ($existing) {
        // تحديث التفضيل الموجود
        $stmt = $db->prepare("
            UPDATE user_preferences 
            SET preference_value = ?, updated_at = NOW()
            WHERE lesson_id = ? AND preference_key = ?
        ");
        return $stmt->execute([$value, $lessonId, $key]);
    } else {
        // إضافة تفضيل جديد
        $stmt = $db->prepare("
            INSERT INTO user_preferences 
            (lesson_id, preference_key, preference_value, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        return $stmt->execute([$lessonId, $key, $value]);
    }
} 