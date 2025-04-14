<?php
require_once 'config/config.php';
require_once 'includes/db.php';

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Récupérer toutes les catégories
try {
    $stmt = $conn->prepare("SELECT DISTINCT category FROM projects ORDER BY category ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
    // Log error
    error_log("Erreur lors de la récupération des catégories: " . $e->getMessage());
}

// Récupérer tous les projets avec leurs images
try {
    $stmt = $conn->prepare("SELECT p.id, p.title, p.description, p.category, p.created_at FROM projects p ORDER BY p.category, p.created_at DESC");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les images pour chaque projet
    $projectsByCategory = [];
    foreach ($projects as $project) {
        // Récupérer les images du projet
        $stmt = $conn->prepare("SELECT image_path FROM project_images WHERE project_id = ?");
        $stmt->execute([$project['id']]);
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $project['images'] = $images;
        
        // Organiser par catégorie
        if (!isset($projectsByCategory[$project['category']])) {
            $projectsByCategory[$project['category']] = [];
        }
        $projectsByCategory[$project['category']][] = $project;
    }
} catch (PDOException $e) {
    $projectsByCategory = [];
    // Log error
    error_log("Erreur lors de la récupération des projets: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Réalisations - Pro Alu et PVC</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/lightbox2@2.11.3/dist/css/lightbox.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .gallery-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        
        .section-title {
            position: relative;
            margin-bottom: 50px;
            text-align: center;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .gallery-filters {
            margin-bottom: 2.5rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }

        .filter-btn {
            margin: 0.25rem;
            border-radius: 30px;
            padding: 8px 20px;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
            box-shadow: none;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .gallery-grid {
            margin-top: 30px;
        }
        
        .gallery-item {
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        
        .gallery-item-inner {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            background-color: white;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .gallery-image-container {
            position: relative;
            overflow: hidden;
            padding-top: 75%; /* 4:3 Aspect Ratio */
        }
        
        .gallery-image-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .gallery-item:hover .gallery-image-container img {
            transform: scale(1.1);
        }
        
        .gallery-item-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .project-title {
            margin-top: 0;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            color: #333;
        }

        .project-category {
            color: var(--primary-color);
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .project-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        
        .view-more {
            display: inline-block;
            color: var(--primary-color);
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            margin-top: auto;
        }
        
        .view-more:hover {
            text-decoration: underline;
        }
        
        .cta-section {
            background-color: #e9ecef;
            border-radius: 10px;
            padding: 40px;
            margin-top: 50px;
            text-align: center;
        }
        
        .cta-section .lead {
            margin-bottom: 20px;
            font-size: 1.25rem;
            font-weight: 500;
        }
        
        .cta-btn {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .cta-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        @media (max-width: 768px) {
            .gallery-filters {
                flex-direction: row;
                overflow-x: auto;
                padding-bottom: 10px;
                justify-content: flex-start;
            }
            
            .filter-btn {
                flex: 0 0 auto;
                font-size: 0.8rem;
                padding: 6px 15px;
            }
            
            .cta-section {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                Pro Alu et PVC
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#accueil">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="realisations.php">Réalisations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comparatif.php">Comparatif</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="client/login.php"><i class="bi bi-person-circle"></i> Client</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php"><i class="bi bi-lock"></i> Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-success btn-sm ms-2" href="devis.php">Demander un devis</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Gallery Content -->
    <section class="gallery-section">
        <div class="container">
            <h1 class="section-title">Nos Réalisations</h1>
            
            <!-- Gallery Filters -->
            <div class="gallery-filters">
                <button class="filter-btn active" data-filter="all">Tous les projets</button>
                <?php foreach ($categories as $category): ?>
                    <button class="filter-btn" data-filter="<?php echo htmlspecialchars($category); ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Gallery Grid -->
            <div class="row gallery-grid">
                <?php if (empty($projectsByCategory)): ?>
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Aucun projet n'est disponible pour le moment. Revenez bientôt pour découvrir nos réalisations !
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($projectsByCategory as $category => $categoryProjects): ?>
                        <?php foreach ($categoryProjects as $project): ?>
                            <div class="col-md-6 col-lg-4 gallery-item" data-category="<?php echo htmlspecialchars($category); ?>">
                                <div class="gallery-item-inner">
                                    <div class="gallery-image-container">
                                        <?php if (!empty($project['images']) && isset($project['images'][0])): ?>
                                            <a href="<?php echo htmlspecialchars($project['images'][0]); ?>" data-lightbox="gallery-<?php echo $project['id']; ?>" data-title="<?php echo htmlspecialchars($project['title']); ?>">
                                                <img src="<?php echo htmlspecialchars($project['images'][0]); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="img-fluid">
                                            </a>
                                        <?php else: ?>
                                            <img src="assets/img/placeholder.jpg" alt="<?php echo htmlspecialchars($project['title']); ?>" class="img-fluid">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="gallery-item-content">
                                        <span class="project-category"><?php echo htmlspecialchars($category); ?></span>
                                        <h3 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h3>
                                        
                                        <?php if (isset($project['description'])): ?>
                                            <p class="project-description">
                                                <?php echo nl2br(htmlspecialchars(substr($project['description'], 0, 100) . (strlen($project['description']) > 100 ? '...' : ''))); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <a href="#" class="view-more" data-bs-toggle="modal" data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                            Voir plus <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Images cachées pour Lightbox -->
                                <?php if (!empty($project['images'])): ?>
                                    <?php for ($i = 1; $i < count($project['images']); $i++): ?>
                                        <a href="<?php echo htmlspecialchars($project['images'][$i]); ?>" data-lightbox="gallery-<?php echo $project['id']; ?>" style="display: none;"></a>
                                    <?php endfor; ?>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Modal pour chaque projet -->
                            <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <?php if (!empty($project['images']) && isset($project['images'][0])): ?>
                                                        <img src="<?php echo htmlspecialchars($project['images'][0]); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="img-fluid rounded mb-3">
                                                    <?php else: ?>
                                                        <img src="assets/img/placeholder.jpg" alt="<?php echo htmlspecialchars($project['title']); ?>" class="img-fluid rounded mb-3">
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($project['images']) && count($project['images']) > 1): ?>
                                                        <div class="row g-2">
                                                            <?php for ($i = 1; $i < min(4, count($project['images'])); $i++): ?>
                                                                <div class="col-4">
                                                                    <a href="<?php echo htmlspecialchars($project['images'][$i]); ?>" data-lightbox="modal-gallery-<?php echo $project['id']; ?>">
                                                                        <img src="<?php echo htmlspecialchars($project['images'][$i]); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="img-fluid rounded">
                                                                    </a>
                                                                </div>
                                                            <?php endfor; ?>
                                                            <?php if (count($project['images']) > 4): ?>
                                                                <div class="col-4">
                                                                    <a href="<?php echo htmlspecialchars($project['images'][4]); ?>" data-lightbox="modal-gallery-<?php echo $project['id']; ?>" class="position-relative d-block">
                                                                        <img src="<?php echo htmlspecialchars($project['images'][4]); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="img-fluid rounded">
                                                                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 rounded">
                                                                            <span class="text-white fw-bold">+<?php echo count($project['images']) - 4; ?></span>
                                                                        </div>
                                                                    </a>
                                                                </div>
                                                                <?php for ($i = 5; $i < count($project['images']); $i++): ?>
                                                                    <a href="<?php echo htmlspecialchars($project['images'][$i]); ?>" data-lightbox="modal-gallery-<?php echo $project['id']; ?>" style="display: none;"></a>
                                                                <?php endfor; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 class="text-primary mb-3"><?php echo htmlspecialchars($category); ?></h5>
                                                    <?php if (isset($project['description'])): ?>
                                                        <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                                                    <?php else: ?>
                                                        <p>Aucune description disponible pour ce projet.</p>
                                                    <?php endif; ?>
                                                    <hr>
                                                    <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($project['created_at'])); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                            <a href="devis.php" class="btn btn-primary">Demander un devis similaire</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="cta-section">
                <h3>Vous avez un projet en tête ?</h3>
                <p class="lead">Nos experts sont prêts à vous accompagner dans la réalisation de votre projet sur mesure.</p>
                <a href="devis.php" class="btn btn-success cta-btn">
                    <i class="bi bi-pencil-square me-2"></i>Demander un devis gratuit
                </a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lightbox2@2.11.3/dist/js/lightbox.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Gallery filtering
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const galleryItems = document.querySelectorAll('.gallery-item');
            
            // Animation pour les éléments de la galerie
            function animateItems() {
                galleryItems.forEach((item, index) => {
                    if (item.style.display !== 'none') {
                        setTimeout(() => {
                            item.style.opacity = '1';
                            item.style.transform = 'translateY(0)';
                        }, index * 50);
                    }
                });
            }
            
            // Initialiser l'animation au chargement
            galleryItems.forEach(item => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            });
            
            // Déclencher l'animation après un court délai
            setTimeout(animateItems, 100);

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.dataset.filter;

                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Réinitialiser les styles pour l'animation
                    galleryItems.forEach(item => {
                        item.style.opacity = '0';
                        item.style.transform = 'translateY(20px)';
                    });

                    // Filter gallery items
                    galleryItems.forEach(item => {
                        if (filter === 'all' || item.dataset.category === filter) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    // Animer les éléments visibles
                    setTimeout(animateItems, 50);
                });
            });
            
            // Créer une image placeholder si elle n'existe pas
            function checkPlaceholderImage() {
                const placeholderPath = 'assets/img/placeholder.jpg';
                const img = new Image();
                img.src = placeholderPath;
                
                img.onerror = function() {
                    // Si l'image n'existe pas, créer un canvas pour générer une image placeholder
                    const canvas = document.createElement('canvas');
                    canvas.width = 800;
                    canvas.height = 600;
                    const ctx = canvas.getContext('2d');
                    
                    // Fond gris clair
                    ctx.fillStyle = '#f0f0f0';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    
                    // Texte
                    ctx.fillStyle = '#999999';
                    ctx.font = 'bold 24px Arial';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText('Image non disponible', canvas.width / 2, canvas.height / 2);
                    
                    // Logo de l'entreprise (simple)
                    ctx.font = 'bold 18px Arial';
                    ctx.fillText('Pro Alu et PVC', canvas.width / 2, canvas.height / 2 + 40);
                    
                    // Convertir le canvas en image data URL
                    const dataUrl = canvas.toDataURL('image/jpeg');
                    
                    // Remplacer toutes les images placeholder manquantes
                    document.querySelectorAll('img[src="' + placeholderPath + '"]').forEach(img => {
                        img.src = dataUrl;
                    });
                };
            }
            
            // Vérifier l'image placeholder
            checkPlaceholderImage();
        });

        // Initialize Lightbox
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': 'Image %1 sur %2',
            'fadeDuration': 300,
            'imageFadeDuration': 300
        });
    </script>
</body>
</html>
