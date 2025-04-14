<?php
/**
 * Fonctions utilitaires pour l'administration
 */

/**
 * Formate une date au format français
 * @param string $date Date au format MySQL
 * @return string Date au format français (jj/mm/aaaa)
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Formate un prix avec le symbole euro
 * @param float $price Prix à formater
 * @return string Prix formaté
 */
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

/**
 * Tronque un texte à une longueur donnée
 * @param string $text Texte à tronquer
 * @param int $length Longueur maximale
 * @param string $suffix Suffixe à ajouter si le texte est tronqué
 * @return string Texte tronqué
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Génère un slug à partir d'un texte
 * @param string $text Texte à transformer en slug
 * @return string Slug
 */
function generateSlug($text) {
    // Remplacer les caractères spéciaux et les espaces par des tirets
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Translittération
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Supprimer les caractères indésirables
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remplacer les tirets multiples
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

/**
 * Vérifie si un utilisateur est un administrateur
 * @return bool True si l'utilisateur est admin, false sinon
 */
function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Génère un token CSRF
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si un token CSRF est valide
 * @param string $token Token à vérifier
 * @return bool True si le token est valide, false sinon
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}
