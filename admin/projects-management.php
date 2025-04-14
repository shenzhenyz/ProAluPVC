<?php
session_start();
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Handle form submissions
$message = '';
$messageType = '';

// Add new project
if (isset($_POST['add_project'])) {
    $title = $_POST['title'] ?? '';
    $client_name = $_POST['client_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'planned';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    
    if (!empty($title) && !empty($client_name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO projects (title, client_name, description, status, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $client_name, $description, $status, $start_date, $end_date]);
            
            $message = 'Projet ajouté avec succès!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Erreur lors de l\'ajout du projet: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = 'Veuillez remplir tous les champs obligatoires.';
        $messageType = 'warning';
    }
}

// Update project status
if (isset($_POST['update_status'])) {
    $project_id = $_POST['project_id'] ?? 0;
    $new_status = $_POST['new_status'] ?? '';
    
    if ($project_id > 0 && !empty($new_status)) {
        try {
            $stmt = $conn->prepare("UPDATE projects SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $project_id]);
            
            $message = 'Statut du projet mis à jour avec succès!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Erreur lors de la mise à jour du statut: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Edit project
if (isset($_POST['edit_project'])) {
    $project_id = $_POST['project_id'] ?? 0;
    $title = $_POST['title'] ?? '';
    $client_name = $_POST['client_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? '';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $progress = $_POST['progress'] ?? 0;
    
    if ($project_id > 0 && !empty($title) && !empty($client_name)) {
        try {
            $stmt = $conn->prepare("UPDATE projects SET title = ?, client_name = ?, description = ?, status = ?, start_date = ?, end_date = ?, progress = ? WHERE id = ?");
            $stmt->execute([$title, $client_name, $description, $status, $start_date, $end_date, $progress, $project_id]);
            
            $message = 'Projet mis à jour avec succès!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Erreur lors de la mise à jour du projet: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = 'Veuillez remplir tous les champs obligatoires.';
        $messageType = 'warning';
    }
}

// Update project progress
if (isset($_POST['update_progress'])) {
    $project_id = $_POST['project_id'] ?? 0;
    $progress = $_POST['progress'] ?? 0;
    
    if ($project_id > 0 && is_numeric($progress) && $progress >= 0 && $progress <= 100) {
        try {
            $stmt = $conn->prepare("UPDATE projects SET progress = ? WHERE id = ?");
            $stmt->execute([$progress, $project_id]);
            
            $message = 'Progression du projet mise à jour avec succès!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Erreur lors de la mise à jour de la progression: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Delete project
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $project_id = $_GET['delete'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        
        $message = 'Projet supprimé avec succès!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Erreur lors de la suppression du projet: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Search projects
$search = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

// Get all projects with search and filter
$query = "SELECT * FROM projects WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR client_name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($filter_status)) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY 
    CASE 
        WHEN status = 'in_progress' THEN 1
        WHEN status = 'planned' THEN 2
        WHEN status = 'completed' THEN 3
        ELSE 4
    END, 
    start_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page variables
$pageTitle = 'Gestion des Chantiers';
$currentPage = 'projects-management';

// Export projects to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="projets_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Header
    fputcsv($output, ['ID', 'Titre', 'Client', 'Description', 'Statut', 'Date de début', 'Date de fin', 'Progression']);
    
    // CSV Data
    foreach ($projects as $project) {
        $status = getStatusLabel($project['status']);
        fputcsv($output, [
            $project['id'],
            $project['title'],
            $project['client_name'],
            $project['description'],
            $status,
            $project['start_date'],
            $project['end_date'],
            $project['progress'] . '%'
        ]);
    }
    
    fclose($output);
    exit;
}

// Fonction pour obtenir la classe de badge en fonction du statut
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'planned':
            return 'bg-warning';
        case 'in_progress':
            return 'bg-primary';
        case 'completed':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
}

// Fonction pour obtenir le libellé du statut en français
function getStatusLabel($status) {
    switch ($status) {
        case 'planned':
            return 'Planifié';
        case 'in_progress':
            return 'En cours';
        case 'completed':
            return 'Terminé';
        default:
            return 'Inconnu';
    }
}

// Statistiques
$stats = [
    'total' => count($projects),
    'planned' => count(array_filter($projects, function($p) { return $p['status'] === 'planned'; })),
    'in_progress' => count(array_filter($projects, function($p) { return $p['status'] === 'in_progress'; })),
    'completed' => count(array_filter($projects, function($p) { return $p['status'] === 'completed'; }))
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Chantiers - Pro Alu et PVC</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
            background-color: #f5f5f5;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            background-color: var(--secondary-color);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .sidebar-brand {
            padding: 10px 20px;
            margin-bottom: 20px;
        }
        
        .sidebar-brand img {
            max-width: 100%;
            height: auto;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
            margin-bottom: 5px;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white);
        }
        
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        /* Main content */
        .main-content {
            padding: 20px;
        }
        
        /* Header */
        .admin-header {
            background-color: var(--white);
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .admin-header h1 {
            font-size: 24px;
            margin-bottom: 0;
        }
        
        /* Stats Cards */
        .stats-card {
            background-color: var(--white);
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stats-card h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-card p {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        /* Project Cards */
        .project-card {
            background-color: var(--white);
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .project-card .card-header {
            border-bottom: none;
            padding: 15px 20px;
        }
        
        .project-card .card-body {
            padding: 20px;
        }
        
        .project-card .client-name {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .project-card .project-dates {
            font-size: 14px;
            color: #6c757d;
            margin-top: 10px;
        }
        
        .project-card .progress {
            height: 10px;
            margin-top: 15px;
        }
        
        /* Kanban Board */
        .kanban-column {
            background-color: var(--light-gray);
            border-radius: 5px;
            padding: 15px;
            height: 100%;
        }
        
        .kanban-column-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-brand">
                    <img src="../assets/images/logo.png" alt="Pro Alu et PVC" class="img-fluid">
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quotes.php">
                            <i class="bi bi-file-earmark-text"></i> Devis
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="projects-management.php">
                            <i class="bi bi-kanban"></i> Chantiers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-grid"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-images"></i> Galerie
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-gear"></i> Paramètres
                        </a>
                    </li>
                    <li class="nav-item mt-5">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto main-content">
                <!-- Header -->
                <div class="admin-header d-flex justify-content-between align-items-center">
                    <h1>Gestion des Chantiers</h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                            <i class="bi bi-plus-circle me-2"></i> Nouveau Chantier
                        </button>
                        <a href="?export=csv" class="btn btn-success">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i> Exporter en CSV
                        </a>
                    </div>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Search and Filter -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="GET" action="">
                            <div class="input-group">
                                <input type="search" class="form-control" name="search" placeholder="Rechercher un chantier...">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" action="">
                            <div class="input-group">
                                <select class="form-select" name="filter_status">
                                    <option value="">Tous les statuts</option>
                                    <option value="planned">Planifié</option>
                                    <option value="in_progress">En cours</option>
                                    <option value="completed">Terminé</option>
                                </select>
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-filter"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-6 col-lg-3">
                        <div class="stats-card">
                            <div class="icon">
                                <i class="bi bi-building"></i>
                            </div>
                            <h3><?php echo $stats['total']; ?></h3>
                            <p>Total des chantiers</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stats-card">
                            <div class="icon">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <h3><?php echo $stats['planned']; ?></h3>
                            <p>Planifiés</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stats-card">
                            <div class="icon">
                                <i class="bi bi-gear"></i>
                            </div>
                            <h3><?php echo $stats['in_progress']; ?></h3>
                            <p>En cours</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stats-card">
                            <div class="icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h3><?php echo $stats['completed']; ?></h3>
                            <p>Terminés</p>
                        </div>
                    </div>
                </div>
                
                <!-- Kanban Board -->
                <div class="row">
                    <!-- Planifiés -->
                    <div class="col-md-4 mb-4">
                        <div class="kanban-column">
                            <div class="kanban-column-header">
                                <h5 class="d-flex align-items-center">
                                    <span class="badge bg-warning me-2">
                                        <?php echo $stats['planned']; ?>
                                    </span>
                                    Planifiés
                                </h5>
                            </div>
                            
                            <?php foreach ($projects as $project): ?>
                                <?php if ($project['status'] === 'planned'): ?>
                                    <div class="project-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                                            <p class="client-name">
                                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($project['client_name']); ?>
                                            </p>
                                            <p class="card-text">
                                                <?php echo substr(htmlspecialchars($project['description']), 0, 100); ?>...
                                            </p>
                                            <p class="project-dates">
                                                <i class="bi bi-calendar"></i> 
                                                <?php echo date('d/m/Y', strtotime($project['start_date'])); ?> - 
                                                <?php echo date('d/m/Y', strtotime($project['end_date'])); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                                    <i class="bi bi-eye"></i> Détails
                                                </button>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                    <input type="hidden" name="new_status" value="in_progress">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-success">
                                                        <i class="bi bi-arrow-right"></i> Démarrer
                                                    </button>
                                                </form>
                                                <a href="?delete=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-danger delete-project">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- En cours -->
                    <div class="col-md-4 mb-4">
                        <div class="kanban-column">
                            <div class="kanban-column-header">
                                <h5 class="d-flex align-items-center">
                                    <span class="badge bg-primary me-2">
                                        <?php echo $stats['in_progress']; ?>
                                    </span>
                                    En cours
                                </h5>
                            </div>
                            
                            <?php foreach ($projects as $project): ?>
                                <?php if ($project['status'] === 'in_progress'): ?>
                                    <div class="project-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                                            <p class="client-name">
                                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($project['client_name']); ?>
                                            </p>
                                            <p class="card-text">
                                                <?php echo substr(htmlspecialchars($project['description']), 0, 100); ?>...
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small>Progression: <?php echo $project['progress']; ?>%</small>
                                                <small><?php echo date('d/m/Y', strtotime($project['end_date'])); ?></small>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $project['progress']; ?>%" aria-valuenow="<?php echo $project['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                                    <i class="bi bi-eye"></i> Détails
                                                </button>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                    <input type="hidden" name="new_status" value="completed">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-circle"></i> Terminer
                                                    </button>
                                                </form>
                                                <form method="POST" action="" class="progress-form">
                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                    <input type="number" class="form-control form-control-sm" name="progress" value="<?php echo $project['progress']; ?>" min="0" max="100">
                                                    <button type="submit" name="update_progress" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-arrow-repeat"></i> Mettre à jour
                                                    </button>
                                                </form>
                                                <a href="?delete=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-danger delete-project">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Terminés -->
                    <div class="col-md-4 mb-4">
                        <div class="kanban-column">
                            <div class="kanban-column-header">
                                <h5 class="d-flex align-items-center">
                                    <span class="badge bg-success me-2">
                                        <?php echo $stats['completed']; ?>
                                    </span>
                                    Terminés
                                </h5>
                            </div>
                            
                            <?php foreach ($projects as $project): ?>
                                <?php if ($project['status'] === 'completed'): ?>
                                    <div class="project-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                                            <p class="client-name">
                                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($project['client_name']); ?>
                                            </p>
                                            <p class="card-text">
                                                <?php echo substr(htmlspecialchars($project['description']), 0, 100); ?>...
                                            </p>
                                            <p class="project-dates">
                                                <i class="bi bi-calendar-check"></i> Terminé le 
                                                <?php echo date('d/m/Y', strtotime($project['end_date'])); ?>
                                            </p>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                                    <i class="bi bi-eye"></i> Détails
                                                </button>
                                                <button class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-file-earmark-text"></i> Rapport
                                                </button>
                                                <a href="?delete=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-danger delete-project">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Project Modals -->
    <?php foreach ($projects as $project): ?>
    <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="projectModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="projectModalLabel<?php echo $project['id']; ?>">Détails du Chantier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4><?php echo htmlspecialchars($project['title']); ?></h4>
                    
                    <div class="mb-3">
                        <span class="badge <?php echo getStatusBadgeClass($project['status']); ?>">
                            <?php echo getStatusLabel($project['status']); ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <span class="fw-bold">Client:</span>
                        <p><?php echo htmlspecialchars($project['client_name']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <span class="fw-bold">Description:</span>
                        <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <span class="fw-bold">Dates:</span>
                        <p>
                            Début: <?php echo date('d/m/Y', strtotime($project['start_date'])); ?><br>
                            Fin prévue: <?php echo date('d/m/Y', strtotime($project['end_date'])); ?>
                        </p>
                    </div>
                    
                    <?php if ($project['status'] === 'in_progress'): ?>
                    <div class="mb-3">
                        <span class="fw-bold">Progression:</span>
                        <div class="progress mt-2">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $project['progress']; ?>%" aria-valuenow="<?php echo $project['progress']; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo $project['progress']; ?>%
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <span class="fw-bold">Changer le statut:</span>
                        <div class="d-flex gap-2 mt-2">
                            <select class="form-select" id="statusChange<?php echo $project['id']; ?>">
                                <option value="planned" <?php echo $project['status'] === 'planned' ? 'selected' : ''; ?>>Planifié</option>
                                <option value="in_progress" <?php echo $project['status'] === 'in_progress' ? 'selected' : ''; ?>>En cours</option>
                                <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                            </select>
                            <form method="POST" action="">
                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                <input type="hidden" name="new_status" id="newStatus<?php echo $project['id']; ?>">
                                <button type="submit" name="update_status" class="btn btn-primary">Mettre à jour</button>
                            </form>
                            <script>
                                document.getElementById('statusChange<?php echo $project['id']; ?>').addEventListener('change', function() {
                                    document.getElementById('newStatus<?php echo $project['id']; ?>').value = this.value;
                                });
                            </script>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <span class="fw-bold">Modifier le projet:</span>
                        <form method="POST" action="" class="edit-project-form">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <div class="mb-3">
                                <label for="projectTitle<?php echo $project['id']; ?>" class="form-label">Titre du chantier</label>
                                <input type="text" class="form-control" id="projectTitle<?php echo $project['id']; ?>" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="clientName<?php echo $project['id']; ?>" class="form-label">Nom du client</label>
                                <input type="text" class="form-control" id="clientName<?php echo $project['id']; ?>" name="client_name" value="<?php echo htmlspecialchars($project['client_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="projectDescription<?php echo $project['id']; ?>" class="form-label">Description</label>
                                <textarea class="form-control" id="projectDescription<?php echo $project['id']; ?>" name="description" rows="3" required><?php echo htmlspecialchars($project['description']); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="startDate<?php echo $project['id']; ?>" class="form-label">Date de début</label>
                                    <input type="date" class="form-control" id="startDate<?php echo $project['id']; ?>" name="start_date" value="<?php echo $project['start_date']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="endDate<?php echo $project['id']; ?>" class="form-label">Date de fin prévue</label>
                                    <input type="date" class="form-control" id="endDate<?php echo $project['id']; ?>" name="end_date" value="<?php echo $project['end_date']; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="projectStatus<?php echo $project['id']; ?>" class="form-label">Statut</label>
                                <select class="form-select" id="projectStatus<?php echo $project['id']; ?>" name="status" required>
                                    <option value="planned" <?php echo $project['status'] === 'planned' ? 'selected' : ''; ?>>Planifié</option>
                                    <option value="in_progress" <?php echo $project['status'] === 'in_progress' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="projectProgress<?php echo $project['id']; ?>" class="form-label">Progression</label>
                                <input type="number" class="form-control" id="projectProgress<?php echo $project['id']; ?>" name="progress" value="<?php echo $project['progress']; ?>" min="0" max="100">
                            </div>
                            <button type="submit" name="edit_project" class="btn btn-primary">Mettre à jour</button>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary">Modifier</button>
                    <?php if ($project['status'] === 'completed'): ?>
                    <button type="button" class="btn btn-success">Générer rapport</button>
                    <?php endif; ?>
                    <a href="?delete=<?php echo $project['id']; ?>" class="btn btn-danger delete-project">
                        <i class="bi bi-trash"></i> Supprimer
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <!-- Add Project Modal -->
    <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProjectModalLabel">Ajouter un nouveau chantier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addProjectForm" method="POST" action="">
                        <div class="mb-3">
                            <label for="projectTitle" class="form-label">Titre du chantier</label>
                            <input type="text" class="form-control" id="projectTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="clientName" class="form-label">Nom du client</label>
                            <input type="text" class="form-control" id="clientName" name="client_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="projectDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="projectDescription" name="description" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="startDate" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="startDate" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="endDate" class="form-label">Date de fin prévue</label>
                                <input type="date" class="form-control" id="endDate" name="end_date" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="projectStatus" class="form-label">Statut</label>
                            <select class="form-select" id="projectStatus" name="status" required>
                                <option value="planned">Planifié</option>
                                <option value="in_progress">En cours</option>
                                <option value="completed">Terminé</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="addProjectForm" name="add_project" class="btn btn-primary">Ajouter</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            // Add project form validation
            const addProjectForm = document.getElementById('addProjectForm');
            if (addProjectForm) {
                addProjectForm.addEventListener('submit', function(event) {
                    const title = document.getElementById('projectTitle').value.trim();
                    const clientName = document.getElementById('clientName').value.trim();
                    const description = document.getElementById('projectDescription').value.trim();
                    const startDate = document.getElementById('startDate').value;
                    const endDate = document.getElementById('endDate').value;
                    
                    let isValid = true;
                    let errorMessage = '';
                    
                    if (!title) {
                        errorMessage += 'Le titre du chantier est requis.\n';
                        isValid = false;
                    }
                    
                    if (!clientName) {
                        errorMessage += 'Le nom du client est requis.\n';
                        isValid = false;
                    }
                    
                    if (!description) {
                        errorMessage += 'La description est requise.\n';
                        isValid = false;
                    }
                    
                    if (!startDate) {
                        errorMessage += 'La date de début est requise.\n';
                        isValid = false;
                    }
                    
                    if (!endDate) {
                        errorMessage += 'La date de fin est requise.\n';
                        isValid = false;
                    }
                    
                    if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                        errorMessage += 'La date de fin doit être postérieure à la date de début.\n';
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        event.preventDefault();
                        alert(errorMessage);
                    }
                });
            }
            
            // Delete project confirmation
            const deleteLinks = document.querySelectorAll('.delete-project');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(event) {
                    if (!confirm('Êtes-vous sûr de vouloir supprimer ce chantier ? Cette action est irréversible.')) {
                        event.preventDefault();
                    }
                });
            });
            
            // Progress update validation
            const progressForms = document.querySelectorAll('.progress-form');
            progressForms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    const progressInput = form.querySelector('input[name="progress"]');
                    const progress = parseInt(progressInput.value);
                    
                    if (isNaN(progress) || progress < 0 || progress > 100) {
                        event.preventDefault();
                        alert('La progression doit être un nombre entre 0 et 100.');
                    }
                });
            });
            
            // Edit project form validation
            const editProjectForms = document.querySelectorAll('.edit-project-form');
            editProjectForms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    const title = form.querySelector('input[name="title"]').value.trim();
                    const clientName = form.querySelector('input[name="client_name"]').value.trim();
                    const description = form.querySelector('textarea[name="description"]').value.trim();
                    const startDate = form.querySelector('input[name="start_date"]').value;
                    const endDate = form.querySelector('input[name="end_date"]').value;
                    const progress = form.querySelector('input[name="progress"]').value;
                    
                    let isValid = true;
                    let errorMessage = '';
                    
                    if (!title) {
                        errorMessage += 'Le titre du chantier est requis.\n';
                        isValid = false;
                    }
                    
                    if (!clientName) {
                        errorMessage += 'Le nom du client est requis.\n';
                        isValid = false;
                    }
                    
                    if (!description) {
                        errorMessage += 'La description est requise.\n';
                        isValid = false;
                    }
                    
                    if (!startDate) {
                        errorMessage += 'La date de début est requise.\n';
                        isValid = false;
                    }
                    
                    if (!endDate) {
                        errorMessage += 'La date de fin est requise.\n';
                        isValid = false;
                    }
                    
                    if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                        errorMessage += 'La date de fin doit être postérieure à la date de début.\n';
                        isValid = false;
                    }
                    
                    if (progress && (isNaN(progress) || progress < 0 || progress > 100)) {
                        errorMessage += 'La progression doit être un nombre entre 0 et 100.\n';
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        event.preventDefault();
                        alert(errorMessage);
                    }
                });
            });
        });
    </script>
</body>
</html>
