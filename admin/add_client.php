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

// Traitement du formulaire d'ajout de client
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $send_credentials = isset($_POST['send_credentials']);
    
    // Validation des champs
    if (empty($name)) {
        $errors[] = "Le nom du client est obligatoire";
    }
    
    if (empty($email)) {
        $errors[] = "L'email du client est obligatoire";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Cet email est déjà utilisé par un autre client";
        }
    }
    
    if (empty($phone)) {
        $errors[] = "Le numéro de téléphone est obligatoire";
    }
    
    if (empty($password)) {
        // Générer un mot de passe aléatoire si non fourni
        $password = generateRandomPassword();
    }
    
    // Si aucune erreur, ajouter le client
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO clients (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
        $result = $stmt->execute([$name, $email, $phone, $hashed_password]);
        
        if ($result) {
            $client_id = $conn->lastInsertId();
            
            // Si l'option d'envoi des identifiants est cochée
            if ($send_credentials) {
                // Enregistrer les identifiants pour l'envoi
                $_SESSION['credentials_to_send'] = [
                    'client_id' => $client_id,
                    'email' => $email,
                    'password' => $password
                ];
                
                // Rediriger vers la page d'envoi des identifiants
                header('Location: send_credentials.php?id=' . $client_id);
                exit;
            }
            
            $_SESSION['success_message'] = "Le client a été ajouté avec succès.";
            header('Location: clients.php');
            exit;
        } else {
            $errors[] = "Erreur lors de l'ajout du client.";
        }
    }
}

/**
 * Génère un mot de passe aléatoire
 * @param int $length Longueur du mot de passe
 * @return string Mot de passe généré
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

$pageTitle = "Ajouter un Client";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Ajouter un Client</h1>
        <a href="clients.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour à la liste
        </a>
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

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Informations du client</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="add_client.php">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Téléphone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="password" name="password" value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="generatePassword">Générer</button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Laissez vide pour générer automatiquement un mot de passe.</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="send_credentials" name="send_credentials" checked>
                        <label class="custom-control-label" for="send_credentials">Envoyer les identifiants par email au client</label>
                    </div>
                </div>
                
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Ajouter le client</button>
                    <a href="clients.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script pour générer un mot de passe aléatoire
document.getElementById('generatePassword').addEventListener('click', function() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    let password = '';
    const length = 10;
    
    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * chars.length);
        password += chars.charAt(randomIndex);
    }
    
    document.getElementById('password').value = password;
});
</script>

<?php include 'includes/footer.php'; ?>
