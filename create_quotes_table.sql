-- Script SQL pour cru00e9er la table quotes

CREATE TABLE IF NOT EXISTS quotes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('pending', 'accepted', 'rejected', 'expired') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expiry_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
