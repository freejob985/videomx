<?php
/**
 * API Endpoints for Lesson Actions
 * ==============================
 * Handles AJAX requests for:
 * - Toggling completion status
 * - Toggling review status
 */

require_once '../includes/lesson-functions.php';

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['status' => 'error', 'message' => 'Method not allowed']));
}

// Get action and lesson ID
$action = $_POST['action'] ?? '';
$lessonId = (int)($_POST['lesson_id'] ?? 0);

// Validate lesson ID
if (!$lessonId) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => 'Invalid lesson ID']));
}

// Process actions
$response = [];
switch ($action) {
    case 'toggle_completion':
        $response = toggleLessonCompletion($lessonId);
        break;
        
    case 'toggle_review':
        $response = toggleLessonReview($lessonId);
        break;
        
    default:
        $response = [
            'status' => 'error',
            'message' => 'Invalid action'
        ];
        http_response_code(400);
}

// Send response
header('Content-Type: application/json');
echo json_encode($response); 