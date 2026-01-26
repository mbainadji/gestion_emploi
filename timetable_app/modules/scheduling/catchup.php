<?php
require_once __DIR__ . '/../../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_catchup'])) {
        $teacher_id = $_POST['teacher_id'];
        $course_id = $_POST['course_id'];
        $class_id = $_POST['class_id'];
        $slot_id = $_POST['slot_id'];
        $room_id = $_POST['room_id'];
        $reason = $_POST['reason'];
        $type = $_POST['type'] ?? 'Rattrapage';
        $request_type = $_POST['request_type'] ?? 'catchup';

        // Check for room conflict
        $stmt = $pdo->prepare("SELECT * FROM timetable WHERE slot_id = ? AND room_id = ? AND semester_id = 1");
        $stmt->execute([$slot_id, $room_id]);
        $conflict = $stmt->fetch();

        if ($conflict) {
            $error = "La salle est déjà occupée sur ce créneau.";
            $show_quick_add = true;
        } else {
            // If teacher, they can directly schedule TD/TP for their courses if admin allowed (not specified, but keeping existing logic)
            // But if it's a "proposal" or "catchup" explicitly marked as such, we use the requests table
            if ($role === 'teacher' && ($type === 'TD' || $type === 'TP') && $request_type === 'catchup') {
                $stmt = $pdo->prepare("INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, type) VALUES (?, ?, ?, ?, ?, 1, ?)");
                $stmt->execute([$class_id, $course_id, $teacher_id, $room_id, $slot_id, $type]);
                $message = "Session de $type programmée avec succès.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO catchup_requests (teacher_id, course_id, class_id, slot_id, room_id, reason, request_type, session_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$teacher_id, $course_id, $class_id, $slot_id, $room_id, $reason, $request_type, $type]);
                $message = ($request_type === 'proposal' ? "Proposition d'emploi du temps" : "Demande de rattrapage") . " envoyée à l'administrateur.";
            }
        }
    } elseif (isset($_POST['quick_add_room'])) {
        $new_room_name = trim($_POST['new_room_name']);
        $new_room_cap = (int)$_POST['new_room_cap'];
        if (!empty($new_room_name) && $new_room_cap > 0) {
            $stmt = $pdo->prepare("INSERT INTO rooms (name, capacity) VALUES (?, ?)");
            $stmt->execute([$new_room_name, $new_room_cap]);
            $message = "Nouvelle salle '$new_room_name' ajoutée avec succès. Vous pouvez maintenant la sélectionner.";
        }
    }
}

if ($role === 'admin') {
    if (isset($_POST['approve_id'])) {
        $id = $_POST['approve_id'];
        $stmt = $pdo->prepare("SELECT * FROM catchup_requests WHERE id = ?");
        $stmt->execute([$id]);
        $req = $stmt->fetch();

        if ($req && $req['status'] === 'pending') {
            // Move to timetable
            $stmt = $pdo->prepare("INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, type) VALUES (?, ?, ?, ?, ?, 1, ?)");
            $stmt->execute([$req['class_id'], $req['course_id'], $req['teacher_id'], $req['room_id'], $req['slot_id'], $req['session_type'] ?? 'Cours']);
            
            $stmt = $pdo->prepare("UPDATE catchup_requests SET status = 'approved' WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Demande approuvée et ajoutée à l'emploi du temps.";

            // Notify Teacher and Students
            $notif_msg = "Votre demande (" . (($req['request_type'] ?? 'catchup') === 'proposal' ? 'Proposition' : 'Rattrapage') . ") pour " . $req['class_id'] . " a été approuvée.";
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES ((SELECT user_id FROM teachers WHERE id = ?), ?)")->execute([$req['teacher_id'], $notif_msg]);

            $students_stmt = $pdo->prepare("SELECT user_id FROM students WHERE class_id = ?");
            $students_stmt->execute([$req['class_id']]);
            $students = $students_stmt->fetchAll();
            foreach ($students as $student) {
                $student_msg = "Changement d'emploi du temps pour votre classe (" . ($req['session_type'] ?? 'Cours') . "). Consultez votre planning.";
                $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$student['user_id'], $student_msg]);
            }
        }
    } elseif (isset($_POST['reject_id'])) {
        $id = $_POST['reject_id'];
        $stmt = $pdo->prepare("UPDATE catchup_requests SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Demande rejetée.";
        
        // Notify Teacher
        $stmt = $pdo->prepare("SELECT teacher_id, request_type FROM catchup_requests WHERE id = ?");
        $stmt->execute([$id]);
        $req = $stmt->fetch();
        if ($req) {
            $notif_msg = "Votre " . (($req['request_type'] ?? 'catchup') === 'proposal' ? 'proposition' : 'demande de rattrapage') . " a été refusée.";
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES ((SELECT user_id FROM teachers WHERE id = ?), ?)")->execute([$req['teacher_id'], $notif_msg]);
        }
    }
}

