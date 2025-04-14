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

$errors = [];
$success = false;

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

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $reset_password = isset($_POST['reset_password']);
    
    // Validation des champs
    if (empty($name)) {
        $errors[] = "Le nom du client est obligatoire";
    }
    
    if (empty($email)) {
        $errors[] = "L'email du client est obligatoire";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    } else {
        // Vu00e9rifier si l'email existe du00e9ju00e0 pour un autre client
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
        $stmt->execute([$email, $client_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Cet email est du00e9ju00e0 utilisu00e9 par un autre client";
        }
    }
    
    if (empty($phone)) {
        $errors[] = "Le numu00e9ro de tu00e9lu00e9phone est obligatoire";
    }
    
    // Si aucune erreur, mettre u00e0 jour le client
    if (empty($errors)) {
        // Pru00e9parer la requu00eate de mise u00e0 jour
        if ($reset_password || !empty($new_password)) {
            // Si ru00e9initialisation du mot de passe demandu00e9e
            if ($reset_password && empty($new_password)) {
                $new_password = generateRandomPassword();
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE clients SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $result = $stmt->execute([$name, $email, $phone, $hashed_password, $client_id]);
            
            // Stocker le nouveau mot de passe pour affichage
            $password_updated = true;
            $generated_password = $new_password;
        } else {
            // Mise u00e0 jour sans changer le mot de passe
            $stmt = $conn->prepare("UPDATE clients SET name = ?, email = ?, phone = ? WHERE id = ?");
            $result = $stmt->execute([$name, $email, $phone, $client_id]);
            $password_updated = false;
        }
        
        if ($result) {
            $success = true;
            // Mettre u00e0 jour les donnu00e9es du client pour l'affichage
            $client['name'] = $name;
            $client['email'] = $email;
            $client['phone'] = $phone;
        } else {
            $errors[] = "Erreur lors de la mise u00e0 jour du client.";
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

$pageTitle = "Modifier le Client";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Modifier le Client</h1>
        <div>
            <a href="view_client.php?id=<?php echo $client_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm mr-2">
                <i class="fas fa-eye fa-sm text-white-50"></i> Voir le profil
            </a>
            <a href="clients.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour u00e0 la liste
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Le client a u00e9tu00e9 mis u00e0 jour avec succu00e8s.
            <?php if (isset($password_updated) && $password_updated): ?>
                <div class="mt-2">
                    <strong>Nouveau mot de passe :</strong> <?php echo htmlspecialchars($generated_password); ?>
                    <a href="send_credentials.php?id=<?php echo $client_id; ?>" class="btn btn-sm btn-primary ml-2">
                        <i class="fas fa-envelope"></i> Envoyer les identifiants par email
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Informations du client</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="edit_client.php?id=<?php echo $client_id; ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($client['name']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Tu00e9lu00e9phone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="new_password">Nouveau mot de passe</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="new_password" name="new_password">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="generatePassword">Gu00e9nu00e9rer</button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Laissez vide pour conserver le mot de passe actuel.</small>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="reset_password" name="reset_password">
                                <label class="custom-control-label" for="reset_password">Ru00e9initialiser le mot de passe</label>
                                <small class="form-text text-muted">Cochez cette case pour gu00e9nu00e9rer automatiquement un nouveau mot de passe.</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    <a href="clients.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script pour gu00e9nu00e9rer un mot de passe alu00e9atoire
document.getElementById('generatePassword').addEventListener('click', function() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    let password = '';
    const length = 10;
    
    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * chars.length);
        password += chars.charAt(randomIndex);
    }
    
    document.getElementById('new_password').value = password;
});

// Du00e9sactiver le champ de mot de passe si la ru00e9initialisation est cochu00e9e
document.getElementById('reset_password').addEventListener('change', function() {
    const passwordField = document.getElementById('new_password');
    const generateBtn = document.getElementById('generatePassword');
    
    if (this.checked) {
        passwordField.disabled = true;
        generateBtn.disabled = true;
        passwordField.value = '';
    } else {
        passwordField.disabled = false;
        generateBtn.disabled = false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
