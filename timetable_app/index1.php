<?php
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="text-align: center; margin-bottom: 3rem; padding: 3rem;">
    <h1>Tableau de Bord</h1>
    <p style="font-size: 1.1rem; color: var(--text-muted);">Bienvenue, <strong><?php echo $_SESSION['full_name']; ?></strong>. Gérez vos activités académiques en toute simplicité.</p>
</div>

<div class="links-grid">
    <?php if (hasRole('admin')): ?>
        <div class="link-card">
            <h3>Structure</h3>
            <p>Gérez les départements, filières et programmes de l'établissement.</p>
            <a href="<?php echo BASE_URL; ?>/modules/academics/departments_view.php" class="btn btn-primary">Départements</a>
        </div>
        <div class="link-card">
            <h3>Planification</h3>
            <p>Organisez les cours et gérez les conflits d'emploi du temps.</p>
            <a href="<?php echo BASE_URL; ?>/modules/scheduling/manage.php" class="btn btn-success">Planning</a>
        </div>
        <div class="link-card">
            <h3>Rattrapages</h3>
            <p>Validez et suivez les demandes de sessions de rattrapage.</p>
            <a href="<?php echo BASE_URL; ?>/modules/scheduling/catchup.php" class="btn btn-secondary" style="background: var(--warning);">Rattrapages</a>
        </div>
        <div class="link-card">
            <h3>Analyses</h3>
            <p>Consultez les statistiques d'occupation et les rapports d'assiduité.</p>
            <a href="<?php echo BASE_URL; ?>/modules/reports/stats.php" class="btn btn-secondary" style="background: var(--success);">Statistiques</a>
        </div>
        <div class="link-card">
            <h3>Communication</h3>
            <p>Envoyez des annonces et convocations aux enseignants en temps réel.</p>
            <a href="<?php echo BASE_URL; ?>/modules/notifications/send.php" class="btn btn-primary">Envoyer une annonce</a>
        </div>
    <?php elseif (hasRole('teacher')): ?>
        <div class="link-card">
            <h3>Communication</h3>
            <p>Envoyez des messages importants à vos étudiants.</p>
            <a href="<?php echo BASE_URL; ?>/modules/notifications/send.php" class="btn btn-primary">Notifier mes étudiants</a>
        </div>
        <div class="link-card">
            <h3>Mon Planning</h3>
            <p>Consultez votre emploi du temps hebdomadaire mis à jour.</p>
            <a href="<?php echo BASE_URL; ?>/modules/views/view.php" class="btn btn-primary">Voir Planning</a>
        </div>
        <div class="link-card">
            <h3>Gestion Cours</h3>
            <p>Ajoutez ou modifiez vos créneaux de cours autorisés.</p>
            <a href="<?php echo BASE_URL; ?>/modules/scheduling/manage.php" class="btn btn-success">Mes Cours</a>
        </div>
        <div class="link-card">
            <h3>Rattrapage</h3>
            <p>Formulez une demande de rattrapage en cas d'absence.</p>
            <a href="<?php echo BASE_URL; ?>/modules/scheduling/catchup.php" class="btn btn-secondary" style="background: var(--warning);">Demander</a>
        </div>
        <div class="link-card">
            <h3>Disponibilités</h3>
            <p>Soumettez vos préférences de créneaux et vos contraintes.</p>
            <a href="<?php echo BASE_URL; ?>/modules/preferences/submit.php" class="btn btn-secondary">Désidératas</a>
        </div>
    <?php elseif (hasRole('student')): ?>
        <div class="link-card">
            <h3>Emploi du Temps</h3>
            <p>Consultez votre planning de cours et les changements récents.</p>
            <a href="<?php echo BASE_URL; ?>/modules/views/view.php" class="btn btn-primary">Voir Mon Planning</a>
        </div>
        <div class="link-card">
            <h3>Profil</h3>
            <p>Mettez à jour vos informations et changez votre mot de passe.</p>
            <a href="<?php echo BASE_URL; ?>/modules/accounts/profile.php" class="btn btn-secondary">Mon Profil</a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
