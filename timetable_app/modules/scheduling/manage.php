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
            if ($role === 'admin') {
                $error .= " <a href='../rooms/index.php' style='color: #842029; text-decoration: underline;'>Créer une nouvelle salle ?</a>";
            }
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

// Fetch Names for Breadcrumbs
$breadcrumb_dept = null;
$breadcrumb_prog = null;
$breadcrumb_class = null;

if ($prefill_dept) {
    $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$prefill_dept]);
    $breadcrumb_dept = $stmt->fetchColumn();
}
if ($prefill_prog) {
    $stmt = $pdo->prepare("SELECT name FROM programs WHERE id = ?");
    $stmt->execute([$prefill_prog]);
    $breadcrumb_prog = $stmt->fetchColumn();
}
if ($filter_class) {
    $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->execute([$filter_class]);
    $breadcrumb_class = $stmt->fetchColumn();
}

require_once __DIR__ . '/../../includes/header.php';
?>
<style>
    .highlight-conflict {
        background-color: #f8d7da !important;
        border: 2px solid #dc3545;
    }
</style>

<div class="card">
    <nav style="margin-bottom: 1rem; font-size: 0.9rem; color: var(--text-muted);">
        <a href="../academics/departments_view.php" style="color: var(--primary); font-weight: 600;">Départements</a>
        <?php if ($breadcrumb_dept): ?>
            <span style="margin: 0 0.5rem;">/</span>
            <a href="../academics/departments_view.php?step=program&dept_id=<?php echo $prefill_dept; ?>" style="color: var(--primary); font-weight: 600;">
                <?php echo htmlspecialchars($breadcrumb_dept); ?>
            </a>
        <?php endif; ?>
        <?php if ($breadcrumb_prog): ?>
            <span style="margin: 0 0.5rem;">/</span>
            <a href="../academics/departments_view.php?step=level&dept_id=<?php echo $prefill_dept; ?>&program_id=<?php echo $prefill_prog; ?>" style="color: var(--primary); font-weight: 600;">
                <?php echo htmlspecialchars($breadcrumb_prog); ?>
            </a>
        <?php endif; ?>
        <?php if ($breadcrumb_class): ?>
            <span style="margin: 0 0.5rem;">/</span>
            <span style="font-weight: 600;"><?php echo htmlspecialchars($breadcrumb_class); ?></span>
        <?php endif; ?>
    </nav>

    <h2>Gestion des Emplois du Temps</h2>
    <?php if ($message): ?><div class="alert alert-success" style="color: green; margin-bottom: 1rem;"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" style="color: red; margin-bottom: 1rem;"><?php echo $error; ?></div><?php endif; ?>

    <?php if ($role === 'admin'): ?>
    <div style="display: flex; gap: 1rem; margin-bottom: 20px;">
        <form method="GET" style="background:#f8f9fa; padding:10px; flex: 1; display:flex; gap:10px; border-radius: 8px;">
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
        <a href="catchup.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.5rem;">
            <span>📋</span> Demandes & Propositions
        </a>
    </div>
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
                <input list="prog_list" id="prog_search" class="form-control" placeholder="Rechercher une filière..." value="<?php 
                    foreach($programs as $p) {
                        if($p['id'] == $prefill_prog) { echo htmlspecialchars($p['name']); break; }
                    }
                ?>">
                <datalist id="prog_list">
                    <?php foreach($programs as $p) echo "<option value='".htmlspecialchars($p['name'])."' data-id='{$p['id']}' data-dept='{$p['department_id']}'>"; ?>
                </datalist>
                <input type="hidden" id="admin_prog" value="<?php echo $prefill_prog; ?>">
            </div>
            <div class="form-group"><label>Classe / Niveau</label>
                <input list="class_list" id="class_search" class="form-control" placeholder="Rechercher une classe..." required value="<?php 
                    foreach($classes as $c) {
                        if($c['id'] == $filter_class) { echo htmlspecialchars($c['name']); break; }
                    }
                ?>">
                <datalist id="class_list">
                    <?php foreach($classes as $c) echo "<option value='".htmlspecialchars($c['name'])."' data-id='{$c['id']}' data-prog='{$c['program_id']}'>"; ?>
                </datalist>
                <input type="hidden" name="class_id" id="admin_class" value="<?php echo $filter_class; ?>">
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
            <div class="form-group"><label>UE (Cours)</label>
                <input list="course_list" id="course_search" class="form-control" placeholder="Rechercher une UE..." required>
                <datalist id="course_list">
                    <?php foreach($courses as $c) echo "<option value='".htmlspecialchars($c['code'] . " - " . $c['title'])."' data-id='{$c['id']}'>"; ?>
                </datalist>
                <input type="hidden" name="course_id" id="course_id_hidden">
            </div>
            
            <?php if ($role === 'admin'): ?>
                <div class="form-group"><label>Enseignant</label>
                    <input list="teacher_list" id="teacher_search" class="form-control" placeholder="Rechercher un enseignant..." required>
                    <datalist id="teacher_list">
                        <?php foreach($teachers as $t) echo "<option value='".htmlspecialchars($t['name'])."' data-id='{$t['id']}'>"; ?>
                    </datalist>
                    <input type="hidden" name="teacher_id" id="teacher_id_hidden">
                </div>
            <?php else: ?>
                <input type="hidden" name="teacher_id" value="<?php echo $current_teacher_id; ?>">
                <div class="form-group"><label>Enseignant</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" disabled></div>
            <?php endif; ?>

            <div class="form-group"><label>Salle</label>
                <input list="rooms_list" id="room_search" class="form-control" placeholder="Rechercher une salle..." required value="<?php 
                    if(isset($_POST['room_id'])) {
                        foreach($rooms as $r) {
                            if($r['id'] == $_POST['room_id']) { echo htmlspecialchars($r['name']); break; }
                        }
                    }
                ?>">
                <datalist id="rooms_list">
                    <?php foreach($rooms as $r) echo "<option value='".htmlspecialchars($r['name'])."' data-id='{$r['id']}'>Cap: {$r['capacity']}</option>"; ?>
                </datalist>
                <input type="hidden" name="room_id" id="room_id_hidden" value="<?php echo $_POST['room_id'] ?? ''; ?>">
            </div>
            <div class="form-group"><label>Date</label><input type="date" name="date_passage" class="form-control"></div>
            <div class="form-group"><label>Créneau</label><select name="slot_id" class="form-control" required><?php foreach($slots as $s) echo "<option value='{$s['id']}'>{$s['day']} {$s['start_time']}-{$s['end_time']}</option>"; ?></select></div>
        </div>
        
        <button type="submit" class="btn btn-success" style="margin-top: 1.5rem; width: 100%; padding: 0.8rem;">Programmer la session</button>
    </form>
