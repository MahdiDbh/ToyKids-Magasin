<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToyKids - Galerie de Jouets</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- En-tête et navigation -->
    <header>
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <h1><i class="fas fa-star"></i> ToyKids <i class="fas fa-gift"></i></h1>
                    <p class="tagline">Le paradis des jouets !</p>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php"><i class="fas fa-home"></i> Accueil</a></li>
                    <li><a href="jouets.php" class="active"><i class="fas fa-gamepad"></i> Nos Jouets</a></li>
                    <li><a href="panier.php"><i class="fas fa-shopping-cart"></i> Panier 
                        <?php 
                        if (isset($_COOKIE['panier'])) {
                            $panier = json_decode($_COOKIE['panier'], true) ?: [];
                            $nb = array_sum($panier);
                            if ($nb > 0) echo "($nb)";
                        }
                        ?>
                    </a></li>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Section Hero -->
    <section class="hero" style="padding: 50px 20px;">
        <div class="hero-content">
            <h2><i class="fas fa-gift"></i> Notre Collection de Jouets <i class="fas fa-gift"></i></h2>
            <p class="hero-subtitle">Découvrez tous nos jouets merveilleux !</p>
        </div>
    </section>

    <!-- Section Galerie de Jouets -->
    <section class="products-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-star"></i> Tous nos Jouets <i class="fas fa-star"></i></h2>
            
            <!-- Filtres -->
            <div class="filters" style="text-align: center; margin-bottom: 40px;">
                <form method="GET" action="jouets.php" style="display: inline-flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
                    <select name="categorie" class="filter-select" onchange="this.form.submit()" style="padding: 12px 20px; border-radius: 25px; border: 3px solid #FF6B9D; font-size: 1em; font-family: 'Baloo 2', sans-serif; background: white; cursor: pointer;">
                        <option value="">Toutes les catégories</option>
                        <option value="Électronique" <?php echo (isset($_GET['categorie']) && $_GET['categorie'] == 'Électronique') ? 'selected' : ''; ?>><i class="fas fa-robot"></i> Électronique</option>
                        <option value="Poupées" <?php echo (isset($_GET['categorie']) && $_GET['categorie'] == 'Poupées') ? 'selected' : ''; ?>><i class="fas fa-female"></i> Poupées</option>
                        <option value="Éducatif" <?php echo (isset($_GET['categorie']) && $_GET['categorie'] == 'Éducatif') ? 'selected' : ''; ?>><i class="fas fa-puzzle-piece"></i> Éducatif</option>
                        <option value="Véhicules" <?php echo (isset($_GET['categorie']) && $_GET['categorie'] == 'Véhicules') ? 'selected' : ''; ?>><i class="fas fa-car"></i> Véhicules</option>
                        <option value="Construction" <?php echo (isset($_GET['categorie']) && $_GET['categorie'] == 'Construction') ? 'selected' : ''; ?>><i class="fas fa-cubes"></i> Construction</option>
                        <option value="Peluches" <?php echo (isset($_GET['categorie']) && $_GET['categorie'] == 'Peluches') ? 'selected' : ''; ?>><i class="fas fa-paw"></i> Peluches</option>
                        <option value="Sport" <?php echo (isset($_GET['categorie']) && $_GET['categorie'] == 'Sport') ? 'selected' : ''; ?>><i class="fas fa-football-ball"></i> Sport</option>
                        <option value="Créatif" <?php echo (isset($_GET['categorie']) && $_GET['categorie'] == 'Créatif') ? 'selected' : ''; ?>>🎨 Créatif</option>
                    </select>
                    
                    <select name="tri" class="filter-select" onchange="this.form.submit()" style="padding: 12px 20px; border-radius: 25px; border: 3px solid #667eea; font-size: 1em; font-family: 'Baloo 2', sans-serif; background: white; cursor: pointer;">
                        <option value="">Trier par</option>
                        <option value="prix_asc" <?php echo (isset($_GET['tri']) && $_GET['tri'] == 'prix_asc') ? 'selected' : ''; ?>>Prix croissant</option>
                        <option value="prix_desc" <?php echo (isset($_GET['tri']) && $_GET['tri'] == 'prix_desc') ? 'selected' : ''; ?>>Prix décroissant</option>
                        <option value="nom" <?php echo (isset($_GET['tri']) && $_GET['tri'] == 'nom') ? 'selected' : ''; ?>>Nom A-Z</option>
                    </select>
                </form>
            </div>

            <!-- Grille de Produits -->
            <div class="products-grid">
                <?php
                require_once 'config.php';
                
                try {
                    $pdo = getDBConnection();
                    
                    // Construction de la requête avec filtres
                    $sql = "SELECT * FROM jouets WHERE 1=1";
                    $params = [];
                    
                    // Filtre par catégorie
                    if (isset($_GET['categorie']) && !empty($_GET['categorie'])) {
                        $sql .= " AND categorie = :categorie";
                        $params[':categorie'] = $_GET['categorie'];
                    }
                    
                    // Tri
                    if (isset($_GET['tri'])) {
                        switch ($_GET['tri']) {
                            case 'prix_asc':
                                $sql .= " ORDER BY prix ASC";
                                break;
                            case 'prix_desc':
                                $sql .= " ORDER BY prix DESC";
                                break;
                            case 'nom':
                                $sql .= " ORDER BY nom_jouet ASC";
                                break;
                            default:
                                $sql .= " ORDER BY id_jouet DESC";
                        }
                    } else {
                        $sql .= " ORDER BY id_jouet DESC";
                    }
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $jouets = $stmt->fetchAll();
                    
                    if (count($jouets) > 0) {
                        foreach ($jouets as $jouet) {
                            // Emojis selon la catégorie
                            $icon_map = [
                                'Électronique' => '<i class="fas fa-robot"></i>',
                                'Poupées' => '<i class="fas fa-female"></i>',
                                'Éducatif' => '<i class="fas fa-puzzle-piece"></i>',
                                'Véhicules' => '<i class="fas fa-car"></i>',
                                'Construction' => '<i class="fas fa-cubes"></i>',
                                'Peluches' => '<i class="fas fa-paw"></i>',
                                'Sport' => '<i class="fas fa-football-ball"></i>',
                                'Créatif' => '<i class="fas fa-palette"></i>',
                                'Plein air' => '<i class="fas fa-running"></i>',
                                'Jeux de société' => '<i class="fas fa-dice"></i>'
                            ];
                            
                            $icon = isset($icon_map[$jouet['categorie']]) ? $icon_map[$jouet['categorie']] : '<i class="fas fa-gift"></i>';
                            
                            echo '<div class="product-card fade-in">';
                            echo '<div class="product-image">';
                            echo '<span class="product-emoji">' . $icon . '</span>';
                            echo '</div>';
                            echo '<h3>' . htmlspecialchars($jouet['nom_jouet']) . '</h3>';
                            echo '<p class="product-description">' . htmlspecialchars(substr($jouet['description'], 0, 100)) . '...</p>';
                            echo '<p class="product-age"><i class="fas fa-child"></i> ' . htmlspecialchars($jouet['age_recommande']) . '</p>';
                            echo '<span class="product-category">' . htmlspecialchars($jouet['categorie']) . '</span>';
                            echo '<p class="product-price">' . number_format($jouet['prix'], 2, ',', ' ') . ' €</p>';
                            
                            if ($jouet['stock'] > 0) {
                                echo '<p style="color: #28a745; font-weight: 600; margin: 10px 0;"><i class="fas fa-check"></i> En stock (' . $jouet['stock'] . ' disponible' . ($jouet['stock'] > 1 ? 's' : '') . ')</p>';
                                echo '<form method="POST" action="panier.php" style="display: inline-block; margin-top: 10px;">';
                                echo '<input type="hidden" name="action" value="ajouter">';
                                echo '<input type="hidden" name="id_jouet" value="' . $jouet['id_jouet'] . '">';
                                echo '<input type="hidden" name="quantite" value="1">';
                                echo '<button type="submit" class="btn btn-primary btn-small"><i class="fas fa-cart-plus"></i> Ajouter au panier</button>';
                                echo '</form>';
                            } else {
                                echo '<p style="color: #dc3545; font-weight: 600; margin: 10px 0;"><i class="fas fa-times"></i> Rupture de stock</p>';
                            }
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="info-message" style="grid-column: 1/-1;">';
                        echo '<p><i class="fas fa-sad-tear"></i> Aucun jouet trouvé dans cette catégorie.</p>';
                        echo '<a href="jouets.php" class="btn btn-primary" style="margin-top: 15px;">Voir tous les jouets</a>';
                        echo '</div>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="error-message" style="grid-column: 1/-1;">';
                    echo '<p>Impossible de charger les jouets pour le moment. Veuillez réessayer plus tard.</p>';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- Statistiques -->
            <div style="text-align: center; margin-top: 50px; padding: 30px; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); border-radius: 20px;">
                <?php
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(stock) as total_stock FROM jouets");
                    $stats = $stmt->fetch();
                    echo '<h3 style="font-size: 1.8em; color: #764ba2; margin-bottom: 15px;"><i class="fas fa-chart-bar"></i> Nos Statistiques</h3>';
                    echo '<p style="font-size: 1.3em; color: #333;"><strong>' . $stats['total'] . '</strong> jouets différents disponibles</p>';
                    echo '<p style="font-size: 1.3em; color: #333;"><strong>' . $stats['total_stock'] . '</strong> jouets en stock total</p>';
                } catch (Exception $e) {
                    // Ignorer les erreurs de statistiques
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Section Appel à l'action -->
    <section class="cta-section">
        <div class="container">
            <h2><i class="fas fa-gift"></i> Trouvé le jouet parfait ? <i class="fas fa-gift"></i></h2>
            <p>Commandez maintenant et faites plaisir à votre enfant !</p>
            <a href="commande.php" class="btn btn-large">Commander maintenant <i class="fas fa-shopping-cart"></i></a>
        </div>
    </section>

    <!-- Pied de page -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-star"></i> ToyKids</h3>
                    <p>Le meilleur magasin de jouets en ligne pour vos enfants !</p>
                </div>
                <div class="footer-section">
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="jouets.php">Nos Jouets</a></li>
                        <li><a href="commande.php">Commander</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p><i class="fas fa-envelope"></i> mahdi.debbah@outlook.com</p>
                    <p><i class="fas fa-phone"></i> 01 23 45 67 89</p>
                    <p><i class="fas fa-map-marker-alt"></i> Paris, France</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 ToyKids - Tous droits réservés | Fait avec <i class="fas fa-heart"></i> pour les enfants</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
