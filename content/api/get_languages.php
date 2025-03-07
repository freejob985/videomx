<?php
/**
 * API للحصول على قائمة اللغات مع التصفيح
 * المدخلات:
 * - page: رقم الصفحة الحالية
 * - per_page: عدد العناصر في كل صفحة
 */

// إعداد اتصال قاعدة البيانات
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'courses_db';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset("utf8");

// التحقق من الاتصال
if ($conn->connect_error) {
    die(json_encode(['error' => 'فشل الاتصال بقاعدة البيانات']));
}

// استلام المعاملات
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 6;

// حساب الإزاحة
$offset = ($page - 1) * $per_page;

// استعلام لجلب إجمالي عدد اللغات
$total_query = "SELECT COUNT(DISTINCT l.id) as total 
                FROM languages l
                INNER JOIN courses c ON c.language_id = l.id
                INNER JOIN lessons les ON les.course_id = c.id
                WHERE les.status_id = 1";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_languages = $total_row['total'];

// استعلام لجلب اللغات مع التصفيح
$languages_query = "SELECT 
                        l.*,
                        COUNT(DISTINCT les.id) as lessons_count
                    FROM languages l
                    INNER JOIN courses c ON c.language_id = l.id
                    INNER JOIN lessons les ON les.course_id = c.id
                    WHERE les.status_id = 1
                    GROUP BY l.id
                    ORDER BY l.name ASC
                    LIMIT ?, ?";

$stmt = $conn->prepare($languages_query);
$stmt->bind_param("ii", $offset, $per_page);
$stmt->execute();
$result = $stmt->get_result();

$languages = [];
while ($row = $result->fetch_assoc()) {
    $languages[] = $row;
}

// تحضير البيانات للرد
$response = [
    'languages' => $languages,
    'total' => $total_languages,
    'current_page' => $page,
    'total_pages' => ceil($total_languages / $per_page)
];

// إغلاق الاتصالات
$stmt->close();
$conn->close();

// إرسال الرد
header('Content-Type: application/json');
echo json_encode($response); 