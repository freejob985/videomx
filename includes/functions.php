/**
 * جلب اللغات مع الترتيب حسب عدد الدروس
 * @param int $page رقم الصفحة الحالية
 * @param int $perPage عدد العناصر في كل صفحة
 * @param bool $orderByLessons ترتيب حسب عدد الدروس
 * @return array مصفوفة تحتوي على اللغات مع إحصائياتها
 */
function getLanguagesPaginated($page = 1, $perPage = 12, $orderByLessons = true) {
    global $conn;
    
    $offset = ($page - 1) * $perPage;
    
    // استعلام محسن يتوافق مع هيكل قاعدة البيانات
    $sql = "SELECT l.*, 
            l.updated_at as last_update,
            COUNT(DISTINCT c.id) as courses_count,
            COUNT(DISTINCT ls.id) as lessons_count,
            COALESCE(SUM(ls.duration), 0) as total_duration,
            COUNT(DISTINCT CASE WHEN ls.completed = 1 THEN ls.id END) as completed_lessons,
            COUNT(DISTINCT CASE WHEN ls.is_important = 1 THEN ls.id END) as important_lessons,
            COUNT(DISTINCT CASE WHEN ls.is_theory = 1 THEN ls.id END) as theory_lessons,
            COUNT(DISTINCT CASE WHEN ls.is_reviewed = 1 THEN ls.id END) as reviewed_lessons
            FROM languages l
            LEFT JOIN courses c ON l.id = c.language_id
            LEFT JOIN lessons ls ON c.id = ls.course_id
            GROUP BY l.id
            ORDER BY lessons_count DESC, courses_count DESC, l.name ASC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * البحث عن اللغات مع دعم الترتيب حسب عدد الدروس
 * @param string $search نص البحث
 * @param int $page رقم الصفحة
 * @param int $perPage عدد العناصر في الصفحة
 * @return array مصفوفة تحتوي على نتائج البحث والعدد الإجمالي
 */
function searchLanguages($search, $page = 1, $perPage = 12) {
    global $conn;
    
    $offset = ($page - 1) * $perPage;
    $search = '%' . $search . '%';
    
    // استعلام البحث محدث ليتطابق مع getLanguagesPaginated
    $sql = "SELECT l.*, 
            l.updated_at as last_update,
            COUNT(DISTINCT c.id) as courses_count,
            COUNT(DISTINCT ls.id) as lessons_count,
            COALESCE(SUM(ls.duration), 0) as total_duration,
            COUNT(DISTINCT CASE WHEN ls.completed = 1 THEN ls.id END) as completed_lessons,
            COUNT(DISTINCT CASE WHEN ls.is_important = 1 THEN ls.id END) as important_lessons,
            COUNT(DISTINCT CASE WHEN ls.is_theory = 1 THEN ls.id END) as theory_lessons,
            COUNT(DISTINCT CASE WHEN ls.is_reviewed = 1 THEN ls.id END) as reviewed_lessons
            FROM languages l
            LEFT JOIN courses c ON l.id = c.language_id
            LEFT JOIN lessons ls ON c.id = ls.course_id
            WHERE l.name LIKE ?
            GROUP BY l.id
            ORDER BY lessons_count DESC, courses_count DESC, l.name ASC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $search, $perPage, $offset);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // الحصول على إجمالي عدد النتائج
    $countSql = "SELECT COUNT(*) as total FROM languages WHERE name LIKE ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("s", $search);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    return [
        'results' => $results,
        'total' => $total
    ];
}

/**
 * دالة لجلب إحصائيات الدورات والدروس للغة معينة
 * 
 * @param int $language_id معرف اللغة
 * @return array مصفوفة تحتوي على الإحصائيات
 */
