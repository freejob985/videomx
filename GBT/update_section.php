<?php
/**
 * update_section.php
 * ملف لتحديث وصف قسم معين
 * 
 * المدخلات:
 * - section_id: معرف القسم
 * - description: الوصف الجديد (يمكن أن يحتوي على HTML)
 * 
 * المخرجات:
 * - JSON يحتوي على حالة العملية ورسالة
 */

// تكوين قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'courses_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// إعداد headers لـ JSON
header('Content-Type: application/json; charset=utf-8');

// التحقق من وجود البيانات المطلوبة
if (!isset($_POST['section_id']) || !is_numeric($_POST['section_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'معرف القسم غير صالح'
    ]);
    exit;
}

$sectionId = (int)$_POST['section_id'];
$description = isset($_POST['description']) ? $_POST['description'] : '';

// تنظيف HTML للحماية من XSS مع السماح بعناصر HTML محددة
$allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><blockquote><pre><code><ul><ol><li><table><thead><tbody><tr><th><td><a><img><div><span>';
$description = strip_tags($description, $allowedTags);

try {
    // إنشاء اتصال PDO
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // تحديث وصف القسم
    $stmt = $pdo->prepare("UPDATE sections SET description = ? WHERE id = ?");
    $stmt->execute([$description, $sectionId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'تم تحديث وصف القسم بنجاح'
        ]);
    } else {
        // التحقق من وجود القسم
        $checkStmt = $pdo->prepare("SELECT id FROM sections WHERE id = ?");
        $checkStmt->execute([$sectionId]);
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'لم يتم إجراء أي تغييرات على وصف القسم'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'لم يتم العثور على القسم'
            ]);
        }
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطأ عام: ' . $e->getMessage()
    ]);
} 