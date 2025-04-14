<?php
// Script pour créer la base de données MySQL
require_once 'config/config.php';

$host = DB_HOST;
$port = DB_PORT;
$user = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

// Messages de statut
$messages = [];
$success = false;

try {
    // Connexion à MySQL sans spécifier de base de données
    $conn = new PDO("mysql:host=$host;port=$port", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $messages[] = "Connexion au serveur MySQL réussie.";
    
    // Créer la base de données si elle n'existe pas
    $sql = "CREATE DATABASE IF NOT EXISTS $database";
    $conn->exec($sql);
    $messages[] = "Base de données '$database' créée ou déjà existante.";
    
    // Sélectionner la base de données
    $conn->exec("USE $database");
    $messages[] = "Base de données '$database' sélectionnée.";
    
    // Créer les tables nécessaires
    $tables = [
        "CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `email` varchar(100) NOT NULL,
            `name` varchar(100) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `address` text DEFAULT NULL,
            `role` enum('admin','client') NOT NULL DEFAULT 'client',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "CREATE TABLE IF NOT EXISTS `projects` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `title` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
            `progress` int(11) NOT NULL DEFAULT 0,
            `start_date` date DEFAULT NULL,
            `end_date` date DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "CREATE TABLE IF NOT EXISTS `quotes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `project_id` int(11) DEFAULT NULL,
            `title` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `amount` decimal(10,2) NOT NULL,
            `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `project_id` (`project_id`),
            CONSTRAINT `quotes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `quotes_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "CREATE TABLE IF NOT EXISTS `messages` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `sender_id` int(11) NOT NULL,
            `receiver_id` int(11) NOT NULL,
            `subject` varchar(100) NOT NULL,
            `content` text NOT NULL,
            `is_read` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `sender_id` (`sender_id`),
            KEY `receiver_id` (`receiver_id`),
            CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "CREATE TABLE IF NOT EXISTS `admin_messages` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `subject` varchar(100) NOT NULL,
            `content` text NOT NULL,
            `is_read` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `admin_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    // Créer les tables
    foreach ($tables as $sql) {
        $conn->exec($sql);
    }
    $messages[] = "Tables créées avec succès.";
    
    // Insérer des données de démonstration
    // Vérifier si des utilisateurs existent déjà
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        // Ajouter un administrateur
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->exec("INSERT INTO users (username, password, email, name, role) 
                    VALUES ('admin', '$adminPassword', 'admin@proaluetpvc.com', 'Administrateur', 'admin')");
        
        // Ajouter un client
        $clientPassword = password_hash('client123', PASSWORD_DEFAULT);
        $conn->exec("INSERT INTO users (username, password, email, name, phone, address, role) 
                    VALUES ('client', '$clientPassword', 'client@example.com', 'Jean Dupont', '06 12 34 56 78', '123 Rue de Paris, 75001 Paris', 'client')");
        
        // Récupérer les IDs
        $adminId = $conn->lastInsertId();
        $stmt = $conn->query("SELECT id FROM users WHERE username = 'client'");
        $clientId = $stmt->fetchColumn();
        
        // Ajouter des projets
        $conn->exec("INSERT INTO projects (user_id, title, description, status, progress, start_date, end_date) 
                    VALUES ($clientId, 'Rénovation fenêtres salon', 'Installation de 3 fenêtres en aluminium', 'in_progress', 60, '2023-03-15', '2023-04-30')");
        $project1Id = $conn->lastInsertId();
        
        $conn->exec("INSERT INTO projects (user_id, title, description, status, progress, start_date, end_date) 
                    VALUES ($clientId, 'Porte d\'entrée PVC', 'Remplacement de la porte d\'entrée', 'completed', 100, '2023-02-10', '2023-02-25')");
        $project2Id = $conn->lastInsertId();
        
        $conn->exec("INSERT INTO projects (user_id, title, description, status, progress, start_date, end_date) 
                    VALUES ($clientId, 'Véranda jardin', 'Installation d\'une véranda de 20m²', 'pending', 10, '2023-05-01', '2023-06-15')");
        $project3Id = $conn->lastInsertId();
        
        // Ajouter des devis
        $conn->exec("INSERT INTO quotes (user_id, project_id, title, description, amount, status) 
                    VALUES ($clientId, $project1Id, 'Fenêtres en aluminium pour salon', 'Fourniture et pose de 3 fenêtres en aluminium double vitrage', 2500.00, 'approved')");
        
        $conn->exec("INSERT INTO quotes (user_id, project_id, title, description, amount, status) 
                    VALUES ($clientId, $project2Id, 'Porte d\'entrée en PVC', 'Fourniture et pose d\'une porte d\'entrée en PVC avec serrure 3 points', 1200.00, 'approved')");
        
        $conn->exec("INSERT INTO quotes (user_id, project_id, title, description, amount, status) 
                    VALUES ($clientId, $project3Id, 'Véranda de jardin 20m²', 'Construction d\'une véranda de 20m² avec toit en polycarbonate', 8500.00, 'pending')");
        
        // Ajouter des messages
        $conn->exec("INSERT INTO messages (sender_id, receiver_id, subject, content, is_read) 
                    VALUES ($adminId, $clientId, 'Confirmation de rendez-vous', 'Bonjour et bienvenue chez Pro Alu et PVC. Nous confirmons notre rendez-vous du 15 avril pour la prise de mesures.', 0)");
        
        $conn->exec("INSERT INTO messages (sender_id, receiver_id, subject, content, is_read) 
                    VALUES ($clientId, $adminId, 'Question sur le devis', 'Bonjour, j\'aurais besoin de précisions concernant le devis des fenêtres. Merci.', 0)");
        
        // Ajouter des messages admin
        $conn->exec("INSERT INTO admin_messages (user_id, subject, content, is_read) 
                    VALUES ($clientId, 'Bienvenue chez Pro Alu et PVC', 'Bienvenue dans votre espace client. N\'hésitez pas à nous contacter pour toute question.', 0)");
        
        $messages[] = "Données de démonstration insérées avec succès.";
    } else {
        $messages[] = "Des données existent déjà dans la base de données.";
    }
    
    $success = true;
    
} catch (PDOException $e) {
    $messages[] = "Erreur: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création de la base de données MySQL - Pro Alu et PVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #2E7D32;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .success-message {
            color: #2E7D32;
        }
        .error-message {
            color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Création de la base de données MySQL - Pro Alu et PVC</h3>
            </div>
            <div class="card-body">
                <h4>Résultats de la création:</h4>
                <ul class="list-group mt-3">
                    <?php foreach ($messages as $message): ?>
                        <li class="list-group-item">
                            <?php if (strpos($message, 'Erreur') !== false): ?>
                                <i class="fas fa-times-circle text-danger me-2"></i>
                                <?php echo $message; ?>
                            <?php else: ?>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <?php echo $message; ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if ($success): ?>
                    <div class="alert alert-success mt-4">
                        <strong>Succès!</strong> La base de données MySQL a été créée. Vous pouvez maintenant accéder à l'application.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger mt-4">
                        <strong>Erreur!</strong> Des problèmes sont survenus lors de la création de la base de données. Veuillez vérifier les messages ci-dessus.
                    </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
                    <?php if ($success): ?>
                        <a href="client/login.php" class="btn btn-success">Accéder à l'espace client</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="mb-0">Informations de connexion</h4>
                </div>
                <div class="card-body">
                    <h5>Espace Admin:</h5>
                    <ul>
                        <li><strong>Nom d'utilisateur:</strong> admin</li>
                        <li><strong>Mot de passe:</strong> admin123</li>
                        <li><strong>URL:</strong> <a href="admin/login.php">admin/login.php</a></li>
                    </ul>
                    
                    <h5>Espace Client:</h5>
                    <ul>
                        <li><strong>Nom d'utilisateur:</strong> client</li>
                        <li><strong>Mot de passe:</strong> client123</li>
                        <li><strong>URL:</strong> <a href="client/login.php">client/login.php</a></li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
