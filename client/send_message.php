<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';

// Récupérer les informations du client
$client_id = $_SESSION['client_id'];
$db = Database::getInstance();
$conn = $db->getConnection();

// Récupérer les devis du client pour le sélecteur
$stmt = $conn->prepare("SELECT id, title FROM quotes WHERE client_id = ? ORDER BY created_at DESC");
$stmt->execute([$client_id]);
$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les projets du client pour le sélecteur
$stmt = $conn->prepare("SELECT id, title FROM projects WHERE client_id = ? ORDER BY created_at DESC");
$stmt->execute([$client_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pré-remplir les champs si on vient d'une autre page
$related_type = isset($_GET['related_type']) ? $_GET['related_type'] : '';
$related_id = isset($_GET['related_id']) ? intval($_GET['related_id']) : 0;
$subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Traitement de l'envoi du message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $related_type = $_POST['related_type'] ?? '';
    $related_id = intval($_POST['related_id'] ?? 0);
    
    // Validation
    $errors = [];
    
    if (empty($subject)) {
        $errors[] = "Le sujet est obligatoire.";
    }
    
    if (empty($message)) {
        $errors[] = "Le message ne peut pas être vide.";
    }
    
    if (empty($errors)) {
        // Enregistrer le message dans la base de données
        $stmt = $conn->prepare("INSERT INTO messages (client_id, subject, message, related_type, related_id, created_at, status) 
                                VALUES (?, ?, ?, ?, ?, NOW(), 'unread')");
        $result = $stmt->execute([$client_id, $subject, $message, $related_type ?: null, $related_id ?: null]);
        
        if ($result) {
            $_SESSION['success_message'] = "Votre message a été envoyé avec succès. L'administration vous répondra dans les plus brefs délais.";
            header('Location: messages.php');
            exit;
        } else {
            $errors[] = "Erreur lors de l'envoi du message.";
        }
    }
}

$pageTitle = "Envoyer un message";
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
                <h2 class="h3">Envoyer un message</h2>
                <a href="messages.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i> Retour aux messages
                </a>
            </div>
            
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <form action="send_message.php" method="POST">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Sujet</label>
                            <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($subject); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Lié à (optionnel)</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <select class="form-select" id="related_type" name="related_type">
                                        <option value="">Aucun</option>
                                        <option value="quote" <?php echo $related_type === 'quote' ? 'selected' : ''; ?>>Devis</option>
                                        <option value="project" <?php echo $related_type === 'project' ? 'selected' : ''; ?>>Projet</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <select class="form-select" id="related_id" name="related_id" disabled>
                                        <option value="">Sélectionnez un élément</option>
                                    </select>
                                    
                                    <!-- Liste des devis (cachée par défaut) -->
                                    <select class="form-select d-none" id="quote_options">
                                        <option value="">Sélectionnez un devis</option>
                                        <?php foreach ($quotes as $quote): ?>
                                            <option value="<?php echo $quote['id']; ?>" <?php echo ($related_type === 'quote' && $related_id == $quote['id']) ? 'selected' : ''; ?>>
                                                Devis #<?php echo $quote['id']; ?> - <?php echo htmlspecialchars($quote['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <!-- Liste des projets (cachée par défaut) -->
                                    <select class="form-select d-none" id="project_options">
                                        <option value="">Sélectionnez un projet</option>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?php echo $project['id']; ?>" <?php echo ($related_type === 'project' && $related_id == $project['id']) ? 'selected' : ''; ?>>
                                                Projet #<?php echo $project['id']; ?> - <?php echo htmlspecialchars($project['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="8" required></textarea>
                            <div class="form-text">Soyez aussi précis que possible pour faciliter le traitement de votre demande.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i> Envoyer le message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body p-4">
                    <h5><i class="bi bi-info-circle me-2"></i> Informations</h5>
                    <p class="mb-0">Notre équipe s'engage à vous répondre dans un délai de 24 à 48 heures ouvrables. Pour les demandes urgentes, veuillez nous contacter directement par téléphone au numéro indiqué sur notre site web.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion du champ "Lié à"
document.addEventListener('DOMContentLoaded', function() {
    const relatedTypeSelect = document.getElementById('related_type');
    const relatedIdSelect = document.getElementById('related_id');
    const quoteOptions = document.getElementById('quote_options');
    const projectOptions = document.getElementById('project_options');
    
    // Fonction pour mettre à jour le sélecteur d'éléments liés
    function updateRelatedOptions() {
        const selectedType = relatedTypeSelect.value;
        
        // Réinitialiser le sélecteur d'éléments
        relatedIdSelect.innerHTML = '';
        
        if (selectedType === '') {
            // Aucun type sélectionné
            relatedIdSelect.disabled = true;
            return;
        }
        
        // Activer le sélecteur
        relatedIdSelect.disabled = false;
        
        // Copier les options appropriées
        const sourceSelect = selectedType === 'quote' ? quoteOptions : projectOptions;
        const options = sourceSelect.querySelectorAll('option');
        
        options.forEach(function(option) {
            const newOption = document.createElement('option');
            newOption.value = option.value;
            newOption.text = option.text;
            newOption.selected = option.selected;
            relatedIdSelect.appendChild(newOption);
        });
    }
    
    // Initialiser le sélecteur d'éléments liés
    updateRelatedOptions();
    
    // Mettre à jour le sélecteur d'éléments liés lorsque le type change
    relatedTypeSelect.addEventListener('change', updateRelatedOptions);
    
    // Si un type est déjà sélectionné (par exemple, lors du chargement de la page)
    if (relatedTypeSelect.value !== '') {
        relatedIdSelect.disabled = false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
