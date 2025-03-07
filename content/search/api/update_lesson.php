<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['lesson_id'])) {
        throw new Exception('Lesson ID is required');
    }

    $lesson_id = (int)$data['lesson_id'];
    $updates = [];
    $params = [':lesson_id' => $lesson_id];

    // Handle different update types
    if (isset($data['completed'])) {
        $updates[] = "completed = :completed";
        $params[':completed'] = (int)$data['completed'];
    }

    if (isset($data['is_important'])) {
        $updates[] = "is_important = :is_important";
        $params[':is_important'] = (int)$data['is_important'];
    }

    if (isset($data['is_theory'])) {
        $updates[] = "is_theory = :is_theory";
        $params[':is_theory'] = (int)$data['is_theory'];
    }

    if (isset($data['tags'])) {
        $updates[] = "tags = :tags";
        $params[':tags'] = $data['tags'];
    }

    if (empty($updates)) {
        throw new Exception('No updates provided');
    }

    // Build and execute update query
    $query = "UPDATE lessons SET " . implode(', ', $updates) . " WHERE id = :lesson_id";
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Lesson updated successfully']);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 