<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Vérifier si la table clients existe et la créer si nécessaire
try {
    $tableExists = $conn->query("SHOW TABLES LIKE 'clients'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Charger et exécuter le script SQL pour créer la table clients
        $sqlFile = file_get_contents(dirname(__DIR__) . '/create_clients_table.sql');
        $conn->exec($sqlFile);
    }
} catch (PDOException $e) {
    // En cas d'erreur, on continue et on laissera l'erreur s'afficher plus tard si nécessaire
}

// Vu00e9rifier si l'ID du client est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID de client invalide.";
    header('Location: clients.php');
    exit;
}

$client_id = $_GET['id'];

// Ru00e9cupu00e9rer les informations du client
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    $_SESSION['error_message'] = "Client non trouvu00e9.";
    header('Location: clients.php');
    exit;
}

$message = '';
$messageType = '';
$password = '';

// Si des identifiants sont en attente d'envoi depuis l'ajout ou la modification
if (isset($_SESSION['credentials_to_send']) && $_SESSION['credentials_to_send']['client_id'] == $client_id) {
    $password = $_SESSION['credentials_to_send']['password'];
    unset($_SESSION['credentials_to_send']);
}

// Traitement de l'envoi des identifiants
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $client['email'];
    $name = $client['name'];
    
    // Si un nouveau mot de passe est demandu00e9
    if (isset($_POST['reset_password']) && $_POST['reset_password'] === '1') {
        // Gu00e9nu00e9rer un nouveau mot de passe
        $password = generateRandomPassword();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Mettre u00e0 jour le mot de passe dans la base de donnu00e9es
        $stmt = $conn->prepare("UPDATE clients SET password = ? WHERE id = ?");
        $result = $stmt->execute([$hashed_password, $client_id]);
        
        if (!$result) {
            $message = "Erreur lors de la mise u00e0 jour du mot de passe.";
            $messageType = "danger";
        }
    } elseif (empty($password)) {
        $message = "Veuillez gu00e9nu00e9rer un nouveau mot de passe ou saisir un mot de passe existant.";
        $messageType = "danger";
    }
    
    if (empty($message)) {
        // Pru00e9parer le contenu de l'email
        $subject = "Vos identifiants de connexion - Pro Alu et PVC";
        
        $emailContent = "<html><body>";
        $emailContent .= "<h2>Bienvenue chez Pro Alu et PVC</h2>";
        $emailContent .= "<p>Cher(e) {$name},</p>";
        $emailContent .= "<p>Votre espace client est maintenant disponible. Vous pouvez vous connecter avec les identifiants suivants :</p>";
        $emailContent .= "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        $emailContent .= "<p><strong>Email :</strong> {$email}</p>";
        $emailContent .= "<p><strong>Mot de passe :</strong> {$password}</p>";
        $emailContent .= "</div>";
        $emailContent .= "<p>Pour accu00e9der u00e0 votre espace client, veuillez cliquer sur le lien ci-dessous :</p>";
        $emailContent .= "<p><a href='http://proaluetpvc.com/client/login.php' style='background-color: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Accu00e9der u00e0 mon espace client</a></p>";
        $emailContent .= "<p>Nous vous recommandons de changer votre mot de passe du00e8s votre premiu00e8re connexion.</p>";
        $emailContent .= "<p>Si vous avez des questions, n'hu00e9sitez pas u00e0 nous contacter.</p>";
        $emailContent .= "<p>Cordialement,<br>L'u00e9quipe Pro Alu et PVC</p>";
        $emailContent .= "</body></html>";
        
        // En-tu00eates pour l'email HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Pro Alu et PVC <noreply@proaluetpvc.com>" . "\r\n";
        
        // Envoi de l'email
        $mailSent = mail($email, $subject, $emailContent, $headers);
        
        if ($mailSent) {
            $message = "Les identifiants ont u00e9tu00e9 envoyu00e9s avec succu00e8s u00e0 {$email}.";
            $messageType = "success";
            $password = ''; // Vider le mot de passe apru00e8s l'envoi
        } else {
            $message = "Erreur lors de l'envoi de l'email. Veuillez vu00e9rifier la configuration de votre serveur mail.";
            $messageType = "danger";
        }
    }
}