</div>

<script>
function syncDatalist(inputId, listId, hiddenId, callback) {
    const input = document.getElementById(inputId);
    const list = document.getElementById(listId);
    const hidden = document.getElementById(hiddenId);

    input.addEventListener('input', function() {
        let foundId = '';
        for (let opt of list.options) {
            if (opt.value === input.value) {
                foundId = opt.dataset.id;
                break;
            }
        }
        hidden.value = foundId;
        if (callback) callback(foundId);
    });
}

// Store original options for filtering
const allProgOptions = Array.from(document.getElementById('prog_list').options).map(opt => ({
    value: opt.value,
    id: opt.dataset.id,
    dept: opt.dataset.dept
}));

const allClassOptions = Array.from(document.getElementById('class_list').options).map(opt => ({
    value: opt.value,
    id: opt.dataset.id,
    prog: opt.dataset.prog
}));

function filterPrograms(deptId) {
    const progList = document.getElementById('prog_list');
    const progSearch = document.getElementById('prog_search');
    const progHidden = document.getElementById('admin_prog');
    
    // Clear current selection if not matching new dept
    if (progHidden.value) {
        const currentProg = allProgOptions.find(p => p.id === progHidden.value);
        if (deptId && currentProg && currentProg.dept !== deptId) {
            progSearch.value = '';
            progHidden.value = '';
            filterClasses('');
        }
    }

    progList.innerHTML = '';
    allProgOptions.forEach(opt => {
        if (!deptId || opt.dept === deptId) {
            const o = document.createElement('option');
            o.value = opt.value;
            o.dataset.id = opt.id;
            o.dataset.dept = opt.dept;
            progList.appendChild(o);
        }
    });
}

function filterClasses(progId) {
    const classList = document.getElementById('class_list');
    const classSearch = document.getElementById('class_search');
    const classHidden = document.getElementById('admin_class');

    // Clear current selection if not matching new prog
    if (classHidden.value) {
        const currentClass = allClassOptions.find(c => c.id === classHidden.value);
        if (progId && currentClass && currentClass.prog !== progId) {
            classSearch.value = '';
            classHidden.value = '';
        }
    }

    classList.innerHTML = '';
    allClassOptions.forEach(opt => {
        if (!progId || opt.prog === progId) {
            const o = document.createElement('option');
            o.value = opt.value;
            o.dataset.id = opt.id;
            o.dataset.prog = opt.prog;
            classList.appendChild(o);
        }
    });
}

function initFilters() {
    syncDatalist('room_search', 'rooms_list', 'room_id_hidden');
    syncDatalist('prog_search', 'prog_list', 'admin_prog', filterClasses);
    syncDatalist('class_search', 'class_list', 'admin_class');
    syncDatalist('course_search', 'course_list', 'course_id_hidden');
    if (document.getElementById('teacher_search')) {
        syncDatalist('teacher_search', 'teacher_list', 'teacher_id_hidden');
    }

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
