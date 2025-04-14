<?php require_once 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de Confidentialité - Pro Alu et PVC</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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
                        <a class="nav-link" href="index.php#realisations">Réalisations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#comparatif">Comparatif</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-success ms-2" href="devis.php">Demander un devis</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Privacy Policy Content -->
    <div class="container py-5">
        <h1 class="mb-4">Politique de Confidentialité</h1>
        
        <section class="mb-5">
            <h2>1. Collecte des informations</h2>
            <p>Nous collectons les informations suivantes lorsque vous utilisez notre site :</p>
            <ul>
                <li>Nom et prénom</li>
                <li>Adresse email</li>
                <li>Numéro de téléphone</li>
                <li>Informations relatives à votre projet</li>
            </ul>
        </section>

        <section class="mb-5">
            <h2>2. Utilisation des informations</h2>
            <p>Les informations que nous collectons sont utilisées pour :</p>
            <ul>
                <li>Traiter vos demandes de devis</li>
                <li>Vous contacter concernant nos services</li>
                <li>Améliorer notre site web et nos services</li>
                <li>Personnaliser votre expérience utilisateur</li>
            </ul>
        </section>

        <section class="mb-5">
            <h2>3. Protection des informations</h2>
            <p>Nous mettons en œuvre une variété de mesures de sécurité pour préserver la sécurité de vos informations personnelles. Nous utilisons un cryptage à la pointe de la technologie pour protéger les informations sensibles transmises en ligne.</p>
        </section>

        <section class="mb-5">
            <h2>4. Cookies</h2>
            <p>Notre site utilise des cookies pour améliorer votre expérience de navigation. Vous pouvez choisir de désactiver les cookies dans les paramètres de votre navigateur.</p>
        </section>

        <section class="mb-5">
            <h2>5. Divulgation à des tiers</h2>
            <p>Nous ne vendons, n'échangeons et ne transférons pas vos informations personnelles identifiables à des tiers. Cela ne comprend pas les tierces parties de confiance qui nous aident à exploiter notre site web ou à mener nos affaires, tant que ces parties conviennent de garder ces informations confidentielles.</p>
        </section>

        <section class="mb-5">
            <h2>6. Consentement</h2>
            <p>En utilisant notre site, vous consentez à notre politique de confidentialité.</p>
        </section>

        <section class="mb-5">
            <h2>7. Contact</h2>
            <p>Pour toute question concernant cette politique de confidentialité, vous pouvez nous contacter :</p>
            <ul>
                <li>Par email : <?php echo ADMIN_EMAIL; ?></li>
                <li>Par téléphone : 0551 15 30 23</li>
                <li>Par courrier : Lotissement El Salem 3, Villa N°1, Tahar Bouchet, Birkhadem, Alger</li>
            </ul>
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
