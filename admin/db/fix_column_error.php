<?php
// Script pour corriger l'erreur de colonne manquante 'start_date'

// Connexion à la base de données
try {
    $db = new PDO('mysql:host=localhost;dbname=proalu_pvc', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES utf8");
    
    echo "<h2>Connexion à la base de données réussie</h2>";
    
    // Vérifier si la table projects existe
    $stmt = $db->query("SHOW TABLES LIKE 'projects'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Vérifier si la colonne start_date existe
        $stmt = $db->query("SHOW COLUMNS FROM projects LIKE 'start_date'");
        $columnExists = $stmt->rowCount() > 0;
        
        if (!$columnExists) {
            // Ajouter la colonne start_date
            $db->exec("ALTER TABLE projects ADD COLUMN start_date DATE AFTER date");
            echo "<p>La colonne 'start_date' a été ajoutée à la table 'projects'</p>";
            
            // Mettre à jour les valeurs existantes
            $db->exec("UPDATE projects SET start_date = date WHERE start_date IS NULL");
            echo "<p>Les valeurs de 'start_date' ont été initialisées</p>";
        } else {
            echo "<p>La colonne 'start_date' existe déjà dans la table 'projects'</p>";
        }
    } else {
        // Créer la table projects si elle n'existe pas
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
        
        echo "<p>La table 'projects' a été créée avec la colonne 'start_date'</p>";
        
        // Insérer des données de test
        $db->exec("INSERT INTO `projects` (`title`, `client`, `description`, `date`, `start_date`, `status`) VALUES
            ('Rénovation Fenêtres Villa Méditerranée', 'M. Dupont', 'Remplacement de 8 fenêtres en aluminium', '2023-03-15', '2023-03-15', 'completed'),
            ('Installation Véranda Moderne', 'Mme Martin', 'Installation d\'une véranda de 20m²', '2023-04-02', '2023-04-02', 'in_progress'),
            ('Pose Volets Roulants Résidence Les Pins', 'Copropriété Les Pins', 'Installation de 24 volets roulants', '2023-04-10', '2023-04-10', 'in_progress'),
            ('Remplacement Porte d\'Entrée', 'M. Benali', 'Remplacement d\'une porte d\'entrée en PVC', '2023-04-25', '2023-04-25', 'planned')
        ");
        
        echo "<p>Des données de test ont été ajoutées à la table 'projects'</p>";
    }
    
    echo "<p><a href='../index.php'>Retour au tableau de bord</a></p>";
    
} catch(PDOException $e) {
    echo "<h2>Erreur de base de données</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p><a href='../index.php'>Retour au tableau de bord</a></p>";
}
?>
