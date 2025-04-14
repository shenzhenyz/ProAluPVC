<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';

// Récupérer les informations du client
$client_id = $_SESSION['client_id'];
$db = Database::getInstance();
$conn = $db->getConnection();

// Récupérer les messages administrateur
$stmt = $conn->prepare("SELECT * FROM admin_messages 
                        WHERE client_id = ? 
                        ORDER BY created_at DESC");
$stmt->execute([$client_id]);
$admin_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les messages envoyés par le client
$stmt = $conn->prepare("SELECT * FROM messages 
                        WHERE client_id = ? 
                        ORDER BY created_at DESC");
$stmt->execute([$client_id]);
$client_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fusionner et trier tous les messages par date (du plus récent au plus ancien)
$all_messages = array_merge($admin_messages, $client_messages);

usort($all_messages, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Compter les messages non lus
$unread_count = 0;
foreach ($admin_messages as $message) {
    if ($message['status'] === 'unread') {
        $unread_count++;
    }
}

// Marquer comme lu si demandé
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $message_id = intval($_GET['mark_read']);
    $stmt = $conn->prepare("UPDATE admin_messages SET status = 'read' WHERE id = ? AND client_id = ?");
    $stmt->execute([$message_id, $client_id]);
    header('Location: messages.php');
    exit;
}

$pageTitle = "Mes messages";
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
                <h2 class="h3">Mes messages</h2>
                <a href="send_message.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i> Nouveau message
                </a>
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
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <?php if (count($all_messages) > 0): ?>
                        <div class="list-group message-list">
                            <?php foreach ($all_messages as $message): ?>
                                <?php 
                                // Déterminer si c'est un message admin ou client
                                $is_admin_message = isset($message['sender_type']) ? false : true;
                                $message_id = $message['id'];
                                $message_status = $message['status'];
                                $message_date = date('d/m/Y à H:i', strtotime($message['created_at']));
                                $message_subject = $message['subject'];
                                
                                // Déterminer l'URL de visualisation
                                $view_url = $is_admin_message ? "view_admin_message.php?id=$message_id" : "view_message.php?id=$message_id";
                                ?>
                                
                                <a href="<?php echo $view_url; ?>" class="list-group-item list-group-item-action <?php echo ($is_admin_message && $message_status === 'unread') ? 'unread-message' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h5 class="mb-1">
                                            <?php if ($is_admin_message && $message_status === 'unread'): ?>
                                                <span class="badge bg-danger me-2">Nouveau</span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($message_subject); ?>
                                        </h5>
                                        <small class="text-muted"><?php echo $message_date; ?></small>
                                    </div>
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <p class="mb-1 text-truncate" style="max-width: 80%;">
                                            <?php echo substr(htmlspecialchars($message['message']), 0, 100) . (strlen($message['message']) > 100 ? '...' : ''); ?>
                                        </p>
                                        <span class="badge <?php echo $is_admin_message ? 'bg-primary' : 'bg-success'; ?>">
                                            <?php echo $is_admin_message ? 'De l\'administration' : 'Envoyé par vous'; ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($message['related_type']) && !empty($message['related_id'])): ?>
                                        <small class="text-muted">
                                            <?php if ($message['related_type'] === 'quote'): ?>
                                                Lié au devis #<?php echo $message['related_id']; ?>
                                            <?php elseif ($message['related_type'] === 'project'): ?>
                                                Lié au projet #<?php echo $message['related_id']; ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-envelope-open text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">Vous n'avez pas encore de messages.</p>
                            <p class="text-muted">Utilisez le bouton "Nouveau message" pour contacter l'administration.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.unread-message {
    background-color: #f8f9fa;
    border-left: 4px solid #0d6efd;
    font-weight: 500;
}

.message-list .list-group-item {
    margin-bottom: 0.5rem;
    border-radius: 0.5rem;
    transition: all 0.2s;
}

.message-list .list-group-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>

<?php include 'includes/footer.php'; ?>
