<?php
require_once 'config.php';

echo "Test de connexion à la base de données...\n\n";

try {
    $pdo = getDBConnection();
    echo "✅ Connexion OK\n\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM jouets");
    $count = $stmt->fetchColumn();
    echo "Nombre de jouets: $count\n\n";
    
    if ($count > 0) {
        echo "Exemple de jouets:\n";
        $stmt = $pdo->query("SELECT nom_jouet, prix, stock FROM jouets LIMIT 3");
        while ($row = $stmt->fetch()) {
            echo "- " . $row['nom_jouet'] . " : " . $row['prix'] . "€ (stock: " . $row['stock'] . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
