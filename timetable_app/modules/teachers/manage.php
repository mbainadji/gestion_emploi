<?php
require_once '../../includes/config.php';
requireRole('admin');

$message = '';
$error = '';

// Traitement de l'ajout et de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $name = trim($_POST['name']);
    $department_id = (int)$_POST['department_id'];
    $program_id = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
    
    if (!empty($name)) {
        if ($_POST['action'] === 'add') {
            try {
                $pdo->beginTransaction();
                
                $username = strtolower(str_replace(' ', '', $name));
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $check->execute([$username]);
                if ($check->fetch()) {
                    $username .= rand(10, 99);
                }
                
                $password_hash = password_hash('pass123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, 'teacher', ?)");
                $stmt->execute([$username, $password_hash, $name]);
                $user_id = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("INSERT INTO teachers (user_id, name, department_id, program_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $name, $department_id, $program_id]);
                
                $pdo->commit();
                $message = "Enseignant ajouté. Compte : <strong>$username</strong> / pass123";
                logHistory($_SESSION['user_id'], 'CREATE', 'teachers', $pdo->lastInsertId(), null, "Ajout $name");
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur : " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE teachers SET name = ?, department_id = ?, program_id = ? WHERE id = ?");
                $stmt->execute([$name, $department_id, $program_id, $id]);
                
                $uid = $pdo->query("SELECT user_id FROM teachers WHERE id = $id")->fetchColumn();
                if ($uid) {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                    $stmt->execute([$name, $uid]);
                }
                $pdo->commit();
                $message = "Enseignant modifié.";
                logHistory($_SESSION['user_id'], 'UPDATE', 'teachers', $id, null, "Modif $name");
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

// Filtre par filière
$selected_program = $_GET['program_id'] ?? null;
$selected_class = $_GET['class_id'] ?? null;
$selected_semester = $_GET['semester_id'] ?? null;
$search = $_GET['search'] ?? '';

$sql = "SELECT DISTINCT t.*, d.name as dept_name, u.username, p_main.name as main_program,
        (SELECT GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') 
         FROM teacher_courses tc 
         JOIN courses c ON tc.course_id = c.id 
         JOIN programs p ON c.program_id = p.id 
         WHERE tc.teacher_id = t.id) as programs_taught
        FROM teachers t 
        LEFT JOIN departments d ON t.department_id = d.id 
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN programs p_main ON t.program_id = p_main.id";

$conditions = [];
$params = [];

// Filtrage avancé : Filière + Classe (Niveau)
if ($selected_program && $selected_class) {
    // Enseignants intervenant dans la Filière ET la Classe spécifiées
    $conditions[] = "t.id IN (
        SELECT tc.teacher_id 
        FROM teacher_courses tc 
        JOIN courses c ON tc.course_id = c.id 
        WHERE c.program_id = ? AND tc.class_id = ?
    )";
    $params[] = $selected_program;
    $params[] = $selected_class;
} elseif ($selected_program) {
    // Comportement existant : Filière principale OU Cours dans la filière
    $conditions[] = "(t.program_id = ? OR t.id IN (SELECT tc.teacher_id FROM teacher_courses tc JOIN courses c ON tc.course_id = c.id WHERE c.program_id = ?))";
    $params[] = $selected_program;
    $params[] = $selected_program;
} elseif ($selected_class) {
    $conditions[] = "t.id IN (SELECT tc.teacher_id FROM teacher_courses tc WHERE tc.class_id = ?)";
    $params[] = $selected_class;
}

if (!empty($search)) {
    $conditions[] = "(t.name LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY t.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$teachers_list = $stmt->fetchAll();

// Récupération des filières (GROUP BY pour éviter les doublons visuels)
$programs = $pdo->query("SELECT * FROM programs GROUP BY name ORDER BY name")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="card">
    <h1 style="margin-bottom: 2rem;">Gestion des Enseignants</h1>
    
    <?php if ($message): ?><div class="alert" style="background:var(--success); color:white; padding:10px; border-radius:4px; margin-bottom:1rem;"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert" style="background:var(--danger); color:white; padding:10px; border-radius:4px; margin-bottom:1rem;"><?php echo $error; ?></div><?php endif; ?>

    <!-- Section Filtrage par Filière -->
    <div class="card" style="background: var(--bg); border: 1px solid var(--border); box-shadow: none; margin-bottom: 2rem;">
        <h3 style="margin-top: 0; font-size: 1rem; color: var(--primary);">Choisir une Filière</h3>
        
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
            <a href="manage.php" class="btn <?php echo !$selected_program ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 0.9rem;">Toutes les filières</a>
            <?php foreach ($programs as $p): ?>
                <a href="?program_id=<?php echo $p['id']; ?>" class="btn <?php echo $selected_program == $p['id'] ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 0.9rem; background-color: <?php echo $selected_program == $p['id'] ? '' : '#e9ecef'; ?>; color: <?php echo $selected_program == $p['id'] ? '' : '#333'; ?>; border: 1px solid #ced4da;">
                    <?php echo htmlspecialchars($p['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <?php if($selected_program): ?><input type="hidden" name="program_id" value="<?php echo $selected_program; ?>"><?php endif; ?>
            
            <div style="flex: 0 0 150px;">
                <label>Semestre</label>
                <select name="semester_id" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                    <option value="">-- Tous --</option>
                    <option value="1" <?php echo $selected_semester == 1 ? 'selected' : ''; ?>>Semestre 1</option>
                    <option value="2" <?php echo $selected_semester == 2 ? 'selected' : ''; ?>>Semestre 2</option>
                </select>
            </div>

            <div style="flex: 0 0 150px;">
                <label>Niveau / Classe</label>
                <select name="class_id" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                    <option value="">-- Toutes --</option>
                    <?php foreach ($classes as $cl): ?>
                        <option value="<?php echo $cl['id']; ?>" <?php echo $selected_class == $cl['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cl['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 200px;">
                <label>Recherche rapide</label>
                <input type="text" name="search" placeholder="Nom ou utilisateur..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Rechercher</button>
            <a href="manage.php" class="btn btn-secondary">Tout voir</a>
        </form>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Liste des Enseignants <?php echo $selected_program ? "(".count($teachers_list).")" : ""; ?></h2>
        <button onclick="document.getElementById('addForm').style.display='block'" class="btn btn-success">+ Ajouter un Enseignant</button>
    </div>

    <!-- Formulaire d'ajout (masqué par défaut) -->
    <div id="addForm" class="card" style="display: none; background: #fff; border: 2px solid var(--primary); margin-bottom: 2rem;">
        <h3>Nouveau Profil Enseignant</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Nom complet</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Département de rattachement</label>
                    <select name="department_id" required>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Filière principale (Optionnel)</label>
                    <select name="program_id">
                        <option value="">-- Aucune --</option>
                        <?php foreach ($programs as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-success">Enregistrer</button>
                <button type="button" onclick="document.getElementById('addForm').style.display='none'" class="btn btn-secondary">Annuler</button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nom de l'Enseignant</th>
                    <th>Nom d'utilisateur</th>
                    <th>Département</th>
                    <th>Filière Principale</th>
                    <th>Filières enseignées</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teachers_list)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">Aucun enseignant trouvé pour cette sélection.</td></tr>
                <?php endif; ?>
                <?php foreach ($teachers_list as $t): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($t['name']); ?></strong></td>
                    <td><code style="background: var(--bg); padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($t['username'] ?? '-'); ?></code></td>
                    <td><?php echo htmlspecialchars($t['dept_name'] ?? '-'); ?></td>
                    <td><span class="badge" style="background: #e9ecef; color: #333;"><?php echo htmlspecialchars($t['main_program'] ?? '-'); ?></span></td>
                    <td><small><?php echo htmlspecialchars($t['programs_taught'] ?? '-'); ?></small></td>
                    <td style="display: flex; gap: 0.5rem;">
                        <a href="assign.php?teacher_id=<?php echo $t['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">Assigner Cours</a>
                        <a href="?delete=<?php echo $t['id']; ?>" onclick="return confirm('Supprimer ?')" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
