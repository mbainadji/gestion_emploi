# Database Operations Documentation - Timetable System

This document outlines the advanced database features implemented in the MySQL database, including views, triggers, stored procedures, and transaction management.

## 1. Database Views
Views simplify complex queries by combining multiple tables into a single virtual table.

- **`v_timetable_details`**: Provides a comprehensive view of the timetable with human-readable names for courses, classes, teachers, and rooms.
  - *Usage*: `SELECT * FROM v_timetable_details;`
- **`v_teacher_workload`**: Summarizes the total number of sessions and estimated hours assigned to each teacher.
  - *Usage*: `SELECT * FROM v_teacher_workload;`

## 2. Triggers (Recycle Bin System)
Triggers are used to automate data logging and integrity.

- **`trg_timetable_after_delete`**: Automatically logs any deleted session from the `timetable` table into the `deleted_records_log` table.
- **`deleted_records_log`**: A table that stores deleted records in JSON format for potential recovery.
  - *Fields*: `table_name`, `original_id`, `data` (JSON), `deleted_at`.

## 3. Stored Procedures (Backup & Recovery)
Procedures for administrative database maintenance.

### Backup a Table
Creates a snapshot of an existing table with a timestamp.
```sql
CALL sp_backup_table('timetable');
```
This creates a table like `backup_timetable_20231027_120000`.

### Restore a Table
Restores a table from a specific backup. **Warning**: This drops the current table before restoring.
```sql
CALL sp_restore_table('timetable', 'backup_timetable_20231027_120000');
```

## 4. Transaction Management
Critical operations in the PHP application are wrapped in ACID-compliant transactions to ensure data consistency.

### Example in `manage.php`:
When adding or deleting a session, the following pattern is used:
1. `beginTransaction()`
2. Execute the primary operation (Insert/Delete).
3. Execute the secondary operation (Log History).
4. `commit()`
If any step fails, `rollBack()` is called to revert all changes.

## 5. Manual Backup Command
To perform a full database backup via CLI:
```bash
mysqldump -u succes -psucces237 timetable > full_backup.sql
```

## 6. Recovery Query
If you need to see what was deleted recently:
```sql
SELECT * FROM deleted_records_log ORDER BY deleted_at DESC LIMIT 10;
```
