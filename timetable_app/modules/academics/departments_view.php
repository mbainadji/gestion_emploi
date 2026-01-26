<?php
require_once __DIR__ . '/../../includes/config.php';

$step = $_GET['step'] ?? 'dept';
$dept_id = $_GET['dept_id'] ?? null;
$program_id = $_GET['program_id'] ?? null;

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

$programs = [];
if ($dept_id) {
    $stmt = $pdo->prepare("SELECT * FROM programs WHERE department_id = ? ORDER BY name");
    $stmt->execute([$dept_id]);
    $programs = $stmt->fetchAll();
}

$classes = [];
if ($program_id) {
    // We group by name to show unique levels across semesters if needed, 
    // but usually, we want to see the classes for the current semester.
    $stmt = $pdo->prepare("SELECT DISTINCT name, id FROM classes WHERE program_id = ? ORDER BY name");
    $stmt->execute([$program_id]);
    $classes = $stmt->fetchAll();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <nav style="margin-bottom: 2rem; font-size: 0.9rem; color: var(--text-muted);">
        <a href="departments_view.php" style="color: var(--primary); font-weight: 600;">Départements</a>
        <?php if ($dept_id): ?>
            <span style="margin: 0 0.5rem;">/</span>
            <a href="departments_view.php?step=program&dept_id=<?php echo $dept_id; ?>" style="color: var(--primary); font-weight: 600;">
                <?php 
                $d_name = '';
                foreach($departments as $d) if($d['id'] == $dept_id) $d_name = $d['name'];
                echo htmlspecialchars($d_name);
                ?>
            </a>
        <?php endif; ?>
        <?php if ($program_id): ?>
            <span style="margin: 0 0.5rem;">/</span>
            <span style="font-weight: 600;">
                <?php 
                foreach($programs as $p) if($p['id'] == $program_id) echo htmlspecialchars($p['name']);
                ?>
            </span>
        <?php endif; ?>
    </nav>

    <?php if ($step === 'dept'): ?>
        <h2>Sélectionnez un Département</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
            <?php foreach ($departments as $dept): ?>
                <a href="?step=program&dept_id=<?php echo $dept['id']; ?>" class="card" style="text-decoration: none; border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s; text-align: center; padding: 2rem;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏢</div>
                    <div style="font-weight: 700; color: var(--text); font-size: 1.1rem;"><?php echo htmlspecialchars($dept['name']); ?></div>
                </a>
            <?php endforeach; ?>
        </div>

    <?php elseif ($step === 'program'): ?>
        <h2>Filières du département</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
            <?php if (empty($programs)): ?>
                <p>Aucune filière trouvée pour ce département.</p>
            <?php endif; ?>
            <?php foreach ($programs as $prog): ?>
                <a href="?step=level&dept_id=<?php echo $dept_id; ?>&program_id=<?php echo $prog['id']; ?>" class="card" style="text-decoration: none; border: 1px solid var(--border); text-align: center; padding: 2rem;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🎓</div>
                    <div style="font-weight: 700; color: var(--text); font-size: 1.1rem;"><?php echo htmlspecialchars($prog['name']); ?></div>
                </a>
            <?php endforeach; ?>
        </div>

    <?php elseif ($step === 'level'): ?>
        <h2>Niveaux de formation</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
            <?php if (empty($classes)): ?>
                <div class="card" style="grid-column: 1/-1; text-align: center; padding: 3rem; border: 2px dashed var(--border);">
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Aucune classe/niveau n'est encore configuré pour cette filière.</p>
                    <?php if (hasRole('admin')): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/academics/manage.php" class="btn btn-primary">Configurer les classes</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($classes as $c): ?>
                    <?php
                    // Check if timetable entries exist for this class
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM timetable WHERE class_id = ?");
                    $stmt->execute([$c['id']]);
                    $has_timetable = $stmt->fetchColumn() > 0;
                    ?>
                    <div class="card" style="text-decoration: none; border: 1px solid var(--border); text-align: center; padding: 1.5rem;">
                        <div style="font-weight: 800; font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($c['name']); ?></div>
                        
                        <?php if ($has_timetable): ?>
                            <a href="<?php echo BASE_URL; ?>/modules/views/view.php?department_id=<?php echo $dept_id; ?>&program_id=<?php echo $program_id; ?>&class_id=<?php echo $c['id']; ?>" 
                               class="btn btn-primary" style="font-size: 0.8rem; width: 100%;">Voir l'emploi du temps</a>
                        <?php else: ?>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">Aucun cours programmé</div>
                            <?php if (hasRole('admin')): ?>
                                <a href="<?php echo BASE_URL; ?>/modules/scheduling/manage.php?class_id=<?php echo $c['id']; ?>" 
                                   class="btn btn-success" style="font-size: 0.8rem; width: 100%;">Créer l'emploi du temps</a>
                            <?php elseif (hasRole('teacher')): ?>
                                <a href="<?php echo BASE_URL; ?>/modules/scheduling/catchup.php" 
                                   class="btn btn-secondary" style="font-size: 0.8rem; width: 100%;">Soumettre un cours</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary);
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>