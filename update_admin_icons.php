<?php
// Script pour ajouter Font Awesome et remplacer les emojis dans les fichiers admin

$admin_files = [
    'admin/dashboard.php',
    'admin/commandes.php',
    'admin/messages.php',
    'admin/clients.php',
    'admin/jouets.php',
    'admin/ajouter-commande.php',
    'admin/ajouter-jouet.php',
    'admin/modifier-jouet.php'
];

$emoji_replacements = [
    '🎨' => '<i class="fas fa-palette"></i>',
    '🎈' => '<i class="fas fa-gift"></i>',
    '📊' => '<i class="fas fa-chart-bar"></i>',
    '🛒' => '<i class="fas fa-shopping-cart"></i>',
    '📧' => '<i class="fas fa-envelope"></i>',
    '👥' => '<i class="fas fa-users"></i>',
    '🎮' => '<i class="fas fa-gamepad"></i>',
    '🚪' => '<i class="fas fa-sign-out-alt"></i>',
    '✅' => '<i class="fas fa-check-circle"></i>',
    '❌' => '<i class="fas fa-times-circle"></i>',
    '⏳' => '<i class="fas fa-clock"></i>',
    '📦' => '<i class="fas fa-box"></i>',
    '🎉' => '<i class="fas fa-gift"></i>',
    '➕' => '<i class="fas fa-plus"></i>',
    '🗑️' => '<i class="fas fa-trash"></i>',
    '✏️' => '<i class="fas fa-edit"></i>',
    '👶' => '<i class="fas fa-child"></i>',
];

$font_awesome_link = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';

foreach ($admin_files as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        echo "❌ Fichier non trouvé : $file\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    $original_content = $content;
    
    // Ajouter Font Awesome si pas déjà présent
    if (strpos($content, 'font-awesome') === false && strpos($content, '</head>') !== false) {
        $content = str_replace('</head>', "    $font_awesome_link\n</head>", $content);
    }
    
    // Remplacer les emojis
    foreach ($emoji_replacements as $emoji => $icon) {
        $content = str_replace($emoji, $icon, $content);
    }
    
    if ($content !== $original_content) {
        file_put_contents($filepath, $content);
        echo "✅ Mis à jour : $file\n";
    } else {
        echo "⏭️  Aucun changement : $file\n";
    }
}

echo "\n✨ Mise à jour terminée !\n";
?>
