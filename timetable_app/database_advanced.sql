-- Database Improvements: Views, Triggers, Transactions and Backup Logic

-- 1. RECYCLE BIN FOR DELETED RECORDS
CREATE TABLE IF NOT EXISTS deleted_records_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100),
    original_id INT,
    data JSON,
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_by INT
);

-- 2. VIEWS FOR SIMPLIFIED QUERIES
-- View for full timetable details
CREATE OR REPLACE VIEW v_timetable_details AS
SELECT 
    t.id AS session_id,
    c.title AS course_title,
    c.code AS course_code,
    cl.name AS class_name,
    tc.name AS teacher_name,
    r.name AS room_name,
    s.day,
    s.start_time,
    s.end_time,
    t.type AS session_type,
    t.week_number,
    t.date_passage
FROM timetable t
JOIN courses c ON t.course_id = c.id
JOIN classes cl ON t.class_id = cl.id
JOIN teachers tc ON t.teacher_id = tc.id
JOIN rooms r ON t.room_id = r.id
JOIN slots s ON t.slot_id = s.id;

-- View for teacher workload
CREATE OR REPLACE VIEW v_teacher_workload AS
SELECT 
    tc.id AS teacher_id,
    tc.name AS teacher_name,
    COUNT(t.id) AS total_sessions,
    SUM(CASE WHEN t.type = 'CM' THEN 1.5 ELSE 1 END) AS estimated_hours -- Assuming CM is 1.5h, others 1h
FROM teachers tc
LEFT JOIN timetable t ON tc.id = t.teacher_id
GROUP BY tc.id, tc.name;

-- 3. TRIGGERS
-- Trigger to log deletion in timetable
DELIMITER //
CREATE TRIGGER trg_timetable_after_delete
AFTER DELETE ON timetable
FOR EACH ROW
BEGIN
    INSERT INTO deleted_records_log (table_name, original_id, data)
    VALUES ('timetable', OLD.id, JSON_OBJECT(
        'class_id', OLD.class_id,
        'course_id', OLD.course_id,
        'teacher_id', OLD.teacher_id,
        'room_id', OLD.room_id,
        'slot_id', OLD.slot_id,
        'type', OLD.type,
        'date_passage', OLD.date_passage
    ));
END //
DELIMITER ;

-- 4. STORED PROCEDURES FOR BACKUP & RECOVERY
DELIMITER //

-- Procedure to backup a specific table
CREATE PROCEDURE sp_backup_table(IN source_table VARCHAR(100))
BEGIN
    SET @backup_name = CONCAT('backup_', source_table, '_', DATE_FORMAT(NOW(), '%Y%m%d_%H%i%s'));
    SET @query = CONCAT('CREATE TABLE ', @backup_name, ' SELECT * FROM ', source_table);
    PREPARE stmt FROM @query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END //

-- Procedure to recover a table from a backup (example usage)
-- CALL sp_restore_table('timetable', 'backup_timetable_20231027_120000');
CREATE PROCEDURE sp_restore_table(IN target_table VARCHAR(100), IN backup_table VARCHAR(100))
BEGIN
    -- Drop target if exists (careful!)
    SET @query1 = CONCAT('DROP TABLE IF EXISTS ', target_table);
    PREPARE stmt1 FROM @query1;
    EXECUTE stmt1;
    DEALLOCATE PREPARE stmt1;
    
    -- Restore from backup
    SET @query2 = CONCAT('CREATE TABLE ', target_table, ' SELECT * FROM ', backup_table);
    PREPARE stmt2 FROM @query2;
    EXECUTE stmt2;
    DEALLOCATE PREPARE stmt2;
END //

DELIMITER ;
