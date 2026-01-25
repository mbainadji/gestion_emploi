<?php
// config.php - Configuration and DB connection (MySQL version)

// Dev: afficher les erreurs pour diagnostiquer les 500 en local
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier que l'extension PDO MySQL est présente
if (!extension_loaded('pdo_mysql')) {
    die("Extension pdo_mysql manquante. Installez et activez l'extension PDO MySQL.\n");
}

// --- Configuration de la base de données ---
// Il est FORTEMENT recommandé d'utiliser des variables d'environnement pour la configuration.
// Ne mettez jamais de mots de passe en clair dans ce fichier.
// Créez un fichier .env à la racine de votre projet et chargez-le (par exemple avec docker-compose).
//
// Exemple de contenu pour un fichier .env :
// DB_HOST=localhost
// DB_NAME=timetable
// DB_USER=succes
// DB_PASS=succes237

$host = getenv('DB_HOST') ?: 'localhost'; // 'db' si vous utilisez le docker-compose ci-dessous
$db   = getenv('DB_NAME') ?: 'timetable';
$user = getenv('DB_USER') ?: 'succes';
$pass = getenv('DB_PASS') ?: 'succes237';
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

// --- Définition de l'URL de base ---
// La détection automatique peut être fragile.
// Pour une meilleure portabilité, vous pouvez aussi la définir via une variable d'environnement.
// Define base URL dynamically
// Determine protocol reliably even if some $_SERVER keys are missing
$https = $_SERVER['HTTPS'] ?? '';
$server_port = $_SERVER['SERVER_PORT'] ?? '';
$protocol = ((($https !== '') && $https !== 'off') || ($server_port == 443)) ? "https://" : "http://";
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

// If base_dir computed is empty (e.g. script at /index.php) ensure we point to the app folder
if (empty($base_dir) || $base_dir === '') {
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
