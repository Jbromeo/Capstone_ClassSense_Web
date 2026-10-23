<?php
/**
 * generate_local_insights.php
 *
 * Generates realistic, data-driven AI insights for ALL 206 students
 * using their actual grades and attendance data — NO external API calls.
 * Stores results directly in ai_insights (SQL Server).
 *
 * Access: http://localhost/ClassSense/generate_local_insights.php
 */

require_once __DIR__ . '/api/config.php';

if (ob_get_level()) ob_end_clean();
header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');
set_time_limit(0);

function out(string $msg): void {
    echo $msg . "\n";
    if (ob_get_level()) ob_flush();
    flush();
}

// ─── Local insight generator ─────────────────────────────────────────────────
// Builds a realistic paragraph + tips from the student's actual data snapshot.
function buildLocalInsight(array $snap, string $className): array {
    $name        = $snap['name'] ?? 'the student';
    $firstName   = explode(' ', trim($name))[0];
    $attRate     = $snap['attendanceRate'] ?? null;
    $present     = $snap['present'] ?? 0;
    $late        = $snap['late'] ?? 0;
    $absent      = $snap['absent'] ?? 0;
    $totalDays   = $snap['totalDays'] ?? 0;
    $finalGrade  = $snap['finalGrade'] ?? null;
    $written     = $snap['writtenAvg'] ?? null;
    $performance = $snap['performanceAvg'] ?? null;
    $weakCat     = $snap['weakestCategory'] ?? null;

    // ── Attendance narrative ──────────────────────────────────────────────────
    if ($attRate === null || $totalDays === 0) {
        $attSentence = "{$firstName} has no recorded attendance yet.";
    } elseif ($attRate >= 95) {
        $attSentence = "{$firstName} has excellent attendance with a {$attRate}% presence rate across {$totalDays} recorded sessions" . ($late > 0 ? ", including {$late} late arrival(s)" : "") . ".";
    } elseif ($attRate >= 85) {
        $attSentence = "{$firstName} maintains good attendance at {$attRate}%" . ($absent > 0 ? ", with {$absent} absence(s) noted" : "") . " over {$totalDays} sessions.";
    } elseif ($attRate >= 70) {
        $attSentence = "{$firstName}'s attendance stands at {$attRate}% across {$totalDays} sessions, with {$absent} absence(s) and {$late} late arrival(s) — there is room for improvement in consistency.";
    } else {
        $attSentence = "{$firstName}'s attendance is concerning at {$attRate}% ({$absent} absence(s) out of {$totalDays} sessions), which may be impacting overall academic performance.";
    }

    // ── Grade narrative ───────────────────────────────────────────────────────
    $gradeNarrative = '';
    if ($finalGrade !== null) {
        $gradeStr = number_format($finalGrade, 1);
        if ($finalGrade >= 90) {
            $gradeNarrative = "Academic performance is outstanding with a final grade of {$gradeStr}%, demonstrating mastery across all assessed components.";
        } elseif ($finalGrade >= 85) {
            $gradeNarrative = "Academic performance is commendable with a final grade of {$gradeStr}%, reflecting strong understanding of {$className} content.";
        } elseif ($finalGrade >= 75) {
            $gradeNarrative = "Academic performance is satisfactory with a final grade of {$gradeStr}%, meeting the minimum passing standard for {$className}.";
        } elseif ($finalGrade >= 65) {
            $gradeNarrative = "Academic performance is below the passing threshold with a final grade of {$gradeStr}% in {$className}. Focused effort is needed to reach the 75% minimum.";
        } else {
            $gradeNarrative = "Academic performance is critically low with a final grade of {$gradeStr}% in {$className}. Immediate intervention and additional support are recommended.";
        }
    } else {
        $gradeNarrative = "A final grade has not yet been computed — more assessment components need to be scored before a complete picture emerges.";
    }

    // ── Component breakdown ───────────────────────────────────────────────────
    $componentDetail = '';
    $parts = [];
    if ($written !== null) {
        $wStr = number_format($written, 1);
        $parts[] = "Written Works average of {$wStr}%";
    }
    if ($performance !== null) {
        $pStr = number_format($performance, 1);
        $parts[] = "Performance Tasks average of {$pStr}%";
    }
    if (!empty($parts)) {
        $componentDetail = " {$firstName} has a " . implode(' and a ', $parts) . ".";
    }

    // ── Weakest category note ─────────────────────────────────────────────────
    $weakNote = '';
    $catLabels = [
        'written'     => 'Written Works',
        'performance' => 'Performance Tasks',
        'exam'        => 'Examinations',
        'attendance'  => 'Attendance',
    ];
    if ($weakCat && isset($catLabels[$weakCat])) {
        $wcLabel  = $catLabels[$weakCat];
        $weakNote = " The weakest area is {$wcLabel}, which should be prioritized for improvement.";
    }

    // ── Combine paragraph ─────────────────────────────────────────────────────
    $paragraph = trim("{$attSentence} {$gradeNarrative}{$componentDetail}{$weakNote}");

    // ── Generate contextual tips ──────────────────────────────────────────────
    $tips = [];

    // Attendance tips
    if ($attRate !== null && $attRate < 90) {
        $tips[] = "Aim for 100% attendance in upcoming sessions — every missed class directly impacts quiz and performance scores.";
    }
    if ($late > 1) {
        $tips[] = "Arriving on time ensures you don't miss important instructions or the start of activities. Set a morning routine or alarm reminder.";
    }

    // Written Works tips
    if ($written !== null && $written < 75) {
        $tips[] = "Review previous quiz items and focus on topics where points were lost. Ask the teacher for clarifications on areas you find difficult.";
    } elseif ($written !== null && $written >= 90) {
        $tips[] = "Excellent quiz performance! Maintain this by reviewing notes the night before each assessment.";
    } else {
        $tips[] = "Practice answering past quiz questions to reinforce understanding of key concepts in {$className}.";
    }

    // Performance tips
    if ($performance !== null && $performance < 75) {
        $tips[] = "Participate actively in performance tasks and activities. Practice outputs before submission to improve quality and scores.";
    } elseif ($performance !== null && $performance >= 85) {
        $tips[] = "Strong performance task scores! Keep applying this effort and consider helping classmates who may be struggling.";
    } else {
        $tips[] = "Give each performance task your full effort — these activities carry significant weight in computing your final grade.";
    }

    // Final grade tips
    if ($finalGrade !== null && $finalGrade < 75) {
        $tips[] = "Set aside dedicated study time each day for {$className}. If struggling, seek the teacher's guidance during available consultation hours.";
        $tips[] = "Track which components are pulling your grade down and focus your energy there first.";
    } elseif ($finalGrade !== null && $finalGrade >= 90) {
        $tips[] = "Outstanding work! Consider deepening your knowledge further by exploring enrichment materials related to {$className}.";
    } else {
        $tips[] = "Stay consistent with your current effort level — small improvements across all components can meaningfully raise your final grade.";
    }

    // Trim to 3-4 most relevant tips
    $tips = array_slice(array_unique($tips), 0, 4);

    return ['paragraph' => $paragraph, 'tips' => $tips];
}

