-- Script SQL pour cru00e9er la table projects

CREATE TABLE IF NOT EXISTS projects (
    id INT(11) NOT NULL AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    quote_id INT(11) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('planning', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'planning',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    progress INT(3) DEFAULT 0,
    budget DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
