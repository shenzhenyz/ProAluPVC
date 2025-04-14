<?php
session_start();
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

header('Content-Type: application/json');

try {
    // Validate input
    if (empty($_POST['name']) || empty($_POST['description'])) {
        throw new Exception('Tous les champs sont requis');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT) : null;

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_info = pathinfo($_FILES['image']['name']);
        $extension = strtolower($file_info['extension']);

        // Validate file extension
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            throw new Exception('Type de fichier non autorisé');
        }

        // Generate unique filename
        $filename = uniqid() . '.' . $extension;
        $upload_path = UPLOAD_PATH . 'services/';

        // Create upload directory if it doesn't exist
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path . $filename)) {
            $image_path = 'uploads/services/' . $filename;
        } else {
            throw new Exception('Erreur lors du téléchargement de l\'image');
        }
    }

    if ($id) {
        // Update existing service
        $stmt = $conn->prepare("
            UPDATE services 
            SET name = :name, 
                description = :description" . 
                ($image_path ? ", image_path = :image_path" : "") . "
            WHERE id = :id
        ");
        
        $params = [
            ':name' => $name,
            ':description' => $description,
            ':id' => $id
        ];
        
        if ($image_path) {
            $params[':image_path'] = $image_path;
            
            // Delete old image if it exists
            $old_image = $conn->query("SELECT image_path FROM services WHERE id = $id")->fetchColumn();
            if ($old_image && file_exists(dirname(__DIR__) . '/' . $old_image)) {
                unlink(dirname(__DIR__) . '/' . $old_image);
            }
        }
        
        $stmt->execute($params);
        $message = 'Service mis à jour avec succès';
    } else {
        // Create new service
        $stmt = $conn->prepare("
            INSERT INTO services (name, description, image_path)
            VALUES (:name, :description, :image_path)
        ");
        
        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':image_path' => $image_path
        ]);
        $message = 'Service créé avec succès';
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
