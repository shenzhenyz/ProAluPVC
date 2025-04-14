<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Ru00e9cupu00e9rer tous les clients pour le su00e9lecteur
$stmt = $conn->prepare("SELECT id, name, email FROM clients ORDER BY name ASC");
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ru00e9cupu00e9rer tous les devis pour le su00e9lecteur
$stmt = $conn->prepare("SELECT q.id, q.title, c.name as client_name 
                        FROM quotes q 
                        LEFT JOIN clients c ON q.client_id = c.id 
                        ORDER BY q.created_at DESC");
$stmt->execute();
$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ru00e9cupu00e9rer tous les projets pour le su00e9lecteur
$stmt = $conn->prepare("SELECT p.id, p.title, c.name as client_name 
                        FROM projects p 
                        LEFT JOIN clients c ON p.client_id = c.id 
                        ORDER BY p.created_at DESC");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pru00e9-remplir les champs si on vient d'une autre page
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$related_type = isset($_GET['related_type']) ? $_GET['related_type'] : '';
$related_id = isset($_GET['related_id']) ? intval($_GET['related_id']) : 0;
$subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Traitement de l'envoi du message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = intval($_POST['client_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $related_type = $_POST['related_type'] ?? '';
    $related_id = intval($_POST['related_id'] ?? 0);
    
    // Validation
    $errors = [];
    
    if ($client_id <= 0) {
        $errors[] = "Veuillez su00e9lectionner un client.";
    }
    
    if (empty($subject)) {
        $errors[] = "Le sujet est obligatoire.";
    }
    
    if (empty($message)) {
        $errors[] = "Le message ne peut pas u00eatre vide.";
    }
    
    if (empty($errors)) {
        // Vu00e9rifier que le client existe
        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$client_id]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            $errors[] = "Client non trouvu00e9.";
        } else {
            // Enregistrer le message dans la base de donnu00e9es
            $stmt = $conn->prepare("INSERT INTO admin_messages (client_id, subject, message, related_type, related_id, created_at, status) 
                                    VALUES (?, ?, ?, ?, ?, NOW(), 'unread')");
            $result = $stmt->execute([$client_id, $subject, $message, $related_type ?: null, $related_id ?: null]);
            
            if ($result) {
                // Envoyer un email au client pour l'informer du nouveau message
                $to = $client['email'];
                $email_subject = "Nouveau message - Pro Alu et PVC";
                
                $emailContent = "<html><body>";
                $emailContent .= "<h2>Nouveau message de Pro Alu et PVC</h2>";
                $emailContent .= "<p>Bonjour {$client['name']},</p>";
                $emailContent .= "<p>Vous avez reu00e7u un nouveau message de notre part concernant <strong>'{$subject}'</strong>.</p>";
                $emailContent .= "<p>Pour consulter ce message, veuillez vous connecter u00e0 votre espace client :</p>";
                $emailContent .= "<p><a href='http://proaluetpvc.com/client/login.php' style='background-color: #4e73df; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Accu00e9der u00e0 mon espace client</a></p>";
                $emailContent .= "<p>Cordialement,<br>L'u00e9quipe Pro Alu et PVC</p>";
                $emailContent .= "</body></html>";
                
                // En-tu00eates pour l'email HTML
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: Pro Alu et PVC <noreply@proaluetpvc.com>" . "\r\n";
                
                // Envoi de l'email
                mail($to, $email_subject, $emailContent, $headers);
                
                $_SESSION['success_message'] = "Votre message a u00e9tu00e9 envoyu00e9 avec succu00e8s.";
                header('Location: messages.php');
                exit;
            } else {
                $errors[] = "Erreur lors de l'envoi du message.";
            }
        }
    }
}

$pageTitle = "Envoyer un message";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Envoyer un message</h1>
        <a href="messages.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour u00e0 la liste
        </a>
    </div>

    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Nouveau message</h6>
        </div>
        <div class="card-body">
            <form action="send_message.php" method="POST">
                <div class="form-group">
                    <label for="client_id">Destinataire</label>
                    <select class="form-control" id="client_id" name="client_id" required>
                        <option value="">Su00e9lectionnez un client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $client_id == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?> (<?php echo htmlspecialchars($client['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="subject">Sujet</label>
                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($subject); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Liu00e9 u00e0 (optionnel)</label>
                    <div class="row">
                        <div class="col-md-4">
                            <select class="form-control" id="related_type" name="related_type">
                                <option value="">Aucun</option>
                                <option value="quote" <?php echo $related_type === 'quote' ? 'selected' : ''; ?>>Devis</option>
                                <option value="project" <?php echo $related_type === 'project' ? 'selected' : ''; ?>>Projet</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <select class="form-control" id="related_id" name="related_id" disabled>
                                <option value="">Su00e9lectionnez un u00e9lu00e9ment</option>
                            </select>
                            
                            <!-- Liste des devis (cachu00e9e par du00e9faut) -->
                            <select class="form-control d-none" id="quote_options">
                                <option value="">Su00e9lectionnez un devis</option>
                                <?php foreach ($quotes as $quote): ?>
                                    <option value="<?php echo $quote['id']; ?>" <?php echo ($related_type === 'quote' && $related_id == $quote['id']) ? 'selected' : ''; ?>>
                                        Devis #<?php echo $quote['id']; ?> - <?php echo htmlspecialchars($quote['title']); ?> (<?php echo htmlspecialchars($quote['client_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <!-- Liste des projets (cachu00e9e par du00e9faut) -->
                            <select class="form-control d-none" id="project_options">
                                <option value="">Su00e9lectionnez un projet</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>" <?php echo ($related_type === 'project' && $related_id == $project['id']) ? 'selected' : ''; ?>>
                                        Projet #<?php echo $project['id']; ?> - <?php echo htmlspecialchars($project['title']); ?> (<?php echo htmlspecialchars($project['client_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="10" required></textarea>
                </div>
                
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="notify_client" name="notify_client" value="1" checked>
                    <label class="form-check-label" for="notify_client">Notifier le client par email</label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane mr-2"></i> Envoyer le message
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Gestion du champ "Liu00e9 u00e0"
document.addEventListener('DOMContentLoaded', function() {
    const relatedTypeSelect = document.getElementById('related_type');
    const relatedIdSelect = document.getElementById('related_id');
    const quoteOptions = document.getElementById('quote_options');
    const projectOptions = document.getElementById('project_options');
    
    // Fonction pour mettre u00e0 jour le su00e9lecteur d'u00e9lu00e9ments liu00e9s
    function updateRelatedOptions() {
        const selectedType = relatedTypeSelect.value;
        
        // Ru00e9initialiser le su00e9lecteur d'u00e9lu00e9ments
        relatedIdSelect.innerHTML = '';
        
        if (selectedType === '') {
            // Aucun type su00e9lectionnu00e9
            relatedIdSelect.disabled = true;
            return;
        }
        
        // Activer le su00e9lecteur
        relatedIdSelect.disabled = false;
        
        // Copier les options appropriu00e9es
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
    
    // Initialiser le su00e9lecteur d'u00e9lu00e9ments liu00e9s
    updateRelatedOptions();
    
    // Mettre u00e0 jour le su00e9lecteur d'u00e9lu00e9ments liu00e9s lorsque le type change
    relatedTypeSelect.addEventListener('change', updateRelatedOptions);
    
    // Si un type est du00e9ju00e0 su00e9lectionnu00e9 (par exemple, lors du chargement de la page)
    if (relatedTypeSelect.value !== '') {
        relatedIdSelect.disabled = false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
