<?php
// Script pour corriger les problèmes de base de données
require_once 'config/config.php';
require_once 'includes/db.php';

// Obtenir une connexion à la base de données
$db = Database::getInstance();
$conn = $db->getConnection();

// Tableau pour stocker les messages
$messages = [];

try {
    // 1. Vérifier si la table users existe et a la bonne structure
    $tableExists = $conn->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table users
        $sql = "CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
        $messages[] = "Table 'users' créée avec succès.";
        
        // Insérer un utilisateur admin par défaut
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, email, role) VALUES 
                ('admin', :password, 'admin@proaluetpvc.com', 'admin')";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['password' => $hashedPassword]);
        $messages[] = "Utilisateur admin créé avec succès.";
    } else {
        // Vérifier si la colonne username existe
        $columnExists = $conn->query("SHOW COLUMNS FROM users LIKE 'username'")->rowCount() > 0;
        if (!$columnExists) {
            // Renommer la colonne name en username si elle existe
            $nameExists = $conn->query("SHOW COLUMNS FROM users LIKE 'name'")->rowCount() > 0;
            if ($nameExists) {
                $conn->exec("ALTER TABLE users CHANGE name username VARCHAR(50) NOT NULL");
                $messages[] = "Colonne 'name' renommée en 'username' dans la table 'users'.";
            } else {
                // Ajouter la colonne username si elle n'existe pas
                $conn->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) NOT NULL AFTER id");
                $messages[] = "Colonne 'username' ajoutée à la table 'users'.";
            }
        }
    }
    
    // 2. Vérifier si la table clients existe et a la bonne structure
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
    
    // 3. Vérifier si la table settings existe et a la bonne structure
    $tableExists = $conn->query("SHOW TABLES LIKE 'settings'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table settings
        $sql = "CREATE TABLE settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_group VARCHAR(50) NOT NULL,
            setting_key VARCHAR(50) NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (setting_group, setting_key)
        )";
        $conn->exec($sql);
        $messages[] = "Table 'settings' créée avec succès.";
    }
    
    // Afficher un message de succès
    $success = true;
    $finalMessage = "Toutes les corrections de base de données ont été effectuées avec succès.";
    
} catch (PDOException $e) {
    $success = false;
    $finalMessage = "Erreur lors de la correction de la base de données: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction de la base de données - Pro Alu et PVC</title>
    
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
                        <h1 class="text-center mb-4">Correction de la base de données</h1>
                        
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
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
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
