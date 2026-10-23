<?php
/**
 * redistribute_students.php
 * RESETS and re-distributes students across the 6 teacher classes
 * with exact target counts.
 *
 * Target distribution:
 *   ICT - 7                       → 43 students
 *   TLE - 8                       → 43 students
 *   Math - 9                      → 46 students
 *   Science - 10                  → 46 students
 *   Oral Communication - 11       → 46 students
 *   21st Century Literature - 12  → 46 students
 *
 * Access via: http://localhost/ClassSense/redistribute_students.php
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "ClassSense — Student Redistribution (Reset + Enroll)\n";
echo "======================================================\n\n";

// Exact target counts per class name
$targets = [
    'ICT - 7'                      => 43,
    'TLE - 8'                      => 43,
    'Math - 9'                     => 46,
    'Science - 10'                 => 46,
    'Oral Communication - 11'      => 46,
    '21st Century Literature - 12' => 46,
];

$totalTarget = array_sum($targets); // 270

try {
    $pdo = getPDO();

    // ─── Step 1: Fetch the 6 target classes ──────────────────────────────────
    echo "Step 1: Loading target classes...\n";
    echo "----------------------------------\n";

    $placeholders = implode(',', array_fill(0, count($targets), '?'));
    $stmt = $pdo->prepare("
        SELECT id, class_name, section_name, teacher_name
        FROM classes
        WHERE class_name IN ({$placeholders})
        ORDER BY CASE class_name
            WHEN 'ICT - 7'                      THEN 1
            WHEN 'TLE - 8'                      THEN 2
            WHEN 'Math - 9'                     THEN 3
            WHEN 'Science - 10'                 THEN 4
            WHEN 'Oral Communication - 11'      THEN 5
            WHEN '21st Century Literature - 12' THEN 6
            ELSE 99
        END
    ");
    $stmt->execute(array_keys($targets));
    $classes = $stmt->fetchAll();

    if (count($classes) === 0) {
        echo "❌ No target classes found. Run create_classes.php first.\n";
        exit(1);
    }

    foreach ($classes as $c) {
        $target = $targets[$c['class_name']] ?? '?';
        echo "  ✓ {$c['class_name']} ({$c['section_name']}) → target: {$target} students\n";
    }
    echo "\n";

    // ─── Step 2: CLEAR existing enrollments for all 6 classes ────────────────
    echo "Step 2: Clearing existing enrollments...\n";
    echo "-----------------------------------------\n";

    foreach ($classes as $c) {
        $del = $pdo->prepare("DELETE FROM class_students WHERE class_id = ?");
        $del->execute([$c['id']]);
        $deleted = $del->rowCount();
        echo "  ✓ Cleared {$deleted} enrollment(s) from {$c['class_name']}\n";
    }
    echo "\n";

    // ─── Step 3: Fetch all students in random order ───────────────────────────
    echo "Step 3: Loading all students (shuffled)...\n";
    echo "-------------------------------------------\n";

    $stmt = $pdo->query("
        SELECT uid, first_name, last_name, student_id
        FROM users
        WHERE role = 'student'
        ORDER BY NEWID()
    ");
    $students = $stmt->fetchAll();
    $totalStudents = count($students);

    echo "  ✓ Found {$totalStudents} student(s).\n";

    if ($totalStudents < $totalTarget) {
        echo "  ⚠ WARNING: Only {$totalStudents} students but need {$totalTarget}. Some slots will be empty.\n";
    }
    echo "\n";

    // ─── Step 4: Slice students into exact-sized chunks ───────────────────────
    echo "Step 4: Enrolling students into classes...\n";
    echo "-------------------------------------------\n";

    $insertStmt = $pdo->prepare("
        INSERT INTO class_students (class_id, student_uid, enrolled_at)
        VALUES (?, ?, GETDATE())
    ");

    $offset  = 0;
    $summary = [];

    foreach ($classes as $c) {
        $classId = $c['id'];
        $needed  = $targets[$c['class_name']] ?? 0;
        $chunk   = array_slice($students, $offset, $needed);
        $offset += $needed;

        $enrolled = 0;
        $failed   = 0;

        foreach ($chunk as $student) {
            try {
                $insertStmt->execute([$classId, $student['uid']]);
                $enrolled++;
            } catch (PDOException $e) {
                $failed++;
                echo "    ✗ Failed: {$student['first_name']} {$student['last_name']} — {$e->getMessage()}\n";
            }
        }

        $summary[] = [
            'class'    => $c['class_name'],
            'section'  => $c['section_name'],
            'teacher'  => $c['teacher_name'],
            'target'   => $needed,
            'enrolled' => $enrolled,
            'failed'   => $failed,
        ];

        $status = ($enrolled === $needed) ? '✓' : '⚠';
        echo "  {$status} {$c['class_name']} ({$c['section_name']}): {$enrolled}/{$needed} enrolled";
        if ($failed > 0) echo " | {$failed} failed";
        echo "\n";
    }

    // ─── Step 5: Summary ─────────────────────────────────────────────────────
    echo "\n======================================================\n";
    echo "Final Distribution Summary\n";
    echo "======================================================\n";
    echo str_pad("Class", 34) . str_pad("Section", 12) . str_pad("Target", 8) . "Enrolled\n";
    echo str_repeat("-", 70) . "\n";

    $grandTotal = 0;
    foreach ($summary as $row) {
        $ok = ($row['enrolled'] === $row['target']) ? '' : ' ⚠';
        echo str_pad($row['class'], 34)
           . str_pad($row['section'], 12)
           . str_pad($row['target'], 8)
           . $row['enrolled'] . $ok . "\n";
        $grandTotal += $row['enrolled'];
    }

    echo str_repeat("-", 70) . "\n";
    echo str_pad("TOTAL", 54) . $grandTotal . "\n\n";

    // ─── Step 6: Verify counts directly from DB ───────────────────────────────
    echo "Verification (live DB count):\n";
    foreach ($classes as $c) {
        $vstmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM class_students cs
            JOIN users u ON u.uid = cs.student_uid
            WHERE cs.class_id = ? AND u.role = 'student'
        ");
        $vstmt->execute([$c['id']]);
        $cnt    = (int)$vstmt->fetch()['cnt'];
        $target = $targets[$c['class_name']] ?? 0;
        $ok     = ($cnt === $target) ? '✓' : '✗';
        echo "  {$ok} {$c['class_name']} ({$c['section_name']}): {$cnt} student(s) (target: {$target})\n";
    }

    echo "\n✓ Done! You can now delete this file for security.\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
