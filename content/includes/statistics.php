<?php
/**
 * ملف الإحصائيات - يحتوي على دوال حساب إحصائيات الدروس
 * 
 * @package LearningPlatform
 * @subpackage Statistics
 */

// إضافة الاتصال بقاعدة البيانات
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/time_formatter.php';

/**
 * دالة لجلب إحصائيات الدروس
 * تقوم بحساب:
 * - عدد الدروس المكتملة ومدتها
 * - عدد الدروس المتبقية ومدتها
 * - إجمالي عدد الدروس والمدة
 *
 * @return array مصفوفة تحتوي على الإحصائيات
 */
function get_lessons_statistics() {
    // التأكد من وجود اتصال بقاعدة البيانات
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$conn) {
        error_log("Failed to connect to database in statistics.php: " . mysqli_connect_error());
        return [
            'total_count' => 0,
            'total_duration' => 0,
            'completed_count' => 0,
            'completed_duration' => 0,
            'remaining_count' => 0,
            'remaining_duration' => 0,
            'important_count' => 0,
            'theory_count' => 0,
            'completion_percentage' => 0
        ];
    }
    
    // استعلام لحساب الإحصائيات
    $query = "SELECT 
        COUNT(*) as total_count,
        SUM(COALESCE(duration, 0)) as total_duration,
        SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN completed = 1 THEN COALESCE(duration, 0) ELSE 0 END) as completed_duration,
        SUM(CASE WHEN completed = 0 THEN 1 ELSE 0 END) as remaining_count,
        SUM(CASE WHEN completed = 0 THEN COALESCE(duration, 0) ELSE 0 END) as remaining_duration,
        SUM(CASE WHEN is_important = 1 THEN 1 ELSE 0 END) as important_count,
        SUM(CASE WHEN is_theory = 1 THEN 1 ELSE 0 END) as theory_count
        FROM lessons";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("Error in get_lessons_statistics: " . mysqli_error($conn));
        mysqli_close($conn);
        return [
            'total_count' => 0,
            'total_duration' => 0,
            'completed_count' => 0,
            'completed_duration' => 0,
            'remaining_count' => 0,
            'remaining_duration' => 0,
            'important_count' => 0,
            'theory_count' => 0,
            'completion_percentage' => 0
        ];
    }
    
    $stats = mysqli_fetch_assoc($result);
    
    // حساب نسبة الإكمال
    $stats['completion_percentage'] = $stats['total_count'] > 0 
        ? round(($stats['completed_count'] / $stats['total_count']) * 100, 2)
        : 0;
    
    // تحويل القيم NULL إلى 0
    foreach ($stats as $key => $value) {
        if (is_null($value)) {
            $stats[$key] = 0;
        }
    }
    
    mysqli_close($conn);
    return $stats;
}

/**
 * دالة لتنسيق الوقت من ثواني إلى صيغة مقروءة
 * تم تغيير الاسم لتجنب التعارض مع time_formatter.php
 *
 * @param int $seconds عدد الثواني
 * @return string الوقت المنسق (مثال: "5 ساعات و 30 دقيقة")
 */
function format_time_statistics($seconds) {
    if (!$seconds) return "0 ساعة";
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    $output = [];
    if ($hours > 0) {
        $output[] = $hours . " " . ($hours == 1 ? "ساعة" : "ساعات");
    }
    if ($minutes > 0) {
        $output[] = $minutes . " " . ($minutes == 1 ? "دقيقة" : "دقائق");
    }
    
    return implode(" و ", $output);
}

/**
 * دالة تحسب عدد الدروس المكتملة
 * @param int $course_id معرف الدورة (اختياري)
 * @return int عدد الدروس المكتملة
 */
function get_completed_lessons_count($course_id = null) {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection error in statistics.php");
        return 0;
    }
    
    $sql = "SELECT COUNT(*) as count FROM lessons WHERE completed = 1";
    if ($course_id) {
        $sql .= " AND course_id = " . intval($course_id);
    }
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Query error in get_completed_lessons_count: " . mysqli_error($conn));
        return 0;
    }
    
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

