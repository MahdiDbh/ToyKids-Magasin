<?php
require_once 'auth.php';
$admin = getCurrentAdmin();
$pdo = getDBConnection();

// Récupérer tous les clients
$stmt = $pdo->query("
    SELECT c.*, 
           COUNT(DISTINCT co.id_commande) as nb_commandes,
           COALESCE(SUM(co.montant_total), 0) as total_depense
    FROM clients c
    LEFT JOIN commandes co ON c.id_client = co.id_client
    GROUP BY c.id_client
    ORDER BY c.date_inscription DESC
");
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - ToyKids Admin</title>
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
            <a href="clients.php" class="active">
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
            <h1>Gestion des Clients</h1>
            <div class="admin-profile">
                <span>👤 <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></span>
            </div>
        </header>

        <section class="clients-section">
            <h2><i class="fas fa-users"></i> Liste des Clients (<?php echo count($clients); ?>)</h2>
            
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom Complet</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Ville</th>
                            <th>Commandes</th>
                            <th>Total Dépensé</th>
                            <th>Inscrit le</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>#<?php echo $client['id_client']; ?></td>
                                <td><strong><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></strong></td>
                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                <td><?php echo htmlspecialchars($client['telephone']); ?></td>
                                <td><?php echo htmlspecialchars($client['ville']); ?></td>
                                <td><?php echo $client['nb_commandes']; ?></td>
                                <td class="amount"><?php echo number_format($client['total_depense'], 2, ',', ' '); ?> €</td>
                                <td><?php echo date('d/m/Y', strtotime($client['date_inscription'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
