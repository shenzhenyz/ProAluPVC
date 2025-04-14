<?php
session_start();
require_once '../includes/json_db.php';

// Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord
if (isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $db = JsonDatabase::getInstance();
        
        // Rechercher l'utilisateur par nom d'utilisateur ou email
        $client = $db->findOneBy('users', ['username' => $username, 'role' => 'client']);
        
        if (!$client) {
            // Essayer avec l'email
            $client = $db->findOneBy('users', ['email' => $username, 'role' => 'client']);
        }
        
        if ($client && password_verify($password, $client['password'])) {
            // Connexion réussie
            $_SESSION['client_logged_in'] = true;
            $_SESSION['client_id'] = $client['id'];
            $_SESSION['client_name'] = $client['name'];
            $_SESSION['client_email'] = $client['email'];
            
            // Redirection vers le tableau de bord
            header('Location: index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects';
        }
    }
}

$pageTitle = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Espace Client | Pro Alu et PVC</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .auth-card {
            max-width: 500px;
            width: 100%;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            background-color: white;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-header h2 {
            color: #4CAF50;
            font-weight: 600;
        }
        .auth-header h4 {
            color: #333;
            margin-top: 0.5rem;
        }
        .auth-header-subtitle {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #6c757d;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 5px;
            border: 1px solid #ced4da;
        }
        .form-control:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }
        .btn-success {
            background-color: #4CAF50;
            border-color: #4CAF50;
            padding: 0.75rem 1rem;
            font-weight: 500;
        }
        .btn-success:hover {
            background-color: #388E3C;
            border-color: #388E3C;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="../index.php" class="text-decoration-none">
                    <h2>Pro Alu et PVC</h2>
                </a>
                <h4>Espace Client</h4>
            </div>
            
            <div class="auth-header-subtitle">
                <p>Connectez-vous à votre espace client</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Formulaire de connexion -->
            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur ou email</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success">Se connecter</button>
                </div>
            </form>
            
            <div class="mt-3 text-center">
                <a href="../index.php" class="text-decoration-none text-secondary">Retour à l'accueil</a>
            </div>
            
            <div class="mt-3 text-center">
                <small class="text-muted">Besoin d'aide pour vous connecter ? <a href="../setup_accounts.php" class="text-success">Configurer les comptes</a></small>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
