<?php
// استيراد ملف الاتصال بقاعدة البيانات
require_once '../config/database.php';

/**
 * الحصول على الأقسام حسب اللغة المحددة
 * 
 * @param int $language_id معرف اللغة
 * @return array مصفوفة تحتوي على الأقسام
 */
function getSectionsByLanguage($language_id) {
    global $conn;
    
    // التحقق من صحة المعرف
    $language_id = filter_var($language_id, FILTER_VALIDATE_INT);
    if (!$language_id) {
        return array('error' => 'معرف اللغة غير صالح');
    }

    try {
        // استعلام SQL للحصول على الأقسام
        $query = "SELECT id, name, description 
                 FROM sections 
                 WHERE language_id = ? 
                 ORDER BY name ASC";
                 
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $language_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sections = array();
        while ($row = $result->fetch_assoc()) {
            $sections[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description']
            );
        }
        
        return array('success' => true, 'sections' => $sections);
        
    } catch (Exception $e) {
        return array('error' => 'حدث خطأ أثناء جلب البيانات: ' . $e->getMessage());
    }
}

// التحقق من وجود طلب AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['language_id'])) {
    $language_id = $_GET['language_id'];
    $response = getSectionsByLanguage($language_id);
    
    // إرسال الاستجابة كـ JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?> 