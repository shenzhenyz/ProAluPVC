<?php
// Script pour initialiser la base de données JSON
require_once 'includes/json_db.php';

// Messages de statut
$messages = [];
$success = false;

try {
    // Créer le répertoire database s'il n'existe pas
    $databaseDir = dirname(__FILE__) . '/database';
    if (!file_exists($databaseDir)) {
        if (mkdir($databaseDir, 0777, true)) {
            $messages[] = "Répertoire database créé avec succès.";
        } else {
            throw new Exception("Impossible de créer le répertoire database.");
        }
    } else {
        $messages[] = "Le répertoire database existe déjà.";
    }
    
    // Obtenir l'instance de la base de données JSON
    $db = JsonDatabase::getInstance();
    $messages[] = "Instance de la base de données JSON créée.";
    
    // Initialiser les données de démonstration
    if ($db->initializeDemoData()) {
        $messages[] = "Données de démonstration insérées avec succès.";
    } else {
        $messages[] = "Les données de démonstration existaient déjà ou n'ont pas pu être insérées.";
    }
    
    // Vérifier si les utilisateurs ont été créés
    $users = $db->getAll('users');
    if (!empty($users)) {
        $messages[] = "Nombre d'utilisateurs dans la base de données: " . count($users);
        $success = true;
    } else {
        throw new Exception("Aucun utilisateur n'a été créé dans la base de données.");
    }
    
    // Vérifier les autres tables
    $tables = ['projects', 'quotes', 'messages', 'admin_messages'];
    foreach ($tables as $table) {
        $count = count($db->getAll($table));
        $messages[] = "Nombre d'enregistrements dans la table '$table': $count";
    }
    
    $messages[] = "Initialisation de la base de données JSON terminée avec succès.";
    
} catch (Exception $e) {
    $messages[] = "Erreur: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialisation de la base de données JSON - Pro Alu et PVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #2E7D32;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .success-message {
            color: #2E7D32;
        }
        .error-message {
            color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Initialisation de la base de données JSON - Pro Alu et PVC</h3>
            </div>
            <div class="card-body">
                <h4>Résultats de l'initialisation:</h4>
                <ul class="list-group mt-3">
                    <?php foreach ($messages as $message): ?>
                        <li class="list-group-item">
                            <?php if (strpos($message, 'Erreur') !== false): ?>
                                <i class="fas fa-times-circle text-danger me-2"></i>
                                <?php echo $message; ?>
                            <?php else: ?>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <?php echo $message; ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if ($success): ?>
                    <div class="alert alert-success mt-4">
                        <strong>Succès!</strong> La base de données JSON a été initialisée. Vous pouvez maintenant accéder à l'application.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger mt-4">
                        <strong>Erreur!</strong> Des problèmes sont survenus lors de l'initialisation de la base de données. Veuillez vérifier les messages ci-dessus.
                    </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
                    <?php if ($success): ?>
                        <a href="client/login.php" class="btn btn-success">Accéder à l'espace client</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="mb-0">Informations de connexion</h4>
                </div>
                <div class="card-body">
                    <h5>Espace Admin:</h5>
                    <ul>
                        <li><strong>Nom d'utilisateur:</strong> admin</li>
                        <li><strong>Mot de passe:</strong> admin123</li>
                        <li><strong>URL:</strong> <a href="admin/login.php">admin/login.php</a></li>
                    </ul>
                    
                    <h5>Espace Client:</h5>
                    <ul>
                        <li><strong>Nom d'utilisateur:</strong> client</li>
                        <li><strong>Mot de passe:</strong> client123</li>
                        <li><strong>URL:</strong> <a href="client/login.php">client/login.php</a></li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
