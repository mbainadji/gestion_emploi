<?php
require_once __DIR__ . '/../../includes/config.php';
requireRole('admin');

// 1. Effective hours per teacher (assuming each slot is 3 hours as per sample data)
$teacher_stats = $pdo->query("SELECT te.name, COUNT(t.id) * 3 as total_hours 
                             FROM teachers te 
                             LEFT JOIN timetable t ON te.id = t.teacher_id 
                             GROUP BY te.id 
                             ORDER BY total_hours DESC")->fetchAll();

// 2. Room occupancy rate
$total_slots = $pdo->query("SELECT COUNT(*) FROM slots")->fetchColumn();
$room_stats = [];
if ($total_slots > 0) {
    $stmt = $pdo->prepare("SELECT r.name, COUNT(t.id) as occupied_slots, 
                               (COUNT(t.id) * 100.0 / ?) as occupancy_rate
                               FROM rooms r 
                               LEFT JOIN timetable t ON r.id = t.room_id 
                               GROUP BY r.id");
    $stmt->execute([$total_slots]);
    $room_stats = $stmt->fetchAll();
} else {
    $rooms = $pdo->query("SELECT name FROM rooms")->fetchAll();
    foreach($rooms as $r) {
        $room_stats[] = ['name' => $r['name'], 'occupancy_rate' => 0];
    }
}

// 3. Distribution of session types
$session_type_stats = $pdo->query("SELECT type, COUNT(*) as count FROM timetable GROUP BY type")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="card">
    <h2>Statistiques & Rapports</h2>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
        <!-- Chart: Teacher Hours -->
        <div class="card" style="border: 1px solid var(--border);">
            <h3>Charge de travail par Enseignant</h3>
            <canvas id="teacherChart"></canvas>
        </div>

        <!-- Chart: Session Types -->
        <div class="card" style="border: 1px solid var(--border);">
            <h3>Répartition des Types de Sessions</h3>
            <canvas id="sessionTypeChart"></canvas>
        </div>

        <div class="card" style="border: 1px solid var(--border);">
            <h3>Heures Effectives par Enseignant</h3>
            <table class="table">
                <thead><tr><th>Enseignant</th><th>Total Heures</th></tr></thead>
                <tbody>
                    <?php foreach($teacher_stats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['name']); ?></td>
                            <td><strong><?php echo $stat['total_hours']; ?>h</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card" style="border: 1px solid var(--border);">
            <h3>Taux d'Occupation des Salles</h3>
            <table class="table">
                <thead><tr><th>Salle</th><th>Taux</th></tr></thead>
                <tbody>
                    <?php foreach($room_stats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['name']); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; height: 10px; background: #eee; border-radius: 5px;">
                                        <div style="width: <?php echo $stat['occupancy_rate']; ?>%; height: 100%; background: var(--primary); border-radius: 5px;"></div>
                                    </div>
                                    <span><?php echo round($stat['occupancy_rate'], 1); ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Teacher Hours Chart
    const teacherCtx = document.getElementById('teacherChart').getContext('2d');
    new Chart(teacherCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($teacher_stats, 'name')); ?>,
            datasets: [{
                label: 'Heures totales',
                data: <?php echo json_encode(array_column($teacher_stats, 'total_hours')); ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.6)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Session Type Distribution Chart
    const sessionTypeCtx = document.getElementById('sessionTypeChart').getContext('2d');
    new Chart(sessionTypeCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($session_type_stats, 'type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($session_type_stats, 'count')); ?>,
                backgroundColor: [
                    'rgba(79, 70, 229, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(239, 68, 68, 0.7)'
                ]
            }]
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>