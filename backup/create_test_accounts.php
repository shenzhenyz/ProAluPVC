<?php
// Script pour créer des comptes de test
require_once 'config/config.php';
require_once 'includes/db.php';

// Obtenir une connexion à la base de données
$db = Database::getInstance();
$conn = $db->getConnection();

// Tableau pour stocker les messages
$messages = [];
$success = true;

try {
    // Vérifier si la base de données existe
    try {
        $conn->query("USE " . DB_NAME);
    } catch (PDOException $e) {
        // La base de données n'existe pas, la créer
        $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->exec("USE " . DB_NAME);
        $messages[] = "Base de données créée avec succès.";
    }
    
    // Vérifier si la table users existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    if (!$tableExists) {
        // Créer la table users
        $sql = "CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL,
            role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (email)
        )";
        $conn->exec($sql);
        $messages[] = "Table 'users' créée avec succès.";
    }
    
    // Vérifier si la table clients existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'clients'")->rowCount() > 0;
    if (!$tableExists) {
        // Créer la table clients
        $sql = "CREATE TABLE clients (
            id INT(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            postal_code VARCHAR(10) DEFAULT NULL,
            country VARCHAR(100) DEFAULT 'France',
            company VARCHAR(255) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            password VARCHAR(255) DEFAULT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY (email)
        )";
        $conn->exec($sql);
        $messages[] = "Table 'clients' créée avec succès.";
    }
    
    // 1. Créer un compte admin de test
    $adminEmail = 'admin@proaluetpvc.com';
    $adminPassword = 'Admin123!';
    $adminUsername = 'admin';
    
    // Vérifier si l'admin existe déjà
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $adminExists = $stmt->fetch();
    
    if (!$adminExists) {
        // Créer l'admin
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$adminUsername, $hashedPassword, $adminEmail]);
        $messages[] = "Compte administrateur créé avec succès.";
    } else {
        // Mettre à jour le mot de passe de l'admin
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$hashedPassword, $adminEmail]);
        $messages[] = "Mot de passe administrateur mis à jour avec succès.";
    }
    
    // 2. Créer un compte client de test
    $clientEmail = 'client@example.com';
    $clientPassword = 'Client123!';
    $clientName = 'Client Test';
    
    // Vérifier si le client existe déjà
    $stmt = $conn->prepare("SELECT * FROM clients WHERE email = ?");
    $stmt->execute([$clientEmail]);
    $clientExists = $stmt->fetch();
    
    if (!$clientExists) {
        // Créer le client
        $hashedPassword = password_hash($clientPassword, PASSWORD_DEFAULT);
        $sql = "INSERT INTO clients (name, email, password, phone, address, city, postal_code) 
                VALUES (?, ?, ?, '0123456789', '123 Rue Test', 'Ville Test', '75000')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$clientName, $clientEmail, $hashedPassword]);
        $messages[] = "Compte client créé avec succès.";
    } else {
        // Mettre à jour le mot de passe du client
        $hashedPassword = password_hash($clientPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE clients SET password = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$hashedPassword, $clientEmail]);
        $messages[] = "Mot de passe client mis à jour avec succès.";
    }
    
    // Afficher un message de succès
    $finalMessage = "Comptes de test créés avec succès.";
    
} catch (PDOException $e) {
    $success = false;
    $finalMessage = "Erreur lors de la création des comptes de test: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création de comptes de test - Pro Alu et PVC</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h1 class="text-center mb-4">Création de comptes de test</h1>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $finalMessage; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $finalMessage; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($messages)): ?>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Détails des opérations</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group">
                                        <?php foreach ($messages as $message): ?>
                                            <li class="list-group-item">
                                                <i class="bi bi-arrow-right-circle me-2"></i> <?php echo $message; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Identifiants de connexion</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="mb-3">Compte Administrateur:</h6>
                                <ul class="list-group mb-4">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><strong>Email:</strong></span>
                                        <span class="badge bg-secondary"><?php echo $adminEmail; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><strong>Mot de passe:</strong></span>
                                        <span class="badge bg-secondary"><?php echo $adminPassword; ?></span>
                                    </li>
                                </ul>
                                
                                <h6 class="mb-3">Compte Client:</h6>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><strong>Email:</strong></span>
                                        <span class="badge bg-secondary"><?php echo $clientEmail; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><strong>Mot de passe:</strong></span>
                                        <span class="badge bg-secondary"><?php echo $clientPassword; ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary me-2">Retour à l'accueil</a>
                            <a href="admin/login.php" class="btn btn-success me-2">Espace Admin</a>
                            <a href="client/login.php" class="btn btn-info">Espace Client</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
