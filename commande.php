<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToyKids - Passer une Commande</title>
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

    <!-- Section Hero -->
    <section class="hero" style="padding: 50px 20px;">
        <div class="hero-content">
            <h2><i class="fas fa-shopping-cart"></i> Passer une Commande <i class="fas fa-shopping-cart"></i></h2>
            <p class="hero-subtitle">Remplissez le formulaire pour commander vos jouets préférés !</p>
        </div>
    </section>

    <?php
    require_once 'config.php';
    
    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $errors = [];
        
        // Validation des données
        $nom = securiser($_POST['nom'] ?? '');
        $prenom = securiser($_POST['prenom'] ?? '');
        $email = securiser($_POST['email'] ?? '');
        $telephone = securiser($_POST['telephone'] ?? '');
        $adresse = securiser($_POST['adresse'] ?? '');
        $ville = securiser($_POST['ville'] ?? '');
        $code_postal = securiser($_POST['code_postal'] ?? '');
        $commentaires = securiser($_POST['commentaires'] ?? '');
        
        if (empty($nom) || empty($prenom) || empty($email) || empty($telephone) || empty($adresse)) {
            $errors[] = "Tous les champs obligatoires doivent être remplis.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
        
        // Vérifier qu'au moins un jouet est sélectionné
        $jouets_commandes = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'quantite_') === 0 && intval($value) > 0) {
                $id_jouet = str_replace('quantite_', '', $key);
                $jouets_commandes[$id_jouet] = intval($value);
            }
        }
        
        if (empty($jouets_commandes)) {
            $errors[] = "Veuillez sélectionner au moins un jouet.";
        }
        
        // Si pas d'erreurs, enregistrer la commande
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                $pdo->beginTransaction();
                
                // Vérifier si le client existe déjà
                $stmt = $pdo->prepare("SELECT id_client FROM clients WHERE email = :email");
                $stmt->execute([':email' => $email]);
                $client = $stmt->fetch();
                
                if ($client) {
                    $id_client = $client['id_client'];
                    // Mettre à jour les informations du client
                    $stmt = $pdo->prepare("UPDATE clients SET nom = :nom, prenom = :prenom, telephone = :telephone, adresse = :adresse, ville = :ville, code_postal = :code_postal WHERE id_client = :id_client");
                    $stmt->execute([
                        ':nom' => $nom,
                        ':prenom' => $prenom,
                        ':telephone' => $telephone,
                        ':adresse' => $adresse,
                        ':ville' => $ville,
                        ':code_postal' => $code_postal,
                        ':id_client' => $id_client
                    ]);
                } else {
                    // Créer un nouveau client
                    $stmt = $pdo->prepare("INSERT INTO clients (nom, prenom, email, telephone, adresse, ville, code_postal) VALUES (:nom, :prenom, :email, :telephone, :adresse, :ville, :code_postal)");
                    $stmt->execute([
                        ':nom' => $nom,
                        ':prenom' => $prenom,
                        ':email' => $email,
                        ':telephone' => $telephone,
                        ':adresse' => $adresse,
                        ':ville' => $ville,
                        ':code_postal' => $code_postal
                    ]);
                    $id_client = $pdo->lastInsertId();
                }
                
                // Calculer le montant total
                $montant_total = 0;
                foreach ($jouets_commandes as $id_jouet => $quantite) {
                    $stmt = $pdo->prepare("SELECT prix FROM jouets WHERE id_jouet = :id_jouet");
                    $stmt->execute([':id_jouet' => $id_jouet]);
                    $jouet = $stmt->fetch();
                    if ($jouet) {
                        $montant_total += $jouet['prix'] * $quantite;
                    }
                }
                
                // Créer la commande
                $adresse_livraison = $adresse . ', ' . $code_postal . ' ' . $ville;
                $stmt = $pdo->prepare("INSERT INTO commandes (id_client, montant_total, adresse_livraison, commentaires) VALUES (:id_client, :montant_total, :adresse_livraison, :commentaires)");
                $stmt->execute([
                    ':id_client' => $id_client,
                    ':montant_total' => $montant_total,
                    ':adresse_livraison' => $adresse_livraison,
                    ':commentaires' => $commentaires
                ]);
                $id_commande = $pdo->lastInsertId();
                
                // Ajouter les détails de la commande
                foreach ($jouets_commandes as $id_jouet => $quantite) {
                    $stmt = $pdo->prepare("SELECT prix FROM jouets WHERE id_jouet = :id_jouet");
                    $stmt->execute([':id_jouet' => $id_jouet]);
                    $jouet = $stmt->fetch();
                    
                    if ($jouet) {
                        $prix_unitaire = $jouet['prix'];
                        $sous_total = $prix_unitaire * $quantite;
                        
                        $stmt = $pdo->prepare("INSERT INTO details_commande (id_commande, id_jouet, quantite, prix_unitaire, sous_total) VALUES (:id_commande, :id_jouet, :quantite, :prix_unitaire, :sous_total)");
                        $stmt->execute([
                            ':id_commande' => $id_commande,
                            ':id_jouet' => $id_jouet,
                            ':quantite' => $quantite,
                            ':prix_unitaire' => $prix_unitaire,
                            ':sous_total' => $sous_total
                        ]);
                        
                        // Mettre à jour le stock
                        $stmt = $pdo->prepare("UPDATE jouets SET stock = stock - :quantite WHERE id_jouet = :id_jouet");
                        $stmt->execute([
                            ':quantite' => $quantite,
                            ':id_jouet' => $id_jouet
                        ]);
                    }
                }
                
                $pdo->commit();
                
                echo '<div class="container" style="margin-top: 40px;">';
                echo '<div class="success-message">';
                echo '<h2 style="font-size: 2.5em; margin-bottom: 15px;">🎉 Commande Confirmée ! 🎉</h2>';
                echo '<p style="font-size: 1.3em;">Merci ' . htmlspecialchars($prenom) . ' pour votre commande !</p>';
                echo '<p style="font-size: 1.2em; margin-top: 10px;">Numéro de commande : <strong>#' . $id_commande . '</strong></p>';
                echo '<p style="font-size: 1.2em;">Montant total : <strong>' . number_format($montant_total, 2, ',', ' ') . ' €</strong></p>';
                echo '<p style="margin-top: 20px;">Vous recevrez un email de confirmation à : ' . htmlspecialchars($email) . '</p>';
                echo '<a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Retour à l\'accueil</a>';
                echo '</div>';
                echo '</div>';
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Erreur lors de l'enregistrement de la commande : " . $e->getMessage();
            }
        }
        
        // Affichage des erreurs
        if (!empty($errors)) {
            echo '<div class="container" style="margin-top: 40px;">';
            echo '<div class="error-message">';
            echo '<h3>❌ Erreurs dans le formulaire :</h3>';
            echo '<ul style="text-align: left; margin: 15px auto; max-width: 600px;">';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
            echo '</div>';
        }
    }
    ?>

    <!-- Formulaire de commande -->
    <section class="products-section">
        <div class="container">
            <form method="POST" action="commande.php" id="orderForm" class="form-container">
                <h2 class="section-title" style="margin-top: 0;">👤 Vos Informations</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" required placeholder="Votre nom">
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" required placeholder="Votre prénom">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required placeholder="votre@email.com">
                    </div>
                    <div class="form-group">
                        <label for="telephone">Téléphone *</label>
                        <input type="tel" id="telephone" name="telephone" required placeholder="01 23 45 67 89">
                    </div>
                </div>

                <div class="form-group">
                    <label for="adresse">Adresse de livraison *</label>
                    <input type="text" id="adresse" name="adresse" required placeholder="Numéro et nom de rue">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ville">Ville *</label>
                        <input type="text" id="ville" name="ville" required placeholder="Votre ville">
                    </div>
                    <div class="form-group">
                        <label for="code_postal">Code Postal</label>
                        <input type="text" id="code_postal" name="code_postal" placeholder="75001">
                    </div>
                </div>

                <h2 class="section-title">🎁 Sélectionnez vos Jouets</h2>
                
                <div class="products-grid">
                    <?php
                    try {
                        $pdo = getDBConnection();
                        $stmt = $pdo->query("SELECT * FROM jouets WHERE stock > 0 ORDER BY categorie, nom_jouet");
                        $jouets = $stmt->fetchAll();
                        
                        $jouet_preselect = isset($_GET['jouet']) ? intval($_GET['jouet']) : null;
                        
                        foreach ($jouets as $jouet) {
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
                            $is_preselected = ($jouet_preselect == $jouet['id_jouet']);
                            
                            echo '<div class="product-card" style="' . ($is_preselected ? 'border-color: #FF6B9D; box-shadow: 0 0 20px rgba(255, 107, 157, 0.5);' : '') . '">';
                            echo '<div class="product-image">';
                            echo '<span class="product-emoji">' . $icon . '</span>';
                            echo '</div>';
                            echo '<h3>' . htmlspecialchars($jouet['nom_jouet']) . '</h3>';
                            echo '<p class="product-age"><i class="fas fa-child"></i> ' . htmlspecialchars($jouet['age_recommande']) . '</p>';
                            echo '<span class="product-category">' . htmlspecialchars($jouet['categorie']) . '</span>';
                            echo '<p class="product-price" data-price="' . $jouet['prix'] . '">' . number_format($jouet['prix'], 2, ',', ' ') . ' €</p>';
                            echo '<p style="color: #28a745; font-weight: 600; font-size: 0.9em;"><i class="fas fa-check"></i> Stock : ' . $jouet['stock'] . '</p>';
                            
                            echo '<div class="form-group">';
                            echo '<label for="quantite_' . $jouet['id_jouet'] . '">Quantité</label>';
                            echo '<div class="quantity-selector">';
                            echo '<button type="button" class="quantity-btn" onclick="decrementQuantity(' . $jouet['id_jouet'] . ')">-</button>';
                            echo '<input type="number" id="quantite_' . $jouet['id_jouet'] . '" name="quantite_' . $jouet['id_jouet'] . '" class="quantity-input" min="0" max="' . $jouet['stock'] . '" value="' . ($is_preselected ? '1' : '0') . '" onchange="calculateTotal()" data-price="' . $jouet['prix'] . '">';
                            echo '<button type="button" class="quantity-btn" onclick="incrementQuantity(' . $jouet['id_jouet'] . ', ' . $jouet['stock'] . ')">+</button>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } catch (Exception $e) {
                        echo '<p class="error-message">Impossible de charger les jouets.</p>';
                    }
                    ?>
                </div>

                <div class="form-group" style="margin-top: 40px;">
                    <label for="commentaires">Commentaires (optionnel)</label>
                    <textarea id="commentaires" name="commentaires" placeholder="Ajoutez un message ou des instructions spéciales..."></textarea>
                </div>

                <!-- Résumé de la commande -->
                <div style="background: linear-gradient(135deg, #FFD89B 0%, #19547B 100%); padding: 30px; border-radius: 20px; color: white; margin-top: 30px;">
                    <h3 style="font-size: 2em; margin-bottom: 20px; text-align: center;">📋 Résumé de votre Commande</h3>
                    <div id="orderSummary" style="font-size: 1.3em; text-align: center;">
                        <p>Nombre d'articles : <strong><span id="totalItems">0</span></strong></p>
                        <p style="font-size: 1.5em; margin-top: 15px;">Total à payer : <strong><span id="totalPrice">0,00</span> €</strong></p>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-large btn-primary">✓ Valider ma Commande</button>
                </div>
            </form>
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
                    <p><i class="fas fa-envelope"></i> contact@toykids.com</p>
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
