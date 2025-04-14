<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Traitement de la ru00e9ponse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    $reply_message = trim($_POST['reply_message'] ?? '');
    
    if (empty($reply_message)) {
        $_SESSION['error_message'] = "Le message ne peut pas u00eatre vide.";
        header("Location: view_message.php?id=$message_id");
        exit;
    }
    
    // Vu00e9rifier que le message existe
    $stmt = $conn->prepare("SELECT m.*, c.email as client_email, c.name as client_name 
                            FROM messages m 
                            LEFT JOIN clients c ON m.client_id = c.id 
                            WHERE m.id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        $_SESSION['error_message'] = "Message non trouvu00e9.";
        header('Location: messages.php');
        exit;
    }
    
    // Insu00e9rer la ru00e9ponse dans la base de donnu00e9es
    $stmt = $conn->prepare("INSERT INTO message_replies (message_id, sender_type, message, created_at) 
                            VALUES (?, 'admin', ?, NOW())");
    $result = $stmt->execute([$message_id, $reply_message]);
    
    if ($result) {
        // Envoyer un email au client pour l'informer de la ru00e9ponse
        $to = $message['client_email'];
        $subject = "Ru00e9ponse u00e0 votre message - Pro Alu et PVC";
        
        $emailContent = "<html><body>";
        $emailContent .= "<h2>Ru00e9ponse u00e0 votre message</h2>";
        $emailContent .= "<p>Bonjour {$message['client_name']},</p>";
        $emailContent .= "<p>Nous avons ru00e9pondu u00e0 votre message concernant <strong>'{$message['subject']}'</strong>.</p>";
        $emailContent .= "<p>Pour consulter notre ru00e9ponse, veuillez vous connecter u00e0 votre espace client :</p>";
        $emailContent .= "<p><a href='http://proaluetpvc.com/client/login.php' style='background-color: #4e73df; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Accu00e9der u00e0 mon espace client</a></p>";
        $emailContent .= "<p>Cordialement,<br>L'u00e9quipe Pro Alu et PVC</p>";
        $emailContent .= "</body></html>";
        
        // En-tu00eates pour l'email HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Pro Alu et PVC <noreply@proaluetpvc.com>" . "\r\n";
        
        // Envoi de l'email
        mail($to, $subject, $emailContent, $headers);
        
        $_SESSION['success_message'] = "Votre ru00e9ponse a u00e9tu00e9 envoyu00e9e avec succu00e8s.";
        header("Location: view_message.php?id=$message_id");
        exit;
    } else {
        $_SESSION['error_message'] = "Erreur lors de l'envoi de la ru00e9ponse.";
        header("Location: view_message.php?id=$message_id");
        exit;
    }
}

// Affichage du formulaire de ru00e9ponse
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID de message invalide.";
    header('Location: messages.php');
    exit;
}

$message_id = $_GET['id'];

// Ru00e9cupu00e9rer les informations du message
$stmt = $conn->prepare("SELECT m.*, c.name as client_name, c.email as client_email 
                        FROM messages m 
                        LEFT JOIN clients c ON m.client_id = c.id 
                        WHERE m.id = ?");
$stmt->execute([$message_id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    $_SESSION['error_message'] = "Message non trouvu00e9.";
    header('Location: messages.php');
    exit;
}

// Ru00e9cupu00e9rer les ru00e9ponses pru00e9cu00e9dentes
$stmt = $conn->prepare("SELECT r.*, 'admin' as sender_type 
                        FROM message_replies r 
                        WHERE r.message_id = ? 
                        ORDER BY r.created_at ASC");
$stmt->execute([$message_id]);
$replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Ru00e9pondre au message";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Ru00e9pondre au message</h1>
        <a href="view_message.php?id=<?php echo $message_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour au message
        </a>
    </div>

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
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Message original</h6>
                </div>
                <div class="card-body">
                    <div class="message-header mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>De:</strong> <?php echo htmlspecialchars($message['client_name']); ?> (<?php echo htmlspecialchars($message['client_email']); ?>)</p>
                                <p><strong>Date:</strong> <?php echo date('d/m/Y u00e0 H:i', strtotime($message['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Sujet:</strong> <?php echo htmlspecialchars($message['subject']); ?></p>
                                <?php if (!empty($message['related_id'])): ?>
                                    <?php if ($message['related_type'] === 'quote'): ?>
                                        <p><strong>Liu00e9 u00e0:</strong> <a href="view_quote.php?id=<?php echo $message['related_id']; ?>">Devis #<?php echo $message['related_id']; ?></a></p>
                                    <?php elseif ($message['related_type'] === 'project'): ?>
                                        <p><strong>Liu00e9 u00e0:</strong> <a href="view_project.php?id=<?php echo $message['related_id']; ?>">Projet #<?php echo $message['related_id']; ?></a></p>
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

            <!-- Ru00e9ponses pru00e9cu00e9dentes -->
            <?php if (count($replies) > 0): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Ru00e9ponses pru00e9cu00e9dentes</h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($replies as $reply): ?>
                            <div class="reply-item mb-4 <?php echo $reply['sender_type'] === 'admin' ? 'admin-reply' : 'client-reply'; ?>">
                                <div class="reply-header d-flex justify-content-between">
                                    <div>
                                        <strong>
                                            <?php echo $reply['sender_type'] === 'admin' ? 'Administrateur' : htmlspecialchars($message['client_name']); ?>
                                        </strong>
                                        <span class="text-muted ml-2"><?php echo date('d/m/Y u00e0 H:i', strtotime($reply['created_at'])); ?></span>
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

            <!-- Formulaire de ru00e9ponse -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Votre ru00e9ponse</h6>
                </div>
                <div class="card-body">
                    <form action="reply_message.php" method="POST">
                        <input type="hidden" name="message_id" value="<?php echo $message_id; ?>">
                        <div class="form-group">
                            <label for="reply_message">Message</label>
                            <textarea class="form-control" id="reply_message" name="reply_message" rows="8" required></textarea>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="notify_client" name="notify_client" value="1" checked>
                            <label class="form-check-label" for="notify_client">Notifier le client par email</label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane mr-2"></i> Envoyer la ru00e9ponse
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
