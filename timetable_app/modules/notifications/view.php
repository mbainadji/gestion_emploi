<?php
require_once '../../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Traitement AJAX pour marquer comme lu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $notif_id = (int)$_POST['id'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);
    echo json_encode(['success' => true]);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $notif_id = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);
    echo json_encode(['success' => true]);
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$total_notifs = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$total_notifs->execute([$user_id]);
$total_pages = ceil($total_notifs->fetchColumn() / $limit);

// Récupération des notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY timestamp DESC LIMIT $limit OFFSET $offset");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="card">
    <h1>Mes Notifications</h1>
    
    <?php if (empty($notifications)): ?>
        <p>Aucune notification pour le moment.</p>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>" id="notif-<?php echo $notif['id']; ?>">
                    <div class="notif-content">
                        <div class="notif-date"><?php echo date('d/m/Y H:i', strtotime($notif['timestamp'])); ?></div>
                        <div class="notif-message"><?php echo nl2br(htmlspecialchars($notif['message'])); ?></div>
                    </div>
                    <?php if (!$notif['is_read']): ?>
                        <div class="actions">
                            <button class="btn-mark-read" onclick="markAsRead(<?php echo $notif['id']; ?>)">Marquer comme lu</button>
                            <button class="btn-delete" onclick="deleteNotification(<?php echo $notif['id']; ?>)" title="Supprimer">&times;</button>
                        </div>
                    <?php else: ?>
                        <div class="actions">
                            <span class="status-read">Lu</span>
                            <button class="btn-delete" onclick="deleteNotification(<?php echo $notif['id']; ?>)" title="Supprimer">&times;</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div style="margin-top:20px; text-align:center;">
            <?php if ($page > 1): ?><a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">&laquo; Précédent</a><?php endif; ?>
            <?php if ($total_pages > 1): ?><span style="margin:0 10px;">Page <?php echo $page; ?> sur <?php echo $total_pages; ?></span><?php endif; ?>
            <?php if ($page < $total_pages): ?><a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Suivant &raquo;</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .notification-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.3s;
    }
    .notification-item.unread {
        background-color: #e8f4fd;
        border-left: 4px solid #007bff;
    }
    .notification-item.read {
        background-color: #fff;
        color: #666;
    }
    .notif-date {
        font-size: 0.85rem;
        color: #888;
        margin-bottom: 5px;
    }
    .btn-mark-read {
        background: none;
        border: 1px solid #007bff;
        color: #007bff;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
    }
    .btn-mark-read:hover {
        background: #007bff;
        color: white;
    }
    .status-read {
        color: #28a745;
        font-size: 0.9rem;
        font-style: italic;
    }
    .actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .btn-delete {
        background: none;
        border: none;
        color: #dc3545;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0 5px;
        line-height: 1;
    }
    .btn-delete:hover {
        color: #a71d2a;
    }
</style>

<script>
function markAsRead(id) {
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('id', id);

    fetch('view.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.getElementById('notif-' + id);
            item.classList.remove('unread');
            item.classList.add('read');
            
            // Remplacer le bouton par "Lu"
            const btn = item.querySelector('.btn-mark-read');
            if (btn) {
                const span = document.createElement('span');
                span.className = 'status-read';
                span.textContent = 'Lu';
                btn.parentNode.replaceChild(span, btn);
            }
            
            // Décrémenter le compteur du header (optionnel, nécessite de cibler l'élément badge)
            const badge = document.querySelector('.nav-links .badge');
            if (badge) {
                let count = parseInt(badge.textContent);
                if (count > 1) {
                    badge.textContent = count - 1;
                } else {
                    badge.remove();
                }
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function deleteNotification(id) {
    if (!confirm('Supprimer cette notification ?')) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    fetch('view.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.getElementById('notif-' + id);
            
            // Decrement badge if deleting an unread notification
            if (item.classList.contains('unread')) {
                const badge = document.querySelector('.nav-links .badge');
                if (badge) {
                    let count = parseInt(badge.textContent);
                    if (count > 1) {
                        badge.textContent = count - 1;
                    } else {
                        badge.remove();
                    }
                }
            }
            
            item.remove();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

<?php require_once '../../includes/footer.php'; ?>