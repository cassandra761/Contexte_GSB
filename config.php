<?php
// configuration de la base de données et démarrage de session
session_start();

// Informations de connexion  (à adapter selon votre installation)
define('DB_HOST', 'localhost');
define('DB_NAME', 'gsb');
define('DB_USER', 'gsbuser');
define('DB_PASS', 'gsbpass');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // en production, on ne renvoie pas le message d'erreur en clair
    die('Erreur de connexion à la base de données.');
}

// fonctions d'aide
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function ensureLoggedIn() {
    if (empty($_SESSION['user'])) {
        redirect('login.php');
    }
}

?>