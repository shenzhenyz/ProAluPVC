-- Script SQL pour cru00e9er la table materials

CREATE TABLE IF NOT EXISTS materials (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    unit VARCHAR(50) DEFAULT 'pièce',
    category VARCHAR(100) DEFAULT NULL,
    stock INT(11) DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
