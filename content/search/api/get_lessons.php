<?php
header('Content-Type: application/json; charset=utf-8');

// التأكد من وجود ملف الاتصال بقاعدة البيانات
$config_file = __DIR__ . '/../config/database.php';
if (!file_exists($config_file)) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'ملف الاتصال بقاعدة البيانات غير موجود'
    ]));
}

require_once $config_file;
require_once '../includes/helpers.php';

try {
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 21;
    $offset = ($page - 1) * $per_page;

    // Build base query
    $query = "SELECT DISTINCT 
        l.*, 
        c.title as course_title,
        s.name as section_name,
        st.name as status_name,
        st.color as status_color
    FROM lessons l
    LEFT JOIN courses c ON l.course_id = c.id
    LEFT JOIN sections s ON l.section_id = s.id
    LEFT JOIN statuses st ON l.status_id = st.id
    WHERE 1=1";

    $params = array();
    $where_conditions = array();

    // Add filter conditions
    if (isset($_GET['language_id']) && !empty($_GET['language_id'])) {
        $where_conditions[] = "c.language_id = ?";
        $params[] = $_GET['language_id'];
    }

    if (isset($_GET['status_id']) && !empty($_GET['status_id'])) {
        $where_conditions[] = "l.status_id = ?";
        $params[] = $_GET['status_id'];
    }

    if (isset($_GET['section_id']) && !empty($_GET['section_id'])) {
        $where_conditions[] = "l.section_id = ?";
        $params[] = $_GET['section_id'];
    }

    if (isset($_GET['course_id']) && !empty($_GET['course_id'])) {
        $where_conditions[] = "l.course_id = ?";
        $params[] = $_GET['course_id'];
    }

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $where_conditions[] = "(l.title LIKE ? OR l.tags LIKE ?)";
        $search_term = '%' . $_GET['search'] . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }

    // Add where conditions to query
    if (!empty($where_conditions)) {
        $query .= " AND " . implode(" AND ", $where_conditions);
    }

    // Get total count
    $count_query = "SELECT COUNT(DISTINCT l.id) as total FROM lessons l 
                    LEFT JOIN courses c ON l.course_id = c.id
                    LEFT JOIN sections s ON l.section_id = s.id
                    WHERE 1=1";
    if (!empty($where_conditions)) {
        $count_query .= " AND " . implode(" AND ", $where_conditions);
    }

    $stmt = $conn->prepare($count_query);
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total_result = $stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total = $total_row['total'];

    // Add pagination to main query
    $query .= " ORDER BY l.order_number LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;

    // Prepare and execute main query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $lessons = array();
    while ($row = $result->fetch_assoc()) {
        $row['duration_formatted'] = formatDuration($row['duration']);
        $row['is_important'] = (bool)$row['is_important'];
        $row['is_theory'] = (bool)$row['is_theory'];
        $row['completed'] = (bool)$row['completed'];
        $lessons[] = $row;
    }

    // Get statistics
    $stats = getLessonsStats($conn, $where_conditions, $params);

    // Calculate total pages
    $total_pages = ceil($total / $per_page);

    // Return JSON response
    echo json_encode([
        'success' => true,
        'lessons' => $lessons,
        'total' => $total,
        'pages' => $total_pages,
        'current_page' => $page,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب الدروس',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?> 