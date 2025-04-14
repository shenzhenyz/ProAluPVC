<?php
require_once '../config/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("
        SELECT p.*, GROUP_CONCAT(pi.image_path) as additional_images
        FROM projects p
        LEFT JOIN project_images pi ON p.id = pi.project_id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process additional images
    foreach ($projects as &$project) {
        if ($project['additional_images']) {
            $project['additional_images'] = explode(',', $project['additional_images']);
        } else {
            $project['additional_images'] = [];
        }
    }
    
    echo json_encode($projects);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
