<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-window-maximize"></i>
            <span>Pro Alu et PVC</span>
        </div>
        <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="index.php" class="sidebar-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Tableau de bord</span>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="quotes.php" class="sidebar-link <?php echo $current_page === 'quotes.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i>
                <span>Mes devis</span>
                <?php
                // Compter les devis en attente
                try {
                    $db = Database::getInstance();
                    $conn = $db->getConnection();
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM quotes WHERE client_id = ? AND status = 'pending'");
                    $stmt->execute([$_SESSION['client_id']]);
                    $pendingQuotes = $stmt->fetchColumn();
                    
                    if ($pendingQuotes > 0) {
                        echo '<span class="badge">' . $pendingQuotes . '</span>';
                    }
                } catch (PDOException $e) {
                    // Ignorer les erreurs
                }
                ?>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="projects.php" class="sidebar-link <?php echo $current_page === 'projects.php' ? 'active' : ''; ?>">
                <i class="fas fa-project-diagram"></i>
                <span>Mes projets</span>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="messages.php" class="sidebar-link <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
                <?php
                // Compter les messages non lus
                try {
                    $db = Database::getInstance();
                    $conn = $db->getConnection();
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE client_id = ? AND is_read = 0 AND sender_type = 'admin'");
                    $stmt->execute([$_SESSION['client_id']]);
                    $unreadMessages = $stmt->fetchColumn();
                    
                    if ($unreadMessages > 0) {
                        echo '<span class="badge">' . $unreadMessages . '</span>';
                    }
                } catch (PDOException $e) {
                    // Ignorer les erreurs
                }
                ?>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="gallery.php" class="sidebar-link <?php echo $current_page === 'gallery.php' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i>
                <span>Galerie</span>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="profile.php" class="sidebar-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Mon profil</span>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="contact.php" class="sidebar-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">
                <i class="fas fa-phone-alt"></i>
                <span>Contact</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?php 
                $clientName = $_SESSION['client_name'] ?? 'Client';
                echo substr($clientName, 0, 1); 
                ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['client_name'] ?? 'Client'); ?></div>
                <div class="sidebar-user-role">Client</div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Toggle Button (only visible on small screens) -->
<div class="d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1050;">
    <button class="btn btn-primary mobile-toggle">
        <i class="fas fa-bars"></i>
    </button>
</div>
