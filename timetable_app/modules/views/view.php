<?php
require_once __DIR__ . '/../../includes/config.php';
requireLogin();

$class_id = $_GET['class_id'] ?? null;
$classes = $pdo->query("SELECT * FROM classes")->fetchAll();

$slots = $pdo->query("SELECT * FROM slots ORDER BY id")->fetchAll();
$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$hours = $pdo->query("SELECT DISTINCT start_time, end_time FROM slots ORDER BY start_time")->fetchAll();

$schedule = [];
if ($class_id) {
    $stmt = $pdo->prepare("SELECT t.*, co.code as course_code, te.name as teacher_name, r.name as room_name FROM timetable t JOIN courses co ON t.course_id = co.id JOIN teachers te ON t.teacher_id = te.id JOIN rooms r ON t.room_id = r.id WHERE t.class_id = ? AND t.semester_id = 1");
    $stmt->execute([$class_id]);
    $entries = $stmt->fetchAll();
    foreach ($entries as $e) {
        $schedule[$e['slot_id']][] = $e;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Consultation de l'Emploi du Temps</h2>
    <form method="GET">
        <label>Sélectionnez une classe :</label>
        <select name="class_id" onchange="this.form.submit()">
            <option value="">-- Choisir --</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $class_id == $c['id'] ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($class_id): ?>
<div class="card">
    <h3>Emploi du Temps : <?php 
        $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        echo $stmt->fetchColumn();
    ?></h3>
    
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
                                echo "<span class='ue-code'>{$entry['course_code']}</span>-{$entry['group_name']} (" . ($entry['type'] ?? 'CM') . ")<br>";
                                echo "<span class='teacher'>{$entry['teacher_name']}</span><br>";
                                echo "<span class='room'>Salle: {$entry['room_name']}</span><br>";
                                echo "<small>" . ($entry['date_passage'] ?? '') . "</small>";
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
