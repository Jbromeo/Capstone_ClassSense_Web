<?php
/**
 * distribute_students.php
 * Distributes all registered students across the 6 newly created teacher classes.
 * Access via: http://localhost/ClassSense/distribute_students.php
 *
 * Safe to re-run — skips already-enrolled students.
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "ClassSense — Student Distribution\n";
echo "===========================================\n\n";

try {
    $pdo = getPDO();

    // ─── Step 1: Fetch all registered students ──────────────────────────────
    echo "Step 1: Loading all students from database...\n";
    echo "----------------------------------------------\n";

    $stmt = $pdo->query("
        SELECT uid, first_name, last_name, student_id
        FROM users
        WHERE role = 'student'
        ORDER BY NEWID()
    ");
    $students = $stmt->fetchAll();
    $totalStudents = count($students);

    echo "✓ Found {$totalStudents} student(s) in the database.\n\n";

    if ($totalStudents === 0) {
        echo "❌ No students found. Nothing to distribute.\n";
        exit(0);
    }

    // ─── Step 2: Fetch the 6 target classes ─────────────────────────────────
    echo "Step 2: Loading target classes...\n";
    echo "----------------------------------\n";

    $targetClassNames = [
        'ICT - 7',
        'TLE - 8',
        'Math - 9',
        'Science - 10',
        'Oral Communication - 11',
        '21st Century Literature - 12',
    ];

    $placeholders = implode(',', array_fill(0, count($targetClassNames), '?'));
    $stmt = $pdo->prepare("
        SELECT id, class_name, section_name, teacher_name
        FROM classes
        WHERE class_name IN ({$placeholders})
        ORDER BY created_at DESC
    ");
    $stmt->execute($targetClassNames);
    $classes = $stmt->fetchAll();

    if (count($classes) === 0) {
        echo "❌ None of the target classes found. Did you run create_classes.php first?\n";
        exit(1);
    }

    echo "✓ Found " . count($classes) . " class(es):\n";
    foreach ($classes as $c) {
        echo "   • {$c['class_name']} — {$c['section_name']} (Teacher: {$c['teacher_name']})\n";
    }
    echo "\n";

    $numClasses = count($classes);

    // ─── Step 3: Shuffle students and distribute ─────────────────────────────
    echo "Step 3: Distributing students...\n";
    echo "---------------------------------\n";

    // Shuffle so distribution is random each time the script runs
    shuffle($students);

    // Calculate target sizes: spread students as evenly as possible
    $baseCount   = (int) floor($totalStudents / $numClasses);
    $remainder   = $totalStudents % $numClasses;

    // Assign sizes: first $remainder classes get +1
    $classSizes = [];
    for ($i = 0; $i < $numClasses; $i++) {
        $classSizes[$i] = $baseCount + ($i < $remainder ? 1 : 0);
    }

    // Slice the shuffled student array into chunks
    $chunks = [];
    $offset = 0;
    for ($i = 0; $i < $numClasses; $i++) {
        $chunks[$i] = array_slice($students, $offset, $classSizes[$i]);
        $offset += $classSizes[$i];
    }

    // ─── Step 4: Insert into class_students ─────────────────────────────────
    $totalEnrolled  = 0;
    $totalSkipped   = 0;
    $totalFailed    = 0;
    $summary        = [];

    $checkStmt  = $pdo->prepare("SELECT COUNT(*) AS cnt FROM class_students WHERE class_id = ? AND student_uid = ?");
    $insertStmt = $pdo->prepare("INSERT INTO class_students (class_id, student_uid, enrolled_at) VALUES (?, ?, GETDATE())");

    for ($i = 0; $i < $numClasses; $i++) {
        $class   = $classes[$i];
        $chunk   = $chunks[$i];
        $classId = $class['id'];
        $enrolled = 0;
        $skipped  = 0;
        $failed   = 0;

        foreach ($chunk as $student) {
            try {
                // Skip if already enrolled
                $checkStmt->execute([$classId, $student['uid']]);
                if ((int)$checkStmt->fetch()['cnt'] > 0) {
                    $skipped++;
                    continue;
                }

                $insertStmt->execute([$classId, $student['uid']]);
                $enrolled++;
            } catch (PDOException $e) {
                $failed++;
                echo "   ✗ Failed enrolling {$student['first_name']} {$student['last_name']}: {$e->getMessage()}\n";
            }
        }

        $totalEnrolled += $enrolled;
        $totalSkipped  += $skipped;
        $totalFailed   += $failed;

        $summary[] = [
            'class'    => $class['class_name'],
            'section'  => $class['section_name'],
            'teacher'  => $class['teacher_name'],
            'enrolled' => $enrolled,
            'skipped'  => $skipped,
            'total'    => count($chunk),
        ];

        echo "✓ {$class['class_name']} ({$class['section_name']}): {$enrolled} enrolled";
        if ($skipped > 0) echo ", {$skipped} already enrolled (skipped)";
        if ($failed  > 0) echo ", {$failed} failed";
        echo "\n";
    }

    // ─── Step 5: Summary ─────────────────────────────────────────────────────
    echo "\n===========================================\n";
    echo "Distribution Summary\n";
    echo "===========================================\n";
    echo str_pad("Class", 32) . str_pad("Section", 12) . str_pad("Teacher", 28) . "Enrolled\n";
    echo str_repeat("-", 85) . "\n";
    foreach ($summary as $row) {
        echo str_pad($row['class'], 32)
           . str_pad($row['section'], 12)
           . str_pad(substr($row['teacher'], 0, 26), 28)
           . "{$row['enrolled']} / {$row['total']}\n";
    }
    echo str_repeat("-", 85) . "\n";
    echo "Total students processed : {$totalStudents}\n";
    echo "Newly enrolled           : {$totalEnrolled}\n";
    echo "Already enrolled (skip)  : {$totalSkipped}\n";
    echo "Failed                   : {$totalFailed}\n";

    // Verify final counts
    echo "\nVerification — current class_students counts:\n";
    foreach ($classes as $c) {
        $vstmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM class_students cs JOIN users u ON u.uid = cs.student_uid WHERE cs.class_id = ? AND u.role = 'student'");
        $vstmt->execute([$c['id']]);
        $cnt = (int)$vstmt->fetch()['cnt'];
        echo "   • {$c['class_name']} ({$c['section_name']}): {$cnt} student(s)\n";
    }

    echo "\n✓ Done! You can now delete this file for security.\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
