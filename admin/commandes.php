<?php
require_once 'auth.php';
$admin = getCurrentAdmin();
$pdo = getDBConnection();

// Traitement de la suppression d'une commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_commande'])) {
    $id_commande = intval($_POST['id_commande']);
    
    try {
        $pdo->beginTransaction();
        
        // Récupérer les détails pour restaurer le stock
        $stmt = $pdo->prepare("SELECT id_jouet, quantite FROM details_commande WHERE id_commande = :id");
        $stmt->execute([':id' => $id_commande]);
        $details = $stmt->fetchAll();
        
        // Restaurer le stock
        foreach ($details as $detail) {
            $stmt = $pdo->prepare("UPDATE jouets SET stock = stock + :quantite WHERE id_jouet = :id");
            $stmt->execute([
                ':quantite' => $detail['quantite'],
                ':id' => $detail['id_jouet']
            ]);
        }
        
        // Supprimer les détails de commande (la clé étrangère CASCADE supprimera automatiquement)
        $stmt = $pdo->prepare("DELETE FROM commandes WHERE id_commande = :id");
        $stmt->execute([':id' => $id_commande]);
        
        $pdo->commit();
        $success_message = "Commande #$id_commande supprimée avec succès et stock restauré !";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Traitement de la mise à jour du statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_statut'])) {
    $id_commande = intval($_POST['id_commande']);
    $nouveau_statut = securiser($_POST['statut']);
    
    try {
        $stmt = $pdo->prepare("UPDATE commandes SET statut = :statut WHERE id_commande = :id");
        $stmt->execute([
            ':statut' => $nouveau_statut,
            ':id' => $id_commande
        ]);
        $success_message = "Statut de la commande #$id_commande mis à jour avec succès !";
    } catch (Exception $e) {
        $error_message = "Erreur lors de la mise à jour.";
    }
}

// Filtres
$filtre_statut = isset($_GET['statut']) ? securiser($_GET['statut']) : '';
$recherche = isset($_GET['recherche']) ? securiser($_GET['recherche']) : '';

// Récupérer les commandes avec filtres
$sql = "SELECT c.*, cl.nom, cl.prenom, cl.email, cl.telephone 
        FROM commandes c
        JOIN clients cl ON c.id_client = cl.id_client
        WHERE 1=1";
$params = [];

if (!empty($filtre_statut)) {
    $sql .= " AND c.statut = :statut";
    $params[':statut'] = $filtre_statut;
}

if (!empty($recherche)) {
    $sql .= " AND (cl.nom LIKE :recherche OR cl.prenom LIKE :recherche OR cl.email LIKE :recherche OR c.id_commande LIKE :recherche)";
    $params[':recherche'] = "%$recherche%";
}

$sql .= " ORDER BY c.date_commande DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

