<?php
<<<<<<< HEAD
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
=======
require 'config.php';
ensureLoggedIn();
$user = $_SESSION['user'];

// helper functions
function getReport($pdo, $user_id, $month) {
    $stmt = $pdo->prepare('SELECT * FROM expense_reports WHERE user_id = ? AND report_month = ?');
    $stmt->execute([$user_id, $month]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
>>>>>>> 75602c25ef7ae693f61b62c7a4206c3ffee4b408
}

// Optionnel : vérifier le rôle
if ($_SESSION['user']['role'] !== 'visiteur') {
    die('Accès interdit.');
}

// Connexion PDO
try {
    $bdd = new PDO(
        'mysql:host=localhost;dbname=gsbV2;charset=utf8', 'lvillachane', 'jux7g');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} 
catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// Récupérer les informations de l'utilisateur
$visitorLogin = $_SESSION['user']['login'];
try {
    $getVisitor = $bdd->prepare('SELECT id, nom, prenom FROM visiteur WHERE login = :login');
    $getVisitor->execute([':login' => $visitorLogin]);
    $visitorRow = $getVisitor->fetch(PDO::FETCH_ASSOC);
    if (!$visitorRow) {
        die('Utilisateur visiteur introuvable.');
    }
    $visitorId = $visitorRow['id'];
} catch (PDOException $e) {
    die('Erreur lors de la récupération du visiteur : ' . $e->getMessage());
}

$message = '';
$activeTab = 'new-expense';
if (isset($_GET['tab']) && in_array($_GET['tab'], ['new-expense', 'my-expenses'], true)) {
    $activeTab = $_GET['tab'];
}

function ensureFicheExiste(PDO $bdd, $idVisiteur, $mois) {
    $check = $bdd->prepare('SELECT * FROM FicheFrais WHERE idVisiteur = :idVisiteur AND mois = :mois');
    $check->execute([':idVisiteur' => $idVisiteur, ':mois' => $mois]);
    $fiche = $check->fetch(PDO::FETCH_ASSOC);
    if ($fiche) {
        return $fiche;
    }
    $insert = $bdd->prepare('INSERT INTO FicheFrais (idVisiteur, mois, nbJustificatif, montantValide, dateModif, idEtat) VALUES (:idVisiteur, :mois, 0, 0.00, :dateModif, "CR")');
    $insert->execute([':idVisiteur' => $idVisiteur, ':mois' => $mois, ':dateModif' => date('Y-m-d')]);
    return [
        'idVisiteur' => $idVisiteur,
        'mois' => $mois,
        'nbJustificatif' => 0,
        'montantValide' => 0.00,
        'dateModif' => date('Y-m-d'),
        'idEtat' => 'CR'
    ];
}

function calculerMontant(PDO $bdd, $idVisiteur, $mois) {

    // Total forfait
    $sqlForfait = "
        SELECT SUM(l.quantite * f.montant) as total
        FROM LigneFraisForfait l
        JOIN FraisForfait f ON l.idFraisForfait = f.id
        WHERE l.idVisiteur = :idVisiteur AND l.mois = :mois
    ";
    $stmt = $bdd->prepare($sqlForfait);
    $stmt->execute([':idVisiteur' => $idVisiteur, ':mois' => $mois]);
    $totalForfait = $stmt->fetchColumn() ?? 0;

    // Total hors forfait
    $sqlHF = "
        SELECT SUM(montant) 
        FROM LigneFraisHorsForfait
        WHERE idVisiteur = :idVisiteur AND mois = :mois
    ";
    $stmt = $bdd->prepare($sqlHF);
    $stmt->execute([':idVisiteur' => $idVisiteur, ':mois' => $mois]);
    $totalHF = $stmt->fetchColumn() ?? 0;

    return $totalForfait + $totalHF;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activeTab = 'my-expenses';
    $action = $_POST['action'] ?? '';
    $mois = $_POST['mois'] ?? '';
    // on attend le format 2024-04 (HTML month)
    if ($mois !== '') {
        $mois = str_replace('-', '', $mois);
    }

    if ($action === 'save_forfaits') {
        $fiche = ensureFicheExiste($bdd, $visitorId, $mois);

        $forfaits = [
            'ETP' => intval($_POST['forfait_etape'] ?? 0),
            'KM' => intval($_POST['forfait_km'] ?? 0),
            'NUI' => intval($_POST['forfait_nuitees'] ?? 0),
            'REP' => intval($_POST['forfait_repas'] ?? 0)
        ];

        foreach ($forfaits as $idFrais => $qte) {
            $stmt = $bdd->prepare('INSERT INTO LigneFraisForfait (idVisiteur, mois, idFraisForfait, quantite) VALUES (:idVisiteur, :mois, :idFraisForfait, :quantite) ON DUPLICATE KEY UPDATE quantite = :quantite');
            $stmt->execute([':idVisiteur' => $visitorId, ':mois' => $mois, ':idFraisForfait' => $idFrais, ':quantite' => $qte]);
        }

        $updateFiche = $bdd->prepare('UPDATE FicheFrais SET dateModif = :dateModif, nbJustificatif = nbJustificatif + 1 WHERE idVisiteur = :idVisiteur AND mois = :mois');
        // $updateFiche->execute([':nbJustif' => intval($_POST['nbJustificatif'] ?? 0), ':dateModif' => date('Y-m-d'), ':idVisiteur' => $visitorId, ':mois' => $mois]);

        $message = 'Frais forfaitisés enregistrés en base.';

        $montantTotal = calculerMontant($bdd, $visitorId, $mois);

        $updateMontant = $bdd->prepare('
            UPDATE FicheFrais 
            SET montantValide = :montant
            WHERE idVisiteur = :idVisiteur AND mois = :mois
        ');

        $updateMontant->execute([
            ':montant' => $montantTotal,
            ':idVisiteur' => $visitorId,
            ':mois' => $mois
        ]);
    } elseif ($action === 'add_hors_forfait') {
        $fiche = ensureFicheExiste($bdd, $visitorId, $mois);

        $date = $_POST['hfDate'] ?? '';
        $libelle = trim($_POST['hfLabel'] ?? '');
        $montant = floatval($_POST['hfAmount'] ?? 0);

        if ($date && $libelle && $montant > 0) {
            $stmt = $bdd->prepare('INSERT INTO LigneFraisHorsForfait (idVisiteur, mois, libelle, date, montant) VALUES (:idVisiteur, :mois, :libelle, :date, :montant)');
            $stmt->execute([':idVisiteur' => $visitorId, ':mois' => $mois, ':libelle' => $libelle, ':date' => $date, ':montant' => $montant]);

            $updateFiche = $bdd->prepare('UPDATE FicheFrais SET dateModif = :dateModif, nbJustificatif = nbJustificatif + 1 WHERE idVisiteur = :idVisiteur AND mois = :mois');
            $updateFiche->execute([':dateModif' => date('Y-m-d'), ':idVisiteur' => $visitorId, ':mois' => $mois]);

            $message = 'Frais hors forfait enregistré en base.';
        } else {
            $message = 'Données hors forfait invalides.';
        }

        $montantTotal = calculerMontant($bdd, $visitorId, $mois);

        $updateMontant = $bdd->prepare('
            UPDATE FicheFrais 
            SET montantValide = :montant
            WHERE idVisiteur = :idVisiteur AND mois = :mois
        ');

        $updateMontant->execute([
            ':montant' => $montantTotal,
            ':idVisiteur' => $visitorId,
            ':mois' => $mois
        ]);
    }
}

try {
    $mesFichesStmt = $bdd->prepare('SELECT * FROM FicheFrais WHERE idVisiteur = :idVisiteur ORDER BY mois DESC');
    $mesFichesStmt->execute([':idVisiteur' => $visitorId]);
    $mesFiches = $mesFichesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur lors de la récupération des notes de frais : ' . $e->getMessage());
}

$horsForfaitsStmt = $bdd->prepare('
    SELECT date, libelle, montant 
    FROM LigneFraisHorsForfait 
    WHERE idVisiteur = :idVisiteur 
    ORDER BY date DESC
');
$horsForfaitsStmt->execute([':idVisiteur' => $visitorId]);
$horsForfaits = $horsForfaitsStmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note de Frais GSB</title>
    <link rel="stylesheet" href="expense_report.css">
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

    <div class="container">
        <h1 style="color: #8ab98b; margin-bottom: 30px;">Espace de Gestion des Notes de Frais</h1>
        <?php if (!empty($message)): ?>
            <div style="padding:10px; background:#e8ffe8; border:1px solid #8ab98b; color:#185b1f; margin-bottom:12px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="<?php echo $activeTab === 'new-expense' ? 'tab-button active' : 'tab-button'; ?>" onclick="switchTab('new-expense')">Nouvelle note</button>
            <button class="<?php echo $activeTab === 'my-expenses' ? 'tab-button active' : 'tab-button'; ?>" onclick="switchTab('my-expenses')">Mes notes de frais</button>
        </div>

        <!-- TAB 1: Nouvelle note -->
        <div id="new-expense" class="tab-content <?php echo $activeTab === 'new-expense' ? 'active' : ''; ?>">
            <h2>Renseigner fiche de frais</h2>

            <div style="margin-bottom:16px; display:flex; gap:12px; align-items:center;">
                <label for="ficheMonth" style="font-weight:600;">Mois :</label>
                <input type="month" id="ficheMonth" name="mois">
            </div>

            <form id="saveForfaitsForm" method="POST" style="display:none;">
                <input type="hidden" name="action" value="save_forfaits">
                <input type="hidden" name="mois" id="saveForfaitsMois">
                <input type="hidden" name="forfait_etape" id="hidden_forfait_etape">
                <input type="hidden" name="forfait_km" id="hidden_forfait_km">
                <input type="hidden" name="forfait_nuitees" id="hidden_forfait_nuitees">
                <input type="hidden" name="forfait_repas" id="hidden_forfait_repas">
                <input type="hidden" name="nbJustificatif" id="hidden_nbJustificatif" value="0">
            </form>

            <section style="margin-bottom:20px;">
                <h3 style="margin:0 0 10px 0; color:#7b90a6;">Éléments forfaitisés</h3>
                <div class="expense-form">
                    <div class="form-group">
                        <label for="forfait_etape">Forfait Étape (quantité)</label>
                        <input type="number" id="forfait_etape" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="forfait_km">Frais kilométriques (km)</label>
                        <input type="number" id="forfait_km" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="forfait_nuitees">Nuitées hôtel (nb)</label>
                        <input type="number" id="forfait_nuitees" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="forfait_repas">Repas restaurant (nb)</label>
                        <input type="number" id="forfait_repas" min="0" value="0">
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <button class="submit-btn" onclick="saveForfaits()">Enregistrer forfaits</button>
                </div>
            </section>

            <section>
                <h3 style="margin:0 0 10px 0; color:#7b90a6;">Frais hors forfait</h3>

                <form id="hfForm" method="POST" onsubmit="return addHorsForfait(event)">
                    <input type="hidden" name="action" value="add_hors_forfait">
                    <input type="hidden" name="mois" id="hfMois">
                    <div class="expense-form">
                        <div class="form-group">
                            <label for="hfDate">Date d'engagement</label>
                            <input type="date" id="hfDate" name="hfDate" required>
                        </div>
                        <div class="form-group">
                            <label for="hfLabel">Libellé</label>
                            <input type="text" id="hfLabel" name="hfLabel" required>
                        </div>
                        <div class="form-group">
                            <label for="hfAmount">Montant (€)</label>
                            <input type="number" id="hfAmount" name="hfAmount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="submit-btn">Ajouter hors forfait</button>
                        </div>
                    </div>
                </form>
            </section>
        </div>

        <!-- TAB 2: Mes notes de frais -->
        <div id="my-expenses" class="tab-content <?php echo $activeTab === 'my-expenses' ? 'active' : ''; ?>">
            <h2>Mes notes de frais</h2>
            <?php if (!empty($mesFiches)): ?>
                <table class="expenses-table">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Justificatifs</th>
                            <th>Montant validé</th>
                            <th>Date modif</th>
                            <th>État</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mesFiches as $fiche): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fiche['mois']); ?></td>
                                <td><?php echo htmlspecialchars($fiche['nbJustificatif']); ?></td>
                                <td><?php echo number_format($fiche['montantValide'], 2, ',', ' '); ?> €</td>
                                <td><?php echo htmlspecialchars($fiche['dateModif']); ?></td>
                                <td><?php
                                    switch ($fiche['idEtat']) {
                                        case 'CR': echo 'En attente'; break;
                                        case 'VA': echo 'Validée'; break;
                                        case 'RB': echo 'Refusée / Remboursée'; break;
                                        case 'CL': echo 'Clôturée'; break;
                                        default: echo htmlspecialchars($fiche['idEtat']);
                                    }
                                ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <h2>Mes notes de hors frais</h2>
                <table class="expenses-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Libellé</th>
                            <th>Montant</th>
                            <th>ActÉtation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($horsForfaits)): ?>
                            <?php foreach ($horsForfaits as $hf): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($hf['date']); ?></td>
                                    <td><?php echo htmlspecialchars($hf['libelle']); ?></td>
                                    <td><?php echo number_format($hf['montant'], 2, ',', ' '); ?> €</td>
                                    <td><?php
                                    switch ($fiche['idEtat']) {
                                        case 'CR': echo 'En attente'; break;
                                        case 'VA': echo 'Validée'; break;
                                        case 'RB': echo 'Refusée / Remboursée'; break;
                                        case 'CL': echo 'Clôturée'; break;
                                        default: echo htmlspecialchars($fiche['idEtat']);
                                    }
                                    ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">Aucun frais hors forfait</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#666;">Aucune note de frais enregistrée pour le moment.</p>
            <?php endif; ?>
        </div>


    </div>
    <script src="expense_report.js"></script>
</body>

</html>