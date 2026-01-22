<?php
require_once __DIR__ . '/../../includes/config.php';

// Page that shows both login and register forms and posts to the existing handlers
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width:900px;margin:2rem auto;display:flex;gap:2rem;flex-wrap:wrap;">
    <div style="flex:1;min-width:300px;">
        <h2>Connexion</h2>
        <form method="POST" action="login.php">
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

    <div style="flex:1;min-width:300px;">
        <h2>Inscription</h2>
        <form method="POST" action="register.php">
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
