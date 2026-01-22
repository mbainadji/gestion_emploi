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
        redirect('/index.php');
    } else {
        $error = 'Identifiants incorrects.';
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 400px; margin: 2rem auto;">
    <h2>Connexion</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="POST">
        <div>
            <label>Nom d'utilisateur</label>
            <input type="text" name="username" required>
        </div>
        <div>
            <label>Mot de passe</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>
    <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous ici</a>.</p>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
