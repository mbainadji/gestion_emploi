<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Emploi du Temps</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
<nav>
    <div class="container">
        <a href="<?php echo BASE_URL; ?>/index.php" class="brand">Timetable Manager</a>
        <ul>
            <?php if (isLoggedIn()): ?>
                <li><a href="<?php echo BASE_URL; ?>/modules/views/view.php">Emploi du Temps</a></li>
                <?php if (hasRole('admin')): ?>
                    <li><a href="<?php echo BASE_URL; ?>/modules/academics/manage.php">Chef Dpt (Académique)</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/teachers/manage.php">Enseignants</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/rooms/manage.php">Salles</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/views/room_view.php">Salles View</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/arbitration/manage.php">Arbitrage</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/scheduling/manage.php">Planification</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/history/view.php">Historique</a></li>
                <?php elseif (hasRole('teacher')): ?>
                    <li><a href="<?php echo BASE_URL; ?>/modules/preferences/submit.php">Désidératas</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/teachers/schedule_extra.php">Programmer TD/TP</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/views/teacher_view.php">Mon Emploi du Temps</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>/modules/accounts/logout.php">Déconnexion (<?php echo $_SESSION['username']; ?>)</a></li>
            <?php else: ?>
                <li><a href="<?php echo BASE_URL; ?>/modules/accounts/login.php">Connexion</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<div class="container content">
