<?php
/**
 * seed_attendance_and_grades.php
 *
 * Seeds REALISTIC attendance (Oct 20-23, 2026) and grade data for all 6 classes.
 * - Attendance: 4 days, ~85-90% Present, 5-8% Late, 3-7% Absent
 * - Weights:    Written 25%, Performance 40%, Exam 25%, Attendance 10%
 * - Quizzes & Activities vary per class (different counts and HPS)
 * - Scores:     Realistic bell curve — most 60-90%, few high achievers, few struggling
 * - No exam components (deferred)
 *
 * Access: http://localhost/ClassSense/seed_attendance_and_grades.php
 */

require_once __DIR__ . '/api/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "ClassSense — Seed Attendance & Grades\n";
echo "========================================\n\n";

// ─── Configuration ──────────────────────────────────────────────────────────

$ATTENDANCE_DATES = ['2026-10-20', '2026-10-21', '2026-10-22', '2026-10-23'];
$DATE_LABELS      = ['10/20/26',   '10/21/26',   '10/22/26',   '10/23/26'];
$WEIGHTS = ['written' => 25, 'performance' => 40, 'exam' => 25, 'attendance' => 10];

// Each class gets a UNIQUE set of quizzes and activities with varied HPS
$CLASS_COMPONENTS = [
    'ICT - 7' => [
        'written'     => [['name' => 'Quiz 1', 'hps' => 30], ['name' => 'Quiz 2', 'hps' => 50], ['name' => 'Quiz 3', 'hps' => 40]],
        'performance' => [['name' => 'Hands-on Activity 1', 'hps' => 50], ['name' => 'Lab Exercise 1', 'hps' => 30]],
    ],
    'TLE - 8' => [
        'written'     => [['name' => 'Quiz 1', 'hps' => 20], ['name' => 'Quiz 2', 'hps' => 50]],
        'performance' => [['name' => 'Practical Work 1', 'hps' => 40], ['name' => 'Practical Work 2', 'hps' => 50], ['name' => 'Project Output', 'hps' => 60]],
    ],
    'Math - 9' => [
        'written'     => [['name' => 'Quiz 1', 'hps' => 50], ['name' => 'Quiz 2', 'hps' => 30], ['name' => 'Quiz 3', 'hps' => 50], ['name' => 'Quiz 4', 'hps' => 20]],
        'performance' => [['name' => 'Problem Set 1', 'hps' => 50], ['name' => 'Board Work Activity', 'hps' => 30]],
    ],
    'Science - 10' => [
        'written'     => [['name' => 'Quiz 1', 'hps' => 40], ['name' => 'Quiz 2', 'hps' => 40]],
        'performance' => [['name' => 'Lab Report 1', 'hps' => 50], ['name' => 'Experiment Output', 'hps' => 60]],
    ],
    'Oral Communication - 11' => [
        'written'     => [['name' => 'Quiz 1', 'hps' => 25], ['name' => 'Quiz 2', 'hps' => 50], ['name' => 'Quiz 3', 'hps' => 30]],
        'performance' => [['name' => 'Speech Delivery 1', 'hps' => 50], ['name' => 'Group Discussion', 'hps' => 40], ['name' => 'Impromptu Speaking', 'hps' => 30]],
    ],
    '21st Century Literature - 12' => [
        'written'     => [['name' => 'Quiz 1', 'hps' => 30], ['name' => 'Quiz 2', 'hps' => 40]],
        'performance' => [['name' => 'Literary Analysis Paper', 'hps' => 60], ['name' => 'Creative Writing Piece', 'hps' => 50]],
    ],
];

// ─── Helper: realistic score generator ──────────────────────────────────────
// Bell-curve distribution: most students 60-90%, high achievers 90-100%, struggling 40-65%
function realisticScore(int $hps): float {
    $r = mt_rand(1, 100);
    if ($r <= 8) {
        // ~8% struggling (40-65% of HPS)
        $pct = mt_rand(40, 65) / 100;
    } elseif ($r <= 22) {
        // ~14% high achievers (90-100% of HPS)
        $pct = mt_rand(90, 100) / 100;
    } elseif ($r <= 55) {
        // ~33% solid (75-89% of HPS)
        $pct = mt_rand(75, 89) / 100;
    } else {
        // ~45% average (60-79% of HPS)
        $pct = mt_rand(60, 79) / 100;
    }
    $raw = round($hps * $pct);
    return min($hps, max(0, $raw));
}

