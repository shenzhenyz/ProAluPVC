<?php
// Script pour créer un administrateur
require_once '../config/config.php';
require_once '../includes/db.php';

// Informations de l'administrateur
$username = 'admin';
$email = 'admin@proalu.com';
$password = 'admin123'; // Mot de passe simple pour les tests
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = 'admin';

try {
    // Connexion à la base de données
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Vérifier si l'administrateur existe déjà
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingAdmin) {
        // Mettre à jour le mot de passe de l'administrateur existant
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE email = ?");
        $stmt->execute([$username, $hashedPassword, $email]);
        echo "L'administrateur a été mis à jour avec succès.<br>";
        echo "Email: $email<br>";
        echo "Mot de passe: $password<br>";
    } else {
        // Créer un nouvel administrateur
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$username, $email, $hashedPassword, $role]);
        echo "Un nouvel administrateur a été créé avec succès.<br>";
        echo "Email: $email<br>";
        echo "Mot de passe: $password<br>";
    }
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}

// Créer la table users si elle n'existe pas
try {
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        last_login DATETIME NULL
    )";
    
    $conn->exec($sql);
    echo "La table 'users' a été vérifiée/créée avec succès.<br>";
} catch (PDOException $e) {
    echo "Erreur lors de la création de la table users: " . $e->getMessage();
}

echo "<br><a href='login.php'>Retour à la page de connexion</a>";
?>
