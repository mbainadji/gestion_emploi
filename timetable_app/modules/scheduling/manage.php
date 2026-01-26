<?php
require_once __DIR__ . '/../../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Only admin and teacher can access this
if ($role !== 'admin' && $role !== 'teacher') {
    die("Accès refusé.");
}

$message = '';
$error = '';

// Get teacher ID if the user is a teacher
$current_teacher_id = null;
if ($role === 'teacher') {
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $current_teacher_id = $stmt->fetchColumn();
}

$highlight_ids = isset($_GET['highlight']) ? explode(',', $_GET['highlight']) : [];

if (isset($_POST['add_entry'])) {
    $class_id = $_POST['class_id'];
    $course_id = $_POST['course_id'];
    $teacher_id = $_POST['teacher_id'];
    $room_id = $_POST['room_id'];
    $slot_id = $_POST['slot_id'];
    $group_name = $_POST['group_name'];
    $week_number = !empty($_POST['week_number']) ? (int)$_POST['week_number'] : null;
    $date_passage = $_POST['date_passage'];
    $semester_id = 1; // demo
    $type = $_POST['type'] ?? 'Cours';

    // Teachers can only add entries for themselves
    if ($role === 'teacher' && $teacher_id != $current_teacher_id) {
        $error = "Vous ne pouvez programmer que vos propres cours.";
    } else {
        // Conflict Check
        $stmt = $pdo->prepare("SELECT * FROM timetable WHERE slot_id = ? AND (room_id = ? OR teacher_id = ? OR (class_id = ? AND group_name = ?)) AND semester_id = ? AND (week_number IS NULL OR ? IS NULL OR week_number = ?)");
        $stmt->execute([$slot_id, $room_id, $teacher_id, $class_id, $group_name, $semester_id, $week_number, $week_number]);
        if ($stmt->fetch()) {
            $error = "Conflit détecté : La salle, l'enseignant ou la classe est déjà occupé sur ce créneau.";
        } else {
            // Room capacity vs Class size check
            $stmt = $pdo->prepare("SELECT size FROM classes WHERE id = ?");
            $stmt->execute([$class_id]);
            $class_size = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT capacity FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
            $room_capacity = $stmt->fetchColumn();

            if ($class_size > $room_capacity) {
                $error = "La capacité de la salle ($room_capacity) est insuffisante pour l'effectif de la classe ($class_size).";
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name, week_number, date_passage, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$class_id, $course_id, $teacher_id, $room_id, $slot_id, $semester_id, $group_name, $week_number, $date_passage, $type]);
                    
                    $last_id = $pdo->lastInsertId();
                    
                    // Log history
                    logHistory($_SESSION['user_id'], 'CREATE', 'timetable', $last_id, null, "Class:$class_id, UE:$course_id");
                    
                    // --- NOTIFICATIONS ---
                    // Notify Teacher if added by Admin
                    if ($role === 'admin') {
                        $teacher_user_stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
                        $teacher_user_stmt->execute([$teacher_id]);
                        $teacher_user_id = $teacher_user_stmt->fetchColumn();
                        if ($teacher_user_id) {
                            $notif_msg = "Un nouveau cours a été ajouté à votre emploi du temps par l'administrateur.";
                            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$teacher_user_id, $notif_msg]);
                        }
                    }

                    // Notify Students
                    $students_stmt = $pdo->prepare("SELECT user_id FROM students WHERE class_id = ?");
                    $students_stmt->execute([$class_id]);
                    $students = $students_stmt->fetchAll();
                    foreach ($students as $student) {
                        $student_msg = "Nouvelle mise à jour de votre emploi du temps. Consultez le planning.";
                        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$student['user_id'], $student_msg]);
                    }
                    // ----------------------

                    $pdo->commit();
                    $message = "Plage horaire ajoutée avec succès.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
                }
            }
        }
    }
}

if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $pdo->beginTransaction();
        
        // Teachers can only delete their own entries
        if ($role === 'teacher') {
            $stmt = $pdo->prepare("SELECT teacher_id FROM timetable WHERE id = ?");
            $stmt->execute([$delete_id]);
            $owner_id = $stmt->fetchColumn();
            if ($owner_id != $current_teacher_id) {
                throw new Exception("Vous ne pouvez supprimer que vos propres cours.");
            }
        }

        // Get session details for notifications
        $session_stmt = $pdo->prepare("SELECT class_id, teacher_id FROM timetable WHERE id = ?");
        $session_stmt->execute([$delete_id]);
        $session_info = $session_stmt->fetch();

        $stmt = $pdo->prepare("DELETE FROM timetable WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        logHistory($_SESSION['user_id'], 'DELETE', 'timetable', $delete_id, "Entry deleted", null);
        
        // --- NOTIFICATIONS ---
        if ($session_info) {
            // Notify Teacher
            $teacher_user_stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
            $teacher_user_stmt->execute([$session_info['teacher_id']]);
            $teacher_user_id = $teacher_user_stmt->fetchColumn();
            if ($teacher_user_id && $teacher_user_id != $user_id) {
                $notif_msg = "Un cours a été supprimé de votre emploi du temps.";
                $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$teacher_user_id, $notif_msg]);
            }

            // Notify Students
            $students_stmt = $pdo->prepare("SELECT user_id FROM students WHERE class_id = ?");
            $students_stmt->execute([$session_info['class_id']]);
            $students = $students_stmt->fetchAll();
            foreach ($students as $student) {
                $student_msg = "Un cours a été supprimé de votre emploi du temps. Veuillez vérifier le planning.";
                $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$student['user_id'], $student_msg]);
            }
        }
        // ----------------------

        $pdo->commit();
        $message = "Entrée supprimée.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Filtres
