<?php
// Script d'initialisation de la base de données SQLite
require_once 'includes/sqlite_db.php';

$message = '';

try {
    // Obtenir l'instance de la base de données SQLite
    $db = SQLiteDatabase::getInstance();
    $conn = $db->getConnection();
    
    // Initialiser les tables
    if ($db->initializeTables()) {
        $message .= "Les tables ont été créées avec succès.<br>";
    } else {
        $message .= "Erreur lors de la création des tables.<br>";
    }
    
    // Insérer des données de démonstration
    if ($db->insertDemoData()) {
        $message .= "Les données de démonstration ont été insérées avec succès.<br>";
    } else {
        $message .= "Erreur lors de l'insertion des données de démonstration.<br>";
    }
    
} catch (PDOException $e) {
    $message = "Erreur: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialisation de la base de données - Pro Alu et PVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #2E7D32;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-success {
            background-color: #2E7D32;
            border-color: #2E7D32;
        }
        .btn-success:hover {
            background-color: #1B5E20;
            border-color: #1B5E20;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h3 class="mb-0">Initialisation de la base de données SQLite</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <h5>Résultat de l'initialisation</h5>
                            <p class="mb-0"><?php echo $message; ?></p>
                        </div>
                        
                        <h5 class="mb-3">Informations de connexion</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Espace Admin</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Nom d'utilisateur:</strong> admin</p>
                                        <p><strong>Mot de passe:</strong> admin123</p>
                                        <a href="admin/index.php" class="btn btn-primary">Accéder à l'espace admin</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">Espace Client</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Nom d'utilisateur:</strong> client</p>
                                        <p><strong>Mot de passe:</strong> client123</p>
                                        <a href="client/index.php" class="btn btn-success">Accéder à l'espace client</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
