<?php
require_once __DIR__ . '/../../includes/config.php';
requireRole('teacher');

// Get teacher ID
$stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher_id = $stmt->fetchColumn();

$message = '';
$error = '';

if (isset($_POST['add_session'])) {
    $course_id = $_POST['course_id'];
    $room_id = $_POST['room_id'];
    $slot_id = $_POST['slot_id'];
    $type = $_POST['type']; // TD, TP, Rattrapage
    $date_passage = $_POST['date_passage'];
    $class_id = $_POST['class_id'];
    $semester_id = 1;

    // Conflict Check
    $stmt = $pdo->prepare("SELECT * FROM timetable WHERE slot_id = ? AND (room_id = ? OR teacher_id = ? OR class_id = ?) AND semester_id = ?");
    $stmt->execute([$slot_id, $room_id, $teacher_id, $class_id, $semester_id]);
    
    if ($stmt->fetch()) {
        $error = "Conflit détecté : La salle, vous-même ou la classe est déjà occupé sur ce créneau.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, type, date_passage) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$class_id, $course_id, $teacher_id, $room_id, $slot_id, $semester_id, $type, $date_passage]);
        $message = "Session programmée avec succès.";
    }
}

// Get courses assigned to this teacher
$stmt = $pdo->prepare("SELECT tc.*, c.code, c.title, cl.name as class_name FROM teacher_courses tc JOIN courses c ON tc.course_id = c.id JOIN classes cl ON tc.class_id = cl.id WHERE tc.teacher_id = ?");
$stmt->execute([$teacher_id]);
$assigned_courses = $stmt->fetchAll();

$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll();
$slots = $pdo->query("SELECT * FROM slots")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Programmer un TD, TP ou Rattrapage</h2>
    <?php if ($message): ?><p style="color: green;"><?php echo $message; ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>

    <form method="POST">
        <div>
            <label>UE & Classe</label>
            <select name="course_class" onchange="const parts = this.value.split(':'); document.getElementById('course_id').value = parts[0]; document.getElementById('class_id').value = parts[1];">
                <option value="">-- Sélectionner --</option>
                <?php foreach ($assigned_courses as $ac): ?>
                    <option value="<?php echo $ac['course_id'] . ':' . $ac['class_id']; ?>">
                        <?php echo $ac['code']; ?> - <?php echo $ac['class_name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="course_id" id="course_id">
            <input type="hidden" name="class_id" id="class_id">
        </div>
        <div>
            <label>Type de session</label>
            <select name="type">
                <option value="TD">TD</option>
                <option value="TP">TP</option>
                <option value="Rattrapage">Rattrapage</option>
            </select>
        </div>
        <div><label>Salle</label><select name="room_id"><?php foreach($rooms as $r) echo "<option value='{$r['id']}'>{$r['name']} (Cap: {$r['capacity']})</option>"; ?></select></div>
        <div><label>Date</label><input type="date" name="date_passage" required></div>
        <div><label>Créneau</label><select name="slot_id"><?php foreach($slots as $s) echo "<option value='{$s['id']}'>{$s['day']} {$s['start_time']}-{$s['end_time']}</option>"; ?></select></div>
        <button type="submit" name="add_session" class="btn btn-success">Programmer</button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
