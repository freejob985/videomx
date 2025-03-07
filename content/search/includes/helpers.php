<?php
/**
 * Helper functions for the application
 */

/**
 * Format duration from seconds to HH:MM:SS
 * @param int $seconds
 * @return string
 */
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
}

/**
 * Get lessons statistics
 * @param mysqli $conn Database connection
 * @param array $where_conditions Array of WHERE conditions
 * @param array $params Array of parameters for prepared statement
 * @return array
 */
function getLessonsStats($conn, $where_conditions = [], $params = []) {
    try {
        // Base query for total lessons
        $query = "SELECT 
            COUNT(DISTINCT l.id) as total_lessons,
            SUM(l.completed = 1) as completed_lessons,
            SUM(l.is_important = 1) as important_lessons,
            SUM(l.is_theory = 1) as theory_lessons,
            SUM(l.duration) as total_duration,
            SUM(CASE WHEN l.completed = 1 THEN l.duration ELSE 0 END) as completed_duration
        FROM lessons l
        LEFT JOIN courses c ON l.course_id = c.id
        LEFT JOIN sections s ON l.section_id = s.id
        WHERE 1=1";

        // Add where conditions if any
        if (!empty($where_conditions)) {
            $query .= " AND " . implode(" AND ", $where_conditions);
        }

        // Remove pagination parameters from params array
        $statsParams = array_slice($params, 0, -2);

        // Prepare and execute query
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Error preparing stats query: " . $conn->error);
        }

        // Bind parameters if any
        if (!empty($statsParams)) {
            $types = str_repeat('s', count($statsParams));
            $stmt->bind_param($types, ...$statsParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();

        if (!$stats) {
            throw new Exception("No statistics found");
        }

        // Calculate percentages and format durations
        $total_lessons = (int)$stats['total_lessons'];
        $completed_lessons = (int)$stats['completed_lessons'];
        $important_lessons = (int)$stats['important_lessons'];
        $theory_lessons = (int)$stats['theory_lessons'];
        $total_duration = (int)$stats['total_duration'];
        $completed_duration = (int)$stats['completed_duration'];

        return [
            'total_lessons' => $total_lessons,
            'completed_lessons' => $completed_lessons,
            'important_lessons' => $important_lessons,
            'theory_lessons' => $theory_lessons,
            'completed_percentage' => $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0,
            'important_percentage' => $total_lessons > 0 ? round(($important_lessons / $total_lessons) * 100) : 0,
            'theory_percentage' => $total_lessons > 0 ? round(($theory_lessons / $total_lessons) * 100) : 0,
            'total_duration_formatted' => formatDuration($total_duration),
            'completed_duration_formatted' => formatDuration($completed_duration),
            'remaining_duration_formatted' => formatDuration($total_duration - $completed_duration),
            'duration_percentage' => $total_duration > 0 ? round(($completed_duration / $total_duration) * 100) : 0
        ];

    } catch (Exception $e) {
        error_log("Error in getLessonsStats: " . $e->getMessage());
        return [
            'total_lessons' => 0,
            'completed_lessons' => 0,
            'important_lessons' => 0,
            'theory_lessons' => 0,
            'completed_percentage' => 0,
            'important_percentage' => 0,
            'theory_percentage' => 0,
            'total_duration_formatted' => '00:00:00',
            'completed_duration_formatted' => '00:00:00',
            'remaining_duration_formatted' => '00:00:00',
            'duration_percentage' => 0
        ];
    }
}
?> 