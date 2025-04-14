<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once 'includes/auth_check.php';

// Récupérer l'ID du projet
$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$client_id = $_SESSION['client_id'];

// Vérifier si le projet existe et appartient au client
$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT p.*, s.name as service_name FROM projects p 
                        LEFT JOIN services s ON p.service_id = s.id 
                        WHERE p.id = ? AND p.client_id = ?");
$stmt->execute([$project_id, $client_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

// Si le projet n'existe pas ou n'appartient pas au client, rediriger
if (!$project) {
    header('Location: projects.php');
    exit;
}

// Récupérer les étapes du projet
$stmt = $conn->prepare("SELECT * FROM project_milestones 
                        WHERE project_id = ? 
                        ORDER BY order_number ASC");
$stmt->execute([$project_id]);
$milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les commentaires associés au projet
$stmt = $conn->prepare("SELECT * FROM project_comments 
                        WHERE project_id = ? 
                        ORDER BY created_at ASC");
$stmt->execute([$project_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les photos du projet
$stmt = $conn->prepare("SELECT * FROM project_photos 
                        WHERE project_id = ? 
                        ORDER BY created_at DESC");
$stmt->execute([$project_id]);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de l'ajout d'un commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment_text = trim($_POST['comment']);
    
    if (!empty($comment_text)) {
        $stmt = $conn->prepare("INSERT INTO project_comments (project_id, user_id, user_type, comment, created_at) 
                                VALUES (?, ?, 'client', ?, NOW())");
        $result = $stmt->execute([$project_id, $client_id, $comment_text]);
        
        if ($result) {
            // Rediriger pour éviter la soumission multiple du formulaire
            header('Location: project_details.php?id=' . $project_id);
            exit;
        }
    }
}

// Calculer la progression du projet
$progress = 0;
if ($project['status'] === 'planned') $progress = 10;
elseif ($project['status'] === 'in_progress') $progress = 50;
elseif ($project['status'] === 'completed') $progress = 100;

$pageTitle = "Détails du Projet #" . str_pad($project['id'], 5, '0', STR_PAD_LEFT);
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Projet #<?php echo str_pad($project['id'], 5, '0', STR_PAD_LEFT); ?></h2>
                <a href="projects.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i> Retour aux projets</a>
            </div>
            
            <!-- Détails du projet -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Informations du projet</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Type de projet :</strong> <?php echo htmlspecialchars($project['service_name']); ?></p>
                            <p><strong>Date de début :</strong> <?php echo date('d/m/Y', strtotime($project['created_at'])); ?></p>
                            <p><strong>Date de fin estimée :</strong> 
                                <?php echo !empty($project['estimated_end_date']) ? date('d/m/Y', strtotime($project['estimated_end_date'])) : 'À déterminer'; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p>
                                <strong>Statut :</strong>
                                <?php if ($project['status'] === 'planned'): ?>
                                    <span class="badge bg-info">Planifié</span>
                                <?php elseif ($project['status'] === 'in_progress'): ?>
                                    <span class="badge bg-primary">En cours</span>
                                <?php elseif ($project['status'] === 'completed'): ?>
                                    <span class="badge bg-success">Terminé</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>Progression :</strong></p>
                            <div class="progress mb-2" style="height: 20px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" 
                                    aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $progress; ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($project['description'])): ?>
                        <hr>
                        <h6>Description du projet :</h6>
                        <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Étapes du projet -->
            <?php if (count($milestones) > 0): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Étapes du projet</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($milestones as $index => $milestone): ?>
                            <div class="timeline-item mb-4">
                                <div class="d-flex">
                                    <div class="timeline-indicator me-3">
                                        <?php if ($milestone['status'] === 'completed'): ?>
                                            <div class="indicator-circle bg-success"><i class="bi bi-check-lg text-white"></i></div>
                                        <?php elseif ($milestone['status'] === 'in_progress'): ?>
                                            <div class="indicator-circle bg-primary"><i class="bi bi-gear text-white"></i></div>
                                        <?php else: ?>
                                            <div class="indicator-circle bg-secondary"><i class="bi bi-clock text-white"></i></div>
                                        <?php endif; ?>
                                        <?php if ($index < count($milestones) - 1): ?>
                                            <div class="indicator-line"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>
                                            <?php echo htmlspecialchars($milestone['title']); ?>
                                            <?php if ($milestone['status'] === 'completed'): ?>
                                                <span class="badge bg-success ms-2">Terminé</span>
                                            <?php elseif ($milestone['status'] === 'in_progress'): ?>
                                                <span class="badge bg-primary ms-2">En cours</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary ms-2">À venir</span>
                                            <?php endif; ?>
                                        </h6>
                                        <p class="text-muted small">
                                            <?php if ($milestone['status'] === 'completed' && !empty($milestone['completed_date'])): ?>
                                                Terminé le <?php echo date('d/m/Y', strtotime($milestone['completed_date'])); ?>
                                            <?php elseif (!empty($milestone['estimated_date'])): ?>
                                                Prévu pour le <?php echo date('d/m/Y', strtotime($milestone['estimated_date'])); ?>
                                            <?php endif; ?>
                                        </p>
                                        <p><?php echo nl2br(htmlspecialchars($milestone['description'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Photos du projet -->
            <?php if (count($photos) > 0): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Photos du projet</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($photos as $photo): ?>
                            <div class="col-md-4 mb-3">
                                <a href="../uploads/project_photos/<?php echo $photo['filename']; ?>" target="_blank">
                                    <img src="../uploads/project_photos/<?php echo $photo['filename']; ?>" 
                                         class="img-fluid rounded" 
                                         alt="<?php echo htmlspecialchars($photo['title']); ?>">
                                </a>
                                <p class="small mt-1"><?php echo htmlspecialchars($photo['title']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Commentaires -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Discussion</h5>
                </div>
                <div class="card-body">
                    <?php if (count($comments) > 0): ?>
                        <div class="comments mb-4">
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment mb-3 p-3 <?php echo $comment['user_type'] === 'admin' ? 'bg-light text-start' : 'bg-success bg-opacity-10 text-end'; ?> rounded">
                                    <div class="comment-content">
                                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                    </div>
                                    <div class="comment-meta small text-muted mt-2">
                                        <?php echo $comment['user_type'] === 'admin' ? 'Pro Alu et PVC' : 'Vous'; ?> - 
                                        <?php echo date('d/m/Y à H:i', strtotime($comment['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted my-4">Aucun message dans cette discussion pour le moment.</p>
                    <?php endif; ?>
                    
                    <!-- Formulaire d'ajout de commentaire -->
                    <form method="POST" action="project_details.php?id=<?php echo $project_id; ?>">
                        <div class="mb-3">
                            <label for="comment" class="form-label">Ajouter un message</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Envoyer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-indicator {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.indicator-circle {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
}
.indicator-line {
    width: 2px;
    background-color: #dee2e6;
    height: 100%;
    position: absolute;
    top: 30px;
    bottom: 0;
    left: 14px;
    z-index: 1;
}
</style>

<?php include 'includes/footer.php'; ?>