/**
 * Gu00e9nu00e8re un mot de passe alu00e9atoire
 * @param int $length Longueur du mot de passe
 * @return string Mot de passe gu00e9nu00e9ru00e9
 */
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $password = '';
    $max = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    
    return $password;
}

$pageTitle = "Envoyer les identifiants";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Envoyer les identifiants</h1>
        <a href="view_client.php?id=<?php echo $client_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour au profil
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Envoyer les identifiants u00e0 <?php echo htmlspecialchars($client['name']); ?></h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="client-info mb-4">
                        <h5>Informations du client</h5>
                        <p><strong>Nom :</strong> <?php echo htmlspecialchars($client['name']); ?></p>
                        <p><strong>Email :</strong> <?php echo htmlspecialchars($client['email']); ?></p>
                        <p><strong>Tu00e9lu00e9phone :</strong> <?php echo htmlspecialchars($client['phone']); ?></p>
                    </div>
                    
                    <form method="POST" action="send_credentials.php?id=<?php echo $client_id; ?>">
                        <div class="form-group">
                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="reset_password" name="reset_password" value="1">
                                <label class="custom-control-label" for="reset_password">Gu00e9nu00e9rer un nouveau mot de passe</label>
                                <small class="form-text text-muted">Cochez cette case pour gu00e9nu00e9rer un nouveau mot de passe pour ce client.</small>
                            </div>
                        </div>
                        
                        <?php if (!empty($password)): ?>
                            <div class="alert alert-info">
                                <p><strong>Mot de passe u00e0 envoyer :</strong> <?php echo htmlspecialchars($password); ?></p>
                                <small>Ce mot de passe sera envoyu00e9 au client par email.</small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-envelope mr-2"></i> Envoyer les identifiants
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-6">
                    <div class="card border-left-info">
                        <div class="card-body">
                            <h5 class="card-title">Informations importantes</h5>
                            <ul class="mb-0">
                                <li>Un email contenant les identifiants de connexion sera envoyu00e9 au client.</li>
                                <li>L'email contiendra un lien vers la page de connexion de l'espace client.</li>
                                <li>Le client sera invitu00e9 u00e0 changer son mot de passe du00e8s sa premiu00e8re connexion.</li>
                                <li>Assurez-vous que l'adresse email du client est correcte avant d'envoyer les identifiants.</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card border-left-warning mt-4">
                        <div class="card-body">
                            <h5 class="card-title">Aperu00e7u de l'email</h5>
                            <div class="email-preview p-3 bg-light rounded">
                                <p><strong>Objet :</strong> Vos identifiants de connexion - Pro Alu et PVC</p>
                                <hr>
                                <p><strong>Bienvenue chez Pro Alu et PVC</strong></p>
                                <p>Cher(e) <?php echo htmlspecialchars($client['name']); ?>,</p>
                                <p>Votre espace client est maintenant disponible. Vous pouvez vous connecter avec les identifiants suivants :</p>
                                <div class="bg-white p-2 rounded">
                                    <p><strong>Email :</strong> <?php echo htmlspecialchars($client['email']); ?></p>
                                    <p><strong>Mot de passe :</strong> [Mot de passe]</p>
                                </div>
                                <p>Pour accu00e9der u00e0 votre espace client, veuillez cliquer sur le lien ci-dessous :</p>
                                <p>[Bouton d'accu00e8s u00e0 l'espace client]</p>
                                <p>Nous vous recommandons de changer votre mot de passe du00e8s votre premiu00e8re connexion.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Activer/du00e9sactiver le champ de mot de passe en fonction de la case u00e0 cocher
document.getElementById('reset_password').addEventListener('change', function() {
    const passwordField = document.getElementById('password');
    if (this.checked) {
        passwordField.disabled = true;
    } else {
        passwordField.disabled = false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
