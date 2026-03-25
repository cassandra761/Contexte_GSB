<?php
session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Connexion à la base de données
    try {
        $bdd = new PDO(
            'mysql:host=localhost;dbname=gsbV2;charset=utf8', 'root', '');
        $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (PDOException $e) {
        $_SESSION['error_message'] = 'Erreur de connexion à la base de données.';
        header('Location: login.php');
        exit();
    }

    $authenticated = false;
    $userRole = '';
    $userData = [];

    // Vérifier dans la table visiteur
    if (!$authenticated) {
        $stmt = $bdd->prepare('SELECT id, nom, prenom, login, mdp FROM visiteur WHERE login = :login');
        $stmt->execute([':login' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['mdp'] === $password) {
            $authenticated = true;
            $userRole = 'visiteur';
            $userData = $user;
        }
    }

    // Vérifier dans la table comptable
    if (!$authenticated) {
        $stmt = $bdd->prepare('SELECT id, nom, prenom, login, mdp FROM comptable WHERE login = :login');
        $stmt->execute([':login' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['mdp'] === $password) {
            $authenticated = true;
            $userRole = 'comptable';
            $userData = $user;
        }
    }

    // Vérifier dans la table administrateur
    if (!$authenticated) {
        $stmt = $bdd->prepare('SELECT id, nom, prenom, login, mdp FROM administrateur WHERE login = :login');
        $stmt->execute([':login' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['mdp'] === $password) {
            $authenticated = true;
            $userRole = 'administrateur';
            $userData = $user;
        }
    }

    // Si authentifié, créer la session
    if ($authenticated) {
        $_SESSION['user'] = [
            'login' => $userData['login'],
            'role' => $userRole,
            'nom' => $userData['nom'],
            'prenom' => $userData['prenom']
        ];

        if ($userRole === 'visiteur') {
            header('Location: expense_report.php');
        } elseif ($userRole === 'comptable') {
            header('Location: accountant.php');
        } elseif ($userRole === 'administrateur') {
            header('Location: admin.php');
        }

        exit();
    } else {
        $_SESSION['error_message'] = 'Nom d\'utilisateur ou mot de passe incorrect.';
        header('Location: login.php');
        exit();
    }
}
?>

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
                <a href="legal notices.php">Mentions légales</a>
            </nav>
        </header>
        <main class="main">
            <div class="login-container">
                <div class="login-card">
                    <div class="login-header">
                        <h1>Connexion GSB</h1>
                        <p>Accédez à votre espace personnel</p>
                    </div>

                    <div id="errorMessage" class="error-message" style="display: block; margin-bottom: 10px; color: #900; background:#fee; border:1px solid #f99; padding:8px; display:none;" ></div>

                    <?php
                    if (isset($_SESSION['error_message'])) {
                        echo '<div id="errorMessage" class="error-message" style="display:block; margin-bottom: 10px; color:#900; background:#fee; border:1px solid #f99; padding:8px;">'.htmlspecialchars($_SESSION['error_message']).'</div>';
                        unset($_SESSION['error_message']);
                    }
                    if (isset($_GET['error'])) {
                        echo '<div id="errorMessage" class="error-message" style="display:block; margin-bottom: 10px; color:#900; background:#fee; border:1px solid #f99; padding:8px;">'.htmlspecialchars($_GET['error']).'</div>';
                    }
                    ?>
                    <form id="loginForm" class="login-form" method="POST" action="login.php">
                        <div class="form-group">
                            <label for="username">
                                Nom d'utilisateur
                            </label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                placeholder="ex: lvillachane"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="password">
                                Mot de passe
                            </label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password"
                                placeholder="ex: jux7g"
                                required
                            >
                        </div>

                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Se souvenir de moi</label>
                        </div>

                        <button type="submit" class="login-btn">Se connecter</button>
                    </form>

                    <div class="login-footer">
                        <a href="#" class="forgot-password">Mot de passe oublié ?</a>
                        <p style="font-size: 12px; color: var(--gray); margin-top: 15px;">
                            Compte de test : Visiteur<br>
                            Login: <strong>lvillachane</strong><br>
                            MDP: <strong>jux7g</strong>
                        <br>
                            Compte de test : Comptable<br>
                            Login: <strong>comptable1</strong><br>
                            MDP: <strong>comp123</strong>
                        <br>
                            Compte de test : admin<br>
                            Login: <strong>admin</strong><br>
                            MDP: <strong>password</strong>
                        </p>

                        <p>

                            Pour créer un compte, veuillez contacter l'administrateur du système. <br>
                            Pour des raisons de sécurité, l'inscription en ligne n'est pas disponible.<br>
                            * Les données de connexion sont fictives et utilisées uniquement à des fins de démonstration.
                        </p>
                    </div>
                </div>


            </div>
        </main>
    </div>
    <div class="overlay"></div>
    </header>
</body>