$filter_class = $_GET['class_id'] ?? '';
$filter_teacher = $_GET['teacher_id'] ?? '';

// Pre-fill department and program for filters if class_id is provided
$prefill_dept = '';
$prefill_prog = '';
if ($filter_class) {
    $stmt = $pdo->prepare("SELECT p.id as prog_id, p.department_id FROM classes c JOIN programs p ON c.program_id = p.id WHERE c.id = ?");
    $stmt->execute([$filter_class]);
    $pre = $stmt->fetch();
    if ($pre) {
        $prefill_prog = $pre['prog_id'];
        $prefill_dept = $pre['department_id'];
    }
}

// If teacher, they only see their own schedule by default or restricted
if ($role === 'teacher') {
    $filter_teacher = $current_teacher_id;
}

$sql = "SELECT t.*, c.name as class_name, co.code as course_code, te.name as teacher_name, r.name as room_name, s.day, s.start_time, s.end_time 
        FROM timetable t 
        JOIN classes c ON t.class_id = c.id 
        JOIN courses co ON t.course_id = co.id 
        JOIN teachers te ON t.teacher_id = te.id 
        JOIN rooms r ON t.room_id = r.id 
        JOIN slots s ON t.slot_id = s.id
        WHERE 1=1";
$params = [];

if ($filter_class) { $sql .= " AND t.class_id = ?"; $params[] = $filter_class; }
if ($filter_teacher) { $sql .= " AND t.teacher_id = ?"; $params[] = $filter_teacher; }

$sql .= " ORDER BY s.id, t.week_number";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$timetable = $stmt->fetchAll();

$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$programs = $pdo->query("SELECT * FROM programs ORDER BY name")->fetchAll();
$courses = $pdo->query("SELECT * FROM courses ORDER BY code")->fetchAll();
$teachers = $pdo->query("SELECT * FROM teachers ORDER BY name")->fetchAll();
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY name")->fetchAll();
$slots = $pdo->query("SELECT * FROM slots ORDER BY id")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<style>
    .highlight-conflict {
        background-color: #f8d7da !important;
        border: 2px solid #dc3545;
    }
</style>

