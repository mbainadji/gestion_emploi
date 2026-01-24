<?php
require_once '../../includes/config.php';
requireRole('admin');

$semester_id = 1; // Par défaut Semestre 1

// Détection des conflits
$conflicts = [];

// 1. Conflits de Salle : Une salle utilisée par plusieurs cours sur le même créneau (et même semaine)
$sql_room = "
    SELECT t1.id as id1, t2.id as id2, 
           r.name as room_name, 
           s.day, s.start_time, s.end_time,
           c1.code as course1, c2.code as course2,
           t1.week_number as w1, t2.week_number as w2
    FROM timetable t1
    JOIN timetable t2 ON t1.room_id = t2.room_id AND t1.slot_id = t2.slot_id AND t1.semester_id = t2.semester_id AND t1.id < t2.id
    JOIN rooms r ON t1.room_id = r.id
    JOIN slots s ON t1.slot_id = s.id
    JOIN courses c1 ON t1.course_id = c1.id
    JOIN courses c2 ON t2.course_id = c2.id
    WHERE t1.semester_id = $semester_id 
      AND (t1.week_number IS NULL OR t2.week_number IS NULL OR t1.week_number = t2.week_number)
";
$stmt = $pdo->query($sql_room);
while ($row = $stmt->fetch()) {
    $conflicts[] = [
        'type' => 'Salle',
        'entity' => $row['room_name'],
        'details' => "Conflit entre {$row['course1']} et {$row['course2']} le {$row['day']} {$row['start_time']}-{$row['end_time']}",
        'ids' => [$row['id1'], $row['id2']]
    ];
}

// 2. Conflits d'Enseignant : Un enseignant sur plusieurs cours en même temps
$sql_teacher = "
    SELECT t1.id as id1, t2.id as id2, 
           te.name as teacher_name, 
           s.day, s.start_time, s.end_time,
           c1.code as course1, c2.code as course2
    FROM timetable t1
    JOIN timetable t2 ON t1.teacher_id = t2.teacher_id AND t1.slot_id = t2.slot_id AND t1.semester_id = t2.semester_id AND t1.id < t2.id
    JOIN teachers te ON t1.teacher_id = te.id
    JOIN slots s ON t1.slot_id = s.id
    JOIN courses c1 ON t1.course_id = c1.id
    JOIN courses c2 ON t2.course_id = c2.id
    WHERE t1.semester_id = $semester_id 
      AND (t1.week_number IS NULL OR t2.week_number IS NULL OR t1.week_number = t2.week_number)
";
$stmt = $pdo->query($sql_teacher);
while ($row = $stmt->fetch()) {
    $conflicts[] = [
        'type' => 'Enseignant',
        'entity' => $row['teacher_name'],
        'details' => "Enseigne {$row['course1']} et {$row['course2']} simultanément le {$row['day']} {$row['start_time']}-{$row['end_time']}",
        'ids' => [$row['id1'], $row['id2']]
    ];
}

// 3. Conflits de Classe : Une classe (ou groupe) a plusieurs cours en même temps
// Logique : Conflit si (Même classe) ET (Même créneau) ET (Semaines compatibles) ET
//           ( (Groupe A = Groupe B) OU (Un des groupes est NULL/Vide = Classe Entière) )
//           SAUF si (Groupe A != Groupe B) et aucun n'est vide (ex: G1 vs G2 est OK)
$sql_class = "
    SELECT t1.id as id1, t2.id as id2, 
           cl.name as class_name, 
           s.day, s.start_time, s.end_time,
           c1.code as course1, c2.code as course2,
           t1.group_name as g1, t2.group_name as g2
    FROM timetable t1
    JOIN timetable t2 ON t1.class_id = t2.class_id AND t1.slot_id = t2.slot_id AND t1.semester_id = t2.semester_id AND t1.id < t2.id
    JOIN classes cl ON t1.class_id = cl.id
    JOIN slots s ON t1.slot_id = s.id
    JOIN courses c1 ON t1.course_id = c1.id
    JOIN courses c2 ON t2.course_id = c2.id
    WHERE t1.semester_id = $semester_id 
      AND (t1.week_number IS NULL OR t2.week_number IS NULL OR t1.week_number = t2.week_number)
      AND (
          (t1.group_name = t2.group_name) OR 
          (t1.group_name IS NULL OR t1.group_name = '') OR 
          (t2.group_name IS NULL OR t2.group_name = '')
      )
";
$stmt = $pdo->query($sql_class);
while ($row = $stmt->fetch()) {
    $g1 = $row['g1'] ? $row['g1'] : 'Classe Entière';
    $g2 = $row['g2'] ? $row['g2'] : 'Classe Entière';
    $conflicts[] = [
        'type' => 'Classe',
        'entity' => $row['class_name'],
        'details' => "Conflit pour {$row['class_name']} ($g1 / $g2) : {$row['course1']} et {$row['course2']} le {$row['day']} {$row['start_time']}-{$row['end_time']}",
        'ids' => [$row['id1'], $row['id2']]
    ];
}

require_once '../../includes/header.php';
?>

<div class="card">
    <h1>Arbitrage des Conflits</h1>
    <p>Détection automatique des incohérences dans l'emploi du temps (Semestre <?php echo $semester_id; ?>).</p>

    <?php if (empty($conflicts)): ?>
        <div class="alert" style="background:#d4edda; color:#155724; padding:15px; border-radius:4px;">
            <strong>Aucun conflit détecté !</strong> L'emploi du temps semble cohérent sur les contraintes de base (Salles, Enseignants).
        </div>
    <?php else: ?>
        <div class="alert" style="background:#f8d7da; color:#721c24; padding:15px; border-radius:4px; margin-bottom:20px;">
            <strong><?php echo count($conflicts); ?> conflit(s) détecté(s).</strong> Une intervention est nécessaire.
        </div>

        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8f9fa; text-align:left;">
                    <th style="padding:10px; border-bottom:2px solid #dee2e6;">Type</th>
                    <th style="padding:10px; border-bottom:2px solid #dee2e6;">Entité concernée</th>
                    <th style="padding:10px; border-bottom:2px solid #dee2e6;">Détails du conflit</th>
                    <th style="padding:10px; border-bottom:2px solid #dee2e6;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conflicts as $c): ?>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #dee2e6;">
                        <?php
                        $badge_color = '#6c757d'; // Default grey
                        if ($c['type'] == 'Salle') $badge_color = '#dc3545'; // Red
                        elseif ($c['type'] == 'Enseignant') $badge_color = '#ffc107'; // Yellow/Orange
                        elseif ($c['type'] == 'Classe') $badge_color = '#007bff'; // Blue
                        ?>
                        <span class="badge" style="background:<?php echo $badge_color; ?>; color:white; padding:3px 8px; border-radius:4px; font-size:0.85em;">
                            <?php echo htmlspecialchars($c['type']); ?>
                        </span>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid #dee2e6;"><strong><?php echo htmlspecialchars($c['entity']); ?></strong></td>
                    <td style="padding:10px; border-bottom:1px solid #dee2e6;"><?php echo htmlspecialchars($c['details']); ?></td>
                    <td style="padding:10px; border-bottom:1px solid #dee2e6;">
                        <a href="../scheduling/manage.php?highlight=<?php echo implode(',', $c['ids']); ?>" class="btn btn-primary" style="padding:5px 10px; font-size:0.9em; text-decoration:none;">Résoudre</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div style="margin-top:30px;">
        <a href="../scheduling/manage.php" class="btn btn-secondary">Accéder à l'outil de Planification &rarr;</a>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>