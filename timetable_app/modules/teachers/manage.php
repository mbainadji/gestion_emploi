<?php
require_once '../../includes/config.php';
requireRole('admin');

$message = '';
$error = '';

// Traitement de l'ajout et de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $name = trim($_POST['name']);
    $department_id = (int)$_POST['department_id'];
    // Pour simplifier, on crée un utilisateur par défaut si non sélectionné, ou on pourrait lier à un user existant.
    // Ici, on suppose que l'admin crée le profil enseignant et que le compte utilisateur est géré à part ou automatiquement.
    // Pour cette démo, on va créer un user automatique : username = nom en minuscule, password = pass123
    
    if (!empty($name)) {
        if ($_POST['action'] === 'add') {
            try {
                $pdo->beginTransaction();
                
                // 1. Création du compte utilisateur
                $username = strtolower(str_replace(' ', '', $name));
                // Check if username exists
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $check->execute([$username]);
                if ($check->fetch()) {
                    $username .= rand(10, 99); // Suffixe aléatoire si doublon
                }
                
                $password_hash = password_hash('pass123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, 'teacher', ?)");
                $stmt->execute([$username, $password_hash, $name]);
                $user_id = $pdo->lastInsertId();
                
                // 2. Création du profil enseignant
                $stmt = $pdo->prepare("INSERT INTO teachers (user_id, name, department_id) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $name, $department_id]);
                
                $pdo->commit();
                $message = "Enseignant ajouté avec succès. Compte utilisateur : <strong>$username</strong> / pass123";
                logHistory($_SESSION['user_id'], 'CREATE', 'teachers', $pdo->lastInsertId(), null, "Ajout enseignant $name");
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur : " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            try {
                $pdo->beginTransaction();
                // Mise à jour du profil enseignant
                $stmt = $pdo->prepare("UPDATE teachers SET name = ?, department_id = ? WHERE id = ?");
                $stmt->execute([$name, $department_id, $id]);
                
                // Mise à jour du nom complet dans la table users (optionnel mais recommandé pour la cohérence)
                // On récupère d'abord le user_id
                $uid = $pdo->query("SELECT user_id FROM teachers WHERE id = $id")->fetchColumn();
                if ($uid) {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                    $stmt->execute([$name, $uid]);
                }
                $pdo->commit();
                $message = "Enseignant modifié avec succès.";
                logHistory($_SESSION['user_id'], 'UPDATE', 'teachers', $id, null, "Modification enseignant $name");
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur lors de la modification : " . $e->getMessage();
            }
        }
    } else {
        $error = "Le nom est obligatoire.";
    }
}

// Traitement de la suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Vérification des dépendances (cours programmés, désidératas)
    $check1 = $pdo->prepare("SELECT COUNT(*) FROM timetable WHERE teacher_id = ?");
    $check1->execute([$id]);
    $check2 = $pdo->prepare("SELECT COUNT(*) FROM teacher_courses WHERE teacher_id = ?");
    $check2->execute([$id]);
    
    if ($check1->fetchColumn() > 0 || $check2->fetchColumn() > 0) {
        $error = "Impossible de supprimer cet enseignant car il a des cours programmés ou assignés.";
    } else {
        // Suppression propre : d'abord le profil teacher, puis le user (optionnel, ici on garde le user pour l'historique ou on le supprime ?)
        // On va supprimer le profil teacher uniquement pour l'instant pour ne pas casser l'historique user.
        $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Profil enseignant supprimé.";
        logHistory($_SESSION['user_id'], 'DELETE', 'teachers', $id, null, "Suppression enseignant ID $id");
    }
}

// Récupération des données
$search = $_GET['search'] ?? '';
$sql = "SELECT t.*, d.name as dept_name, u.username 
        FROM teachers t 
        LEFT JOIN departments d ON t.department_id = d.id 
        LEFT JOIN users u ON t.user_id = u.id";
