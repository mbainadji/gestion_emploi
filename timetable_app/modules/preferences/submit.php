<?php
require_once '../../includes/config.php';
requireRole('teacher');

$user_id = $_SESSION['user_id'];
$semester_id = 1; // Semestre 1 par défaut pour l'exemple

// Récupération de l'ID enseignant
$stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$user_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    require_once '../../includes/header.php';
    echo "<div class='card'><p style='color:red'>Erreur : Profil enseignant introuvable associé à ce compte.</p></div>";
    require_once '../../includes/footer.php';
    exit;
}
$teacher_id = $teacher['id'];

$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_slots = $_POST['slots'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // Suppression des anciennes préférences pour ce semestre
        $stmt = $pdo->prepare("DELETE FROM desiderata WHERE teacher_id = ? AND semester_id = ?");
        $stmt->execute([$teacher_id, $semester_id]);
        
        // Insertion des nouvelles préférences
        if (!empty($selected_slots)) {
            $stmt = $pdo->prepare("INSERT INTO desiderata (teacher_id, slot_id, semester_id, is_preferred) VALUES (?, ?, ?, 1)");
            foreach ($selected_slots as $slot_id) {
                $stmt->execute([$teacher_id, $slot_id, $semester_id]);
            }
        }
        
        $pdo->commit();
        $message = "Vos préférences ont été enregistrées avec succès.";
        logHistory($user_id, 'UPDATE', 'desiderata', $teacher_id, null, "Mise à jour préférences Semestre $semester_id");
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Erreur lors de l'enregistrement : " . $e->getMessage();
    }
}

// Récupération des données pour la grille
$slots = $pdo->query("SELECT * FROM slots ORDER BY id")->fetchAll();
$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$hours = $pdo->query("SELECT DISTINCT start_time, end_time FROM slots ORDER BY start_time")->fetchAll();

// Récupération des préférences existantes
$existing_prefs = [];
$stmt = $pdo->prepare("SELECT slot_id FROM desiderata WHERE teacher_id = ? AND semester_id = ?");
$stmt->execute([$teacher_id, $semester_id]);
while ($row = $stmt->fetch()) {
    $existing_prefs[] = $row['slot_id'];
}

// Récupération des cours déjà programmés pour affichage
$scheduled_courses = [];
$stmt = $pdo->prepare("
    SELECT t.slot_id, co.code, cl.name as class_name 
    FROM timetable t 
    JOIN courses co ON t.course_id = co.id 
    JOIN classes cl ON t.class_id = cl.id 
    WHERE t.teacher_id = ? AND t.semester_id = ?
");
$stmt->execute([$teacher_id, $semester_id]);
while ($row = $stmt->fetch()) {
    $scheduled_courses[$row['slot_id']][] = $row;
}

require_once '../../includes/header.php';
?>
<style>
    .pref-grid {
        display: grid;
        grid-template-columns: 100px repeat(6, 1fr);
        gap: 1px;
        background: #dee2e6;
        border: 1px solid #dee2e6;
        margin-top: 1rem;
    }
    .pref-cell, .grid-header {
        background: white;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        flex-direction: column;
    }
    .grid-header {
        font-weight: bold;
        background-color: #f8f9fa;
    }
    .pref-cell {
        cursor: pointer;
        transition: background-color 0.2s;
        min-height: 60px;
        user-select: none;
    }
    .pref-cell:hover {
        background-color: #f1f3f5;
    }
    .pref-cell.disabled {
        cursor: not-allowed;
        background-color: #e9ecef;
        opacity: 0.8;
    }
    .pref-cell.selected {
        background-color: #d4edda;
        color: #155724;
        font-weight: bold;
        border: 1px solid #c3e6cb;
    }
    .pref-cell input {
        display: none;
    }
    .scheduled-info {
        font-size: 0.75rem;
        color: #0056b3;
        margin-top: 4px;
        background: rgba(255,255,255,0.5);
        padding: 2px 4px;
        border-radius: 4px;
    }
    .grid-legend {
        display: flex;
        gap: 20px;
        margin-top: 15px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 4px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
    }
    .legend-color {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 3px;
        line-height: 20px;
    }
</style>

<div class="card">
    <h1>Mes Désidératas (Semestre 1)</h1>
    
    <?php if ($message): ?>
        <div class="alert" style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <p>Cliquez sur les créneaux horaires pour indiquer vos disponibilités préférentielles (en vert).</p>
    
    <form method="POST">
        <div class="pref-grid">
            <div class="grid-header">Horaire</div>
            <?php foreach ($days as $day): ?>
                <div class="grid-header"><?php echo $day; ?></div>
            <?php endforeach; ?>

            <?php foreach ($hours as $h): ?>
                <div class="grid-header"><?php echo $h['start_time'] . ' - ' . $h['end_time']; ?></div>
                <?php foreach ($days as $day): ?>
                    <?php 
                        // Recherche du slot correspondant
                        $current_slot_id = null;
                        foreach ($slots as $s) {
                            if ($s['day'] === $day && $s['start_time'] === $h['start_time']) {
                                $current_slot_id = $s['id'];
                                break;
                            }
                        }
                    ?>
                    <?php if ($current_slot_id): ?>
                        <?php 
                        $is_checked = in_array($current_slot_id, $existing_prefs); 
                        $has_course = isset($scheduled_courses[$current_slot_id]);
                        ?>
                        <label class="pref-cell <?php echo $is_checked ? 'selected' : ''; ?> <?php echo $has_course ? 'disabled' : ''; ?>">
                            <?php if (!$has_course): ?>
                                <input type="checkbox" name="slots[]" value="<?php echo $current_slot_id; ?>" <?php echo $is_checked ? 'checked' : ''; ?> onchange="this.parentElement.classList.toggle('selected')">
                                <span><?php echo $is_checked ? 'Préférentiel' : 'Disponible'; ?></span>
                            <?php else: ?>
                                <span>Occupé</span>
                            <?php endif; ?>
                            <?php if ($has_course): ?>
                                <div class="scheduled-info">
                                    <?php foreach ($scheduled_courses[$current_slot_id] as $sc): ?>
                                        <div><strong><?php echo htmlspecialchars($sc['code']); ?></strong></div>
                                        <div><?php echo htmlspecialchars($sc['class_name']); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </label>
                    <?php else: ?>
                        <div class="pref-cell" style="background: #eee; color: #999;">-</div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="grid-legend">
            <div class="legend-item">
                <span class="legend-color" style="background-color: #d4edda; border: 1px solid #c3e6cb;"></span>
                Créneau préférentiel
            </div>
            <div class="legend-item">
                <span class="legend-color" style="color: #0056b3; text-align: center; font-weight: bold; border: 1px solid #dee2e6; background-color: white;">UE</span>
                Cours programmé
            </div>
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn btn-primary">Enregistrer mes préférences</button>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>