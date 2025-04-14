<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';

// Récupérer l'ID du devis
$quote_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$client_id = $_SESSION['client_id'];

// Vérifier si le devis existe et appartient au client
$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT q.*, s.name as service_name FROM quotes q 
                        LEFT JOIN services s ON q.service_id = s.id 
                        WHERE q.id = ? AND q.client_id = ?");
$stmt->execute([$quote_id, $client_id]);
$quote = $stmt->fetch(PDO::FETCH_ASSOC);

// Si le devis n'existe pas ou n'appartient pas au client, rediriger
if (!$quote) {
    header('Location: quotes.php');
    exit;
}

// Récupérer les commentaires associés au devis
$stmt = $conn->prepare("SELECT * FROM quote_comments 
                        WHERE quote_id = ? 
                        ORDER BY created_at ASC");
$stmt->execute([$quote_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de l'ajout d'un commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment_text = trim($_POST['comment']);
    
    if (!empty($comment_text)) {
        $stmt = $conn->prepare("INSERT INTO quote_comments (quote_id, user_id, user_type, comment, created_at) 
                                VALUES (?, ?, 'client', ?, NOW())");
        $result = $stmt->execute([$quote_id, $client_id, $comment_text]);
        
        if ($result) {
            // Rediriger pour éviter la soumission multiple du formulaire
            header('Location: quote_details.php?id=' . $quote_id);
            exit;
        }
    }
}

$pageTitle = "Détails du Devis #" . str_pad($quote['id'], 5, '0', STR_PAD_LEFT);
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Devis #<?php echo str_pad($quote['id'], 5, '0', STR_PAD_LEFT); ?></h2>
                <a href="quotes.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i> Retour aux devis</a>
            </div>
            
            <!-- Détails du devis -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Informations du devis</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Service demandé :</strong> <?php echo htmlspecialchars($quote['service_name']); ?></p>
                            <p><strong>Date de demande :</strong> <?php echo date('d/m/Y à H:i', strtotime($quote['created_at'])); ?></p>
                            <p>
                                <strong>Statut :</strong>
                                <?php if ($quote['status'] === 'pending'): ?>
                                    <span class="badge bg-warning">En attente</span>
                                <?php elseif ($quote['status'] === 'approved'): ?>
                                    <span class="badge bg-success">Approuvé</span>
                                <?php elseif ($quote['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger">Refusé</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($quote['price'])): ?>
                                <p><strong>Prix estimé :</strong> <?php echo number_format($quote['price'], 2, ',', ' '); ?> DZD</p>
                            <?php endif; ?>
                            <?php if (!empty($quote['valid_until'])): ?>
                                <p><strong>Valide jusqu'au :</strong> <?php echo date('d/m/Y', strtotime($quote['valid_until'])); ?></p>
                            <?php endif; ?>
                            <?php if ($quote['status'] === 'approved'): ?>
                                <div class="mt-3">
                                    <a href="#" class="btn btn-success">Accepter le devis</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Description de votre projet :</h6>
                    <p><?php echo nl2br(htmlspecialchars($quote['message'])); ?></p>
                    
                    <?php if (!empty($quote['admin_message'])): ?>
                        <hr>
                        <h6>Réponse de Pro Alu et PVC :</h6>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(htmlspecialchars($quote['admin_message'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Commentaires -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Discussion</h5>
                </div>
                <div class="card-body">
                    <?php if (count($comments) > 0): ?>
                        <div class="comments mb-4">
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment mb-3 p-3 <?php echo $comment['user_type'] === 'admin' ? 'bg-light text-start' : 'bg-success bg-opacity-10 text-end'; ?> rounded">
                                    <div class="comment-content">
                                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                    </div>
                                    <div class="comment-meta small text-muted mt-2">
                                        <?php echo $comment['user_type'] === 'admin' ? 'Pro Alu et PVC' : 'Vous'; ?> - 
                                        <?php echo date('d/m/Y à H:i', strtotime($comment['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted my-4">Aucun message dans cette discussion pour le moment.</p>
                    <?php endif; ?>
                    
                    <!-- Formulaire d'ajout de commentaire -->
                    <form method="POST" action="quote_details.php?id=<?php echo $quote_id; ?>">
                        <div class="mb-3">
                            <label for="comment" class="form-label">Ajouter un message</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Envoyer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
