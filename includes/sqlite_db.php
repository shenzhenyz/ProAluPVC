<?php
require_once dirname(__DIR__) . '/config/sqlite_config.php';

class SQLiteDatabase {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            // Créer le répertoire database s'il n'existe pas
            $databaseDir = dirname(__DIR__) . '/database';
            if (!file_exists($databaseDir)) {
                mkdir($databaseDir, 0777, true);
            }
            
            // Connexion à la base de données SQLite
            $this->conn = new PDO('sqlite:' . DB_PATH);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Activer les contraintes de clé étrangère
            $this->conn->exec('PRAGMA foreign_keys = ON');
            
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new SQLiteDatabase();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
    
    // Méthode pour initialiser les tables de la base de données
    public function initializeTables() {
        try {
            // Table users
            $this->conn->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                phone TEXT,
                address TEXT,
                role TEXT NOT NULL DEFAULT 'client',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Table projects
            $this->conn->exec("CREATE TABLE IF NOT EXISTS projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                status TEXT NOT NULL DEFAULT 'pending',
                start_date TIMESTAMP,
                end_date TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )");
            
            // Table quotes
            $this->conn->exec("CREATE TABLE IF NOT EXISTS quotes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                project_id INTEGER,
                title TEXT NOT NULL,
                description TEXT,
                amount REAL,
                status TEXT NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (project_id) REFERENCES projects(id)
            )");
            
            // Table messages
            $this->conn->exec("CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sender_id INTEGER NOT NULL,
                receiver_id INTEGER NOT NULL,
                subject TEXT NOT NULL,
                content TEXT NOT NULL,
                is_read INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id),
                FOREIGN KEY (receiver_id) REFERENCES users(id)
            )");
            
            // Table admin_messages
            $this->conn->exec("CREATE TABLE IF NOT EXISTS admin_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                subject TEXT NOT NULL,
                content TEXT NOT NULL,
                is_read INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )");
            
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Méthode pour insérer des données de démonstration
    public function insertDemoData() {
        try {
            // Vérifier si des utilisateurs existent déjà
            $stmt = $this->conn->query("SELECT COUNT(*) FROM users");
            $userCount = $stmt->fetchColumn();
            
            if ($userCount == 0) {
                // Insérer un administrateur
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $this->conn->exec("INSERT INTO users (username, password, email, name, role) 
                    VALUES ('admin', '$adminPassword', 'admin@proalu.com', 'Administrateur', 'admin')");
                
                // Insérer un client
                $clientPassword = password_hash('client123', PASSWORD_DEFAULT);
                $this->conn->exec("INSERT INTO users (username, password, email, name, phone, address, role) 
                    VALUES ('client', '$clientPassword', 'client@example.com', 'Jean Dupont', '06 12 34 56 78', '123 Rue de Paris, 75001 Paris', 'client')");
                
                // Insérer des projets pour le client
                $this->conn->exec("INSERT INTO projects (user_id, title, description, status, start_date) 
                    VALUES (2, 'Rénovation fenêtres', 'Remplacement de 5 fenêtres PVC', 'in_progress', '2023-01-15')");
                $this->conn->exec("INSERT INTO projects (user_id, title, description, status, start_date) 
                    VALUES (2, 'Installation porte d''entrée', 'Nouvelle porte d''entrée en aluminium', 'pending', '2023-02-20')");
                
                // Insérer des devis pour le client
                $this->conn->exec("INSERT INTO quotes (user_id, project_id, title, description, amount, status) 
                    VALUES (2, 1, 'Devis fenêtres PVC', 'Remplacement de 5 fenêtres PVC double vitrage', 3500.00, 'accepted')");
                $this->conn->exec("INSERT INTO quotes (user_id, project_id, title, description, amount, status) 
                    VALUES (2, 2, 'Devis porte d''entrée', 'Porte d''entrée en aluminium avec serrure 3 points', 1800.00, 'pending')");
                
                // Insérer des messages
                $this->conn->exec("INSERT INTO messages (sender_id, receiver_id, subject, content) 
                    VALUES (1, 2, 'Confirmation de rendez-vous', 'Bonjour, je vous confirme notre rendez-vous pour la prise de mesures le 20 janvier à 14h.')");
                $this->conn->exec("INSERT INTO messages (sender_id, receiver_id, subject, content) 
                    VALUES (2, 1, 'Question sur le devis', 'Bonjour, j''aurais besoin de précisions concernant le devis des fenêtres. Merci.')");
                
                // Insérer des messages admin
                $this->conn->exec("INSERT INTO admin_messages (user_id, subject, content) 
                    VALUES (2, 'Bienvenue chez Pro Alu et PVC', 'Bienvenue dans votre espace client. N''hésitez pas à nous contacter pour toute question.')");
            }
            
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>
