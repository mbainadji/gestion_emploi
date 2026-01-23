<?php
require_once __DIR__ . '/../../includes/config.php';

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? 'student'; // 'student' or 'teacher'

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $error = "Ce nom d'utilisateur est déjà pris.";
    } else {
        try {
            $pdo->beginTransaction();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role, $full_name]);
            $user_id = $pdo->lastInsertId();

            if ($role === 'teacher') {
                // Assuming department_id 1 for now, this should be part of the form later
                $stmt = $pdo->prepare("INSERT INTO teachers (user_id, name, department_id) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $full_name, 1]);
            }

            $pdo->commit();
            $message = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la création du compte : " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 2rem auto;">
    <h2>Inscription</h2>
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>
    <?php if ($message): ?><p style="color: green;"><?php echo $message; ?></p><?php endif; ?>
    
    <form method="POST">
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
    <p>Déjà un compte ? <a href="login.php">Connectez-vous ici</a>.</p>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
