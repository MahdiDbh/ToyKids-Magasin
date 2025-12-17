<?php
require_once 'auth.php';
$admin = getCurrentAdmin();
$pdo = getDBConnection();

// Traiter la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_jouet'])) {
    $id_jouet = intval($_POST['id_jouet']);
    
    try {
        // Vérifier si le jouet est dans des commandes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM details_commande WHERE id_jouet = :id_jouet");
        $stmt->execute([':id_jouet' => $id_jouet]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $error_message = "Impossible de supprimer ce jouet car il est présent dans $count commande(s).";
        } else {
            $stmt = $pdo->prepare("DELETE FROM jouets WHERE id_jouet = :id_jouet");
            $stmt->execute([':id_jouet' => $id_jouet]);
            $success_message = "Jouet supprimé avec succès !";
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupérer tous les jouets
$stmt = $pdo->query("
    SELECT j.*,
           COALESCE(SUM(dc.quantite), 0) as total_vendu
    FROM jouets j
    LEFT JOIN details_commande dc ON j.id_jouet = dc.id_jouet
    GROUP BY j.id_jouet
    ORDER BY j.categorie, j.nom_jouet
");
$jouets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jouets - ToyKids Admin</title>
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
            <h1>Gestion des Jouets</h1>
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
                <i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <section class="jouets-section">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2><i class="fas fa-gamepad"></i> Liste des Jouets (<?php echo count($jouets); ?>)</h2>
                <a href="ajouter-jouet.php" class="btn-primary" style="text-decoration: none;">
                    <i class="fas fa-plus"></i> Ajouter un Jouet
                </a>
            </div>
            
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Âge</th>
                            <th>Vendus</th>
                            <th>Ajouté le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jouets as $jouet): ?>
                            <tr>
                                <td>#<?php echo $jouet['id_jouet']; ?></td>
                                <td><strong><?php echo htmlspecialchars($jouet['nom_jouet']); ?></strong></td>
                                <td><span class="category-badge"><?php echo htmlspecialchars($jouet['categorie']); ?></span></td>
                                <td class="amount"><?php echo number_format($jouet['prix'], 2, ',', ' '); ?> €</td>
                                <td>
                                    <?php if ($jouet['stock'] <= 5): ?>
                                        <span class="stock-low"><?php echo $jouet['stock']; ?></span>
                                    <?php else: ?>
                                        <span class="stock-ok"><?php echo $jouet['stock']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($jouet['age_recommande']); ?></td>
                                <td><?php echo $jouet['total_vendu']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($jouet['date_ajout'])); ?></td>
                                <td class="action-buttons">
                                    <a href="modifier-jouet.php?id=<?php echo $jouet['id_jouet']; ?>" class="btn-edit" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce jouet ?');">
                                        <input type="hidden" name="id_jouet" value="<?php echo $jouet['id_jouet']; ?>">
                                        <button type="submit" name="delete_jouet" class="btn-delete" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
