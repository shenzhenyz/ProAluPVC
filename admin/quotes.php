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
            $stmt = $conn->prepare("UPDATE quotes SET status = :status WHERE id = :id");
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
        $stmt = $conn->prepare("DELETE FROM quotes WHERE id = ?");
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

// Récupérer les statistiques des devis
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM quotes");
    $totalQuotes = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) as pending FROM quotes WHERE status = 'pending'");
    $pendingQuotes = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) as approved FROM quotes WHERE status = 'approved'");
    $approvedQuotes = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) as rejected FROM quotes WHERE status = 'rejected'");
    $rejectedQuotes = $stmt->fetchColumn();
} catch (PDOException $e) {
    $totalQuotes = $pendingQuotes = $approvedQuotes = $rejectedQuotes = 0;
}

// Récupérer tous les devis
try {
    $stmt = $conn->query("SELECT * FROM quotes ORDER BY created_at DESC");
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $quotes = [];
    $message = 'Erreur lors de la récupération des devis: ' . $e->getMessage();
    $messageType = 'danger';
}

// Fonction pour obtenir la classe de badge en fonction du statut
function getStatusBadgeClass($status) {
    switch ($status) {
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
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <h3><?php echo $totalQuotes; ?></h3>
            <p class="text-muted">Total des devis</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background-color: #ffc107;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <h3><?php echo $pendingQuotes; ?></h3>
            <p class="text-muted">En attente</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background-color: #28a745;">
                <i class="bi bi-check-circle"></i>
            </div>
            <h3><?php echo $approvedQuotes; ?></h3>
            <p class="text-muted">Approuvés</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background-color: #dc3545;">
                <i class="bi bi-x-circle"></i>
            </div>
            <h3><?php echo $rejectedQuotes; ?></h3>
            <p class="text-muted">Rejetés</p>
        </div>
    </div>
</div>

<!-- Quotes Table -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste des devis</h5>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addQuoteModal">
            <i class="bi bi-plus-circle me-2"></i> Ajouter un devis
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($quotes)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Aucun devis trouvé</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($quotes as $quote): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quote['name']); ?></td>
                                <td><?php echo htmlspecialchars($quote['email']); ?></td>
                                <td><?php echo htmlspecialchars($quote['phone']); ?></td>
                                <td><?php echo htmlspecialchars($quote['service']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($quote['created_at'])); ?></td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($quote['status']); ?>">
                                        <?php echo getStatusLabel($quote['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#quoteModal<?php echo $quote['id']; ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#editQuoteModal<?php echo $quote['id']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?php echo $quote['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce devis?');">
                                        <i class="bi bi-trash"></i>
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

<!-- Quote Modals -->
<?php foreach ($quotes as $quote): ?>
<div class="modal fade" id="quoteModal<?php echo $quote['id']; ?>" tabindex="-1" aria-labelledby="quoteModalLabel<?php echo $quote['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quoteModalLabel<?php echo $quote['id']; ?>">Détails du devis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6>Informations client</h6>
                    <p><strong>Nom:</strong> <?php echo htmlspecialchars($quote['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($quote['email']); ?></p>
                    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($quote['phone']); ?></p>
                </div>
                <div class="mb-3">
                    <h6>Détails du devis</h6>
                    <p><strong>Référence:</strong> <?php echo htmlspecialchars($quote['reference']); ?></p>
                    <p><strong>Service:</strong> <?php echo htmlspecialchars($quote['service']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($quote['created_at'])); ?></p>
                    <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($quote['message'])); ?></p>
                </div>
                <div>
                    <h6>Statut</h6>
                    <form method="post" action="">
                        <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
                        <div class="mb-3">
                            <select name="new_status" class="form-select">
                                <option value="pending" <?php echo ($quote['status'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                                <option value="approved" <?php echo ($quote['status'] == 'approved') ? 'selected' : ''; ?>>Approuvé</option>
                                <option value="rejected" <?php echo ($quote['status'] == 'rejected') ? 'selected' : ''; ?>>Rejeté</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-success">Mettre à jour le statut</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Add Quote Modal -->
<div class="modal fade" id="addQuoteModal" tabindex="-1" aria-labelledby="addQuoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addQuoteModalLabel">Ajouter un devis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="service_id" class="form-label">Service</label>
                        <select class="form-select" id="service_id" name="service_id" required>
                            <option value="">Sélectionnez un service</option>
                            <!-- Options à remplir dynamiquement depuis la base de données -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">En attente</option>
                            <option value="approved">Approuvé</option>
                            <option value="rejected">Rejeté</option>
                        </select>
                    </div>
                    <button type="submit" name="add_quote" class="btn btn-success">Ajouter</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
