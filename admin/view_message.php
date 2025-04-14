<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_functions.php';

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
$stmt = $conn->prepare("SELECT m.*, c.name as client_name, c.email as client_email 
                        FROM messages m 
                        LEFT JOIN clients c ON m.client_id = c.id 
                        WHERE m.id = ?");
$stmt->execute([$message_id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    $_SESSION['error_message'] = "Message non trouvé.";
    header('Location: messages.php');
    exit;
}

// Marquer le message comme lu s'il ne l'est pas déjà
if ($message['status'] === 'unread') {
    $stmt = $conn->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
    $stmt->execute([$message_id]);
    $message['status'] = 'read';
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

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Détails du message</h1>
        <div>
            <a href="reply_message.php?id=<?php echo $message_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-reply fa-sm text-white-50"></i> Répondre
            </a>
            <a href="messages.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour à la liste
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <!-- Message original -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Message de <?php echo htmlspecialchars($message['client_name']); ?></h6>
                    <span class="badge <?php echo $message['status'] === 'unread' ? 'badge-warning' : 'badge-success'; ?>">
                        <?php echo $message['status'] === 'unread' ? 'Non lu' : 'Lu'; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="message-header mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>De:</strong> <?php echo htmlspecialchars($message['client_name']); ?> (<?php echo htmlspecialchars($message['client_email']); ?>)</p>
                                <p><strong>Date:</strong> <?php echo date('d/m/Y à H:i', strtotime($message['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Sujet:</strong> <?php echo htmlspecialchars($message['subject']); ?></p>
                                <?php if (!empty($message['related_id'])): ?>
                                    <?php if ($message['related_type'] === 'quote'): ?>
                                        <p><strong>Lié à:</strong> <a href="view_quote.php?id=<?php echo $message['related_id']; ?>">Devis #<?php echo $message['related_id']; ?></a></p>
                                    <?php elseif ($message['related_type'] === 'project'): ?>
                                        <p><strong>Lié à:</strong> <a href="view_project.php?id=<?php echo $message['related_id']; ?>">Projet #<?php echo $message['related_id']; ?></a></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="message-content">
                        <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Réponses -->
            <?php if (count($replies) > 0): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Réponses (<?php echo count($replies); ?>)</h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($replies as $reply): ?>
                            <div class="reply-item mb-4 <?php echo $reply['sender_type'] === 'admin' ? 'admin-reply' : 'client-reply'; ?>">
                                <div class="reply-header d-flex justify-content-between">
                                    <div>
                                        <strong>
                                            <?php echo $reply['sender_type'] === 'admin' ? 'Administrateur' : htmlspecialchars($message['client_name']); ?>
                                        </strong>
                                        <span class="text-muted ml-2"><?php echo date('d/m/Y à H:i', strtotime($reply['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="reply-content mt-2 p-3 bg-light rounded">
                                    <p><?php echo nl2br(htmlspecialchars($reply['message'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulaire de réponse rapide -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Réponse rapide</h6>
                </div>
                <div class="card-body">
                    <form action="reply_message.php" method="POST">
                        <input type="hidden" name="message_id" value="<?php echo $message_id; ?>">
                        <div class="form-group">
                            <textarea class="form-control" name="reply_message" rows="5" placeholder="Votre réponse..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane mr-2"></i> Envoyer la réponse
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.admin-reply .reply-content {
    background-color: #e8f4fe !important;
    border-left: 4px solid #4e73df;
}

.client-reply .reply-content {
    background-color: #f8f9fc !important;
    border-left: 4px solid #36b9cc;
}
</style>

<?php include 'includes/footer.php'; ?>
