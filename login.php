<?php
require 'config.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // contrôles de saisie
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Veuillez renseigner tous les champs.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, username, password, first_name, last_name, profile FROM users WHERE username = ?'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // connexion réussie
            $_SESSION['user'] = $user;
            redirect('expense_report.php');
        } else {
            $errors[] = 'Identifiants incorrects.';
        }
    }
}

$pageTitle = 'Connexion GSB';
$extraCss = '<link rel="stylesheet" href="login.css">';
include 'includes/header.php';
?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h1>Connexion GSB</h1>
            <p>Accédez à votre espace personnel</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-message" style="display:block;">
                <?php echo implode('<br>', $errors); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="login-form" novalidate>
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="ex: lvillachane"
                    required
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
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
                Compte de test : <br>
                Login: <strong>lvillachane</strong><br>
                MDP: <strong>jux7g</strong>
            </p>
            <p>
                Pour créer un compte, veuillez contacter l'administrateur du système. <br>
                Pour des raisons de sécurité, l'inscription en ligne n'est pas disponible.<br>
                * Les données de connexion sont fictives et utilisées uniquement à des fins de démonstration.
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>