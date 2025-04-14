<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Validate input
    $required_fields = ['name', 'email', 'phone', 'service_id', 'message'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Le champ $field est requis");
        }
    }

    // Sanitize input - Remplacer FILTER_SANITIZE_STRING (déprécié) par htmlspecialchars
    $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8');
    $service_id = filter_var($_POST['service_id'], FILTER_SANITIZE_NUMBER_INT);
    $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
    
    // Nouveaux champs optionnels
    $address = isset($_POST['address']) ? htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8') : '';
    $budget = isset($_POST['budget']) ? htmlspecialchars($_POST['budget'], ENT_QUOTES, 'UTF-8') : '';
    $timeframe = isset($_POST['timeframe']) ? htmlspecialchars($_POST['timeframe'], ENT_QUOTES, 'UTF-8') : '';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email invalide');
    }

    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Vérifier si la table quote_requests existe et la créer si nécessaire
    $tableExists = $conn->query("SHOW TABLES LIKE 'quote_requests'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table quote_requests
        $sql = "CREATE TABLE quote_requests (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            service_id INT NOT NULL,
            message TEXT NOT NULL,
            address VARCHAR(255),
            budget VARCHAR(50),
            timeframe VARCHAR(50),
            status ENUM('new', 'processing', 'completed', 'rejected') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
    } else {
        // Vérifier si les colonnes existent et les ajouter si nécessaire
        $columnsToAdd = [
            'address' => 'VARCHAR(255)',
            'budget' => 'VARCHAR(50)',
            'timeframe' => 'VARCHAR(50)'
        ];
        
        foreach ($columnsToAdd as $column => $type) {
            $columnExists = $conn->query("SHOW COLUMNS FROM quote_requests LIKE '$column'")->rowCount() > 0;
            if (!$columnExists) {
                $conn->exec("ALTER TABLE quote_requests ADD COLUMN $column $type");
            }
        }
    }

    // Insert quote request
    $stmt = $conn->prepare("
        INSERT INTO quote_requests (name, email, phone, service_id, message, address, budget, timeframe)
        VALUES (:name, :email, :phone, :service_id, :message, :address, :budget, :timeframe)
    ");

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':service_id' => $service_id,
        ':message' => $message,
        ':address' => $address,
        ':budget' => $budget,
        ':timeframe' => $timeframe
    ]);

    // Récupérer le nom du service pour l'email
    $serviceStmt = $conn->prepare("SELECT name FROM services WHERE id = :id");
    $serviceStmt->execute([':id' => $service_id]);
    $serviceName = $serviceStmt->fetchColumn();

    // Send email notification
    $to = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'contact@proaluetpvc.com';
    $subject = "Nouvelle demande de devis - Pro Alu et PVC";
    
    $email_message = "Nouvelle demande de devis :\n\n";
    $email_message .= "Nom : $name\n";
    $email_message .= "Email : $email\n";
    $email_message .= "Téléphone : $phone\n";
    $email_message .= "Service souhaité : $serviceName\n";
    
    if (!empty($address)) {
        $email_message .= "Adresse du projet : $address\n";
    }
    
    if (!empty($budget)) {
        $email_message .= "Budget : $budget\n";
    }
    
    if (!empty($timeframe)) {
        $email_message .= "Délai souhaité : $timeframe\n";
    }
    
    $email_message .= "Message : $message\n";

    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Configuration SMTP pour localhost (XAMPP)
    ini_set('SMTP', 'localhost');
    ini_set('smtp_port', 25);
    
    // Tentative d'envoi d'email avec gestion d'erreur
    $mail_sent = @mail($to, $subject, $email_message, $headers);
    
    if (!$mail_sent) {
        // Si l'envoi d'email échoue, on continue quand même mais on enregistre l'erreur
        error_log("Échec de l'envoi d'email pour la demande de devis de $name ($email)");
    }

    // Redirection avec message de succès
    session_start();
    $_SESSION['quote_success'] = true;
    
    echo json_encode([
        'success' => true,
        'message' => 'Votre demande de devis a été envoyée avec succès'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
