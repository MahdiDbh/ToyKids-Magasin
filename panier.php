<?php
// Démarrer la session pour les messages
session_start();

// Fonctions pour gérer le panier avec cookies
function getPanier() {
    if (isset($_COOKIE['panier'])) {
        return json_decode($_COOKIE['panier'], true) ?: [];
    }
    return [];
}

function savePanier($panier) {
    setcookie('panier', json_encode($panier), time() + (86400 * 30), '/'); // 30 jours
}

function ajouterAuPanier($id_jouet, $quantite = 1) {
    $panier = getPanier();
    if (isset($panier[$id_jouet])) {
        $panier[$id_jouet] += $quantite;
    } else {
        $panier[$id_jouet] = $quantite;
    }
    savePanier($panier);
}

function supprimerDuPanier($id_jouet) {
    $panier = getPanier();
    unset($panier[$id_jouet]);
    savePanier($panier);
}

function modifierQuantite($id_jouet, $quantite) {
    $panier = getPanier();
    if ($quantite <= 0) {
        unset($panier[$id_jouet]);
    } else {
        $panier[$id_jouet] = $quantite;
    }
    savePanier($panier);
}

function viderPanier() {
    setcookie('panier', '', time() - 3600, '/');
}

function getNombreProduits() {
    $panier = getPanier();
    return array_sum($panier);
}

// Traitement des actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'ajouter' && isset($_POST['id_jouet']) && isset($_POST['quantite'])) {
            $id_jouet = intval($_POST['id_jouet']);
            $quantite = intval($_POST['quantite']);
            if ($quantite > 0) {
                ajouterAuPanier($id_jouet, $quantite);
                $message = 'Produit ajouté au panier !';
            }
        } elseif ($action === 'supprimer' && isset($_POST['id_jouet'])) {
            supprimerDuPanier(intval($_POST['id_jouet']));
            $message = 'Produit retiré du panier !';
        } elseif ($action === 'modifier' && isset($_POST['id_jouet']) && isset($_POST['quantite'])) {
            modifierQuantite(intval($_POST['id_jouet']), intval($_POST['quantite']));
            $message = 'Quantité mise à jour !';
        } elseif ($action === 'vider') {
            viderPanier();
            $message = 'Panier vidé !';
        }
        
        // Recharger pour éviter le double submit
        header('Location: panier.php');
        exit;
    }
}

// Récupérer les détails des produits du panier
require_once 'config.php';
$panier = getPanier();
$produits_panier = [];
$total = 0;

if (!empty($panier)) {
    try {
        $pdo = getDBConnection();
        $ids = array_keys($panier);
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT * FROM jouets WHERE id_jouet IN ($placeholders)");
        $stmt->execute($ids);
        $jouets = $stmt->fetchAll();
        
        foreach ($jouets as $jouet) {
            $quantite = $panier[$jouet['id_jouet']];
            $sous_total = $jouet['prix'] * $quantite;
            $total += $sous_total;
            
            $produits_panier[] = [
                'jouet' => $jouet,
                'quantite' => $quantite,
                'sous_total' => $sous_total
            ];
        }
    } catch (Exception $e) {
        $error = 'Erreur lors du chargement du panier.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToyKids - Mon Panier</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        .panier-table {
            width: 100%;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .panier-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .panier-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: left;
            font-size: 1.1em;
        }
        .panier-table td {
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
        }
        .panier-table tr:last-child td {
            border-bottom: none;
        }
        .panier-produit {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .panier-produit-image {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
        }
        .panier-quantite {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .panier-quantite input {
            width: 60px;
            padding: 8px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }
        .panier-total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-top: 30px;
        }
        .panier-total h3 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        .panier-total p {
            font-size: 3em;
            font-weight: 800;
        }
        .panier-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        .btn-supprimer {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-supprimer:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        .panier-vide {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
        }
        .panier-vide i {
            font-size: 5em;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
    </style>
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
                    <li><a href="panier.php" class="active"><i class="fas fa-shopping-cart"></i> Panier (<?php echo getNombreProduits(); ?>)</a></li>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Section Hero -->
    <section class="hero" style="padding: 50px 20px;">
        <div class="hero-content">
            <h2><i class="fas fa-shopping-cart"></i> Mon Panier</h2>
            <p class="hero-subtitle">Vérifiez vos articles avant de commander</p>
        </div>
    </section>

    <!-- Section Panier -->
    <section class="products-section">
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($produits_panier)): ?>
                <div class="panier-vide">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Votre panier est vide</h2>
                    <p style="margin: 20px 0; color: #7f8c8d;">Découvrez notre collection de jouets !</p>
                    <a href="jouets.php" class="btn btn-primary">Voir les jouets</a>
                </div>
            <?php else: ?>
                <div class="panier-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Prix unitaire</th>
                                <th>Quantité</th>
                                <th>Sous-total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produits_panier as $item): ?>
                                <tr>
                                    <td>
                                        <div class="panier-produit">
                                            <div class="panier-produit-image">
                                                <i class="fas fa-gift"></i>
                                            </div>
                                            <div>
                                                <h3 style="margin: 0; color: #2c3e50;"><?php echo htmlspecialchars($item['jouet']['nom_jouet']); ?></h3>
                                                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 0.9em;"><?php echo htmlspecialchars($item['jouet']['categorie']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-size: 1.2em; color: #667eea; font-weight: 700;">
                                        <?php echo number_format($item['jouet']['prix'], 2, ',', ' '); ?> €
                                    </td>
                                    <td>
                                        <form method="POST" class="panier-quantite">
                                            <input type="hidden" name="action" value="modifier">
                                            <input type="hidden" name="id_jouet" value="<?php echo $item['jouet']['id_jouet']; ?>">
                                            <input type="number" name="quantite" value="<?php echo $item['quantite']; ?>" min="1" max="<?php echo $item['jouet']['stock']; ?>" onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td style="font-size: 1.3em; color: #27ae60; font-weight: 800;">
                                        <?php echo number_format($item['sous_total'], 2, ',', ' '); ?> €
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="supprimer">
                                            <input type="hidden" name="id_jouet" value="<?php echo $item['jouet']['id_jouet']; ?>">
                                            <button type="submit" class="btn-supprimer" onclick="return confirm('Retirer ce produit du panier ?')">
                                                <i class="fas fa-trash"></i> Retirer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="panier-total">
                    <h3>Total de votre panier</h3>
                    <p><?php echo number_format($total, 2, ',', ' '); ?> €</p>
                </div>

                <div class="panier-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="vider">
                        <button type="submit" class="btn btn-secondary" onclick="return confirm('Êtes-vous sûr de vouloir vider le panier ?')">
                            <i class="fas fa-trash"></i> Vider le panier
                        </button>
                    </form>
                    <a href="jouets.php" class="btn btn-info">
                        <i class="fas fa-arrow-left"></i> Continuer mes achats
                    </a>
                    <a href="commande.php" class="btn btn-primary btn-large">
                        <i class="fas fa-check"></i> Valider ma commande
                    </a>
                </div>
            <?php endif; ?>
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
                        <li><a href="panier.php">Panier</a></li>
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
