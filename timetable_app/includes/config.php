<?php
// config.php - Configuration and DB connection (MySQL version)

$host = 'localhost';
$db   = 'timetable';
$user = 'succes';
$pass = 'succes237';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

session_start();

// Define base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Determine the app root directory relative to the document root
// This file is in /timetable_app/includes/config.php
$script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$app_root_index = strpos($script_path, '/modules/');
if ($app_root_index === false) {
    $app_root_index = strpos($script_path, '/index.php');
}

if ($app_root_index !== false) {
    $base_dir = substr($script_path, 0, $app_root_index);
} else {
    // Fallback if we are not in modules or index (e.g. root index)
    $base_dir = '/timetable_app'; 
}

$base_url = $protocol . $domainName . rtrim($base_dir, '/');

define('BASE_URL', $base_url);

// Basic helper functions
function redirect($url) {
    if (strpos($url, 'http') !== 0) {
        if (strpos($url, '/') === 0) {
            $url = BASE_URL . $url;
        } else {
            $url = BASE_URL . '/' . $url;
        }
    }
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/timetable_app/modules/accounts/login.php');
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        die("Access denied.");
    }
}

function logHistory($user_id, $action, $table, $record_id, $old_val = null, $new_val = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO history (user_id, action, table_name, record_id, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $table, $record_id, $old_val, $new_val]);
}

function isTimetableComplete($class_id, $semester_id) {
    global $pdo;
    // Get count of courses that should be scheduled for this class
    // For demo, we check if at least 5 entries exist or match teacher_courses count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM teacher_courses WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $assigned = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT course_id) FROM timetable WHERE class_id = ? AND semester_id = ?");
    $stmt->execute([$class_id, $semester_id]);
    $scheduled = $stmt->fetchColumn();
    
    return ($assigned > 0 && $scheduled >= $assigned);
}
?>
