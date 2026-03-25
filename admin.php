<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Optionnel : vérifier le rôle
if ($_SESSION['user']['role'] !== 'administrateur') {
    die('Accès interdit.');
}

// Connexion PDO
try {
    $bdd = new PDO(
        'mysql:host=localhost;dbname=gsbV2;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// Variables pour les messages et les résultats
$message = '';
$messageType = '';

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action === 'add_user') {
            // Ajouter un utilisateur
            $userType = $_POST['user_type'];
            $nom = trim($_POST['nom']);
            $prenom = trim($_POST['prenom']);
            $login = trim($_POST['login']);
            $mdp = $_POST['mdp'];
            $adresse = trim($_POST['adresse'] ?? '');
            $cp = trim($_POST['cp'] ?? '');
            $ville = trim($_POST['ville'] ?? '');
            $dateEmbauche = $_POST['date_embauche'] ?? date('Y-m-d');
            
            // Générer un ID unique pour l'utilisateur
            $id = strtolower(substr($prenom, 0, 1) . substr($nom, 0, 1)) . rand(10, 99);
            
            if ($userType === 'visiteur') {
                $stmt = $bdd->prepare("INSERT INTO visiteur (id, nom, prenom, login, mdp, adresse, cp, ville, dateEmbauche) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            } else {
                $stmt = $bdd->prepare("INSERT INTO comptable (id, nom, prenom, login, mdp, adresse, cp, ville, dateEmbauche) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            }
            
            $stmt->execute([$id, $nom, $prenom, $login, $mdp, $adresse, $cp, $ville, $dateEmbauche]);
            $message = "✓ Utilisateur $nom $prenom ajouté avec succès !";
            $messageType = 'success';
            
        } elseif ($action === 'delete_user') {
            // Supprimer un utilisateur
            $userType = $_POST['user_type'];
            $userId = $_POST['user_id'];
            
            if ($userType === 'visiteur') {
                $bdd->prepare("DELETE FROM visiteur WHERE id = ?")->execute([$userId]);
            } else {
                $bdd->prepare("DELETE FROM comptable WHERE id = ?")->execute([$userId]);
            }
            
            $message = "✓ Utilisateur supprimé avec succès !";
            $messageType = 'success';
            
        } elseif ($action === 'execute_query') {
            // Exécuter une requête SQL
            $sql = trim($_POST['sql_query']);
            if (!empty($sql)) {
                $stmt = $bdd->prepare($sql);
                $stmt->execute();
                $queryResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($queryResult)) {
                    $message = "✓ Requête exécutée. " . $stmt->rowCount() . " ligne(s) affectée(s).";
                }
                $messageType = 'success';
            }
        } elseif ($action === 'reset_password') {
            // Réinitialiser le mot de passe d'un utilisateur
            $resetType = $_POST['reset_type'];
            $userId = $_POST['reset_id'];
            $newPwd = $_POST['reset_pwd'];
            
            $table = ($resetType === 'visiteur') ? 'visiteur' : 'comptable';
            $stmt = $bdd->prepare("UPDATE `$table` SET mdp = ? WHERE id = ?");
            $stmt->execute([$newPwd, $userId]);
            
            if ($stmt->rowCount() > 0) {
                $message = "✓ Mot de passe réinitialisé pour $userId";
                $messageType = 'success';
            } else {
                $message = "⚠ Utilisateur $userId non trouvé.";
                $messageType = 'error';
            }
            
        } elseif ($action === 'export_users') {
            // Exporter les utilisateurs
            $exportType = $_POST['export_type'];
            $users = [];
            
            if ($exportType === 'all' || $exportType === 'visiteur') {
                $stmt = $bdd->prepare("SELECT id, nom, prenom, login, adresse, cp, ville, dateEmbauche FROM visiteur ORDER BY nom");
                $stmt->execute();
                $visiteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($visiteurs as $v) {
                    $v['type'] = 'Visiteur';
                    $users[] = $v;
                }
            }
            
            if ($exportType === 'all' || $exportType === 'comptable') {
                $stmt = $bdd->prepare("SELECT id, nom, prenom, login, adresse, cp, ville, dateEmbauche FROM comptable ORDER BY nom");
                $stmt->execute();
                $comptables = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($comptables as $c) {
                    $c['type'] = 'Comptable';
                    $users[] = $c;
                }
            }
            
            // Créer un fichier texte à télécharger
            $filename = 'utilisateurs_' . date('Ymd_His') . '.txt';
            header('Content-Type: text/plain; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"$filename\"");
            
            echo "EXPORT DES UTILISATEURS\n";
            echo "Généré le : " . date('d/m/Y H:i:s') . "\n";
            echo str_repeat("=", 100) . "\n\n";
            
            foreach ($users as $user) {
                echo "ID: " . $user['id'] . " | Type: " . $user['type'] . "\n";
                echo "  Nom: " . $user['nom'] . "\n";
                echo "  Prénom: " . $user['prenom'] . "\n";
                echo "  Login: " . $user['login'] . "\n";
                echo "  Adresse: " . ($user['adresse'] ?? 'N/A') . "\n";
                echo "  CP: " . ($user['cp'] ?? 'N/A') . "\n";
                echo "  Ville: " . ($user['ville'] ?? 'N/A') . "\n";
                echo "  Date d'embauche: " . ($user['dateEmbauche'] ?? 'N/A') . "\n";
                echo "\n";
            }
            exit();
            
        } elseif ($action === 'maintenance') {
            // Opérations de maintenance
            $operation = $_POST['maintenance_op'];
            
            if ($operation === 'check_db') {
                $stmt = $bdd->prepare("CHECK TABLE visiteur, comptable, administrateur, FicheFrais, Etat");
                $result = $bdd->query("CHECK TABLE visiteur, comptable, administrateur, FicheFrais, Etat")->fetchAll(PDO::FETCH_ASSOC);
                $message = "✓ Vérification complétée : " . count($result) . " table(s) vérifiée(s).";
                $messageType = 'success';
            } elseif ($operation === 'optimize') {
                $bdd->query("OPTIMIZE TABLE visiteur, comptable, administrateur, FicheFrais, Etat");
                $message = "✓ Optimisation des tables complétée.";
                $messageType = 'success';
            }
        }
    } catch (Exception $e) {
        $message = "✗ Erreur : " . $e->getMessage();
        $messageType = 'error';
    }
}

// Vérifier si on demande à voir une table ou sa structure
if (isset($_GET['view_table'])) {
    $tableName = $_GET['view_table'];
    try {
        $stmt = $bdd->prepare("SELECT * FROM `$tableName` LIMIT 100");
        $stmt->execute();
        $tableData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h1>Contenu de la table: " . htmlspecialchars($tableName) . "</h1>";
        if (!empty($tableData)) {
            echo "<table class='expenses-table'><thead><tr>";
            foreach (array_keys($tableData[0]) as $column) {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
            echo "</tr></thead><tbody>";
            foreach ($tableData as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>La table est vide.</p>";
        }
        echo "<br><a href='admin.php'>Retour à l'administration</a>";
        exit();
    } catch (PDOException $e) {
        die('Erreur lors de la récupération des données : ' . $e->getMessage());
    }
} elseif (isset($_GET['show_structure'])) {
    $tableName = $_GET['show_structure'];
    try {
        $stmt = $bdd->prepare("DESCRIBE `$tableName`");
        $stmt->execute();
        $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h1>Structure de la table: " . htmlspecialchars($tableName) . "</h1>";
        echo "<table class='expenses-table'><thead><tr>";
        echo "<th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th>";
        echo "</tr></thead><tbody>";
        foreach ($structure as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "<br><a href='admin.php'>Retour à l'administration</a>";
        exit();
    } catch (PDOException $e) {
        die('Erreur lors de la récupération des données : ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration GSB</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="icon" href="logo GSB.png">
</head>
<body>
    <div class="header-connected">
        <div class="user-info">
            <div class="profile-badge">
                <div class="profile-icon">👤</div>
                <div class="profile-text">
                    <h3><?php echo htmlspecialchars($_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom']); ?></h3>
                    <p>Administrateur</p>
                </div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">Déconnexion</a>
    </div>

    <h1>Administrateur</h1>

    <?php if (!empty($message)): ?>
        <div style="margin: 20px auto; max-width: 1200px; padding: 15px; border-radius: 8px; <?php echo $messageType === 'success' ? 'background: #d4edda; border: 1px solid #c3e6cb; color: #155724;' : 'background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('users')">Gestion Utilisateurs</button>
            <button class="tab-button" onclick="switchTab('search')">Recherche</button>
            <button class="tab-button" onclick="switchTab('stats')">Statistiques</button>
            <button class="tab-button" onclick="switchTab('database')">Base de Données</button>
            <button class="tab-button" onclick="switchTab('tools')">Outils</button>
        </div>

        <!-- TAB 1: Gestion Utilisateurs -->
        <div id="users" class="tab-content active">
            <h2>Gestion des Utilisateurs</h2>

            <!-- Bouton pour ajouter un utilisateur -->
            <div style="margin-bottom: 20px;">
                <button class="submit-btn" onclick="showAddUserForm()">Ajouter un Utilisateur</button>
            </div>

            <!-- Formulaire d'ajout d'utilisateur (caché par défaut) -->
            <div id="addUserForm" style="display: none; margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px;">
                <h3>Ajouter un Nouvel Utilisateur</h3>
                <form method="POST" action="admin.php">
                    <input type="hidden" name="action" value="add_user">
                    <div class="expense-form">
                        <div class="form-group">
                            <label for="user_type">Type d'utilisateur</label>
                            <select id="user_type" name="user_type" required>
                                <option value="visiteur">Visiteur médical</option>
                                <option value="comptable">Comptable</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" required>
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" required>
                        </div>
                        <div class="form-group">
                            <label for="login">Login</label>
                            <input type="text" id="login" name="login" required>
                        </div>
                        <div class="form-group">
                            <label for="mdp">Mot de passe</label>
                            <input type="password" id="mdp" name="mdp" required>
                        </div>
                        <div class="form-group">
                            <label for="adresse">Adresse</label>
                            <input type="text" id="adresse" name="adresse">
                        </div>
                        <div class="form-group">
                            <label for="cp">Code Postal</label>
                            <input type="text" id="cp" name="cp">
                        </div>
                        <div class="form-group">
                            <label for="ville">Ville</label>
                            <input type="text" id="ville" name="ville">
                        </div>
                        <div class="form-group">
                            <label for="date_embauche">Date d'embauche</label>
                            <input type="date" id="date_embauche" name="date_embauche">
                        </div>
                        <div class="form-group full">
                            <button type="submit" class="submit-btn">Ajouter l'Utilisateur</button>
                            <button type="button" class="submit-btn" style="background: #6c757d; margin-left: 10px;" onclick="hideAddUserForm()">Annuler</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Liste des visiteurs -->
            <h3>Visiteurs Médicaux</h3>
            <?php
            $visiteursStmt = $bdd->query('SELECT * FROM visiteur ORDER BY nom, prenom');
            $visiteurs = $visiteursStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php if (!empty($visiteurs)): ?>
                <table class="expenses-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Login</th>
                            <th>Ville</th>
                            <th>Date Embauche</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visiteurs as $visiteur): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($visiteur['id']); ?></td>
                                <td><?php echo htmlspecialchars($visiteur['nom']); ?></td>
                                <td><?php echo htmlspecialchars($visiteur['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($visiteur['login']); ?></td>
                                <td><?php echo htmlspecialchars($visiteur['ville']); ?></td>
                                <td><?php echo htmlspecialchars($visiteur['dateEmbauche']); ?></td>
                                <td>
                                    <button class="btn-edit" onclick="editUser('visiteur', '<?php echo $visiteur['id']; ?>')">Modifier</button>
                                    <button class="btn-delete" onclick="deleteUser('visiteur', '<?php echo $visiteur['id']; ?>')">Supprimer</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucun visiteur trouvé.</p>
            <?php endif; ?>

            <!-- Liste des comptables -->
            <h3>Comptables</h3>
            <?php
            $comptablesStmt = $bdd->query('SELECT * FROM comptable ORDER BY nom, prenom');
            $comptables = $comptablesStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php if (!empty($comptables)): ?>
                <table class="expenses-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Login</th>
                            <th>Ville</th>
                            <th>Date Embauche</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comptables as $comptable): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($comptable['id']); ?></td>
                                <td><?php echo htmlspecialchars($comptable['nom']); ?></td>
                                <td><?php echo htmlspecialchars($comptable['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($comptable['login']); ?></td>
                                <td><?php echo htmlspecialchars($comptable['ville']); ?></td>
                                <td><?php echo htmlspecialchars($comptable['dateEmbauche']); ?></td>
                                <td>
                                    <button class="btn-edit" onclick="editUser('comptable', '<?php echo $comptable['id']; ?>')">Modifier</button>
                                    <button class="btn-delete" onclick="deleteUser('comptable', '<?php echo $comptable['id']; ?>')">Supprimer</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucun comptable trouvé.</p>
            <?php endif; ?>
        </div>

        <!-- TAB 2: Recherche Utilisateurs -->
        <div id="search" class="tab-content">
            <h2>Recherche Utilisateurs</h2>
            
            <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <form method="GET" action="admin.php">
                    <div style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                        <div>
                            <label for="search_type">Type :</label><br>
                            <select id="search_type" name="search_type">
                                <option value="all">Tous</option>
                                <option value="visiteur">Visiteurs</option>
                                <option value="comptable">Comptables</option>
                            </select>
                        </div>
                        <div>
                            <label for="search_query">Recherche :</label><br>
                            <input type="text" id="search_query" name="search_query" placeholder="Nom, prénom ou login..." value="<?php echo htmlspecialchars($_GET['search_query'] ?? ''); ?>" style="padding: 8px 12px; border: 2px solid #8ab98b; border-radius: 6px;">
                        </div>
                        <button type="submit" class="submit-btn" style="padding: 8px 16px; margin-bottom: 0;">Rechercher</button>
                        <a href="admin.php?tab=search" class="submit-btn" style="background: #6c757d; padding: 8px 16px; margin-bottom: 0; text-decoration: none;">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <?php if (isset($_GET['search_query']) && !empty($_GET['search_query'])): ?>
                <?php
                $searchType = $_GET['search_type'] ?? 'all';
                $query = '%' . $_GET['search_query'] . '%';
                $results = [];

                if ($searchType === 'all' || $searchType === 'visiteur') {
                    $stmt = $bdd->prepare("SELECT id, nom, prenom, login, ville, dateEmbauche, 'visiteur' as type FROM visiteur WHERE nom LIKE ? OR prenom LIKE ? OR login LIKE ? ORDER BY nom");
                    $stmt->execute([$query, $query, $query]);
                    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
                }

                if ($searchType === 'all' || $searchType === 'comptable') {
                    $stmt = $bdd->prepare("SELECT id, nom, prenom, login, ville, dateEmbauche, 'comptable' as type FROM comptable WHERE nom LIKE ? OR prenom LIKE ? OR login LIKE ? ORDER BY nom");
                    $stmt->execute([$query, $query, $query]);
                    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
                }
                ?>

                <h3>Résultats de recherche (<?php echo count($results); ?> trouvé<?php echo count($results) > 1 ? 's' : ''; ?>)</h3>
                <?php if (!empty($results)): ?>
                    <table class="expenses-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Login</th>
                                <th>Ville</th>
                                <th>Date Embauche</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $user): ?>
                                <tr>
                                    <td><span class="status-badge" style="background: <?php echo $user['type'] === 'visiteur' ? '#d1ecf1' : '#d4edda'; ?>; color: <?php echo $user['type'] === 'visiteur' ? '#0c5460' : '#155724'; ?>"><?php echo ucfirst($user['type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['login']); ?></td>
                                    <td><?php echo htmlspecialchars($user['ville']); ?></td>
                                    <td><?php echo htmlspecialchars($user['dateEmbauche']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucun utilisateur trouvé avec ce critère de recherche.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- TAB 3: Statistiques -->
        <div id="stats" class="tab-content">
            <h2>Statistiques du Système</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <?php
                $totalVisiteurs = $bdd->query("SELECT COUNT(*) as count FROM visiteur")->fetch()['count'];
                $totalComptables = $bdd->query("SELECT COUNT(*) as count FROM comptable")->fetch()['count'];
                $totalFrais = $bdd->query("SELECT COUNT(*) as count FROM LigneFraisHorsForfait")->fetch()['count'];
                $totalMontant = $bdd->query("SELECT SUM(montant) as total FROM LigneFraisHorsForfait")->fetch()['total'];
                ?>
                
                <div style="background: linear-gradient(135deg, #8ab98b 0%, #7b90a6 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9;">👥 Visiteurs Médicaux</h3>
                    <p style="margin: 0; font-size: 32px; font-weight: bold;"><?php echo $totalVisiteurs; ?></p>
                </div>

                <div style="background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9;">📋 Comptables</h3>
                    <p style="margin: 0; font-size: 32px; font-weight: bold;"><?php echo $totalComptables; ?></p>
                </div>

                <div style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9;">💰 Frais Saisis</h3>
                    <p style="margin: 0; font-size: 32px; font-weight: bold;"><?php echo $totalFrais; ?></p>
                </div>

                <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9;">💵 Montant Total</h3>
                    <p style="margin: 0; font-size: 32px; font-weight: bold;"><?php echo number_format($totalMontant, 2); ?>€</p>
                </div>
            </div>

            <h3>Détails par État de Fiche</h3>
            <?php
            $etatStats = $bdd->query("
                SELECT e.libelle, COUNT(f.idVisiteur) as count
                FROM FicheFrais f
                JOIN Etat e ON f.idEtat = e.id
                GROUP BY f.idEtat, e.libelle
                ORDER BY e.libelle
            ")->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <table class="expenses-table">
                <thead>
                    <tr>
                        <th>État</th>
                        <th>Nombre de Fiches</th>
                        <th>Proportion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalEtat = array_sum(array_column($etatStats, 'count'));
                    foreach ($etatStats as $stat):
                        $percentage = $totalEtat > 0 ? ($stat['count'] / $totalEtat) * 100 : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['libelle']); ?></td>
                            <td><?php echo $stat['count']; ?></td>
                            <td>
                                <div style="background: #e9ecef; border-radius: 4px; height: 24px; overflow: hidden; position: relative;">
                                    <div style="background: #8ab98b; height: 100%; width: <?php echo $percentage; ?>%; transition: width 0.3s;"></div>
                                    <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: 600; color: #333;"><?php echo number_format($percentage, 1); ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- TAB 4: Gestion Base de Données -->
        <div id="database" class="tab-content">
            <h2>Gestion de la Base de Données</h2>

            <!-- Informations sur la base -->
            <div style="margin-bottom: 30px;">
                <h3>Informations sur la Base de Données</h3>
                <p><strong>Base de données :</strong> gsbV2</p>
                <p><strong>Serveur :</strong> localhost</p>
                <p><strong>Utilisateur :</strong> root</p>
            </div>

            <!-- Liste des tables -->
            <h3>Tables de la Base de Données</h3>
            <?php
            $tablesStmt = $bdd->query('SHOW TABLES');
            $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
            ?>
            <table class="expenses-table">
                <thead>
                    <tr>
                        <th>Nom de la Table</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $table): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($table); ?></td>
                            <td>
                                <button class="btn-view" onclick="viewTable('<?php echo $table; ?>')">Voir le contenu</button>
                                <button class="btn-structure" onclick="showTableStructure('<?php echo $table; ?>')">Structure</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Exécution de requêtes SQL -->
            <h3>Exécuter une Requête SQL</h3>
            <div style="margin-bottom: 20px;">
                <form method="POST" action="admin.php">
                    <input type="hidden" name="action" value="execute_query">
                    <div class="form-group full">
                        <label for="sql_query">Requête SQL</label>
                        <textarea id="sql_query" name="sql_query" rows="5" placeholder="Entrez votre requête SQL ici..." required></textarea>
                    </div>
                    <div class="form-group full">
                        <button type="submit" class="submit-btn" style="background: #dc3545;">Exécuter la Requête</button>
                        <small style="color: #666; display: block; margin-top: 10px;">
                            ⚠️ Attention : Les requêtes de modification (INSERT, UPDATE, DELETE) seront exécutées. Soyez prudent !
                        </small>
                    </div>
                </form>
            </div>

            <!-- Résultats de la requête -->
            <?php if (isset($queryResult)): ?>
                <h3>Résultats de la Requête</h3>
                <?php if (is_array($queryResult)): ?>
                    <table class="expenses-table">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($queryResult[0] ?? []) as $column): ?>
                                    <th><?php echo htmlspecialchars($column); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queryResult as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                        <td><?php echo htmlspecialchars($value); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php echo htmlspecialchars($queryResult); ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- TAB 5: Outils -->
        <div id="tools" class="tab-content">
            <h2>Outils d'Administration</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                
                <!-- Outil 1 : Réinitialiser les mots de passe -->
                <div style="background: white; border: 2px solid #8ab98b; padding: 20px; border-radius: 8px;">
                    <h3>Réinitialiser Mot de Passe</h3>
                    <p style="color: #666; font-size: 14px;">Réinitialiser le mot de passe d'un utilisateur à une valeur par défaut.</p>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="reset_password">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="reset_type">Type :</label>
                            <select id="reset_type" name="reset_type" required style="padding: 8px; border: 2px solid #8ab98b; border-radius: 6px; margin-bottom: 10px;">
                                <option value="">-- Sélectionner --</option>
                                <option value="visiteur">Visiteur</option>
                                <option value="comptable">Comptable</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="reset_id">ID utilisateur :</label>
                            <input type="text" id="reset_id" name="reset_id" placeholder="ex: a131" required style="padding: 8px; border: 2px solid #8ab98b; border-radius: 6px; margin-bottom: 10px;">
                        </div>
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="reset_pwd">Nouveau MDP :</label>
                            <input type="text" id="reset_pwd" name="reset_pwd" placeholder="Nouveau mot de passe" required style="padding: 8px; border: 2px solid #8ab98b; border-radius: 6px; margin-bottom: 10px;">
                        </div>
                        <button type="submit" class="submit-btn" style="width: 100%; padding: 10px;">Réinitialiser</button>
                    </form>
                </div>

                <!-- Outil 2 : Exporter Utilisateurs -->
                <div style="background: white; border: 2px solid #17a2b8; padding: 20px; border-radius: 8px;">
                    <h3>Exporter Utilisateurs</h3>
                    <p style="color: #666; font-size: 14px;">Télécharger une liste complète des utilisateurs au format texte.</p>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="export_users">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="export_type">Type :</label>
                            <select id="export_type" name="export_type" required style="padding: 8px; border: 2px solid #8ab98b; border-radius: 6px; margin-bottom: 10px;">
                                <option value="all">Tous</option>
                                <option value="visiteur">Visiteurs seulement</option>
                                <option value="comptable">Comptables seulement</option>
                            </select>
                        </div>
                        <button type="submit" class="submit-btn" style="width: 100%; padding: 10px; background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);">Exporter</button>
                    </form>
                </div>

                <!-- Outil 3 : Nettoyage -->
                <div style="background: white; border: 2px solid #dc3545; padding: 20px; border-radius: 8px;">
                    <h3>Maintenance</h3>
                    <p style="color: #666; font-size: 14px;">Opérations de maintenance et de nettoyage de la base de données.</p>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="maintenance">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="maintenance_op">Opération :</label>
                            <select id="maintenance_op" name="maintenance_op" required style="padding: 8px; border: 2px solid #8ab98b; border-radius: 6px; margin-bottom: 10px;">
                                <option value="">-- Sélectionner --</option>
                                <option value="check_db">Vérifier la base</option>
                                <option value="optimize">Optimiser les tables</option>
                            </select>
                        </div>
                        <button type="submit" class="submit-btn" onclick="return confirm('Cette opération peut prendre du temps. Continuer ?');" style="width: 100%; padding: 10px; background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">Exécuter</button>
                    </form>
                </div>
            </div>

            <h3>À Propos du Système</h3>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <p><strong>Système :</strong> Gestion des Frais GSB</p>
                <p><strong>Version :</strong> 2.0</p>
                <p><strong>Base de données :</strong> gsbV2</p>
                <p><strong>Administrateur :</strong> <?php echo htmlspecialchars($_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom']); ?></p>
                <p><strong>Connexion depuis :</strong> <?php echo $_SERVER['REMOTE_ADDR']; ?></p>
                <p><strong>Date/Heure :</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>
        </div>
    </div>

    <script src="admin.js"></script>
</body>
</html>