<?php
// Script pour appliquer la configuration MySQL optimale

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configCode = $_POST['config_code'] ?? '';
    $dbCode = $_POST['db_code'] ?? '';
    $configIndex = $_POST['config_index'] ?? '';
    
    $message = '';
    $success = true;
    
    // Sauvegarder les fichiers existants
    if (file_exists('config/config.php')) {
        copy('config/config.php', 'config/config.php.bak');
        $message .= "Sauvegarde de config.php créée.<br>";
    }
    
    if (file_exists('includes/db.php')) {
        copy('includes/db.php', 'includes/db.php.bak');
        $message .= "Sauvegarde de db.php créée.<br>";
    }
    
    // Écrire le nouveau fichier de configuration
    if (!empty($configCode)) {
        if (file_put_contents('config/config.php', $configCode)) {
            $message .= "Fichier config.php mis à jour avec succès.<br>";
        } else {
            $message .= "Erreur lors de la mise à jour du fichier config.php.<br>";
            $success = false;
        }
    } else {
        $message .= "Code de configuration vide.<br>";
        $success = false;
    }
    
    // Écrire le nouveau fichier de connexion
    if (!empty($dbCode)) {
        if (file_put_contents('includes/db.php', $dbCode)) {
            $message .= "Fichier db.php mis à jour avec succès.<br>";
        } else {
            $message .= "Erreur lors de la mise à jour du fichier db.php.<br>";
            $success = false;
        }
    } else {
        $message .= "Code de connexion vide.<br>";
        $success = false;
    }
    
    // Initialiser la base de données
    if ($success) {
        try {
            require_once 'config/config.php';
            require_once 'includes/db.php';
            
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Créer les tables si elles n'existent pas
            $tables = [
                'users' => "CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    name VARCHAR(100) NOT NULL,
                    phone VARCHAR(20),
                    address TEXT,
                    role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                
                'projects' => "CREATE TABLE IF NOT EXISTS projects (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    title VARCHAR(100) NOT NULL,
                    description TEXT,
                    status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                    start_date DATE,
                    end_date DATE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )",
                
                'quotes' => "CREATE TABLE IF NOT EXISTS quotes (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    project_id INT,
                    title VARCHAR(100) NOT NULL,
                    description TEXT,
                    amount DECIMAL(10,2),
                    status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (project_id) REFERENCES projects(id)
                )",
                
                'messages' => "CREATE TABLE IF NOT EXISTS messages (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    sender_id INT NOT NULL,
                    receiver_id INT NOT NULL,
                    subject VARCHAR(100) NOT NULL,
                    content TEXT NOT NULL,
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (sender_id) REFERENCES users(id),
                    FOREIGN KEY (receiver_id) REFERENCES users(id)
                )",
                
                'admin_messages' => "CREATE TABLE IF NOT EXISTS admin_messages (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    subject VARCHAR(100) NOT NULL,
                    content TEXT NOT NULL,
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )"
            ];
            
            foreach ($tables as $table => $sql) {
                try {
                    $conn->exec($sql);
                    $message .= "Table '$table' créée ou vérifiée avec succès.<br>";
                } catch (PDOException $e) {
                    $message .= "Erreur lors de la création de la table '$table': " . $e->getMessage() . "<br>";
                }
            }
            
            // Vérifier si des utilisateurs existent
            $stmt = $conn->query("SELECT COUNT(*) FROM users");
            $userCount = $stmt->fetchColumn();
            
            if ($userCount == 0) {
                // Insérer un administrateur
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, email, name, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['admin', $adminPassword, 'admin@proalu.com', 'Administrateur', 'admin']);
                $message .= "Utilisateur administrateur créé avec succès.<br>";
                
                // Insérer un client
                $clientPassword = password_hash('client123', PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, email, name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute(['client', $clientPassword, 'client@example.com', 'Jean Dupont', '06 12 34 56 78', '123 Rue de Paris, 75001 Paris', 'client']);
                $message .= "Utilisateur client créé avec succès.<br>";
                
                // Insérer des projets pour le client
                $stmt = $conn->prepare("INSERT INTO projects (user_id, title, description, status, start_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([2, 'Rénovation fenêtres', 'Remplacement de 5 fenêtres PVC', 'in_progress', '2023-01-15']);
                $stmt->execute([2, 'Installation porte d\'entrée', 'Nouvelle porte d\'entrée en aluminium', 'pending', '2023-02-20']);
                $message .= "Projets de démonstration créés avec succès.<br>";
                
                // Insérer des devis pour le client
                $stmt = $conn->prepare("INSERT INTO quotes (user_id, project_id, title, description, amount, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([2, 1, 'Devis fenêtres PVC', 'Remplacement de 5 fenêtres PVC double vitrage', 3500.00, 'accepted']);
                $stmt->execute([2, 2, 'Devis porte d\'entrée', 'Porte d\'entrée en aluminium avec serrure 3 points', 1800.00, 'pending']);
                $message .= "Devis de démonstration créés avec succès.<br>";
                
                // Insérer des messages
                $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content) VALUES (?, ?, ?, ?)");
                $stmt->execute([1, 2, 'Confirmation de rendez-vous', 'Bonjour, je vous confirme notre rendez-vous pour la prise de mesures le 20 janvier à 14h.']);
                $stmt->execute([2, 1, 'Question sur le devis', 'Bonjour, j\'aurais besoin de précisions concernant le devis des fenêtres. Merci.']);
                $message .= "Messages de démonstration créés avec succès.<br>";
                
                // Insérer des messages admin
                $stmt = $conn->prepare("INSERT INTO admin_messages (user_id, subject, content) VALUES (?, ?, ?)");
                $stmt->execute([2, 'Bienvenue chez Pro Alu et PVC', 'Bienvenue dans votre espace client. N\'hésitez pas à nous contacter pour toute question.']);
                $message .= "Messages admin de démonstration créés avec succès.<br>";
            } else {
                $message .= "Des utilisateurs existent déjà dans la base de données.<br>";
            }
            
        } catch (PDOException $e) {
            $message .= "Erreur lors de l'initialisation de la base de données: " . $e->getMessage() . "<br>";
            $success = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application de la configuration MySQL - Pro Alu et PVC</title>
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
                        <h3 class="mb-0">Application de la configuration MySQL</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($message)): ?>
                            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> mb-4">
                                <h5>Résultat de l'opération</h5>
                                <p class="mb-0"><?php echo $message; ?></p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <p>Cette page doit être accédée depuis le formulaire de test des connexions MySQL.</p>
                            </div>
                        <?php endif; ?>
                        
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
                                        <a href="admin/login.php" class="btn btn-primary">Accéder à l'espace admin</a>
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
                                        <a href="client/login.php" class="btn btn-success">Accéder à l'espace client</a>
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
