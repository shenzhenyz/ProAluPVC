<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['client_logged_in']) || $_SESSION['client_logged_in'] !== true) {
    // Rediriger vers la page de connexion
    header('Location: login.php');
    exit;
}
