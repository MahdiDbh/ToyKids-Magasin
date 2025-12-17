<?php
/**
 * Fichier de vérification d'authentification admin
 * À inclure au début de chaque page admin
 */

session_start();
require_once '../config.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}

// Fonction pour obtenir les informations de l'admin connecté
function getCurrentAdmin() {
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? '',
        'nom' => $_SESSION['admin_nom'] ?? '',
        'prenom' => $_SESSION['admin_prenom'] ?? ''
    ];
}
?>