// Stats rapides
$stmt = $pdo->query("SELECT statut, COUNT(*) as nombre FROM commandes GROUP BY statut");
$stats_statuts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Commandes - ToyKids Admin</title>
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
        <!-- Top Bar -->
        <header class="topbar">
            <h1>Gestion des Commandes</h1>
            <div class="admin-profile">
                <span>👤 <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></span>
            </div>
        </header>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Stats rapides -->
        <section class="quick-stats">
            <?php
            $statut_colors = [
                'En attente' => 'warning',
                'Confirmée' => 'info',
                'Expédiée' => 'primary',
                'Livrée' => 'success',
                'Annulée' => 'danger'
            ];
            foreach ($statut_colors as $statut => $color):
                $count = $stats_statuts[$statut] ?? 0;
            ?>
                <a href="?statut=<?php echo urlencode($statut); ?>" class="quick-stat stat-<?php echo $color; ?>">
                    <h3><?php echo $count; ?></h3>
                    <p><?php echo htmlspecialchars($statut); ?></p>
                </a>
            <?php endforeach; ?>
        </section>

        <!-- Filtres -->
        <section class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <input type="text" name="recherche" placeholder="🔍 Rechercher (nom, email, ID...)" value="<?php echo htmlspecialchars($recherche); ?>">
                </div>
                <div class="filter-group">
                    <select name="statut">
                        <option value="">Tous les statuts</option>
                        <option value="En attente" <?php echo $filtre_statut === 'En attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="Confirmée" <?php echo $filtre_statut === 'Confirmée' ? 'selected' : ''; ?>>Confirmée</option>
                        <option value="Expédiée" <?php echo $filtre_statut === 'Expédiée' ? 'selected' : ''; ?>>Expédiée</option>
                        <option value="Livrée" <?php echo $filtre_statut === 'Livrée' ? 'selected' : ''; ?>>Livrée</option>
                        <option value="Annulée" <?php echo $filtre_statut === 'Annulée' ? 'selected' : ''; ?>>Annulée</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Filtrer</button>
                <a href="commandes.php" class="btn-secondary">Réinitialiser</a>
                <a href="ajouter-commande.php" class="btn-primary" style="margin-left: auto;"><i class="fas fa-plus"></i> Nouvelle Commande</a>
            </form>
        </section>

        <!-- Liste des commandes -->
        <section class="orders-section">
            <h2>📋 Liste des Commandes (<?php echo count($commandes); ?>)</h2>
            
            <?php if (count($commandes) > 0): ?>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes as $commande): ?>
                                <tr>
                                    <td><strong>#<?php echo $commande['id_commande']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']); ?></strong><br>
                                        <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($commande['email']); ?></small><br>
                                        <small>📞 <?php echo htmlspecialchars($commande['telephone']); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?></td>
                                    <td class="amount"><?php echo number_format($commande['montant_total'], 2, ',', ' '); ?> €</td>
                                    <td>
                                        <form method="POST" class="status-form">
                                            <input type="hidden" name="id_commande" value="<?php echo $commande['id_commande']; ?>">
                                            <select name="statut" class="status-select status-<?php echo strtolower(str_replace(' ', '-', $commande['statut'])); ?>" onchange="this.form.submit()">
                                                <option value="En attente" <?php echo $commande['statut'] === 'En attente' ? 'selected' : ''; ?>><i class="fas fa-clock"></i> En attente</option>
                                                <option value="Confirmée" <?php echo $commande['statut'] === 'Confirmée' ? 'selected' : ''; ?>><i class="fas fa-check-circle"></i> Confirmée</option>
                                                <option value="Expédiée" <?php echo $commande['statut'] === 'Expédiée' ? 'selected' : ''; ?>><i class="fas fa-box"></i> Expédiée</option>
                                                <option value="Livrée" <?php echo $commande['statut'] === 'Livrée' ? 'selected' : ''; ?>><i class="fas fa-gift"></i> Livrée</option>
                                                <option value="Annulée" <?php echo $commande['statut'] === 'Annulée' ? 'selected' : ''; ?>><i class="fas fa-times-circle"></i> Annulée</option>
                                            </select>
                                            <input type="hidden" name="update_statut" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <button onclick="toggleDetails(<?php echo $commande['id_commande']; ?>)" class="btn-small btn-info">
                                            📋 Détails
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer cette commande ? Le stock sera restauré.');">
                                            <input type="hidden" name="id_commande" value="<?php echo $commande['id_commande']; ?>">
                                            <input type="hidden" name="delete_commande" value="1">
                                            <button type="submit" class="btn-small" style="background: #e74c3c; color: white;">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr id="details-<?php echo $commande['id_commande']; ?>" class="order-details" style="display: none;">
                                    <td colspan="6">
                                        <div class="details-content">
                                            <div class="details-section">
                                                <h4><i class="fas fa-box"></i> Adresse de livraison</h4>
                                                <p><?php echo nl2br(htmlspecialchars($commande['adresse_livraison'])); ?></p>
                                            </div>
                                            
                                            <?php if (!empty($commande['commentaires'])): ?>
                                                <div class="details-section">
                                                    <h4>💬 Commentaires</h4>
                                                    <p><?php echo nl2br(htmlspecialchars($commande['commentaires'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="details-section">
                                                <h4>🎁 Articles commandés</h4>
                                                <?php
                                                $stmt_details = $pdo->prepare("
                                                    SELECT dc.*, j.nom_jouet 
                                                    FROM details_commande dc
                                                    JOIN jouets j ON dc.id_jouet = j.id_jouet
                                                    WHERE dc.id_commande = :id
                                                ");
                                                $stmt_details->execute([':id' => $commande['id_commande']]);
                                                $details = $stmt_details->fetchAll();
                                                ?>
                                                <table class="details-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Jouet</th>
                                                            <th>Prix unitaire</th>
                                                            <th>Quantité</th>
                                                            <th>Sous-total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($details as $detail): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($detail['nom_jouet']); ?></td>
                                                                <td><?php echo number_format($detail['prix_unitaire'], 2, ',', ' '); ?> €</td>
                                                                <td><?php echo $detail['quantite']; ?></td>
                                                                <td><?php echo number_format($detail['sous_total'], 2, ',', ' '); ?> €</td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        <tr class="total-row">
                                                            <td colspan="3"><strong>Total</strong></td>
                                                            <td><strong><?php echo number_format($commande['montant_total'], 2, ',', ' '); ?> €</strong></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>😕 Aucune commande trouvée.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        function toggleDetails(id) {
            const detailsRow = document.getElementById('details-' + id);
            if (detailsRow.style.display === 'none') {
                detailsRow.style.display = 'table-row';
            } else {
                detailsRow.style.display = 'none';
            }
        }
    </script>
</body>
</html>
