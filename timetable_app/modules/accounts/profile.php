<?php
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $email, $phone, $user_id])) {
            $_SESSION['full_name'] = $full_name;
            $message = "Profil mis à jour avec succès.";
            // Refresh user data
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['phone'] = $phone;
        } else {
            $error = "Erreur lors de la mise à jour.";
        }
    } elseif (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        
        if (password_verify($old_pass, $user['password'])) {
            if ($new_pass === $confirm_pass) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $user_id]);
                $message = "Mot de passe changé avec succès.";
            } else {
                $error = "Les nouveaux mots de passe ne correspondent pas.";
            }
        } else {
            $error = "L'ancien mot de passe est incorrect.";
        }
    }
}
?>

<div class="card" style="max-width: 800px; margin: 2rem auto;">
    <h1>Mon Profil</h1>
    
    <?php if ($message): ?><div class="alert alert-success" style="background: var(--success); color: white; padding: 10px; border-radius: 4px; margin-bottom: 1rem;"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" style="background: var(--danger); color: white; padding: 10px; border-radius: 4px; margin-bottom: 1rem;"><?php echo $error; ?></div><?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Informations personnelles -->
        <div class="card" style="border: 1px solid var(--border); box-shadow: none;">
            <h3>Informations Personnelles</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Nom Complet</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="form-control">
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary" style="margin-top: 1rem;">Mettre à jour</button>
            </form>
        </div>

        <!-- Sécurité -->
        <div class="card" style="border: 1px solid var(--border); box-shadow: none;">
            <h3>Sécurité</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Ancien mot de passe</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-secondary" style="margin-top: 1rem;">Changer le mot de passe</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
