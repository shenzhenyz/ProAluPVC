<?php
// Script pour réparer la base de données et créer de nouveaux comptes
require_once 'config/config.php';
require_once 'includes/db.php';

$messages = [];

try {
    // Connexion à la base de données
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 1. Supprimer les comptes existants
    $conn->exec("DELETE FROM users WHERE email = 'admin@proalu.com'");
    $conn->exec("DELETE FROM clients WHERE email = 'client@proalu.com'");
    $messages[] = "Comptes existants supprimés avec succès.";
    
    // 2. Créer la table users si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        last_login DATETIME NULL
    )";
    $conn->exec($sql);
    $messages[] = "Table 'users' vérifiée/créée avec succès.";
    
    // 3. Créer la table clients si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS clients (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        address VARCHAR(255) NULL,
        city VARCHAR(100) NULL,
        postal_code VARCHAR(20) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        last_login DATETIME NULL
    )";
    $conn->exec($sql);
    $messages[] = "Table 'clients' vérifiée/créée avec succès.";
    
    // 4. Créer la table services si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS services (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT NULL,
        icon VARCHAR(50) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    $messages[] = "Table 'services' vérifiée/créée avec succès.";
    
    // 5. Créer la table quotes (devis) si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS quotes (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id INT(11) UNSIGNED NULL,
        service_id INT(11) UNSIGNED NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
    )";
    $conn->exec($sql);
    $messages[] = "Table 'quotes' vérifiée/créée avec succès.";
    
    // 6. Créer la table projects si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS projects (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id INT(11) UNSIGNED NULL,
        service_id INT(11) UNSIGNED NULL,
        quote_id INT(11) UNSIGNED NULL,
        title VARCHAR(100) NOT NULL,
        description TEXT NULL,
        status ENUM('planned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'planned',
        start_date DATE NULL,
        end_date DATE NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
        FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL
    )";
    $conn->exec($sql);
    $messages[] = "Table 'projects' vérifiée/créée avec succès.";
    
    // 7. Insérer un service par défaut
    $stmt = $conn->prepare("SELECT id FROM services WHERE name = 'Fenêtres PVC'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $conn->exec("INSERT INTO services (name, description, created_at) VALUES ('Fenêtres PVC', 'Installation et remplacement de fenêtres en PVC', NOW())");
        $messages[] = "Service par défaut créé avec succès.";
    }
    
    // 8. Créer un nouvel administrateur
    $adminUsername = 'admin_proalu';
    $adminEmail = 'admin@proalu.com';
    $adminPassword = 'admin123';
    $adminHashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
    $stmt->execute([$adminUsername, $adminEmail, $adminHashedPassword]);
    $messages[] = "Nouvel administrateur créé avec succès.";
    
    // 9. Créer un nouveau client
    $clientName = 'Client Test';
    $clientEmail = 'client@proalu.com';
    $clientPhone = '0600000000';
    $clientPassword = 'client123';
    $clientHashedPassword = password_hash($clientPassword, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO clients (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$clientName, $clientEmail, $clientPhone, $clientHashedPassword]);
    $messages[] = "Nouveau client créé avec succès.";
    
    // 10. Créer un devis de test pour le client
    $clientId = $conn->lastInsertId();
    $serviceId = $conn->query("SELECT id FROM services LIMIT 1")->fetchColumn();
    
    $stmt = $conn->prepare("INSERT INTO quotes (client_id, service_id, name, email, phone, message, status, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$clientId, $serviceId, $clientName, $clientEmail, $clientPhone, 'Demande de devis pour fenêtres PVC']);
    $messages[] = "Devis de test créé avec succès.";
    
    // 11. Créer un projet de test pour le client
    $quoteId = $conn->lastInsertId();
    $stmt = $conn->prepare("INSERT INTO projects (client_id, service_id, quote_id, title, description, status, created_at) 
                           VALUES (?, ?, ?, ?, ?, 'planned', NOW())");
    $stmt->execute([$clientId, $serviceId, $quoteId, 'Installation fenêtres', 'Installation de fenêtres PVC dans une maison']);
    $messages[] = "Projet de test créé avec succès.";
    
} catch (PDOException $e) {
    $messages[] = "Erreur: " . $e->getMessage();
}

// Modifier le fichier client/index.php pour ajouter une vérification des tables
try {
    $indexFile = file_get_contents('client/index.php');
    $pattern = '// Récupérer les demandes de devis du client';
    $replacement = '// Vérifier si les tables existent
try {
    // Récupérer les demandes de devis du client';
    
    $indexFile = str_replace($pattern, $replacement, $indexFile);
    
    $pattern = '$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);';
    $replacement = '$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la table n\'existe pas, initialiser un tableau vide
    if (strpos($e->getMessage(), "Table \'proalu_pvc.quotes\' doesn\'t exist") !== false) {
        $quotes = [];
    } else {
        throw $e;
    }
}';
    
    $indexFile = str_replace($pattern, $replacement, $indexFile);
    
    $pattern = '// Récupérer les projets en cours du client';
    $replacement = '// Récupérer les projets en cours du client
try {';
    
    $indexFile = str_replace($pattern, $replacement, $indexFile);
    
    $pattern = '$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);';
    if (strpos($indexFile, $pattern) !== false) {
        $replacement = '$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la table n\'existe pas, initialiser un tableau vide
    if (strpos($e->getMessage(), "Table \'proalu_pvc.projects\' doesn\'t exist") !== false) {
        $projects = [];
    } else {
        throw $e;
    }
}';
        
        $indexFile = str_replace($pattern, $replacement, $indexFile);
        file_put_contents('client/index.php', $indexFile);
        $messages[] = "Fichier client/index.php modifié avec succès pour gérer les tables manquantes.";
    } else {
        $messages[] = "Impossible de trouver le pattern dans client/index.php. Veuillez vérifier le fichier manuellement.";
    }
} catch (Exception $e) {
    $messages[] = "Erreur lors de la modification du fichier client/index.php: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réparation de la base de données - Pro Alu et PVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Réparation de la base de données</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h4>Opérations effectuées :</h4>
                            <ul class="mt-3">
                                <?php foreach ($messages as $message): ?>
                                    <li><?php echo $message; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="alert alert-info">
                            <h4>Identifiants de connexion :</h4>
                            <div class="mt-3">
                                <h5>Administrateur :</h5>
                                <ul>
                                    <li><strong>Email :</strong> admin@proalu.com</li>
                                    <li><strong>Mot de passe :</strong> admin123</li>
                                </ul>
                                
                                <h5>Client :</h5>
                                <ul>
                                    <li><strong>Email :</strong> client@proalu.com</li>
                                    <li><strong>Mot de passe :</strong> client123</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h4>Liens de connexion :</h4>
                            <ul class="list-group mt-3">
                                <li class="list-group-item">
                                    <a href="admin/login.php" class="btn btn-outline-success">Connexion Administrateur</a>
                                </li>
                                <li class="list-group-item">
                                    <a href="client/login.php" class="btn btn-outline-primary">Connexion Client</a>
                                </li>
                                <li class="list-group-item">
                                    <a href="index.php" class="btn btn-outline-secondary">Retour à l'accueil</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
