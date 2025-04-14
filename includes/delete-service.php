<?php
session_start();
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

// Validate service ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID de service invalide';
    header('Location: ../admin/services.php');
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get service image path before deletion
    $stmt = $conn->prepare("SELECT image_path FROM services WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $image_path = $stmt->fetchColumn();

    // Delete service from database
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    // Delete associated image file if it exists
    if ($image_path && file_exists(dirname(__DIR__) . '/' . $image_path)) {
        unlink(dirname(__DIR__) . '/' . $image_path);
    }

    $_SESSION['success'] = 'Service supprimé avec succès';

} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression du service: ' . $e->getMessage();
}

header('Location: ../admin/services.php');
exit;
?>
