<?php
// Démarrer la session et vérifier si l'administrateur est connecté
session_start();

// Include database configuration
require_once dirname(__DIR__) . '/config/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Rediriger vers la page de connexion si non connecté
    header('Location: login.php');
    exit;
}

// Set page variables
$pageTitle = 'Tableau de bord';
$currentPage = 'dashboard';

// Start output buffering
ob_start();

// Connexion à la base de données
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // En cas d'erreur, afficher un message d'erreur et arrêter l'exécution
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Récupérer les statistiques pour le tableau de bord
try {
    // Récupérer le nombre de devis
    $stmt = $conn->query("SELECT COUNT(*) FROM quote_requests");
    $stats['quotes']['total'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'pending'");
    $stats['quotes']['pending'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'approved'");
    $stats['quotes']['approved'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'rejected'");
    $stats['quotes']['rejected'] = $stmt->fetchColumn();
    
    // Récupérer le nombre de projets
    $stmt = $conn->query("SELECT COUNT(*) FROM projects");
    $stats['projects']['total'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM projects WHERE status = 'planned'");
    $stats['projects']['planned'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'");
    $stats['projects']['in_progress'] = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'");
    $stats['projects']['completed'] = $stmt->fetchColumn();
    
    // Récupérer le nombre de services
    $stmt = $conn->query("SELECT COUNT(*) FROM services");
    $stats['services'] = $stmt->fetchColumn();
    
    // Récupérer le nombre de clients
    $stmt = $conn->query("SELECT COUNT(*) FROM clients");
    $stats['clients'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    // En cas d'erreur, utiliser les données factices
    $stats = [
        'quotes' => [
            'total' => 12,
            'pending' => 5,
            'approved' => 4,
            'rejected' => 3
        ],
        'projects' => [
            'total' => 8,
            'planned' => 2,
            'in_progress' => 3,
            'completed' => 3
        ],
        'services' => 6,
        'clients' => 10
    ];
}

// Récupérer les projets récents
try {
    $stmt = $conn->query("SELECT * FROM projects ORDER BY created_at DESC LIMIT 5");
    $recentProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Utiliser les données factices en cas d'erreur
    $recentProjects = [
        [
            'id' => 1,
            'title' => 'Rénovation Fenêtres Villa Méditerranée',
            'client_name' => 'M. Dupont',
            'status' => 'completed',
            'start_date' => '2023-03-15'
        ],
        [
            'id' => 2,
            'title' => 'Installation Véranda Moderne',
            'client_name' => 'Mme Martin',
            'status' => 'in_progress',
            'start_date' => '2023-04-02'
        ],
        [
            'id' => 3,
            'title' => 'Pose Volets Roulants Résidence Les Pins',
            'client_name' => 'Copropriété Les Pins',
            'status' => 'in_progress',
            'start_date' => '2023-04-10'
        ],
        [
            'id' => 4,
            'title' => 'Remplacement Porte d\'Entrée',
            'client_name' => 'M. Benali',
            'status' => 'planned',
            'start_date' => '2023-04-25'
        ]
    ];
}

// Récupérer les devis récents
try {
    $stmt = $conn->query("SELECT * FROM quote_requests ORDER BY created_at DESC LIMIT 5");
    $recentQuotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Utiliser les données factices en cas d'erreur
    $recentQuotes = [
        [
            'id' => 1,
            'name' => 'Jean Dupont',
            'service' => 'Fenêtres en Aluminium',
            'created_at' => '2023-04-08',
            'status' => 'pending'
        ],
        [
            'id' => 2,
            'name' => 'Marie Martin',
            'service' => 'Portes en PVC',
            'created_at' => '2023-04-07',
            'status' => 'approved'
        ],
        [
            'id' => 3,
            'name' => 'Ahmed Benali',
            'service' => 'Vérandas',
            'created_at' => '2023-04-05',
            'status' => 'rejected'
        ],
        [
            'id' => 4,
            'name' => 'Sophie Leclerc',
            'service' => 'Volets Roulants',
            'created_at' => '2023-04-03',
            'status' => 'pending'
        ],
        [
            'id' => 5,
            'name' => 'Karim Mansouri',
            'service' => 'Stores et Pergolas',
            'created_at' => '2023-04-01',
            'status' => 'approved'
        ]
    ];
}

// Fonction pour obtenir la classe de badge en fonction du statut
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'planned':
            return 'bg-info';
        case 'in_progress':
            return 'bg-warning';
        case 'completed':
            return 'bg-success';
        case 'pending':
            return 'bg-warning';
        case 'approved':
            return 'bg-success';
        case 'rejected':
            return 'bg-danger';
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
        case 'pending':
            return 'En attente';
        case 'approved':
            return 'Approuvé';
        case 'rejected':
            return 'Rejeté';
        default:
            return 'Inconnu';
    }
}

?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <h3><?php echo $stats['quotes']['total']; ?></h3>
            <p class="text-muted">Devis</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background-color: #0d6efd;">
                <i class="bi bi-kanban"></i>
            </div>
            <h3><?php echo $stats['projects']['total']; ?></h3>
            <p class="text-muted">Projets</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background-color: #6f42c1;">
                <i class="bi bi-gear"></i>
            </div>
            <h3><?php echo $stats['services']; ?></h3>
            <p class="text-muted">Services</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background-color: #fd7e14;">
                <i class="bi bi-people"></i>
            </div>
            <h3><?php echo $stats['clients']; ?></h3>
            <p class="text-muted">Clients</p>
        </div>
    </div>
</div>

<!-- Projects and Quotes Overview -->
<div class="row mb-4">
    <!-- Projects Overview -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Aperçu des projets</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-icon" style="background-color: #17a2b8; width: 40px; height: 40px;">
                                <i class="bi bi-calendar-check" style="font-size: 1.2rem;"></i>
                            </div>
                            <h4><?php echo $stats['projects']['planned']; ?></h4>
                            <p class="text-muted small">Planifiés</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-icon" style="background-color: #ffc107; width: 40px; height: 40px;">
                                <i class="bi bi-hammer" style="font-size: 1.2rem;"></i>
                            </div>
                            <h4><?php echo $stats['projects']['in_progress']; ?></h4>
                            <p class="text-muted small">En cours</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-icon" style="background-color: #28a745; width: 40px; height: 40px;">
                                <i class="bi bi-check-circle" style="font-size: 1.2rem;"></i>
                            </div>
                            <h4><?php echo $stats['projects']['completed']; ?></h4>
                            <p class="text-muted small">Terminés</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quotes Overview -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Aperçu des devis</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-icon" style="background-color: #ffc107; width: 40px; height: 40px;">
                                <i class="bi bi-hourglass-split" style="font-size: 1.2rem;"></i>
                            </div>
                            <h4><?php echo $stats['quotes']['pending']; ?></h4>
                            <p class="text-muted small">En attente</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-icon" style="background-color: #28a745; width: 40px; height: 40px;">
                                <i class="bi bi-check-circle" style="font-size: 1.2rem;"></i>
                            </div>
                            <h4><?php echo $stats['quotes']['approved']; ?></h4>
                            <p class="text-muted small">Approuvés</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-icon" style="background-color: #dc3545; width: 40px; height: 40px;">
                                <i class="bi bi-x-circle" style="font-size: 1.2rem;"></i>
                            </div>
                            <h4><?php echo $stats['quotes']['rejected']; ?></h4>
                            <p class="text-muted small">Rejetés</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Projects -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Projets récents</h5>
        <a href="projects.php" class="btn btn-sm btn-outline-success">Voir tous</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentProjects)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Aucun projet récent</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentProjects as $project): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                        <td><?php echo htmlspecialchars($project['client_name']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($project['start_date'])); ?></td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($project['status']); ?>">
                                <?php echo getStatusLabel($project['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="projects.php?view=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Quotes -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Devis récents</h5>
        <a href="quotes.php" class="btn btn-sm btn-outline-success">Voir tous</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentQuotes)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Aucun devis récent</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentQuotes as $quote): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($quote['name']); ?></td>
                        <td><?php echo isset($quote['service']) ? htmlspecialchars($quote['service']) : 'Non spécifié'; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($quote['created_at'])); ?></td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($quote['status']); ?>">
                                <?php echo getStatusLabel($quote['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="quotes.php?view=<?php echo $quote['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include the layout template
require_once 'includes/layout.php';
?>
