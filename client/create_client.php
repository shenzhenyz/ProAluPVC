<?php
// Script pour créer un client
require_once '../config/config.php';
require_once '../includes/db.php';

// Informations du client
$name = 'Client Test';
$email = 'client@proalu.com';
$phone = '0600000000';
$password = 'client123'; // Mot de passe simple pour les tests
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Connexion à la base de données
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Créer la table clients si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS clients (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        address VARCHAR(255) NULL,
        city VARCHAR(100) NULL,
        postal_code VARCHAR(20) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        last_login DATETIME NULL
    )";
    
    $conn->exec($sql);
    echo "La table 'clients' a été vérifiée/créée avec succès.<br>";
    
    // Vérifier si le client existe déjà
    $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    $existingClient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingClient) {
        // Mettre à jour le mot de passe du client existant
        $stmt = $conn->prepare("UPDATE clients SET name = ?, phone = ?, password = ? WHERE email = ?");
        $stmt->execute([$name, $phone, $hashedPassword, $email]);
        echo "Le client a été mis à jour avec succès.<br>";
        echo "Email: $email<br>";
        echo "Mot de passe: $password<br>";
    } else {
        // Créer un nouveau client
        $stmt = $conn->prepare("INSERT INTO clients (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $phone, $hashedPassword]);
        echo "Un nouveau client a été créé avec succès.<br>";
        echo "Email: $email<br>";
        echo "Mot de passe: $password<br>";
    }
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}

echo "<br><a href='login.php'>Retour à la page de connexion</a>";
?>