// Fetch requests
if ($role === 'admin') {
    $requests = $pdo->query("SELECT r.*, te.name as teacher_name, co.title as course_title, cl.name as class_name, sl.day, sl.start_time 
                            FROM catchup_requests r 
                            JOIN teachers te ON r.teacher_id = te.id 
                            JOIN courses co ON r.course_id = co.id 
                            JOIN classes cl ON r.class_id = cl.id 
                            JOIN slots sl ON r.slot_id = sl.id 
                            ORDER BY r.created_at DESC")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT r.*, te.name as teacher_name, co.title as course_title, cl.name as class_name, sl.day, sl.start_time 
                          FROM catchup_requests r 
                          JOIN teachers te ON r.teacher_id = te.id 
                          JOIN courses co ON r.course_id = co.id 
                          JOIN classes cl ON r.class_id = cl.id 
                          JOIN slots sl ON r.slot_id = sl.id 
                          WHERE te.user_id = ? 
                          ORDER BY r.created_at DESC");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll();
}

$courses = $pdo->query("SELECT * FROM courses")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes")->fetchAll();
$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll();
$slots = $pdo->query("SELECT * FROM slots")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Gestion des Propositions & Rattrapages</h2>
    <?php if (isset($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>

    <?php if ($role === 'teacher'): ?>
    <div class="card" style="border: 1px solid var(--border); margin-bottom: 2rem;">
        <h3>Soumettre une Programmation</h3>
        <?php if (isset($error)): ?><div class="alert alert-danger" style="color: red; margin-bottom: 1rem;"><?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST">
            <?php 
            $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $tid = $stmt->fetchColumn();
            ?>
            <input type="hidden" name="teacher_id" value="<?php echo $tid; ?>">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Type de demande</label>
                    <select name="request_type" class="form-control" required>
                        <option value="proposal">Proposition d'emploi du temps (Cours Normal)</option>
                        <option value="catchup">Session de Rattrapage</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nature du cours</label>
                    <select name="type" class="form-control" required>
                        <option value="Cours">Cours Magistral (CM)</option>
                        <option value="TD">Travaux Dirigés (TD)</option>
                        <option value="TP">Travaux Pratiques (TP)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>UE</label>
                    <input list="course_list" id="course_search" class="form-control" placeholder="Rechercher une UE..." required>
                    <datalist id="course_list">
                        <?php foreach($courses as $c) echo "<option value='".htmlspecialchars($c['code'] . " - " . $c['title'])."' data-id='{$c['id']}'>"; ?>
                    </datalist>
                    <input type="hidden" name="course_id" id="course_id_hidden">
                </div>
                <div class="form-group">
                    <label>Classe</label>
                    <input list="class_list" id="class_search" class="form-control" placeholder="Rechercher une classe..." required>
                    <datalist id="class_list">
                        <?php foreach($classes as $c) echo "<option value='".htmlspecialchars($c['name'])."' data-id='{$c['id']}'>"; ?>
                    </datalist>
                    <input type="hidden" name="class_id" id="class_id_hidden">
                </div>
                <div class="form-group">
                    <label>Créneau souhaité</label>
                    <select name="slot_id" class="form-control" required>
                        <?php foreach($slots as $s) echo "<option value='{$s['id']}'>{$s['day']} {$s['start_time']}</option>"; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Salle</label>
                    <input list="rooms_list" id="room_search" class="form-control" placeholder="Rechercher une salle..." required>
                    <datalist id="rooms_list">
                        <?php foreach($rooms as $r) echo "<option value='".htmlspecialchars($r['name'])."' data-id='{$r['id']}'>"; ?>
                    </datalist>
                    <input type="hidden" name="room_id" id="room_id_hidden">
                </div>
            </div>
            <div class="form-group" style="margin-top: 1rem;">
                <label>Motif</label>
                <textarea name="reason" class="form-control" rows="2" required></textarea>
            </div>
            <button type="submit" name="request_catchup" class="btn btn-primary" style="margin-top: 1rem;">Envoyer la demande</button>
        </form>

        <?php if (isset($show_quick_add) && $show_quick_add): ?>
        <hr style="margin: 2rem 0;">
        <h4>Résoudre un conflit de salle ?</h4>
        <p style="font-size: 0.9rem; color: #666;">Si aucune salle n'est disponible, vous pouvez en ajouter une rapidement (soumis à validation administrative ultérieure).</p>
        <form method="POST" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div class="form-group">
                <label>Nom de la salle</label>
                <input type="text" name="new_room_name" class="form-control" placeholder="Ex: Salle de réunion" required>
            </div>
            <div class="form-group">
                <label>Capacité</label>
                <input type="number" name="new_room_cap" class="form-control" value="30" required>
            </div>
            <button type="submit" name="quick_add_room" class="btn btn-secondary">Ajouter Salle</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <h3>Demandes en attente</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Type</th>
                <th>Enseignant</th>
                <th>Cours</th>
                <th>Classe</th>
                <th>Créneau</th>
                <th>Statut</th>
                <?php if ($role === 'admin'): ?><th>Action</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($requests as $r): ?>
                <tr>
                    <td>
                        <span class="badge" style="background: <?php echo ($r['request_type'] ?? 'catchup') === 'proposal' ? '#007bff' : '#6f42c1'; ?>; color: white;">
                            <?php echo ($r['request_type'] ?? 'catchup') === 'proposal' ? 'Proposition' : 'Rattrapage'; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($r['teacher_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['course_title']); ?></td>
                    <td><?php echo htmlspecialchars($r['class_name']); ?></td>
                    <td><?php echo $r['day'] . ' ' . $r['start_time']; ?></td>
                    <td>
                        <span class="badge" style="background: <?php echo $r['status'] === 'approved' ? 'var(--success)' : ($r['status'] === 'pending' ? 'var(--secondary)' : 'var(--danger)'); ?>; color: white;">
                            <?php echo $r['status']; ?>
                        </span>
                    </td>
                    <?php if ($role === 'admin' && $r['status'] === 'pending'): ?>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <form method="POST">
                                    <input type="hidden" name="approve_id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Approuver</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="reject_id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Refuser cette demande ?')">Rejeter</button>
                                </form>
                            </div>
                        </td>
                    <?php elseif ($role === 'admin'): ?>
                        <td>-</td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function syncDatalist(inputId, listId, hiddenId) {
    const input = document.getElementById(inputId);
    const list = document.getElementById(listId);
    const hidden = document.getElementById(hiddenId);

    if (!input || !list || !hidden) return;

    input.addEventListener('input', function() {
        let foundId = '';
        for (let opt of list.options) {
            if (opt.value === input.value) {
                foundId = opt.dataset.id;
                break;
            }
        }
        hidden.value = foundId;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    syncDatalist('course_search', 'course_list', 'course_id_hidden');
    syncDatalist('class_search', 'class_list', 'class_id_hidden');
    syncDatalist('room_search', 'rooms_list', 'room_id_hidden');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>