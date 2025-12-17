<?php
require_once 'auth.php';
$admin = getCurrentAdmin();
$pdo = getDBConnection();

// Récupérer les statistiques
try {
    // Total des commandes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM commandes");
    $total_commandes = $stmt->fetch()['total'];
    
    // Total des revenus
    $stmt = $pdo->query("SELECT SUM(montant_total) as total FROM commandes WHERE statut != 'Annulée'");
    $total_revenus = $stmt->fetch()['total'] ?? 0;
    
    // Total des clients
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
    $total_clients = $stmt->fetch()['total'];
    
    // Total des jouets
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM jouets");
    $total_jouets = $stmt->fetch()['total'];
    
    // Commandes en attente
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM commandes WHERE statut = 'En attente'");
    $commandes_attente = $stmt->fetch()['total'];
    
    // Messages non lus
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts WHERE statut = 'Non lu'");
    $messages_non_lus = $stmt->fetch()['total'];
    
    // Dernières commandes
    $stmt = $pdo->query("
        SELECT c.*, cl.nom, cl.prenom, cl.email 
        FROM commandes c
        JOIN clients cl ON c.id_client = cl.id_client
        ORDER BY c.date_commande DESC
        LIMIT 5
    ");
    $dernieres_commandes = $stmt->fetchAll();
    
    // Statistiques par statut
    $stmt = $pdo->query("
        SELECT statut, COUNT(*) as nombre, SUM(montant_total) as montant
        FROM commandes
        GROUP BY statut
    ");
    $stats_par_statut = $stmt->fetchAll();
    
    // Top 5 jouets vendus
    $stmt = $pdo->query("
        SELECT j.nom_jouet, SUM(dc.quantite) as total_vendu, j.prix
        FROM details_commande dc
        JOIN jouets j ON dc.id_jouet = j.id_jouet
        GROUP BY dc.id_jouet
        ORDER BY total_vendu DESC
        LIMIT 5
    ");
    $top_jouets = $stmt->fetchAll();
    
    // Ventes par catégorie
    $stmt = $pdo->query("
        SELECT j.categorie, SUM(dc.quantite) as quantite, SUM(dc.sous_total) as montant
        FROM details_commande dc
        JOIN jouets j ON dc.id_jouet = j.id_jouet
        GROUP BY j.categorie
        ORDER BY montant DESC
    ");
    $ventes_par_categorie = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Erreur lors du chargement des statistiques.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ToyKids Admin</title>
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
            <a href="dashboard.php" class="active">
                <span class="icon"><i class="fas fa-chart-bar"></i></span>
                Dashboard
            </a>
            <a href="commandes.php">
                <span class="icon"><i class="fas fa-shopping-cart"></i></span>
                Commandes
                <?php if ($commandes_attente > 0): ?>
                    <span class="badge"><?php echo $commandes_attente; ?></span>
                <?php endif; ?>
            </a>
            <a href="messages.php">
                <span class="icon"><i class="fas fa-envelope"></i></span>
                Messages
                <?php if ($messages_non_lus > 0): ?>
                    <span class="badge"><?php echo $messages_non_lus; ?></span>
                <?php endif; ?>
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
            <h1>Dashboard</h1>
            <div class="admin-profile">
                <span>👤 <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></span>
            </div>
        </header>

        <!-- Stats Cards -->
        <section class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_revenus, 2, ',', ' '); ?> €</h3>
                    <p>Revenus Total</p>
                </div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-content">
                    <h3><?php echo $total_commandes; ?></h3>
                    <p>Commandes</p>
                </div>
            </div>
            <div class="stat-card stat-info">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-content">
                    <h3><?php echo $total_clients; ?></h3>
                    <p>Clients</p>
                </div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-icon">🎁</div>
                <div class="stat-content">
                    <h3><?php echo $total_jouets; ?></h3>
                    <p>Jouets</p>
                </div>
            </div>
        </section>

        <!-- Charts Row -->
        <section class="charts-row">
            <div class="chart-card">
                <h2>📈 Commandes par Statut</h2>
                <div class="chart-content">
                    <?php foreach ($stats_par_statut as $stat): ?>
                        <div class="stat-row">
                            <span class="stat-label">
                                <?php
                                $icons = [
                                    'En attente' => '<i class="fas fa-clock"></i>',
                                    'Confirmée' => '<i class="fas fa-check-circle"></i>',
                                    'Expédiée' => '<i class="fas fa-box"></i>',
                                    'Livrée' => '<i class="fas fa-gift"></i>',
                                    'Annulée' => '<i class="fas fa-times-circle"></i>'
                                ];
                                echo $icons[$stat['statut']] ?? '📋';
                                ?>
                                <?php echo htmlspecialchars($stat['statut']); ?>
                            </span>
                            <span class="stat-value">
                                <?php echo $stat['nombre']; ?> commandes
                                (<?php echo number_format($stat['montant'], 2, ',', ' '); ?> €)
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="chart-card">
                <h2>🏆 Top 5 Jouets Vendus</h2>
                <div class="chart-content">
                    <?php foreach ($top_jouets as $index => $jouet): ?>
                        <div class="stat-row">
                            <span class="stat-label">
                                <span class="rank">#<?php echo $index + 1; ?></span>
                                <?php echo htmlspecialchars($jouet['nom_jouet']); ?>
                            </span>
                            <span class="stat-value">
                                <?php echo $jouet['total_vendu']; ?> vendus
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Category Sales -->
        <section class="full-width-card">
            <h2><i class="fas fa-chart-bar"></i> Ventes par Catégorie</h2>
            <div class="category-grid">
                <?php foreach ($ventes_par_categorie as $cat): ?>
                    <div class="category-stat">
                        <div class="category-header">
                            <?php
                            $emoji_map = [
                                'Électronique' => '🤖',
                                'Poupées' => '👸',
                                'Éducatif' => '🧩',
                                'Véhicules' => '🚗',
                                'Construction' => '🏗️',
                                'Peluches' => '🧸',
                                'Sport' => '⚽',
                                'Créatif' => '<i class="fas fa-palette"></i>',
                                'Plein air' => '🏃',
                                'Jeux de société' => '🎲'
                            ];
                            echo $emoji_map[$cat['categorie']] ?? '🎁';
                            ?>
                            <strong><?php echo htmlspecialchars($cat['categorie']); ?></strong>
                        </div>
                        <div class="category-stats">
                            <p><?php echo $cat['quantite']; ?> articles vendus</p>
                            <p class="category-amount"><?php echo number_format($cat['montant'], 2, ',', ' '); ?> €</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Recent Orders -->
        <section class="recent-orders">
            <h2>🕒 Dernières Commandes</h2>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dernieres_commandes as $commande): ?>
                            <tr>
                                <td>#<?php echo $commande['id_commande']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($commande['email']); ?></small>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                                <td class="amount"><?php echo number_format($commande['montant_total'], 2, ',', ' '); ?> €</td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $commande['statut'])); ?>">
                                        <?php echo htmlspecialchars($commande['statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="commandes.php?id=<?php echo $commande['id_commande']; ?>" class="btn-small btn-primary">Voir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="commandes.php" class="btn-secondary">Voir toutes les commandes</a>
            </div>
        </section>
    </main>
</body>
</html>
