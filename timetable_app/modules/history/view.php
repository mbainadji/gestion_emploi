<?php
require_once __DIR__ . '/../../includes/config.php';
requireRole('admin');

$logs = $pdo->query("SELECT h.*, u.username FROM history h JOIN users u ON h.user_id = u.id ORDER BY h.timestamp DESC LIMIT 100")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Historique des modifications</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Table</th>
                <th>Ancienne Valeur</th>
                <th>Nouvelle Valeur</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo $log['timestamp']; ?></td>
                    <td><?php echo $log['username']; ?></td>
                    <td><?php echo $log['action']; ?></td>
                    <td><?php echo $log['table_name']; ?></td>
                    <td><?php echo $log['old_value']; ?></td>
                    <td><?php echo $log['new_value']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
