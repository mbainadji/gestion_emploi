<?php
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h1>Bienvenue sur l'application de Gestion d'Emploi du Temps</h1>
    <p>Cette plateforme permet la gestion optimisée des emplois du temps de l'établissement.</p>
    
    <?php if (!isLoggedIn()): ?>
        <p>Veuillez vous <a href="<?php echo BASE_URL; ?>/modules/accounts/login.php">connecter</a> pour accéder aux fonctionnalités.</p>
    <?php else: ?>
        <p>Bonjour, <strong><?php echo $_SESSION['full_name']; ?></strong>. Vous êtes connecté en tant que <strong><?php echo $_SESSION['role']; ?></strong>.</p>
        <div class="dashboard-links">
            <?php if (hasRole('admin')): ?>
                <a href="<?php echo BASE_URL; ?>/modules/academics/departments_view.php" class="btn btn-primary">Départements & Filières</a>
                <a href="<?php echo BASE_URL; ?>/modules/scheduling/manage.php" class="btn btn-success">Gérer les Emplois du Temps</a>
                <a href="<?php echo BASE_URL; ?>/modules/scheduling/catchup.php" class="btn btn-secondary" style="background: #f59e0b;">Gérer les Rattrapages</a>
                <a href="<?php echo BASE_URL; ?>/modules/reports/stats.php" class="btn btn-secondary" style="background: #10b981;">Statistiques & Rapports</a>
                <a href="<?php echo BASE_URL; ?>/modules/academics/manage.php" class="btn btn-secondary">Paramètres Académiques</a>
            <?php elseif (hasRole('teacher')): ?>
                <a href="<?php echo BASE_URL; ?>/modules/views/view.php" class="btn btn-primary">Mon Emploi du Temps</a>
                <a href="<?php echo BASE_URL; ?>/modules/scheduling/manage.php" class="btn btn-success">Modifier mes Cours</a>
                <a href="<?php echo BASE_URL; ?>/modules/scheduling/catchup.php" class="btn btn-secondary" style="background: #f59e0b;">Demander un Rattrapage</a>
                <a href="<?php echo BASE_URL; ?>/modules/preferences/submit.php" class="btn btn-secondary">Soumettre mes Désidératas</a>
            <?php elseif (hasRole('student')): ?>
                <a href="<?php echo BASE_URL; ?>/modules/views/view.php" class="btn btn-primary">Voir Mon Emploi du Temps</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