try {
    $pdo = getPDO();

    // ─── Step 1: Load the 6 classes and their students ──────────────────────
    echo "Step 1: Loading classes and students...\n";
    echo "----------------------------------------\n";

    $classNames = array_keys($CLASS_COMPONENTS);
    $placeholders = implode(',', array_fill(0, count($classNames), '?'));
    $stmt = $pdo->prepare("
        SELECT id, class_name, section_name, teacher_uid, teacher_name
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
    $stmt->execute($classNames);
    $classes = $stmt->fetchAll();

    $classStudents = [];
    foreach ($classes as $c) {
        $stmt = $pdo->prepare("
            SELECT cs.student_uid
            FROM class_students cs
            JOIN users u ON u.uid = cs.student_uid
            WHERE cs.class_id = ? AND u.role = 'student'
        ");
        $stmt->execute([$c['id']]);
        $uids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $classStudents[$c['id']] = $uids;
        echo "  ✓ {$c['class_name']} ({$c['section_name']}): " . count($uids) . " students\n";
    }
    echo "\n";

    // ─── Step 2: Set grading weights for every class ────────────────────────
    echo "Step 2: Setting grading weights (W25/P40/E25/A10)...\n";
    echo "-----------------------------------------------------\n";

    $mergeWeight = $pdo->prepare("
        MERGE grade_weights AS target
        USING (SELECT ? AS class_id, ? AS category) AS source
        ON target.class_id = source.class_id AND target.category = source.category
        WHEN MATCHED THEN UPDATE SET weight_percent = ?
        WHEN NOT MATCHED THEN INSERT (class_id, category, weight_percent) VALUES (?, ?, ?);
    ");

    foreach ($classes as $c) {
        foreach ($WEIGHTS as $cat => $w) {
            $mergeWeight->execute([$c['id'], $cat, $w, $c['id'], $cat, $w]);
        }
        echo "  ✓ {$c['class_name']}: Written {$WEIGHTS['written']}% | Performance {$WEIGHTS['performance']}% | Exam {$WEIGHTS['exam']}% | Attendance {$WEIGHTS['attendance']}%\n";
    }
    echo "\n";

    // ─── Step 3: Seed attendance records ─────────────────────────────────────
    echo "Step 3: Seeding attendance (Oct 20-23)...\n";
    echo "-------------------------------------------\n";

    // Pre-clean existing attendance for these dates
    foreach ($classes as $c) {
        foreach ($ATTENDANCE_DATES as $d) {
            $pdo->prepare("DELETE FROM attendance WHERE class_id = ? AND CAST(date AS DATE) = ?")->execute([$c['id'], $d]);
        }
    }

    // Also pre-clean attendance grade components
    foreach ($classes as $c) {
        foreach ($DATE_LABELS as $lbl) {
            $pdo->prepare("DELETE FROM grade_components WHERE class_id = ? AND category = 'attendance' AND name = ?")->execute([$c['id'], $lbl]);
        }
    }

    $insertAtt = $pdo->prepare("
        INSERT INTO attendance (student_uid, class_id, date, timestamp, status, session_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $insertComp = $pdo->prepare("
        INSERT INTO grade_components (class_id, category, name, hps, quarter, session_id)
        VALUES (?, 'attendance', ?, 10, 1, ?)
    ");

    $mergeGrade = $pdo->prepare("
        MERGE grades AS target
        USING (SELECT ? AS component_id, ? AS student_uid) AS source
        ON target.component_id = source.component_id AND target.student_uid = source.student_uid
        WHEN MATCHED THEN UPDATE SET score = ?, updated_at = GETDATE()
        WHEN NOT MATCHED THEN INSERT (component_id, student_uid, score) VALUES (?, ?, ?);
    ");

    $totalAttRecords = 0;

    foreach ($classes as $c) {
        $students = $classStudents[$c['id']];
        $numStudents = count($students);
        $classLabel = $c['class_name'];

        for ($di = 0; $di < count($ATTENDANCE_DATES); $di++) {
            $dateStr  = $ATTENDANCE_DATES[$di];
            $label    = $DATE_LABELS[$di];
            $sessionId = strtolower(bin2hex(random_bytes(16)));

            // Create attendance grade component for this date
            $insertComp->execute([$c['id'], $label, $sessionId]);
            $compId = $pdo->lastInsertId();

            // Determine realistic counts
            $absentCount = (int)round($numStudents * (mt_rand(3, 7) / 100));
            $lateCount   = (int)round($numStudents * (mt_rand(5, 10) / 100));
            $presentCount = $numStudents - $absentCount - $lateCount;

            // Shuffle students and assign statuses
            $shuffled = $students;
            shuffle($shuffled);

            $statusMap = [];
            $idx = 0;
            for ($i = 0; $i < $absentCount && $idx < $numStudents; $i++, $idx++) {
                $statusMap[$shuffled[$idx]] = 'Absent';
            }
            for ($i = 0; $i < $lateCount && $idx < $numStudents; $i++, $idx++) {
                $statusMap[$shuffled[$idx]] = 'Late';
            }
            for (; $idx < $numStudents; $idx++) {
                $statusMap[$shuffled[$idx]] = 'Present';
            }

            // Insert attendance records and grade scores
            foreach ($statusMap as $uid => $status) {
                // Attendance record
                $timestamp = $dateStr . ' 07:' . str_pad(mt_rand(25, 59), 2, '0', STR_PAD_LEFT) . ':' . str_pad(mt_rand(0, 59), 2, '0', STR_PAD_LEFT);
                $insertAtt->execute([$uid, $c['id'], $dateStr, $timestamp, $status, $sessionId]);

                // Grade score: Present=10, Late=5, Absent=0
                $score = ($status === 'Present') ? 10 : (($status === 'Late') ? 5 : 0);
                $mergeGrade->execute([$compId, $uid, $score, $compId, $uid, $score]);
                $totalAttRecords++;
            }
        }

        echo "  ✓ {$classLabel}: 4 days × {$numStudents} students = " . ($numStudents * 4) . " records\n";
    }
    echo "  Total attendance records: {$totalAttRecords}\n\n";

    // ─── Step 4: Create quiz & performance components + scores ──────────────
    echo "Step 4: Seeding quizzes & performance tasks...\n";
    echo "-------------------------------------------------\n";

    // Pre-clean existing written/performance components for these classes
    foreach ($classes as $c) {
        // Delete grades for existing components first (FK safe)
        $pdo->prepare("
            DELETE g FROM grades g
            INNER JOIN grade_components gc ON g.component_id = gc.id
            WHERE gc.class_id = ? AND gc.category IN ('written', 'performance') AND gc.quarter = 1
        ")->execute([$c['id']]);
        $pdo->prepare("
            DELETE FROM grade_components
            WHERE class_id = ? AND category IN ('written', 'performance') AND quarter = 1
        ")->execute([$c['id']]);
    }

    $insertCompGeneric = $pdo->prepare("
        INSERT INTO grade_components (class_id, category, name, hps, quarter)
        VALUES (?, ?, ?, ?, 1)
    ");

    $totalGradeRecords = 0;

    foreach ($classes as $c) {
        $className  = $c['class_name'];
        $classId    = $c['id'];
        $students   = $classStudents[$classId];
        $components = $CLASS_COMPONENTS[$className] ?? [];

        foreach (['written', 'performance'] as $cat) {
            $catComps = $components[$cat] ?? [];
            foreach ($catComps as $comp) {
                $insertCompGeneric->execute([$classId, $cat, $comp['name'], $comp['hps']]);
                $compId = $pdo->lastInsertId();

                foreach ($students as $uid) {
                    $score = realisticScore($comp['hps']);
                    $mergeGrade->execute([$compId, $uid, $score, $compId, $uid, $score]);
                    $totalGradeRecords++;
                }

                $catLabel = ($cat === 'written') ? 'Quiz' : 'Performance';
                echo "  ✓ {$className} → [{$catLabel}] {$comp['name']} (HPS: {$comp['hps']}) — " . count($students) . " scores\n";
            }
        }
    }
    echo "\n  Total grade scores created: {$totalGradeRecords}\n\n";

    // ─── Step 5: Summary ─────────────────────────────────────────────────────
    echo "========================================\n";
    echo "Summary\n";
    echo "========================================\n\n";

    foreach ($classes as $c) {
        $className = $c['class_name'];
        $numStudents = count($classStudents[$c['id']]);
        $components = $CLASS_COMPONENTS[$className] ?? [];
        $writtenCount = count($components['written'] ?? []);
        $perfCount    = count($components['performance'] ?? []);

        echo "📚 {$className} ({$c['section_name']})\n";
        echo "   Teacher:     {$c['teacher_name']}\n";
        echo "   Students:    {$numStudents}\n";
        echo "   Attendance:  4 days (Oct 20-23)\n";
        echo "   Written:     {$writtenCount} component(s)\n";
        echo "   Performance: {$perfCount} component(s)\n";
        echo "   Weights:     W25% / P40% / E25% / A10%\n\n";
    }

    // Final DB verification
    echo "Database Verification:\n";
    echo "----------------------\n";
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM attendance");
    echo "  Total attendance records: " . $stmt->fetch()['cnt'] . "\n";
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM grade_components WHERE quarter = 1");
    echo "  Total grade components (Term 1): " . $stmt->fetch()['cnt'] . "\n";
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM grades");
    echo "  Total grade entries: " . $stmt->fetch()['cnt'] . "\n";

    echo "\n✓ Done! You can safely delete this file.\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