/**
 * دالة تحسب عدد الدروس المتبقية
 * @param int $course_id معرف الدورة (اختياري)
 * @return int عدد الدروس المتبقية
 */
function get_remaining_lessons_count($course_id = null) {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection error in statistics.php");
        return 0;
    }
    
    $sql = "SELECT COUNT(*) as count FROM lessons WHERE completed = 0";
    if ($course_id) {
        $sql .= " AND course_id = " . intval($course_id);
    }
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Query error in get_remaining_lessons_count: " . mysqli_error($conn));
        return 0;
    }
    
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

/**
 * دالة تحسب إجمالي وقت الدروس المكتملة
 * @param int $course_id معرف الدورة (اختياري)
 * @return int إجمالي الوقت بالثواني
 */
function get_completed_lessons_duration($course_id = null) {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection error in statistics.php");
        return 0;
    }
    
    $sql = "SELECT COALESCE(duration, 0) as duration FROM lessons WHERE completed = 1";
    if ($course_id) {
        $sql .= " AND course_id = " . intval($course_id);
    }
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Query error in get_completed_lessons_duration: " . mysqli_error($conn));
        return 0;
    }
    
    $total_seconds = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $total_seconds += intval($row['duration']);
    }
    
    return $total_seconds;
}

/**
 * دالة تحسب إجمالي وقت الدروس المتبقية
 * @param int $course_id معرف الدورة (اختياري)
 * @return int إجمالي الوقت بالثواني
 */
function get_remaining_lessons_duration($course_id = null) {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection error in statistics.php");
        return 0;
    }
    
    $sql = "SELECT COALESCE(duration, 0) as duration FROM lessons WHERE completed = 0";
    if ($course_id) {
        $sql .= " AND course_id = " . intval($course_id);
    }
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Query error in get_remaining_lessons_duration: " . mysqli_error($conn));
        return 0;
    }
    
    $total_seconds = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $total_seconds += intval($row['duration']);
    }
    
    return $total_seconds;
}

/**
 * دالة تحسب إجمالي وقت جميع الدروس
 * @param int $course_id معرف الدورة (اختياري)
 * @return string الوقت بتنسيق HH:MM:SS
 */
function get_total_lessons_duration($course_id = null) {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection error in statistics.php");
        return "0:00";
    }
    
    $sql = "SELECT duration FROM lessons";
    if ($course_id) {
        $sql .= " WHERE course_id = " . intval($course_id);
    }
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Query error in get_total_lessons_duration: " . mysqli_error($conn));
        return "0:00";
    }
    
    $total_seconds = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $total_seconds += intval($row['duration']);
    }
    
    return format_time_statistics($total_seconds);
}

/**
 * دالة تحول الدقائق إلى تنسيق ساعات:دقائق
 * @param int $minutes عدد الدقائق
 * @return string الوقت بتنسيق ساعات:دقائق
 */
function format_duration($minutes) {
    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    return sprintf("%02d:%02d", $hours, $remaining_minutes);
}

/**
 * دالة تجلب إحصائيات تفصيلية للدروس
 * @param int $course_id معرف الدورة (اختياري)
 * @return array مصفوفة تحتوي على جميع الإحصائيات التفصيلية
 */
function get_detailed_lessons_statistics($course_id = null) {
    $completed_duration = get_completed_lessons_duration($course_id);
    $remaining_duration = get_remaining_lessons_duration($course_id);
    
    return [
        'completed_count' => get_completed_lessons_count($course_id),
        'remaining_count' => get_remaining_lessons_count($course_id),
        'completed_duration' => $completed_duration,
        'remaining_duration' => $remaining_duration,
        'total_count' => get_completed_lessons_count($course_id) + get_remaining_lessons_count($course_id),
        'total_duration' => $completed_duration + $remaining_duration
    ];
}
?> 