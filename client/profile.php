<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';

$pageTitle = "Mon Profil";

// Récupérer les informations du client
$client_id = $_SESSION['client_id'];
$db = Database::getInstance();
$conn = $db->getConnection();

$message = '';
$messageType = '';

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Vérifier si l'email existe déjà pour un autre client
    try {
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
        $stmt->execute([$email, $client_id]);
        
        if ($stmt->rowCount() > 0) {
            $message = 'Cet email est déjà utilisé par un autre compte';
            $messageType = 'danger';
        } else {
            // Mise à jour des informations de base
            $stmt = $conn->prepare("UPDATE clients SET name = ?, email = ?, phone = ? WHERE id = ?");
            $result = $stmt->execute([$name, $email, $phone, $client_id]);
            
            if ($result) {
                // Mise à jour de la session
                $_SESSION['client_name'] = $name;
                $_SESSION['client_email'] = $email;
                
                $message = 'Votre profil a été mis à jour avec succès';
                $messageType = 'success';
                
                // Mise à jour du mot de passe si nécessaire
                if (!empty($current_password) && !empty($new_password)) {
                    // Vérifier le mot de passe actuel
                    $stmt = $conn->prepare("SELECT password FROM clients WHERE id = ?");
                    $stmt->execute([$client_id]);
                    $client_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (password_verify($current_password, $client_data['password'])) {
                        if ($new_password === $confirm_password) {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare("UPDATE clients SET password = ? WHERE id = ?");
                            $stmt->execute([$hashed_password, $client_id]);
                            
                            $message = 'Votre profil et mot de passe ont été mis à jour avec succès';
                        } else {
                            $message = 'Les nouveaux mots de passe ne correspondent pas';
                            $messageType = 'danger';
                        }
                    } else {
                        $message = 'Le mot de passe actuel est incorrect';
                        $messageType = 'danger';
                    }
                }
            } else {
                $message = 'Une erreur est survenue lors de la mise à jour de votre profil';
                $messageType = 'danger';
            }
        }
    } catch (PDOException $e) {
        $message = 'Une erreur est survenue: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Récupérer les informations actualisées du client
try {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $client = [
        'name' => $_SESSION['client_name'],
        'email' => $_SESSION['client_email'],
        'phone' => ''
    ];
}

// Contenu principal
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Mon Profil</h2>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType ?: 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="profile.php">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="mb-3">Informations personnelles</h5>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom complet</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($client['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h5 class="mb-3">Changer le mot de passe</h5>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    <small class="text-muted">Laissez les champs vides si vous ne souhaitez pas changer votre mot de passe.</small>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
