-- Script pour créer la table materials

-- Table materials
CREATE TABLE IF NOT EXISTS `materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion de données de test dans la table materials
INSERT IGNORE INTO `materials` (`name`, `description`, `category`, `price`, `stock`) VALUES
('Profilé Aluminium Standard', 'Profilé en aluminium pour fenêtres standard', 'Fenêtres', 45.50, 120),
('Profilé PVC Blanc', 'Profilé en PVC blanc pour fenêtres', 'Fenêtres', 28.75, 200),
('Vitrage Double 4/16/4', 'Vitrage double pour isolation thermique standard', 'Vitrage', 85.00, 50),
('Quincaillerie Oscillo-battant', 'Kit complet pour ouverture oscillo-battante', 'Accessoires', 65.30, 45),
('Joint EPDM Noir', 'Joint d\'étanchéité en EPDM pour menuiseries', 'Accessoires', 3.25, 500);
