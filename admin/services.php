<?php
// Set page variables
$pageTitle = 'Gestion des Services';
$currentPage = 'services';

// Start output buffering
ob_start();

// Include database connection
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO services (name, description, type, price_range, image_path) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    // Handle image upload
                    $imagePath = '';
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                        $uploadDir = '../uploads/services/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                            $imagePath = 'uploads/services/' . $fileName;
                        }
                    }
                    
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['type'],
                        $_POST['price_range'],
                        $imagePath
                    ]);
                    
                    $message = 'Service ajouté avec succès.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erreur lors de l\'ajout du service: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'update':
                try {
                    $imagePath = $_POST['current_image'];
                    
                    // Handle new image upload
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                        $uploadDir = '../uploads/services/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                            // Delete old image if exists
                            if (!empty($_POST['current_image'])) {
                                @unlink('../' . $_POST['current_image']);
                            }
                            $imagePath = 'uploads/services/' . $fileName;
                        }
                    }
                    
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        $fileType = mime_content_type($_FILES['image']['tmp_name']);
                        $maxFileSize = 2 * 1024 * 1024; // 2MB
                    
                        if (!in_array($fileType, $allowedTypes)) {
                            $errors[] = "Le type de fichier n'est pas autorisé.";
                        } elseif ($_FILES['image']['size'] > $maxFileSize) {
                            $errors[] = "La taille du fichier dépasse la limite autorisée.";
                        } else {
                            move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
                        }
                    }

                    $stmt = $conn->prepare("
                        UPDATE services 
                        SET name = ?, description = ?, type = ?, price_range = ?, image_path = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['type'],
                        $_POST['price_range'],
                        $imagePath,
                        $_POST['id']
                    ]);
                    
                    $message = 'Service mis à jour avec succès.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erreur lors de la mise à jour du service: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'delete':
                try {
                    // Récupérer le chemin de l'image avant de supprimer
                    $stmt = $conn->prepare("SELECT image_path FROM services WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $service = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Supprimer l'image si elle existe
                    if ($service && !empty($service['image_path'])) {
                        @unlink('../' . $service['image_path']);
                    }
                    
                    // Supprimer le service
                    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    
                    $message = 'Service supprimé avec succès.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erreur lors de la suppression du service: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get all services
try {
    $stmt = $conn->query("SELECT * FROM services ORDER BY name");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalServices = count($services);
} catch (PDOException $e) {
    $message = 'Erreur de base de données: ' . $e->getMessage();
    $messageType = 'danger';
    $services = [];
    $totalServices = 0;
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
    <div class="col-md-4">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-tools"></i>
            </div>
            <h3><?php echo $totalServices; ?></h3>
            <p>Total des services</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-window"></i>
            </div>
            <h3><?php 
            $fenetreCount = 0;
            foreach ($services as $s) {
                if (isset($s['type']) && (
                    $s['type'] == 'Fenêtre PVC' || 
                    $s['type'] == 'Fenêtre Aluminium' || 
                    $s['type'] == 'Porte-fenêtre'
                )) {
                    $fenetreCount++;
                }
            }
            echo $fenetreCount;
            ?></h3>
            <p>Menuiseries</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-house-door"></i>
            </div>
            <h3><?php 
            $porteCount = 0;
            foreach ($services as $s) {
                if (isset($s['type']) && (
                    $s['type'] == 'Porte d\'entrée' || 
                    $s['type'] == 'Porte de garage' || 
                    $s['type'] == 'Portail'
                )) {
                    $porteCount++;
                }
            }
            echo $porteCount;
            ?></h3>
            <p>Portes & Portails</p>
        </div>
    </div>
</div>

<!-- Services Table -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Liste des services</h5>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="bi bi-plus-circle me-2"></i> Ajouter un Service
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Gamme de Prix</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Aucun service trouvé</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($services as $service): ?>
                    <tr>
                        <td>
                            <?php if (isset($service['image_path']) && !empty($service['image_path'])): ?>
                                <img src="<?php echo '../' . htmlspecialchars($service['image_path']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light text-center" style="width: 50px; height: 50px; line-height: 50px;">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($service['name']); ?></td>
                        <td><?php echo isset($service['type']) ? htmlspecialchars($service['type']) : 'Non défini'; ?></td>
                        <td><?php echo mb_strimwidth(htmlspecialchars($service['description']), 0, 100, '...'); ?></td>
                        <td><?php echo isset($service['price_range']) ? htmlspecialchars($service['price_range']) : 'Non défini'; ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                    data-bs-toggle="modal" data-bs-target="#editServiceModal"
                                    onclick="editService(<?php echo $service['id']; ?>, 
                                        '<?php echo addslashes($service['name']); ?>', 
                                        '<?php echo isset($service['type']) ? addslashes($service['type']) : ''; ?>', 
                                        '<?php echo addslashes($service['description']); ?>', 
                                        '<?php echo isset($service['price_range']) ? addslashes($service['price_range']) : ''; ?>', 
                                        '<?php echo isset($service['image_path']) ? addslashes($service['image_path']) : ''; ?>'
                                    )">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    data-bs-toggle="modal" data-bs-target="#deleteServiceModal"
                                    onclick="deleteService(<?php echo $service['id']; ?>, '<?php echo addslashes($service['name']); ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="services.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="Fenêtre PVC">Fenêtre PVC</option>
                            <option value="Fenêtre Aluminium">Fenêtre Aluminium</option>
                            <option value="Porte d'entrée">Porte d'entrée</option>
                            <option value="Porte-fenêtre">Porte-fenêtre</option>
                            <option value="Porte de garage">Porte de garage</option>
                            <option value="Portail">Portail</option>
                            <option value="Véranda">Véranda</option>
                            <option value="Pergola">Pergola</option>
                            <option value="Volet roulant">Volet roulant</option>
                            <option value="Store">Store</option>
                            <option value="Garde-corps">Garde-corps</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Gamme de Prix</label>
                        <input type="text" class="form-control" name="price_range" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="services.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="current_image" id="edit_current_image">
                
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" id="edit_type" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="Fenêtre PVC">Fenêtre PVC</option>
                            <option value="Fenêtre Aluminium">Fenêtre Aluminium</option>
                            <option value="Porte d'entrée">Porte d'entrée</option>
                            <option value="Porte-fenêtre">Porte-fenêtre</option>
                            <option value="Porte de garage">Porte de garage</option>
                            <option value="Portail">Portail</option>
                            <option value="Véranda">Véranda</option>
                            <option value="Pergola">Pergola</option>
                            <option value="Volet roulant">Volet roulant</option>
                            <option value="Store">Store</option>
                            <option value="Garde-corps">Garde-corps</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Gamme de Prix</label>
                        <input type="text" class="form-control" name="price_range" id="edit_price_range" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Image Actuelle</label>
                        <div id="current_image_preview" class="mb-2"></div>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <small class="text-muted">Laissez vide pour conserver l'image actuelle</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Service Modal -->
<div class="modal fade" id="deleteServiceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="services.php" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la Suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le service <strong id="delete_name"></strong> ?</p>
                    <p class="text-danger">Cette action est irréversible.</p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Function to handle edit service modal
function editService(id, name, type, description, priceRange, imagePath) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_price_range').value = priceRange;
    document.getElementById('edit_current_image').value = imagePath;
    
    // Show current image preview
    const imagePreview = document.getElementById('current_image_preview');
    if (imagePath) {
        imagePreview.innerHTML = `<img src="../${imagePath}" alt="${name}" class="img-thumbnail" style="max-height: 100px;">`;
    } else {
        imagePreview.innerHTML = '<div class="alert alert-info">Aucune image</div>';
    }
}

// Function to handle delete service modal
function deleteService(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
}
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
