<?php
require_once '../../includes/config.php';
requireRole('admin');

$message = '';

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);
    $capacity = (int)$_POST['capacity'];

    if (!empty($name) && $capacity > 0) {
        $stmt = $pdo->prepare("INSERT INTO rooms (name, capacity) VALUES (?, ?)");
        if ($stmt->execute([$name, $capacity])) {
            $message = "Salle ajoutée avec succès !";
            logHistory($_SESSION['user_id'], 'CREATE', 'rooms', $pdo->lastInsertId(), null, "Ajout salle $name");
        } else {
            $message = "Erreur lors de l'ajout.";
        }
    } else {
        $message = "Veuillez remplir correctement tous les champs.";
    }
}

// Traitement de la suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Vérifier si la salle est utilisée
    $check = $pdo->prepare("SELECT COUNT(*) FROM timetable WHERE room_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        $message = "Impossible de supprimer cette salle car elle est utilisée dans un emploi du temps.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Salle supprimée.";
        logHistory($_SESSION['user_id'], 'DELETE', 'rooms', $id, null, "Suppression salle ID $id");
    }
}

// Récupération de la liste des salles
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Salles</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        h1 { margin-top: 0; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .btn-back { display: inline-block; margin-bottom: 15px; color: #666; text-decoration: none; }
        .btn-back:hover { text-decoration: underline; }
        
        .form-inline { display: flex; gap: 10px; background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; align-items: flex-end; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { padding: 9px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background-color: #218838; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f8f9fa; color: #555; font-weight: 600; }
        tr:hover { background-color: #f1f1f1; }
        .actions a { color: #dc3545; text-decoration: none; font-size: 0.9rem; margin-left: 10px; }
        .actions a:hover { text-decoration: underline; }
        .alert { padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn-back">&larr; Retour au Tableau de Bord</a>
        
        <h1>Gestion des Salles</h1>

        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Formulaire d'ajout -->
        <form method="POST" action="" class="form-inline">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="name">Nom de la salle</label>
                <input type="text" id="name" name="name" placeholder="Ex: S003, Amphi 100" required>
            </div>
            <div class="form-group">
                <label for="capacity">Capacité (places)</label>
                <input type="number" id="capacity" name="capacity" placeholder="Ex: 50" required min="1">
            </div>
            <button type="submit">Ajouter</button>
        </form>

        <!-- Liste des salles -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Capacité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><?php echo $room['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($room['name']); ?></strong></td>
                    <td><?php echo $room['capacity']; ?> places</td>
                    <td class="actions">
                        <!-- Lien de suppression avec confirmation JS -->
                        <a href="?delete=<?php echo $room['id']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette salle ?');">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>