$params = [];
if (!empty($search)) {
    $sql .= " WHERE t.name LIKE ? OR u.username LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY t.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$teachers = $stmt->fetchAll();

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

// Préparation pour l'édition
$edit_teacher = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_teacher = $stmt->fetch();
}

require_once '../../includes/header.php';
?>

<div class="card">
    <h1>Gestion des Enseignants</h1>
    
    <?php if ($message): ?><div class="alert" style="background:#d4edda; color:#155724; padding:10px; margin-bottom:15px;"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert" style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:15px;"><?php echo $error; ?></div><?php endif; ?>

    <form method="POST" class="form-inline" style="background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:20px; display:flex; gap:10px; align-items:flex-end;">
        <input type="hidden" name="action" value="<?php echo $edit_teacher ? 'edit' : 'add'; ?>">
        <?php if ($edit_teacher): ?>
            <input type="hidden" name="id" value="<?php echo $edit_teacher['id']; ?>">
        <?php endif; ?>
        
        <div style="flex:1">
            <label style="display:block; margin-bottom:5px;">Nom complet</label>
            <input type="text" name="name" value="<?php echo $edit_teacher ? htmlspecialchars($edit_teacher['name']) : ''; ?>" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
        </div>
        <div style="flex:1">
            <label style="display:block; margin-bottom:5px;">Département</label>
            <select name="department_id" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <?php foreach ($departments as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo ($edit_teacher && $edit_teacher['department_id'] == $d['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success" style="padding:9px 15px;"><?php echo $edit_teacher ? 'Modifier' : 'Ajouter'; ?></button>
        <?php if ($edit_teacher): ?>
            <a href="manage.php" class="btn btn-secondary" style="padding:9px 15px; text-decoration:none; background:#6c757d; color:white; border-radius:4px;">Annuler</a>
        <?php endif; ?>
    </form>

    <!-- Formulaire de recherche -->
    <form method="GET" action="" style="margin-top: 20px; margin-bottom: 20px; display: flex; gap: 10px;">
        <input type="text" name="search" placeholder="Rechercher par nom ou utilisateur..." value="<?php echo htmlspecialchars($search); ?>" style="flex-grow: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        <button type="submit" class="btn btn-primary" style="padding: 9px 15px;">Rechercher</button>
        <a href="manage.php" class="btn btn-secondary" style="padding: 9px 15px; text-decoration:none; background:#6c757d; color:white; border-radius:4px;">Réinitialiser</a>
    </form>

    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#f8f9fa; text-align:left;">
                <th style="padding:10px; border-bottom:2px solid #dee2e6;">Nom</th>
                <th style="padding:10px; border-bottom:2px solid #dee2e6;">Utilisateur</th>
                <th style="padding:10px; border-bottom:2px solid #dee2e6;">Département</th>
                <th style="padding:10px; border-bottom:2px solid #dee2e6;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teachers as $t): ?>
            <tr>
                <td style="padding:10px; border-bottom:1px solid #dee2e6;"><strong><?php echo htmlspecialchars($t['name']); ?></strong></td>
                <td style="padding:10px; border-bottom:1px solid #dee2e6; color:#666;"><?php echo htmlspecialchars($t['username'] ?? 'N/A'); ?></td>
                <td style="padding:10px; border-bottom:1px solid #dee2e6;"><?php echo htmlspecialchars($t['dept_name'] ?? '-'); ?></td>
                <td style="padding:10px; border-bottom:1px solid #dee2e6;">
                    <!-- Lien vers l'affectation des cours (à venir) -->
                    <a href="assign.php?teacher_id=<?php echo $t['id']; ?>" style="color:#007bff; text-decoration:none; margin-right:10px;">Assigner Cours</a>
                    <a href="?edit=<?php echo $t['id']; ?>" style="color:#ffc107; text-decoration:none; margin-right:10px;">Modifier</a>
                    <a href="?delete=<?php echo $t['id']; ?>" onclick="return confirm('Supprimer cet enseignant ?');" style="color:#dc3545; text-decoration:none;">Supprimer</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>