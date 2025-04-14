<?php
// Script de test de connexion à la base de données
require_once 'config/config.php';

echo "<h1>Test de connexion à la base de données</h1>";
echo "<p>Tentative de connexion à la base de données avec les paramètres suivants :</p>";
echo "<ul>";
echo "<li>Hôte : " . DB_HOST . "</li>";
echo "<li>Utilisateur : " . DB_USER . "</li>";
echo "<li>Mot de passe : " . (empty(DB_PASS) ? "(vide)" : "****") . "</li>";
echo "<li>Base de données : " . DB_NAME . "</li>";
echo "</ul>";

try {
    // Test de connexion directe avec PDO
    echo "<h2>Test de connexion PDO directe</h2>";
    $start = microtime(true);
    $conn = new PDO("mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $end = microtime(true);
    $time = round(($end - $start) * 1000, 2);
    echo "<p style='color: green;'>✅ Connexion PDO réussie en $time ms</p>";
    
    // Vérifier si la base de données existe
    echo "<h2>Vérification de la base de données</h2>";
    $stmt = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ La base de données '" . DB_NAME . "' existe</p>";
    } else {
        echo "<p style='color: red;'>❌ La base de données '" . DB_NAME . "' n'existe pas</p>";
    }
    
    // Vérifier les tables
    echo "<h2>Vérification des tables</h2>";
    $conn->exec("USE " . DB_NAME);
    $tables = ['users', 'projects', 'quotes', 'messages', 'admin_messages'];
    $tableExists = [];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ La table '$table' existe</p>";
            $tableExists[$table] = true;
        } else {
            echo "<p style='color: red;'>❌ La table '$table' n'existe pas</p>";
            $tableExists[$table] = false;
        }
    }
    
    // Vérifier si la table users contient des données
    if ($tableExists['users']) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>La table 'users' contient $count enregistrements</p>";
        
        // Vérifier les rôles disponibles
        $stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        echo "<p>Répartition des utilisateurs par rôle :</p>";
        echo "<ul>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>Rôle '{$row['role']}' : {$row['count']} utilisateurs</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur de connexion : " . $e->getMessage() . "</p>";
    
    // Suggestions pour résoudre le problème
    echo "<h2>Suggestions pour résoudre le problème</h2>";
    echo "<ul>";
    if (strpos($e->getMessage(), "Access denied") !== false) {
        echo "<li>Vérifiez que le nom d'utilisateur et le mot de passe sont corrects</li>";
        echo "<li>Vérifiez que l'utilisateur a les droits d'accès à la base de données</li>";
    } elseif (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<li>La base de données '" . DB_NAME . "' n'existe pas. Vous devez la créer.</li>";
        echo "<li>Exécutez le script d'initialisation de la base de données : <a href='init_database.php'>init_database.php</a></li>";
    } elseif (strpos($e->getMessage(), "Connection refused") !== false) {
        echo "<li>Le serveur MySQL n'est pas démarré ou n'accepte pas les connexions</li>";
        echo "<li>Vérifiez que le service MySQL est en cours d'exécution dans XAMPP</li>";
        echo "<li>Essayez de redémarrer le service MySQL</li>";
    } elseif (strpos($e->getMessage(), "No such host") !== false) {
        echo "<li>L'hôte spécifié est incorrect</li>";
        echo "<li>Essayez d'utiliser '127.0.0.1' au lieu de 'localhost' ou vice versa</li>";
    } else {
        echo "<li>Vérifiez que le serveur MySQL est en cours d'exécution</li>";
        echo "<li>Vérifiez les paramètres de connexion dans le fichier config.php</li>";
        echo "<li>Essayez de redémarrer le service MySQL</li>";
    }
    echo "</ul>";
}

// Vérifier si le module mysqli est activé
echo "<h2>Vérification des modules PHP</h2>";
if (extension_loaded('mysqli')) {
    echo "<p style='color: green;'>✅ L'extension mysqli est activée</p>";
} else {
    echo "<p style='color: red;'>❌ L'extension mysqli n'est pas activée</p>";
}

if (extension_loaded('pdo_mysql')) {
    echo "<p style='color: green;'>✅ L'extension pdo_mysql est activée</p>";
} else {
    echo "<p style='color: red;'>❌ L'extension pdo_mysql n'est pas activée</p>";
}

// Informations sur le serveur
echo "<h2>Informations sur le serveur</h2>";
echo "<ul>";
echo "<li>Version PHP : " . phpversion() . "</li>";
echo "<li>Système d'exploitation : " . PHP_OS . "</li>";
echo "<li>Serveur Web : " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "</ul>";

echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
?>
