<?php
// التحقق من وجود الملف في نفس المجلد
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} 
// التحقق من وجود الملف في المجلد الأصلي
else if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} else {
    die('Configuration file not found');
}

// الحصول على معرف اللغة من الطلب
$languageId = isset($_GET['language_id']) ? intval($_GET['language_id']) : null;

try {
    // بناء الاستعلام الأساسي مع GROUP BY للحصول على المجاميع الصحيحة
    $query = "SELECT 
        COUNT(DISTINCT l.id) as total_lessons,
        COUNT(DISTINCT CASE WHEN l.completed = 1 THEN l.id END) as completed_lessons,
        COUNT(DISTINCT CASE WHEN l.is_reviewed = 1 THEN l.id END) as review_lessons,
        COUNT(DISTINCT CASE WHEN l.is_theory = 1 THEN l.id END) as theory_lessons,
        COUNT(DISTINCT CASE WHEN l.is_important = 1 THEN l.id END) as important_lessons,
        COALESCE(SUM(l.duration), 0) as total_duration,
        COALESCE(SUM(CASE WHEN l.completed = 1 THEN l.duration ELSE 0 END), 0) as completed_duration
        FROM lessons l
        INNER JOIN courses c ON l.course_id = c.id";

    // إضافة شرط اللغة إذا تم تحديدها
    if ($languageId) {
        $query .= " WHERE c.language_id = :language_id";
    }

    $stmt = $pdo->prepare($query);
    if ($languageId) {
        $stmt->bindParam(':language_id', $languageId, PDO::PARAM_INT);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // التأكد من أن القيم ليست NULL
    $result = array_map(function($value) {
        return $value === null ? 0 : $value;
    }, $result);

    // حساب الدروس والوقت المتبقي
    $result['remaining_lessons'] = $result['total_lessons'] - $result['completed_lessons'];
    $result['remaining_duration'] = $result['total_duration'] - $result['completed_duration'];

    // إضافة معلومات التشخيص
    $result['debug'] = [
        'language_id' => $languageId,
        'query' => $query
    ];

    // إرجاع النتائج بتنسيق JSON
    header('Content-Type: application/json');
    echo json_encode($result);

} catch(PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'error' => $e->getMessage(),
        'query' => $query ?? null,
        'file_path' => __FILE__,
        'config_exists' => file_exists(__DIR__ . '/config.php')
    ]);
}
?> 