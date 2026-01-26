<?php
require_once '../../includes/config.php';

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'guest';

// Initialisation des variables de filtre
$selected_dept = $_GET['department_id'] ?? null;
$selected_program = $_GET['program_id'] ?? null;
$selected_semester = $_GET['semester_id'] ?? 1;
$selected_room = $_GET['room_id'] ?? null;
$selected_class = $_GET['class_id'] ?? null;
$selected_teacher = $_GET['teacher_id'] ?? null;

// Role-based overrides
if ($role === 'student') {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch();
    if ($student) {
        $selected_dept = $student['department_id'];
        $selected_program = $student['program_id'];
        $selected_class = $student['class_id'];
    }
} elseif ($role === 'teacher') {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $teacher = $stmt->fetch();
    if ($teacher) {
        // Teachers see their own schedule by default
        if (!$selected_teacher && !isset($_GET['teacher_id'])) {
            $selected_teacher = $teacher['id'];
        }
    }
}

// Récupération des données pour les filtres prioritaires
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
$semesters = $pdo->query("SELECT id, name FROM semesters ORDER BY name")->fetchAll();
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();

// Teacher filtering logic: only show teachers associated with the selected program/class
$teachers_query = "SELECT DISTINCT te.id, te.name FROM teachers te";
$teachers_params = [];
$where_clauses = [];

if ($selected_class) {
    $teachers_query .= " JOIN timetable t ON te.id = t.teacher_id";
    $where_clauses[] = "t.class_id = ?";
    $teachers_params[] = $selected_class;
} elseif ($selected_program) {
    $teachers_query .= " JOIN classes c ON c.program_id = ? JOIN timetable t ON t.class_id = c.id AND te.id = t.teacher_id";
    $teachers_params[] = $selected_program;
}

if ($where_clauses) {
    $teachers_query .= " WHERE " . implode(" AND ", $where_clauses);
}
$teachers_query .= " ORDER BY te.name";
$stmt_t = $pdo->prepare($teachers_query);
$stmt_t->execute($teachers_params);
$teachers = $stmt_t->fetchAll();

// Récupération dynamique des programmes
$programs = [];
if ($selected_dept) {
    $stmt = $pdo->prepare("SELECT id, name FROM programs WHERE department_id = ? ORDER BY name");
    $stmt->execute([$selected_dept]);
    $programs = $stmt->fetchAll();
}

// Récupération dynamique des classes
$classes = [];
if ($selected_program) {
    $stmt = $pdo->prepare("SELECT id, name FROM classes WHERE program_id = ? ORDER BY name");
    $stmt->execute([$selected_program]);
    $classes = $stmt->fetchAll();
}

// Requête SQL principale
$sql = "SELECT t.*, c.name as class_name, co.code as course_code, co.title as course_title,
               te.name as teacher_name, r.name as room_name, s.day, s.start_time, s.end_time,
               p.name as program_name, d.name as dept_name
        FROM timetable t
        JOIN classes c ON t.class_id = c.id
        JOIN programs p ON c.program_id = p.id
        JOIN departments d ON p.department_id = d.id
        JOIN courses co ON t.course_id = co.id
        JOIN teachers te ON t.teacher_id = te.id
        JOIN rooms r ON t.room_id = r.id
        JOIN slots s ON t.slot_id = s.id
        WHERE t.semester_id = ?";

$params = [$selected_semester];

if ($selected_room) { $sql .= " AND t.room_id = ?"; $params[] = $selected_room; }
if ($selected_class) { $sql .= " AND t.class_id = ?"; $params[] = $selected_class; }
elseif ($selected_program) { $sql .= " AND c.program_id = ?"; $params[] = $selected_program; }
elseif ($selected_dept) { $sql .= " AND p.department_id = ?"; $params[] = $selected_dept; }

if ($selected_teacher) { $sql .= " AND t.teacher_id = ?"; $params[] = $selected_teacher; }

$sql .= " ORDER BY s.id, t.week_number";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$timetable_entries = $stmt->fetchAll();

