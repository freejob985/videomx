<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

try {
    $lesson_id = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
    
    if ($lesson_id <= 0) {
        throw new Exception("معرف الدرس غير صالح");
    }

    // Get lesson details
    $query = "SELECT 
        l.*,
        c.title as course_title,
        s.name as section_name,
        st.name as status_name,
        st.color as status_color
    FROM lessons l
    LEFT JOIN courses c ON l.course_id = c.id
    LEFT JOIN sections s ON l.section_id = s.id
    LEFT JOIN statuses st ON l.status_id = st.id
    WHERE l.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $lesson_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $lesson = $result->fetch_assoc();

    if (!$lesson) {
        throw new Exception("الدرس غير موجود");
    }

    // Format lesson data
    $lesson['duration_formatted'] = formatDuration($lesson['duration']);
    $lesson['is_important'] = (bool)$lesson['is_important'];
    $lesson['is_theory'] = (bool)$lesson['is_theory'];
    $lesson['completed'] = (bool)$lesson['completed'];

    // Get lesson notes
    $notes_query = "SELECT * FROM notes WHERE lesson_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($notes_query);
    $stmt->bind_param('i', $lesson_id);
    $stmt->execute();
    $notes_result = $stmt->get_result();
    
    $notes = [];
    while ($note = $notes_result->fetch_assoc()) {
        $notes[] = $note;
    }

    echo json_encode([
        'success' => true,
        'lesson' => $lesson,
        'notes' => $notes
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب تفاصيل الدرس',
        'error' => $e->getMessage()
    ]);
}

$conn->close();

/**
 * Helper function to format duration
 */
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
}
?> 