<?php
// Script pour ajouter des services à la base de données
require_once 'config/config.php';
require_once 'includes/db.php';

// Obtenir une connexion à la base de données
$db = Database::getInstance();
$conn = $db->getConnection();

// Tableau pour stocker les messages
$messages = [];
$success = true;

try {
    // Vérifier si la table services existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'services'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table services
        $sql = "CREATE TABLE services (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            image_path VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
        $messages[] = "Table 'services' créée avec succès.";
    }
    
    // Liste des services à ajouter
    $services = [
        // Services Aluminium
        [
            'name' => 'Fenêtres en Aluminium',
            'description' => 'Installation et remplacement de fenêtres en aluminium, offrant une excellente isolation thermique et acoustique avec un design moderne.'
        ],
        [
            'name' => 'Portes en Aluminium',
            'description' => 'Portes d\'entrée et portes-fenêtres en aluminium, alliant sécurité, durabilité et esthétique contemporaine.'
        ],
        [
            'name' => 'Baies vitrées en Aluminium',
            'description' => 'Baies vitrées coulissantes ou à galandage en aluminium, pour un maximum de luminosité et une vue dégagée sur l\'extérieur.'
        ],
        [
            'name' => 'Vérandas en Aluminium',
            'description' => 'Conception et construction de vérandas en aluminium sur mesure, pour agrandir votre espace de vie.'
        ],
        [
            'name' => 'Pergolas en Aluminium',
            'description' => 'Pergolas bioclimatiques en aluminium, pour profiter de votre extérieur en toute saison.'
        ],
        
        // Services PVC
        [
            'name' => 'Fenêtres en PVC',
            'description' => 'Installation et remplacement de fenêtres en PVC, offrant un excellent rapport qualité-prix et une bonne isolation thermique.'
        ],
        [
            'name' => 'Portes en PVC',
            'description' => 'Portes d\'entrée et portes-fenêtres en PVC, combinant sécurité, isolation et facilité d\'entretien.'
        ],
        [
            'name' => 'Volets roulants en PVC',
            'description' => 'Installation de volets roulants en PVC, manuels ou motorisés, pour une meilleure isolation et sécurité.'
        ],
        [
            'name' => 'Portails en PVC',
            'description' => 'Portails et clôtures en PVC, résistants aux intempéries et nécessitant peu d\'entretien.'
        ],
        
        // Services Bois
        [
            'name' => 'Fenêtres en Bois',
            'description' => 'Fabrication et pose de fenêtres en bois, pour un charme traditionnel et une excellente isolation naturelle.'
        ],
        [
            'name' => 'Portes en Bois',
            'description' => 'Portes d\'entrée et portes intérieures en bois massif ou en placage, pour une ambiance chaleureuse et authentique.'
        ],
        [
            'name' => 'Escaliers en Bois',
            'description' => 'Conception et fabrication d\'escaliers en bois sur mesure, alliant esthétique et fonctionnalité.'
        ],
        [
            'name' => 'Terrasses en Bois',
            'description' => 'Construction de terrasses et platelages en bois, pour un espace extérieur naturel et accueillant.'
        ],
        
        // Services mixtes et autres
        [
            'name' => 'Menuiserie mixte Bois-Aluminium',
            'description' => 'Solutions de menuiserie mixte combinant la chaleur du bois à l\'intérieur et la résistance de l\'aluminium à l\'extérieur.'
        ],
        [
            'name' => 'Rénovation énergétique',
            'description' => 'Amélioration de la performance énergétique de votre habitat grâce au remplacement de vos menuiseries.'
        ],
        [
            'name' => 'Dépannage et SAV',
            'description' => 'Service après-vente et dépannage pour tous types de menuiseries (réglages, remplacement de pièces, etc.).'
        ]
    ];
    
    // Vérifier si des services existent déjà
    $stmt = $conn->query("SELECT COUNT(*) FROM services");
    $serviceCount = $stmt->fetchColumn();
    
    if ($serviceCount > 0) {
        // Supprimer les services existants pour éviter les doublons
        $conn->exec("TRUNCATE TABLE services");
        $messages[] = "Services existants supprimés pour éviter les doublons.";
    }
    
    // Ajouter les services
    $stmt = $conn->prepare("INSERT INTO services (name, description) VALUES (?, ?)");
    
    foreach ($services as $service) {
        $stmt->execute([$service['name'], $service['description']]);
    }
    
    $messages[] = count($services) . " services ont été ajoutés avec succès.";
    
    // Afficher un message de succès
    $finalMessage = "Les services ont été ajoutés avec succès à la base de données.";
    
} catch (PDOException $e) {
    $success = false;
    $finalMessage = "Erreur lors de l'ajout des services: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajout des services - Pro Alu et PVC</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h1 class="text-center mb-4">Ajout des services</h1>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $finalMessage; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $finalMessage; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($messages)): ?>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Détails des opérations</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group">
                                        <?php foreach ($messages as $message): ?>
                                            <li class="list-group-item">
                                                <i class="bi bi-arrow-right-circle me-2"></i> <?php echo $message; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Services ajoutés</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6 class="mb-3">Services Aluminium:</h6>
                                        <ul class="list-group mb-4">
                                            <li class="list-group-item">Fenêtres en Aluminium</li>
                                            <li class="list-group-item">Portes en Aluminium</li>
                                            <li class="list-group-item">Baies vitrées en Aluminium</li>
                                            <li class="list-group-item">Vérandas en Aluminium</li>
                                            <li class="list-group-item">Pergolas en Aluminium</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="mb-3">Services PVC:</h6>
                                        <ul class="list-group mb-4">
                                            <li class="list-group-item">Fenêtres en PVC</li>
                                            <li class="list-group-item">Portes en PVC</li>
                                            <li class="list-group-item">Volets roulants en PVC</li>
                                            <li class="list-group-item">Portails en PVC</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="mb-3">Services Bois:</h6>
                                        <ul class="list-group mb-4">
                                            <li class="list-group-item">Fenêtres en Bois</li>
                                            <li class="list-group-item">Portes en Bois</li>
                                            <li class="list-group-item">Escaliers en Bois</li>
                                            <li class="list-group-item">Terrasses en Bois</li>
                                        </ul>
                                    </div>
                                </div>
                                <h6 class="mb-3 mt-3">Services mixtes et autres:</h6>
                                <ul class="list-group">
                                    <li class="list-group-item">Menuiserie mixte Bois-Aluminium</li>
                                    <li class="list-group-item">Rénovation énergétique</li>
                                    <li class="list-group-item">Dépannage et SAV</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary me-2">Retour à l'accueil</a>
                            <a href="devis.php" class="btn btn-success">Demander un devis</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
