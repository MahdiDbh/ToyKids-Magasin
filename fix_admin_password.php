<?php
/**
 * Script de mise à jour du mot de passe admin
 * Exécuter ce script UNE SEULE FOIS pour corriger le mot de passe
 */

require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Nouveau hash pour le mot de passe "admin123"
    $nouveau_hash = '$2y$10$tZE9p/nq1z84WoLpH.MtW.4CVx4VzPC/RuHy28LcxqQuuczQodP9m';
    
    // Mettre à jour le mot de passe de l'admin
    $stmt = $pdo->prepare("UPDATE admins SET password = :password WHERE username = 'admin'");
    $stmt->execute([':password' => $nouveau_hash]);
    
    echo "✅ Mot de passe mis à jour avec succès!\n\n";
    echo "Vous pouvez maintenant vous connecter avec:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n\n";
    
    // Vérification
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin && password_verify('admin123', $admin['password'])) {
        echo "✅ Vérification OK - Le mot de passe fonctionne correctement!\n";
    } else {
        echo "❌ Erreur - Le mot de passe ne fonctionne pas encore.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "\n💡 Assurez-vous que:\n";
    echo "1. XAMPP est démarré (Apache + MySQL)\n";
    echo "2. La base de données 'toykids_shop' existe\n";
    echo "3. La table 'admins' est créée (importer admin_table.sql)\n";
}
?>
