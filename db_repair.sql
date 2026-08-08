-- ClassSense database repair + integrity migration
-- Run in SQL Server Management Studio (or sqlcmd) against the ClassSense database.
-- Safe to re-run: every statement is idempotent.
-- Steps: (1) report current orphans, (2) clean one-time mess, (3) add FK cascades, (4) verify.

:setvar PRINT_COUNTS ON
PRINT '======================================== ClassSense DB Repair START';

-------------------------------------------------------------------------------------------------
-- STEP 1: REPORT CURRENT ORPHAN COUNTS (dry-run snapshot, no data changed yet)
-------------------------------------------------------------------------------------------------
PRINT '--[1] Orphan snapshot before cleanup --';

SELECT 'orphan_class_students' AS check_name, COUNT(*) AS cnt
FROM class_students cs LEFT JOIN users u ON u.uid = cs.student_uid WHERE u.uid IS NULL;

SELECT 'orphan_attendance' AS check_name, COUNT(*) AS cnt
FROM attendance a LEFT JOIN users u ON u.uid = a.student_uid WHERE u.uid IS NULL;

SELECT 'orphan_sessions' AS check_name, COUNT(*) AS cnt
FROM sessions s LEFT JOIN users u ON u.uid = s.uid WHERE u.uid IS NULL;

SELECT 'expired_sessions' AS check_name, COUNT(*) AS cnt
FROM sessions WHERE expires_at < GETDATE();

SELECT 'orphan_notifications' AS check_name, COUNT(*) AS cnt
FROM notifications n LEFT JOIN users u ON u.uid = n.recipient_uid WHERE u.uid IS NULL;

SELECT 'stale_live_sessions' AS check_name, COUNT(*) AS cnt
FROM classes WHERE session_active = 1 AND (session_expires_at IS NULL OR session_expires_at < GETDATE());

-------------------------------------------------------------------------------------------------
-- STEP 2: ONE-TIME CLEANUP
-------------------------------------------------------------------------------------------------
PRINT '--[2] Cleanup: orphaned + expired rows and stale live sessions --';

BEGIN TRANSACTION;

-- Remove enrollments for users that no longer exist
DELETE cs FROM class_students cs LEFT JOIN users u ON u.uid = cs.student_uid WHERE u.uid IS NULL;

-- Remove attendance for users that no longer exist (history is lost on hard delete by design)
DELETE a FROM attendance a LEFT JOIN users u ON u.uid = a.student_uid WHERE u.uid IS NULL;

-- Remove sessions for users that no longer exist (tokens are useless without the account)
DELETE s FROM sessions s LEFT JOIN users u ON u.uid = s.uid WHERE u.uid IS NULL;

-- Remove expired tokens
DELETE FROM sessions WHERE expires_at < GETDATE();

-- Remove notifications for users that no longer exist
DELETE n FROM notifications n LEFT JOIN users u ON u.uid = n.recipient_uid WHERE u.uid IS NULL;

-- Reset stale live attendance sessions so teachers can start a fresh session
UPDATE classes
SET session_active = 0,
    session_id = NULL,
    current_nonce = NULL,
    last_nonce = NULL,
    nonce_issued_at = NULL,
    session_expires_at = NULL,
    session_mode = 'open'
WHERE session_active = 1
  AND (session_expires_at IS NULL OR session_expires_at < GETDATE());

COMMIT TRANSACTION;
PRINT '--[2] Cleanup complete --';

-------------------------------------------------------------------------------------------------
-- STEP 3: ADD / UPGRADE FOREIGN KEY CONSTRAINTS (cascades on user/class delete)
-- Cleanup above guarantees no orphans remain, so these ALTERs will succeed.
-- Existing FK names are discovered dynamically and dropped before re-adding with CASCADE.
-------------------------------------------------------------------------------------------------
PRINT '--[3] Applying FK cascade constraints --';

-- 3a. class_students.class_id -> classes(id) : upgrade to ON DELETE CASCADE
IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE parent_object_id = OBJECT_ID('class_students')
           AND referenced_object_id = OBJECT_ID('classes'))
BEGIN
    DECLARE @fk_cs_class NVARCHAR(200) = (SELECT TOP 1 name FROM sys.foreign_keys
        WHERE parent_object_id = OBJECT_ID('class_students')
          AND referenced_object_id = OBJECT_ID('classes'));
    EXEC('ALTER TABLE class_students DROP CONSTRAINT [' + @fk_cs_class + ']');
END
ALTER TABLE class_students
    ADD CONSTRAINT fk_class_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE;

-- 3b. class_students.student_uid -> users(uid) ON DELETE CASCADE
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_class_students_student' AND parent_object_id = OBJECT_ID('class_students'))
    ALTER TABLE class_students ADD CONSTRAINT fk_class_students_student FOREIGN KEY (student_uid) REFERENCES users(uid) ON DELETE CASCADE;

