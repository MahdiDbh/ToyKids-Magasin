<?php
require_once 'auth.php';
$admin = getCurrentAdmin();
$pdo = getDBConnection();

// Traitement de la mise à jour du statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_statut'])) {
    $id_contact = intval($_POST['id_contact']);
    $nouveau_statut = securiser($_POST['statut']);
    
    try {
        $stmt = $pdo->prepare("UPDATE contacts SET statut = :statut WHERE id_contact = :id");
        $stmt->execute([
            ':statut' => $nouveau_statut,
            ':id' => $id_contact
        ]);
        $success_message = "Statut du message mis à jour !";
    } catch (Exception $e) {
        $error_message = "Erreur lors de la mise à jour.";
    }
}

// Filtres
$filtre_statut = isset($_GET['statut']) ? securiser($_GET['statut']) : '';

// Récupérer les messages
$sql = "SELECT * FROM contacts WHERE 1=1";
$params = [];

if (!empty($filtre_statut)) {
    $sql .= " AND statut = :statut";
    $params[':statut'] = $filtre_statut;
}

$sql .= " ORDER BY date_envoi DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Stats
$stmt = $pdo->query("SELECT statut, COUNT(*) as nombre FROM contacts GROUP BY statut");
$stats_statuts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages de Contact - ToyKids Admin</title>
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
            <a href="messages.php" class="active">
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
            <h1>Messages de Contact</h1>
            <div class="admin-profile">
                <span>👤 <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></span>
            </div>
        </header>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Stats rapides -->
        <section class="quick-stats">
            <a href="?statut=Non lu" class="quick-stat stat-warning">
                <h3><?php echo $stats_statuts['Non lu'] ?? 0; ?></h3>
                <p>Non lus</p>
            </a>
            <a href="?statut=Lu" class="quick-stat stat-info">
                <h3><?php echo $stats_statuts['Lu'] ?? 0; ?></h3>
                <p>Lus</p>
            </a>
            <a href="?statut=Traité" class="quick-stat stat-success">
                <h3><?php echo $stats_statuts['Traité'] ?? 0; ?></h3>
                <p>Traités</p>
            </a>
            <a href="messages.php" class="quick-stat stat-primary">
                <h3><?php echo count($messages); ?></h3>
                <p>Total</p>
            </a>
        </section>

        <!-- Liste des messages -->
        <section class="messages-section">
            <h2>📬 Liste des Messages (<?php echo count($messages); ?>)</h2>
            
            <?php if (count($messages) > 0): ?>
                <div class="messages-grid">
                    <?php foreach ($messages as $message): ?>
                        <div class="message-card <?php echo $message['statut'] === 'Non lu' ? 'unread' : ''; ?>">
                            <div class="message-header">
                                <div class="message-from">
                                    <strong><?php echo htmlspecialchars($message['nom']); ?></strong>
                                    <small><?php echo htmlspecialchars($message['email']); ?></small>
                                </div>
                                <div class="message-date">
                                    <?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($message['sujet'])): ?>
                                <div class="message-subject">
                                    <strong>Sujet :</strong> <?php echo htmlspecialchars($message['sujet']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="message-body">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
                            
                            <div class="message-footer">
                                <form method="POST" class="status-form-inline">
                                    <input type="hidden" name="id_contact" value="<?php echo $message['id_contact']; ?>">
                                    <select name="statut" class="status-select-small" onchange="this.form.submit()">
                                        <option value="Non lu" <?php echo $message['statut'] === 'Non lu' ? 'selected' : ''; ?>>📭 Non lu</option>
                                        <option value="Lu" <?php echo $message['statut'] === 'Lu' ? 'selected' : ''; ?>>📬 Lu</option>
                                        <option value="Traité" <?php echo $message['statut'] === 'Traité' ? 'selected' : ''; ?>><i class="fas fa-check-circle"></i> Traité</option>
                                    </select>
                                    <input type="hidden" name="update_statut" value="1">
                                </form>
                                
                                <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="btn-small btn-primary">
                                    <i class="fas fa-envelope"></i> Répondre
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>📭 Aucun message trouvé.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
