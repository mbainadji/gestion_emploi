<?php
require_once __DIR__ . '/../../includes/config.php';

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

if ($type === 'programs') {
    $stmt = $pdo->prepare("SELECT id, name FROM programs WHERE department_id = ? ORDER BY name");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetchAll());
} elseif ($type === 'classes') {
    $stmt = $pdo->prepare("SELECT id, name FROM classes WHERE program_id = ? ORDER BY name");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetchAll());
}
?>