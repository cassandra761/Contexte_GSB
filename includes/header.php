<?php
// header commun avec navigation
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'GSB'; ?></title>
    <link rel="stylesheet" href="index.css">
    <link rel="icon" href="logo GSB.png">
<?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body>
    <div id="wrap">
        <header class="header">
            <nav class="nav">
                <a href="#wrap" id="open">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                        x="0px" y="0px" width="34px" height="27px" viewBox="0 0 34 27" enable-background="new 0 0 34 27"
                        xml:space="preserve">
                        <rect fill="#FFFFFF" width="34" height="4" />
                        <rect y="11" fill="#FFFFFF" width="34" height="4" />
                        <rect y="23" fill="#FFFFFF" width="34" height="4" />
                    </svg>
                </a>
                <a href="#" id="close">×</a>
                <h1><a href="index.php">Contexte GSB</a></h1>
                <a href="history.php">Notre Histoire</a>
                <a href="search.php">Recherche et Innovation</a>
                <a href="about.php">À propos</a>
                <a href="login.php">Connexion</a>
                <a href="contact.php">Contact</a>
                <a href="legal_notices.php">Mentions légales</a>
            </nav>
        </header>
        <main class="main">
