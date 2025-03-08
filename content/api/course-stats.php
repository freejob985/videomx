<?php
/**
 * API Endpoints for Course Stats
 * ============================
 * معالجة طلبات AJAX الخاصة بإحصائيات الكورس
 */

require_once '../includes/course-stats-functions.php';

header('Content-Type: application/json');

// التحقق من نوع الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['status' => 'error', 'message' => 'Method not allowed']));
}

// التحقق من وجود معرف الكورس
$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
if (!$course_id) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => 'Course ID is required']));
}

// معالجة الطلبات
$action = $_POST['action'] ?? '';
$response = [];

switch ($action) {
    case 'get_stats':
        $response = getCourseDetailedStats($course_id);
        break;
        
    case 'get_lessons':
        $show_completed = isset($_POST['show_completed']) ? (bool)$_POST['show_completed'] : true;
        $response = getLessonsForDropdown($course_id, $show_completed);
        break;
        
    case 'toggle_completed_visibility':
        $show_completed = isset($_POST['show_completed']) ? (bool)$_POST['show_completed'] : true;
        $response = updateCompletedLessonsVisibility($course_id, $show_completed);
        break;
        
    default:
        http_response_code(400);
        $response = ['status' => 'error', 'message' => 'Invalid action'];
}

echo json_encode($response); 