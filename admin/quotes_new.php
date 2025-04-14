<?php
// Set page variables
$pageTitle = 'Gestion des Devis';
$currentPage = 'quotes';

// Start output buffering
ob_start();

// Include database connection
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Handle status updates
$message = '';
$messageType = '';

if (isset($_POST['update_status'])) {
    $quote_id = $_POST['quote_id'] ?? 0;
    $new_status = $_POST['new_status'] ?? '';
    
    if ($quote_id && in_array($new_status, ['pending', 'approved', 'rejected'])) {
        try {
            $stmt = $conn->prepare("UPDATE quote_requests SET status = :status WHERE id = :id");
            $stmt->bindParam(':status', $new_status);
            $stmt->bindParam(':id', $quote_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $message = 'Statut du devis mis à jour avec succès.';
                $messageType = 'success';
            } else {
                $message = 'Erreur lors de la mise à jour du statut.';
                $messageType = 'danger';
            }
        } catch (PDOException $e) {
            $message = 'Erreur de base de données: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Delete quote
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $quote_id = $_GET['delete'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM quote_requests WHERE id = ?");
        $stmt->execute([$quote_id]);
        
        if ($stmt->rowCount() > 0) {
            $message = 'Devis supprimé avec succès';
            $messageType = 'success';
        } else {
            $message = 'Devis introuvable';
            $messageType = 'warning';
        }
    } catch (PDOException $e) {
        $message = 'Erreur lors de la suppression: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get quotes with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Count total quotes
    $stmt = $conn->query("SELECT COUNT(*) FROM quote_requests");
    $total_quotes = $stmt->fetchColumn();
    
    // Calculate total pages
    $total_pages = ceil($total_quotes / $limit);
    
    // Get quotes for current page
    $stmt = $conn->prepare("
        SELECT qr.*, s.name as service_name 
        FROM quote_requests qr
        LEFT JOIN services s ON qr.service_id = s.id
        ORDER BY qr.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Erreur de base de données: ' . $e->getMessage();
    $messageType = 'danger';
    $quotes = [];
    $total_pages = 0;
}

// Get stats
$counts = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

try {
    // Count total quotes by status
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM quote_requests GROUP BY status");
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($statusCounts as $statusCount) {
        $counts[$statusCount['status']] = $statusCount['count'];
        $counts['total'] += $statusCount['count'];
    }
} catch (PDOException $e) {
    // Silently handle error
}

// Afficher un message d'alerte si nécessaire
if (!empty($message)) {
    echo '<div class="alert alert-' . $messageType . ' alert-dismissible fade show" role="alert">';
    echo $message;
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="stats-icon bg-primary">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <span class="badge bg-light text-dark">Total</span>
            </div>
            <h3 class="mb-1"><?php echo $counts['total']; ?></h3>
            <p class="text-muted mb-0">Total Devis</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="stats-icon bg-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <span class="badge bg-warning text-dark">En attente</span>
            </div>
            <h3 class="mb-1"><?php echo $counts['pending']; ?></h3>
            <p class="text-muted mb-0">Devis en attente</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="stats-icon bg-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <span class="badge bg-success">Approuvés</span>
            </div>
            <h3 class="mb-1"><?php echo $counts['approved']; ?></h3>
            <p class="text-muted mb-0">Devis approuvés</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="stats-icon bg-danger">
                    <i class="bi bi-x-circle"></i>
                </div>
                <span class="badge bg-danger">Rejetés</span>
            </div>
            <h3 class="mb-1"><?php echo $counts['rejected']; ?></h3>
            <p class="text-muted mb-0">Devis rejetés</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0"><i class="bi bi-funnel me-2"></i>Filtres de recherche</h5>
    </div>
    <div class="card-body">
        <form action="" method="get">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="statusFilter" class="form-label fw-medium">Statut</label>
                    <select class="form-select shadow-none border" id="statusFilter" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="pending">En attente</option>
                        <option value="approved">Approuvé</option>
                        <option value="rejected">Rejeté</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="serviceFilter" class="form-label fw-medium">Service</label>
                    <select class="form-select shadow-none border" id="serviceFilter" name="service">
                        <option value="">Tous les services</option>
                        <option value="Fenêtres en Aluminium">Fenêtres en Aluminium</option>
                        <option value="Portes en PVC">Portes en PVC</option>
                        <option value="Vérandas">Vérandas</option>
                        <option value="Volets Roulants">Volets Roulants</option>
                        <option value="Stores et Pergolas">Stores et Pergolas</option>
                        <option value="Garde-corps">Garde-corps</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="searchFilter" class="form-label fw-medium">Recherche</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control shadow-none border-start-0" id="searchFilter" name="search" placeholder="Nom, référence, email...">
                    </div>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-filter me-1"></i> Filtrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Quotes Table -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2"></i>Liste des devis</h5>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addQuoteModal">
            <i class="bi bi-plus-circle me-2"></i> Nouveau Devis
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3">Référence</th>
                        <th class="py-3">Nom</th>
                        <th class="py-3">Service</th>
                        <th class="py-3">Date</th>
                        <th class="py-3">Statut</th>
                        <th class="py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($quotes)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">Aucun devis trouvé</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($quotes as $quote): ?>
                    <tr>
                        <td class="fw-medium"><?php echo htmlspecialchars($quote['reference'] ?? 'DEV-'.str_pad($quote['id'], 4, '0', STR_PAD_LEFT)); ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person text-muted"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($quote['name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($quote['email'] ?? ''); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">
                                <?php echo htmlspecialchars($quote['service_name'] ?? 'Non spécifié'); ?>
                            </span>
                        </td>
                        <td>
                            <div>
                                <span class="fw-medium"><?php echo date('d/m/Y', strtotime($quote['created_at'])); ?></span>
                                <small class="d-block text-muted"><?php echo date('H:i', strtotime($quote['created_at'])); ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?php 
                                if ($quote['status'] == 'pending') echo 'bg-warning text-dark';
                                elseif ($quote['status'] == 'approved') echo 'bg-success';
                                elseif ($quote['status'] == 'rejected') echo 'bg-danger';
                                else echo 'bg-secondary';
                            ?> rounded-pill px-3 py-2">
                                <i class="bi <?php 
                                if ($quote['status'] == 'pending') echo 'bi-hourglass-split';
                                elseif ($quote['status'] == 'approved') echo 'bi-check-circle';
                                elseif ($quote['status'] == 'rejected') echo 'bi-x-circle';
                                else echo 'bi-question-circle';
                                ?> me-1"></i>
                                <?php 
                                if ($quote['status'] == 'pending') echo 'En attente';
                                elseif ($quote['status'] == 'approved') echo 'Approuvé';
                                elseif ($quote['status'] == 'rejected') echo 'Rejeté';
                                else echo 'Inconnu';
                                ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#viewQuoteModal<?php echo $quote['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="?delete=<?php echo $quote['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce devis?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white py-3">
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm justify-content-end mb-0">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Quote Modals -->
<?php foreach ($quotes as $quote): ?>
<div class="modal fade" id="viewQuoteModal<?php echo $quote['id']; ?>" tabindex="-1" aria-labelledby="viewQuoteModalLabel<?php echo $quote['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="viewQuoteModalLabel<?php echo $quote['id']; ?>">
                    <i class="bi bi-file-earmark-text me-2 text-success"></i>Détails du Devis #<?php echo htmlspecialchars($quote['reference'] ?? 'DEV-'.str_pad($quote['id'], 4, '0', STR_PAD_LEFT)); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-7 border-end">
                        <div class="mb-4">
                            <h6 class="fw-bold text-uppercase text-muted small mb-3">Informations Client</h6>
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar bg-light rounded-circle p-3 me-3">
                                    <i class="bi bi-person fs-4 text-success"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($quote['name']); ?></h5>
                                    <p class="text-muted mb-0">
                                        <i class="bi bi-envelope-fill me-1 small"></i> <?php echo htmlspecialchars($quote['email']); ?>
                                    </p>
                                    <p class="text-muted mb-0">
                                        <i class="bi bi-telephone-fill me-1 small"></i> <?php echo htmlspecialchars($quote['phone']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="fw-bold text-uppercase text-muted small mb-3">Détails de la demande</h6>
                            <div class="mb-3">
                                <span class="badge bg-light text-dark p-2 mb-2">
                                    <i class="bi bi-tools me-1"></i> <?php echo htmlspecialchars($quote['service_name'] ?? 'Non spécifié'); ?>
                                </span>
                                <p class="text-muted small mb-1">
                                    <i class="bi bi-calendar3 me-1"></i> Créé le <?php echo date('d/m/Y à H:i', strtotime($quote['created_at'])); ?>
                                </p>
                            </div>
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Message du client</h6>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($quote['message'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-5">
                        <div class="mb-4">
                            <h6 class="fw-bold text-uppercase text-muted small mb-3">Statut actuel</h6>
                            <div class="d-flex align-items-center mb-4">
                                <span class="badge <?php 
                                    if ($quote['status'] == 'pending') echo 'bg-warning text-dark';
                                    elseif ($quote['status'] == 'approved') echo 'bg-success';
                                    elseif ($quote['status'] == 'rejected') echo 'bg-danger';
                                    else echo 'bg-secondary';
                                ?> rounded-pill px-3 py-2 me-2">
                                    <i class="bi <?php 
                                    if ($quote['status'] == 'pending') echo 'bi-hourglass-split';
                                    elseif ($quote['status'] == 'approved') echo 'bi-check-circle';
                                    elseif ($quote['status'] == 'rejected') echo 'bi-x-circle';
                                    else echo 'bi-question-circle';
                                    ?> me-1"></i>
                                    <?php 
                                    if ($quote['status'] == 'pending') echo 'En attente';
                                    elseif ($quote['status'] == 'approved') echo 'Approuvé';
                                    elseif ($quote['status'] == 'rejected') echo 'Rejeté';
                                    else echo 'Inconnu';
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <h6 class="fw-bold text-uppercase text-muted small mb-3">Mettre à jour le statut</h6>
                            <form method="post" action="">
                                <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
                                <div class="mb-3">
                                    <select name="new_status" class="form-select shadow-none border">
                                        <option value="pending" <?php echo ($quote['status'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                                        <option value="approved" <?php echo ($quote['status'] == 'approved') ? 'selected' : ''; ?>>Approuvé</option>
                                        <option value="rejected" <?php echo ($quote['status'] == 'rejected') ? 'selected' : ''; ?>>Rejeté</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-success w-100">
                                    <i class="bi bi-check2-circle me-1"></i> Mettre à jour le statut
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="?delete=<?php echo $quote['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce devis?')">
                    <i class="bi bi-trash me-1"></i> Supprimer
                </a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Add Quote Modal -->
<div class="modal fade" id="addQuoteModal" tabindex="-1" aria-labelledby="addQuoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addQuoteModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Ajouter un nouveau devis
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="post" action="" class="row g-3">
                    <div class="col-12 mb-3">
                        <h6 class="fw-bold text-uppercase text-muted small mb-3">Informations Client</h6>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label fw-medium">Nom complet</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control shadow-none" id="name" name="name" placeholder="Ex: Jean Dupont" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label fw-medium">Téléphone</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-telephone"></i></span>
                            <input type="tel" class="form-control shadow-none" id="phone" name="phone" placeholder="Ex: 06 12 34 56 78" required>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="email" class="form-label fw-medium">Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control shadow-none" id="email" name="email" placeholder="Ex: jean.dupont@example.com" required>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-2 mb-3">
                        <h6 class="fw-bold text-uppercase text-muted small mb-3">Détails du devis</h6>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="service_id" class="form-label fw-medium">Service</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-tools"></i></span>
                            <select class="form-select shadow-none" id="service_id" name="service_id" required>
                                <option value="">Sélectionnez un service</option>
                                <!-- Options à remplir dynamiquement depuis la base de données -->
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label fw-medium">Statut initial</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-flag"></i></span>
                            <select class="form-select shadow-none" id="status" name="status" required>
                                <option value="pending" selected>En attente</option>
                                <option value="approved">Approuvé</option>
                                <option value="rejected">Rejeté</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label for="message" class="form-label fw-medium">Message / Description</label>
                        <textarea class="form-control shadow-none" id="message" name="message" rows="4" placeholder="Détails de la demande du client..." required></textarea>
                    </div>
                    
                    <div class="col-12 d-grid gap-2 mt-3">
                        <button type="submit" name="add_quote" class="btn btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Ajouter ce devis
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
