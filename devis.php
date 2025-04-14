<?php
require_once 'config/config.php';
require_once 'includes/db.php';

// Get services for dropdown
$db = Database::getInstance();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT id, name, description FROM services ORDER BY name");
$allServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Regrouper les services par catégorie
$categories = [
    'Aluminium' => [],
    'PVC' => [],
    'Bois' => [],
    'Autres' => []
];

foreach ($allServices as $service) {
    if (strpos($service['name'], 'Aluminium') !== false) {
        $categories['Aluminium'][] = $service;
    } elseif (strpos($service['name'], 'PVC') !== false) {
        $categories['PVC'][] = $service;
    } elseif (strpos($service['name'], 'Bois') !== false) {
        $categories['Bois'][] = $service;
    } else {
        $categories['Autres'][] = $service;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Devis - Pro Alu et PVC</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .service-option {
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            transition: all 0.3s ease;
        }
        .service-option:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-color: #4CAF50;
        }
        .service-option.selected {
            border-color: #4CAF50;
            background-color: rgba(76, 175, 80, 0.05);
        }
        .service-description {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .category-title {
            margin: 1.5rem 0 1rem 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
        }
        .category-title i {
            margin-right: 0.5rem;
        }
        .services-container {
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 500;
        }
        .category-icon-container {
            width: 40px;
            height: 40px;
            background-color: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }
        .category-icon {
            color: white;
            font-size: 1.25rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                Pro Alu et PVC
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#accueil">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="realisations.php">Réalisations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comparatif.php">Comparatif</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="client/login.php"><i class="bi bi-person-circle"></i> Client</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php"><i class="bi bi-lock"></i> Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-success btn-sm ms-2 active" href="devis.php">Demander un devis</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Quote Request Form -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-sm">
                        <div class="card-body p-5">
                            <h1 class="text-center mb-4">Demande de Devis</h1>
                            <p class="text-center text-muted mb-5">Complétez le formulaire ci-dessous pour recevoir un devis personnalisé pour votre projet de menuiserie.</p>
                            
                            <form id="quoteForm" action="includes/process-quote.php" method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Nom complet</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Téléphone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="address" class="form-label">Adresse du projet</label>
                                        <input type="text" class="form-control" id="address" name="address">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Service souhaité</label>
                                    <div class="services-container">
                                        <?php foreach ($categories as $category => $services): ?>
                                            <?php if (!empty($services)): ?>
                                                <h4 class="category-title">
                                                    <div class="category-icon-container">
                                                        <?php if ($category === 'Aluminium'): ?>
                                                            <i class="bi bi-grid-3x3-gap category-icon"></i>
                                                        <?php elseif ($category === 'PVC'): ?>
                                                            <i class="bi bi-window category-icon"></i>
                                                        <?php elseif ($category === 'Bois'): ?>
                                                            <i class="bi bi-tree category-icon"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-tools category-icon"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php echo $category; ?>
                                                </h4>
                                                <div class="row">
                                                    <?php foreach ($services as $service): ?>
                                                        <div class="col-md-6">
                                                            <div class="service-option">
                                                                <div class="form-check">
                                                                    <input class="form-check-input service-radio" type="radio" 
                                                                           name="service_id" id="service<?php echo $service['id']; ?>" 
                                                                           value="<?php echo htmlspecialchars($service['id']); ?>" required>
                                                                    <label class="form-check-label" for="service<?php echo $service['id']; ?>">
                                                                        <strong><?php echo htmlspecialchars($service['name']); ?></strong>
                                                                    </label>
                                                                    <div class="service-description">
                                                                        <?php echo htmlspecialchars($service['description']); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="message" class="form-label">Description de votre projet</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required 
                                              placeholder="Décrivez votre projet en détail (dimensions, matériaux, couleurs, etc.)"></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="budget" class="form-label">Budget approximatif (€)</label>
                                    <select class="form-select" id="budget" name="budget">
                                        <option value="">Sélectionnez une fourchette de budget</option>
                                        <option value="< 1000">Moins de 1 000 €</option>
                                        <option value="1000-3000">1 000 € - 3 000 €</option>
                                        <option value="3000-5000">3 000 € - 5 000 €</option>
                                        <option value="5000-10000">5 000 € - 10 000 €</option>
                                        <option value="10000-20000">10 000 € - 20 000 €</option>
                                        <option value="> 20000">Plus de 20 000 €</option>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="timeframe" class="form-label">Délai souhaité</label>
                                    <select class="form-select" id="timeframe" name="timeframe">
                                        <option value="">Sélectionnez un délai</option>
                                        <option value="< 1 mois">Moins d'un mois</option>
                                        <option value="1-3 mois">1 à 3 mois</option>
                                        <option value="3-6 mois">3 à 6 mois</option>
                                        <option value="> 6 mois">Plus de 6 mois</option>
                                    </select>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">Envoyer ma demande</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Sélection des services
        document.addEventListener('DOMContentLoaded', function() {
            const serviceOptions = document.querySelectorAll('.service-option');
            const serviceRadios = document.querySelectorAll('.service-radio');
            
            serviceRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Retirer la classe selected de toutes les options
                    serviceOptions.forEach(option => {
                        option.classList.remove('selected');
                    });
                    
                    // Ajouter la classe selected à l'option sélectionnée
                    if (this.checked) {
                        this.closest('.service-option').classList.add('selected');
                    }
                });
            });
            
            // Validation du formulaire
            const form = document.getElementById('quoteForm');
            form.addEventListener('submit', function(event) {
                const serviceSelected = document.querySelector('input[name="service_id"]:checked');
                if (!serviceSelected) {
                    event.preventDefault();
                    alert('Veuillez sélectionner un service');
                }
            });
        });
    </script>
</body>
</html>
