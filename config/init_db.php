<?php
require_once 'config.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Obtenir une connexion à la base de données
$db = Database::getInstance();
$conn = $db->getConnection();

// Créer les tables nécessaires si elles n'existent pas
try {
    // Table des utilisateurs
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL,
            role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Table des services
    $conn->exec("
        CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50),
            image VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Table des projets/réalisations
    $conn->exec("
        CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            client_name VARCHAR(100),
            description TEXT,
            image VARCHAR(255),
            status ENUM('planned', 'in_progress', 'completed') DEFAULT 'planned',
            start_date DATE,
            end_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Table des demandes de devis
    $conn->exec("
        CREATE TABLE IF NOT EXISTS quote_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reference VARCHAR(20) NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            address TEXT,
            service_id INT,
            message TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
        )
    ");

    // Table des comparatifs
    $conn->exec("
        CREATE TABLE IF NOT EXISTS comparisons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            alu_description TEXT,
            pvc_description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    echo "Tables créées avec succès.<br>";

    // Vérifier si l'utilisateur admin existe déjà
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = (int)$stmt->fetchColumn();

    // Créer l'utilisateur admin s'il n'existe pas
    if ($adminExists === 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute(['admin', $adminPassword, 'admin@proaluetpvc.com']);
        echo "Utilisateur admin créé avec succès.<br>";
        echo "Nom d'utilisateur: admin<br>";
        echo "Mot de passe: admin123<br>";
        echo "<strong>IMPORTANT: Veuillez changer ce mot de passe dès que possible pour des raisons de sécurité.</strong><br>";
    } else {
        echo "L'utilisateur admin existe déjà.<br>";
    }

    // Ajouter des services par défaut si la table est vide
    $stmt = $conn->query("SELECT COUNT(*) FROM services");
    $servicesCount = (int)$stmt->fetchColumn();

    if ($servicesCount === 0) {
        $defaultServices = [
            [
                'name' => 'Fenêtres en Aluminium',
                'description' => 'Fenêtres en aluminium sur mesure, offrant durabilité et élégance pour votre maison.',
                'icon' => 'bi-window'
            ],
            [
                'name' => 'Portes en PVC',
                'description' => 'Portes en PVC de haute qualité, combinant isolation thermique et sécurité.',
                'icon' => 'bi-door-closed'
            ],
            [
                'name' => 'Vérandas',
                'description' => 'Vérandas personnalisées pour agrandir votre espace de vie avec style et luminosité.',
                'icon' => 'bi-house'
            ],
            [
                'name' => 'Volets Roulants',
                'description' => 'Volets roulants modernes pour une meilleure isolation et sécurité de votre habitation.',
                'icon' => 'bi-layout-sidebar'
            ],
            [
                'name' => 'Stores et Pergolas',
                'description' => 'Solutions d\'ombrage élégantes pour profiter pleinement de vos espaces extérieurs.',
                'icon' => 'bi-sun'
            ],
            [
                'name' => 'Garde-corps',
                'description' => 'Garde-corps en aluminium alliant sécurité et design contemporain pour vos balcons et terrasses.',
                'icon' => 'bi-border-style'
            ]
        ];

        $stmt = $conn->prepare("INSERT INTO services (name, description, icon) VALUES (?, ?, ?)");
        foreach ($defaultServices as $service) {
            $stmt->execute([$service['name'], $service['description'], $service['icon']]);
        }
        echo "Services par défaut ajoutés avec succès.<br>";
    }

    echo "<br><a href='../admin/login.php' class='btn btn-primary'>Aller à la page de connexion</a>";

} catch (PDOException $e) {
    die("Erreur lors de l'initialisation de la base de données: " . $e->getMessage());
}
?>
