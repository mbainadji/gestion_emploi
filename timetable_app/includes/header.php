<?php
require_once __DIR__ . '/config.php';

// Récupération des notifications non lues si connecté
$unread_count = 0;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_count = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Emploi du Temps</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
<nav>
    <div class="container">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <button class="back-button" id="backBtn" aria-label="Retour" title="Retour">←</button>
            <a href="<?php echo BASE_URL; ?>/index.php" class="brand">
                <span style="background: var(--primary); color: white; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 1.1rem;">T</span>
                Timetable
            </a>
        </div>
        
        <button class="nav-toggle" id="navToggle" aria-label="Ouvrir le menu">☰</button>
        
        <ul class="nav-links" id="navLinks">
            <?php if (isLoggedIn()): ?>
                <li><a href="<?php echo BASE_URL; ?>/modules/views/view.php">Planning</a></li>
                <?php if (hasRole('admin')): ?>
                    <li><a href="<?php echo BASE_URL; ?>/modules/academics/departments_view.php">Départements</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/scheduling/manage.php">Planification</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/scheduling/attendance.php">Émargement</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/reports/stats.php">Statistiques</a></li>
                <?php elseif (hasRole('teacher')): ?>
                    <li><a href="<?php echo BASE_URL; ?>/modules/scheduling/manage.php">Mes Cours</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/scheduling/teacher_schedule_request.php">Soumettre Planning</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/scheduling/attendance.php">Émargement</a></li>
                <?php endif; ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/modules/notifications/view.php" style="position: relative;">
                        Notifications
                        <?php if ($unread_count > 0): ?>
                            <span class="badge" style="background: var(--danger); color: white; margin-left: 0.3rem;"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="<?php echo BASE_URL; ?>/modules/accounts/profile.php">Mon Profil</a></li>
                <li><a href="<?php echo BASE_URL; ?>/modules/accounts/logout.php" class="btn btn-secondary" style="color: white; padding: 0.4rem 0.8rem;">Déconnexion</a></li>
            <?php else: ?>
                <li><a href="<?php echo BASE_URL; ?>/modules/accounts/login.php" class="btn btn-primary" style="color: white;">Connexion</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<script>
    document.getElementById('backBtn').addEventListener('click', () => {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = '<?php echo BASE_URL; ?>/index.php';
        }
    });

    document.getElementById('navToggle').addEventListener('click', () => {
        document.getElementById('navLinks').classList.toggle('active');
    });

    // Scroll Animation Observer
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.card, .link-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
        observer.observe(el);
    });
</script>

<div class="container content">
