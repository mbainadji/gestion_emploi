<?php
require_once __DIR__ . '/../../includes/config.php';

$error = '';
$message = '';

// Fetch data for dropdowns
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? 'student';
    
    $dept_id = $_POST['department_id'] ?? null;
    $prog_id = $_POST['program_id'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    $room_id = $_POST['room_id'] ?? null;

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $error = "Ce nom d'utilisateur est déjà pris.";
    } else {
        try {
            $pdo->beginTransaction();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role, $full_name]);
            $user_id = $pdo->lastInsertId();

            if ($role === 'teacher') {
                $stmt = $pdo->prepare("INSERT INTO teachers (user_id, name, department_id, program_id, room_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $full_name, $dept_id, $prog_id, $room_id]);
            } else if ($role === 'student') {
                $stmt = $pdo->prepare("INSERT INTO students (user_id, department_id, program_id, class_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $dept_id, $prog_id, $class_id]);
            }

            $pdo->commit();
            $message = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la création du compte : " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 700px; margin: 3rem auto; padding: 3rem;">
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <h2 style="font-size: 2rem; font-weight: 800; color: var(--text);">Rejoignez la Plateforme</h2>
        <p style="color: var(--text-muted);">Créez votre compte pour commencer la planification</p>
    </div>

    <?php if ($error): ?>
        <div style="background: #fef2f2; color: var(--danger); padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; border: 1px solid #fee2e2;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div style="background: #ecfdf5; color: var(--success); padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; border: 1px solid #d1fae5;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" id="registerForm" class="grid-form">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-group">
                <label>Nom complet</label>
                <input type="text" name="full_name" placeholder="Ex: Jean Dupont" required>
            </div>
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" placeholder="jdupont" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1rem;">
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label>Type de profil</label>
                <select name="role" id="roleSelect" onchange="toggleFields()">
                    <option value="student">Étudiant</option>
                    <option value="teacher">Enseignant</option>
                </select>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 2rem 0;">

        <div id="common-fields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-group">
                <label>Département</label>
                <select name="department_id" id="deptSelect" required onchange="loadPrograms(this.value)">
                    <option value="">-- Choisir --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Filière</label>
                <select name="program_id" id="progSelect" required onchange="loadClasses(this.value)">
                    <option value="">-- Choisir --</option>
                </select>
            </div>
        </div>

        <div id="student-fields" style="margin-top: 1rem;">
            <div class="form-group">
                <label>Niveau / Classe</label>
                <select name="class_id" id="classSelect">
                    <option value="">-- Choisir --</option>
                </select>
            </div>
        </div>

        <div id="teacher-fields" style="display:none; margin-top: 1rem;">
            <div class="form-group">
                <label>Salle de bureau (Optionnel)</label>
                <select name="room_id">
                    <option value="">-- Choisir --</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-top: 2rem; font-size: 1.1rem;">Créer mon compte</button>
    </form>
    
    <div style="margin-top: 2rem; text-align: center; font-size: 0.95rem; color: var(--text-muted);">
        Vous avez déjà un compte ? <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Connectez-vous</a>
    </div>
</div>

<script>
function toggleFields() {
    const role = document.getElementById('roleSelect').value;
    const studentFields = document.getElementById('student-fields');
    const teacherFields = document.getElementById('teacher-fields');
    const classSelect = document.getElementById('classSelect');

    if (role === 'teacher') {
        studentFields.style.display = 'none';
        teacherFields.style.display = 'block';
        classSelect.required = false;
    } else {
        studentFields.style.display = 'block';
        teacherFields.style.display = 'none';
        classSelect.required = true;
    }
}

async function loadPrograms(deptId) {
    const progSelect = document.getElementById('progSelect');
    progSelect.innerHTML = '<option value="">-- Chargement... --</option>';
    
    if (!deptId) {
        progSelect.innerHTML = '<option value="">-- Choisir --</option>';
        return;
    }

    const response = await fetch(`get_data.php?type=programs&id=${deptId}`);
    const programs = await response.json();
    
    progSelect.innerHTML = '<option value="">-- Choisir --</option>';
    programs.forEach(p => {
        progSelect.innerHTML += `<option value="${p.id}">${p.name}</option>`;
    });
}

async function loadClasses(progId) {
    const role = document.getElementById('roleSelect').value;
    if (role !== 'student') return;

    const classSelect = document.getElementById('classSelect');
    classSelect.innerHTML = '<option value="">-- Chargement... --</option>';
    
    if (!progId) {
        classSelect.innerHTML = '<option value="">-- Choisir --</option>';
        return;
    }

    const response = await fetch(`get_data.php?type=classes&id=${progId}`);
    const classes = await response.json();
    
    classSelect.innerHTML = '<option value="">-- Choisir --</option>';
    classes.forEach(c => {
        classSelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
    });
}

// Initial call
toggleFields();
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
