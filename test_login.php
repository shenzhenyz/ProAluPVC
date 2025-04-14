<?php
// Inclure les fichiers nécessaires
require_once 'config/config.php';
require_once 'includes/db.php';

// Initialiser la base de données
$db = Database::getInstance();
$conn = $db->getConnection();

// Tester la connexion client
$username = 'client';
$password = 'password'; // Mot de passe supposé pour le test

// Récupérer l'utilisateur
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'client'");
$stmt->execute([$username]);
$user = $stmt->fetch();

echo "<h1>Test de connexion client</h1>";

if ($user) {
    echo "<p>Utilisateur trouvé: " . htmlspecialchars($user['name']) . "</p>";
    
    // Vérifier le mot de passe
    if (password_verify($password, $user['password'])) {
        echo "<p style='color: green;'>Connexion réussie !</p>";
    } else {
        echo "<p style='color: red;'>Mot de passe incorrect</p>";
    }
} else {
    echo "<p style='color: red;'>Utilisateur non trouvé</p>";
}

// Tester la connexion admin
$username = 'admin';
$password = 'admin'; // Mot de passe supposé pour le test

// Récupérer l'utilisateur
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
$stmt->execute([$username]);
$user = $stmt->fetch();

echo "<h1>Test de connexion admin</h1>";

if ($user) {
    echo "<p>Utilisateur trouvé: " . htmlspecialchars($user['name']) . "</p>";
    
    // Vérifier le mot de passe
    if (password_verify($password, $user['password'])) {
        echo "<p style='color: green;'>Connexion réussie !</p>";
    } else {
        echo "<p style='color: red;'>Mot de passe incorrect</p>";
    }
} else {
    echo "<p style='color: red;'>Utilisateur non trouvé</p>";
}

// Afficher les tables disponibles
echo "<h1>Tables disponibles</h1>";
echo "<pre>";
print_r($db->getTables());
echo "</pre>";

// Afficher les données des utilisateurs
echo "<h1>Données des utilisateurs</h1>";
echo "<pre>";
$stmt = $conn->query("SELECT * FROM users");
$users = $stmt->fetchAll();
print_r($users);
echo "</pre>";
?>
