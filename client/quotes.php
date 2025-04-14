<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';

$pageTitle = "Mes Devis";

// Récupérer les informations du client
$client_id = $_SESSION['client_id'];
$db = Database::getInstance();
$conn = $db->getConnection();

// Récupérer les demandes de devis du client
try {
    // Requête principale pour récupérer les devis
    $stmt = $conn->prepare("SELECT q.*, s.name as service_name 
                    FROM quotes q 
                    LEFT JOIN services s ON q.service_id = s.id 
                    WHERE q.client_id = ? 
                    ORDER BY q.created_at DESC");
    $stmt->execute([$client_id]);
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Gérer les erreurs potentielles
    error_log("Erreur dans quotes.php: " . $e->getMessage());
    $quotes = [];
}

// Contenu principal
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Mes Demandes de Devis</h2>
    <a href="../devis.php" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i> Nouveau devis</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($quotes)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <h4 class="mt-3">Aucune demande de devis</h4>
                <p class="text-muted">Vous n'avez pas encore fait de demande de devis.</p>
                <a href="../devis.php" class="btn btn-primary mt-2">Demander un devis</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quotes as $quote): ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($quote['id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                <td><?php echo htmlspecialchars($quote['service_name'] ?? 'Service non spécifié'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($quote['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    $status = $quote['status'] ?? 'pending';
                                    $statusText = '';
                                    $statusClass = '';
                                    
                                    switch ($status) {
                                        case 'pending':
                                            $statusText = 'En attente';
                                            $statusClass = 'bg-warning';
                                            break;
                                        case 'approved':
                                            $statusText = 'Approuvé';
                                            $statusClass = 'bg-success';
                                            break;
                                        case 'rejected':
                                            $statusText = 'Refusé';
                                            $statusClass = 'bg-danger';
                                            break;
                                        default:
                                            $statusText = 'En attente';
                                            $statusClass = 'bg-warning';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td>
                                    <a href="quote_details.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Détails
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
