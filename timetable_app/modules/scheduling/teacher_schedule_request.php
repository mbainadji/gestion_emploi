<?php
require_once __DIR__ . '/../../includes/config.php';
requireRole('teacher');

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get teacher ID
$stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$user_id]);
$teacher_id = $stmt->fetchColumn();

if (!$teacher_id) {
    die("Profil enseignant non trouvé.");
}

// Handle request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $course_id = $_POST['course_id'];
    $class_id = $_POST['class_id'];
    $slot_id = $_POST['slot_id'];
    $reason = $_POST['reason'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO catchup_requests (teacher_id, course_id, class_id, slot_id, room_id, reason, status, request_type, session_type) VALUES (?, ?, ?, ?, 1, ?, 'pending', 'proposal', 'Cours')");
        $stmt->execute([$teacher_id, $course_id, $class_id, $slot_id, $reason]);
        $message = "Votre demande d'emploi du temps a été envoyée à l'administrateur.";
        
        // Notify admin
        $admin_stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $admin_id = $admin_stmt->fetchColumn();
        if ($admin_id) {
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([
                $admin_id, 
                "Nouvelle demande d'emploi du temps de la part de " . $_SESSION['full_name']
            ]);
        }
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

$my_courses_full = $pdo->prepare("SELECT tc.course_id, tc.class_id, c.code, c.title, cl.name as class_name FROM teacher_courses tc JOIN courses c ON tc.course_id = c.id JOIN classes cl ON tc.class_id = cl.id WHERE tc.teacher_id = ?");
$my_courses_full->execute([$teacher_id]);
$courses_full = $my_courses_full->fetchAll();

$slots = $pdo->query("SELECT * FROM slots ORDER BY id")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Soumettre mon Emploi du Temps</h2>
    <p>Utilisez ce formulaire pour envoyer vos souhaits de programmation à l'administration.</p>

    <?php if ($message): ?><div class="alert alert-success" style="background: #d1e7dd; color: #0f5132; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" style="background: #f8d7da; color: #842029; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo $error; ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="submit_request" value="1">
        
        <div class="form-group">
            <label>Cours & Classe</label>
            <select name="course_index" class="form-control" required onchange="updateHiddenFields(this.value)">
                <option value="">-- Sélectionner --</option>
                <?php foreach ($courses_full as $idx => $c): ?>
                    <option value="<?php echo $idx; ?>">
                        <?php echo htmlspecialchars($c['code'] . ' - ' . $c['title'] . ' (' . $c['class_name'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="course_id" id="hidden_course_id">
            <input type="hidden" name="class_id" id="hidden_class_id">
        </div>

        <div class="form-group" style="margin-top: 1rem;">
            <label>Créneau souhaité</label>
            <select name="slot_id" class="form-control" required>
                <?php foreach ($slots as $s): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo $s['day'] . ' ' . $s['start_time'] . '-' . $s['end_time']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
            <label>Notes / Justification (Optionnel)</label>
            <textarea name="reason" class="form-control" rows="3" placeholder="Précisez vos contraintes ou besoins particuliers..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem; width: 100%;">Envoyer la demande à l'Admin</button>
    </form>
</div>

<script>
const coursesData = <?php echo json_encode($courses_full); ?>;
function updateHiddenFields(index) {
    if (index === "") {
        document.getElementById('hidden_course_id').value = "";
        document.getElementById('hidden_class_id').value = "";
        return;
    }
    const data = coursesData[index];
    document.getElementById('hidden_course_id').value = data.course_id;
    document.getElementById('hidden_class_id').value = data.class_id;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
