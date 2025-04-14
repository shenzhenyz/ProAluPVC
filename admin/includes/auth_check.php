<?php
// Ce fichier vérifie si l'utilisateur est connecté en tant qu'administrateur
// S'il n'est pas connecté, il est redirigé vers la page de connexion

session_start();

// Vérifier si l'utilisateur est connecté
if ((!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_logged_in'])) || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] !== true)) {
    // Rediriger vers la page de connexion
    header('Location: login.php');
    exit();
}
