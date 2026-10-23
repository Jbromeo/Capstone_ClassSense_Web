<?php
/**
 * trim_and_redistribute.php
 *
 * 1. Removes enrollments from the 6 target classes
 * 2. Deletes excess students so only 206 remain
 * 3. Re-enrolls with the exact distribution:
 *      ICT - 7                       → 38
 *      TLE - 8                       → 36
 *      Math - 9                      → 35
 *      Science - 10                  → 34
 *      Oral Communication - 11       → 33
 *      21st Century Literature - 12  → 30
 *                              Total = 206
 *
 * Access: http://localhost/ClassSense/trim_and_redistribute.php
 */

require_once __DIR__ . '/api/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "ClassSense — Trim to 206 Students & Redistribute\n";
echo "===================================================\n\n";

$distribution = [
    'ICT - 7'                      => 38,
    'TLE - 8'                      => 36,
    'Math - 9'                     => 35,
    'Science - 10'                 => 34,
    'Oral Communication - 11'      => 33,
    '21st Century Literature - 12' => 30,
];
$TARGET_TOTAL = array_sum($distribution); // 206

try {
    $pdo = getPDO();

    // ─── Step 1: Load the 6 target classes ─────────────────────────────────
    echo "Step 1: Loading target classes...\n";
    echo "----------------------------------\n";

    $placeholders = implode(',', array_fill(0, count($distribution), '?'));
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
    $stmt->execute(array_keys($distribution));
    $classes = $stmt->fetchAll();

    if (count($classes) < 6) {
        echo "❌ Expected 6 classes, found " . count($classes) . ". Aborting.\n";
        exit(1);
    }

    foreach ($classes as $c) {
        $target = $distribution[$c['class_name']] ?? '?';
        echo "  ✓ {$c['class_name']} ({$c['section_name']}) → target: {$target}\n";
    }
    echo "\n";

    // ─── Step 2: Clear ALL enrollments for the 6 classes ────────────────────
    echo "Step 2: Clearing existing enrollments...\n";
    echo "-----------------------------------------\n";

    foreach ($classes as $c) {
        $del = $pdo->prepare("DELETE FROM class_students WHERE class_id = ?");
        $del->execute([$c['id']]);
        echo "  ✓ Cleared {$del->rowCount()} enrollment(s) from {$c['class_name']}\n";
    }
    echo "\n";

    // ─── Step 3: Count current students ─────────────────────────────────────
    echo "Step 3: Counting current students...\n";
    echo "-------------------------------------\n";

    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'student'");
    $currentTotal = (int)$stmt->fetch()['cnt'];
    echo "  Current student count: {$currentTotal}\n";
    echo "  Target student count:  {$TARGET_TOTAL}\n";

    $excess = $currentTotal - $TARGET_TOTAL;

    if ($excess > 0) {
        echo "  Need to remove: {$excess} student(s)\n\n";

        // ─── Step 4: Delete excess students ─────────────────────────────────
        echo "Step 4: Removing {$excess} excess students...\n";
        echo "-----------------------------------------------\n";

        // Pick the students to DELETE (random selection)
        // Note: SQL Server ODBC doesn't support parameterized TOP, so we interpolate directly
        $excessInt = (int)$excess;
        $stmt = $pdo->query("
            SELECT TOP ({$excessInt}) uid
            FROM users
            WHERE role = 'student'
            ORDER BY NEWID()
        ");
        $toDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $deletedCount = 0;
        foreach ($toDelete as $uid) {
            // Clean up related records first (FK safe)
            $pdo->prepare("DELETE FROM attendance WHERE student_uid = ?")->execute([$uid]);
            $pdo->prepare("DELETE FROM grades WHERE student_uid = ?")->execute([$uid]);
            $pdo->prepare("DELETE FROM ai_insights WHERE student_uid = ?")->execute([$uid]);
            $pdo->prepare("DELETE FROM notifications WHERE recipient_uid = ?")->execute([$uid]);
            $pdo->prepare("DELETE FROM class_students WHERE student_uid = ?")->execute([$uid]);
            $pdo->prepare("DELETE FROM sessions WHERE uid = ?")->execute([$uid]);
            $pdo->prepare("DELETE FROM push_subscriptions WHERE uid = ?")->execute([$uid]);
            $pdo->prepare("DELETE FROM users WHERE uid = ?")->execute([$uid]);
            $deletedCount++;
        }

        echo "  ✓ Deleted {$deletedCount} student account(s) and their related records.\n\n";

        // Verify
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'student'");
        $remaining = (int)$stmt->fetch()['cnt'];
        echo "  Students remaining: {$remaining}\n";
        if ($remaining !== $TARGET_TOTAL) {
            echo "  ⚠ WARNING: Expected {$TARGET_TOTAL}, got {$remaining}\n";
        }
        echo "\n";
    } elseif ($excess === 0) {
        echo "  Already at exactly {$TARGET_TOTAL} students. No deletion needed.\n\n";
    } else {
        echo "  ⚠ Only {$currentTotal} students exist (need {$TARGET_TOTAL}). Will enroll what we have.\n\n";
    }

    // ─── Step 5: Fetch remaining students (shuffled) ────────────────────────
    echo "Step 5: Loading remaining students (shuffled)...\n";
    echo "-------------------------------------------------\n";

    $stmt = $pdo->query("
        SELECT uid, first_name, last_name, student_id
        FROM users
        WHERE role = 'student'
        ORDER BY NEWID()
    ");
    $students = $stmt->fetchAll();
    echo "  ✓ Loaded " . count($students) . " student(s).\n\n";

    // ─── Step 6: Enroll into classes with exact counts ──────────────────────
    echo "Step 6: Enrolling students...\n";
    echo "------------------------------\n";

    $insertStmt = $pdo->prepare("
        INSERT INTO class_students (class_id, student_uid, enrolled_at)
        VALUES (?, ?, GETDATE())
    ");

    $offset    = 0;
    $summary   = [];

    foreach ($classes as $c) {
        $classId = $c['id'];
        $needed  = $distribution[$c['class_name']] ?? 0;
        $chunk   = array_slice($students, $offset, $needed);
        $offset += $needed;

        $enrolled = 0;
        foreach ($chunk as $student) {
            try {
                $insertStmt->execute([$classId, $student['uid']]);
                $enrolled++;
            } catch (PDOException $e) {
                echo "    ✗ Failed: {$student['first_name']} {$student['last_name']} — {$e->getMessage()}\n";
            }
        }

        $match = ($enrolled === $needed) ? '✓' : '⚠';
        echo "  {$match} {$c['class_name']} ({$c['section_name']}): {$enrolled}/{$needed}\n";

        $summary[] = [
            'class'    => $c['class_name'],
            'section'  => $c['section_name'],
            'teacher'  => $c['teacher_name'],
            'target'   => $needed,
            'enrolled' => $enrolled,
        ];
    }

    // ─── Step 7: Final Summary ──────────────────────────────────────────────
    echo "\n===================================================\n";
    echo "Final Distribution\n";
    echo "===================================================\n";
    echo str_pad("Class", 34) . str_pad("Section", 12) . str_pad("Teacher", 24) . str_pad("Target", 8) . "Actual\n";
    echo str_repeat("-", 86) . "\n";

    $grandTotal = 0;
    foreach ($summary as $row) {
        echo str_pad($row['class'], 34)
           . str_pad($row['section'], 12)
           . str_pad(substr($row['teacher'], 0, 22), 24)
           . str_pad($row['target'], 8)
           . $row['enrolled'] . "\n";
        $grandTotal += $row['enrolled'];
    }
    echo str_repeat("-", 86) . "\n";
    echo str_pad("TOTAL", 78) . $grandTotal . "\n\n";

    // Live DB verification
    echo "Verification (live DB):\n";
    foreach ($classes as $c) {
        $vstmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM class_students cs
            JOIN users u ON u.uid = cs.student_uid
            WHERE cs.class_id = ? AND u.role = 'student'
        ");
        $vstmt->execute([$c['id']]);
        $cnt    = (int)$vstmt->fetch()['cnt'];
        $target = $distribution[$c['class_name']];
        $ok     = ($cnt === $target) ? '✓' : '✗';
        echo "  {$ok} {$c['class_name']}: {$cnt} (target: {$target})\n";
    }

    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'student'");
    $finalTotal = (int)$stmt->fetch()['cnt'];
    echo "\n  Total students in database: {$finalTotal}\n";
    echo "\n✓ Done! You can safely delete this file.\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
