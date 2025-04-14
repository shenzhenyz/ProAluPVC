<?php
// Script pour initialiser la base de données et créer les tables nécessaires
// Configuration de la base de données
$host = '127.0.0.1';
$port = 3307;
$user = 'root';
$password = '';
$dbname = 'proaluetpvc';

// Messages de statut
$messages = [];

try {
    // Créer la connexion
    $conn = new PDO("mysql:host=$host;port=$port", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la base de données si elle n'existe pas
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    $conn->exec($sql);
    $messages[] = "Base de données '$dbname' créée ou déjà existante.";
    
    // Sélectionner la base de données
    $conn->exec("USE $dbname");
    
    // Créer la table users si elle n'existe pas
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
    $messages[] = "Table 'users' créée ou déjà existante.";
    
    // Vérifier si un utilisateur admin existe déjà
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        // Créer un utilisateur admin par défaut
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, email, name, role) 
                VALUES ('admin', :password, 'admin@proaluetpvc.com', 'Administrateur', 'admin')";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['password' => $hashedPassword]);
        $messages[] = "Utilisateur admin créé avec succès.";
    }
    
    // Vérifier si un utilisateur client existe déjà
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'client'");
    $clientCount = $stmt->fetchColumn();
    
    if ($clientCount == 0) {
        // Créer un utilisateur client par défaut
        $hashedPassword = password_hash('client123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, email, name, role) 
                VALUES ('client', :password, 'client@example.com', 'Jean Dupont', 'client')";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['password' => $hashedPassword]);
        $messages[] = "Utilisateur client créé avec succès.";
    }
    
    // Créer la table quote_requests si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS quote_requests (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        service VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    $messages[] = "Table 'quote_requests' créée ou déjà existante.";
    
    // Créer la table projects si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS projects (
        id INT PRIMARY KEY AUTO_INCREMENT,
        client_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        progress INT DEFAULT 0,
        start_date DATE,
        end_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    $messages[] = "Table 'projects' créée ou déjà existante.";
    
    // Créer la table quotes si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS quotes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        client_id INT NOT NULL,
        description TEXT NOT NULL,
        amount DECIMAL(10,2),
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    $messages[] = "Table 'quotes' créée ou déjà existante.";
    
    // Créer la table admin_messages si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS admin_messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        client_id INT NOT NULL,
        admin_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('read', 'unread') NOT NULL DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    $messages[] = "Table 'admin_messages' créée ou déjà existante.";
    
    // Créer la table messages si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        client_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('read', 'unread') NOT NULL DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    $messages[] = "Table 'messages' créée ou déjà existante.";
    
    // Vérifier si des données de démonstration existent déjà
    $stmt = $conn->query("SELECT COUNT(*) FROM projects");
    $projectCount = $stmt->fetchColumn();
    
    if ($projectCount == 0) {
        // Insérer des projets de démonstration
        $sql = "INSERT INTO projects (client_id, title, description, status, progress, start_date, end_date) VALUES 
                (2, 'Rénovation fenêtres salon', 'Installation de 3 fenêtres en aluminium', 'in_progress', 60, '2023-03-15', '2023-04-30'),
                (2, 'Porte d\'entrée PVC', 'Remplacement de la porte d\'entrée', 'completed', 100, '2023-02-10', '2023-02-25'),
                (2, 'Véranda jardin', 'Installation d\'une véranda de 20m²', 'pending', 10, '2023-05-01', '2023-06-15')";
        $conn->exec($sql);
        $messages[] = "Projets de démonstration créés avec succès.";
        
        // Insérer des devis de démonstration
        $sql = "INSERT INTO quotes (client_id, description, amount, status) VALUES 
                (2, 'Fenêtres en aluminium pour salon', 2500.00, 'approved'),
                (2, 'Porte d\'entrée en PVC', 1200.00, 'approved'),
                (2, 'Véranda de jardin 20m²', 8500.00, 'pending')";
        $conn->exec($sql);
        $messages[] = "Devis de démonstration créés avec succès.";
        
        // Insérer des messages de démonstration
        $sql = "INSERT INTO admin_messages (client_id, admin_id, subject, message, status) VALUES 
                (2, 1, 'Bienvenue chez Pro Alu et PVC', 'Bonjour et bienvenue dans votre espace client. N\'hésitez pas à nous contacter si vous avez des questions.', 'unread'),
                (2, 1, 'Confirmation de rendez-vous', 'Nous confirmons notre rendez-vous du 15 avril pour la prise de mesures.', 'unread')";
        $conn->exec($sql);
        $messages[] = "Messages de démonstration créés avec succès.";
    }
    
    $messages[] = "Initialisation de la base de données terminée avec succès.";
    
} catch(PDOException $e) {
    $messages[] = "Erreur: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialisation de la base de données - Pro Alu et PVC</title>
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
                <h3 class="mb-0">Initialisation de la base de données Pro Alu et PVC</h3>
            </div>
            <div class="card-body">
                <h4>Résultats de l'initialisation:</h4>
                <ul class="list-group mt-3">
                    <?php foreach ($messages as $message): ?>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <?php echo $message; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="alert alert-success mt-4">
                    <strong>Succès!</strong> La base de données a été initialisée. Vous pouvez maintenant accéder à l'application.
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
                    <a href="client/index.php" class="btn btn-success">Accéder à l'espace client</a>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h4 class="mb-0">Informations de connexion</h4>
            </div>
            <div class="card-body">
                <h5>Espace Admin:</h5>
                <ul>
                    <li><strong>Nom d'utilisateur:</strong> admin</li>
                    <li><strong>Mot de passe:</strong> admin123</li>
                    <li><strong>URL:</strong> <a href="admin/index.php">admin/index.php</a></li>
                </ul>
                
                <h5>Espace Client:</h5>
                <ul>
                    <li><strong>Nom d'utilisateur:</strong> client</li>
                    <li><strong>Mot de passe:</strong> client123</li>
                    <li><strong>URL:</strong> <a href="client/index.php">client/index.php</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
