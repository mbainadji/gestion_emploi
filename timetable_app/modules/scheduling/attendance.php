<?php
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$message = '';

if ($role !== 'teacher' && $role !== 'admin') {
    die("Accès refusé.");
}

$date = $_GET['date'] ?? date('Y-m-d');
$view = $_GET['view'] ?? ($role === 'admin' ? 'teacher_report' : 'mark_students');

// For Teachers: Mark their own presence + students
if ($role === 'teacher' && isset($_POST['mark_teacher_present'])) {
    $tid = $_POST['timetable_id'];
    $stmt = $pdo->prepare("INSERT INTO teacher_attendance (timetable_id, date, status) VALUES (?, ?, 'present') ON DUPLICATE KEY UPDATE status='present', signed_at=CURRENT_TIMESTAMP");
    $stmt->execute([$tid, $date]);
    $message = "Votre présence a été enregistrée.";
}

// For Admin: View Teacher Attendance Report
$teacher_report = [];
if ($role === 'admin' && $view === 'teacher_report') {
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as class_name, co.title as course_title, te.name as teacher_name, 
               s.start_time, s.end_time, ta.status as teacher_status, ta.signed_at
        FROM timetable t
        JOIN classes c ON t.class_id = c.id
        JOIN courses co ON t.course_id = co.id
        JOIN slots s ON t.slot_id = s.id
        JOIN teachers te ON t.teacher_id = te.id
        LEFT JOIN teacher_attendance ta ON t.id = ta.timetable_id AND ta.date = ?
        WHERE (t.date_passage = ? OR t.date_passage IS NULL)
        ORDER BY s.start_time ASC
    ");
    $stmt->execute([$date, $date]);
    $teacher_report = $stmt->fetchAll();
}

// Handle Student Attendance Saving
if (isset($_POST['save_attendance'])) {
    $session_id = $_POST['session_id'];
    $statuses = $_POST['status'] ?? [];
    try {
        $pdo->beginTransaction();
        
        // Mark teacher present automatically when they mark students
        if ($role === 'teacher') {
            $stmt = $pdo->prepare("INSERT INTO teacher_attendance (timetable_id, date, status) VALUES (?, ?, 'present') ON DUPLICATE KEY UPDATE status='present'");
            $stmt->execute([$session_id, $date]);
        }

        $stmt = $pdo->prepare("DELETE FROM attendance WHERE timetable_id = ? AND date = ?");
        $stmt->execute([$session_id, $date]);
        
        $stmt = $pdo->prepare("INSERT INTO attendance (timetable_id, student_id, status, date) VALUES (?, ?, ?, ?)");
        foreach ($statuses as $student_id => $status) {
            $stmt->execute([$session_id, $student_id, $status, $date]);
        }
        $pdo->commit();
        $message = "Émargement enregistré avec succès.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Erreur : " . $e->getMessage();
    }
}

