<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';

// Récupérer les informations du client
$client_id = $_SESSION['client_id'];
$db = Database::getInstance();
$conn = $db->getConnection();

// Vérifier si l'ID du message est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID de message invalide.";
    header('Location: messages.php');
    exit;
}

$message_id = $_GET['id'];

// Récupérer les informations du message
$stmt = $conn->prepare("SELECT * FROM messages WHERE id = ? AND client_id = ?");
$stmt->execute([$message_id, $client_id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    $_SESSION['error_message'] = "Message non trouvé ou vous n'avez pas l'autorisation de le consulter.";
    header('Location: messages.php');
    exit;
}

// Récupérer les réponses à ce message
$stmt = $conn->prepare("SELECT r.*, 'admin' as sender_type 
                        FROM message_replies r 
                        WHERE r.message_id = ? 
                        ORDER BY r.created_at ASC");
$stmt->execute([$message_id]);
$replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Détails du message";
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
                <h2 class="h3">Détails du message</h2>
                <div>
                    <a href="messages.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i> Retour aux messages
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Message original -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Votre message</h5>
                        <span class="badge bg-light text-dark"><?php echo date('d/m/Y à H:i', strtotime($message['created_at'])); ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="message-header mb-3">
                        <h4><?php echo htmlspecialchars($message['subject']); ?></h4>
                        <div class="badge bg-<?php echo $message['status'] === 'read' ? 'success' : 'warning'; ?> mt-2">
                            <?php echo $message['status'] === 'read' ? 'Lu par l\'administration' : 'Non lu'; ?>
                        </div>
                        <?php if (!empty($message['related_id'])): ?>
                            <div class="mt-2 mb-3">
                                <?php if ($message['related_type'] === 'quote'): ?>
                                    <span class="badge bg-info">Lié au devis #<?php echo $message['related_id']; ?></span>
                                    <a href="quote_details.php?id=<?php echo $message['related_id']; ?>" class="btn btn-sm btn-outline-info ms-2">Voir le devis</a>
                                <?php elseif ($message['related_type'] === 'project'): ?>
                                    <span class="badge bg-success">Lié au projet #<?php echo $message['related_id']; ?></span>
                                    <a href="project_details.php?id=<?php echo $message['related_id']; ?>" class="btn btn-sm btn-outline-success ms-2">Voir le projet</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <hr>
                    <div class="message-content">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Réponses -->
            <?php if (count($replies) > 0): ?>
                <h4 class="h5 mb-3">Réponses de l'administration</h4>
                <?php foreach ($replies as $reply): ?>
                    <div class="card border-0 shadow-sm mb-3 ms-5">
                        <div class="card-header py-2 bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <small><strong>Administration</strong></small>
                                <small><?php echo date('d/m/Y à H:i', strtotime($reply['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="card-body py-3">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($reply['message'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> Votre message est en attente de réponse de l'administration.
                </div>
            <?php endif; ?>
            
            <!-- Bouton pour envoyer un nouveau message -->
            <div class="mt-4 text-center">
                <a href="send_message.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i> Envoyer un nouveau message
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
