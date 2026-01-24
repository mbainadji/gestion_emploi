<?php
require_once __DIR__ . '/../../includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        redirect('/dashboard.php');
    } else {
        $error = 'Identifiants incorrects.';
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 450px; margin: 4rem auto; padding: 3rem;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <div style="background: var(--primary); color: white; width: 60px; height: 60px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);">T</div>
        <h2 style="font-size: 1.75rem; font-weight: 800; color: var(--text);">Espace Connexion</h2>
        <p style="color: var(--text-muted);">Accédez à votre espace de gestion</p>
    </div>
    
    <?php if ($error): ?>
        <div style="background: #fef2f2; color: var(--danger); padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; border: 1px solid #fee2e2; font-size: 0.9rem; text-align: center;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Identifiant</label>
            <input type="text" name="username" placeholder="votre_nom" required>
        </div>
        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-top: 1rem;">Se connecter</button>
    </form>
    
    <div style="margin-top: 2rem; text-align: center; font-size: 0.9rem; color: var(--text-muted);">
        Vous n'avez pas de compte ? <a href="register.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Inscrivez-vous</a>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
