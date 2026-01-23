<?php
// Use shared header/footer for consistent design inside timetable_app
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero" style="margin-top:1.5rem;">
    <h1>Gestion de l'Emploi du Temps Universitaire</h1>
    <p>Système optimal de planification pour ICT203 2025/2026</p>
    <?php if (isLoggedIn()): ?>
        <a href="<?php echo BASE_URL; ?>/index1.php" class="cta-button">Accéder au Tableau de Bord</a>
    <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/modules/accounts/login.php" class="cta-button">Commencer maintenant</a>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