-- 3c. attendance.student_uid -> users(uid) ON DELETE CASCADE
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_attendance_student' AND parent_object_id = OBJECT_ID('attendance'))
    ALTER TABLE attendance ADD CONSTRAINT fk_attendance_student FOREIGN KEY (student_uid) REFERENCES users(uid) ON DELETE CASCADE;

-- 3d. attendance.class_id -> classes(id) ON DELETE CASCADE
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_attendance_class' AND parent_object_id = OBJECT_ID('attendance'))
    ALTER TABLE attendance ADD CONSTRAINT fk_attendance_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE;

-- 3e. classes.teacher_uid -> users(uid) NO ACTION (BLOCK, do not cascade).
--     We intentionally do NOT cascade teacher->classes: SQL Server forbids the
--     multiple-cascade-paths that would arise (user -> classes -> class_students
--     vs user -> class_students). Instead the app cleans a teacher's classes+events
--     before deleting the teacher (see api/fetch.php DELETE). NO ACTION guarantees
--     that any future direct-DB delete of a teacher without cleaning children FAILS
--     instead of orphaning classes/enrollments/attendance.
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_classes_teacher' AND parent_object_id = OBJECT_ID('classes'))
BEGIN
    IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE parent_object_id = OBJECT_ID('classes')
               AND referenced_object_id = OBJECT_ID('users'))
    BEGIN
        DECLARE @fk_classes_teacher NVARCHAR(200) = (SELECT TOP 1 name FROM sys.foreign_keys
            WHERE parent_object_id = OBJECT_ID('classes')
              AND referenced_object_id = OBJECT_ID('users'));
        EXEC('ALTER TABLE classes DROP CONSTRAINT [' + @fk_classes_teacher + ']');
    END
    ALTER TABLE classes ADD CONSTRAINT fk_classes_teacher FOREIGN KEY (teacher_uid) REFERENCES users(uid);
    PRINT '-- added fk_classes_teacher (NO ACTION) --';
END

-- 3f. events.teacher_uid -> users(uid) ON DELETE CASCADE
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_events_teacher' AND parent_object_id = OBJECT_ID('events'))
    ALTER TABLE events ADD CONSTRAINT fk_events_teacher FOREIGN KEY (teacher_uid) REFERENCES users(uid) ON DELETE CASCADE;

-- 3g. sessions.uid -> users(uid) ON DELETE CASCADE (removes tokens on user delete)
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_sessions_user' AND parent_object_id = OBJECT_ID('sessions'))
    ALTER TABLE sessions ADD CONSTRAINT fk_sessions_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE;

-- 3h. notifications.recipient_uid -> users(uid) ON DELETE CASCADE
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_notifications_recipient' AND parent_object_id = OBJECT_ID('notifications'))
    ALTER TABLE notifications ADD CONSTRAINT fk_notifications_recipient FOREIGN KEY (recipient_uid) REFERENCES users(uid) ON DELETE CASCADE;

PRINT '--[3] FK constraints applied --';

-------------------------------------------------------------------------------------------------
-- STEP 4: VERIFY — all orphan counts must be ZERO
-------------------------------------------------------------------------------------------------
PRINT '--[4] Verification after migration --';

SELECT 'orphan_class_students' AS check_name, COUNT(*) AS cnt
FROM class_students cs LEFT JOIN users u ON u.uid = cs.student_uid WHERE u.uid IS NULL;

SELECT 'orphan_attendance' AS check_name, COUNT(*) AS cnt
FROM attendance a LEFT JOIN users u ON u.uid = a.student_uid WHERE u.uid IS NULL;

SELECT 'orphan_sessions' AS check_name, COUNT(*) AS cnt
FROM sessions s LEFT JOIN users u ON u.uid = s.uid WHERE u.uid IS NULL;

SELECT 'orphan_notifications' AS check_name, COUNT(*) AS cnt
FROM notifications n LEFT JOIN users u ON u.uid = n.recipient_uid WHERE u.uid IS NULL;

SELECT 'stale_live_sessions' AS check_name, COUNT(*) AS cnt
FROM classes WHERE session_active = 1 AND (session_expires_at IS NULL OR session_expires_at < GETDATE());

PRINT '--[5] FK list now enforced --';
SELECT fk.name AS foreign_key, OBJECT_NAME(fk.parent_object_id) AS table_name,
       COL_NAME(fkc.parent_column_id, fkc.parent_object_id) AS column_name,
       OBJECT_NAME(fk.referenced_object_id) AS references_table,
       COL_NAME(fkc.referenced_column_id, fkc.referenced_object_id) AS referenced_column
FROM sys.foreign_keys fk
JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
WHERE fk.parent_object_id IN (OBJECT_ID('class_students'), OBJECT_ID('attendance'),
                              OBJECT_ID('classes'), OBJECT_ID('events'),
                              OBJECT_ID('sessions'), OBJECT_ID('notifications'))
ORDER BY table_name;

PRINT '======================================== ClassSense DB Repair END';
