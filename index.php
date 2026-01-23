<?php
// Use shared header/footer for consistent design
require_once __DIR__ . '/timetable_app/includes/header.php';
?>

<div class="hero" style="margin-top:1.5rem;">
    <h1>Gestion de l'Emploi du Temps Universitaire</h1>
    <p>Système optimal de planification pour ICT203 2025/2026</p>
    <?php if (isLoggedIn()): ?>
        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="cta-button">Accéder au Tableau de Bord</a>
    <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/modules/accounts/login.php" class="cta-button">Commencer maintenant</a>
    <?php endif; ?>
</div>

<section class="card">
    <h2>Accueil</h2>
    <p>Bienvenue — cette application permet de gérer les emplois du temps, les salles, les enseignants et les préférences. Accédez rapidement aux sections ci‑dessous.</p>
    <div class="links-grid">
        <div class="link-card">
            <h3>Connexion / Inscription</h3>
            <p>Se connecter ou créer un compte pour accéder au tableau de bord.</p>
            <a href="<?php echo BASE_URL; ?>/modules/accounts/login.php">Se connecter</a>
        </div>
        <div class="link-card">
            <h3>Voir Emploi du Temps</h3>
            <p>Consulter les emplois du temps par classe, enseignant ou salle.</p>
            <a href="<?php echo BASE_URL; ?>/modules/views/view.php">Consulter</a>
        </div>
        <div class="link-card">
            <h3>Gestion Académique</h3>
            <p>Paramétrer années, semestres, filières et classes (admin).</p>
            <a href="<?php echo BASE_URL; ?>/modules/academics/manage.php">Gérer</a>
        </div>
        <div class="link-card">
            <h3>Enseignants</h3>
            <p>Ajouter ou modifier les enseignants et leurs affectations.</p>
            <a href="<?php echo BASE_URL; ?>/modules/teachers/manage.php">Enseignants</a>
        </div>
        <div class="link-card">
            <h3>Salles</h3>
            <p>Gérer les salles (capacités, types) et leur disponibilité.</p>
            <a href="<?php echo BASE_URL; ?>/modules/rooms/index.php">Salles</a>
        </div>
        <div class="link-card">
            <h3>Préférences</h3>
            <p>Soumettre ou consulter les désidératas des enseignants.</p>
            <a href="<?php echo BASE_URL; ?>/modules/preferences/submit.php">Préférences</a>
        </div>
        <div class="link-card">
            <h3>Arbitrage</h3>
            <p>Arbitrer les conflits et ajuster les plannings (admin).</p>
            <a href="<?php echo BASE_URL; ?>/modules/arbitration/manage.php">Arbitrage</a>
        </div>
        <div class="link-card">
            <h3>Documentation</h3>
            <p>Lire la documentation du projet et les notes d'installation.</p>
            <a href="<?php echo BASE_URL; ?>/documentation.md">Documentation</a>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/timetable_app/includes/footer.php';
?>
