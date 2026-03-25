<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Optionnel : vérifier le rôle
if ($_SESSION['user']['role'] !== 'comptable') {
    die('Accès interdit.');
}

// Connexion PDO
try {
    $bdd = new PDO(
        'mysql:host=localhost;dbname=gsbV2;charset=utf8', 'comptable1', 'comp123');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// Récupérer les informations de l'utilisateur (en fonction du login session)
// Note : la table comptable peut être interrogée si l'ID est disponible en session.
// Dans ce cas, nous affichons simplement le login stocké dans la session.
$comptableLogin = $_SESSION['user']['login'] ?? 'comptable';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['idVisiteur'], $_POST['mois'])) {
    $action = $_POST['action'];
    $idVisiteur = $_POST['idVisiteur'];
    $mois = $_POST['mois'];

    $etatCible = '';
    if ($action === 'valider') {
        $etatCible = 'VA';
    } elseif ($action === 'refuser') {
        $etatCible = 'RB';
    }

    if ($etatCible !== '') {
        try {
            $updateStmt = $bdd->prepare('UPDATE FicheFrais SET idEtat = :etat, dateModif = :dateModif WHERE idVisiteur = :idVisiteur AND mois = :mois');
            $updateStmt->execute([
                ':etat' => $etatCible,
                ':dateModif' => date('Y-m-d'),
                ':idVisiteur' => $idVisiteur,
                ':mois' => $mois,
            ]);
            $message = ($action === 'valider') ? 'Fiche de frais validée.' : 'Fiche de frais refusée.';
        } catch (PDOException $e) {
            $message = 'Erreur lors de la mise à jour : ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Récupérer les fiches de frais en attente (CR)
try {
    $fichesStmt = $bdd->prepare('SELECT f.*, v.nom, v.prenom FROM FicheFrais AS f JOIN visiteur AS v ON f.idVisiteur = v.id WHERE f.idEtat = :etat ORDER BY f.dateModif DESC');
    $fichesStmt->execute([':etat' => 'CR']);
    $fiches = $fichesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer l'historique des fiches validées ou refusées
    $historiqueStmt = $bdd->prepare('SELECT f.*, v.nom, v.prenom FROM FicheFrais AS f JOIN visiteur AS v ON f.idVisiteur = v.id WHERE f.idEtat IN ("VA", "RB") ORDER BY f.dateModif DESC');
    $historiqueStmt->execute();
    $historique = $historiqueStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur de récupération des fiches de frais : ' . $e->getMessage());
}

// Récupérer les fiches de hors forfait en attente (CR)
try {
    $horsFraisStmt = $bdd->prepare('SELECT hf.*, v.nom, v.prenom FROM LigneFraisHorsForfait hf JOIN visiteur v ON hf.idVisiteur = v.id ORDER BY hf.date DESC ');
    $horsFraisStmt->execute();
    $horsFrais = $horsFraisStmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer l'historique des hors frais validés ou refusés
    $historiqueHorsFraisStmt = $bdd->prepare('SELECT hf.*, v.nom, v.prenom FROM LigneFraisHorsForfait hf JOIN visiteur v ON hf.idVisiteur = v.id ORDER BY hf.date DESC');
    $historiqueHorsFraisStmt->execute();
    $historiqueHorsFrais = $historiqueHorsFraisStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur de récupération des hors frais : ' . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comptable GSB</title>
    <link rel="stylesheet" href="accountant.css">
    <link rel="icon" href="logo GSB.png">
</head>

<body>
    <div class="header-connected">
        <div style="text-align: center; flex: 1;">
            <img src="logo.png" alt="Logo GSB" style="height: 40px;">
        </div>
        <div class="user-info">
            <div class="profile-badge">
                <div class="profile-icon">👤</div>
                <div class="profile-text">
                    <h3 id="userFullName"><?php echo htmlspecialchars($_SESSION['user']['prenom'] ?? 'Utilisateur') . ' ' . htmlspecialchars($_SESSION['user']['nom'] ?? ''); ?></h3>
                    <p id="userProfile"><?php echo htmlspecialchars($_SESSION['user']['role'] ?? 'non défini'); ?></p>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">Se déconnecter</a>
        </div>
    </div>
    <main>
        <h1>Bienvenue, sur votre tableau de bord comptable</h1>
        <?php echo isset($message) ? $message : ''; ?>
    </main>

    <div class="container">
        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('expense')">Frais</button>
            <button class="tab-button" onclick="switchTab('excluding-expenses')">Hors frais</button>
            <button class="tab-button" onclick="switchTab('history')">Historique</button>
        </div>

        <!-- TAB 1: Frais -->
        <div id="expense" class="tab-content active">
            <h2>Renseigner fiche de frais</h2>
            <?php echo htmlspecialchars($message); ?>
            <?php if (empty($fiches)): ?>
                <p style="text-align: center; color: #666;">Aucune fiche de frais en attente de validation.</p>
            <?php else: ?>
                <table class="expenses-table">
                    <thead>
                        <tr>
                            <th>Visiteur</th>
                            <th>Mois</th>
                            <th>Justificatifs</th>
                            <th>Montant</th>
                            <th>Date Modification</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fiches as $fiche): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fiche['prenom'] . ' ' . $fiche['nom']); ?></td>
                                <td><?php echo htmlspecialchars($fiche['mois']); ?></td>
                                <td><?php echo htmlspecialchars($fiche['nbJustificatif']); ?></td>
                                <td><?php echo number_format($fiche['montantValide'], 2, ',', ' '); ?> €</td>
                                <td><?php echo htmlspecialchars($fiche['dateModif']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="idVisiteur" value="<?php echo htmlspecialchars($fiche['idVisiteur']); ?>">
                                        <input type="hidden" name="mois" value="<?php echo htmlspecialchars($fiche['mois']); ?>">
                                        <button type="submit" name="action" value="valider" class="btn-valider">Valider</button>
                                        <button type="submit" name="action" value="refuser" class="btn-refuser">Refuser</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- TAB 2: Hors frais -->
        <div id="excluding-expenses" class="tab-content">
            <h2>Hors frais</h2>

            <?php if (empty($horsFrais)): ?>
                <p style="text-align: center; color: #666;">Aucun hors frais.</p>
            <?php else: ?>
                <table class="expenses-table">
                    <thead>
                        <tr>
                            <th>Visiteur</th>
                            <th>Date</th>
                            <th>Libellé</th>
                            <th>Montant</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horsFrais as $hf): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($hf['prenom'] . ' ' . $hf['nom']); ?></td>
                                <td><?php echo htmlspecialchars($hf['date']); ?></td>
                                <td><?php echo htmlspecialchars($hf['libelle']); ?></td>
                                <td><?php echo number_format($hf['montant'], 2, ',', ' '); ?> €</td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="idLigne" value="<?php echo $hf['id']; ?>">
                                        <button type="submit" name="action_hf" value="valider" class="btn-valider">Valider</button>
                                        <button type="submit" name="action_hf" value="refuser" class="btn-refuser">Refuser</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- TAB 3: Historique -->
        <div id="history" class="tab-content">
            <h2>Historique des fiches</h2>
            <?php if (empty($historique)): ?>
                <p style="text-align: center; color: #666;">Aucune fiche validée ou refusée récemment.</p>
            <?php else: ?>
                <table class="expenses-table">
                    <thead>
                        <tr>
                            <th>Visiteur</th>
                            <th>Mois</th>
                            <th>Justificatifs</th>
                            <th>Montant</th>
                            <th>Date Modification</th>
                            <th>État</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historique as $fiche): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fiche['prenom'] . ' ' . $fiche['nom']); ?></td>
                                <td><?php echo htmlspecialchars($fiche['mois']); ?></td>
                                <td><?php echo htmlspecialchars($fiche['nbJustificatif']); ?></td>
                                <td><?php echo number_format($fiche['montantValide'], 2, ',', ' '); ?> €</td>
                                <td><?php echo htmlspecialchars($fiche['dateModif']); ?></td>
                                <td><?php echo htmlspecialchars($fiche['idEtat'] === 'VA' ? 'Validée' : 'Refusée'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <script src="accountant.js"></script>
</body>
</html>
