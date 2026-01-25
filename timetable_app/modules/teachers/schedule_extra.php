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
    $room_id = (int)$_POST['room_id'];
    $slot_id = (int)$_POST['slot_id'];
    $type = $_POST['type']; // TD, TP, Rattrapage
    $date_passage = $_POST['date_passage'];
    
    // Parse course_class value
    $parts = explode(':', $_POST['course_class']);
    $course_id = (int)$parts[0];
    $class_id = (int)$parts[1];
    $semester_id = 1; // TODO: Devrait être dynamique, basé sur le cours

    // --- FIX: Vérification de conflit robuste ---
    // Récupérer les détails du créneau (jour, heure de début/fin)
    $slot_stmt = $pdo->prepare("SELECT day, start_time, end_time FROM slots WHERE id = ?");
    $slot_stmt->execute([$slot_id]);
    $slot = $slot_stmt->fetch();

    if (!$slot) {
        $error = "Créneau horaire invalide.";
    } else {
        // Vérifie s'il y a un conflit avec une autre session (ponctuelle ou récurrente)
        $conflict_stmt = $pdo->prepare("
            SELECT t.id
            FROM timetable t
            JOIN slots s ON t.slot_id = s.id
            WHERE
                (t.teacher_id = :teacher_id OR t.room_id = :room_id OR t.class_id = :class_id)
                AND s.start_time < :end_time AND s.end_time > :start_time -- Vérifie le chevauchement des heures
                AND (
                    -- Conflit avec une session ponctuelle (même date)
                    t.date_passage = :date_passage
                    OR
                    -- Conflit avec une session récurrente (même jour de la semaine)
                    (t.date_passage IS NULL AND s.day = :day_name)
                )
            LIMIT 1
        ");

        $conflict_stmt->execute([
            ':teacher_id' => $teacher_id,
            ':room_id' => $room_id,
            ':class_id' => $class_id,
            ':start_time' => $slot['start_time'],
            ':end_time' => $slot['end_time'],
            ':date_passage' => $date_passage,
            ':day_name' => $slot['day']
        ]);

        if ($conflict_stmt->fetch()) {
            $error = "Conflit détecté : La salle, vous-même ou la classe est déjà occupé sur ce créneau.";
        } else {
        $stmt = $pdo->prepare("INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, semester_id, type, date_passage) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$class_id, $course_id, $teacher_id, $room_id, $slot_id, $semester_id, $type, $date_passage]);
        $message = "Session programmée avec succès.";
        }
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
            <select name="course_class" required>
                <option value="">-- Sélectionner --</option>
                <?php foreach ($assigned_courses as $ac): ?>
                    <option value="<?php echo $ac['course_id'] . ':' . $ac['class_id']; ?>">
                        <?php echo $ac['code']; ?> - <?php echo $ac['class_name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
