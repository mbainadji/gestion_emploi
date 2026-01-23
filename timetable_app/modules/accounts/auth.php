<?php
require_once __DIR__ . '/../../includes/config.php';

$error = '';
$message = '';

// Handle POST from either form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form_type'] ?? '';
    if ($form === 'login') {
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
    } elseif ($form === 'register') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? 'student';

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Ce nom d'utilisateur est déjà pris.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role, $full_name]);
            $user_id = $pdo->lastInsertId();

            if ($role === 'teacher') {
                $stmt = $pdo->prepare("INSERT INTO teachers (user_id, name) VALUES (?, ?)");
                $stmt->execute([$user_id, $full_name]);
            }

            $message = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card auth-container">
    <div class="auth-column">
        <h2>Connexion</h2>
        <?php if ($error && ($_POST['form_type'] ?? '') === 'login'): ?><p style="color:red"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="form_type" value="login">
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
        <p>Ou <a href="login.php">voir la page de connexion</a>.</p>
    </div>

    <div class="auth-column">
        <h2>Inscription</h2>
        <?php if ($message): ?><p style="color:green"><?php echo $message; ?></p><?php endif; ?>
        <?php if ($error && ($_POST['form_type'] ?? '') === 'register'): ?><p style="color:red"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="form_type" value="register">
            <div>
                <label>Nom complet</label>
                <input type="text" name="full_name" required>
            </div>
            <div>
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" required>
            </div>
            <div>
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </div>
            <div>
                <label>Je suis un :</label>
                <select name="role">
                    <option value="student">Étudiant</option>
                    <option value="teacher">Enseignant</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">S'inscrire</button>
        </form>
        <p>Ou <a href="register.php">voir la page d'inscription</a>.</p>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
