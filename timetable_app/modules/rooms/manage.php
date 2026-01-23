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

require_once '../../includes/header.php';
?>

<div class="card">
    <h1>Gestion des Salles</h1>

    <?php if ($message): ?>
        <div class="alert" style="padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire d'ajout -->
    <form method="POST" action="" style="display: flex; gap: 10px; background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; align-items: flex-end;">
        <input type="hidden" name="action" value="add">
        <div style="flex: 1;">
            <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Nom de la salle</label>
            <input type="text" name="name" placeholder="Ex: S003, Amphi 100" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
        </div>
        <div style="flex: 1;">
            <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem;">Capacité (places)</label>
            <input type="number" name="capacity" placeholder="Ex: 50" required min="1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
        </div>
        <button type="submit" class="btn btn-success" style="padding: 9px 15px;">Ajouter</button>
    </form>

    <!-- Liste des salles -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #eee;">ID</th>
                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #eee;">Nom</th>
                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #eee;">Capacité</th>
                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #eee;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
            <tr>
                <td style="padding: 12px; border-bottom: 1px solid #eee;"><?php echo $room['id']; ?></td>
                <td style="padding: 12px; border-bottom: 1px solid #eee;"><strong><?php echo htmlspecialchars($room['name']); ?></strong></td>
                <td style="padding: 12px; border-bottom: 1px solid #eee;"><?php echo $room['capacity']; ?> places</td>
                <td style="padding: 12px; border-bottom: 1px solid #eee;">
                    <a href="?delete=<?php echo $room['id']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette salle ?');" style="color: #dc3545; text-decoration: none; font-size: 0.9rem;">Supprimer</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
require_once '../../includes/footer.php';
?>
