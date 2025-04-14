<?php
require_once 'config/config.php';
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro Alu et PVC - Menuiserie Aluminium et PVC</title>
    <meta name="description" content="Entreprise spécialisée dans la menuiserie aluminium et PVC à Alger. Services professionnels pour vos portes, fenêtres et vérandas.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .contact-info {
            margin-bottom: 20px;
        }
        .contact-info i {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .map iframe {
            height: 300px;
            border-radius: 10px;
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
                        <a class="nav-link active" href="#accueil">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="realisations.php">Réalisations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comparatif.php">Comparatif</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
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

    <!-- Hero Section -->
    <section id="accueil" class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content animate-fade-in">
                    <h1 class="display-1">Excellence en Menuiserie Bois, Aluminium & PVC</h1>
                    <p class="lead mb-4">Transformez votre espace avec nos solutions sur mesure. Qualité, durabilité et esthétique pour votre confort.</p>
                    <div class="d-flex gap-3">
                        <a href="devis.php" class="btn btn-success">Demander un devis</a>
                        <a href="#services" class="btn btn-outline-dark">Nos Services</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image animate-fade-in" style="animation-delay: 0.3s;">
                        <!-- Add your hero image here -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 mb-3">Nos Services</h2>
                <p class="lead text-muted">Des solutions adaptées à tous vos besoins en menuiserie</p>
            </div>
            <div class="row">
                <!-- Services will be loaded dynamically -->
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <img src="assets/images/Home.jpg" alt="Pourquoi nous choisir" class="img-fluid rounded-3">
                </div>
                <div class="col-lg-6">
                    <h2 class="display-4 mb-4">Pourquoi Nous Choisir </h2>
                    <div class="d-flex mb-4">
                        <div class="feature-icon me-4">
                            <i class="bi bi-check-circle-fill text-primary h1"></i>
                        </div>
                        <div>
                            <h3>Qualité Supérieure</h3>
                            <p>Nous utilisons uniquement des matériaux de première qualité pour garantir la durabilité et la performance de nos installations.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-4">
                        <div class="feature-icon me-4">
                            <i class="bi bi-people text-primary h1"></i>
                        </div>
                        <div>
                            <h3>Équipe Expérimentée</h3>
                            <p>Notre équipe de professionnels qualifiés assure une installation parfaite et un service impeccable.</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="feature-icon me-4">
                            <i class="bi bi-clock text-primary h1"></i>
                        </div>
                        <div>
                            <h3>Service Rapide</h3>
                            <p>Nous respectons les délais convenus et assurons une installation rapide et efficace.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Projects Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 mb-3">Nos Réalisations Récentes</h2>
                <p class="lead text-muted">Découvrez nos derniers projets réalisés</p>
            </div>
            <div class="row gallery">
                <!-- Gallery items will be loaded dynamically -->
            </div>
            <div class="text-center mt-4">
                <a href="realisations.php" class="btn btn-primary">Voir toutes nos réalisations</a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 mb-3">Contactez-Nous</h2>
                <p class="lead text-muted">Nous sommes à votre écoute pour répondre à toutes vos questions</p>
            </div>
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="map h-100">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3198.446061371613!2d3.0500857!3d36.7231133!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x128fb3e0b9454b05%3A0x3e35d5fc76f3c2f6!2sBirkhadem%2C%20Alger!5e0!3m2!1sfr!2sdz!4v1680123456789!5m2!1sfr!2sdz" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="bi bi-geo-alt text-primary mb-2" style="font-size: 1.5rem;"></i>
                                    <h5 class="card-title">Adresse</h5>
                                    <p class="card-text small">Lotissement El Salem 3, Villa N°1<br>Tahar Bouchet, Birkhadem, Alger</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="bi bi-telephone text-primary mb-2" style="font-size: 1.5rem;"></i>
                                    <h5 class="card-title">Téléphone</h5>
                                    <p class="card-text"><a href="tel:0551153023" class="text-decoration-none">0551 15 30 23</a></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="bi bi-envelope text-primary mb-2" style="font-size: 1.5rem;"></i>
                                    <h5 class="card-title">Email</h5>
                                    <p class="card-text small"><a href="mailto:doudou.kamel.pro@gmail.com" class="text-decoration-none">doudou.kamel.pro@gmail.com</a></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="bi bi-clock text-primary mb-2" style="font-size: 1.5rem;"></i>
                                    <h5 class="card-title">Horaires</h5>
                                    <p class="card-text small">Lun-Ven: 9h-18h<br>Sam: 9h-13h</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
