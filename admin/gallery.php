<?php
// Set page variables
$pageTitle = 'Galerie';
$currentPage = 'gallery';

// Start output buffering
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5>Gestion de la Galerie</h5>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addImageModal">
                <i class="bi bi-plus-circle me-2"></i> Ajouter une Image
            </button>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row" id="gallery-container">
                    <!-- Images will be loaded here -->
                    <div class="col-md-4 mb-4">
                        <div class="card gallery-item">
                            <img src="../assets/images/gallery/sample1.jpg" class="card-img-top" alt="Fenêtre Aluminium">
                            <div class="card-body">
                                <h5 class="card-title">Fenêtre Aluminium</h5>
                                <p class="card-text">Fenêtre en aluminium installée à Paris</p>
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-sm btn-success" onclick="editImage(1)"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteImage(1)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card gallery-item">
                            <img src="../assets/images/gallery/sample2.jpg" class="card-img-top" alt="Porte PVC">
                            <div class="card-body">
                                <h5 class="card-title">Porte PVC</h5>
                                <p class="card-text">Porte en PVC installée à Lyon</p>
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-sm btn-success" onclick="editImage(2)"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteImage(2)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card gallery-item">
                            <img src="../assets/images/gallery/sample3.jpg" class="card-img-top" alt="Véranda">
                            <div class="card-body">
                                <h5 class="card-title">Véranda</h5>
                                <p class="card-text">Véranda en aluminium installée à Marseille</p>
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-sm btn-success" onclick="editImage(3)"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteImage(3)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Image Modal -->
<div class="modal fade" id="addImageModal" tabindex="-1" aria-labelledby="addImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addImageModalLabel">Ajouter une Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addImageForm">
                    <div class="mb-3">
                        <label for="image_title" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="image_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="image_description" class="form-label">Description</label>
                        <textarea class="form-control" id="image_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image_category" class="form-label">Catégorie</label>
                        <select class="form-select" id="image_category">
                            <option value="aluminium">Aluminium</option>
                            <option value="pvc">PVC</option>
                            <option value="veranda">Véranda</option>
                            <option value="porte">Porte</option>
                            <option value="fenetre">Fenêtre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="image_file" class="form-label">Image</label>
                        <input type="file" class="form-control" id="image_file" accept="image/*" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="saveImageBtn">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Image Modal -->
<div class="modal fade" id="editImageModal" tabindex="-1" aria-labelledby="editImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editImageModalLabel">Modifier l'Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editImageForm">
                    <input type="hidden" id="edit_image_id">
                    <div class="mb-3">
                        <label for="edit_image_title" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="edit_image_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_image_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image_category" class="form-label">Catégorie</label>
                        <select class="form-select" id="edit_image_category">
                            <option value="aluminium">Aluminium</option>
                            <option value="pvc">PVC</option>
                            <option value="veranda">Véranda</option>
                            <option value="porte">Porte</option>
                            <option value="fenetre">Fenêtre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image Actuelle</label>
                        <div id="current_image_preview" class="mb-2">
                            <img src="" alt="Preview" style="max-width: 100%; max-height: 200px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image_file" class="form-label">Nouvelle Image (optionnel)</label>
                        <input type="file" class="form-control" id="edit_image_file" accept="image/*">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="updateImageBtn">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Image Modal -->
<div class="modal fade" id="deleteImageModal" tabindex="-1" aria-labelledby="deleteImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteImageModalLabel">Confirmer la Suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette image ? Cette action est irréversible.</p>
                <input type="hidden" id="delete_image_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add custom scripts
$scripts = <<<SCRIPTS
<script>
function editImage(id) {
    // Implement edit functionality
    const editModal = new bootstrap.Modal(document.getElementById('editImageModal'));
    document.getElementById('edit_image_id').value = id;
    editModal.show();
}

function deleteImage(id) {
    // Implement delete functionality
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteImageModal'));
    document.getElementById('delete_image_id').value = id;
    deleteModal.show();
}

document.getElementById('saveImageBtn').addEventListener('click', function() {
    // Implement save functionality here
    alert('Image ajoutée avec succès!');
    document.getElementById('addImageForm').reset();
    new bootstrap.Modal(document.getElementById('addImageModal')).hide();
});

document.getElementById('updateImageBtn').addEventListener('click', function() {
    // Implement update functionality here
    alert('Image mise à jour avec succès!');
    new bootstrap.Modal(document.getElementById('editImageModal')).hide();
});

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    // Récupérer l'ID de l'image à supprimer
    const itemId = this.getAttribute('data-id') || document.getElementById('delete_image_id').value;
    
    if (!itemId) {
        alert('Erreur: ID de l\'image non trouvé');
        return;
    }
    
    // Récupérer les éléments de la galerie
    const galleryItems = document.querySelectorAll('.gallery-item');
    let targetItem = null;
    let parentElement = null;
    
    // Trouver l'élément correspondant à l'ID
    galleryItems.forEach((item, index) => {
        if (index + 1 === parseInt(itemId)) {
            targetItem = item;
            parentElement = item.closest('.col-md-4');
        }
    });
    
    if (!targetItem || !parentElement) {
        alert('Erreur: Élément de galerie non trouvé');
        return;
    }
    
    // Pour cette démonstration, nous supprimons simplement l'élément du DOM
    parentElement.remove();
    
    // Afficher un message de confirmation
    alert(`Image ${itemId} supprimée avec succès!`);
    
    // Fermer la modal
    const deleteModal = document.getElementById('deleteImageModal');
    const modal = bootstrap.Modal.getInstance(deleteModal);
    modal.hide();
});
</script>
SCRIPTS;

// Include the layout template
require_once 'includes/layout.php';
?>