// ─── Snapshot builder (mirrors buildInsightSnapshot in config.php) ────────────
function buildStudentSnapshot(PDO $pdo, string $studentUid, string $classId): array {
    // Student name
    $s = $pdo->prepare("SELECT first_name, last_name FROM users WHERE uid = ?");
    $s->execute([$studentUid]);
    $u = $s->fetch();
    $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));

    // Class name
    $cs = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
    $cs->execute([$classId]);
    $className = $cs->fetch()['class_name'] ?? 'Unknown Class';

    // Attendance
    $as = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status IN ('Present','Verified') THEN 1 ELSE 0 END) AS present,
            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) AS late,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent
        FROM attendance WHERE class_id = ? AND student_uid = ?
    ");
    $as->execute([$classId, $studentUid]);
    $att = $as->fetch();
    $total   = (int)($att['total'] ?? 0);
    $present = (int)($att['present'] ?? 0);
    $late    = (int)($att['late'] ?? 0);
    $absent  = (int)($att['absent'] ?? 0);
    $attRate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : null;

    // Weights
    $ws = $pdo->prepare("SELECT category, weight_percent FROM grade_weights WHERE class_id = ?");
    $ws->execute([$classId]);
    $weights = ['written' => 0, 'performance' => 0, 'exam' => 0, 'attendance' => 0];
    foreach ($ws->fetchAll() as $w) $weights[$w['category']] = (int)$w['weight_percent'];

    // Components + grades (Term 1)
    $comps = $pdo->prepare("SELECT id, category, name, hps FROM grade_components WHERE class_id = ? AND quarter = 1 ORDER BY category, id");
    $comps->execute([$classId]);
    $components = $comps->fetchAll();

    $ids = array_column($components, 'id');
    $grades = [];
    if (!empty($ids)) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $gs = $pdo->prepare("SELECT component_id, score FROM grades WHERE component_id IN ($ph) AND student_uid = ?");
        $gs->execute(array_merge($ids, [$studentUid]));
        foreach ($gs->fetchAll() as $g) $grades[$g['component_id']] = (float)$g['score'];
    }

    // Category averages
    $catAvgs = [];
    foreach (['written', 'performance', 'exam', 'attendance'] as $cat) {
        $catComps = array_filter($components, fn($c) => $c['category'] === $cat);
        $ts = 0; $th = 0;
        foreach ($catComps as $c) {
            if (isset($grades[$c['id']])) { $ts += $grades[$c['id']]; $th += (int)$c['hps']; }
        }
        $catAvgs[$cat] = $th > 0 ? round(($ts / $th) * 100, 1) : null;
    }

    // Final grade
    $finalTotal = 0; $finalWeight = 0;
    foreach ($catAvgs as $cat => $avg) {
        $w = $weights[$cat] ?? 0;
        if ($avg !== null && $w > 0) { $finalTotal += $avg * ($w / 100); $finalWeight += $w; }
    }
    $finalGrade = $finalWeight > 0 ? round($finalTotal, 1) : null;

    // Weakest category
    $weakCat = null; $weakVal = null;
    foreach ($catAvgs as $cat => $avg) {
        if ($avg !== null && ($weakVal === null || $avg < $weakVal)) { $weakCat = $cat; $weakVal = $avg; }
    }

    return [
        'name'            => $name,
        'className'       => $className,
        'attendanceRate'  => $attRate,
        'totalDays'       => $total,
        'present'         => $present,
        'late'            => $late,
        'absent'          => $absent,
        'writtenAvg'      => $catAvgs['written'],
        'performanceAvg'  => $catAvgs['performance'],
        'finalGrade'      => $finalGrade,
        'weakestCategory' => $weakCat,
    ];
}

