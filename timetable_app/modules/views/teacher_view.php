<?php
require_once __DIR__ . '/../../includes/config.php';
requireLogin();

// If admin is viewing, allow selecting a teacher, otherwise use current teacher
$teacher_id = $_GET['teacher_id'] ?? null;
if (!hasRole('admin') || !$teacher_id) {
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher_id = $stmt->fetchColumn();
}

$slots = $pdo->query("SELECT * FROM slots ORDER BY id")->fetchAll();
$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$hours = $pdo->query("SELECT DISTINCT start_time, end_time FROM slots ORDER BY start_time")->fetchAll();

$schedule = [];
if ($teacher_id) {
    $stmt = $pdo->prepare("SELECT t.*, co.code as course_code, cl.name as class_name, r.name as room_name FROM timetable t JOIN courses co ON t.course_id = co.id JOIN classes cl ON t.class_id = cl.id JOIN rooms r ON t.room_id = r.id WHERE t.teacher_id = ? AND t.semester_id = 1");
    $stmt->execute([$teacher_id]);
    $entries = $stmt->fetchAll();
    foreach ($entries as $e) {
        $schedule[$e['slot_id']][] = $e;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Emploi du Temps : Enseignant</h2>
    <?php if (hasRole('admin')): ?>
        <form method="GET">
            <label>Sélectionnez un enseignant :</label>
            <select name="teacher_id" onchange="this.form.submit()">
                <?php 
                $teachers = $pdo->query("SELECT * FROM teachers")->fetchAll();
                foreach ($teachers as $t) echo "<option value='{$t['id']}' ".($teacher_id == $t['id'] ? 'selected' : '').">{$t['name']}</option>";
                ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<?php if ($teacher_id): ?>
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
                                echo "{$entry['class_name']}-{$entry['group_name']}<br>";
                                echo "<span class='room'>Salle: {$entry['room_name']}</span>";
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
