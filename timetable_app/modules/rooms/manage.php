<?php
require_once __DIR__ . '/../../includes/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO rooms (name, capacity, is_predefined) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['capacity'], $_POST['is_predefined'] ?? 0]);
    redirect('/modules/rooms/manage.php');
}

$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Gestion des Salles</h2>
    <table>
        <thead><tr><th>Nom</th><th>Capacité</th><th>Prédéfinie?</th></tr></thead>
        <tbody>
            <?php foreach ($rooms as $r): ?>
                <tr>
                    <td><?php echo $r['name']; ?></td>
                    <td><?php echo $r['capacity']; ?></td>
                    <td><?php echo $r['is_predefined'] ? 'Oui' : 'Non'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Ajouter une Salle</h3>
    <form method="POST">
        <div><label>Nom de la salle</label><input type="text" name="name" required></div>
        <div><label>Capacité</label><input type="number" name="capacity" required></div>
        <div>
            <label><input type="checkbox" name="is_predefined" value="1" checked> Salle prédéfinie</label>
        </div>
        <button type="submit" class="btn btn-success">Ajouter</button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