// Get sessions list for dropdown
if ($role === 'teacher') {
    $stmt = $pdo->prepare("SELECT t.*, c.name as class_name, co.title as course_title, s.start_time, s.end_time 
                          FROM timetable t 
                          JOIN classes c ON t.class_id = c.id 
                          JOIN courses co ON t.course_id = co.id 
                          JOIN slots s ON t.slot_id = s.id
                          JOIN teachers te ON t.teacher_id = te.id
                          WHERE te.user_id = ? AND (t.date_passage = ? OR t.date_passage IS NULL)");
    $stmt->execute([$user_id, $date]);
} else {
    $stmt = $pdo->prepare("SELECT t.*, c.name as class_name, co.title as course_title, s.start_time, s.end_time 
                          FROM timetable t 
                          JOIN classes c ON t.class_id = c.id 
                          JOIN courses co ON t.course_id = co.id 
                          JOIN slots s ON t.slot_id = s.id
                          WHERE (t.date_passage = ? OR t.date_passage IS NULL)");
    $stmt->execute([$date]);
}
$sessions = $stmt->fetchAll();

$session_id = $_GET['session_id'] ?? null;
$students = [];
$existing_teacher_presence = false;

if ($session_id) {
    $stmt = $pdo->prepare("SELECT s.*, u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.class_id = (SELECT class_id FROM timetable WHERE id = ?)");
    $stmt->execute([$session_id]);
    $students = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT status FROM teacher_attendance WHERE timetable_id = ? AND date = ?");
    $stmt->execute([$session_id, $date]);
    $existing_teacher_presence = $stmt->fetchColumn() === 'present';

    $existing_attendance = [];
    $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE timetable_id = ? AND date = ?");
    $stmt->execute([$session_id, $date]);
    while ($row = $stmt->fetch()) {
        $existing_attendance[$row['student_id']] = $row['status'];
    }
}
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>Gestion de l'Émargement</h1>
        <div class="btn-group">
            <?php if ($role === 'admin'): ?>
                <a href="?view=teacher_report&date=<?php echo $date; ?>" class="btn <?php echo $view === 'teacher_report' ? 'btn-primary' : 'btn-secondary'; ?>">Suivi Enseignants</a>
            <?php endif; ?>
            <a href="?view=mark_students&date=<?php echo $date; ?>" class="btn <?php echo $view === 'mark_students' ? 'btn-primary' : 'btn-secondary'; ?>">Émargement Étudiants</a>
        </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>

    <form method="GET" style="display: flex; gap: 1rem; margin-bottom: 2rem; align-items: flex-end; background: #f8f9fa; padding: 1rem; border-radius: 8px;">
        <input type="hidden" name="view" value="<?php echo $view; ?>">
        <div class="form-group">
            <label>Date du jour</label>
            <input type="date" name="date" value="<?php echo $date; ?>" class="form-control" onchange="this.form.submit()">
        </div>
        <?php if ($view === 'mark_students'): ?>
            <div class="form-group">
                <label>Sélectionner la séance</label>
                <select name="session_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Choisir une séance --</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $session_id == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo $s['start_time'] . ' - ' . $s['class_name'] . ' - ' . $s['course_title']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </form>

    <?php if ($view === 'teacher_report'): ?>
        <h3>Rapport de présence des Enseignants - <?php echo date('d/m/Y', strtotime($date)); ?></h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Horaire</th>
                    <th>Enseignant</th>
                    <th>Cours / Classe</th>
                    <th>Statut</th>
                    <th>Heure de signature</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teacher_report as $row): ?>
                    <tr>
                        <td><?php echo $row['start_time']; ?> - <?php echo $row['end_time']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['teacher_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['course_title']); ?> (<?php echo $row['class_name']; ?>)</td>
                        <td>
                            <?php if ($row['teacher_status'] === 'present'): ?>
                                <span class="badge" style="background: var(--success); color: white;">PRÉSENT</span>
                            <?php else: ?>
                                <span class="badge" style="background: var(--danger); color: white;">ABSENT</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $row['signed_at'] ? date('H:i:s', strtotime($row['signed_at'])) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php elseif ($view === 'mark_students' && $session_id): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3>Liste des Étudiants</h3>
            <?php if ($role === 'teacher' && !$existing_teacher_presence): ?>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="timetable_id" value="<?php echo $session_id; ?>">
                    <button type="submit" name="mark_teacher_present" class="btn btn-success">Valider ma présence (Signature)</button>
                </form>
            <?php elseif ($existing_teacher_presence): ?>
                <span class="badge" style="background: var(--success); color: white;">Vous êtes marqué PRÉSENT</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($students)): ?>
            <form method="POST">
                <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Présence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $st): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($st['full_name']); ?></td>
                                <td>
                                    <select name="status[<?php echo $st['id']; ?>]" class="form-control">
                                        <option value="present" <?php echo ($existing_attendance[$st['id']] ?? 'present') === 'present' ? 'selected' : ''; ?>>Présent</option>
                                        <option value="absent" <?php echo ($existing_attendance[$st['id']] ?? '') === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                        <option value="late" <?php echo ($existing_attendance[$st['id']] ?? '') === 'late' ? 'selected' : ''; ?>>En retard</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="save_attendance" class="btn btn-primary" style="margin-top: 1rem;">Enregistrer l'émanrgement</button>
            </form>
        <?php else: ?>
            <p>Aucun étudiant trouvé pour cette classe.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
