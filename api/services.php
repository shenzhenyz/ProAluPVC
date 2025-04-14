<?php
require_once '../config/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SELECT * FROM services ORDER BY id");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add icon classes based on service type
    foreach ($services as &$service) {
        switch (strtolower($service['type'])) {
            case 'fenetre':
            case 'fenêtre':
                $service['icon'] = 'bi-window';
                break;
            case 'porte':
                $service['icon'] = 'bi-door-closed';
                break;
            case 'veranda':
            case 'véranda':
                $service['icon'] = 'bi-house';
                break;
            case 'volet':
                $service['icon'] = 'bi-shield';
                break;
            default:
                $service['icon'] = 'bi-tools';
        }
    }
    
    echo json_encode($services);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
