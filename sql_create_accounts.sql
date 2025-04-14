-- Création de la table users si elle n'existe pas
CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    last_login DATETIME NULL
);

-- Création de la table clients si elle n'existe pas
CREATE TABLE IF NOT EXISTS clients (
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
);

-- Suppression des utilisateurs existants pour éviter les conflits
DELETE FROM users WHERE email = 'admin@proalu.com';
DELETE FROM clients WHERE email = 'client@proalu.com';

-- Création d'un compte administrateur
-- Mot de passe: admin123 (déjà hashé pour insertion directe)
INSERT INTO users (username, email, password, role, created_at) 
VALUES ('admin_proalu', 'admin@proalu.com', '$2y$10$Nt1f.UwQy5D.0VrLVzKZAONEkvzptt1XCEMgVBcXvd5LYiJPjKIyC', 'admin', NOW());

-- Création d'un compte client
-- Mot de passe: client123 (déjà hashé pour insertion directe)
INSERT INTO clients (name, email, phone, password, created_at)
VALUES ('Client Test', 'client@proalu.com', '0600000000', '$2y$10$0ypxmKBLe1JbSHSWpY0KAuSQJ.tn9XLzFtbdofFXGr/WRzyKxDJbO', NOW());
