<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion GSB</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="login.css">
    <link rel="icon" href="logo GSB.png">
</head>

<?php
$bdd = new PDO('mysql:host=localhost;dbname=test;charset=utf8', 'usertest', 'password');
?>

<body>
    <div class="container">
        <div class="login-container">
            <h1>Connexion</h1>
            <form action="login.php" method="post">
                <input type="text" name="username" placeholder="Nom d'utilisateur" required>
                <input type="password" name="password" placeholder="Mot de passe" required>
                <button type="submit">Se connecter</button>
            </form>
        </div>
    </div>

</body>