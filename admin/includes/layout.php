<?php
// Vérifier si une session est déjà active avant d'en démarrer une nouvelle
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (vérifie les deux types de variables de session possibles)
if ((!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_logged_in'])) || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] !== true)) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard Admin'; ?> - Pro Alu et PVC</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --secondary-color: #333;
            --light-gray: #f8f9fa;
            --white: #ffffff;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light-gray);
            padding-top: 70px;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
            padding-right: 2rem;
            border-right: 1px solid #eee;
            margin-right: 1rem;
        }
        
        .navbar {
            padding: 0.5rem 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-nav {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
        }
        
        .nav-item {
            margin-right: 0.25rem;
        }
        
        .nav-link {
            color: var(--secondary-color);
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            transition: all 0.3s ease;
            border-radius: 4px;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(76, 175, 80, 0.1);
        }
        
        .nav-link .bi {
            margin-right: 5px;
        }
        
        .admin-content {
            min-height: calc(100vh - 70px);
            padding: 20px;
        }
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        /* Stats cards */
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .stats-icon i {
            color: white;
            font-size: 1.5rem;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: var(--white);
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        
        .table {
            vertical-align: middle;
        }
        
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
    <?php if (isset($additionalStyles)) echo $additionalStyles; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Pro Alu et PVC</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" aria-controls="navbarAdmin" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="index.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'services' ? 'active' : ''; ?>" href="services.php">
                            <i class="bi bi-gear"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>" href="projects.php">
                            <i class="bi bi-kanban"></i> Projets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'gallery' ? 'active' : ''; ?>" href="gallery.php">
                            <i class="bi bi-images"></i> Galerie
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'quotes' ? 'active' : ''; ?>" href="quotes.php">
                            <i class="bi bi-file-earmark-text"></i> Devis
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'clients' ? 'active' : ''; ?>" href="clients.php">
                            <i class="bi bi-people"></i> Clients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'messages' ? 'active' : ''; ?>" href="messages.php">
                            <i class="bi bi-chat-dots"></i> Messages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'materials' ? 'active' : ''; ?>" href="materials.php">
                            <i class="bi bi-box-seam"></i> Matériaux
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>" href="settings.php">
                            <i class="bi bi-sliders"></i> Paramètres
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid admin-content">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-0 fw-bold"><?php echo $pageTitle ?? 'Tableau de bord'; ?></h1>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Page Content -->
        <?php echo $content ?? ''; ?>
    </main>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html>