<div class="card">
    <h2>Gestion des Emplois du Temps</h2>
    <?php if ($message): ?><div class="alert alert-success" style="color: green; margin-bottom: 1rem;"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" style="color: red; margin-bottom: 1rem;"><?php echo $error; ?></div><?php endif; ?>

    <?php if ($role === 'admin'): ?>
    <form method="GET" style="background:#f8f9fa; padding:10px; margin-bottom:20px; display:flex; gap:10px; border-radius: 8px;">
        <select name="class_id">
            <option value="">-- Toutes les classes --</option>
            <?php foreach($classes as $c) echo "<option value='{$c['id']}' ".($filter_class==$c['id']?'selected':'').">{$c['name']}</option>"; ?>
        </select>
        <select name="teacher_id">
            <option value="">-- Tous les enseignants --</option>
            <?php foreach($teachers as $t) echo "<option value='{$t['id']}' ".($filter_teacher==$t['id']?'selected':'').">{$t['name']}</option>"; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <?php if(!empty($highlight_ids)): ?>
            <div style="margin-left:auto; color:#dc3545; font-weight:bold;">Mode Résolution de Conflit</div>
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <h3>Affectations <?php echo $role === 'teacher' ? 'personnelles' : 'actuelles'; ?></h3>
    <table class="table">
        <thead><tr><th>Classe</th><th>UE</th><th>Enseignant</th><th>Salle</th><th>Créneau</th><th>Action</th></tr></thead>
        <tbody>
            <?php if (empty($timetable)): ?>
                <tr><td colspan="6" style="text-align: center;">Aucune programmation trouvée.</td></tr>
            <?php endif; ?>
            <?php foreach ($timetable as $entry): ?>
                <?php 
                $is_highlighted = in_array($entry['id'], $highlight_ids);
                ?>
                <tr class="<?php echo $is_highlighted ? 'highlight-conflict' : ''; ?>">
                    <td><?php echo htmlspecialchars($entry['class_name']); ?> <?php echo $entry['group_name'] ? "(".htmlspecialchars($entry['group_name']).")" : ''; ?></td>
                    <td><?php echo htmlspecialchars($entry['course_code']); ?></td>
                    <td><?php echo htmlspecialchars($entry['teacher_name']); ?></td>
                    <td><?php echo htmlspecialchars($entry['room_name']); ?></td>
                    <td>
                        <?php echo $entry['day']; ?> <?php echo $entry['start_time']; ?>
                        <?php if($entry['week_number']) echo "<br><small>Sem. {$entry['week_number']}</small>"; ?>
                    </td>
                    <td>
                        <?php if ($role === 'admin' || ($role === 'teacher' && $entry['teacher_id'] == $current_teacher_id)): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $entry['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Supprimer?')">Supprimer</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top: 2rem;">
    <h3>Ajouter une programmation</h3>
    <form method="POST">
        <input type="hidden" name="add_entry" value="1">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group"><label>Département</label>
                <select id="admin_dept" class="form-control" onchange="filterPrograms(this.value)">
                    <option value="">-- Tous les départements --</option>
                    <?php foreach($departments as $d) echo "<option value='{$d['id']}' ".($prefill_dept == $d['id'] ? 'selected' : '').">{$d['name']}</option>"; ?>
                </select>
            </div>
            <div class="form-group"><label>Filière (Program)</label>
                <select id="admin_prog" class="form-control" onchange="filterClasses(this.value)">
                    <option value="">-- Toutes les filières --</option>
                    <?php foreach($programs as $p) echo "<option value='{$p['id']}' data-dept='{$p['department_id']}' ".($prefill_prog == $p['id'] ? 'selected' : '').">{$p['name']}</option>"; ?>
                </select>
            </div>
            <div class="form-group"><label>Classe / Niveau</label>
                <select name="class_id" id="admin_class" class="form-control" required>
                    <option value="">-- Choisir la classe --</option>
                    <?php foreach($classes as $c) echo "<option value='{$c['id']}' data-prog='{$c['program_id']}' ".($filter_class == $c['id'] ? 'selected' : '').">{$c['name']}</option>"; ?>
                </select>
            </div>
            <div class="form-group"><label>Type de session</label>
                <select name="type" class="form-control" required>
                    <option value="Cours">Cours Magistral</option>
                    <option value="TD">Travaux Dirigés (TD)</option>
                    <option value="TP">Travaux Pratiques (TP)</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
            <div class="form-group"><label>Groupe</label><input type="text" name="group_name" class="form-control" placeholder="G1, G2 ou laisse vide"></div>
            <div class="form-group"><label>Semaine</label><input type="number" name="week_number" class="form-control" placeholder="Numéro de semaine (optionnel)"></div>
            <div class="form-group"><label>UE (Cours)</label><select name="course_id" class="form-control" required><?php foreach($courses as $c) echo "<option value='{$c['id']}'>{$c['code']} - {$c['title']}</option>"; ?></select></div>
            
            <?php if ($role === 'admin'): ?>
                <div class="form-group"><label>Enseignant</label><select name="teacher_id" class="form-control" required><?php foreach($teachers as $t) echo "<option value='{$t['id']}'>{$t['name']}</option>"; ?></select></div>
            <?php else: ?>
                <input type="hidden" name="teacher_id" value="<?php echo $current_teacher_id; ?>">
                <div class="form-group"><label>Enseignant</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" disabled></div>
            <?php endif; ?>

            <div class="form-group"><label>Salle</label><select name="room_id" class="form-control" required><?php foreach($rooms as $r) echo "<option value='{$r['id']}'>{$r['name']} (Cap: {$r['capacity']})</option>"; ?></select></div>
            <div class="form-group"><label>Date</label><input type="date" name="date_passage" class="form-control"></div>
            <div class="form-group"><label>Créneau</label><select name="slot_id" class="form-control" required><?php foreach($slots as $s) echo "<option value='{$s['id']}'>{$s['day']} {$s['start_time']}-{$s['end_time']}</option>"; ?></select></div>
        </div>
        
        <button type="submit" class="btn btn-success" style="margin-top: 1.5rem; width: 100%; padding: 0.8rem;">Programmer la session</button>
    </form>
</div>

<script>
function filterPrograms(deptId) {
    const progSelect = document.getElementById('admin_prog');
    const progs = progSelect.querySelectorAll('option');
    progSelect.value = '';
    filterClasses(''); // Reset classes too
    
    progs.forEach(opt => {
        if (!deptId || opt.value === '' || opt.dataset.dept === deptId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
}

function filterClasses(progId) {
    const classSelect = document.getElementById('admin_class');
    const classes = classSelect.querySelectorAll('option');
    // Don't reset if it's the initial load with prefill
    if (!progId) classSelect.value = '';
    
    classes.forEach(opt => {
        if (!progId || opt.value === '' || opt.dataset.prog === progId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
}

function initFilters() {
    const deptId = document.getElementById('admin_dept').value;
    const progId = document.getElementById('admin_prog').value;
    if (deptId) filterPrograms(deptId);
    if (progId) filterClasses(progId);
}

window.onload = initFilters;
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