// ─── Main ─────────────────────────────────────────────────────────────────────

out("ClassSense — Local Insight Generator (No API)");
out("===============================================");
out("Started: " . date('Y-m-d H:i:s'));
out("");

$targetClasses = [
    'ICT - 7', 'TLE - 8', 'Math - 9',
    'Science - 10', 'Oral Communication - 11', '21st Century Literature - 12',
];

try {
    $pdo = getPDO();

    // Load classes
    $ph = implode(',', array_fill(0, count($targetClasses), '?'));
    $stmt = $pdo->prepare("SELECT id, class_name, section_name FROM classes WHERE class_name IN ({$ph}) ORDER BY class_name");
    $stmt->execute($targetClasses);
    $classes = $stmt->fetchAll();

    // Upsert statement
    $upsert = $pdo->prepare("
        MERGE ai_insights AS target
        USING (SELECT ? AS student_uid, ? AS class_id) AS source
        ON target.student_uid = source.student_uid AND target.class_id = source.class_id
        WHEN MATCHED THEN UPDATE SET
            insight_paragraph = ?,
            insight_tips      = ?,
            signature         = ?,
            created_at        = GETDATE(),
            pending_refresh   = 0,
            changed_at        = NULL
        WHEN NOT MATCHED THEN INSERT
            (student_uid, class_id, insight_paragraph, insight_tips, signature, pending_refresh, changed_at)
        VALUES
            (source.student_uid, source.class_id, ?, ?, ?, 0, NULL);
    ");

    $totalStudents = 0;
    $successCount  = 0;
    $failedCount   = 0;
    $studentNum    = 0;

    foreach ($classes as $c) {
        $classId   = $c['id'];
        $className = $c['class_name'];
        $section   = $c['section_name'];

        // Load roster
        $rs = $pdo->prepare("
            SELECT cs.student_uid, u.first_name, u.last_name
            FROM class_students cs
            JOIN users u ON u.uid = cs.student_uid
            WHERE cs.class_id = ? AND u.role = 'student'
            ORDER BY u.last_name, u.first_name
        ");
        $rs->execute([$classId]);
        $roster = $rs->fetchAll();
        $totalStudents += count($roster);

        out("──────────────────────────────────────────────────");
        out("📚 {$className} ({$section}) — " . count($roster) . " students");
        out("──────────────────────────────────────────────────");

        foreach ($roster as $student) {
            $studentNum++;
            $uid      = $student['student_uid'];
            $name     = strtoupper(trim($student['first_name'] . ' ' . $student['last_name']));
            $progress = "[{$studentNum}/{$totalStudents}]";

            try {
                // Build data snapshot
                $snap    = buildStudentSnapshot($pdo, $uid, $classId);
                // Generate local insight
                $insight = buildLocalInsight($snap, $className);

                $paragraph = $insight['paragraph'];
                $tipsJson  = json_encode($insight['tips']);
                $signature = md5($paragraph);

                $upsert->execute([
                    $uid, $classId,         // USING source
                    $paragraph, $tipsJson, $signature,  // WHEN MATCHED
                    $paragraph, $tipsJson, $signature,  // WHEN NOT MATCHED
                ]);

                $snippet = mb_substr($paragraph, 0, 70);
                out("  {$progress} ✓ {$name} — \"{$snippet}…\"");
                $successCount++;
            } catch (Throwable $e) {
                out("  {$progress} ✗ {$name} — ERROR: " . $e->getMessage());
                $failedCount++;
            }
        }
        out("");
    }

    out("==========================================");
    out("COMPLETE — " . date('Y-m-d H:i:s'));
    out("==========================================");
    out("Generated : {$successCount}");
    out("Failed    : {$failedCount}");
    out("Total     : {$studentNum} / {$totalStudents}");
    out("");

    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM ai_insights");
    out("Total insights in database: " . $stmt->fetch()['cnt']);
    out("");
    out("ALL DONE. Safe to delete this file.");

} catch (Exception $e) {
    out("");
    out("❌ Fatal: " . $e->getMessage());
    out($e->getTraceAsString());
    exit(1);
}
