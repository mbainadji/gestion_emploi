-- Database Improvements: Views and Triggers

-- 1. Views
-- Full Timetable View
CREATE OR REPLACE VIEW v_timetable_full AS
SELECT 
    t.id AS timetable_id,
    ay.name AS academic_year,
    sem.name AS semester,
    d.name AS department,
    p.name AS program,
    cl.name AS class_name,
    c.title AS course_name,
    c.code AS course_code,
    tea.name AS teacher_name,
    r.name AS room_name,
    s.day,
    s.start_time,
    s.end_time,
    t.type AS session_type
FROM timetable t
JOIN semesters sem ON t.semester_id = sem.id
JOIN academic_years ay ON sem.academic_year_id = ay.id
JOIN classes cl ON t.class_id = cl.id
JOIN programs p ON cl.program_id = p.id
JOIN departments d ON p.department_id = d.id
JOIN courses c ON t.course_id = c.id
JOIN teachers tea ON t.teacher_id = tea.id
JOIN rooms r ON t.room_id = r.id
JOIN slots s ON t.slot_id = s.id;

-- Teacher Workload View
CREATE OR REPLACE VIEW v_teacher_workload AS
SELECT 
    tea.id AS teacher_id,
    tea.name AS teacher_name,
    COUNT(t.id) AS total_sessions
FROM teachers tea
LEFT JOIN timetable t ON tea.id = t.teacher_id
GROUP BY tea.id, tea.name;

-- 2. Triggers
-- Log deletions from timetable to a history table
CREATE TABLE IF NOT EXISTS timetable_deleted (
    id INT,
    class_id INT,
    course_id INT,
    teacher_id INT,
    slot_id INT,
    room_id INT,
    semester_id INT,
    type VARCHAR(20),
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TRIGGER IF EXISTS before_timetable_delete;
DELIMITER //
CREATE TRIGGER before_timetable_delete
BEFORE DELETE ON timetable
FOR EACH ROW
BEGIN
    INSERT INTO timetable_deleted (id, class_id, course_id, teacher_id, slot_id, room_id, semester_id, type)
    VALUES (OLD.id, OLD.class_id, OLD.course_id, OLD.teacher_id, OLD.slot_id, OLD.room_id, OLD.semester_id, OLD.type);
END; //
DELIMITER ;
