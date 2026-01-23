<?php
require_once '../../includes/config.php';
requireRole('admin');

$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

// Vérification de l'enseignant
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    require_once '../../includes/header.php';
    echo "<div class='card'><p style='color:red'>Enseignant introuvable.</p><a href='manage.php' class='btn btn-secondary'>Retour</a></div>";
    require_once '../../includes/footer.php';
    exit;
}

$message = '';
$error = '';

// Traitement de l'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign') {
    $course_id = (int)$_POST['course_id'];
    $class_id = (int)$_POST['class_id'];
    
    // Vérification doublon
    $check = $pdo->prepare("SELECT id FROM teacher_courses WHERE teacher_id = ? AND course_id = ? AND class_id = ?");
    $check->execute([$teacher_id, $course_id, $class_id]);
    
    if ($check->fetch()) {
        $error = "Ce cours est déjà assigné à cet enseignant pour cette classe.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO teacher_courses (teacher_id, course_id, class_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$teacher_id, $course_id, $class_id])) {
            $message = "Cours assigné avec succès.";
            logHistory($_SESSION['user_id'], 'ASSIGN', 'teacher_courses', $pdo->lastInsertId(), null, "Assignation cours $course_id à enseignant $teacher_id");
        } else {
            $error = "Erreur lors de l'assignation.";
        }
    }
}

// Traitement de la suppression (Désassignation)
if (isset($_GET['remove'])) {
    $assign_id = (int)$_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM teacher_courses WHERE id = ?");
    $stmt->execute([$assign_id]);
    $message = "Assignation supprimée.";
    logHistory($_SESSION['user_id'], 'UNASSIGN', 'teacher_courses', $assign_id, null, "Suppression assignation ID $assign_id");
}

// Récupération des données
$stmt = $pdo->prepare("
    SELECT c.* 
    FROM courses c 
    JOIN programs p ON c.program_id = p.id 
    WHERE p.department_id = ? 
    ORDER BY c.code
");
$stmt->execute([$teacher['department_id']]);
$courses = $stmt->fetchAll();

$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();

// Liste des cours assignés
$stmt = $pdo->prepare("
    SELECT tc.id, c.code, c.title, cl.name as class_name 
    FROM teacher_courses tc 
    JOIN courses c ON tc.course_id = c.id 
    JOIN classes cl ON tc.class_id = cl.id 
    WHERE tc.teacher_id = ?
    ORDER BY cl.name, c.code
");
$stmt->execute([$teacher_id]);
$assigned_courses = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="card">
    <h1>Assignation des Cours : <?php echo htmlspecialchars($teacher['name']); ?></h1>
    <a href="manage.php" class="btn btn-secondary" style="margin-bottom: 15px; display: inline-block;">&larr; Retour à la liste</a>

    <?php if ($message): ?><div class="alert" style="background:#d4edda; color:#155724; padding:10px; margin-bottom:15px;"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert" style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:15px;"><?php echo $error; ?></div><?php endif; ?>

    <form method="POST" style="background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:20px; display:flex; gap:10px; align-items:flex-end;">
        <input type="hidden" name="action" value="assign">
        <div style="flex:1">
            <label style="display:block; margin-bottom:5px;">Cours (UE)</label>
            <select name="course_id" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <?php foreach ($courses as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code'] . ' - ' . $c['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:1">
            <label style="display:block; margin-bottom:5px;">Classe</label>
            <select name="class_id" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <?php foreach ($classes as $cl): ?>
                    <option value="<?php echo $cl['id']; ?>"><?php echo htmlspecialchars($cl['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success" style="padding:9px 15px;">Assigner</button>
    </form>

    <h3>Cours actuellement assignés</h3>
    <ul style="list-style:none; padding:0;">
        <?php foreach ($assigned_courses as $ac): ?>
            <li style="background:white; border:1px solid #eee; padding:10px; margin-bottom:5px; display:flex; justify-content:space-between; align-items:center;">
                <span><strong><?php echo htmlspecialchars($ac['code']); ?></strong> : <?php echo htmlspecialchars($ac['title']); ?> (<?php echo htmlspecialchars($ac['class_name']); ?>)</span>
                <a href="?teacher_id=<?php echo $teacher_id; ?>&remove=<?php echo $ac['id']; ?>" onclick="return confirm('Retirer ce cours ?');" style="color:#dc3545; text-decoration:none;">Retirer</a>
            </li>
        <?php endforeach; ?>
        <?php if (empty($assigned_courses)): ?>
            <li style="color:#666; font-style:italic;">Aucun cours assigné pour le moment.</li>
        <?php endif; ?>
    </ul>
</div>

<?php require_once '../../includes/footer.php'; ?>