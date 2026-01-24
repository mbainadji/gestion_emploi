<?php
// Use shared header/footer for consistent design inside timetable_app
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero">
    <h1>Planification Universitaire Intelligente</h1>
    <p>Une solution moderne et intuitive pour la gestion des emplois du temps, le suivi des présences et l'optimisation des ressources académiques.</p>
    <?php if (isLoggedIn()): ?>
        <a href="<?php echo BASE_URL; ?>/index1.php" class="cta-button">Aller au Tableau de Bord</a>
    <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/modules/accounts/login.php" class="cta-button">Commencer maintenant</a>
    <?php endif; ?>
</div>

<div class="links-grid" style="margin-top: -5rem;">
    <div class="link-card">
        <div style="font-size: 2.5rem; margin-bottom: 1rem;">📅</div>
        <h3>Planning Dynamique</h3>
        <p>Visualisation claire et précise des cours par jour, semaine ou mois avec détection de conflits.</p>
    </div>
    <div class="link-card">
        <div style="font-size: 2.5rem; margin-bottom: 1rem;">📊</div>
        <h3>Analyses Avancées</h3>
        <p>Suivez la charge de travail des enseignants et l'occupation des salles en temps réel.</p>
    </div>
    <div class="link-card">
        <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔔</div>
        <h3>Notifications</h3>
        <p>Restez informé des changements d'emploi du temps et des sessions de rattrapage par notifications.</p>
    </div>
</div>

<div class="card" style="margin-top: 4rem; text-align: center; padding: 4rem 2rem;">
    <h2 style="font-size: 2rem; margin-bottom: 1rem;">Prêt à optimiser votre établissement ?</h2>
    <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto 2rem;">Rejoignez les dizaines d'enseignants qui utilisent déjà notre plateforme pour simplifier leur quotidien académique.</p>
    <?php if (!isLoggedIn()): ?>
        <a href="<?php echo BASE_URL; ?>/modules/accounts/login.php" class="btn btn-primary btn-lg" style="padding: 1rem 3rem; font-size: 1.1rem;">Se connecter</a>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
