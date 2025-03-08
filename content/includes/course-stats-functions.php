<?php
/**
 * دوال إحصائيات الدروس والكورسات
 * ===============================
 * ملف يحتوي على الدوال الخاصة بإحصائيات الدروس والكورسات
 * 
 * المميزات:
 * - حساب عدد الدروس
 * - حساب نسبة الإكمال
 * - إدارة حالة عرض الدروس
 * - التنقل بين الدروس
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';

// Initialize database connection
function getCourseStatsPDO() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
            ];
            $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    return $pdo;
}

/**
 * جلب إحصائيات الكورس المفصلة
 * @param int $course_id معرف الكورس
 * @return array إحصائيات الكورس
 */
function getCourseDetailedStats($course_id) {
    try {
        $pdo = getCourseStatsPDO();
        $query = "
            SELECT 
                COUNT(*) as total_lessons,
                COALESCE(SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END), 0) as completed_lessons,
                COALESCE(SUM(CASE WHEN is_reviewed = 1 THEN 1 ELSE 0 END), 0) as reviewed_lessons,
                COALESCE(ROUND((SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0)), 1), 0) as completion_percentage,
                -- حساب وقت الكورس الكلي
                (SELECT COALESCE(duration, 0) 
                 FROM courses 
                 WHERE id = ?) as total_duration,
                -- حساب الوقت المكتمل من الكورس
                (SELECT COALESCE(
                    (duration * (SELECT COALESCE(ROUND((SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0)), 1), 0) / 100
                     FROM lessons 
                     WHERE course_id = courses.id)), 0)
                 FROM courses 
                 WHERE id = ?) as completed_duration
            FROM lessons 
            WHERE course_id = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$course_id, $course_id, $course_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // تحويل جميع القيم إلى أرقام
        foreach ($stats as $key => $value) {
            if (is_numeric($value)) {
                $stats[$key] = (float)$value;
            }
        }
        
        // التأكد من أن الأوقات أرقام صحيحة
        $stats['total_duration'] = floor($stats['total_duration']);
        $stats['completed_duration'] = floor($stats['completed_duration']);
        
        return $stats;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [
            'total_lessons' => 0,
            'completed_lessons' => 0,
            'reviewed_lessons' => 0,
            'completion_percentage' => 0,
            'total_duration' => 0,
            'completed_duration' => 0
        ];
    }
}

/**
 * جلب قائمة الدروس للقائمة المنسدلة
 * @param int $course_id معرف الكورس
 * @param bool $show_completed عرض الدروس المكتملة
 * @return array قائمة الدروس
 */
function getLessonsForDropdown($course_id, $show_completed = true) {
    try {
        $pdo = getCourseStatsPDO();
        $query = "
            SELECT 
                l.id,
                l.title,
                l.completed,
                l.is_reviewed,
                l.order_number,
                l.duration,
                COALESCE(s.name, 'غير محدد') as status_name,
                COALESCE(s.color, '#6c757d') as status_color,
                CASE 
                    WHEN l.completed = 1 THEN 1
                    WHEN l.is_reviewed = 1 THEN 2
                    ELSE 3
                END as sort_order
            FROM lessons l
            LEFT JOIN statuses s ON l.status_id = s.id
            WHERE l.course_id = ?
        ";
        
        if (!$show_completed) {
            $query .= " AND (l.completed = 0 OR l.completed IS NULL)";
        }
        
        $query .= " ORDER BY sort_order ASC, l.order_number ASC, l.id ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * تحديث حالة عرض الدروس المكتملة
 * @param int $course_id معرف الكورس
 * @param bool $show_completed حالة العرض
 * @return array نتيجة التحديث
 */
function updateCompletedLessonsVisibility($course_id, $show_completed) {
    try {
        $pdo = getCourseStatsPDO();
        $lessons = getLessonsForDropdown($course_id, $show_completed);
        
        return [
            'success' => true,
            'lessons' => $lessons,
            'message' => $show_completed ? 'تم إظهار جميع الدروس' : 'تم إخفاء الدروس المكتملة'
        ];
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث حالة العرض'
        ];
    }
} 