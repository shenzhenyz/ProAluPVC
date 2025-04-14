<?php
require_once '../config/config.php';

// Du00e9truire toutes les variables de session
$_SESSION = array();

// Du00e9truire la session
session_destroy();

// Rediriger vers la page d'accueil
header('Location: ../index.php');
exit;
