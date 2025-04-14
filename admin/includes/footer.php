<?php
// Ru00e9cupu00e9rer le contenu mis en buffer
$content = ob_get_clean();

// Inclure le layout avec le contenu
include 'layout.php';
?>