function getCourseStats($language_id) {
    global $conn;
    
    // التحقق من صحة المعرف
    $language_id = (int)$language_id;
    if ($language_id <= 0) {
        return null;
    }

    try {
        // الإحصائيات العامة والتقدم
        $stats_query = "
            SELECT 
                -- إحصائيات عامة
                COUNT(DISTINCT c.id) as total_courses,
                COUNT(DISTINCT l.id) as total_lessons,
                
                -- الدروس المكتملة وغير المكتملة
                COUNT(DISTINCT CASE WHEN l.status_id = 3 THEN l.id END) as completed_lessons,
                COUNT(DISTINCT CASE WHEN l.status_id != 3 OR l.status_id IS NULL THEN l.id END) as remaining_lessons,
                
                -- مجموع مدة الدروس المكتملة وغير المكتملة
                COALESCE(SUM(CASE WHEN l.status_id = 3 THEN l.duration END), 0) as completed_duration,
                COALESCE(SUM(CASE WHEN l.status_id != 3 OR l.status_id IS NULL THEN l.duration END), 0) as remaining_duration
            FROM courses c
            LEFT JOIN lessons l ON c.id = l.course_id
            WHERE c.language_id = ?
        ";
        
        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param('i', $language_id);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();

        // إحصائيات حسب الحالة
        $status_query = "
            SELECT 
                s.id as status_id,
                s.name as status_name,
                COUNT(DISTINCT CASE WHEN c.language_id = ? THEN l.id END) as lessons_count,
                COALESCE(SUM(CASE WHEN c.language_id = ? THEN l.duration ELSE 0 END), 0) as total_duration
            FROM statuses s
            LEFT JOIN lessons l ON s.id = l.status_id
            LEFT JOIN courses c ON l.course_id = c.id
            WHERE s.language_id = ?
            GROUP BY s.id, s.name
            ORDER BY s.id
        ";
        
        $stmt = $conn->prepare($status_query);
        $stmt->bind_param('iii', $language_id, $language_id, $language_id);
        $stmt->execute();
        $status_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Debug: طباعة القيم للتحقق
        error_log("Stats for language_id $language_id: " . print_r($stats, true));
        error_log("Status stats: " . print_r($status_stats, true));

        return [
            'general_stats' => [
                'total_courses' => (int)($stats['total_courses'] ?? 0),
                'total_lessons' => (int)($stats['total_lessons'] ?? 0)
            ],
            'progress_stats' => [
                'completed_lessons' => (int)($stats['completed_lessons'] ?? 0),
                'remaining_lessons' => (int)($stats['remaining_lessons'] ?? 0),
                'completed_duration' => (int)($stats['completed_duration'] ?? 0),
                'remaining_duration' => (int)($stats['remaining_duration'] ?? 0)
            ],
            'status_stats' => array_map(function($status) {
                return [
                    'status_id' => (int)$status['status_id'],
                    'status_name' => $status['status_name'],
                    'lessons_count' => (int)($status['lessons_count'] ?? 0),
                    'total_duration' => (int)($status['total_duration'] ?? 0)
                ];
            }, $status_stats)
        ];
    } catch (Exception $e) {
        error_log("Error in getCourseStats: " . $e->getMessage());
        return [
            'general_stats' => ['total_courses' => 0, 'total_lessons' => 0],
            'progress_stats' => [
                'completed_lessons' => 0,
                'remaining_lessons' => 0,
                'completed_duration' => 0,
                'remaining_duration' => 0
            ],
            'status_stats' => []
        ];
    }
}

/**
 * دالة للتحقق من وجود لغة معينة
 * @param int $language_id معرف اللغة
 * @return bool
 */
function languageExists($language_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM languages WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $language_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * دالة لجلب معلومات لغة معينة
 * @param int $language_id معرف اللغة
 * @return array|null
 */
function getLanguageInfo($language_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM languages WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $language_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * دالة لجلب الدورات الخاصة بلغة معينة
 * @param int $language_id معرف اللغة
 * @return array
 */
function getCoursesByLanguage($language_id) {
    global $conn;
    
    $query = "
        SELECT 
            c.*,
            COUNT(DISTINCT l.id) as lessons_count,
            COALESCE(SUM(l.duration), 0) as total_duration
        FROM courses c
        LEFT JOIN lessons l ON c.id = l.course_id
        WHERE c.language_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $language_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} 