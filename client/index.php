<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure les fichiers nécessaires
require_once '../config/config.php';
require_once '../includes/db.php';

// Définir le titre de la page
$pageTitle = 'Tableau de bord';

// Récupérer les informations du client
$client_id = $_SESSION['client_id'];
$db = Database::getInstance();
$conn = $db->getConnection();

// Récupérer le nombre de devis
$stmt = $conn->prepare("SELECT COUNT(*) FROM quotes WHERE user_id = ?");
$stmt->execute([$client_id]);
$totalQuotes = $stmt->fetchColumn();

// Récupérer le nombre de devis en attente
$stmt = $conn->prepare("SELECT COUNT(*) FROM quotes WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$client_id]);
$pendingQuotes = $stmt->fetchColumn();

// Récupérer le nombre de projets
$stmt = $conn->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ?");
$stmt->execute([$client_id]);
$totalProjects = $stmt->fetchColumn();

// Récupérer le nombre de projets en cours
$stmt = $conn->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ? AND status = 'in_progress'");
$stmt->execute([$client_id]);
$activeProjects = $stmt->fetchColumn();

// Récupérer les derniers devis
$stmt = $conn->prepare("SELECT * FROM quotes WHERE user_id = ? ORDER BY id DESC LIMIT 5");
$stmt->execute([$client_id]);
$recentQuotes = $stmt->fetchAll();

// Récupérer les projets en cours
$stmt = $conn->prepare("SELECT * FROM projects WHERE user_id = ? AND status = 'in_progress' ORDER BY id DESC LIMIT 3");
$stmt->execute([$client_id]);
$activeProjectsList = $stmt->fetchAll();

// Récupérer les messages non lus
$stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$client_id]);
$unreadMessages = $stmt->fetchColumn();

// Récupérer les messages administratifs non lus
$stmt = $conn->prepare("SELECT COUNT(*) FROM admin_messages WHERE user_id = ? AND is_read = 0");
$stmt->execute([$client_id]);
$unreadAdminMessages = $stmt->fetchColumn();

// Démarrer la mise en mémoire tampon
ob_start();
?>

<div class="row fade-in">
    <!-- Statistiques -->
    <div class="col-12">
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <div class="stat-icon bg-primary-soft rounded-circle">
                                <i class="fas fa-file-invoice text-primary"></i>
                            </div>
                        </div>
                        <h3 class="counter mb-0"><?= $totalQuotes ?></h3>
                        <p class="text-muted mb-0">Devis total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <div class="stat-icon bg-warning-soft rounded-circle">
                                <i class="fas fa-clock text-warning"></i>
                            </div>
                        </div>
                        <h3 class="counter mb-0"><?= $pendingQuotes ?></h3>
                        <p class="text-muted mb-0">Devis en attente</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <div class="stat-icon bg-success-soft rounded-circle">
                                <i class="fas fa-tasks text-success"></i>
                            </div>
                        </div>
                        <h3 class="counter mb-0"><?= $totalProjects ?></h3>
                        <p class="text-muted mb-0">Projets total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <div class="stat-icon bg-info-soft rounded-circle">
                                <i class="fas fa-hammer text-info"></i>
                            </div>
                        </div>
                        <h3 class="counter mb-0"><?= $activeProjects ?></h3>
                        <p class="text-muted mb-0">Projets en cours</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- Derniers devis -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Derniers devis</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($recentQuotes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Titre</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentQuotes as $quote): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($quote['title']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($quote['created_at'])) ?></td>
                                        <td><?= number_format($quote['amount'], 2, ',', ' ') ?> €</td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            
                                            switch ($quote['status']) {
                                                case 'pending':
                                                    $statusClass = 'badge bg-warning';
                                                    $statusText = 'En attente';
                                                    break;
                                                case 'approved':
                                                    $statusClass = 'badge bg-success';
                                                    $statusText = 'Approuvé';
                                                    break;
                                                case 'rejected':
                                                    $statusClass = 'badge bg-danger';
                                                    $statusText = 'Refusé';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?= $statusClass ?>"><?= $statusText ?></span>
                                        </td>
                                        <td>
                                            <a href="quote_details.php?id=<?= $quote['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4">
                        <p class="text-muted">Aucun devis disponible pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-end">
                <a href="quotes.php" class="btn btn-sm btn-primary">Voir tous les devis</a>
            </div>
        </div>
        
        <!-- Projets en cours -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Projets en cours</h5>
            </div>
            <div class="card-body">
                <?php if (count($activeProjectsList) > 0): ?>
                    <?php foreach ($activeProjectsList as $project): ?>
                        <div class="project-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0"><?= htmlspecialchars($project['title']) ?></h6>
                                <span class="badge bg-info">En cours</span>
                            </div>
                            <p class="small text-muted mb-2"><?= htmlspecialchars(substr($project['description'], 0, 100)) ?>...</p>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $project['progress'] ?>%;" aria-valuenow="<?= $project['progress'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted"><?= $project['progress'] ?>% terminé</small>
                                <a href="project_details.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-link p-0">Détails</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center">
                        <p class="text-muted">Aucun projet en cours pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-end">
                <a href="projects.php" class="btn btn-sm btn-primary">Voir tous les projets</a>
            </div>
        </div>
    </div>
    
    <!-- Sidebar droit -->
    <div class="col-lg-4">
        <!-- Bienvenue -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Bienvenue, <?= htmlspecialchars($_SESSION['client_name']) ?> !</h5>
                <p class="card-text">Bienvenue dans votre espace client Pro Alu et PVC. Ici, vous pouvez suivre vos devis, projets et communiquer avec notre équipe.</p>
                
                <div class="d-grid gap-2">
                    <a href="request_quote.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i> Demander un devis
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Notifications</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php if ($unreadMessages > 0): ?>
                <a href="messages.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-envelope text-primary me-2"></i>
                        Messages non lus
                    </div>
                    <span class="badge bg-primary rounded-pill"><?= $unreadMessages ?></span>
                </a>
                <?php endif; ?>
                
                <?php if ($unreadAdminMessages > 0): ?>
                <a href="admin_messages.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-bullhorn text-danger me-2"></i>
                        Annonces importantes
                    </div>
                    <span class="badge bg-danger rounded-pill"><?= $unreadAdminMessages ?></span>
                </a>
                <?php endif; ?>
                
                <?php if ($pendingQuotes > 0): ?>
                <a href="quotes.php?status=pending" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-file-invoice text-warning me-2"></i>
                        Devis en attente
                    </div>
                    <span class="badge bg-warning rounded-pill"><?= $pendingQuotes ?></span>
                </a>
                <?php endif; ?>
                
                <?php if ($unreadMessages == 0 && $unreadAdminMessages == 0 && $pendingQuotes == 0): ?>
                <div class="list-group-item text-center text-muted py-4">
                    <i class="fas fa-check-circle mb-2" style="font-size: 2rem;"></i>
                    <p class="mb-0">Vous êtes à jour !</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Récupérer le contenu mis en mémoire tampon et le nettoyer
$content = ob_get_clean();

// Inclure le template
include '../includes/client_template.php';
?>
