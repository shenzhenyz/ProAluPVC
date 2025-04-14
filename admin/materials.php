<?php
// Set page variables
$pageTitle = 'Gestion des Matériaux';
$currentPage = 'materials';

// Start output buffering
ob_start();

// Include database connection
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Vérifier si la table materials existe et la créer si nécessaire
try {
    $tableExists = $conn->query("SHOW TABLES LIKE 'materials'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Charger et exécuter le script SQL pour créer la table materials
        $sqlFile = file_get_contents(dirname(__DIR__) . '/create_materials_table.sql');
        $conn->exec($sqlFile);
    }
} catch (PDOException $e) {
    $message = 'Erreur lors de la création de la table materials: ' . $e->getMessage();
    $messageType = 'danger';
}

// Handle form submissions
if (!isset($message)) $message = '';
if (!isset($messageType)) $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO materials (name, description, category, price, stock) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['category'],
                        $_POST['price'],
                        $_POST['stock']
                    ]);
                    
                    $message = 'Matériau ajouté avec succès.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erreur lors de l\'ajout du matériau: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'update':
                try {
                    $stmt = $conn->prepare("
                        UPDATE materials 
                        SET name = ?, description = ?, category = ?, price = ?, stock = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['category'],
                        $_POST['price'],
                        $_POST['stock'],
                        $_POST['id']
                    ]);
                    
                    $message = 'Matériau mis à jour avec succès.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erreur lors de la mise à jour du matériau: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    
                    $message = 'Matériau supprimé avec succès.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erreur lors de la suppression du matériau: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get all materials
try {
    $stmt = $conn->query("SELECT * FROM materials ORDER BY name ASC");
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count materials by category
    $totalMaterials = count($materials);
    $categories = [];
    $totalStock = 0;
    $averagePrice = 0;
    
    foreach ($materials as $material) {
        if (!isset($categories[$material['category']])) {
            $categories[$material['category']] = 0;
        }
        $categories[$material['category']]++;
        $totalStock += $material['stock'];
        $averagePrice += $material['price'];
    }
    
    if ($totalMaterials > 0) {
        $averagePrice = $averagePrice / $totalMaterials;
    }
    
    // Get top 3 categories
    arsort($categories);
    $topCategories = array_slice($categories, 0, 3, true);
    
} catch (PDOException $e) {
    $message = 'Erreur de base de données: ' . $e->getMessage();
    $messageType = 'danger';
    $materials = [];
    $totalMaterials = 0;
    $topCategories = [];
    $totalStock = 0;
    $averagePrice = 0;
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
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <h3><?php echo $totalMaterials; ?></h3>
            <p>Total des matériaux</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-tags"></i>
            </div>
            <h3><?php echo count($topCategories); ?></h3>
            <p>Catégories</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-archive"></i>
            </div>
            <h3><?php echo $totalStock; ?></h3>
            <p>Stock total</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-currency-euro"></i>
            </div>
            <h3><?php echo number_format($averagePrice, 2); ?>€</h3>
            <p>Prix moyen</p>
        </div>
    </div>
</div>

<!-- Materials Table -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Liste des matériaux</h5>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                <i class="bi bi-plus-circle me-2"></i> Ajouter un Matériau
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Catégorie</th>
                        <th>Prix (€/m²)</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($materials)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Aucun matériau trouvé</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($materials as $material): ?>
                    <tr>
                        <td><?php echo $material['id']; ?></td>
                        <td><?php echo htmlspecialchars($material['name']); ?></td>
                        <td><?php echo htmlspecialchars($material['description']); ?></td>
                        <td><?php echo htmlspecialchars($material['category']); ?></td>
                        <td><?php echo number_format($material['price'], 2); ?>€</td>
                        <td>
                            <span class="badge <?php echo $material['stock'] > 10 ? 'bg-success' : ($material['stock'] > 0 ? 'bg-warning' : 'bg-danger'); ?>">
                                <?php echo $material['stock']; ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-success me-1" 
                                    onclick="editMaterial(<?php echo htmlspecialchars(json_encode($material)); ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteMaterial(<?php echo $material['id']; ?>, '<?php echo htmlspecialchars($material['name']); ?>')">
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

<!-- Add Material Modal -->
<div class="modal fade" id="addMaterialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="materials.php" method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Matériau</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <select class="form-select" name="category" required>
                            <option value="">Sélectionner une catégorie</option>
                            <option value="Fenêtres">Fenêtres</option>
                            <option value="Portes">Portes</option>
                            <option value="Vitrage">Vitrage</option>
                            <option value="Accessoires">Accessoires</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prix (€/m²)</label>
                            <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock" min="0" required>
                        </div>
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

<!-- Edit Material Modal -->
<div class="modal fade" id="editMaterialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="materials.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le Matériau</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <select class="form-select" name="category" id="edit_category" required>
                            <option value="">Sélectionner une catégorie</option>
                            <option value="Fenêtres">Fenêtres</option>
                            <option value="Portes">Portes</option>
                            <option value="Vitrage">Vitrage</option>
                            <option value="Accessoires">Accessoires</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prix (€/m²)</label>
                            <input type="number" class="form-control" name="price" id="edit_price" min="0" step="0.01" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock" id="edit_stock" min="0" required>
                        </div>
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

<!-- Delete Material Modal -->
<div class="modal fade" id="deleteMaterialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="materials.php" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la Suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le matériau <strong id="delete_name"></strong> ?</p>
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
function editMaterial(material) {
    document.getElementById('edit_id').value = material.id;
    document.getElementById('edit_name').value = material.name;
    document.getElementById('edit_description').value = material.description;
    document.getElementById('edit_category').value = material.category;
    document.getElementById('edit_price').value = material.price;
    document.getElementById('edit_stock').value = material.stock;
    
    new bootstrap.Modal(document.getElementById('editMaterialModal')).show();
}

function deleteMaterial(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteMaterialModal')).show();
}
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
