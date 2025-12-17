<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToyKids - Bienvenue dans notre Magasin de Jouets !</title>
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
                    <li><a href="index.php" class="active"><i class="fas fa-home"></i> Accueil</a></li>
                    <li><a href="jouets.php"><i class="fas fa-gamepad"></i> Nos Jouets</a></li>
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

    <!-- Section Hero - Bannière principale -->
    <section class="hero">
        <div class="hero-content">
            <h2 class="animate-bounce"><i class="fas fa-star"></i> Bienvenue chez ToyKids ! <i class="fas fa-star"></i></h2>
            <p class="hero-subtitle">Le monde magique des jouets pour tous les enfants</p>
            <div class="hero-buttons">
                <a href="jouets.php" class="btn btn-primary">Découvrir nos jouets</a>
                <a href="commande.php" class="btn btn-secondary">Commander maintenant</a>
            </div>
        </div>
        <div class="hero-animation">
            <div class="floating-toy"><i class="fas fa-rocket"></i></div>
            <div class="floating-toy"><i class="fas fa-teddy-bear"></i></div>
            <div class="floating-toy"><i class="fas fa-puzzle-piece"></i></div>
            <div class="floating-toy"><i class="fas fa-palette"></i></div>
            <div class="floating-toy"><i class="fas fa-football-ball"></i></div>
        </div>
    </section>

    <!-- Section À propos -->
    <section class="about-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-star"></i> Qui sommes-nous ? <i class="fas fa-star"></i></h2>
            <div class="about-content">
                <div class="about-card">
                    <div class="card-icon"><i class="fas fa-gift"></i></div>
                    <h3>Des jouets de qualité</h3>
                    <p>Nous sélectionnons les meilleurs jouets pour garantir la joie et la sécurité de vos enfants !</p>
                </div>
                <div class="about-card">
                    <div class="card-icon"><i class="fas fa-heart"></i></div>
                    <h3>Pour tous les âges</h3>
                    <p>De 2 à 14 ans, trouvez le jouet parfait adapté à l'âge de votre enfant !</p>
                </div>
                <div class="about-card">
                    <div class="card-icon"><i class="fas fa-shipping-fast"></i></div>
                    <h3>Livraison rapide</h3>
                    <p>Recevez vos jouets rapidement et en toute sécurité directement chez vous !</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Catégories -->
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-th-large"></i> Nos Catégories <i class="fas fa-th-large"></i></h2>
            <div class="categories-grid">
                <div class="category-card" style="--color: #FF6B9D">
                    <div class="category-icon"><i class="fas fa-robot"></i></div>
                    <h3>Électronique</h3>
                    <p>Robots, trains, gadgets</p>
                </div>
                <div class="category-card" style="--color: #FFA07A">
                    <div class="category-icon"><i class="fas fa-female"></i></div>
                    <h3>Poupées</h3>
                    <p>Poupées et accessoires</p>
                </div>
                <div class="category-card" style="--color: #98D8C8">
                    <div class="category-icon"><i class="fas fa-puzzle-piece"></i></div>
                    <h3>Éducatif</h3>
                    <p>Puzzles et jeux d'éveil</p>
                </div>
                <div class="category-card" style="--color: #F7DC6F">
                    <div class="category-icon"><i class="fas fa-car"></i></div>
                    <h3>Véhicules</h3>
                    <p>Voitures et avions</p>
                </div>
                <div class="category-card" style="--color: #BB8FCE">
                    <div class="category-icon"><i class="fas fa-cubes"></i></div>
                    <h3>Construction</h3>
                    <p>Blocs et LEGO</p>
                </div>
                <div class="category-card" style="--color: #85C1E2">
                    <div class="category-icon"><i class="fas fa-paw"></i></div>
                    <h3>Peluches</h3>
                    <p>Doudous tout doux</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Jouets populaires -->
    <section class="featured-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-star"></i> Jouets Populaires <i class="fas fa-star"></i></h2>
            <div class="products-preview">
                <?php
                require_once 'config.php';
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->query("SELECT * FROM jouets WHERE stock > 0 ORDER BY RAND() LIMIT 4");
                    $jouets = $stmt->fetchAll();
                    
                    if (count($jouets) > 0) {
                        foreach ($jouets as $jouet) {
                            echo '<div class="product-card">';
                            echo '<div class="product-image">';
                            echo '<span class="product-emoji"><i class="fas fa-gift"></i></span>';
                            echo '</div>';
                            echo '<h3>' . htmlspecialchars($jouet['nom_jouet']) . '</h3>';
                            echo '<p class="product-age"><i class="fas fa-child"></i> ' . htmlspecialchars($jouet['age_recommande']) . '</p>';
                            echo '<p class="product-price">' . number_format($jouet['prix'], 2, ',', ' ') . ' €</p>';
                            echo '<a href="jouets.php" class="btn btn-small">Voir détails</a>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div style="text-align: center; padding: 40px;">';
                        echo '<p style="font-size: 1.3em; color: #7f8c8d;"><i class="fas fa-palette"></i> Nos jouets seront bientôt disponibles !</p>';
                        echo '</div>';
                    }
                } catch (Exception $e) {
                    echo '<div style="text-align: center; padding: 40px;">';
                    echo '<p style="font-size: 1.2em; color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> Impossible de charger les jouets pour le moment.</p>';
                    echo '<p style="color: #7f8c8d; margin-top: 10px;">Veuillez vérifier que la base de données est correctement configurée.</p>';
                    echo '</div>';
                }
                ?>
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <a href="jouets.php" class="btn btn-primary">Voir tous les jouets</a>
            </div>
        </div>
    </section>

    <!-- Section Appel à l'action -->
    <section class="cta-section">
        <div class="container">
            <h2><i class="fas fa-gift"></i> Prêt à faire plaisir ? <i class="fas fa-gift"></i></h2>
            <p>Découvrez notre collection complète et commandez dès maintenant !</p>
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
                    <p><i class="fas fa-envelope"></i> mahdi.debbah@outlok.com</p>
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
