<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Vérifier si la table clients existe et la créer si nécessaire
$clientsTableExists = false;
$checkClientsTable = $conn->query("SHOW TABLES LIKE 'clients'")->rowCount();
if ($checkClientsTable > 0) {
    $clientsTableExists = true;
} else {
    // Charger et exécuter le script SQL pour créer la table clients
    try {
        $sqlFile = file_get_contents(dirname(__DIR__) . '/create_clients_table.sql');
        $conn->exec($sqlFile);
        $clientsTableExists = true;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la création de la table clients: " . $e->getMessage();
    }
}

// Vérifier si la table quotes existe
$quotesTableExists = false;
$checkQuotesTable = $conn->query("SHOW TABLES LIKE 'quotes'")->rowCount();
if ($checkQuotesTable > 0) {
    $quotesTableExists = true;
}

// Initialiser les variables
$clients = [];
$total_clients = 0;
$new_clients = 0;
$active_clients = 0;

// Traitement de la suppression d'un client si la table existe
if ($clientsTableExists && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $client_id = $_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
        $result = $stmt->execute([$client_id]);
        
        if ($result) {
            $_SESSION['success_message'] = "Le client a été supprimé avec succès.";
        } else {
            $_SESSION['error_message'] = "Erreur lors de la suppression du client.";
        }
        
        header('Location: clients.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la suppression du client: " . $e->getMessage();
        header('Location: clients.php');
        exit;
    }
}

// Récupérer tous les clients si la table existe
if ($clientsTableExists) {
    try {
        $stmt = $conn->prepare("SELECT * FROM clients ORDER BY name ASC");
        $stmt->execute();
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les statistiques des clients
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM clients");
        $stmt->execute();
        $total_clients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM clients WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $new_clients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        // Ignorer l'erreur silencieusement
    }
}

// Récupérer le nombre de clients actifs si la table quotes existe
if ($quotesTableExists) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT client_id) as total FROM quotes");
        $stmt->execute();
        $active_clients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        // Ignorer l'erreur silencieusement
    }
}

$pageTitle = "Gestion des Clients";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle; ?></h1>
        <a href="add_client.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-user-plus fa-sm text-white-50"></i> Ajouter un client
        </a>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Statistiques des clients -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Clients</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_clients; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Nouveaux Clients (30j)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $new_clients; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Clients Actifs (avec devis)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_clients; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des clients -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Clients</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Actions:</div>
                    <a class="dropdown-item" href="export_clients.php"><i class="fas fa-file-export fa-sm fa-fw mr-2 text-gray-400"></i>Exporter</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Tu00e9lu00e9phone</th>
                            <th>Date d'inscription</th>
                            <th>Devis</th>
                            <th>Projets</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($clients)): ?>
                        <?php foreach ($clients as $client): ?>
                            <?php 
                            // Initialiser les compteurs
                            $quotes_count = 0;
                            $projects_count = 0;
                            
                            // Compter les devis du client si la table quotes existe
                            if ($quotesTableExists) {
                                try {
                                    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM quotes WHERE client_id = ?");
                                    $stmt->execute([$client['id']]);
                                    $quotes_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                } catch (PDOException $e) {
                                    // Ignorer l'erreur silencieusement
                                }
                            }
                            
                            // Vérifier si la table projects existe
                            $projectsTableExists = false;
                            $checkProjectsTable = $conn->query("SHOW TABLES LIKE 'projects'")->rowCount();
                            if ($checkProjectsTable > 0) {
                                $projectsTableExists = true;
                            }
                            
                            // Compter les projets du client si la table projects existe
                            if ($projectsTableExists) {
                                try {
                                    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM projects WHERE client_id = ?");
                                    $stmt->execute([$client['id']]);
                                    $projects_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                } catch (PDOException $e) {
                                    // Ignorer l'erreur silencieusement
                                }
                            }
                            ?>
                            <tr>
                                <td><?php echo $client['id']; ?></td>
                                <td><?php echo htmlspecialchars($client['name']); ?></td>
                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                <td><?php echo htmlspecialchars($client['phone']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($client['created_at'])); ?></td>
                                <td>
                                    <a href="quotes.php?client_id=<?php echo $client['id']; ?>">
                                        <?php echo $quotes_count; ?> devis
                                    </a>
                                </td>
                                <td>
                                    <a href="projects.php?client_id=<?php echo $client['id']; ?>">
                                        <?php echo $projects_count; ?> projets
                                    </a>
                                </td>
                                <td>
                                    <a href="edit_client.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="view_client.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $client['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="send_credentials.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-success" title="Envoyer les identifiants">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                </td>
                            </tr>
                            
                            <!-- Modal de suppression -->
                            <div class="modal fade" id="deleteModal<?php echo $client['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">u00d7</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            u00cates-vous su00fbr de vouloir supprimer le client "<?php echo htmlspecialchars($client['name']); ?>" ? Cette action est irru00e9versible et supprimera u00e9galement tous les devis et projets associu00e9s u00e0 ce client.
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-secondary" type="button" data-dismiss="modal">Annuler</button>
                                            <a class="btn btn-danger" href="clients.php?delete=<?php echo $client['id']; ?>">Supprimer</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Aucun client trouvé. Veuillez créer la table clients en exécutant le script SQL.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
