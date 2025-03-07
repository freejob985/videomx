<?php
require_once __DIR__.'/../../config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch($action) {
    case 'add':
        // Handle course addition
        break;
    case 'edit':
        // Handle course editing
        break;
    case 'delete':
        // Handle course deletion
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
