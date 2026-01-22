<?php
require_once __DIR__ . '/../../includes/config.php';
requireLogin();

$room_id = $_GET['room_id'] ?? null;
$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll();

$slots = $pdo->query("SELECT * FROM slots ORDER BY id")->fetchAll();
$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$hours = $pdo->query("SELECT DISTINCT start_time, end_time FROM slots ORDER BY start_time")->fetchAll();

$schedule = [];
if ($room_id) {
    $stmt = $pdo->prepare("SELECT t.*, co.code as course_code, cl.name as class_name, te.name as teacher_name FROM timetable t JOIN courses co ON t.course_id = co.id JOIN classes cl ON t.class_id = cl.id JOIN teachers te ON t.teacher_id = te.id WHERE t.room_id = ? AND t.semester_id = 1");
    $stmt->execute([$room_id]);
    $entries = $stmt->fetchAll();
    foreach ($entries as $e) {
        $schedule[$e['slot_id']][] = $e;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Occupation des Salles</h2>
    <form method="GET">
        <label>Sélectionnez une salle :</label>
        <select name="room_id" onchange="this.form.submit()">
            <option value="">-- Choisir --</option>
            <?php foreach ($rooms as $r): ?>
                <option value="<?php echo $r['id']; ?>" <?php echo $room_id == $r['id'] ? 'selected' : ''; ?>><?php echo $r['name']; ?> (Cap: <?php echo $r['capacity']; ?>)</option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($room_id): ?>
<div class="card">
    <div class="timetable-grid">
        <div class="grid-header">Horaire</div>
        <?php foreach ($days as $day): ?>
            <div class="grid-header"><?php echo $day; ?></div>
        <?php endforeach; ?>

        <?php foreach ($hours as $h): ?>
            <div class="grid-header"><?php echo $h['start_time']; ?>-<?php echo $h['end_time']; ?></div>
            <?php foreach ($days as $day): ?>
                <div class="slot">
                    <?php 
                        $stmt = $pdo->prepare("SELECT id FROM slots WHERE day = ? AND start_time = ?");
                        $stmt->execute([$day, $h['start_time']]);
                        $s_id = $stmt->fetchColumn();
                        if (isset($schedule[$s_id])) {
                            foreach ($schedule[$s_id] as $entry) {
                                echo "<div class='slot-filled'>";
                                echo "<span class='ue-code'>{$entry['course_code']}</span> (" . ($entry['type'] ?? 'CM') . ")<br>";
                                echo "{$entry['class_name']}<br>";
                                echo "<span class='teacher'>{$entry['teacher_name']}</span>";
                                echo "</div>";
                            }
                        }
                    ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
