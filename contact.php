<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToyKids - Contactez-nous</title>
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
                    <li><a href="contact.php" class="active"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Section Hero -->
    <section class="hero" style="padding: 50px 20px;">
        <div class="hero-content">
            <h2><i class="fas fa-envelope"></i> Contactez-nous <i class="fas fa-envelope"></i></h2>
            <p class="hero-subtitle">Nous sommes là pour répondre à toutes vos questions !</p>
        </div>
    </section>

    <?php
    require_once 'config.php';
    
    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $errors = [];
        $success = false;
        
        // Validation des données
        $nom = securiser($_POST['nom'] ?? '');
        $email = securiser($_POST['email'] ?? '');
        $sujet = securiser($_POST['sujet'] ?? '');
        $message = securiser($_POST['message'] ?? '');
        
        // Validations
        if (empty($nom)) {
            $errors[] = "Le nom est obligatoire.";
        }
        
        if (empty($email)) {
            $errors[] = "L'email est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
        
        if (empty($message)) {
            $errors[] = "Le message est obligatoire.";
        } elseif (strlen($message) < 10) {
            $errors[] = "Le message doit contenir au moins 10 caractères.";
        }
        
        // Si pas d'erreurs, enregistrer le message
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("INSERT INTO contacts (nom, email, sujet, message) VALUES (:nom, :email, :sujet, :message)");
                $stmt->execute([
                    ':nom' => $nom,
                    ':email' => $email,
                    ':sujet' => $sujet,
                    ':message' => $message
                ]);
                
                $success = true;
                
                echo '<div class="container" style="margin-top: 40px;">';
                echo '<div class="success-message">';
                echo '<h2 style="font-size: 2.5em; margin-bottom: 15px;"><i class="fas fa-check-circle"></i> Message Envoyé ! <i class="fas fa-check-circle"></i></h2>';
                echo '<p style="font-size: 1.3em;">Merci ' . htmlspecialchars($nom) . ' pour votre message !</p>';
                echo '<p style="margin-top: 15px;">Nous vous répondrons dans les plus brefs délais à l\'adresse : ' . htmlspecialchars($email) . '</p>';
                echo '<a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Retour à l\'accueil</a>';
                echo '</div>';
                echo '</div>';
                
            } catch (Exception $e) {
                $errors[] = "Erreur lors de l'envoi du message. Veuillez réessayer.";
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

    <!-- Formulaire de contact -->
    <section class="products-section">
        <div class="container">
            <div class="form-container">
                <h2 class="section-title" style="margin-top: 0;">💬 Envoyez-nous un Message</h2>
                
                <form method="POST" action="contact.php" id="contactForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom complet *</label>
                            <input type="text" id="nom" name="nom" required placeholder="Votre nom">
                        </div>
                        <div class="form-group">
                            <label for="email">Adresse Email *</label>
                            <input type="email" id="email" name="email" required placeholder="votre@email.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sujet">Sujet</label>
                        <select id="sujet" name="sujet" required>
                            <option value="">-- Choisissez un sujet --</option>
                            <option value="Question sur un produit">Question sur un produit</option>
                            <option value="Suivi de commande">Suivi de commande</option>
                            <option value="Retour ou échange">Retour ou échange</option>
                            <option value="Problème technique">Problème technique</option>
                            <option value="Suggestion">Suggestion</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Votre Message *</label>
                        <textarea id="message" name="message" required placeholder="Écrivez votre message ici..." rows="8"></textarea>
                        <small id="charCount" style="color: #666; display: block; margin-top: 5px;">0 / 500 caractères</small>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn btn-large btn-primary">📨 Envoyer le Message</button>
                    </div>
                </form>
            </div>

            <!-- Informations de contact -->
            <div style="margin-top: 60px;">
                <h2 class="section-title"><i class="fas fa-phone"></i> Nos Coordonnées</h2>
                <div class="about-content">
                    <div class="about-card">
                        <div class="card-icon"><i class="fas fa-envelope"></i></div>
                        <h3>Email</h3>
                        <p><a href="mailto:contact@toykids.com" style="color: #d63447; text-decoration: none; font-weight: 600;">contact@toykids.com</a></p>
                        <p style="margin-top: 10px; font-size: 0.95em;">Réponse sous 24h</p>
                    </div>
                    <div class="about-card">
                        <div class="card-icon"><i class="fas fa-phone"></i></div>
                        <h3>Téléphone</h3>
                        <p style="color: #d63447; font-weight: 600;">01 23 45 67 89</p>
                        <p style="margin-top: 10px; font-size: 0.95em;">Lun-Ven : 9h-18h<br>Sam : 9h-12h</p>
                    </div>
                    <div class="about-card">
                        <div class="card-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <h3>Adresse</h3>
                        <p style="color: #d63447; font-weight: 600;">123 Rue des Jouets</p>
                        <p style="margin-top: 5px;">75001 Paris</p>
                        <p style="margin-top: 5px;">France</p>
                    </div>
                </div>
            </div>

            <!-- FAQ -->
            <div style="margin-top: 60px; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); padding: 40px; border-radius: 25px;">
                <h2 class="section-title" style="color: #764ba2;">❓ Questions Fréquentes</h2>
                <div style="max-width: 800px; margin: 0 auto;">
                    <div style="background: white; padding: 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3 style="color: #FF6B9D; margin-bottom: 10px;">🚚 Quels sont les délais de livraison ?</h3>
                        <p>Nous livrons sous 2 à 5 jours ouvrés en France métropolitaine.</p>
                    </div>
                    <div style="background: white; padding: 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3 style="color: #FF6B9D; margin-bottom: 10px;">💳 Quels modes de paiement acceptez-vous ?</h3>
                        <p>Nous acceptons les cartes bancaires, PayPal et les virements bancaires.</p>
                    </div>
                    <div style="background: white; padding: 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3 style="color: #FF6B9D; margin-bottom: 10px;">🔄 Puis-je retourner un produit ?</h3>
                        <p>Oui, vous disposez de 14 jours pour retourner un produit non déballé.</p>
                    </div>
                    <div style="background: white; padding: 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3 style="color: #FF6B9D; margin-bottom: 10px;">✨ Les jouets sont-ils garantis ?</h3>
                        <p>Tous nos jouets sont garantis 1 an contre les défauts de fabrication.</p>
                    </div>
                </div>
            </div>
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
