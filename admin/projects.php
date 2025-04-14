<?php
// Set page variables
$pageTitle = 'Gestion des Chantiers';
$currentPage = 'projects';

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
                        INSERT INTO projects (title, category, client_name, location, description, status, start_date, image_path) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    // Handle image upload
                    $imagePath = '';
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                        $uploadDir = '../uploads/projects/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                            $imagePath = 'uploads/projects/' . $fileName;
                        }
                    }
                    
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['category'],
                        $_POST['client_name'],
                        $_POST['location'],
                        $_POST['description'],
                        $_POST['status'],
                        $_POST['start_date'],
                        $imagePath
                    ]);
                    
                    $message = 'Chantier ajouté avec succès.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erreur lors de l\'ajout du chantier: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'update':
                try {
                    $imagePath = $_POST['current_image'];
                    
                    // Handle new image upload
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                        $uploadDir = '../uploads/projects/';
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
                            $imagePath = 'uploads/projects/' . $fileName;
                        }
                    }
                    
                    $stmt = $conn->prepare("
                        UPDATE projects 
                        SET title = ?, category = ?, client_name = ?, location = ?, 
                            description = ?, status = ?, start_date = ?, image_path = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['category'],
                        $_POST['client_name'],
                        $_POST['location'],
                        $_POST['description'],
                        $_POST['status'],
                        $_POST['start_date'],
                        $imagePath,
                        $_POST['id']
                    ]);
                    
                    $message = 'Chantier mis à jour avec succès.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erreur lors de la mise à jour du chantier: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'delete':
                try {
                    // Get image path before deleting
                    $stmt = $conn->prepare("SELECT image_path FROM projects WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $project = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Delete the project
                    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    
                    // Delete the image file if it exists
                    if ($project && !empty($project['image_path'])) {
                        @unlink('../' . $project['image_path']);
                    }
                    
                    $message = 'Chantier supprimé avec succès.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erreur lors de la suppression du chantier: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Vérifier si la table projects existe
try {
    // Vérifier si la table existe
    $tableExists = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'projects'")->rowCount();
    if ($checkTable > 0) {
        $tableExists = true;
    }
    
    $projects = [];
    
    if ($tableExists) {
        // Récupérer les statistiques des projets
        $totalProjects = 0;
        $plannedProjects = 0;
        $inProgressProjects = 0;
        $completedProjects = 0;

        try {
            // Total des projets
            $stmt = $conn->query("SELECT COUNT(*) FROM projects");
            $totalProjects = $stmt->fetchColumn();
            
            // Projets planifiés
            $stmt = $conn->query("SELECT COUNT(*) FROM projects WHERE status = 'planned'");
            $plannedProjects = $stmt->fetchColumn();
            
            // Projets en cours
            $stmt = $conn->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'");
            $inProgressProjects = $stmt->fetchColumn();
            
            // Projets terminés
            $stmt = $conn->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'");
            $completedProjects = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // En cas d'erreur, on continue avec les valeurs par défaut
        }

        // Récupérer les projets
        try {
            $stmt = $conn->query("SELECT * FROM projects ORDER BY created_at DESC");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $message = 'Erreur lors de la récupération des projets: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
} catch (PDOException $e) {
    $message = 'Erreur de base de données: ' . $e->getMessage();
    $messageType = 'danger';
    $projects = [];
    $totalProjects = $plannedProjects = $inProgressProjects = $completedProjects = 0;
}

// Fonction pour obtenir la classe de badge en fonction du statut
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'planned':
            return 'bg-info';
        case 'in_progress':
            return 'bg-warning';
        case 'completed':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
}

// Fonction pour obtenir le libellé du statut en français
function getStatusLabel($status) {
    switch ($status) {
        case 'planned':
            return 'Planifié';
        case 'in_progress':
            return 'En cours';
        case 'completed':
            return 'Terminé';
        default:
            return 'Inconnu';
    }
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
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-kanban"></i>
            </div>
            <h3><?php echo $totalProjects; ?></h3>
            <p class="text-muted">Total des chantiers</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background-color: #17a2b8;">
                <i class="bi bi-calendar-check"></i>
            </div>
            <h3><?php echo $plannedProjects; ?></h3>
            <p class="text-muted">Planifiés</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background-color: #ffc107;">
                <i class="bi bi-hammer"></i>
            </div>
            <h3><?php echo $inProgressProjects; ?></h3>
            <p class="text-muted">En cours</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background-color: #28a745;">
                <i class="bi bi-check-circle"></i>
            </div>
            <h3><?php echo $completedProjects; ?></h3>
            <p class="text-muted">Terminés</p>
        </div>
    </div>
</div>

<!-- Projects Table -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste des chantiers</h5>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addProjectModal">
            <i class="bi bi-plus-circle me-2"></i> Ajouter un Chantier
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Titre</th>
                        <th>Catégorie</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Aucun chantier trouvé</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($projects as $project): ?>
                    <tr>
                        <td>
                            <?php if (!empty($project['image_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($project['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($project['title']); ?>" 
                                     style="width: 60px; height: 60px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light text-center" style="width: 60px; height: 60px; line-height: 60px;">
                                    <i class="bi bi-building"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                        <td><?php echo htmlspecialchars($project['category']); ?></td>
                        <td><?php echo htmlspecialchars($project['client_name']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($project['start_date'])); ?></td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($project['status']); ?>">
                                <?php echo getStatusLabel($project['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary me-1" 
                                    onclick="viewProject(<?php echo htmlspecialchars(json_encode($project)); ?>)">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success me-1" 
                                    onclick="editProject(<?php echo htmlspecialchars(json_encode($project)); ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['title']); ?>')">
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

<!-- Add Project Modal -->
<div class="modal fade" id="addProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="projects.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Chantier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Titre</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Catégorie</label>
                            <select class="form-select" name="category" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <option value="Fenêtres Aluminium">Fenêtres Aluminium</option>
                                <option value="Fenêtres PVC">Fenêtres PVC</option>
                                <option value="Portes PVC">Portes PVC</option>
                                <option value="Portes Aluminium">Portes Aluminium</option>
                                <option value="Véranda">Véranda</option>
                                <option value="Volets Roulants">Volets Roulants</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom du Client</label>
                            <input type="text" class="form-control" name="client_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Localisation</label>
                            <input type="text" class="form-control" name="location" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date de début</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="status" required>
                                <option value="planned">Planifié</option>
                                <option value="in_progress">En cours</option>
                                <option value="completed">Terminé</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Image Principale</label>
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

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="projects.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="current_image" id="edit_current_image">
                
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le Chantier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Titre</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Catégorie</label>
                            <select class="form-select" name="category" id="edit_category" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <option value="Fenêtres Aluminium">Fenêtres Aluminium</option>
                                <option value="Fenêtres PVC">Fenêtres PVC</option>
                                <option value="Portes PVC">Portes PVC</option>
                                <option value="Portes Aluminium">Portes Aluminium</option>
                                <option value="Véranda">Véranda</option>
                                <option value="Volets Roulants">Volets Roulants</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom du Client</label>
                            <input type="text" class="form-control" name="client_name" id="edit_client_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Localisation</label>
                            <input type="text" class="form-control" name="location" id="edit_location" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date de début</label>
                            <input type="date" class="form-control" name="start_date" id="edit_start_date" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="planned">Planifié</option>
                                <option value="in_progress">En cours</option>
                                <option value="completed">Terminé</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Image Actuelle</label>
                        <div id="edit_image_preview" class="mb-2"></div>
                        <input type="file" class="form-control" name="image" accept="image/*">
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

<!-- View Project Modal -->
<div class="modal fade" id="viewProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="view_title">Détails du Chantier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div id="view_image_container" class="mb-3">
                            <!-- Image will be inserted here -->
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Informations</h6>
                                <p><strong>Client:</strong> <span id="view_client"></span></p>
                                <p><strong>Catégorie:</strong> <span id="view_category"></span></p>
                                <p><strong>Localisation:</strong> <span id="view_location"></span></p>
                                <p><strong>Date de début:</strong> <span id="view_date"></span></p>
                                <p><strong>Statut:</strong> <span id="view_status_badge"></span></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">Description</h6>
                                <p id="view_description"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Project Modal -->
<div class="modal fade" id="deleteProjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="projects.php" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la Suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le chantier <strong id="delete_name"></strong> ?</p>
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
function viewProject(project) {
    document.getElementById('view_title').textContent = project.title;
    document.getElementById('view_client').textContent = project.client_name;
    document.getElementById('view_category').textContent = project.category;
    document.getElementById('view_location').textContent = project.location;
    document.getElementById('view_date').textContent = new Date(project.start_date).toLocaleDateString('fr-FR');
    document.getElementById('view_description').textContent = project.description;
    
    // Set status badge
    let statusClass = '';
    let statusText = '';
    
    switch(project.status) {
        case 'planned':
            statusClass = 'bg-secondary';
            statusText = 'Planifié';
            break;
        case 'in_progress':
            statusClass = 'bg-warning';
            statusText = 'En cours';
            break;
        case 'completed':
            statusClass = 'bg-success';
            statusText = 'Terminé';
            break;
        default:
            statusClass = 'bg-secondary';
            statusText = 'Inconnu';
    }
    
    document.getElementById('view_status_badge').innerHTML = `<span class="badge ${statusClass}">${statusText}</span>`;
    
    // Set image
    const imageContainer = document.getElementById('view_image_container');
    if (project.image_path) {
        imageContainer.innerHTML = `<img src="../${project.image_path}" alt="${project.title}" class="img-fluid rounded">`;
    } else {
        imageContainer.innerHTML = '<div class="alert alert-info">Aucune image disponible</div>';
    }
    
    new bootstrap.Modal(document.getElementById('viewProjectModal')).show();
}

function editProject(project) {
    document.getElementById('edit_id').value = project.id;
    document.getElementById('edit_title').value = project.title;
    document.getElementById('edit_category').value = project.category;
    document.getElementById('edit_client_name').value = project.client_name;
    document.getElementById('edit_location').value = project.location;
    document.getElementById('edit_description').value = project.description;
    document.getElementById('edit_status').value = project.status;
    document.getElementById('edit_current_image').value = project.image_path;
    
    // Format date for input field (YYYY-MM-DD)
    const date = new Date(project.start_date);
    const formattedDate = date.toISOString().split('T')[0];
    document.getElementById('edit_start_date').value = formattedDate;
    
    // Set image preview
    const imagePreview = document.getElementById('edit_image_preview');
    if (project.image_path) {
        imagePreview.innerHTML = `<img src="../${project.image_path}" alt="${project.title}" style="max-width: 200px;">`;
    } else {
        imagePreview.innerHTML = '<div class="alert alert-info">Aucune image</div>';
    }
    
    new bootstrap.Modal(document.getElementById('editProjectModal')).show();
}

function deleteProject(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteProjectModal')).show();
}
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
