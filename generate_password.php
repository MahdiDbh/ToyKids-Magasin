<?php
// Script pour générer le hash correct du mot de passe admin123
echo "Hash pour 'admin123': \n";
echo password_hash('admin123', PASSWORD_DEFAULT);
echo "\n\n";

// Vérification que le hash fonctionne
$hash = password_hash('admin123', PASSWORD_DEFAULT);
if (password_verify('admin123', $hash)) {
    echo "✓ Le hash est correct et fonctionne!\n";
} else {
    echo "✗ Erreur dans le hash\n";
}
?>
