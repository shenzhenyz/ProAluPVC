<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Vérifier si l'ID du client est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID de client invalide.";
    header('Location: clients.php');
    exit;
}

$client_id = $_GET['id'];

// Récupérer les informations du client
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    $_SESSION['error_message'] = "Client non trouvé.";
    header('Location: clients.php');
    exit;
}

// Récupérer les devis du client
$stmt = $conn->prepare("SELECT q.*, s.name as service_name FROM quotes q 
                        LEFT JOIN services s ON q.service_id = s.id 
                        WHERE q.client_id = ? 
                        ORDER BY q.created_at DESC");
$stmt->execute([$client_id]);
$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les projets du client
$stmt = $conn->prepare("SELECT p.*, s.name as service_name FROM projects p 
                        LEFT JOIN services s ON p.service_id = s.id 
                        WHERE p.client_id = ? 
                        ORDER BY p.created_at DESC");
$stmt->execute([$client_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$total_quotes = count($quotes);
$pending_quotes = 0;
$approved_quotes = 0;
$rejected_quotes = 0;

foreach ($quotes as $quote) {
    if ($quote['status'] === 'pending') $pending_quotes++;
    elseif ($quote['status'] === 'approved') $approved_quotes++;
    elseif ($quote['status'] === 'rejected') $rejected_quotes++;
}

$total_projects = count($projects);
$planned_projects = 0;
$in_progress_projects = 0;
$completed_projects = 0;

foreach ($projects as $project) {
    if ($project['status'] === 'planned') $planned_projects++;
    elseif ($project['status'] === 'in_progress') $in_progress_projects++;
    elseif ($project['status'] === 'completed') $completed_projects++;
}

$pageTitle = "Profil Client - " . $client['name'];
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Profil Client</h1>
        <div>
            <a href="edit_client.php?id=<?php echo $client_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-edit fa-sm text-white-50"></i> Modifier
            </a>
            <a href="send_credentials.php?id=<?php echo $client_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm mr-2">
                <i class="fas fa-envelope fa-sm text-white-50"></i> Envoyer identifiants
            </a>
            <a href="clients.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour à la liste
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Informations du client -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Informations du client</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                            <a class="dropdown-item" href="edit_client.php?id=<?php echo $client_id; ?>"><i class="fas fa-edit fa-sm fa-fw mr-2 text-gray-400"></i>Modifier</a>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#deleteModal"><i class="fas fa-trash fa-sm fa-fw mr-2 text-gray-400"></i>Supprimer</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img class="img-profile rounded-circle mb-3" src="../assets/img/user-avatar.png" alt="Avatar" style="width: 100px; height: 100px;">
                        <h4><?php echo htmlspecialchars($client['name']); ?></h4>
                        <p class="text-muted">Client depuis <?php echo date('d/m/Y', strtotime($client['created_at'])); ?></p>
                    </div>
                    
                    <hr>
                    
                    <div class="client-details">
                        <div class="mb-3">
                            <strong><i class="fas fa-envelope mr-2"></i> Email:</strong>
                            <p><?php echo htmlspecialchars($client['email']); ?></p>
                        </div>
                        <div class="mb-3">
                            <strong><i class="fas fa-phone mr-2"></i> Téléphone:</strong>
                            <p><?php echo htmlspecialchars($client['phone']); ?></p>
                        </div>
                        <div class="mb-3">
                            <strong><i class="fas fa-calendar mr-2"></i> Date d'inscription:</strong>
                            <p><?php echo date('d/m/Y à H:i', strtotime($client['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <a href="add_quote.php?client_id=<?php echo $client_id; ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus-circle"></i> Nouveau devis
                        </a>
                        <a href="add_project.php?client_id=<?php echo $client_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus-circle"></i> Nouveau projet
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistiques</h6>
                </div>
                <div class="card-body">
                    <h6 class="font-weight-bold">Devis</h6>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Total</span>
                            <span class="font-weight-bold"><?php echo $total_quotes; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>En attente</span>
                            <span class="text-warning"><?php echo $pending_quotes; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Approuvés</span>
                            <span class="text-success"><?php echo $approved_quotes; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Refusés</span>
                            <span class="text-danger"><?php echo $rejected_quotes; ?></span>
                        </div>
                    </div>
                    
                    <h6 class="font-weight-bold">Projets</h6>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Total</span>
                            <span class="font-weight-bold"><?php echo $total_projects; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Planifiés</span>
                            <span class="text-info"><?php echo $planned_projects; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>En cours</span>
                            <span class="text-primary"><?php echo $in_progress_projects; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Terminés</span>
                            <span class="text-success"><?php echo $completed_projects; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-xl-8 col-lg-7">
            <!-- Devis récents -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Devis récents</h6>
                    <a href="quotes.php?client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-primary">Voir tous</a>
                </div>
                <div class="card-body">
                    <?php if (count($quotes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Service</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Prix</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($quotes, 0, 5) as $quote): ?>
                                        <tr>
                                            <td><?php echo $quote['id']; ?></td>
                                            <td><?php echo htmlspecialchars($quote['service_name']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($quote['created_at'])); ?></td>
                                            <td>
                                                <?php if ($quote['status'] === 'pending'): ?>
                                                    <span class="badge badge-warning">En attente</span>
                                                <?php elseif ($quote['status'] === 'approved'): ?>
                                                    <span class="badge badge-success">Approuvé</span>
                                                <?php elseif ($quote['status'] === 'rejected'): ?>
                                                    <span class="badge badge-danger">Refusé</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($quote['price']) ? number_format($quote['price'], 2, ',', ' ') . ' DZD' : '-'; ?>
                                            </td>
                                            <td>
                                                <a href="edit_quote.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view_quote.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">Aucun devis pour ce client.</p>
                            <a href="add_quote.php?client_id=<?php echo $client_id; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-plus-circle"></i> Créer un devis
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Projets récents -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Projets récents</h6>
                    <a href="projects.php?client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-primary">Voir tous</a>
                </div>
                <div class="card-body">
                    <?php if (count($projects) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Service</th>
                                        <th>Date début</th>
                                        <th>Statut</th>
                                        <th>Progression</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($projects, 0, 5) as $project): ?>
                                        <?php 
                                        $progress = 0;
                                        if ($project['status'] === 'planned') $progress = 10;
                                        elseif ($project['status'] === 'in_progress') $progress = 50;
                                        elseif ($project['status'] === 'completed') $progress = 100;
                                        ?>
                                        <tr>
                                            <td><?php echo $project['id']; ?></td>
                                            <td><?php echo htmlspecialchars($project['service_name']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($project['created_at'])); ?></td>
                                            <td>
                                                <?php if ($project['status'] === 'planned'): ?>
                                                    <span class="badge badge-info">Planifié</span>
                                                <?php elseif ($project['status'] === 'in_progress'): ?>
                                                    <span class="badge badge-primary">En cours</span>
                                                <?php elseif ($project['status'] === 'completed'): ?>
                                                    <span class="badge badge-success">Terminé</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">Aucun projet pour ce client.</p>
                            <a href="add_project.php?client_id=<?php echo $client_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus-circle"></i> Créer un projet
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer le client "<?php echo htmlspecialchars($client['name']); ?>" ? Cette action est irréversible et supprimera également tous les devis et projets associés à ce client.
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Annuler</button>
                <a class="btn btn-danger" href="clients.php?delete=<?php echo $client_id; ?>">Supprimer</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
