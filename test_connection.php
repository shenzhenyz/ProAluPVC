<?php
// Script simple pour tester la connexion à la base de données
require_once 'config/config.php';
require_once 'includes/db.php';

echo "<h1>Test de connexion à la base de données</h1>";
echo "<p>Tentative de connexion avec les paramètres suivants :</p>";
echo "<ul>";
echo "<li>Hôte : " . DB_HOST . "</li>";
echo "<li>Port : " . DB_PORT . "</li>";
echo "<li>Utilisateur : " . DB_USER . "</li>";
echo "<li>Mot de passe : " . (empty(DB_PASS) ? "(vide)" : "****") . "</li>";
echo "<li>Base de données : " . DB_NAME . "</li>";
echo "</ul>";

try {
    // Obtenir l'instance de la base de données
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Tester la connexion
    $stmt = $conn->query("SELECT 1");
    
    echo "<p style='color: green; font-weight: bold;'>✅ Connexion réussie !</p>";
    
    // Vérifier si la base de données existe
    try {
        $conn->exec("USE " . DB_NAME);
        echo "<p style='color: green;'>✅ Base de données '" . DB_NAME . "' accessible</p>";
        
        // Vérifier les tables
        $tables = ['users', 'projects', 'quotes', 'messages', 'admin_messages'];
        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✅ Table '$table' existe</p>";
                
                // Compter les enregistrements
                $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<p style='margin-left: 20px;'>- Nombre d'enregistrements : $count</p>";
            } else {
                echo "<p style='color: red;'>❌ Table '$table' n'existe pas</p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur lors de l'accès à la base de données : " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Erreur de connexion : " . $e->getMessage() . "</p>";
    
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
        echo "<li>Vérifiez que le port " . DB_PORT . " est bien celui utilisé par MySQL</li>";
        echo "<li>Essayez de redémarrer le service MySQL</li>";
    } else {
        echo "<li>Vérifiez que le serveur MySQL est en cours d'exécution</li>";
        echo "<li>Vérifiez que le port " . DB_PORT . " est correct</li>";
        echo "<li>Essayez de redémarrer le service MySQL</li>";
    }
    echo "</ul>";
}

// Ajouter des liens utiles
echo "<div style='margin-top: 20px;'>";
echo "<a href='init_database.php' style='margin-right: 10px;'>Initialiser la base de données</a> | ";
echo "<a href='client/login.php' style='margin: 0 10px;'>Espace client</a> | ";
echo "<a href='admin/login.php' style='margin: 0 10px;'>Espace admin</a> | ";
echo "<a href='index.php' style='margin-left: 10px;'>Retour à l'accueil</a>";
echo "</div>";
?>
