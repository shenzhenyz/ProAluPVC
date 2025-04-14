-- Script pour créer les tables manquantes

-- Table settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table materials
CREATE TABLE IF NOT EXISTS `materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `stock` int(11) DEFAULT '0',
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion de données de test dans la table settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_description`) VALUES
('company_name', 'Pro Alu et PVC', 'Nom de l\'entreprise'),
('company_email', 'contact@proaluetpvc.fr', 'Email de contact principal'),
('company_phone', '+33 6 12 34 56 78', 'Numéro de téléphone principal'),
('company_address', '123 Avenue des Menuisiers, 75001 Paris', 'Adresse de l\'entreprise'),
('site_description', 'Spécialiste en menuiserie aluminium et PVC', 'Description du site pour le SEO'),
('social_facebook', 'https://facebook.com/proaluetpvc', 'Lien Facebook'),
('social_instagram', 'https://instagram.com/proaluetpvc', 'Lien Instagram'),
('maintenance_mode', '0', 'Mode maintenance (1 = activé, 0 = désactivé)');

-- Insertion de données de test dans la table materials
INSERT IGNORE INTO `materials` (`name`, `description`, `category`, `price`, `unit`, `stock`, `image`) VALUES
('Profilé Aluminium Standard', 'Profilé en aluminium pour fenêtres standard', 'aluminium', 45.50, 'mètre', 120, 'alu_profile.jpg'),
('Profilé PVC Blanc', 'Profilé en PVC blanc pour fenêtres', 'pvc', 28.75, 'mètre', 200, 'pvc_profile_white.jpg'),
('Vitrage Double 4/16/4', 'Vitrage double pour isolation thermique standard', 'vitrage', 85.00, 'mètre carré', 50, 'double_glazing.jpg'),
('Quincaillerie Oscillo-battant', 'Kit complet pour ouverture oscillo-battante', 'quincaillerie', 65.30, 'unité', 45, 'hardware_tilt.jpg'),
('Joint EPDM Noir', 'Joint d\'étanchéité en EPDM pour menuiseries', 'joints', 3.25, 'mètre', 500, 'epdm_seal.jpg');
