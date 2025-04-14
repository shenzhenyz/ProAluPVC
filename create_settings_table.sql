-- Script SQL pour cru00e9er la table settings

CREATE TABLE IF NOT EXISTS settings (
    id INT(11) NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(255) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    setting_group VARCHAR(100) DEFAULT 'general',
    description TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
