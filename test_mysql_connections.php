<?php
// Script pour tester différentes configurations de connexion MySQL
$connections = [
    [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'root',
        'pass' => '',
        'name' => 'proaluetpvc'
    ],
    [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'pass' => '',
        'name' => 'proaluetpvc'
    ],
    [
        'host' => 'localhost',
        'port' => 3307,
        'user' => 'root',
        'pass' => '',
        'name' => 'proaluetpvc'
    ],
    [
        'host' => '127.0.0.1',
        'port' => 3307,
        'user' => 'root',
        'pass' => '',
        'name' => 'proaluetpvc'
    ],
    [
        'host' => 'localhost',
        'socket' => true,
        'user' => 'root',
        'pass' => '',
        'name' => 'proaluetpvc'
    ]
];

$results = [];

foreach ($connections as $index => $config) {
    $results[$index] = [
        'config' => $config,
        'success' => false,
        'message' => '',
        'time' => 0
    ];
    
    try {
        $start = microtime(true);
        
        if (isset($config['socket']) && $config['socket']) {
            // Connexion via socket
            $dsn = "mysql:host={$config['host']};dbname={$config['name']};unix_socket=/tmp/mysql.sock";
            $conn = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        } else {
            // Connexion standard
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']}";
            $conn = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        
        // Tester la connexion
        $stmt = $conn->query("SELECT 1");
        $end = microtime(true);
        $time = round(($end - $start) * 1000, 2);
        
        $results[$index]['success'] = true;
        $results[$index]['message'] = "Connexion réussie en $time ms";
        $results[$index]['time'] = $time;
        
        // Tester la création de la base de données si elle n'existe pas
        try {
            $conn->exec("CREATE DATABASE IF NOT EXISTS proaluetpvc");
            $results[$index]['message'] .= "<br>Base de données créée ou déjà existante";
            
            // Sélectionner la base de données
            $conn->exec("USE proaluetpvc");
            
            // Vérifier si la table users existe
            $stmt = $conn->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() > 0) {
                $results[$index]['message'] .= "<br>La table 'users' existe";
                
                // Compter les utilisateurs
                $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $results[$index]['message'] .= "<br>Nombre d'utilisateurs: $count";
            } else {
                $results[$index]['message'] .= "<br>La table 'users' n'existe pas";
            }
        } catch (PDOException $e) {
            $results[$index]['message'] .= "<br>Erreur lors de la création/vérification de la base de données: " . $e->getMessage();
        }
        
    } catch (PDOException $e) {
        $results[$index]['message'] = "Erreur: " . $e->getMessage();
    }
}

// Trouver la meilleure configuration
$bestConfig = null;
foreach ($results as $index => $result) {
    if ($result['success']) {
        if ($bestConfig === null || $result['time'] < $results[$bestConfig]['time']) {
            $bestConfig = $index;
        }
    }
}

// Générer le code de configuration
$configCode = '';
if ($bestConfig !== null) {
    $config = $connections[$bestConfig];
    $configCode = "<?php\n";
    $configCode .= "// Database configuration\n";
    $configCode .= "define('DB_HOST', '{$config['host']}');\n";
    if (!isset($config['socket']) || !$config['socket']) {
        $configCode .= "define('DB_PORT', {$config['port']});\n";
    }
    $configCode .= "define('DB_USER', '{$config['user']}');\n";
    $configCode .= "define('DB_PASS', '{$config['pass']}');\n";
    $configCode .= "define('DB_NAME', '{$config['name']}');\n\n";
    $configCode .= "// Site configuration\n";
    $configCode .= "define('SITE_NAME', 'Pro Alu et PVC');\n";
    $configCode .= "define('SITE_URL', 'http://localhost/proalu-pvc');\n";
    $configCode .= "define('ADMIN_EMAIL', 'doudou.kamel.pro@gmail.com');\n\n";
    $configCode .= "// File upload settings\n";
    $configCode .= "define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');\n";
    $configCode .= "define('MAX_FILE_SIZE', 5242880); // 5MB\n";
    $configCode .= "define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);\n\n";
    $configCode .= "// Color scheme\n";
    $configCode .= "define('PRIMARY_COLOR', '#2E7D32'); // Dark green\n";
    $configCode .= "define('SECONDARY_COLOR', '#000000'); // Black\n";
    $configCode .= "define('ACCENT_COLOR', '#4CAF50'); // Light green\n";
    $configCode .= "?>";
}

