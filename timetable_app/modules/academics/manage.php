<?php
require_once __DIR__ . '/../../includes/config.php';
requireRole('admin');

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_year'])) {
        $stmt = $pdo->prepare("INSERT INTO academic_years (name) VALUES (?)");
        $stmt->execute([$_POST['name']]);
    } elseif (isset($_POST['add_semester'])) {
        $stmt = $pdo->prepare("INSERT INTO semesters (academic_year_id, name) VALUES (?, ?)");
        $stmt->execute([$_POST['academic_year_id'], $_POST['name']]);
    } elseif (isset($_POST['add_dept'])) {
        $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
        $stmt->execute([$_POST['name']]);
    } elseif (isset($_POST['add_program'])) {
        $stmt = $pdo->prepare("INSERT INTO programs (department_id, name) VALUES (?, ?)");
        $stmt->execute([$_POST['department_id'], $_POST['name']]);
    } elseif (isset($_POST['add_class'])) {
        $stmt = $pdo->prepare("INSERT INTO classes (name, program_id, size, semester_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['program_id'], $_POST['size'], $_POST['semester_id']]);
    }
    redirect('/modules/academics/manage.php');
}

$years = $pdo->query("SELECT * FROM academic_years")->fetchAll();
$semesters = $pdo->query("SELECT s.*, ay.name as year_name FROM semesters s JOIN academic_years ay ON s.academic_year_id = ay.id")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
$programs = $pdo->query("SELECT p.*, d.name as dept_name FROM programs p JOIN departments d ON p.department_id = d.id")->fetchAll();
$classes = $pdo->query("SELECT c.*, p.name as program_name, s.name as semester_name FROM classes c JOIN programs p ON c.program_id = p.id JOIN semesters s ON c.semester_id = s.id")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <h2>Paramétrage Académique</h2>
    
    <h3>Années Académiques & Semestres</h3>
    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <table>
                <thead><tr><th>Année</th></tr></thead>
                <tbody><?php foreach($years as $y) echo "<tr><td>{$y['name']}</td></tr>"; ?></tbody>
            </table>
            <form method="POST">
                <input type="text" name="name" placeholder="Nouvelle année (ex: 2025/2026)" required>
                <button type="submit" name="add_year" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
        <div style="flex: 1;">
            <table>
                <thead><tr><th>Semestre</th><th>Année</th></tr></thead>
                <tbody><?php foreach($semesters as $s) echo "<tr><td>{$s['name']}</td><td>{$s['year_name']}</td></tr>"; ?></tbody>
            </table>
            <form method="POST">
                <select name="academic_year_id"><?php foreach($years as $y) echo "<option value='{$y['id']}'>{$y['name']}</option>"; ?></select>
                <input type="text" name="name" placeholder="Nom semestre" required>
                <button type="submit" name="add_semester" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <h3>Départements & Filières</h3>
    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <table>
                <thead><tr><th>Département</th></tr></thead>
                <tbody><?php foreach($departments as $d) echo "<tr><td>{$d['name']}</td></tr>"; ?></tbody>
            </table>
            <form method="POST">
                <input type="text" name="name" placeholder="Nom département" required>
                <button type="submit" name="add_dept" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
        <div style="flex: 1;">
            <table>
                <thead><tr><th>Filière</th><th>Département</th></tr></thead>
                <tbody><?php foreach($programs as $p) echo "<tr><td>{$p['name']}</td><td>{$p['dept_name']}</td></tr>"; ?></tbody>
            </table>
            <form method="POST">
                <select name="department_id"><?php foreach($departments as $d) echo "<option value='{$d['id']}'>{$d['name']}</option>"; ?></select>
                <input type="text" name="name" placeholder="Nom filière" required>
                <button type="submit" name="add_program" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <h3>Classes</h3>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Filière</th>
                <th>Semestre</th>
                <th>Effectif</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classes as $class): ?>
                <tr>
                    <td><?php echo $class['name']; ?></td>
                    <td><?php echo $class['program_name']; ?></td>
                    <td><?php echo $class['semester_name']; ?></td>
                    <td><?php echo $class['size']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Ajouter une Classe</h3>
    <form method="POST" id="addClassForm">
        <input type="hidden" name="add_class" value="1">
        <div>
            <label>Filière</label>
            <select name="program_id" id="program_id">
                <?php foreach ($programs as $p): ?>
                    <option value="<?php echo $p['id']; ?>" data-name="<?php echo $p['name']; ?>"><?php echo $p['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Niveau</label>
            <select id="level_select">
                <option value="L1">L1</option>
                <option value="L2">L2</option>
                <option value="L3">L3</option>
                <option value="M1">M1</option>
                <option value="M2">M2</option>
            </select>
        </div>
        <div>
            <label>Nom de la classe (Généré automatiquement)</label>
            <input type="text" name="name" id="class_name" required>
        </div>
        <div>
            <label>Semestre</label>
            <select name="semester_id">
                <?php foreach ($semesters as $s): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?> (<?php echo $s['year_name']; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Effectif</label>
            <input type="number" name="size" required value="100">
        </div>
        <button type="submit" class="btn btn-success">Ajouter</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const programSelect = document.getElementById('program_id');
    const levelSelect = document.getElementById('level_select');
    const nameInput = document.getElementById('class_name');

    function updateClassName() {
        const selectedOption = programSelect.options[programSelect.selectedIndex];
        const programName = selectedOption.getAttribute('data-name');
        const level = levelSelect.value;
        
        if (programName === 'ICT4D') {
            nameInput.value = 'ICT4D-' + level;
        } else {
            nameInput.value = level;
        }
    }

    programSelect.addEventListener('change', updateClassName);
    levelSelect.addEventListener('change', updateClassName);
    
    // Initial call
    updateClassName();
});
</script>

<div class="card">
    <h3>Départements & Filières</h3>
    <p>Gérez les départements et filières ici (simplifié pour la démo).</p>
    <!-- In a full app, more CRUD here -->
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
