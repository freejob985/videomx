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