<?php
// Set page variables
$pageTitle = 'Paramu00e8tres du Site';
$currentPage = 'settings';

// Start output buffering
ob_start();

// Include database connection
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['form_type'])) {
        switch ($_POST['form_type']) {
            case 'site_settings':
                try {
                    // Check if settings exist
                    $stmt = $conn->query("SELECT COUNT(*) FROM settings WHERE setting_group = 'site'");
                    $settingsExist = $stmt->fetchColumn() > 0;
                    
                    if ($settingsExist) {
                        // Update existing settings
                        $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE setting_name = ? AND setting_group = 'site'");
                        
                        $stmt->execute([$_POST['site_title'], 'title']);
                        $stmt->execute([$_POST['site_email'], 'email']);
                        $stmt->execute([$_POST['site_phone'], 'phone']);
                        $stmt->execute([$_POST['site_address'], 'address']);
                        $stmt->execute([$_POST['site_description'], 'description']);
                    } else {
                        // Insert new settings
                        $stmt = $conn->prepare("INSERT INTO settings (setting_group, setting_name, value) VALUES ('site', ?, ?)");
                        
                        $stmt->execute(['title', $_POST['site_title']]);
                        $stmt->execute(['email', $_POST['site_email']]);
                        $stmt->execute(['phone', $_POST['site_phone']]);
                        $stmt->execute(['address', $_POST['site_address']]);
                        $stmt->execute(['description', $_POST['site_description']]);
                    }
                    
                    $message = 'Paramu00e8tres du site mis u00e0 jour avec succu00e8s.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erreur lors de la mise u00e0 jour des paramu00e8tres du site: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'social_settings':
                try {
                    // Check if settings exist
                    $stmt = $conn->query("SELECT COUNT(*) FROM settings WHERE setting_group = 'social'");
                    $settingsExist = $stmt->fetchColumn() > 0;
                    
                    if ($settingsExist) {
                        // Update existing settings
                        $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE setting_name = ? AND setting_group = 'social'");
                        
                        $stmt->execute([$_POST['facebook'], 'facebook']);
                        $stmt->execute([$_POST['instagram'], 'instagram']);
                        $stmt->execute([$_POST['linkedin'], 'linkedin']);
                    } else {
                        // Insert new settings
                        $stmt = $conn->prepare("INSERT INTO settings (setting_group, setting_name, value) VALUES ('social', ?, ?)");
                        
                        $stmt->execute(['facebook', $_POST['facebook']]);
                        $stmt->execute(['instagram', $_POST['instagram']]);
                        $stmt->execute(['linkedin', $_POST['linkedin']]);
                    }
                    
                    $message = 'Paramu00e8tres des ru00e9seaux sociaux mis u00e0 jour avec succu00e8s.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erreur lors de la mise u00e0 jour des paramu00e8tres des ru00e9seaux sociaux: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'admin_settings':
                try {
                    // Check if admin exists
                    $stmt = $conn->prepare("SELECT id, password FROM users WHERE role = 'admin' AND id = ?");
                    $stmt->execute([$_POST['admin_id']]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($admin) {
                        // Update admin information
                        if (!empty($_POST['admin_password']) && $_POST['admin_password'] === $_POST['admin_password_confirm']) {
                            // Update with new password
                            $hashedPassword = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
                            
                            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                            $stmt->execute([
                                $_POST['admin_name'],
                                $_POST['admin_email'],
                                $hashedPassword,
                                $_POST['admin_id']
                            ]);
                        } else if (empty($_POST['admin_password'])) {
                            // Update without changing password
                            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                            $stmt->execute([
                                $_POST['admin_name'],
                                $_POST['admin_email'],
                                $_POST['admin_id']
                            ]);
                        } else {
                            throw new Exception('Les mots de passe ne correspondent pas.');
                        }
                        
                        $message = 'Paramu00e8tres administrateur mis u00e0 jour avec succu00e8s.';
                        $messageType = 'success';
                    } else {
                        $message = 'Administrateur non trouvu00e9.';
                        $messageType = 'danger';
                    }
                } catch (Exception $e) {
                    $message = 'Erreur lors de la mise u00e0 jour des paramu00e8tres administrateur: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get site settings
try {
    $siteSettings = [];
    $stmt = $conn->query("SELECT setting_name, value FROM settings WHERE setting_group = 'site'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $siteSettings[$row['setting_name']] = $row['value'];
    }
    
    // Set default values if not found
    $siteSettings = array_merge([
        'title' => 'Pro Alu et PVC',
        'email' => 'contact@proaluetpvc.com',
        'phone' => '+33 6 12 34 56 78',
        'address' => '123 Rue de l\'Aluminium, 75000 Paris',
        'description' => 'Pro Alu et PVC - Spu00e9cialiste des menuiseries aluminium et PVC sur mesure.'
    ], $siteSettings);
    
    // Get social settings
    $socialSettings = [];
    $stmt = $conn->query("SELECT setting_name, value FROM settings WHERE setting_group = 'social'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $socialSettings[$row['setting_name']] = $row['value'];
    }
    
    // Set default values if not found
    $socialSettings = array_merge([
        'facebook' => 'https://facebook.com/proaluetpvc',
        'instagram' => 'https://instagram.com/proaluetpvc',
        'linkedin' => 'https://linkedin.com/company/proaluetpvc'
    ], $socialSettings);
    
    // Get admin info
    $adminInfo = [];
    $stmt = $conn->query("SELECT id, name, email FROM users WHERE role = 'admin' LIMIT 1");
    $adminInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'id' => 1,
        'name' => 'Kamel Doudou',
        'email' => 'admin@proaluetpvc.com'
    ];
    
} catch (PDOException $e) {
    $message = 'Erreur de base de donnu00e9es: ' . $e->getMessage();
    $messageType = 'danger';
    
    // Set default values
    $siteSettings = [
        'title' => 'Pro Alu et PVC',
        'email' => 'contact@proaluetpvc.com',
        'phone' => '+33 6 12 34 56 78',
        'address' => '123 Rue de l\'Aluminium, 75000 Paris',
        'description' => 'Pro Alu et PVC - Spu00e9cialiste des menuiseries aluminium et PVC sur mesure.'
    ];
    
    $socialSettings = [
        'facebook' => 'https://facebook.com/proaluetpvc',
        'instagram' => 'https://instagram.com/proaluetpvc',
        'linkedin' => 'https://linkedin.com/company/proaluetpvc'
    ];
    
    $adminInfo = [
        'id' => 1,
        'name' => 'Kamel Doudou',
        'email' => 'admin@proaluetpvc.com'
    ];
}

// Afficher un message d'alerte si nu00e9cessaire
if (!empty($message)) {
    echo '<div class="alert alert-' . $messageType . ' alert-dismissible fade show" role="alert">';
    echo $message;
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-gear"></i>
            </div>
            <h3>Gu00e9nu00e9ral</h3>
            <p>Paramu00e8tres du site</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-share"></i>
            </div>
            <h3>Social</h3>
            <p>Ru00e9seaux sociaux</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-person-circle"></i>
            </div>
            <h3>Admin</h3>
            <p>Compte administrateur</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h3>Su00e9curitu00e9</h3>
            <p>Protection du site</p>
        </div>
    </div>
</div>

<!-- Site Settings -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Paramu00e8tres du Site</h5>
        <form action="settings.php" method="POST" class="mt-4">
            <input type="hidden" name="form_type" value="site_settings">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="site_title" class="form-label">Titre du Site</label>
                    <input type="text" class="form-control" id="site_title" name="site_title" 
                           value="<?php echo htmlspecialchars($siteSettings['title']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="site_email" class="form-label">Email de Contact</label>
                    <input type="email" class="form-control" id="site_email" name="site_email" 
                           value="<?php echo htmlspecialchars($siteSettings['email']); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="site_phone" class="form-label">Tu00e9lu00e9phone</label>
                    <input type="text" class="form-control" id="site_phone" name="site_phone" 
                           value="<?php echo htmlspecialchars($siteSettings['phone']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="site_address" class="form-label">Adresse</label>
                    <input type="text" class="form-control" id="site_address" name="site_address" 
                           value="<?php echo htmlspecialchars($siteSettings['address']); ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="site_description" class="form-label">Description du Site</label>
                <textarea class="form-control" id="site_description" name="site_description" rows="3" required><?php echo htmlspecialchars($siteSettings['description']); ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save me-2"></i> Enregistrer les Modifications
            </button>
        </form>
    </div>
</div>

<div class="row">
    <!-- Social Media Settings -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Ru00e9seaux Sociaux</h5>
                <form action="settings.php" method="POST" class="mt-4">
                    <input type="hidden" name="form_type" value="social_settings">
                    
                    <div class="mb-3">
                        <label for="facebook" class="form-label">
                            <i class="bi bi-facebook me-2 text-primary"></i> Facebook
                        </label>
                        <input type="url" class="form-control" id="facebook" name="facebook" 
                               value="<?php echo htmlspecialchars($socialSettings['facebook']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="instagram" class="form-label">
                            <i class="bi bi-instagram me-2 text-danger"></i> Instagram
                        </label>
                        <input type="url" class="form-control" id="instagram" name="instagram" 
                               value="<?php echo htmlspecialchars($socialSettings['instagram']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="linkedin" class="form-label">
                            <i class="bi bi-linkedin me-2 text-info"></i> LinkedIn
                        </label>
                        <input type="url" class="form-control" id="linkedin" name="linkedin" 
                               value="<?php echo htmlspecialchars($socialSettings['linkedin']); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-2"></i> Enregistrer les Modifications
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Admin Settings -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Paramu00e8tres de l'Administrateur</h5>
                <form action="settings.php" method="POST" class="mt-4">
                    <input type="hidden" name="form_type" value="admin_settings">
                    <input type="hidden" name="admin_id" value="<?php echo $adminInfo['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="admin_name" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="admin_name" name="admin_name" 
                               value="<?php echo htmlspecialchars($adminInfo['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" 
                               value="<?php echo htmlspecialchars($adminInfo['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Nouveau Mot de Passe</label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password">
                        <small class="text-muted">Laissez vide pour conserver le mot de passe actuel</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_password_confirm" class="form-label">Confirmer le Mot de Passe</label>
                        <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm">
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-2"></i> Mettre u00e0 Jour
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Security Settings -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Paramu00e8tres de Su00e9curitu00e9</h5>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i> Les paramu00e8tres de su00e9curitu00e9 sont gu00e9ru00e9s automatiquement par le systu00e8me.
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <h6>Derniu00e8res connexions</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>IP</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo date('d/m/Y H:i'); ?></td>
                            <td><?php echo $_SERVER['REMOTE_ADDR']; ?></td>
                            <td><span class="badge bg-success">Ru00e9ussie</span></td>
                        </tr>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime('-1 day')); ?></td>
                            <td><?php echo $_SERVER['REMOTE_ADDR']; ?></td>
                            <td><span class="badge bg-success">Ru00e9ussie</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="col-md-6">
                <h6>Options de su00e9curitu00e9</h6>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="two_factor_auth" checked disabled>
                    <label class="form-check-label" for="two_factor_auth">Authentification u00e0 deux facteurs</label>
                </div>
                
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="ip_restriction" checked disabled>
                    <label class="form-check-label" for="ip_restriction">Restriction d'accu00e8s par IP</label>
                </div>
                
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="auto_logout" checked disabled>
                    <label class="form-check-label" for="auto_logout">Du00e9connexion automatique apru00e8s inactivitu00e9</label>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