// Générer le code de connexion
$dbCode = '';
if ($bestConfig !== null) {
    $config = $connections[$bestConfig];
    $dbCode = "<?php\n";
    $dbCode .= "require_once dirname(__DIR__) . '/config/config.php';\n\n";
    $dbCode .= "class Database {\n";
    $dbCode .= "    private static \$instance = null;\n";
    $dbCode .= "    private \$conn;\n\n";
    $dbCode .= "    private function __construct() {\n";
    $dbCode .= "        try {\n";
    $dbCode .= "            \$options = [\n";
    $dbCode .= "                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
    $dbCode .= "                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
    $dbCode .= "                PDO::ATTR_TIMEOUT => 5,\n";
    $dbCode .= "                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8\"\n";
    $dbCode .= "            ];\n\n";
    
    if (isset($config['socket']) && $config['socket']) {
        $dbCode .= "            \$this->conn = new PDO(\n";
        $dbCode .= "                \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";unix_socket=/tmp/mysql.sock\",\n";
        $dbCode .= "                DB_USER,\n";
        $dbCode .= "                DB_PASS,\n";
        $dbCode .= "                \$options\n";
        $dbCode .= "            );\n";
    } else {
        $dbCode .= "            \$this->conn = new PDO(\n";
        $dbCode .= "                \"mysql:host=\" . DB_HOST . \";port=\" . DB_PORT . \";dbname=\" . DB_NAME,\n";
        $dbCode .= "                DB_USER,\n";
        $dbCode .= "                DB_PASS,\n";
        $dbCode .= "                \$options\n";
        $dbCode .= "            );\n";
    }
    
    $dbCode .= "        } catch(PDOException \$e) {\n";
    $dbCode .= "            die(\"Connection failed: \" . \$e->getMessage());\n";
    $dbCode .= "        }\n";
    $dbCode .= "    }\n\n";
    $dbCode .= "    public static function getInstance() {\n";
    $dbCode .= "        if (!self::\$instance) {\n";
    $dbCode .= "            self::\$instance = new Database();\n";
    $dbCode .= "        }\n";
    $dbCode .= "        return self::\$instance;\n";
    $dbCode .= "    }\n\n";
    $dbCode .= "    public function getConnection() {\n";
    $dbCode .= "        return \$this->conn;\n";
    $dbCode .= "    }\n";
    $dbCode .= "}\n";
    $dbCode .= "?>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test des connexions MySQL - Pro Alu et PVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #2E7D32;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Test des connexions MySQL</h1>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Résultats des tests</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($results as $index => $result): ?>
                            <div class="card mb-3 <?php echo $result['success'] ? 'success' : 'error'; ?>">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        Configuration #<?php echo $index + 1; ?>
                                        <?php if ($bestConfig !== null && $bestConfig === $index): ?>
                                            <span class="badge bg-success">Meilleure configuration</span>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <h6>Paramètres :</h6>
                                    <ul>
                                        <li>Hôte : <?php echo $result['config']['host']; ?></li>
                                        <?php if (isset($result['config']['socket']) && $result['config']['socket']): ?>
                                            <li>Socket : /tmp/mysql.sock</li>
                                        <?php else: ?>
                                            <li>Port : <?php echo $result['config']['port']; ?></li>
                                        <?php endif; ?>
                                        <li>Utilisateur : <?php echo $result['config']['user']; ?></li>
                                        <li>Mot de passe : <?php echo empty($result['config']['pass']) ? '(vide)' : '****'; ?></li>
                                        <li>Base de données : <?php echo $result['config']['name']; ?></li>
                                    </ul>
                                    <h6>Résultat :</h6>
                                    <p><?php echo $result['message']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($bestConfig === null): ?>
                            <div class="alert alert-danger">
                                <h4>Aucune configuration n'a fonctionné</h4>
                                <p>Voici quelques suggestions pour résoudre le problème :</p>
                                <ul>
                                    <li>Vérifiez que le serveur MySQL est en cours d'exécution</li>
                                    <li>Vérifiez que les identifiants de connexion sont corrects</li>
                                    <li>Essayez de redémarrer le service MySQL</li>
                                    <li>Vérifiez les journaux d'erreurs MySQL</li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h4>Configuration optimale trouvée !</h4>
                                <p>La configuration #<?php echo $bestConfig + 1; ?> a fonctionné avec succès.</p>
                            </div>
                            
                            <h3 class="mt-4">Fichier de configuration</h3>
                            <p>Voici le contenu à mettre dans <code>config/config.php</code> :</p>
                            <pre><?php echo htmlspecialchars($configCode); ?></pre>
                            
                            <h3 class="mt-4">Fichier de connexion</h3>
                            <p>Voici le contenu à mettre dans <code>includes/db.php</code> :</p>
                            <pre><?php echo htmlspecialchars($dbCode); ?></pre>
                            
                            <div class="mt-4">
                                <form method="post" action="apply_mysql_config.php">
                                    <input type="hidden" name="config_code" value="<?php echo htmlspecialchars($configCode); ?>">
                                    <input type="hidden" name="db_code" value="<?php echo htmlspecialchars($dbCode); ?>">
                                    <input type="hidden" name="config_index" value="<?php echo $bestConfig; ?>">
                                    <button type="submit" class="btn btn-success">Appliquer cette configuration</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
