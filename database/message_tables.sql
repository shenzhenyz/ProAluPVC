-- Table pour les messages envoyés par les clients à l'administration
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_type` enum('quote','project') DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `status` enum('read','unread') NOT NULL DEFAULT 'unread',
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table pour les réponses aux messages des clients
CREATE TABLE IF NOT EXISTS `message_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `sender_type` enum('admin','client') NOT NULL DEFAULT 'admin',
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table pour les messages envoyés par l'administration aux clients
CREATE TABLE IF NOT EXISTS `admin_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_type` enum('quote','project') DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `status` enum('read','unread') NOT NULL DEFAULT 'unread',
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table pour les réponses aux messages de l'administration
CREATE TABLE IF NOT EXISTS `admin_message_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `sender_type` enum('admin','client') NOT NULL DEFAULT 'client',
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ajout des contraintes de clé étrangère
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_client_id_fk` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

ALTER TABLE `message_replies`
  ADD CONSTRAINT `message_replies_message_id_fk` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

ALTER TABLE `admin_messages`
  ADD CONSTRAINT `admin_messages_client_id_fk` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

ALTER TABLE `admin_message_replies`
  ADD CONSTRAINT `admin_message_replies_message_id_fk` FOREIGN KEY (`message_id`) REFERENCES `admin_messages` (`id`) ON DELETE CASCADE;
