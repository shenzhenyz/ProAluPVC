<?php
// Script de correction des problèmes de base de données
require_once 'config/config.php';
require_once 'includes/db.php';

// Obtenir la connexion à la base de données
$db = Database::getInstance();
$conn = $db->getConnection();

// Fonction pour vérifier si une table existe
function tableExists($conn, $tableName) {
    try {
        $result = $conn->query("SHOW TABLES LIKE '$tableName'");
        return $result->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Fonction pour vérifier si une colonne existe dans une table
function columnExists($conn, $tableName, $columnName) {
    try {
        $result = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        return $result->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Fonction pour créer une table si elle n'existe pas
function createTableIfNotExists($conn, $tableName, $createTableSQL) {
    if (!tableExists($conn, $tableName)) {
        try {
            $conn->exec($createTableSQL);
            echo "<div class='alert alert-success'>Table '$tableName' créée avec succès.</div>";
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Erreur lors de la création de la table '$tableName': " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>La table '$tableName' existe déjà.</div>";
    }
}

// Fonction pour ajouter une colonne si elle n'existe pas
function addColumnIfNotExists($conn, $tableName, $columnName, $columnDefinition) {
    if (tableExists($conn, $tableName) && !columnExists($conn, $tableName, $columnName)) {
        try {
            $conn->exec("ALTER TABLE `$tableName` ADD COLUMN `$columnName` $columnDefinition");
            echo "<div class='alert alert-success'>Colonne '$columnName' ajoutée à la table '$tableName'.</div>";
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Erreur lors de l'ajout de la colonne '$columnName' à la table '$tableName': " . $e->getMessage() . "</div>";
        }
    } else if (tableExists($conn, $tableName)) {
        echo "<div class='alert alert-info'>La colonne '$columnName' existe déjà dans la table '$tableName'.</div>";
    }
}

// Fonction pour renommer une table si nécessaire
function renameTableIfExists($conn, $oldTableName, $newTableName) {
    if (tableExists($conn, $oldTableName) && !tableExists($conn, $newTableName)) {
        try {
            $conn->exec("RENAME TABLE `$oldTableName` TO `$newTableName`");
            echo "<div class='alert alert-success'>Table '$oldTableName' renommée en '$newTableName'.</div>";
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Erreur lors du renommage de la table '$oldTableName' en '$newTableName': " . $e->getMessage() . "</div>";
        }
    }
}

// Fonction pour synchroniser les données entre deux tables
function syncTables($conn, $sourceTable, $targetTable, $matchColumn, $columnsToSync) {
    if (tableExists($conn, $sourceTable) && tableExists($conn, $targetTable)) {
        try {
            // Construire la requête UPDATE
            $updateSQL = "UPDATE `$targetTable` t1 JOIN `$sourceTable` t2 ON t1.`$matchColumn` = t2.`$matchColumn` SET ";
            $updateParts = [];
            foreach ($columnsToSync as $column) {
                $updateParts[] = "t1.`$column` = t2.`$column`";
            }
            $updateSQL .= implode(", ", $updateParts);
            
            // Exécuter la requête
            $stmt = $conn->prepare($updateSQL);
            $stmt->execute();
            
            echo "<div class='alert alert-success'>Synchronisation des données entre '$sourceTable' et '$targetTable' effectuée.</div>";
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Erreur lors de la synchronisation des données: " . $e->getMessage() . "</div>";
        }
    }
}

// Afficher l'en-tête HTML
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction de la base de données</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Correction des problèmes de base de données</h1>';

// 1. Vérifier et créer les tables nécessaires

// Table clients
$createClientsTable = "CREATE TABLE `clients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20),
    `address` TEXT,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
createTableIfNotExists($conn, 'clients', $createClientsTable);

// Table services
$createServicesTable = "CREATE TABLE `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `image` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
createTableIfNotExists($conn, 'services', $createServicesTable);

// Table quotes
$createQuotesTable = "CREATE TABLE `quotes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT,
    `service_id` INT,
    `description` TEXT,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL
)";
createTableIfNotExists($conn, 'quotes', $createQuotesTable);

// Table projects
$createProjectsTable = "CREATE TABLE `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT,
    `quote_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `status` ENUM('in_progress', 'completed', 'cancelled') DEFAULT 'in_progress',
    `progress` INT DEFAULT 0,
    `start_date` DATE,
    `end_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`quote_id`) REFERENCES `quotes`(`id`) ON DELETE SET NULL
)";
createTableIfNotExists($conn, 'projects', $createProjectsTable);

// Table messages
$createMessagesTable = "CREATE TABLE `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT,
    `admin_id` INT,
    `subject` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `sender_type` ENUM('admin', 'client') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
)";
createTableIfNotExists($conn, 'messages', $createMessagesTable);

// Table gallery
$createGalleryTable = "CREATE TABLE `gallery` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT,
    `image_path` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255),
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL
)";
createTableIfNotExists($conn, 'gallery', $createGalleryTable);

// 2. Vérifier et ajouter les colonnes manquantes

// Ajouter la colonne service_id à la table quotes si elle n'existe pas
addColumnIfNotExists($conn, 'quotes', 'service_id', 'INT, ADD CONSTRAINT `fk_quotes_service` FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL');

// Ajouter la colonne progress à la table projects si elle n'existe pas
addColumnIfNotExists($conn, 'projects', 'progress', 'INT DEFAULT 0');

// 3. Renommer les tables si nécessaire pour assurer la cohérence

// Vérifier si quote_requests existe et la renommer en quotes si nécessaire
renameTableIfExists($conn, 'quote_requests', 'quotes');

// 4. Synchroniser les données entre les tables si nécessaire

// Synchroniser les données entre quote_requests et quotes si les deux existent
if (tableExists($conn, 'quote_requests') && tableExists($conn, 'quotes')) {
    syncTables($conn, 'quote_requests', 'quotes', 'id', ['client_id', 'description', 'status', 'created_at']);
}

// Afficher un résumé des tables existantes
echo '<h2 class="mt-4">Tables existantes dans la base de données</h2>';
echo '<ul class="list-group mb-4">';
try {
    $tables = $conn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "<li class='list-group-item'>$table</li>";
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des tables: " . $e->getMessage() . "</div>";
}
echo '</ul>';

// Bouton pour retourner à l'administration
echo '<div class="mt-4">
    <a href="admin/index.php" class="btn btn-primary me-2">Aller à l\'administration</a>
    <a href="client/index.php" class="btn btn-success">Aller à l\'espace client</a>
</div>';

// Afficher le pied de page HTML
echo '</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
?>
