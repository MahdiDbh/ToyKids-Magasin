<?php
require_once 'auth.php';
$admin = getCurrentAdmin();
$pdo = getDBConnection();

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    $nom_jouet = securiser($_POST['nom_jouet'] ?? '');
    $description = securiser($_POST['description'] ?? '');
    $prix = floatval($_POST['prix'] ?? 0);
    $categorie = securiser($_POST['categorie'] ?? '');
    $age_recommande = securiser($_POST['age_recommande'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    $image_url = securiser($_POST['image_url'] ?? '');
    
    // Validation
    if (empty($nom_jouet)) {
        $errors[] = "Le nom du jouet est requis.";
    }
    if ($prix <= 0) {
        $errors[] = "Le prix doit être supérieur à 0.";
    }
    if (empty($categorie)) {
        $errors[] = "La catégorie est requise.";
    }
    if ($stock < 0) {
        $errors[] = "Le stock ne peut pas être négatif.";
    }
    
    // Si pas d'erreurs, créer le jouet
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO jouets (nom_jouet, description, prix, categorie, age_recommande, stock, image_url) VALUES (:nom_jouet, :description, :prix, :categorie, :age_recommande, :stock, :image_url)");
            $stmt->execute([
                ':nom_jouet' => $nom_jouet,
                ':description' => $description,
                ':prix' => $prix,
                ':categorie' => $categorie,
                ':age_recommande' => $age_recommande,
                ':stock' => $stock,
                ':image_url' => $image_url
            ]);
            
            $success_message = "Jouet ajouté avec succès !";
            
        } catch (Exception $e) {
            $errors[] = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    }
}

// Catégories disponibles
$categories = ['Poupées', 'Voitures', 'Jeux de société', 'Construction', 'Peluches', 'Éducatif', 'Sport', 'Électronique'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Jouet - ToyKids Admin</title>
    <link rel="stylesheet" href="../admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-page">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-palette"></i> ToyKids</h2>
            <p>Admin Panel</p>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">
                <span class="icon"><i class="fas fa-chart-bar"></i></span>
                Dashboard
            </a>
            <a href="commandes.php">
                <span class="icon"><i class="fas fa-shopping-cart"></i></span>
                Commandes
            </a>
            <a href="messages.php">
                <span class="icon"><i class="fas fa-envelope"></i></span>
                Messages
            </a>
            <a href="clients.php">
                <span class="icon"><i class="fas fa-users"></i></span>
                Clients
            </a>
            <a href="jouets.php" class="active">
                <span class="icon"><i class="fas fa-gamepad"></i></span>
                Jouets
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                Déconnexion
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="topbar">
            <h1>Ajouter un Nouveau Jouet</h1>
            <div class="admin-profile">
                <span>👤 <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></span>
            </div>
        </header>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                <a href="jouets.php" style="margin-left: 15px; color: #155724; font-weight: 700;">Retour aux jouets</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <h4><i class="fas fa-times-circle"></i> Erreurs :</h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="jouets-section">
            <form method="POST" style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h2><i class="fas fa-gamepad"></i> Informations du Jouet</h2>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 25px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2c3e50;">Nom du jouet * :</label>
                        <input type="text" name="nom_jouet" required placeholder="Ex: Robot Transformable" 
                               value="<?php echo htmlspecialchars($_POST['nom_jouet'] ?? ''); ?>"
                               style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0; font-size: 1em;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2c3e50;">Catégorie * :</label>
                        <select name="categorie" required style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0; font-size: 1em;">
                            <option value="">-- Choisir une catégorie --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo (isset($_POST['categorie']) && $_POST['categorie'] === $cat) ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2c3e50;">Description :</label>
                    <textarea name="description" rows="4" placeholder="Décrivez le jouet..." 
                              style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0; font-size: 1em;"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 25px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2c3e50;">Prix (€) * :</label>
                        <input type="number" name="prix" required min="0" step="0.01" placeholder="19.99" 
                               value="<?php echo htmlspecialchars($_POST['prix'] ?? ''); ?>"
                               style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0; font-size: 1em;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2c3e50;">Stock * :</label>
                        <input type="number" name="stock" required min="0" placeholder="50" 
                               value="<?php echo htmlspecialchars($_POST['stock'] ?? ''); ?>"
                               style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0; font-size: 1em;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2c3e50;">Âge recommandé :</label>
                        <input type="text" name="age_recommande" placeholder="3+" 
                               value="<?php echo htmlspecialchars($_POST['age_recommande'] ?? ''); ?>"
                               style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0; font-size: 1em;">
                    </div>
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2c3e50;">URL de l'image :</label>
                    <input type="url" name="image_url" placeholder="https://example.com/image.jpg" 
                           value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>"
                           style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0; font-size: 1em;">
                    <p style="font-size: 0.85em; color: #7f8c8d; margin-top: 5px;">Laissez vide pour utiliser l'image par défaut</p>
                </div>

                <div style="text-align: center; padding-top: 20px; border-top: 2px solid #ecf0f1;">
                    <button type="submit" class="btn-primary" style="padding: 15px 40px; font-size: 1.1em;">
                        ✓ Ajouter le Jouet
                    </button>
                    <a href="jouets.php" class="btn-secondary" style="padding: 15px 40px; font-size: 1.1em; margin-left: 15px; text-decoration: none;">
                        ← Retour
                    </a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
