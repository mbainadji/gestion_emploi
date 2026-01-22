<?php
require_once __DIR__ . '/../../includes/config.php';
requireRole('teacher');

// Get teacher ID for current user
$stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();
$teacher_id = $teacher['id'];

// Check if within deadline
$stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'preference_deadline'");
$stmt->execute();
$deadline = $stmt->fetchColumn();
$deadline_passed = (strtotime($deadline) < time());

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$deadline_passed) {
    // Clear old preferences for current semester (demo: semester 1)
    $stmt = $pdo->prepare("DELETE FROM desiderata WHERE teacher_id = ? AND semester_id = 1");
    $stmt->execute([$teacher_id]);

    if (isset($_POST['slots'])) {
        $stmt = $pdo->prepare("INSERT INTO desiderata (teacher_id, slot_id, semester_id) VALUES (?, ?, 1)");
        foreach ($_POST['slots'] as $slot_id) {
            $stmt->execute([$teacher_id, $slot_id]);
        }
    }
    $message = "Vos désidératas ont été enregistrés.";
}

$slots = $pdo->query("SELECT * FROM slots ORDER BY id")->fetchAll();
$current_prefs = $pdo->prepare("SELECT slot_id FROM desiderata WHERE teacher_id = ? AND semester_id = 1");
$current_prefs->execute([$teacher_id]);
$my_slots = $current_prefs->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Soumission des Désidératas</h2>
    <?php if (isset($message)): ?><p style="color: green;"><?php echo $message; ?></p><?php endif; ?>
    
    <p>Sélectionnez vos plages horaires souhaitées pour le Semestre 1.</p>
    
    <form method="POST">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
            <?php foreach ($slots as $slot): ?>
                <div style="border: 1px solid #ddd; padding: 10px;">
                    <label>
                        <input type="checkbox" name="slots[]" value="<?php echo $slot['id']; ?>" <?php echo in_array($slot['id'], $my_slots) ? 'checked' : ''; ?>>
                        <?php echo $slot['day']; ?> <?php echo $slot['start_time']; ?> - <?php echo $slot['end_time']; ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <br>
        <button type="submit" class="btn btn-primary" <?php echo $deadline_passed ? 'disabled' : ''; ?>>Enregistrer mes choix</button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
