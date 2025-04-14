<?php
require_once 'config/config.php';
require_once 'includes/db.php';

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Get all material comparisons
$stmt = $conn->query("SELECT * FROM material_comparisons ORDER BY material_type");
$comparisons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparatif des Matériaux - Pro Alu et PVC</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .comparison-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        
        .comparison-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .material-header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 1.5rem;
            border-radius: 8px 8px 0 0;
        }

        .material-content {
            padding: 1.5rem;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .price-range {
            background-color: var(--gray-light);
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
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
                        <a class="nav-link active" href="comparatif.php">Comparatif</a>
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
                        <a class="btn btn-success btn-sm ms-2" href="devis.php">Demander un devis</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Comparison Content -->
    <div class="container py-5">
        <h1 class="text-center mb-5">Comparatif des Matériaux</h1>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="alert alert-info mb-4">
                    <h4 class="alert-heading">Pourquoi comparer ?</h4>
                    <p>Choisir le bon matériau pour vos menuiseries est crucial pour garantir la durabilité, l'efficacité énergétique et l'esthétique de votre projet. Découvrez ci-dessous les caractéristiques de chaque matériau pour faire le meilleur choix.</p>
                </div>
            </div>
        </div>

        <div class="row">
            <?php foreach ($comparisons as $comparison): ?>
                <div class="col-lg-6 mb-4">
                    <div class="comparison-card">
                        <div class="material-header">
                            <h2 class="h3 mb-0"><?php echo htmlspecialchars($comparison['material_type']); ?></h2>
                        </div>
                        <div class="material-content">
                            <h3 class="h5 mb-3">Caractéristiques</h3>
                            <ul class="feature-list">
                                <?php 
                                $characteristics = explode("\n", $comparison['characteristics']);
                                foreach ($characteristics as $characteristic): ?>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i><?php echo htmlspecialchars($characteristic); ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <h3 class="h5 mb-3 mt-4">Avantages</h3>
                            <ul class="feature-list text-success">
                                <?php 
                                $advantages = explode("\n", $comparison['advantages']);
                                foreach ($advantages as $advantage): ?>
                                    <li><i class="bi bi-plus-circle me-2"></i><?php echo htmlspecialchars($advantage); ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <h3 class="h5 mb-3 mt-4">Inconvénients</h3>
                            <ul class="feature-list text-danger">
                                <?php 
                                $disadvantages = explode("\n", $comparison['disadvantages']);
                                foreach ($disadvantages as $disadvantage): ?>
                                    <li><i class="bi bi-dash-circle me-2"></i><?php echo htmlspecialchars($disadvantage); ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <div class="price-range">
                                <h3 class="h5 mb-2">Gamme de prix</h3>
                                <p class="mb-0"><strong><?php echo htmlspecialchars($comparison['price_range']); ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <p class="lead">Besoin d'aide pour choisir le matériau adapté à votre projet ?</p>
            <a href="devis.php" class="btn btn-success btn-sm">Demander un devis personnalisé</a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
