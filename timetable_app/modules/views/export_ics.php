<?php
require_once __DIR__ . '/../../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch entries based on role
if ($role === 'student') {
    $stmt = $pdo->prepare("SELECT t.*, co.title, r.name as room_name, s.day, s.start_time, s.end_time 
                          FROM timetable t 
                          JOIN courses co ON t.course_id = co.id 
                          JOIN rooms r ON t.room_id = r.id 
                          JOIN slots s ON t.slot_id = s.id
                          JOIN students st ON t.class_id = st.class_id 
                          WHERE st.user_id = ?");
    $stmt->execute([$user_id]);
} else if ($role === 'teacher') {
    $stmt = $pdo->prepare("SELECT t.*, co.title, r.name as room_name, s.day, s.start_time, s.end_time 
                          FROM timetable t 
                          JOIN courses co ON t.course_id = co.id 
                          JOIN rooms r ON t.room_id = r.id 
                          JOIN slots s ON t.slot_id = s.id
                          JOIN teachers te ON t.teacher_id = te.id 
                          WHERE te.user_id = ?");
    $stmt->execute([$user_id]);
} else {
    $stmt = $pdo->prepare("SELECT t.*, co.title, r.name as room_name, s.day, s.start_time, s.end_time 
                          FROM timetable t 
                          JOIN courses co ON t.course_id = co.id 
                          JOIN rooms r ON t.room_id = r.id 
                          JOIN slots s ON t.slot_id = s.id");
    $stmt->execute();
}

$events = $stmt->fetchAll();

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="emploi_du_temps.ics"');

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//GestionEmploi//NONSGML v1.0//FR\r\n";
echo "CALSCALE:GREGORIAN\r\n";

foreach ($events as $event) {
    echo "BEGIN:VEVENT\r\n";
    echo "SUMMARY:" . $event['title'] . " (" . ($event['type'] ?? 'Cours') . ")\r\n";
    echo "LOCATION:" . $event['room_name'] . "\r\n";
    // Simplified date logic for demo (repeating weekly)
    echo "RRULE:FREQ=WEEKLY;BYDAY=" . strtoupper(substr($event['day'], 0, 2)) . "\r\n";
    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";
?>