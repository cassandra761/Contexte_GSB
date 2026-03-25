<?php
// Script de migration vers le système de rôles
try {
    $pdo = new PDO('mysql:host=localhost;dbname=gsbV2;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Migration vers le système de rôles</h1>";

    // Créer les nouvelles tables
    $sql = file_get_contents('extension_schema_roles.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $executedCount = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
                echo "<p style='color: green;'>✓ Executé: " . htmlspecialchars(substr($statement, 0, 80)) . "...</p>";
                $executedCount++;
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠ Erreur sur cette instruction (peut-être déjà exécutée): " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    echo "<h2>Résumé</h2>";
    echo "<p>$executedCount instructions exécutées avec succès.</p>";

    // Vérifier que les tables ont été créées
    $tables = ['roles', 'permissions', 'role_permissions', 'utilisateurs', 'logs_audit', 'parametres_systeme'];
    echo "<h3>Vérification des tables créées:</h3><ul>";
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "<li style='color: green;'>✓ Table $table créée</li>";
        } else {
            echo "<li style='color: red;'>✗ Table $table manquante</li>";
        }
    }
    echo "</ul>";

    echo "<p style='color: blue; font-weight: bold;'>Migration terminée ! Vous pouvez maintenant utiliser le nouveau système de rôles.</p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Erreur de migration:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>