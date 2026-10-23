<?php
/**
 * generate_all_insights.php
 *
 * Batch-generates AI insights for every enrolled student in the 6 classes.
 * Streams progress to the browser in real time.
 * Uses Groq (primary) → Ollama (local) → Gemini (fallback).
 *
 * Access: http://localhost/ClassSense/generate_all_insights.php
 *
 * NOTE: This may take several minutes (206 students × ~1-2s per API call).
 *       Do NOT close the browser tab until you see "ALL DONE".
 */

require_once __DIR__ . '/api/config.php';

// Stream output immediately — no buffering
if (ob_get_level()) ob_end_clean();
header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');

set_time_limit(0);
ini_set('max_execution_time', 0);

function out(string $msg): void {
    echo $msg . "\n";
    if (ob_get_level()) ob_flush();
    flush();
}

out("ClassSense — Batch Insight Generator");
out("======================================");
out("Started: " . date('Y-m-d H:i:s'));
out("");

$targetClassNames = [
    'ICT - 7',
    'TLE - 8',
    'Math - 9',
    'Science - 10',
    'Oral Communication - 11',
    '21st Century Literature - 12',
];

try {
    $pdo = getPDO();

    // Load the 6 classes
    $placeholders = implode(',', array_fill(0, count($targetClassNames), '?'));
    $stmt = $pdo->prepare("
        SELECT id, class_name, section_name
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
    $stmt->execute($targetClassNames);
    $classes = $stmt->fetchAll();

    if (empty($classes)) {
        out("❌ No target classes found. Aborting.");
        exit(1);
    }

    // Count total students
    $totalStudents = 0;
    $classRosters  = [];
    foreach ($classes as $c) {
        $s = $pdo->prepare("
            SELECT cs.student_uid, u.first_name, u.last_name
            FROM class_students cs
            JOIN users u ON u.uid = cs.student_uid
            WHERE cs.class_id = ? AND u.role = 'student'
            ORDER BY u.last_name, u.first_name
        ");
        $s->execute([$c['id']]);
        $roster = $s->fetchAll();
        $classRosters[$c['id']] = $roster;
        $totalStudents += count($roster);
    }

    out("Classes: " . count($classes));
    out("Total students: {$totalStudents}");
    out("");

    $successCount = 0;
    $cachedCount  = 0;
    $failedCount  = 0;
    $studentNum   = 0;

    foreach ($classes as $c) {
        $classId   = $c['id'];
        $className = $c['class_name'];
        $section   = $c['section_name'];
        $roster    = $classRosters[$classId];

        out("──────────────────────────────────────────");
        out("📚 {$className} ({$section}) — " . count($roster) . " students");
        out("──────────────────────────────────────────");

        foreach ($roster as $student) {
            $studentNum++;
            $uid      = $student['student_uid'];
            $name     = trim($student['first_name'] . ' ' . $student['last_name']);
            $progress = "[{$studentNum}/{$totalStudents}]";

            try {
                $result = generateInsight($uid, $classId, false);

                if (!$result['ok']) {
                    $errLabel = $result['error'] ?? 'unknown';
                    out("  {$progress} ✗ {$name} — FAILED ({$errLabel})");
                    $failedCount++;

                    // If quota exhausted stop hammering the API
                    if ($errLabel === 'ai_quota_exceeded') {
                        out("");
                        out("⚠ AI quota exhausted — stopping early.");
                        out("Re-run this script later to continue.");
                        out("");
                        goto done;
                    }
                } elseif (!empty($result['cached'])) {
                    out("  {$progress} ↩ {$name} — cached (already up to date)");
                    $cachedCount++;
                } else {
                    $snippet = mb_substr($result['insight']['paragraph'] ?? '', 0, 60);
                    out("  {$progress} ✓ {$name} — generated: \"{$snippet}…\"");
                    $successCount++;
                }
            } catch (Throwable $e) {
                out("  {$progress} ✗ {$name} — EXCEPTION: " . $e->getMessage());
                $failedCount++;
            }

            // Small delay between API calls to avoid rate-limit (300ms)
            usleep(300000);
        }

        out("");
    }

    done:
    out("==========================================");
    out("Batch Complete: " . date('Y-m-d H:i:s'));
    out("==========================================");
    out("Generated (new)  : {$successCount}");
    out("Cached (skipped) : {$cachedCount}");
    out("Failed           : {$failedCount}");
    out("Total processed  : {$studentNum} / {$totalStudents}");
    out("");

    // Final DB count
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM ai_insights");
    $total = (int)$stmt->fetch()['cnt'];
    out("Total insights in database: {$total}");
    out("");
    out("ALL DONE. You can safely delete this file.");

} catch (Exception $e) {
    out("");
    out("❌ Fatal error: " . $e->getMessage());
    out($e->getTraceAsString());
    exit(1);
}
