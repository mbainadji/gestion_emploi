-- Examples of Database Operations Actions

-- 1. View the full timetable with all details
SELECT * FROM v_timetable_details;

-- 2. Check teacher workload statistics
SELECT * FROM v_teacher_workload;

-- 3. Backup the timetable table before a major change
CALL sp_backup_table('timetable');

-- 4. See the list of backups created
SHOW TABLES LIKE 'backup_%';

-- 5. Restore the timetable from a backup
-- Replace 'backup_timetable_TIMESTAMP' with an actual table name from step 4
-- CALL sp_restore_table('timetable', 'backup_timetable_20231027_120000');

-- 6. View recently deleted sessions (Recycle Bin)
SELECT * FROM deleted_records_log;

-- 7. Recovery query: Restore a specific deleted session manually
-- Note: This is an example of how to use the JSON data from deleted_records_log
-- INSERT INTO timetable (class_id, course_id, teacher_id, room_id, slot_id, type)
-- SELECT 
--    data->>'$.class_id', 
--    data->>'$.course_id', 
--    data->>'$.teacher_id', 
--    data->>'$.room_id', 
--    data->>'$.slot_id', 
--    data->>'$.type'
-- FROM deleted_records_log WHERE original_id = 21;

-- 8. Run a full database backup (Command line)
-- mysqldump -u succes -psucces237 timetable > full_backup.sql