$schedule = [];
foreach ($timetable_entries as $entry) {
    $day = $entry['day'];
    $time_slot = $entry['start_time'] . ' - ' . $entry['end_time'];
    $schedule[$time_slot][$day][] = $entry;
}

$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$time_slots = $pdo->query("SELECT DISTINCT start_time, end_time FROM slots ORDER BY start_time")->fetchAll();

// Fetch Names for Breadcrumbs
$breadcrumb_dept = null;
$breadcrumb_prog = null;
$breadcrumb_class = null;

if ($selected_dept) {
    $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$selected_dept]);
    $breadcrumb_dept = $stmt->fetchColumn();
}
if ($selected_program) {
    $stmt = $pdo->prepare("SELECT name FROM programs WHERE id = ?");
    $stmt->execute([$selected_program]);
    $breadcrumb_prog = $stmt->fetchColumn();
}
if ($selected_class) {
    $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->execute([$selected_class]);
    $breadcrumb_class = $stmt->fetchColumn();
}

require_once '../../includes/header.php';
?>

<div class="card">
    <nav style="margin-bottom: 1rem; font-size: 0.9rem; color: var(--text-muted);">
        <a href="../academics/departments_view.php" style="color: var(--primary); font-weight: 600;">Départements</a>
        <?php if ($breadcrumb_dept): ?>
            <span style="margin: 0 0.5rem;">/</span>
            <a href="../academics/departments_view.php?step=program&dept_id=<?php echo $selected_dept; ?>" style="color: var(--primary); font-weight: 600;">
                <?php echo htmlspecialchars($breadcrumb_dept); ?>
            </a>
        <?php endif; ?>
        <?php if ($breadcrumb_prog): ?>
            <span style="margin: 0 0.5rem;">/</span>
            <a href="../academics/departments_view.php?step=level&dept_id=<?php echo $selected_dept; ?>&program_id=<?php echo $selected_program; ?>" style="color: var(--primary); font-weight: 600;">
                <?php echo htmlspecialchars($breadcrumb_prog); ?>
            </a>
        <?php endif; ?>
        <?php if ($breadcrumb_class): ?>
            <span style="margin: 0 0.5rem;">/</span>
            <span style="font-weight: 600;"><?php echo htmlspecialchars($breadcrumb_class); ?></span>
        <?php endif; ?>
    </nav>

    <h1 style="margin-bottom: 1.5rem;">Consultation de l'Emploi du Temps</h1>
    
    <?php if ($role !== 'student'): ?>
    <form method="GET" class="card" style="background: var(--bg); border: 1px solid var(--border); box-shadow: none; padding: 1.5rem;">
        <h3 style="margin-top: 0; font-size: 1rem; color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem;">Filtres Principaux</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label>Département</label>
                <select name="department_id" onchange="this.form.submit()">
                    <option value="">-- Choisir --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $selected_dept == $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Filière</label>
                <select name="program_id" onchange="this.form.submit()" <?php echo !$selected_dept ? 'disabled' : ''; ?>>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $selected_program == $p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Semestre</label>
                <select name="semester_id" onchange="this.form.submit()">
                    <?php foreach ($semesters as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $selected_semester == $s['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Salle</label>
                <select name="room_id" onchange="this.form.submit()">
                    <option value="">-- Toutes --</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo $selected_room == $r['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <details style="margin-top: 1.5rem;" <?php echo ($selected_class || $selected_teacher) ? 'open' : ''; ?>>
            <summary style="cursor: pointer; color: var(--secondary); font-size: 0.9rem; font-weight: 600;">Filtres Avancés (Optionnels)</summary>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border);">
                <div class="form-group">
                    <label>Niveau / Classe</label>
                    <select name="class_id" onchange="this.form.submit()" <?php echo !$selected_program ? 'disabled' : ''; ?>>
                        <option value="">-- Tous les niveaux --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $selected_class == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Enseignant</label>
                    <select name="teacher_id" onchange="this.form.submit()">
                        <option value="">-- Tous --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo $selected_teacher == $t['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </details>
        
    <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
            <a href="export_ics.php" class="btn btn-secondary" style="background: #6366f1;">Exporter Agenda (ICS)</a>
            <button onclick="downloadPDF()" class="btn btn-secondary" style="background: #10b981;">Télécharger en PDF</button>
            <?php if ($role !== 'student'): ?>
                <a href="view.php" class="btn btn-secondary" style="background: #94a3b8;">Effacer</a>
                <button type="submit" class="btn btn-primary">Rechercher</button>
            <?php endif; ?>
        </div>
    </form>

    <script>
        function downloadPDF() {
            const originalTitle = document.title;
            document.title = "Emploi_du_Temps_" + new Date().toISOString().slice(0,10);
            window.print();
            document.title = originalTitle;
        }
    </script>

    <style>
        @media print {
            nav, form, details, .btn, .alert { display: none !important; }
            .card { box-shadow: none; border: none; padding: 0; }
            .timetable-grid { font-size: 10pt; }
            body { background: white; }
        }
    </style>
    <?php else: ?>
        <div class="alert alert-info" style="background: #e0f2fe; color: #0369a1; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            Affichage de l'emploi du temps pour : <strong><?php echo htmlspecialchars($timetable_entries[0]['dept_name'] ?? ''); ?> - <?php echo htmlspecialchars($timetable_entries[0]['program_name'] ?? ''); ?> - <?php echo htmlspecialchars($timetable_entries[0]['class_name'] ?? ''); ?></strong>
        </div>
    <?php endif; ?>

    <div class="table-responsive" style="margin-top: 2rem;">
        <?php if (empty($timetable_entries)): ?>
            <div style="text-align: center; padding: 4rem; background: #fff; border: 2px dashed var(--border); border-radius: var(--radius);">
                <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">📅</span>
                <p style="color: var(--text-muted); font-size: 1.1rem; margin: 0;">Aucun cours trouvé pour cette sélection.</p>
            </div>
        <?php else: ?>
            <table class="timetable-grid">
                <thead>
                    <tr style="background: var(--primary); color: white;">
                        <th style="width: 140px; color: white;">Horaires</th>
                        <?php foreach ($days as $day): ?>
                            <th style="color: white;"><?php echo $day; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_slots as $slot): ?>
                        <?php $slot_label = $slot['start_time'] . ' - ' . $slot['end_time']; ?>
                        <tr>
                            <td style="background: var(--bg); font-weight: 700; color: var(--primary); text-align: center; vertical-align: middle;">
                                <?php echo $slot_label; ?>
                            </td>
                            <?php foreach ($days as $day): ?>
                                <td style="padding: 0.5rem; min-height: 120px;">
                                    <?php if (isset($schedule[$slot_label][$day])): ?>
                                        <?php foreach ($schedule[$slot_label][$day] as $course): ?>
                                            <div style="background: #f0f7ff; border-left: 4px solid var(--primary); padding: 0.8rem; border-radius: 6px; margin-bottom: 0.6rem; box-shadow: var(--shadow-sm);">
                                                <div style="font-weight: 800; color: var(--primary); margin-bottom: 0.3rem; font-size: 0.9rem;">
                                                    <?php echo htmlspecialchars($course['course_code']); ?> 
                                                    <span style="font-weight: 400; font-size: 0.8rem; background: var(--bg); padding: 2px 6px; border-radius: 4px; margin-left: 5px;">
                                                        <?php echo htmlspecialchars($course['type'] ?? 'Cours'); ?>
                                                    </span>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--text); line-height: 1.4;">
                                                    <strong><?php echo htmlspecialchars($course['teacher_name']); ?></strong><br>
                                                    <span style="color: var(--success); font-weight: 600;">📍 <?php echo htmlspecialchars($course['room_name']); ?></span><br>
                                                    <span style="color: var(--secondary); font-size: 0.75rem;">🎓 <?php echo htmlspecialchars($course['class_name']); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
    .timetable-grid { width: 100%; border-collapse: collapse; }
    .timetable-grid th, .timetable-grid td { border: 1px solid var(--border); padding: 0.75rem; vertical-align: top; }
</style>

<?php require_once '../../includes/footer.php'; ?>
