<?php
require_once '../../includes/config.php';
requireRole('admin');

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filtres
$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user_id'] ?? '';

// Récupération du nombre total d'entrées
$count_sql = "SELECT COUNT(*) FROM history";
$params = [];
if ($action_filter) {
    $count_sql .= " WHERE action = ?";
    $params[] = $action_filter;
}
if ($user_filter) {
    $count_sql .= ($action_filter ? " AND" : " WHERE") . " user_id = ?";
    $params[] = $user_filter;
}
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_logs = $stmt->fetchColumn();
$total_pages = ceil($total_logs / $limit);

// Récupération des logs avec jointure sur users
$sql = "SELECT h.*, u.username, u.full_name 
    FROM history h 
    LEFT JOIN users u ON h.user_id = u.id";

if ($action_filter) {
    $sql .= " WHERE h.action = ?";
}
if ($user_filter) {
    $sql .= ($action_filter ? " AND" : " WHERE") . " h.user_id = ?";
}

$sql .= " ORDER BY h.timestamp DESC LIMIT ? OFFSET ?";
// Add limit/offset to params array. Note: PDO execute array treats all as strings usually, 
// but for LIMIT/OFFSET in some drivers/modes we need bindValue. Let's use bindValue loop.

$stmt = $pdo->prepare($sql);
$bind_index = 1;
if ($action_filter) {
    $stmt->bindValue($bind_index++, $action_filter);
}
if ($user_filter) {
    $stmt->bindValue($bind_index++, $user_filter);
}
$stmt->bindValue($bind_index++, $limit, PDO::PARAM_INT);
$stmt->bindValue($bind_index++, $offset, PDO::PARAM_INT);

$stmt->execute();
$logs = $stmt->fetchAll();

// Récupération des types d'actions pour le select
$actions = $pdo->query("SELECT DISTINCT action FROM history ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

// Récupération des utilisateurs pour le select
$users = $pdo->query("SELECT DISTINCT u.id, u.username FROM users u JOIN history h ON u.id = h.user_id ORDER BY u.username")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="card">
    <h1>Historique des Actions</h1>
    <p>Traçabilité des modifications effectuées sur la plateforme.</p>

    <form method="GET" style="margin-bottom: 20px; background: #f8f9fa; padding: 10px; border-radius: 4px; display: flex; gap: 10px; align-items: center;">
        <label for="action">Filtrer par action :</label>
        <select name="action" id="action" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
            <option value="">-- Toutes les actions --</option>
            <?php foreach ($actions as $act): ?>
                <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $action_filter === $act ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($act); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="user_id" style="margin-left: 10px;">Filtrer par utilisateur :</label>
        <select name="user_id" id="user_id" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
            <option value="">-- Tous les utilisateurs --</option>
            <?php foreach ($users as $usr): ?>
                <option value="<?php echo $usr['id']; ?>" <?php echo $user_filter == $usr['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($usr['username']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($action_filter || $user_filter): ?>
            <a href="view.php" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9rem;">Réinitialiser</a>
        <?php endif; ?>
    </form>

    <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
        <thead>
            <tr style="background:#f8f9fa; text-align:left;">
                <th style="padding:10px; border-bottom:2px solid #dee2e6;">Date</th>
                <th style="padding:10px; border-bottom:2px solid #dee2e6;">Utilisateur</th>
                <th style="padding:10px; border-bottom:2px solid #dee2e6;">Action</th>
                <th style="padding:10px; border-bottom:2px solid #dee2e6;">Cible</th>
                <th style="padding:10px; border-bottom:2px solid #dee2e6;">Détails</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td style="padding:10px; border-bottom:1px solid #dee2e6; white-space:nowrap;">
                    <?php echo date('d/m/Y H:i', strtotime($log['timestamp'])); ?>
                </td>
                <td style="padding:10px; border-bottom:1px solid #dee2e6;">
                    <strong><?php echo htmlspecialchars($log['username'] ?? 'Système'); ?></strong>
                    <br><span style="color:#666; font-size:0.8em;"><?php echo htmlspecialchars($log['full_name'] ?? ''); ?></span>
                </td>
                <td style="padding:10px; border-bottom:1px solid #dee2e6;">
                    <span class="badge badge-<?php echo strtolower($log['action']); ?>" style="font-weight:bold;">
                        <?php echo htmlspecialchars($log['action']); ?>
                    </span>
                </td>
                <td style="padding:10px; border-bottom:1px solid #dee2e6;">
                    <?php echo htmlspecialchars($log['table_name']); ?> #<?php echo $log['record_id']; ?>
                </td>
                <td style="padding:10px; border-bottom:1px solid #dee2e6; color:#555;">
                    <?php 
                        // Affichage intelligent des valeurs old/new si présentes
                        if ($log['new_value']) echo htmlspecialchars($log['new_value']);
                        elseif ($log['old_value']) echo "Ancienne valeur : " . htmlspecialchars($log['old_value']);
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination simple -->
    <div style="margin-top:20px; text-align:center;">
        <?php 
            $query_params = $_GET;
            $query_params['page'] = $page - 1;
            $prev_link = '?' . http_build_query($query_params);
            $query_params['page'] = $page + 1;
            $next_link = '?' . http_build_query($query_params);
        ?>
        <?php if ($page > 1): ?><a href="<?php echo $prev_link; ?>" class="btn btn-secondary">&laquo; Précédent</a><?php endif; ?>
        <span style="margin:0 10px;">Page <?php echo $page; ?> sur <?php echo $total_pages; ?></span>
        <?php if ($page < $total_pages): ?><a href="<?php echo $next_link; ?>" class="btn btn-secondary">Suivant &raquo;</a><?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>