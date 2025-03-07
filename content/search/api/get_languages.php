<?php
/**
 * API للحصول على قائمة اللغات المتاحة
 * يقوم بإرجاع اللغات التي تحتوي على دروس فقط
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

try {
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 6;
    $offset = ($page - 1) * $per_page;

    // Get languages with course and lesson counts
    $query = "SELECT 
        l.id,
        l.name,
        COUNT(DISTINCT c.id) as courses_count,
        COUNT(DISTINCT les.id) as lessons_count
    FROM languages l
    LEFT JOIN courses c ON l.id = c.language_id
    LEFT JOIN lessons les ON c.id = les.course_id
    GROUP BY l.id
    HAVING lessons_count > 0
    ORDER BY l.name
    LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $languages = [];
    while ($row = $result->fetch_assoc()) {
        // Add default icon since it's not in database
        $row['icon'] = 'fas fa-code';
        $languages[] = $row;
    }

    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM (
        SELECT l.id
        FROM languages l
        LEFT JOIN courses c ON l.id = c.language_id
        LEFT JOIN lessons les ON c.id = les.course_id
        GROUP BY l.id
        HAVING COUNT(DISTINCT les.id) > 0
    ) as subquery";

    $count_result = $conn->query($count_query);
    $total_count = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_count / $per_page);

    echo json_encode([
        'success' => true,
        'languages' => $languages,
        'current_page' => $page,
        'total_pages' => $total_pages,
        'per_page' => $per_page,
        'total_count' => $total_count
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب اللغات',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?> 