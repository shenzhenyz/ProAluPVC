<?php
// Script pour corriger les problèmes de l'espace client
require_once 'config/config.php';
require_once 'includes/db.php';

// Obtenir une connexion à la base de données
$db = Database::getInstance();
$conn = $db->getConnection();

// Tableau pour stocker les messages
$messages = [];

try {
    // 1. Vérifier si la table projects existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'projects'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table projects
        $sql = "CREATE TABLE projects (
            id INT PRIMARY KEY AUTO_INCREMENT,
            client_id INT NOT NULL,
            quote_id INT,
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
        $messages[] = "Table 'projects' créée avec succès.";
        
        // Insérer des projets de démonstration
        $sql = "INSERT INTO projects (client_id, title, description, status, progress, start_date, end_date) VALUES 
                (1, 'Rénovation fenêtres salon', 'Installation de 3 fenêtres en aluminium', 'in_progress', 60, '2023-03-15', '2023-04-30'),
                (1, 'Porte d\'entrée PVC', 'Remplacement de la porte d\'entrée', 'completed', 100, '2023-02-10', '2023-02-25'),
                (1, 'Véranda jardin', 'Installation d\'une véranda de 20m²', 'pending', 10, '2023-05-01', '2023-06-15')";
        $conn->exec($sql);
        $messages[] = "Projets de démonstration créés avec succès.";
    }
    
    // 2. Vérifier si la table admin_messages existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'admin_messages'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table admin_messages
        $sql = "CREATE TABLE admin_messages (
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
        $messages[] = "Table 'admin_messages' créée avec succès.";
        
        // Insérer des messages de démonstration
        $sql = "INSERT INTO admin_messages (client_id, admin_id, subject, message, status) VALUES 
                (1, 1, 'Bienvenue chez Pro Alu et PVC', 'Bonjour et bienvenue dans votre espace client. N\'hésitez pas à nous contacter si vous avez des questions.', 'unread'),
                (1, 1, 'Confirmation de rendez-vous', 'Nous confirmons notre rendez-vous du 15 avril pour la prise de mesures.', 'unread')";
        $conn->exec($sql);
        $messages[] = "Messages admin de démonstration créés avec succès.";
    }
    
    // 3. Vérifier si la table messages existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'messages'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table messages
        $sql = "CREATE TABLE messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            client_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('read', 'unread') NOT NULL DEFAULT 'unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $conn->exec($sql);
        $messages[] = "Table 'messages' créée avec succès.";
    }
    
    // 4. Vérifier si la table quotes existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'quotes'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table quotes
        $sql = "CREATE TABLE quotes (
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
        $messages[] = "Table 'quotes' créée avec succès.";
        
        // Insérer des devis de démonstration
        $sql = "INSERT INTO quotes (client_id, description, amount, status) VALUES 
                (1, 'Fenêtres en aluminium pour salon', 2500.00, 'approved'),
                (1, 'Porte d\'entrée en PVC', 1200.00, 'approved'),
                (1, 'Véranda de jardin 20m²', 8500.00, 'pending')";
        $conn->exec($sql);
        $messages[] = "Devis de démonstration créés avec succès.";
    }
    
    $messages[] = "Toutes les corrections ont été appliquées avec succès.";
    
} catch (PDOException $e) {
    $messages[] = "Erreur: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction de l'espace client - Pro Alu et PVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <h3 class="mb-0">Correction de l'espace client Pro Alu et PVC</h3>
            </div>
            <div class="card-body">
                <h4>Résultats des corrections:</h4>
                <ul class="list-group mt-3">
                    <?php foreach ($messages as $message): ?>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <?php echo $message; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="alert alert-success mt-4">
                    <strong>Succès!</strong> Les corrections ont été appliquées. Vous pouvez maintenant accéder à l'espace client.
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
                    <a href="client/index.php" class="btn btn-success">Accéder à l'espace client</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
