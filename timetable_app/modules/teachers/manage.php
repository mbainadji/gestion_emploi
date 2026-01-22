<?php
require_once __DIR__ . '/../../includes/config.php';
requireRole('admin');

if (isset($_POST['add_teacher'])) {
    // Create user first
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, 'teacher', ?)");
    $stmt->execute([$_POST['username'], $_POST['password'], $_POST['name']]);
    $user_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO teachers (user_id, name, email, department_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $_POST['name'], $_POST['email'], $_POST['department_id']]);
    redirect('/modules/teachers/manage.php');
}

if (isset($_POST['add_course'])) {
    $stmt = $pdo->prepare("INSERT INTO courses (code, title, program_id) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['code'], $_POST['title'], $_POST['program_id']]);
    redirect('/modules/teachers/manage.php');
}

if (isset($_POST['assign_course'])) {
    $stmt = $pdo->prepare("INSERT INTO teacher_courses (teacher_id, course_id, class_id) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['teacher_id'], $_POST['course_id'], $_POST['class_id']]);
    redirect('/modules/teachers/manage.php');
}

$teachers = $pdo->query("SELECT * FROM teachers")->fetchAll();
$courses = $pdo->query("SELECT * FROM courses")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
$programs = $pdo->query("SELECT * FROM programs")->fetchAll();
$assignments = $pdo->query("SELECT tc.*, t.name as teacher_name, c.code as course_code, cl.name as class_name FROM teacher_courses tc JOIN teachers t ON tc.teacher_id = t.id JOIN courses c ON tc.course_id = c.id JOIN classes cl ON tc.class_id = cl.id")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Gestion des Enseignants</h2>
    <table>
        <thead><tr><th>Nom</th><th>Email</th></tr></thead>
        <tbody>
            <?php foreach ($teachers as $t): ?>
                <tr><td><?php echo $t['name']; ?></td><td><?php echo $t['email']; ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h3>Ajouter un Enseignant</h3>
    <form method="POST">
        <input type="hidden" name="add_teacher" value="1">
        <div><label>Nom Complet</label><input type="text" name="name" required></div>
        <div><label>Username</label><input type="text" name="username" required></div>
        <div><label>Password</label><input type="password" name="password" required></div>
        <div><label>Email</label><input type="text" name="email"></div>
        <div>
            <label>Département</label>
            <select name="department_id">
                <?php foreach ($departments as $d): ?>
                    <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Ajouter</button>
    </form>
</div>

<div class="card">
    <h2>Gestion des UE</h2>
    <table>
        <thead><tr><th>Code</th><th>Intitulé</th></tr></thead>
        <tbody>
            <?php foreach ($courses as $c): ?>
                <tr><td><?php echo $c['code']; ?></td><td><?php echo $c['title']; ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h3>Ajouter une UE</h3>
    <form method="POST">
        <input type="hidden" name="add_course" value="1">
        <div><label>Code UE</label><input type="text" name="code" required></div>
        <div><label>Intitulé</label><input type="text" name="title" required></div>
        <div>
            <label>Filière</label>
            <select name="program_id">
                <?php foreach ($programs as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Ajouter</button>
    </form>
</div>

<div class="card">
    <h2>Affectations UE ↔ Enseignant ↔ Classe</h2>
    <table>
        <thead><tr><th>Enseignant</th><th>UE</th><th>Classe</th></tr></thead>
        <tbody>
            <?php foreach ($assignments as $a): ?>
                <tr><td><?php echo $a['teacher_name']; ?></td><td><?php echo $a['course_code']; ?></td><td><?php echo $a['class_name']; ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h3>Nouvelle Affectation</h3>
    <form method="POST">
        <input type="hidden" name="assign_course" value="1">
        <div>
            <label>Enseignant</label>
            <select name="teacher_id">
                <?php foreach ($teachers as $t): ?>
                    <option value="<?php echo $t['id']; ?>"><?php echo $t['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>UE</label>
            <select name="course_id">
                <?php foreach ($courses as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo $c['code']; ?> - <?php echo $c['title']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Classe</label>
            <select name="class_id">
                <?php foreach ($classes as $cl): ?>
                    <option value="<?php echo $cl['id']; ?>"><?php echo $cl['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Affecter</button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
