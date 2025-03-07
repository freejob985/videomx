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
    
    if (!isset($data['action'])) {
        throw new Exception('Action is required');
    }

    switch ($data['action']) {
        case 'add':
            if (!isset($data['lesson_id'], $data['title'], $data['content'], $data['type'])) {
                throw new Exception('Missing required fields');
            }

            $query = "INSERT INTO notes (lesson_id, title, content, type, code_language, link_url, link_description) 
                     VALUES (:lesson_id, :title, :content, :type, :code_language, :link_url, :link_description)";
            
            $stmt = $db->prepare($query);
            $stmt->bindValue(':lesson_id', $data['lesson_id']);
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':content', $data['content']);
            $stmt->bindValue(':type', $data['type']);
            $stmt->bindValue(':code_language', $data['code_language'] ?? null);
            $stmt->bindValue(':link_url', $data['link_url'] ?? null);
            $stmt->bindValue(':link_description', $data['link_description'] ?? null);
            
            $stmt->execute();
            $note_id = $db->lastInsertId();
            
            echo json_encode(['success' => true, 'note_id' => $note_id]);
            break;

        case 'update':
            if (!isset($data['note_id'])) {
                throw new Exception('Note ID is required');
            }

            $updates = [];
            $params = [':note_id' => $data['note_id']];

            if (isset($data['title'])) {
                $updates[] = "title = :title";
                $params[':title'] = $data['title'];
            }

            if (isset($data['content'])) {
                $updates[] = "content = :content";
                $params[':content'] = $data['content'];
            }

            if (isset($data['type'])) {
                $updates[] = "type = :type";
                $params[':type'] = $data['type'];
            }

            if (isset($data['code_language'])) {
                $updates[] = "code_language = :code_language";
                $params[':code_language'] = $data['code_language'];
            }

            if (isset($data['link_url'])) {
                $updates[] = "link_url = :link_url";
                $params[':link_url'] = $data['link_url'];
            }

            if (isset($data['link_description'])) {
                $updates[] = "link_description = :link_description";
                $params[':link_description'] = $data['link_description'];
            }

            $query = "UPDATE notes SET " . implode(', ', $updates) . " WHERE id = :note_id";
            $stmt = $db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            if (!isset($data['note_id'])) {
                throw new Exception('Note ID is required');
            }

            $query = "DELETE FROM notes WHERE id = :note_id";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':note_id', $data['note_id']);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 