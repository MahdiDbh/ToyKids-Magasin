<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToyKids - Connexion Administrateur</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <?php
    session_start();
    require_once 'config.php';

    // Si déjà connecté, rediriger vers le dashboard
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        header('Location: admin/dashboard.php');
        exit;
    }

    $error_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = securiser($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!empty($username) && !empty($password)) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
                $stmt->execute([':username' => $username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    // Connexion réussie
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id_admin'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_nom'] = $admin['nom'];
                    $_SESSION['admin_prenom'] = $admin['prenom'];

                    // Mettre à jour le dernier login
                    $stmt = $pdo->prepare("UPDATE admins SET dernier_login = NOW() WHERE id_admin = :id");
                    $stmt->execute([':id' => $admin['id_admin']]);

                    header('Location: admin/dashboard.php');
                    exit;
                } else {
                    $error_message = 'Identifiants incorrects.';
                }
            } catch (Exception $e) {
                $error_message = 'Erreur de connexion. Veuillez réessayer.';
            }
        } else {
            $error_message = 'Veuillez remplir tous les champs.';
        }
    }
    ?>

    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><i class="fas fa-lock"></i> Panneau Administrateur</h1>
                <p>ToyKids - Espace Admin</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin-login.php" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <span class="icon"><i class="fas fa-user"></i></span>
                        Nom d'utilisateur
                    </label>
                    <input type="text" id="username" name="username" required autofocus placeholder="Entrez votre nom d'utilisateur">
                </div>

                <div class="form-group">
                    <label for="password">
                        <span class="icon"><i class="fas fa-key"></i></span>
                        Mot de passe
                    </label>
                    <input type="password" id="password" name="password" required placeholder="Entrez votre mot de passe">
                </div>

                <button type="submit" class="btn-login">
                    Se connecter
                </button>
            </form>

            <div class="login-footer">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Retour au site</a>
            </div>

            <div class="login-info">
                <p><strong>Compte de test :</strong></p>
                <p>Utilisateur : <code>admin</code></p>
                <p>Mot de passe : <code>admin123</code></p>
            </div>
        </div>
    </div>
</body>
</html>
