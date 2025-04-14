<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration - Pro Alu et PVC</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
            padding: 40px 0;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .setup-step {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        .setup-step h3 {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background-color: #4CAF50;
            color: white;
            border-radius: 50%;
            margin-right: 10px;
            font-weight: bold;
        }
        .setup-footer {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <div class="setup-header">
                <h1 class="mb-3">Configuration de Pro Alu et PVC</h1>
                <p class="text-muted">Cette page vous aidera à configurer votre site et à créer un compte administrateur</p>
            </div>
            
            <div class="setup-step">
                <h3><span class="step-number">1</span> Vérification de la configuration</h3>
                <div class="card">
                    <div class="card-body">
                        <?php
                        $configOk = true;
                        
                        // Vérifier si le fichier de configuration existe
                        if (file_exists('config/config.php')) {
                            echo '<div class="alert alert-success mb-2"><i class="bi bi-check-circle"></i> Le fichier de configuration existe.</div>';
                        } else {
                            echo '<div class="alert alert-danger mb-2"><i class="bi bi-x-circle"></i> Le fichier de configuration n\'existe pas.</div>';
                            $configOk = false;
                        }
                        
                        // Vérifier si le fichier de connexion à la base de données existe
                        if (file_exists('includes/db.php')) {
                            echo '<div class="alert alert-success mb-2"><i class="bi bi-check-circle"></i> Le fichier de connexion à la base de données existe.</div>';
                        } else {
                            echo '<div class="alert alert-danger mb-2"><i class="bi bi-x-circle"></i> Le fichier de connexion à la base de données n\'existe pas.</div>';
                            $configOk = false;
                        }
                        
                        // Vérifier la connexion à la base de données
                        if ($configOk) {
                            require_once 'config/config.php';
                            require_once 'includes/db.php';
                            
                            try {
                                $db = Database::getInstance();
                                $conn = $db->getConnection();
                                echo '<div class="alert alert-success mb-2"><i class="bi bi-check-circle"></i> La connexion à la base de données est établie.</div>';
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger mb-2"><i class="bi bi-x-circle"></i> Erreur de connexion à la base de données: ' . $e->getMessage() . '</div>';
                                $configOk = false;
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="setup-step">
                <h3><span class="step-number">2</span> Initialisation de la base de données</h3>
                <div class="card">
                    <div class="card-body">
                        <?php if ($configOk): ?>
                            <p>Cette étape va créer les tables nécessaires dans la base de données et un compte administrateur par défaut.</p>
                            <form action="config/init_db.php" method="GET">
                                <button type="submit" class="btn btn-primary">Initialiser la base de données</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> Veuillez d'abord corriger les problèmes de configuration avant d'initialiser la base de données.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="setup-step">
                <h3><span class="step-number">3</span> Accès à l'administration</h3>
                <div class="card">
                    <div class="card-body">
                        <p>Une fois la base de données initialisée, vous pourrez vous connecter à l'espace d'administration avec les identifiants suivants :</p>
                        <ul>
                            <li><strong>Nom d'utilisateur :</strong> admin</li>
                            <li><strong>Mot de passe :</strong> admin123</li>
                        </ul>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Pour des raisons de sécurité, n'oubliez pas de changer ce mot de passe dès votre première connexion.
                        </div>
                        <a href="admin/login.php" class="btn btn-success">Aller à la page de connexion</a>
                    </div>
                </div>
            </div>
            
            <div class="setup-footer">
                <a href="index.php" class="btn btn-outline-secondary">Retour à l'accueil</a>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
