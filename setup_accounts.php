<?php
// Script pour créer un administrateur et un client
require_once 'config/config.php';
require_once 'includes/db.php';

$message = '';

try {
    // Connexion à la base de données
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 1. Créer la table users si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    $message .= "La table 'users' a été vérifiée/créée avec succès.<br>";
    
    // 2. Vérifier si un administrateur existe déjà
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        // 3. Créer un administrateur
        $adminUsername = 'admin';
        $adminEmail = 'admin@proalu.com';
        $adminPassword = 'admin123';
        $adminHashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, name, role, created_at) 
                VALUES (?, ?, ?, ?, 'admin', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$adminUsername, $adminEmail, $adminHashedPassword, 'Administrateur']);
        
        $message .= "Un compte administrateur a été créé avec succès.<br>";
        $message .= "Nom d'utilisateur: <strong>admin</strong><br>";
        $message .= "Mot de passe: <strong>admin123</strong><br>";
    } else {
        $message .= "Un compte administrateur existe déjà.<br>";
    }
    
    // 4. Vérifier si un client existe déjà
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'client'");
    $clientCount = $stmt->fetchColumn();
    
    if ($clientCount == 0) {
        // 5. Créer un client
        $clientUsername = 'client';
        $clientEmail = 'client@example.com';
        $clientPassword = 'client123';
        $clientHashedPassword = password_hash($clientPassword, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, name, phone, address, role, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'client', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $clientUsername, 
            $clientEmail, 
            $clientHashedPassword, 
            'Jean Dupont',
            '06 12 34 56 78',
            '123 Rue de Paris, 75001 Paris'
        ]);
        
        $message .= "Un compte client a été créé avec succès.<br>";
        $message .= "Nom d'utilisateur: <strong>client</strong><br>";
        $message .= "Mot de passe: <strong>client123</strong><br>";
    } else {
        $message .= "Un compte client existe déjà.<br>";
    }
    
} catch (PDOException $e) {
    $message = "Erreur: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration des comptes - Pro Alu et PVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #2E7D32;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-success {
            background-color: #2E7D32;
            border-color: #2E7D32;
        }
        .btn-success:hover {
            background-color: #1B5E20;
            border-color: #1B5E20;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h3 class="mb-0">Configuration des comptes Pro Alu et PVC</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <h5>Résultat de la configuration</h5>
                            <p class="mb-0"><?php echo $message; ?></p>
                        </div>
                        
                        <h5 class="mb-3">Informations de connexion</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Espace Admin</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Nom d'utilisateur:</strong> admin</p>
                                        <p><strong>Mot de passe:</strong> admin123</p>
                                        <a href="admin/index.php" class="btn btn-primary">Accéder à l'espace admin</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">Espace Client</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Nom d'utilisateur:</strong> client</p>
                                        <p><strong>Mot de passe:</strong> client123</p>
                                        <a href="client/index.php" class="btn btn-success">Accéder à l'espace client</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
