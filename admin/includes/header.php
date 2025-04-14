<?php
// Définir la page courante pour le menu
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Contenu à insérer dans le layout
ob_start();
?>
<!-- Le contenu de la page sera inséré ici -->
