<?php
require_once __DIR__ . '/../../includes/config.php';
requireRole('admin');

$message = '';
$error = '';

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
            $stmt = $pdo->prepare("INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, group_name, week_number, date_passage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$class_id, $course_id, $teacher_id, $room_id, $slot_id, $semester_id, $group_name, $week_number, $date_passage]);
            $message = "Plage horaire ajoutée avec succès.";
            
            // Log history
            logHistory($_SESSION['user_id'], 'CREATE', 'timetable', $pdo->lastInsertId(), null, "Class:$class_id, UE:$course_id");
            
            if (isTimetableComplete($class_id, $semester_id)) {
                $message .= " Alerte : L'emploi du temps de cette classe est maintenant complet !";
            }
        }
    }
}

if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM timetable WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    $message = "Entrée supprimée.";
}

// Filtres
$filter_class = $_GET['class_id'] ?? '';
$filter_teacher = $_GET['teacher_id'] ?? '';

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

$classes = $pdo->query("SELECT * FROM classes")->fetchAll();
$courses = $pdo->query("SELECT * FROM courses")->fetchAll();
$teachers = $pdo->query("SELECT * FROM teachers")->fetchAll();
$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll();
$slots = $pdo->query("SELECT * FROM slots")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<style>
    .highlight-conflict {
        background-color: #f8d7da !important;
        border: 2px solid #dc3545;
    }
</style>

<div class="card">
    <h2>Planification et Arbitrage</h2>
    <?php if ($message): ?><p style="color: green;"><?php echo $message; ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>

    <form method="GET" style="background:#f8f9fa; padding:10px; margin-bottom:20px; display:flex; gap:10px;">
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

    <h3>Affectations actuelles</h3>
    <table>
        <thead><tr><th>Classe</th><th>UE</th><th>Enseignant</th><th>Salle</th><th>Créneau</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach ($timetable as $entry): ?>
                <?php 
                $is_highlighted = in_array($entry['id'], $highlight_ids);
                ?>
                <tr class="<?php echo $is_highlighted ? 'highlight-conflict' : ''; ?>">
                    <td><?php echo $entry['class_name']; ?> (<?php echo $entry['group_name']; ?>)</td>
                    <td><?php echo $entry['course_code']; ?></td>
                    <td><?php echo $entry['teacher_name']; ?></td>
                    <td><?php echo $entry['room_name']; ?></td>
                    <td>
                        <?php echo $entry['day']; ?> <?php echo $entry['start_time']; ?>
                        <?php if($entry['week_number']) echo "<br><small>Sem. {$entry['week_number']}</small>"; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $entry['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Supprimer?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Ajouter une programmation</h3>
    <form method="POST">
        <input type="hidden" name="add_entry" value="1">
        <div><label>Classe</label><select name="class_id"><?php foreach($classes as $c) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?></select></div>
        <div><label>Groupe</label><input type="text" name="group_name" placeholder="G1, G2 ou laisse vide"></div>
        <div><label>Semaine</label><input type="number" name="week_number" placeholder="Numéro de semaine (optionnel)"></div>
        <div><label>UE</label><select name="course_id"><?php foreach($courses as $c) echo "<option value='{$c['id']}'>{$c['code']} - {$c['title']}</option>"; ?></select></div>
        <div><label>Enseignant</label><select name="teacher_id"><?php foreach($teachers as $t) echo "<option value='{$t['id']}'>{$t['name']}</option>"; ?></select></div>
        <div><label>Salle</label><select name="room_id"><?php foreach($rooms as $r) echo "<option value='{$r['id']}'>{$r['name']} (Cap: {$r['capacity']})</option>"; ?></select></div>
        <div><label>Date</label><input type="date" name="date_passage"></div>
        <div><label>Créneau</label><select name="slot_id"><?php foreach($slots as $s) echo "<option value='{$s['id']}'>{$s['day']} {$s['start_time']}-{$s['end_time']}</option>"; ?></select></div>
        <button type="submit" class="btn btn-success">Programmer</button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
