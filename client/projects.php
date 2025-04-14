<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';

$pageTitle = "Mes Projets";

// Récupérer les informations du client
$client_id = $_SESSION['client_id'];
$db = Database::getInstance();
$conn = $db->getConnection();

// Récupérer les projets du client
try {
    // Requête principale pour récupérer les projets
    $stmt = $conn->prepare("SELECT p.*, q.description as quote_description 
                    FROM projects p 
                    LEFT JOIN quotes q ON p.quote_id = q.id 
                    WHERE p.client_id = ? 
                    ORDER BY p.created_at DESC");
    $stmt->execute([$client_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Gérer les erreurs potentielles
    error_log("Erreur dans projects.php: " . $e->getMessage());
    $projects = [];
}

// Contenu principal
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Mes Projets</h2>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($projects)): ?>
            <div class="text-center py-5">
                <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
                <h4 class="mt-3">Aucun projet en cours</h4>
                <p class="text-muted">Vous n'avez pas encore de projets en cours.</p>
                <a href="../devis.php" class="btn btn-primary mt-2">Demander un devis</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Projet</th>
                            <th>Date de début</th>
                            <th>Statut</th>
                            <th>Progression</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($project['id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                <td><?php echo htmlspecialchars($project['quote_description'] ?? 'Service non spécifié'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($project['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    $status = $project['status'] ?? 'planned';
                                    $statusText = '';
                                    $statusClass = '';
                                    
                                    switch ($status) {
                                        case 'planned':
                                            $statusText = 'Planifié';
                                            $statusClass = 'bg-info';
                                            break;
                                        case 'in_progress':
                                            $statusText = 'En cours';
                                            $statusClass = 'bg-primary';
                                            break;
                                        case 'completed':
                                            $statusText = 'Terminé';
                                            $statusClass = 'bg-success';
                                            break;
                                        default:
                                            $statusText = 'Planifié';
                                            $statusClass = 'bg-info';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $progress = $project['progress'] ?? 0;
                                    if ($status === 'completed') $progress = 100;
                                    $progressClass = 'bg-info';
                                    
                                    if ($progress >= 100) {
                                        $progressClass = 'bg-success';
                                    } elseif ($progress >= 50) {
                                        $progressClass = 'bg-primary';
                                    } elseif ($progress >= 25) {
                                        $progressClass = 'bg-warning';
                                    }
                                    ?>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar <?php echo $progressClass; ?>" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="d-block mt-1"><?php echo $progress; ?>%</small>
                                </td>
                                <td>
                                    <a href="project_details.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
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
