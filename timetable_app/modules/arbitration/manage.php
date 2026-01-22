<?php
require_once __DIR__ . '/../../includes/config.php';
requireRole('admin');

if (isset($_POST['delete_pref'])) {
    $stmt = $pdo->prepare("DELETE FROM desiderata WHERE id = ?");
    $stmt->execute([$_POST['pref_id']]);
    $message = "Désidérata supprimé.";
}

$prefs = $pdo->query("SELECT d.*, t.name as teacher_name, s.day, s.start_time, s.end_time FROM desiderata d JOIN teachers t ON d.teacher_id = t.id JOIN slots s ON d.slot_id = s.id ORDER BY t.name")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Arbitrage des Désidératas Enseignants</h2>
    <?php if (isset($message)): ?><p style="color: green;"><?php echo $message; ?></p><?php endif; ?>

    <table>
        <thead><tr><th>Enseignant</th><th>Plage souhaitée</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach ($prefs as $p): ?>
                <tr>
                    <td><?php echo $p['teacher_name']; ?></td>
                    <td><?php echo $p['day']; ?> <?php echo $p['start_time']; ?>-<?php echo $p['end_time']; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="pref_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" name="delete_pref" class="btn btn-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
