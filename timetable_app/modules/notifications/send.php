<?php
require_once '../../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message_status = '';
$error = '';

if ($role === 'student') {
    die("Accès refusé.");
}

// Récupération des cibles possibles
$teachers = [];
$classes = [];

if ($role === 'admin') {
    $teachers = $pdo->query("SELECT u.id, u.full_name FROM users u JOIN teachers t ON u.id = t.user_id ORDER BY u.full_name")->fetchAll();
    $classes = $pdo->query("SELECT id, name FROM classes ORDER BY name")->fetchAll();
} elseif ($role === 'teacher') {
    // Récupérer les classes de l'enseignant
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.name 
        FROM classes c 
        JOIN timetable t ON c.id = t.class_id 
        JOIN teachers te ON t.teacher_id = te.id 
        WHERE te.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $classes = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notif'])) {
    $msg_text = trim($_POST['message']);
    $target_type = $_POST['target_type']; // 'all_teachers', 'specific_teacher', 'class_students'
    
    if (empty($msg_text)) {
        $error = "Le message ne peut pas être vide.";
    } else {
        try {
            $pdo->beginTransaction();
            $prefix = ($role === 'admin') ? "[ADMIN] " : "[Enseignant: " . $_SESSION['full_name'] . "] ";
            $final_msg = $prefix . $msg_text;

            if ($role === 'admin') {
                if ($target_type === 'all_teachers') {
                    $stmt = $pdo->query("SELECT user_id FROM teachers");
                    while ($row = $stmt->fetch()) {
                        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$row['user_id'], $final_msg]);
                    }
                } elseif ($target_type === 'specific_teacher') {
                    $target_user_id = $_POST['teacher_id'];
                    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$target_user_id, $final_msg]);
                } elseif ($target_type === 'all_students') {
                    $stmt = $pdo->query("SELECT user_id FROM students");
                    while ($row = $stmt->fetch()) {
                        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$row['user_id'], $final_msg]);
                    }
                } elseif ($target_type === 'class_students') {
                    $class_id = $_POST['class_id'];
                    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE class_id = ?");
                    $stmt->execute([$class_id]);
                    while ($row = $stmt->fetch()) {
                        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$row['user_id'], $final_msg]);
                    }
                }
            } elseif ($role === 'teacher') {
                if ($target_type === 'class_students') {
                    $class_id = $_POST['class_id'];
                    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE class_id = ?");
                    $stmt->execute([$class_id]);
                    while ($row = $stmt->fetch()) {
                        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$row['user_id'], $final_msg]);
                    }
                }
            }

            $pdo->commit();
            $message_status = "Notification envoyée avec succès en temps réel.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 2rem auto;">
    <h1>Envoyer une Notification / Annonce</h1>
    
    <?php if ($message_status): ?><div class="alert alert-success" style="background: #ecfdf5; color: var(--success); padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; border: 1px solid #d1fae5;"><?php echo $message_status; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" style="background: #fef2f2; color: var(--danger); padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; border: 1px solid #fee2e2;"><?php echo $error; ?></div><?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Destinataires</label>
            <select name="target_type" id="target_type" class="form-control" onchange="updateTargetFields()" required>
                <?php if ($role === 'admin'): ?>
                    <option value="all_teachers">Tous les Enseignants</option>
                    <option value="specific_teacher">Un Enseignant Spécifique</option>
                    <option value="all_students">Tous les Étudiants</option>
                    <option value="class_students">Tous les Étudiants d'une Classe</option>
                <?php elseif ($role === 'teacher'): ?>
                    <option value="class_students">Tous les Étudiants d'une Classe</option>
                <?php endif; ?>
            </select>
        </div>

        <?php if ($role === 'admin'): ?>
            <div class="form-group" id="teacher_select_group" style="display:none;">
                <label>Sélectionner l'Enseignant</label>
                <select name="teacher_id" class="form-control">
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="class_select_group" style="display:none;">
                <label>Sélectionner la Classe</label>
                <select name="class_id" class="form-control">
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if ($role === 'teacher'): ?>
            <div class="form-group" id="class_select_group">
                <label>Sélectionner la Classe</label>
                <select name="class_id" class="form-control">
                    <?php if (empty($classes)): ?>
                        <option value="">Aucune classe assignée</option>
                    <?php endif; ?>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Message (Apparaîtra instantanément chez les destinataires)</label>
            <textarea name="message" class="form-control" rows="5" placeholder="Ex: Réunion de département demain à 10h en salle B1..." required></textarea>
        </div>

        <button type="submit" name="send_notif" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
            🚀 Envoyer la notification
        </button>
    </form>
</div>

<script>
function updateTargetFields() {
    const targetType = document.getElementById('target_type').value;
    const teacherGroup = document.getElementById('teacher_select_group');
    const classGroup = document.getElementById('class_select_group');
    
    if (teacherGroup) {
        teacherGroup.style.display = (targetType === 'specific_teacher') ? 'block' : 'none';
    }
    if (classGroup) {
        classGroup.style.display = (targetType === 'class_students') ? 'block' : 'none';
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
