<?php
// Root index.php - Entry point
require_once __DIR__ . '/timetable_app/includes/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail - Gestion d'Emploi du Temps</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        .hero {
            background: linear-gradient(rgba(0, 105, 92, 0.8), rgba(0, 105, 92, 0.8)), url('timetable_app/assets/images/university.jpg');
            background-size: cover;
            color: white;
            padding: 100px 20px;
            text-align: center;
        }
        .hero h1 { font-size: 3em; margin-bottom: 20px; }
        .hero p { font-size: 1.2em; margin-bottom: 30px; }
        .cta-button {
            background: #26a69a;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.1em;
            transition: 0.3s;
        }
        .cta-button:hover { background: #00796b; }
    </style>
</head>
<body>
  <nav style="background: #00695c; padding: 1em; text-align: center;">
    <a href="<?php echo BASE_URL; ?>/index.php" style="color: white; margin: 0 15px; text-decoration: none; font-weight: bold;">Application</a>
    <a href="index3.html" style="color: white; margin: 0 15px; text-decoration: none; font-weight: bold;">Cours</a>
    <?php if (isLoggedIn()): ?>
        <a href="<?php echo BASE_URL; ?>/modules/accounts/logout.php" style="color: white; margin: 0 15px; text-decoration: none; font-weight: bold;">Déconnexion</a>
    <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/modules/accounts/login.php" style="color: white; margin: 0 15px; text-decoration: none; font-weight: bold;">Connexion</a>
    <?php endif; ?>
  </nav>

  <div class="hero">
      <h1>Gestion de l'Emploi du Temps Universitaire</h1>
      <p>Système optimal de planification pour ICT203 2025/2026</p>
      
      <?php if (isLoggedIn()): ?>
          <a href="<?php echo BASE_URL; ?>/index1.php" class="cta-button">Accéder au Tableau de Bord</a>
      <?php else: ?>
          <a href="<?php echo BASE_URL; ?>/modules/accounts/login.php" class="cta-button">Commencer maintenant</a>
      <?php endif; ?>
  </div>

  <footer style="background: #2c3e50; color: white; text-align: center; padding: 2em 0; position: fixed; bottom: 0; width: 100%;">
      <p>&copy; 2025/2026 - Réalisé par le Groupe ICT 203</p>
  </footer>
</body>
</html>
