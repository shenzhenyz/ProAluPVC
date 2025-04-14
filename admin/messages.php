<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Vérifier si la table clients existe
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'clients'")->rowCount();
if ($checkTable > 0) {
    $tableExists = true;
}

// Récupérer tous les clients pour le filtre si la table existe
$clients = [];
if ($tableExists) {
    try {
        $stmt = $conn->prepare("SELECT id, name, email FROM clients ORDER BY name ASC");
        $stmt->execute();
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Ignorer l'erreur silencieusement
    }
}

// Filtres
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Vérifier si la table messages existe
$messagesTableExists = false;
$checkMessagesTable = $conn->query("SHOW TABLES LIKE 'messages'")->rowCount();
if ($checkMessagesTable > 0) {
    $messagesTableExists = true;
}

$messages = [];

if ($messagesTableExists) {
    try {
        // Requête de base
        $query = "SELECT m.*, c.name as client_name, c.email as client_email 
                  FROM messages m 
                  LEFT JOIN clients c ON m.client_id = c.id 
                  WHERE 1=1";
        $params = [];
        
        // Appliquer les filtres
        if ($client_id > 0) {
            $query .= " AND m.client_id = ?";
            $params[] = $client_id;
        }
        
        if (!empty($status)) {
            $query .= " AND m.status = ?";
            $params[] = $status;
        }
        
        if (!empty($search)) {
            $query .= " AND (m.subject LIKE ? OR m.message LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        // Tri par date décroissante
        $query .= " ORDER BY m.created_at DESC";
        
        // Exécuter la requête
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Ignorer l'erreur silencieusement
    }
}

// Traitement de la marque comme lu/non lu si la table messages existe
if ($messagesTableExists) {
    if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
        try {
            $message_id = intval($_GET['mark_read']);
            $stmt = $conn->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
            $stmt->execute([$message_id]);
            $_SESSION['success_message'] = "Message marqué comme lu.";
            header("Location: messages.php" . (isset($_GET['client_id']) ? "?client_id=" . $_GET['client_id'] : ""));
            exit;
        } catch (PDOException $e) {
            // Ignorer l'erreur silencieusement
        }
    }

    if (isset($_GET['mark_unread']) && is_numeric($_GET['mark_unread'])) {
        try {
            $message_id = intval($_GET['mark_unread']);
            $stmt = $conn->prepare("UPDATE messages SET status = 'unread' WHERE id = ?");
            $stmt->execute([$message_id]);
            $_SESSION['success_message'] = "Message marqué comme non lu.";
            header("Location: messages.php" . (isset($_GET['client_id']) ? "?client_id=" . $_GET['client_id'] : ""));
            exit;
        } catch (PDOException $e) {
            // Ignorer l'erreur silencieusement
        }
    }
}

// Compter les messages non lus si la table messages existe
$unread_count = 0;
if ($messagesTableExists) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE status = 'unread'");
        $stmt->execute();
        $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        // Ignorer l'erreur silencieusement
    }
}

$pageTitle = "Gestion des messages";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gestion des messages</h1>
        <a href="send_message.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-envelope fa-sm text-white-50"></i> Nouveau message
        </a>
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

    <!-- Filtres -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="messages.php" class="row align-items-center">
                <div class="col-md-3 mb-3">
                    <label for="client_id">Client</label>
                    <select class="form-control" id="client_id" name="client_id">
                        <option value="">Tous les clients</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $client_id == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?> (<?php echo htmlspecialchars($client['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="status">Statut</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="unread" <?php echo $status === 'unread' ? 'selected' : ''; ?>>Non lu</option>
                        <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Lu</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="search">Recherche</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Sujet, message, nom du client..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2 w-50">Filtrer</button>
                    <a href="messages.php" class="btn btn-secondary w-50">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Messages -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Liste des messages</h6>
            <span class="badge badge-danger"><?php echo $unread_count; ?> non lu(s)</span>
        </div>
        <div class="card-body">
            <?php if (count($messages) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Sujet</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                                <tr class="<?php echo $message['status'] === 'unread' ? 'table-warning' : ''; ?>">
                                    <td>
                                        <a href="view_client.php?id=<?php echo $message['client_id']; ?>">
                                            <?php echo htmlspecialchars($message['client_name']); ?>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($message['client_email']); ?></small>
                                    </td>
                                    <td>
                                        <a href="view_message.php?id=<?php echo $message['id']; ?>">
                                            <?php echo htmlspecialchars($message['subject']); ?>
                                        </a>
                                        <p class="small text-muted mb-0">
                                            <?php echo substr(htmlspecialchars($message['message']), 0, 50) . (strlen($message['message']) > 50 ? '...' : ''); ?>
                                        </p>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?></td>
                                    <td>
                                        <?php if ($message['status'] === 'unread'): ?>
                                            <span class="badge badge-warning">Non lu</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Lu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view_message.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="reply_message.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-reply"></i>
                                        </a>
                                        <?php if ($message['status'] === 'unread'): ?>
                                            <a href="messages.php?mark_read=<?php echo $message['id']; ?><?php echo $client_id ? '&client_id=' . $client_id : ''; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="messages.php?mark_unread=<?php echo $message['id']; ?><?php echo $client_id ? '&client_id=' . $client_id : ''; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-muted">Aucun message trouvé.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
