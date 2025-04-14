-- Script SQL pour cru00e9er la table messages

CREATE TABLE IF NOT EXISTS messages (
    id INT(11) NOT NULL AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    admin_id INT(11) DEFAULT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reply_to INT(11) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_status TINYINT(1) DEFAULT 0,
    project_id INT(11) DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
