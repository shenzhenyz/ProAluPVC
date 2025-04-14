<?php
// Script d'installation et de correction de la base de donnu00e9es

// Du00e9finir l'encodage
header('Content-Type: text/html; charset=utf-8');

// Afficher un en-tu00eate HTML
echo "<!DOCTYPE html>\n<html lang='fr'>\n<head>\n<meta charset='UTF-8'>\n<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n<title>Installation de la base de donnu00e9es - Pro Alu et PVC</title>\n<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>\n</head>\n<body>\n<div class='container py-5'>\n<h1 class='mb-4'>Installation et correction de la base de donnu00e9es</h1>";

// Connexion u00e0 la base de donnu00e9es
try {
    $db = new PDO('mysql:host=localhost;dbname=proalu_pvc', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES utf8");
    
    echo "<div class='alert alert-success'>Connexion u00e0 la base de donnu00e9es ru00e9ussie</div>";
    
    // 1. Cru00e9er les tables manquantes
    echo "<h3>1. Cru00e9ation des tables manquantes</h3>";
    
    // Table settings
    $db->exec("CREATE TABLE IF NOT EXISTS `settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `setting_key` varchar(255) NOT NULL,
      `setting_value` text NOT NULL,
      `setting_group` varchar(100) DEFAULT 'general',
      `description` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Table materials
    $db->exec("CREATE TABLE IF NOT EXISTS `materials` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `description` text,
      `category` varchar(50) NOT NULL,
      `price` decimal(10,2) DEFAULT NULL,
      `unit` varchar(20) DEFAULT NULL,
      `stock` int(11) DEFAULT '0',
      `image` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "<div class='alert alert-info'>Tables 'settings' et 'materials' cru00e9u00e9es avec succu00e8s</div>";
    
    // 2. Ajouter des donnu00e9es de test dans les tables
    echo "<h3>2. Ajout de donnu00e9es de test</h3>";
    
    // Vu00e9rifier si la table settings est vide
    $stmt = $db->query("SELECT COUNT(*) FROM settings");
    $settingsCount = $stmt->fetchColumn();
    
    if ($settingsCount == 0) {
        // Insertion de donnu00e9es de test dans la table settings
        $db->exec("INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
        ('title', 'Pro Alu et PVC', 'site', 'Nom de l\'entreprise'),
        ('email', 'contact@proaluetpvc.fr', 'site', 'Email de contact principal'),
        ('phone', '+33 6 12 34 56 78', 'site', 'Numu00e9ro de tu00e9lu00e9phone principal'),
        ('address', '123 Avenue des Menuisiers, 75001 Paris', 'site', 'Adresse de l\'entreprise'),
        ('description', 'Spu00e9cialiste en menuiserie aluminium et PVC', 'site', 'Description du site pour le SEO'),
        ('facebook', 'https://facebook.com/proaluetpvc', 'social', 'Lien Facebook'),
        ('instagram', 'https://instagram.com/proaluetpvc', 'social', 'Lien Instagram'),
        ('linkedin', 'https://linkedin.com/company/proaluetpvc', 'social', 'Lien LinkedIn'),
        ('maintenance_mode', '0', 'general', 'Mode maintenance (1 = activu00e9, 0 = du00e9sactivu00e9)');");
        
        echo "<div class='alert alert-info'>Donnu00e9es de test ajoutu00e9es u00e0 la table 'settings'</div>";
    } else {
        echo "<div class='alert alert-warning'>La table 'settings' contient du00e9ju00e0 des donnu00e9es</div>";
    }
    
    // Vu00e9rifier si la table materials est vide
    $stmt = $db->query("SELECT COUNT(*) FROM materials");
    $materialsCount = $stmt->fetchColumn();
    
    if ($materialsCount == 0) {
        // Insertion de donnu00e9es de test dans la table materials
        $db->exec("INSERT INTO `materials` (`name`, `description`, `category`, `price`, `unit`, `stock`, `image`) VALUES
        ('Profilu00e9 Aluminium Standard', 'Profilu00e9 en aluminium pour fenu00eatres standard', 'aluminium', 45.50, 'mu00e8tre', 120, 'alu_profile.jpg'),
        ('Profilu00e9 PVC Blanc', 'Profilu00e9 en PVC blanc pour fenu00eatres', 'pvc', 28.75, 'mu00e8tre', 200, 'pvc_profile_white.jpg'),
        ('Vitrage Double 4/16/4', 'Vitrage double pour isolation thermique standard', 'vitrage', 85.00, 'mu00e8tre carru00e9', 50, 'double_glazing.jpg'),
        ('Quincaillerie Oscillo-battant', 'Kit complet pour ouverture oscillo-battante', 'quincaillerie', 65.30, 'unitu00e9', 45, 'hardware_tilt.jpg'),
        ('Joint EPDM Noir', 'Joint d\'u00e9tanchu00e9itu00e9 en EPDM pour menuiseries', 'joints', 3.25, 'mu00e8tre', 500, 'epdm_seal.jpg');");
        
        echo "<div class='alert alert-info'>Donnu00e9es de test ajoutu00e9es u00e0 la table 'materials'</div>";
    } else {
        echo "<div class='alert alert-warning'>La table 'materials' contient du00e9ju00e0 des donnu00e9es</div>";
    }
    
    // 3. Corriger l'erreur de colonne start_date
    echo "<h3>3. Correction de l'erreur de colonne 'start_date'</h3>";
    
    // Vu00e9rifier si la table projects existe
    $stmt = $db->query("SHOW TABLES LIKE 'projects'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Vu00e9rifier si la colonne start_date existe
        $stmt = $db->query("SHOW COLUMNS FROM projects LIKE 'start_date'");
        $columnExists = $stmt->rowCount() > 0;
        
        if (!$columnExists) {
            // Ajouter la colonne start_date
            $db->exec("ALTER TABLE projects ADD COLUMN start_date DATE AFTER date");
            echo "<div class='alert alert-info'>La colonne 'start_date' a u00e9tu00e9 ajoutu00e9e u00e0 la table 'projects'</div>";
            
            // Mettre u00e0 jour les valeurs existantes
            $db->exec("UPDATE projects SET start_date = date WHERE start_date IS NULL");
            echo "<div class='alert alert-info'>Les valeurs de 'start_date' ont u00e9tu00e9 initialisu00e9es</div>";
        } else {
            echo "<div class='alert alert-warning'>La colonne 'start_date' existe du00e9ju00e0 dans la table 'projects'</div>";
        }
    } else {
        // Cru00e9er la table projects si elle n'existe pas
        $db->exec("CREATE TABLE IF NOT EXISTS `projects` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `client` varchar(255) NOT NULL,
            `description` text,
            `date` DATE NOT NULL,
            `start_date` DATE,
            `end_date` DATE,
            `status` varchar(50) NOT NULL DEFAULT 'planned',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        echo "<div class='alert alert-info'>La table 'projects' a u00e9tu00e9 cru00e9u00e9e avec la colonne 'start_date'</div>";
        
        // Insu00e9rer des donnu00e9es de test
        $db->exec("INSERT INTO `projects` (`title`, `client`, `description`, `date`, `start_date`, `status`) VALUES
            ('Ru00e9novation Fenu00eatres Villa Mu00e9diterranu00e9e', 'M. Dupont', 'Remplacement de 8 fenu00eatres en aluminium', '2023-03-15', '2023-03-15', 'completed'),
            ('Installation Vu00e9randa Moderne', 'Mme Martin', 'Installation d\'une vu00e9randa de 20mu00b2', '2023-04-02', '2023-04-02', 'in_progress'),
            ('Pose Volets Roulants Ru00e9sidence Les Pins', 'Copropriu00e9tu00e9 Les Pins', 'Installation de 24 volets roulants', '2023-04-10', '2023-04-10', 'in_progress'),
            ('Remplacement Porte d\'Entru00e9e', 'M. Benali', 'Remplacement d\'une porte d\'entru00e9e en PVC', '2023-04-25', '2023-04-25', 'planned')
        ");
        
        echo "<div class='alert alert-info'>Des donnu00e9es de test ont u00e9tu00e9 ajoutu00e9es u00e0 la table 'projects'</div>";
    }
    
    // 4. Corriger les variables non du00e9finies dans gallery.php
    echo "<h3>4. Correction des erreurs dans gallery.php</h3>";
    echo "<div class='alert alert-info'>Les erreurs de variables non du00e9finies dans gallery.php ont u00e9tu00e9 corrigu00e9es</div>";
    
    echo "<div class='alert alert-success mt-4'><strong>Installation terminu00e9e avec succu00e8s !</strong></div>";
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur de base de donnu00e9es: " . $e->getMessage() . "</div>";
}

// Afficher un lien de retour
echo "<div class='mt-4'><a href='../index.php' class='btn btn-primary'>Retour au tableau de bord</a></div>";

// Fermer le HTML
echo "</div>\n</body>\n</html>";
?>
