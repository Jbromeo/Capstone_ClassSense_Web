-- ClassSense database: CREATE-or-UPDATE (upsert) maintenance script
-- Run in SQL Server Management Studio (or sqlcmd) against the ClassSense database.
-- Safe to re-run: every section checks IF EXISTS and UPDATES the row instead of
-- creating a duplicate. Sections whose key parameters are NULL are SKIPPED, so
-- pressing F5 on the whole file with default values does nothing.
--
-- HOW TO USE:
--   1. Fill in the parameters at the top of the section you need (NULL = "don't change").
--   2. Select that section in SSMS and press F5.
--   For IDENTITY tables (events, notifications, grade_components) leave @id = 0
--   to CREATE a new row; set the real id to UPDATE an existing row.
--
-- Sections (one per table):
--   1  users                 key: uid
--   2  classes               key: id
--   3  class_students        key: (class_id, student_uid)
--   4  attendance            key: (class_id, student_uid, date) -> updates the LATEST row (app's "latest wins" rule)
--   5  events                key: id
--   6  sessions              key: token
--   7  notifications         key: id
--   8  push_subscriptions    key: token
--   9  pre_approved_students key: student_id
--  10  grade_components      key: id
--  11  grades                key: (component_id, student_uid)
--  12  grade_weights         key: (class_id, category)
--  13  ai_insights           key: (student_uid, class_id)
--  14  verification          shows every upserted row

PRINT '======================================== ClassSense Upsert START';

--===============================================================================================
-- SECTION 1: users  (CREATE if uid does not exist, UPDATE otherwise)
--===============================================================================================
DECLARE @u_uid            VARCHAR(128)  = NULL;
DECLARE @u_username       NVARCHAR(255) = NULL;
DECLARE @u_password_hash  NVARCHAR(255) = NULL;
DECLARE @u_role           NVARCHAR(20)  = NULL;   -- 'admin' | 'teacher' | 'student'
DECLARE @u_first_name     NVARCHAR(255) = NULL;
DECLARE @u_last_name      NVARCHAR(255) = NULL;
DECLARE @u_student_id     NVARCHAR(50)  = NULL;
DECLARE @u_employee_id    NVARCHAR(50)  = NULL;
DECLARE @u_phone          NVARCHAR(20)  = NULL;
DECLARE @u_guardian_phone NVARCHAR(20)  = NULL;
DECLARE @u_push_enabled   INT           = NULL;

IF @u_uid IS NOT NULL AND @u_username IS NOT NULL
BEGIN
    IF EXISTS (SELECT 1 FROM users WHERE uid = @u_uid)
    BEGIN
        PRINT '-- users: uid exists -> UPDATE';
        UPDATE users SET
            username       = ISNULL(@u_username,       username),
            password_hash  = ISNULL(@u_password_hash,  password_hash),
            role           = ISNULL(@u_role,           role),
            first_name     = ISNULL(@u_first_name,     first_name),
            last_name      = ISNULL(@u_last_name,      last_name),
            student_id     = ISNULL(@u_student_id,     student_id),
            employee_id    = ISNULL(@u_employee_id,    employee_id),
            phone          = ISNULL(@u_phone,          phone),
            guardian_phone = ISNULL(@u_guardian_phone, guardian_phone),
            push_enabled   = ISNULL(@u_push_enabled,   push_enabled)
        WHERE uid = @u_uid;
    END
    ELSE
    BEGIN
        PRINT '-- users: uid does not exist -> INSERT';
        INSERT INTO users (uid, username, password_hash, role, first_name, last_name,
                           student_id, employee_id, phone, guardian_phone, push_enabled)
        VALUES (@u_uid, @u_username, ISNULL(@u_password_hash, ''),
                ISNULL(@u_role, 'student'), @u_first_name, @u_last_name,
                @u_student_id, @u_employee_id, @u_phone, @u_guardian_phone, ISNULL(@u_push_enabled, 0));
    END
END
ELSE PRINT '-- users: SKIPPED (set @u_uid and @u_username) --';

--===============================================================================================
-- SECTION 2: classes  (CREATE if id does not exist, UPDATE otherwise)
--===============================================================================================
DECLARE @c_id            VARCHAR(36)   = NULL;   -- set NEWID() or an existing id
DECLARE @c_class_name    NVARCHAR(255) = NULL;
DECLARE @c_level         NVARCHAR(100) = NULL;
DECLARE @c_section       NVARCHAR(50)  = NULL;
DECLARE @c_class_code    NVARCHAR(10)  = NULL;
DECLARE @c_schedule      NVARCHAR(50)  = NULL;
DECLARE @c_start_time    NVARCHAR(10)  = NULL;
DECLARE @c_end_time      NVARCHAR(10)  = NULL;
DECLARE @c_time_slot     NVARCHAR(50)  = NULL;
DECLARE @c_session_limit INT           = NULL;
DECLARE @c_teacher_uid   VARCHAR(128)  = NULL;
DECLARE @c_teacher_name  NVARCHAR(255) = NULL;
DECLARE @c_status        NVARCHAR(50)  = NULL;

IF @c_id IS NOT NULL AND @c_teacher_uid IS NOT NULL
BEGIN
    IF EXISTS (SELECT 1 FROM classes WHERE id = @c_id)
    BEGIN
        PRINT '-- classes: id exists -> UPDATE';
        UPDATE classes SET
            class_name    = ISNULL(@c_class_name,    class_name),
            level         = ISNULL(@c_level,         level),
            section_name  = ISNULL(@c_section,       section_name),
            class_code    = ISNULL(@c_class_code,    class_code),
            schedule      = ISNULL(@c_schedule,      schedule),
            start_time    = ISNULL(@c_start_time,    start_time),
            end_time      = ISNULL(@c_end_time,      end_time),
            time_slot     = ISNULL(@c_time_slot,     time_slot),
            session_limit = ISNULL(@c_session_limit, session_limit),
            teacher_uid   = ISNULL(@c_teacher_uid,   teacher_uid),
            teacher_name  = ISNULL(@c_teacher_name,  teacher_name),
            status        = ISNULL(@c_status,        status)
        WHERE id = @c_id;
    END
    ELSE
    BEGIN
        PRINT '-- classes: id does not exist -> INSERT';
        INSERT INTO classes (id, class_name, level, section_name, class_code,
                             schedule, start_time, end_time, time_slot, session_limit,
                             teacher_uid, teacher_name, status)
        VALUES (@c_id, @c_class_name, @c_level, @c_section, @c_class_code,
                @c_schedule, @c_start_time, @c_end_time, @c_time_slot, ISNULL(@c_session_limit, 0),
                @c_teacher_uid, @c_teacher_name, ISNULL(@c_status, 'In Progress'));
    END
END
ELSE PRINT '-- classes: SKIPPED (set @c_id and @c_teacher_uid) --';

--===============================================================================================
-- SECTION 3: class_students  (enroll CREATE-or-UPDATE)
--===============================================================================================
DECLARE @cs_class_id    VARCHAR(36)  = NULL;
DECLARE @cs_student_uid VARCHAR(128) = NULL;

IF @cs_class_id IS NOT NULL AND @cs_student_uid IS NOT NULL
BEGIN
    IF EXISTS (SELECT 1 FROM class_students WHERE class_id = @cs_class_id AND student_uid = @cs_student_uid)
    BEGIN
        PRINT '-- class_students: enrollment exists -> refresh enrolled_at';
        UPDATE class_students SET enrolled_at = GETDATE()
        WHERE class_id = @cs_class_id AND student_uid = @cs_student_uid;
    END
    ELSE
    BEGIN
        PRINT '-- class_students: not enrolled -> INSERT';
        INSERT INTO class_students (class_id, student_uid, enrolled_at)
        VALUES (@cs_class_id, @cs_student_uid, GETDATE());
    END
END
ELSE PRINT '-- class_students: SKIPPED (set @cs_class_id and @cs_student_uid) --';

--===============================================================================================
-- SECTION 4: attendance  (CREATE-or-UPDATE latest row for class/student/date)
-- The app treats the LATEST row for a day as authoritative, so the UPDATE targets
-- the most recent timestamp instead of inserting a duplicate.
--===============================================================================================
DECLARE @a_class_id    VARCHAR(36)  = NULL;
DECLARE @a_student_uid VARCHAR(128) = NULL;
DECLARE @a_date        NVARCHAR(20) = CONVERT(NVARCHAR(10), GETDATE(), 1);  -- M/D/YY
DECLARE @a_status      NVARCHAR(20) = 'Present';   -- 'Present' | 'Late' | 'Absent'
DECLARE @a_session_id  VARCHAR(36)  = NULL;

IF @a_class_id IS NOT NULL AND @a_student_uid IS NOT NULL
BEGIN
    IF EXISTS (SELECT 1 FROM attendance
               WHERE class_id = @a_class_id AND student_uid = @a_student_uid AND date = @a_date)
    BEGIN
        PRINT '-- attendance: row exists for the day -> UPDATE latest row';
        UPDATE a
        SET status = @a_status,
            timestamp = GETDATE(),
            session_id = ISNULL(@a_session_id, session_id)
        FROM attendance a
        WHERE a.id = (SELECT TOP 1 id FROM attendance
                      WHERE class_id = @a_class_id AND student_uid = @a_student_uid AND date = @a_date
                      ORDER BY timestamp DESC);
    END
    ELSE
    BEGIN
        PRINT '-- attendance: no row for the day -> INSERT';
        INSERT INTO attendance (student_uid, class_id, date, timestamp, status, session_id)
        VALUES (@a_student_uid, @a_class_id, @a_date, GETDATE(), @a_status, @a_session_id);
    END
END
ELSE PRINT '-- attendance: SKIPPED (set @a_class_id and @a_student_uid) --';

--===============================================================================================
-- SECTION 5: events  (CREATE-or-UPDATE by id; id = 0 creates a new IDENTITY row)
--===============================================================================================
DECLARE @e_id          INT          = 0;
DECLARE @e_teacher_uid VARCHAR(128) = NULL;
DECLARE @e_title       NVARCHAR(255) = NULL;
DECLARE @e_date_str    DATE          = NULL;
DECLARE @e_start_time  NVARCHAR(10)  = NULL;
DECLARE @e_end_time    NVARCHAR(10)  = NULL;
DECLARE @e_description NTEXT         = NULL;

IF @e_teacher_uid IS NOT NULL AND @e_title IS NOT NULL
BEGIN
    IF @e_id > 0 AND EXISTS (SELECT 1 FROM events WHERE id = @e_id)
    BEGIN
        PRINT '-- events: id exists -> UPDATE';
        UPDATE events SET
            teacher_uid = ISNULL(@e_teacher_uid, teacher_uid),
            title       = ISNULL(@e_title,       title),
            date_str    = ISNULL(@e_date_str,    date_str),
            start_time  = ISNULL(@e_start_time,  start_time),
            end_time    = ISNULL(@e_end_time,    end_time),
            description = ISNULL(CAST(@e_description AS NVARCHAR(MAX)), CAST(description AS NVARCHAR(MAX)))
        WHERE id = @e_id;
    END
    ELSE
    BEGIN
        PRINT '-- events: new event -> INSERT';
        INSERT INTO events (teacher_uid, title, date_str, start_time, end_time, description)
        VALUES (@e_teacher_uid, @e_title, @e_date_str, @e_start_time, @e_end_time, @e_description);
    END
END
ELSE PRINT '-- events: SKIPPED (set @e_teacher_uid and @e_title) --';

--===============================================================================================
-- SECTION 6: sessions  (CREATE-or-UPDATE by token)
--===============================================================================================
DECLARE @s_uid        VARCHAR(128) = NULL;
DECLARE @s_token      NVARCHAR(255) = NULL;
DECLARE @s_expires_at DATETIME      = DATEADD(DAY, 30, GETDATE());

IF @s_uid IS NOT NULL AND @s_token IS NOT NULL
BEGIN
    IF EXISTS (SELECT 1 FROM sessions WHERE token = @s_token)
    BEGIN
        PRINT '-- sessions: token exists -> UPDATE';
        UPDATE sessions SET uid = @s_uid, expires_at = @s_expires_at
        WHERE token = @s_token;
    END
    ELSE
    BEGIN
        PRINT '-- sessions: token does not exist -> INSERT';
        INSERT INTO sessions (uid, token, expires_at) VALUES (@s_uid, @s_token, @s_expires_at);
    END
END
ELSE PRINT '-- sessions: SKIPPED (set @s_uid and @s_token) --';

--===============================================================================================
-- SECTION 7: notifications  (CREATE-or-UPDATE by id; id = 0 creates a new IDENTITY row)
--===============================================================================================
DECLARE @n_id            INT           = 0;
DECLARE @n_recipient_uid VARCHAR(128)  = NULL;
DECLARE @n_type          NVARCHAR(50)  = NULL;
DECLARE @n_title         NVARCHAR(255) = NULL;
DECLARE @n_message       NTEXT         = NULL;
DECLARE @n_link          NVARCHAR(500) = NULL;
DECLARE @n_is_read       INT           = NULL;

IF @n_recipient_uid IS NOT NULL AND @n_title IS NOT NULL
BEGIN
    IF @n_id > 0 AND EXISTS (SELECT 1 FROM notifications WHERE id = @n_id)
    BEGIN
        PRINT '-- notifications: id exists -> UPDATE';
        UPDATE notifications SET
            recipient_uid = ISNULL(@n_recipient_uid, recipient_uid),
            type          = ISNULL(@n_type,          type),
            title         = ISNULL(@n_title,         title),
            message       = ISNULL(CAST(@n_message AS NVARCHAR(MAX)), CAST(message AS NVARCHAR(MAX))),
            link          = ISNULL(@n_link,          link),
            is_read       = ISNULL(@n_is_read,       is_read)
        WHERE id = @n_id;
    END
    ELSE
    BEGIN
        PRINT '-- notifications: new notification -> INSERT';
        INSERT INTO notifications (recipient_uid, type, title, message, link, is_read)
        VALUES (@n_recipient_uid, @n_type, @n_title, @n_message, @n_link, ISNULL(@n_is_read, 0));
    END
END
ELSE PRINT '-- notifications: SKIPPED (set @n_recipient_uid and @n_title) --';

--===============================================================================================
-- SECTION 8: push_subscriptions  (CREATE-or-UPDATE by device token)
--===============================================================================================
DECLARE @p_uid   VARCHAR(128) = NULL;
DECLARE @p_token NVARCHAR(255) = NULL;

IF @p_uid IS NOT NULL AND @p_token IS NOT NULL
BEGIN
    IF EXISTS (SELECT 1 FROM push_subscriptions WHERE token = @p_token)
    BEGIN
        PRINT '-- push_subscriptions: token exists -> UPDATE owner';
        UPDATE push_subscriptions SET uid = @p_uid WHERE token = @p_token;
    END
    ELSE
    BEGIN
        PRINT '-- push_subscriptions: token does not exist -> INSERT';
        INSERT INTO push_subscriptions (uid, token) VALUES (@p_uid, @p_token);
    END
END
ELSE PRINT '-- push_subscriptions: SKIPPED (set @p_uid and @p_token) --';

--===============================================================================================
-- SECTION 9: pre_approved_students  (CREATE-or-UPDATE by student_id)
--===============================================================================================
DECLARE @pa_student_id NVARCHAR(50) = NULL;
DECLARE @pa_used_at    DATETIME     = NULL;

IF @pa_student_id IS NOT NULL
BEGIN
    IF EXISTS (SELECT 1 FROM pre_approved_students WHERE student_id = @pa_student_id)
    BEGIN
        PRINT '-- pre_approved_students: student_id exists -> UPDATE used_at';
        UPDATE pre_approved_students SET used_at = @pa_used_at WHERE student_id = @pa_student_id;
    END
    ELSE
    BEGIN
        PRINT '-- pre_approved_students: student_id does not exist -> INSERT';
        INSERT INTO pre_approved_students (student_id, used_at) VALUES (@pa_student_id, @pa_used_at);
    END
END
ELSE PRINT '-- pre_approved_students: SKIPPED (set @pa_student_id) --';

--===============================================================================================
-- SECTION 10: grade_components  (CREATE-or-UPDATE by id; id = 0 creates a new IDENTITY row)
--===============================================================================================
DECLARE @gc_id         INT           = 0;
DECLARE @gc_class_id   VARCHAR(36)   = NULL;
DECLARE @gc_category   NVARCHAR(20)  = NULL;   -- 'attendance' | 'quiz' | 'exam' | 'assignment' | ...
DECLARE @gc_name       NVARCHAR(255) = NULL;
DECLARE @gc_hps        INT           = NULL;   -- highest possible score
DECLARE @gc_quarter    INT           = NULL;
DECLARE @gc_session_id VARCHAR(36)   = NULL;

IF @gc_class_id IS NOT NULL AND @gc_name IS NOT NULL
BEGIN
    IF @gc_id > 0 AND EXISTS (SELECT 1 FROM grade_components WHERE id = @gc_id)
    BEGIN
        PRINT '-- grade_components: id exists -> UPDATE';
        UPDATE grade_components SET
            class_id   = ISNULL(@gc_class_id,   class_id),
            category   = ISNULL(@gc_category,   category),
            name       = ISNULL(@gc_name,       name),
            hps        = ISNULL(@gc_hps,        hps),
            quarter    = ISNULL(@gc_quarter,    quarter),
            session_id = ISNULL(@gc_session_id, session_id)
        WHERE id = @gc_id;
    END
    ELSE
    BEGIN
        PRINT '-- grade_components: new component -> INSERT';
        INSERT INTO grade_components (class_id, category, name, hps, quarter, session_id)
        VALUES (@gc_class_id, @gc_category, @gc_name, ISNULL(@gc_hps, 50), ISNULL(@gc_quarter, 1), @gc_session_id);
    END
END
ELSE PRINT '-- grade_components: SKIPPED (set @gc_class_id and @gc_name) --';

--===============================================================================================
-- SECTION 11: grades  (CREATE-or-UPDATE by component_id + student_uid)
-- The Grading Center's core upsert: if the student already has a score cell for
-- this component, update it; otherwise create the cell.
--===============================================================================================
DECLARE @g_component_id INT           = NULL;   -- must exist in grade_components
DECLARE @g_student_uid  VARCHAR(128)  = NULL;
DECLARE @g_score        DECIMAL(10,2) = NULL;   -- e.g. 8.50, 45.00, 10.00

IF @g_component_id IS NOT NULL AND @g_student_uid IS NOT NULL AND @g_score IS NOT NULL
BEGIN
    IF EXISTS (SELECT 1 FROM grades WHERE component_id = @g_component_id AND student_uid = @g_student_uid)
    BEGIN
        PRINT '-- grades: score cell exists -> UPDATE';
        UPDATE grades SET score = @g_score, updated_at = GETDATE()
        WHERE component_id = @g_component_id AND student_uid = @g_student_uid;
    END
    ELSE
    BEGIN
        PRINT '-- grades: no score cell -> INSERT';
        INSERT INTO grades (component_id, student_uid, score, updated_at)
        VALUES (@g_component_id, @g_student_uid, @g_score, GETDATE());
    END
END
ELSE PRINT '-- grades: SKIPPED (set @g_component_id, @g_student_uid and @g_score) --';

--===============================================================================================
-- SECTION 12: grade_weights  (CREATE-or-UPDATE by class_id + category)
--===============================================================================================
DECLARE @w_class_id VARCHAR(36)  = NULL;
DECLARE @w_category NVARCHAR(20) = NULL;
DECLARE @w_weight   INT          = NULL;   -- percent (0-100)

IF @w_class_id IS NOT NULL AND @w_category IS NOT NULL
BEGIN
    IF EXISTS (SELECT 1 FROM grade_weights WHERE class_id = @w_class_id AND category = @w_category)
    BEGIN
        PRINT '-- grade_weights: weight exists -> UPDATE';
        UPDATE grade_weights SET weight_percent = @w_weight
        WHERE class_id = @w_class_id AND category = @w_category;
    END
    ELSE
    BEGIN
        PRINT '-- grade_weights: no weight -> INSERT';
        INSERT INTO grade_weights (class_id, category, weight_percent)
        VALUES (@w_class_id, @w_category, ISNULL(@w_weight, 0));
    END
END
ELSE PRINT '-- grade_weights: SKIPPED (set @w_class_id and @w_category) --';

--===============================================================================================
-- SECTION 13: ai_insights  (CREATE-or-UPDATE by student_uid + class_id)
--===============================================================================================
DECLARE @ai_student_uid VARCHAR(128) = NULL;
DECLARE @ai_class_id    VARCHAR(36)  = NULL;
DECLARE @ai_paragraph   NTEXT        = NULL;
DECLARE @ai_tips        NTEXT        = NULL;
DECLARE @ai_signature   NVARCHAR(64) = NULL;

IF @ai_student_uid IS NOT NULL AND @ai_class_id IS NOT NULL AND @ai_signature IS NOT NULL
BEGIN
    IF EXISTS (SELECT 1 FROM ai_insights WHERE student_uid = @ai_student_uid AND class_id = @ai_class_id)
    BEGIN
        PRINT '-- ai_insights: insight exists -> UPDATE';
        UPDATE ai_insights SET
            insight_paragraph = ISNULL(@ai_paragraph, insight_paragraph),
            insight_tips      = ISNULL(@ai_tips,      insight_tips),
            signature         = ISNULL(@ai_signature, signature),
            created_at        = GETDATE()
        WHERE student_uid = @ai_student_uid AND class_id = @ai_class_id;
    END
    ELSE
    BEGIN
        PRINT '-- ai_insights: no insight -> INSERT';
        INSERT INTO ai_insights (student_uid, class_id, insight_paragraph, insight_tips, signature)
        VALUES (@ai_student_uid, @ai_class_id, @ai_paragraph, @ai_tips, @ai_signature);
    END
END
ELSE PRINT '-- ai_insights: SKIPPED (set @ai_student_uid, @ai_class_id and @ai_signature) --';

--===============================================================================================
-- SECTION 14: verification  (show the rows touched by the sections above)
--===============================================================================================
PRINT '--[verify] users --';
SELECT * FROM users WHERE uid = @u_uid;

PRINT '--[verify] classes --';
SELECT * FROM classes WHERE id = @c_id;

PRINT '--[verify] class_students --';
SELECT * FROM class_students WHERE class_id = @cs_class_id AND student_uid = @cs_student_uid;

PRINT '--[verify] attendance (all rows for the day) --';
SELECT * FROM attendance WHERE class_id = @a_class_id AND student_uid = @a_student_uid AND date = @a_date;

PRINT '--[verify] events --';
SELECT * FROM events WHERE id = @e_id OR title = @e_title;

PRINT '--[verify] sessions --';
SELECT * FROM sessions WHERE token = @s_token;

PRINT '--[verify] notifications --';
SELECT * FROM notifications WHERE id = @n_id OR title = @n_title;

PRINT '--[verify] push_subscriptions --';
SELECT * FROM push_subscriptions WHERE token = @p_token;

PRINT '--[verify] pre_approved_students --';
SELECT * FROM pre_approved_students WHERE student_id = @pa_student_id;

PRINT '--[verify] grade_components --';
SELECT * FROM grade_components WHERE id = @gc_id OR name = @gc_name;

PRINT '--[verify] grades (with student + component names) --';
SELECT g.component_id, gc.name AS component, gc.class_id,
       g.student_uid, u.first_name, u.last_name, g.score, g.updated_at
FROM grades g
JOIN grade_components gc ON gc.id = g.component_id
JOIN users u ON u.uid = g.student_uid
WHERE g.component_id = @g_component_id AND g.student_uid = @g_student_uid;

PRINT '--[verify] grade_weights --';
SELECT * FROM grade_weights WHERE class_id = @w_class_id AND category = @w_category;

PRINT '--[verify] ai_insights --';
SELECT * FROM ai_insights WHERE student_uid = @ai_student_uid AND class_id = @ai_class_id;

PRINT '======================================== ClassSense Upsert END';
