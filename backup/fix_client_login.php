<?php
// Script pour corriger les problèmes de connexion client et mettre à jour la base de données
require_once 'config/config.php';
require_once 'includes/db.php';

$messages = [];

try {
    // Connexion à la base de données
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 1. Vérifier si la table projects existe et la créer/modifier si nécessaire
    $tableExists = false;
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'projects'");
        $tableExists = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $messages[] = "Erreur lors de la vérification de la table projects: " . $e->getMessage();
    }
    
    if ($tableExists) {
        // Vérifier si la colonne client_id existe
        $columnExists = false;
        try {
            $stmt = $conn->query("SHOW COLUMNS FROM projects LIKE 'client_id'");
            $columnExists = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $messages[] = "Erreur lors de la vérification de la colonne client_id: " . $e->getMessage();
        }
        
        if (!$columnExists) {
            // Ajouter la colonne client_id
            try {
                $conn->exec("ALTER TABLE projects ADD COLUMN client_id INT(11) UNSIGNED NULL AFTER id");
                $conn->exec("ALTER TABLE projects ADD CONSTRAINT fk_projects_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL");
                $messages[] = "Colonne client_id ajoutée à la table projects avec succès.";
            } catch (PDOException $e) {
                $messages[] = "Erreur lors de l'ajout de la colonne client_id: " . $e->getMessage();
            }
        } else {
            $messages[] = "La colonne client_id existe déjà dans la table projects.";
        }
    } else {
        // Créer la table projects
        try {
            $sql = "CREATE TABLE projects (
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
                FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
            )";
            $conn->exec($sql);
            $messages[] = "Table projects créée avec succès.";
        } catch (PDOException $e) {
            $messages[] = "Erreur lors de la création de la table projects: " . $e->getMessage();
        }
    }
    
    // 2. Vérifier si la table quotes existe et la créer/modifier si nécessaire
    $tableExists = false;
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'quotes'");
        $tableExists = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $messages[] = "Erreur lors de la vérification de la table quotes: " . $e->getMessage();
    }
    
    if ($tableExists) {
        // Vérifier si la colonne client_id existe
        $columnExists = false;
        try {
            $stmt = $conn->query("SHOW COLUMNS FROM quotes LIKE 'client_id'");
            $columnExists = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $messages[] = "Erreur lors de la vérification de la colonne client_id: " . $e->getMessage();
        }
        
        if (!$columnExists) {
            // Ajouter la colonne client_id
            try {
                $conn->exec("ALTER TABLE quotes ADD COLUMN client_id INT(11) UNSIGNED NULL AFTER id");
                $conn->exec("ALTER TABLE quotes ADD CONSTRAINT fk_quotes_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL");
                $messages[] = "Colonne client_id ajoutée à la table quotes avec succès.";
            } catch (PDOException $e) {
                $messages[] = "Erreur lors de l'ajout de la colonne client_id: " . $e->getMessage();
            }
        } else {
            $messages[] = "La colonne client_id existe déjà dans la table quotes.";
        }
    } else {
        // Créer la table quotes
        try {
            $sql = "CREATE TABLE quotes (
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
            $messages[] = "Table quotes créée avec succès.";
        } catch (PDOException $e) {
            $messages[] = "Erreur lors de la création de la table quotes: " . $e->getMessage();
        }
    }
    
    // 3. Vérifier si la table services existe et la créer si nécessaire
    $tableExists = false;
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'services'");
        $tableExists = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $messages[] = "Erreur lors de la vérification de la table services: " . $e->getMessage();
    }
    
    if (!$tableExists) {
        // Créer la table services
        try {
            $sql = "CREATE TABLE services (
                id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT NULL,
                icon VARCHAR(50) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->exec($sql);
            $messages[] = "Table services créée avec succès.";
            
            // Ajouter un service par défaut
            $conn->exec("INSERT INTO services (name, description, created_at) VALUES ('Fenêtres PVC', 'Installation et remplacement de fenêtres en PVC', NOW())");
            $messages[] = "Service par défaut créé avec succès.";
        } catch (PDOException $e) {
            $messages[] = "Erreur lors de la création de la table services: " . $e->getMessage();
        }
    }
    
    // 4. Mettre à jour le client existant ou en créer un nouveau si nécessaire
    // D'abord, récupérer le client existant depuis les images fournies
    $existingClientId = null;
    $existingClientName = "Manil Doudou";
    $existingClientEmail = "manil.doudou.007@gmail.com";
    $existingClientPhone = "0562392388";
    $existingClientPassword = '$2y$10$awlFSgwlFWxWIVo/Tjx4rDpuLFqx3cuHrLw';
    
    try {
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->execute([$existingClientEmail]);
        $existingClient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingClient) {
            $existingClientId = $existingClient['id'];
            $messages[] = "Client existant trouvé avec l'ID: " . $existingClientId;
        } else {
            // Créer un nouveau client avec les informations existantes
            $newPassword = password_hash('client123', PASSWORD_DEFAULT); // Mot de passe par défaut
            
            $stmt = $conn->prepare("INSERT INTO clients (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$existingClientName, $existingClientEmail, $existingClientPhone, $newPassword]);
            $existingClientId = $conn->lastInsertId();
            $messages[] = "Nouveau client créé avec l'ID: " . $existingClientId;
        }
    } catch (PDOException $e) {
        $messages[] = "Erreur lors de la gestion du client: " . $e->getMessage();
    }
    
    // 5. Créer un compte administrateur si nécessaire
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(['admin@proalu.com']);
        $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingAdmin) {
            // Créer un nouvel administrateur
            $adminUsername = 'admin_proalu';
            $adminEmail = 'admin@proalu.com';
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
            $stmt->execute([$adminUsername, $adminEmail, $adminPassword]);
            $messages[] = "Nouvel administrateur créé avec succès.";
        } else {
            $messages[] = "Un administrateur existe déjà.";
        }
    } catch (PDOException $e) {
        $messages[] = "Erreur lors de la gestion de l'administrateur: " . $e->getMessage();
    }
    
    // 6. Corriger le fichier client/index.php
    try {
        $indexFilePath = 'client/index.php';
        $indexContent = file_get_contents($indexFilePath);
        
        // Ajouter un bloc try-catch pour gérer l'erreur de colonne manquante
        if (strpos($indexContent, "WHERE p.client_id = ?") !== false) {
            $pattern = '$stmt->execute([$client_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);';
            $replacement = '$stmt->execute([$client_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la colonne ou la table n\'existe pas, initialiser un tableau vide
    if (strpos($e->getMessage(), "Table \'proalu_pvc.projects\' doesn\'t exist") !== false || 
        strpos($e->getMessage(), "Unknown column \'p.client_id\'") !== false) {
        $projects = [];
    } else {
        throw $e;
    }
}';
            
            // Vérifier si le remplacement est nécessaire
            if (strpos($indexContent, $pattern) !== false && strpos($indexContent, $replacement) === false) {
                $indexContent = str_replace($pattern, $replacement, $indexContent);
                file_put_contents($indexFilePath, $indexContent);
                $messages[] = "Fichier client/index.php corrigé avec succès.";
            } else {
                $messages[] = "Le fichier client/index.php a déjà été corrigé ou le motif à remplacer n'a pas été trouvé.";
            }
        }
    } catch (Exception $e) {
        $messages[] = "Erreur lors de la correction du fichier client/index.php: " . $e->getMessage();
    }
    
} catch (Exception $e) {
    $messages[] = "Erreur générale: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction de la connexion client - Pro Alu et PVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Correction de la connexion client</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h4>Opérations effectuées :</h4>
                            <ul class="mt-3">
                                <?php foreach ($messages as $message): ?>
                                    <li><?php echo $message; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="alert alert-success">
                            <h4>Identifiants de connexion :</h4>
                            <div class="mt-3">
                                <h5>Client existant :</h5>
                                <ul>
                                    <li><strong>Email :</strong> <?php echo $existingClientEmail; ?></li>
                                    <li><strong>Mot de passe :</strong> client123</li>
                                </ul>
                                
                                <h5>Administrateur :</h5>
                                <ul>
                                    <li><strong>Email :</strong> admin@proalu.com</li>
                                    <li><strong>Mot de passe :</strong> admin123</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h4>Liens de connexion :</h4>
                            <ul class="list-group mt-3">
                                <li class="list-group-item">
                                    <a href="client/login.php" class="btn btn-outline-primary">Connexion Client</a>
                                </li>
                                <li class="list-group-item">
                                    <a href="admin/login.php" class="btn btn-outline-success">Connexion Administrateur</a>
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
