<?php
require_once '../../includes/config.php';

// Récupération des données pour les filtres
$classes = $pdo->query("SELECT id, name FROM classes ORDER BY name")->fetchAll();
$teachers = $pdo->query("SELECT id, name FROM teachers ORDER BY name")->fetchAll();
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();
$semesters = $pdo->query("SELECT id, name FROM semesters ORDER BY name")->fetchAll();

// Initialisation des variables de filtre
$selected_class = $_GET['class_id'] ?? null;
$selected_teacher = $_GET['teacher_id'] ?? null;
$selected_room = $_GET['room_id'] ?? null;
$selected_semester = $_GET['semester_id'] ?? 1; // Par défaut Semestre 1

// Construction de la requête SQL en fonction des filtres
$sql = "SELECT t.*, 
               c.name as class_name, 
               co.code as course_code, co.title as course_title,
               te.name as teacher_name,
               r.name as room_name,
               s.day, s.start_time, s.end_time
        FROM timetable t
        JOIN classes c ON t.class_id = c.id
        JOIN courses co ON t.course_id = co.id
        JOIN teachers te ON t.teacher_id = te.id
        JOIN rooms r ON t.room_id = r.id
        JOIN slots s ON t.slot_id = s.id
        WHERE t.semester_id = ?";

$params = [$selected_semester];

if ($selected_class) {
    $sql .= " AND t.class_id = ?";
    $params[] = $selected_class;
}
if ($selected_teacher) {
    $sql .= " AND t.teacher_id = ?";
    $params[] = $selected_teacher;
}
if ($selected_room) {
    $sql .= " AND t.room_id = ?";
    $params[] = $selected_room;
}

$sql .= " ORDER BY s.id, t.week_number";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$timetable_entries = $stmt->fetchAll();

// Organisation des données pour la grille
$schedule = [];
foreach ($timetable_entries as $entry) {
    $day = $entry['day'];
    $time_slot = $entry['start_time'] . ' - ' . $entry['end_time'];
    
    if (!isset($schedule[$time_slot])) {
        $schedule[$time_slot] = [];
    }
    if (!isset($schedule[$time_slot][$day])) {
        $schedule[$time_slot][$day] = [];
    }
    $schedule[$time_slot][$day][] = $entry;
}

// Récupération des jours et créneaux uniques pour la structure du tableau
$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$time_slots = $pdo->query("SELECT DISTINCT start_time, end_time FROM slots ORDER BY start_time")->fetchAll();

require_once '../../includes/header.php';
?>
<style>
    .table-responsive {
        overflow-x: auto;
        margin-top: 20px;
    }
    .timetable-grid {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .timetable-grid th, .timetable-grid td {
        border: 1px solid #dee2e6;
        padding: 8px;
        text-align: center;
        vertical-align: top;
    }
    .timetable-grid th {
        background-color: #f8f9fa;
    }
    .timetable-grid .time-col {
        width: 120px;
        font-weight: bold;
        background-color: #f8f9fa;
    }
    .course-cell {
        padding: 4px;
    }
    .course-card {
        background-color: #e7f3ff;
        border-left: 4px solid #007bff;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 5px;
        text-align: left;
        font-size: 0.85rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .course-card .course-code { font-weight: bold; color: #0056b3; }
    .course-card .teacher-name { font-style: italic; color: #555; display: block; margin: 2px 0; }
    .course-card .week-badge { background-color: #ffc107; color: #212529; padding: 2px 6px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
    .course-card .room-badge { background-color: #28a745; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.75rem; float: right; }
</style>

<div class="card">
    <h1>Consultation des Emplois du Temps</h1>
    
    <form method="GET" action="" class="filters-form" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group">
            <label for="semester_id">Semestre</label>
            <select name="semester_id" id="semester_id" class="form-control">
                <?php foreach ($semesters as $sem): ?>
                    <option value="<?php echo $sem['id']; ?>" <?php echo $selected_semester == $sem['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sem['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="class_id">Classe</label>
            <select name="class_id" id="class_id" class="form-control">
                <option value="">-- Toutes les classes --</option>
                <?php foreach ($classes as $cls): ?>
                    <option value="<?php echo $cls['id']; ?>" <?php echo $selected_class == $cls['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cls['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="teacher_id">Enseignant</label>
            <select name="teacher_id" id="teacher_id" class="form-control">
                <option value="">-- Tous les enseignants --</option>
                <?php foreach ($teachers as $tch): ?>
                    <option value="<?php echo $tch['id']; ?>" <?php echo $selected_teacher == $tch['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tch['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="room_id">Salle</label>
            <select name="room_id" id="room_id" class="form-control">
                <option value="">-- Toutes les salles --</option>
                <?php foreach ($rooms as $rm): ?>
                    <option value="<?php echo $rm['id']; ?>" <?php echo $selected_room == $rm['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($rm['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="view.php" class="btn btn-secondary" style="text-decoration:none; padding: 10px 15px; background: #6c757d; color: white; border-radius: 4px;">Réinitialiser</a>
    </form>

    <div class="timetable-view">
        <?php if (empty($timetable_entries)): ?>
            <div class="alert alert-info">Aucun cours trouvé pour les critères sélectionnés.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="timetable-grid">
                    <thead>
                        <tr>
                            <th>Horaire</th>
                            <?php foreach ($days as $day): ?>
                                <th><?php echo $day; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($time_slots as $slot): ?>
                            <?php $slot_label = $slot['start_time'] . ' - ' . $slot['end_time']; ?>
                            <tr>
                                <td class="time-col"><strong><?php echo $slot_label; ?></strong></td>
                                <?php foreach ($days as $day): ?>
                                    <td class="course-cell">
                                        <?php if (isset($schedule[$slot_label][$day])): ?>
                                            <?php foreach ($schedule[$slot_label][$day] as $course): ?>
                                                <div class="course-card">
                                                    <div class="course-code">
                                                        <?php echo htmlspecialchars($course['course_code']); ?>
                                                        <?php if ($course['group_name']): ?>
                                                            - <?php echo htmlspecialchars($course['group_name']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="course-details">
                                                        <span class="teacher-name"><?php echo htmlspecialchars($course['teacher_name']); ?></span>
                                                        <?php if ($course['week_number']): ?>
                                                            <span class="week-badge">Sem. <?php echo $course['week_number']; ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="room-badge">
                                                        <?php echo htmlspecialchars($course['room_name']); ?>
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
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>