-- ClassSense: realign attendance grading columns with the attendance table
-- Fixes a multi-session bug where a student marked Absent in the attendance
-- table still showed Present (10) in the Grading Center's per-day column.
-- Run in SQL Server Management Studio (or sqlcmd) against the ClassSense database.
-- Steps: (1) dry-run report of mismatches, (2) apply the fix inside a transaction.
-- After this script, re-open the affected class in the Grading Center (refresh).

PRINT '======================================== ClassSense Attendance<>Grading realign';

-- Per-student-per-day authoritative status: the LATEST attendance row for the
-- class/student/date (the app's per-day model uses the same "latest wins" rule).
-- The per-day grading column is named M/D/YY (e.g. 8/14/26); TRY_CONVERT style 1
-- parses that back to a DATE so it can be matched to attendance.date.

-- STEP 1: DRY-RUN — list every attendance-column grade that disagrees with the
-- attendance table before anything is changed.
;WITH latest AS (
    SELECT class_id, student_uid, CAST(date AS DATE) AS att_date, status,
           ROW_NUMBER() OVER (PARTITION BY class_id, student_uid, CAST(date AS DATE)
                              ORDER BY timestamp DESC) AS rn
    FROM attendance
)
SELECT
    gc.class_id,
    gc.quarter,
    gc.name                                              AS attendance_component,
    g.student_uid,
    u.firstName,
    u.lastName,
    g.score                                              AS current_score,
    ISNULL(l.status, '(no attendance row)')              AS attendance_status,
    CASE l.status WHEN 'Late' THEN 5.0
                  WHEN 'Absent' THEN 0.0
                  WHEN 'Present' THEN 10.0
                  ELSE 0.0 END                           AS corrected_score
FROM grades g
JOIN grade_components gc ON gc.id = g.component_id AND gc.category = 'attendance'
LEFT JOIN users u ON u.uid = g.student_uid
LEFT JOIN latest l ON l.class_id = gc.class_id
                  AND l.student_uid = g.student_uid
                  AND l.att_date = TRY_CONVERT(DATE, gc.name, 1)
                  AND l.rn = 1
WHERE g.score <> CASE l.status WHEN 'Late' THEN 5.0
                               WHEN 'Absent' THEN 0.0
                               WHEN 'Present' THEN 10.0
                               ELSE 0.0 END
ORDER BY gc.class_id, gc.name, u.lastName, u.firstName;

-- STEP 2: APPLY — align every attendance-column grade with the attendance table.
PRINT '-- Applying attendance<>grading realignment --';
BEGIN TRANSACTION;

;WITH latest AS (
    SELECT class_id, student_uid, CAST(date AS DATE) AS att_date, status,
           ROW_NUMBER() OVER (PARTITION BY class_id, student_uid, CAST(date AS DATE)
                              ORDER BY timestamp DESC) AS rn
    FROM attendance
),
mapped AS (
    SELECT gc.id AS component_id, g.student_uid,
           CASE l.status WHEN 'Late' THEN 5.0
                         WHEN 'Absent' THEN 0.0
                         WHEN 'Present' THEN 10.0
                         ELSE 0.0 END AS new_score
    FROM grades g
    JOIN grade_components gc ON gc.id = g.component_id AND gc.category = 'attendance'
    LEFT JOIN latest l ON l.class_id = gc.class_id
                      AND l.student_uid = g.student_uid
                      AND l.att_date = TRY_CONVERT(DATE, gc.name, 1)
                      AND l.rn = 1
)
UPDATE g
SET g.score = m.new_score, g.updated_at = GETDATE()
FROM grades g
JOIN mapped m ON m.component_id = g.component_id AND m.student_uid = g.student_uid
WHERE g.score <> m.new_score;

PRINT '-- Realignment applied (rows affected are printed above).';
COMMIT TRANSACTION;
