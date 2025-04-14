<?php
// Database configuration
define('DB_HOST', '127.0.0.1');  // Using IP address instead of localhost
define('DB_PORT', 3307);  // Port spécifique pour MySQL
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'proalu_pvc');

// Site configuration
define('SITE_NAME', 'Pro Alu et PVC');
define('SITE_URL', 'http://localhost/proalu-pvc');
define('ADMIN_EMAIL', 'doudou.kamel.pro@gmail.com');

// File upload settings
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Color scheme
define('PRIMARY_COLOR', '#2E7D32'); // Dark green
define('SECONDARY_COLOR', '#000000'); // Black
define('ACCENT_COLOR', '#4CAF50'); // Light green
define('WHITE', '#FFFFFF');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
