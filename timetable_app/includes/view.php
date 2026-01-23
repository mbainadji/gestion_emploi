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
}

// Récupération des notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY timestamp DESC LIMIT 50");
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
                        <button class="btn-mark-read" onclick="markAsRead(<?php echo $notif['id']; ?>)">Marquer comme lu</button>
                    <?php else: ?>
                        <span class="status-read">Lu</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
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
</script>

<?php require_once '../../includes/footer.php'; ?>