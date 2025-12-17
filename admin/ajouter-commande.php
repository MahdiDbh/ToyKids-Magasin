<?php
require_once 'auth.php';
$admin = getCurrentAdmin();
$pdo = getDBConnection();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Récupérer les données client
    $id_client_existant = intval($_POST['id_client'] ?? 0);
    $nom = securiser($_POST['nom'] ?? '');
    $prenom = securiser($_POST['prenom'] ?? '');
    $email = securiser($_POST['email'] ?? '');
    $telephone = securiser($_POST['telephone'] ?? '');
    $adresse = securiser($_POST['adresse'] ?? '');
    $ville = securiser($_POST['ville'] ?? '');
    $code_postal = securiser($_POST['code_postal'] ?? '');
    $commentaires = securiser($_POST['commentaires'] ?? '');
    $statut = securiser($_POST['statut'] ?? 'En attente');
    
    // Vérifier les jouets sélectionnés
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
    
    // Si pas d'erreurs, créer la commande
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Déterminer le client
            if ($id_client_existant > 0) {
                $id_client = $id_client_existant;
            } else {
                // Créer un nouveau client
                if (empty($nom) || empty($prenom) || empty($email)) {
                    throw new Exception("Veuillez remplir les informations client ou sélectionner un client existant.");
                }
                
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
                $stmt = $pdo->prepare("SELECT prix, stock FROM jouets WHERE id_jouet = :id_jouet");
                $stmt->execute([':id_jouet' => $id_jouet]);
                $jouet = $stmt->fetch();
                
                if (!$jouet) {
                    throw new Exception("Jouet introuvable.");
                }
                
                if ($jouet['stock'] < $quantite) {
                    throw new Exception("Stock insuffisant pour le jouet ID $id_jouet.");
                }
                
                $montant_total += $jouet['prix'] * $quantite;
            }
            
            // Créer la commande
            $adresse_livraison = $adresse . ', ' . $code_postal . ' ' . $ville;
            $stmt = $pdo->prepare("INSERT INTO commandes (id_client, montant_total, adresse_livraison, commentaires, statut) VALUES (:id_client, :montant_total, :adresse_livraison, :commentaires, :statut)");
            $stmt->execute([
                ':id_client' => $id_client,
                ':montant_total' => $montant_total,
                ':adresse_livraison' => $adresse_livraison,
                ':commentaires' => $commentaires,
                ':statut' => $statut
            ]);
            $id_commande = $pdo->lastInsertId();
            
            // Ajouter les détails
            foreach ($jouets_commandes as $id_jouet => $quantite) {
                $stmt = $pdo->prepare("SELECT prix FROM jouets WHERE id_jouet = :id_jouet");
                $stmt->execute([':id_jouet' => $id_jouet]);
                $jouet = $stmt->fetch();
                
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
            
            $pdo->commit();
            $success_message = "Commande #$id_commande créée avec succès !";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

// Récupérer les clients pour le dropdown
$stmt = $pdo->query("SELECT * FROM clients ORDER BY nom, prenom");
$clients = $stmt->fetchAll();

// Récupérer les jouets
$stmt = $pdo->query("SELECT * FROM jouets WHERE stock > 0 ORDER BY categorie, nom_jouet");
$jouets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Commande - ToyKids Admin</title>
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
            <a href="commandes.php" class="active">
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
            <a href="jouets.php">
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
            <h1>Ajouter une Nouvelle Commande</h1>
            <div class="admin-profile">
                <span>👤 <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></span>
            </div>
        </header>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                <a href="commandes.php" style="margin-left: 15px; color: #155724; font-weight: 700;">Retour aux commandes</a>
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

        <section class="orders-section">
            <form method="POST" id="addOrderForm">
                <h2>👤 Client</h2>
                
                <div style="margin-bottom: 30px;">
                    <label>
                        <input type="radio" name="client_type" value="existing" checked onchange="toggleClientForm()"> 
                        Client existant
                    </label>
                    <label style="margin-left: 20px;">
                        <input type="radio" name="client_type" value="new" onchange="toggleClientForm()"> 
                        Nouveau client
                    </label>
                </div>

                <div id="existing-client" style="margin-bottom: 30px;">
                    <label>Sélectionner un client :</label>
                    <select name="id_client" id="client_select" style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0; font-size: 1em;">
                        <option value="0">-- Choisir un client --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id_client']; ?>">
                                <?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom'] . ' - ' . $client['email']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="new-client" style="display: none;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label>Nom :</label>
                            <input type="text" name="nom" placeholder="Nom" style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0;">
                        </div>
                        <div>
                            <label>Prénom :</label>
                            <input type="text" name="prenom" placeholder="Prénom" style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label>Email :</label>
                            <input type="email" name="email" placeholder="Email" style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0;">
                        </div>
                        <div>
                            <label>Téléphone :</label>
                            <input type="tel" name="telephone" placeholder="Téléphone" style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0;">
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label>Adresse :</label>
                        <input type="text" name="adresse" placeholder="Adresse complète" style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0;">
                    </div>
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label>Ville :</label>
                            <input type="text" name="ville" placeholder="Ville" style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0;">
                        </div>
                        <div>
                            <label>Code Postal :</label>
                            <input type="text" name="code_postal" placeholder="Code postal" style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0;">
                        </div>
                    </div>
                </div>

                <h2 style="margin-top: 40px;">🎁 Jouets</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <?php foreach ($jouets as $jouet): ?>
                        <div style="background: white; padding: 20px; border-radius: 12px; border: 2px solid #e0e0e0;">
                            <h4><?php echo htmlspecialchars($jouet['nom_jouet']); ?></h4>
                            <p style="color: #667eea; font-weight: 700; font-size: 1.2em; margin: 10px 0;">
                                <?php echo number_format($jouet['prix'], 2, ',', ' '); ?> €
                            </p>
                            <p style="font-size: 0.9em; color: #7f8c8d;">
                                Stock : <?php echo $jouet['stock']; ?>
                            </p>
                            <label>Quantité :</label>
                            <input type="number" name="quantite_<?php echo $jouet['id_jouet']; ?>" min="0" max="<?php echo $jouet['stock']; ?>" value="0" 
                                   style="width: 100%; padding: 10px; border-radius: 8px; border: 2px solid #e0e0e0; margin-top: 5px;"
                                   data-price="<?php echo $jouet['prix']; ?>" class="quantity-input">
                        </div>
                    <?php endforeach; ?>
                </div>

                <h2>⚙️ Options</h2>
                <div style="margin-bottom: 20px;">
                    <label>Statut de la commande :</label>
                    <select name="statut" style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0; font-size: 1em;">
                        <option value="En attente"><i class="fas fa-clock"></i> En attente</option>
                        <option value="Confirmée"><i class="fas fa-check-circle"></i> Confirmée</option>
                        <option value="Expédiée"><i class="fas fa-box"></i> Expédiée</option>
                        <option value="Livrée"><i class="fas fa-gift"></i> Livrée</option>
                    </select>
                </div>

                <div style="margin-bottom: 30px;">
                    <label>Commentaires :</label>
                    <textarea name="commentaires" rows="4" placeholder="Commentaires optionnels..." 
                              style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #e0e0e0;"></textarea>
                </div>

                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 15px; color: white; margin-bottom: 30px;">
                    <h3 style="font-size: 1.8em; margin-bottom: 15px;">📋 Total de la Commande</h3>
                    <p style="font-size: 2em; font-weight: 700;"><span id="totalPrice">0.00</span> €</p>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="btn-primary" style="padding: 15px 40px; font-size: 1.1em;">
                        ✓ Créer la Commande
                    </button>
                    <a href="commandes.php" class="btn-secondary" style="padding: 15px 40px; font-size: 1.1em; margin-left: 15px;">
                        ← Retour
                    </a>
                </div>
            </form>
        </section>
    </main>

    <script>
        function toggleClientForm() {
            const existingDiv = document.getElementById('existing-client');
            const newDiv = document.getElementById('new-client');
            const clientType = document.querySelector('input[name="client_type"]:checked').value;
            
            if (clientType === 'existing') {
                existingDiv.style.display = 'block';
                newDiv.style.display = 'none';
                document.getElementById('client_select').required = true;
            } else {
                existingDiv.style.display = 'none';
                newDiv.style.display = 'block';
                document.getElementById('client_select').required = false;
            }
        }

        // Calculer le total
        function calculateTotal() {
            const inputs = document.querySelectorAll('.quantity-input');
            let total = 0;
            
            inputs.forEach(input => {
                const quantity = parseInt(input.value) || 0;
                const price = parseFloat(input.dataset.price) || 0;
                total += quantity * price;
            });
            
            document.getElementById('totalPrice').textContent = total.toFixed(2).replace('.', ',');
        }

        // Ajouter l'événement sur tous les inputs de quantité
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });

        // Calculer au chargement
        calculateTotal();
    </script>
</body>
</html>
