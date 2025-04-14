<?php
// Script pour créer les tables manquantes dans la base de données

// Connexion à la base de données
try {
    $db = new PDO('mysql:host=localhost;dbname=proalu_pvc', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES utf8");
    
    echo "<h2>Connexion à la base de données réussie</h2>";
    
    // Lire le contenu du fichier SQL
    $sql = file_get_contents('create_tables.sql');
    
    // Exécuter les requêtes SQL
    $db->exec($sql);
    
    echo "<h3>Les tables ont été créées avec succès</h3>";
    echo "<p>Les tables suivantes ont été créées ou mises à jour :</p>";
    echo "<ul>";
    echo "<li>settings</li>";
    echo "<li>materials</li>";
    echo "</ul>";
    
    echo "<p><a href='../index.php'>Retour au tableau de bord</a></p>";
    
} catch(PDOException $e) {
    echo "<h2>Erreur de base de données</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p><a href='../index.php'>Retour au tableau de bord</a></p>";
}
?>
