<?php
require_once '../includes/functions.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST parameters
$lesson_id = $_POST['lesson_id'] ?? null;
$status_id = $_POST['status_id'] ?? null;

// Validate parameters
if (!$lesson_id || !is_numeric($lesson_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid lesson ID']);
    exit;
}

try {
    // Update lesson status in database
    $pdo = getConnection();
    $stmt = $pdo->prepare('UPDATE lessons SET status_id = ? WHERE id = ?');
    $result = $stmt->execute([$status_id, $lesson_id]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update lesson status']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} 