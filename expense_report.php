<?php
require 'config.php';
ensureLoggedIn();
$user = $_SESSION['user'];
$errors = [];
$success = '';

// helper functions
function getReport($pdo, $user_id, $month) {
    $stmt = $pdo->prepare('SELECT * FROM expense_reports WHERE user_id = ? AND report_month = ?');
    $stmt->execute([$user_id, $month]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createOrUpdateReport($pdo, $user_id, $month, $forfaits) {
    $report = getReport($pdo, $user_id, $month);
    if ($report) {
        $stmt = $pdo->prepare('UPDATE expense_reports SET forfait_etape=?, forfait_km=?, forfait_nuitees=?, forfait_repas=? WHERE id=?');
        $stmt->execute([ $forfaits['forfait_etape'], $forfaits['forfait_km'], $forfaits['forfait_nuitees'], $forfaits['forfait_repas'], $report['id'] ]);
        return $report['id'];
    } else {
        $stmt = $pdo->prepare('INSERT INTO expense_reports(user_id, report_month, forfait_etape, forfait_km, forfait_nuitees, forfait_repas) VALUES(?,?,?,?,?,?)');
        $stmt->execute([$user_id, $month, $forfaits['forfait_etape'], $forfaits['forfait_km'], $forfaits['forfait_nuitees'], $forfaits['forfait_repas']]);
        return $pdo->lastInsertId();
    }
}

function addHorsForfait($pdo, $report_id, $date, $label, $amount) {
    $stmt = $pdo->prepare('INSERT INTO expense_lines(report_id, date, label, amount) VALUES(?,?,?,?)');
    $stmt->execute([$report_id, $date, $label, $amount]);
}

function getHorsForfait($pdo, $report_id) {
    $stmt = $pdo->prepare('SELECT * FROM expense_lines WHERE report_id = ? ORDER BY date');
    $stmt->execute([$report_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deleteHorsForfait($pdo, $hf_id) {
    $stmt = $pdo->prepare('DELETE FROM expense_lines WHERE id = ?');
    $stmt->execute([$hf_id]);
}

// selected month (YYYY-MM)
$month = $_POST['ficheMonth'] ?? date('Y-m');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_forfait') {
        // sanitize numbers
        $forfaits = [
            'forfait_etape' => (int) ($_POST['forfait_etape'] ?? 0),
            'forfait_km' => (int) ($_POST['forfait_km'] ?? 0),
            'forfait_nuitees' => (int) ($_POST['forfait_nuitees'] ?? 0),
            'forfait_repas' => (int) ($_POST['forfait_repas'] ?? 0)
        ];
        $report_id = createOrUpdateReport($pdo, $user['id'], $month, $forfaits);
        $success = 'Forfaits enregistrés.';
    }
    if (isset($_POST['action']) && $_POST['action'] === 'add_hf') {
        $date = $_POST['hfDate'] ?? '';
        $label = trim($_POST['hfLabel'] ?? '');
        $amount = $_POST['hfAmount'] ?? '';
        if ($date === '' || $label === '' || $amount === '') {
            $errors[] = 'Tous les champs du hors forfait sont requis.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = 'Date invalide.';
        } elseif (!is_numeric($amount)) {
            $errors[] = 'Montant invalide.';
        } else {
            $report = getReport($pdo, $user['id'], $month);
            if (!$report) {
                // create empty report first
                $report_id = createOrUpdateReport($pdo, $user['id'], $month, ['forfait_etape'=>0,'forfait_km'=>0,'forfait_nuitees'=>0,'forfait_repas'=>0]);
            } else {
                $report_id = $report['id'];
            }
            addHorsForfait($pdo, $report_id, $date, $label, $amount);
            $success = 'Frais hors forfait ajouté.';
        }
    }
    if (isset($_POST['action']) && $_POST['action'] === 'delete_hf' && isset($_POST['hf_id'])) {
        deleteHorsForfait($pdo, (int)$_POST['hf_id']);
        $success = 'Ligne hors forfait supprimée.';
    }
}

$report = getReport($pdo, $user['id'], $month);
$hfList = $report ? getHorsForfait($pdo, $report['id']) : [];

$pageTitle = 'Note de Frais GSB';
$extraCss = '<link rel="stylesheet" href="expense_report.css">';
include 'includes/header.php';
?>

<div class="header-connected">
    <div style="text-align: center; flex: 1;">
        <img src="logo.png" alt="Logo GSB" style="height: 40px;">
    </div>
    <div class="user-info">
        <div class="profile-badge">
            <div class="profile-icon">👤</div>
            <div class="profile-text">
                <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p><?php echo htmlspecialchars($user['profile']); ?></p>
            </div>
        </div>
        <form method="post" style="display:inline;">
            <button type="submit" name="action" value="logout" class="logout-btn">Déconnexion</button>
        </form>
    </div>
</div>

<div class="container">
    <h1 style="color: #8ab98b; margin-bottom: 30px;">Espace de Gestion des Notes de Frais</h1>

    <?php if ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="error-message"><?php echo implode('<br>', $errors); ?></div>
    <?php endif; ?>

    <form method="post" style="margin-bottom:20px; display:flex; gap:12px; align-items:center;">
        <label for="ficheMonth" style="font-weight:600;">Mois :</label>
        <input type="month" id="ficheMonth" name="ficheMonth" value="<?php echo htmlspecialchars($month); ?>">
        <button class="submit-btn">Charger</button>
    </form>

    <section style="margin-bottom:20px;">
        <h3 style="margin:0 0 10px 0; color:#7b90a6;">Éléments forfaitisés</h3>
        <form method="post" class="expense-form">
            <input type="hidden" name="action" value="save_forfait">
            <div class="form-group">
                <label for="forfait_etape">Forfait Étape (quantité)</label>
                <input type="number" id="forfait_etape" name="forfait_etape" min="0" value="<?php echo $report['forfait_etape'] ?? 0; ?>">
            </div>
            <div class="form-group">
                <label for="forfait_km">Frais kilométriques (km)</label>
                <input type="number" id="forfait_km" name="forfait_km" min="0" value="<?php echo $report['forfait_km'] ?? 0; ?>">
            </div>
            <div class="form-group">
                <label for="forfait_nuitees">Nuitées hôtel (nb)</label>
                <input type="number" id="forfait_nuitees" name="forfait_nuitees" min="0" value="<?php echo $report['forfait_nuitees'] ?? 0; ?>">
            </div>
            <div class="form-group">
                <label for="forfait_repas">Repas restaurant (nb)</label>
                <input type="number" id="forfait_repas" name="forfait_repas" min="0" value="<?php echo $report['forfait_repas'] ?? 0; ?>">
            </div>
            <div style="margin-top:12px;">
                <button class="submit-btn">Enregistrer forfaits</button>
            </div>
        </form>
    </section>

    <section>
        <h3 style="margin:0 0 10px 0; color:#7b90a6;">Frais hors forfait</h3>
        <table class="expenses-table" style="margin-bottom:8px;">
            <thead><tr><th>Date</th><th>Libellé</th><th>Montant</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($hfList as $hf): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($hf['date']); ?></td>
                        <td><?php echo htmlspecialchars($hf['label']); ?></td>
                        <td><?php echo number_format($hf['amount'], 2); ?> €</td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete_hf">
                                <input type="hidden" name="hf_id" value="<?php echo $hf['id']; ?>">
                                <button type="submit" class="submit-btn">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="post" class="expense-form">
            <input type="hidden" name="action" value="add_hf">
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
                <input type="number" step="0.01" id="hfAmount" name="hfAmount" required>
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="submit-btn">Ajouter hors forfait</button>
            </div>
        </form>
    </section>
</div>

<?php include 'includes/footer.php'; ?>