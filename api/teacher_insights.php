<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$pdo = getPDO();
$classId = $_GET['class_id'] ?? null;

if (!$classId) {
    jsonResponse(['error' => 'Missing class_id'], 400);
}

// Ownership gate: only the class teacher may read student insights.
$classStmt = $pdo->prepare("SELECT teacher_uid, class_name FROM classes WHERE id = ?");
$classStmt->execute([$classId]);
$class = $classStmt->fetch();
if (!$class || $class['teacher_uid'] !== $uid) {
    jsonResponse(['error' => 'Class not found'], 404);
}

// Enrolled students (existing accounts only, defends against orphan rows)
$rosterStmt = $pdo->prepare("SELECT cs.student_uid FROM class_students cs JOIN users u ON u.uid = cs.student_uid WHERE cs.class_id = ?");
$rosterStmt->execute([$classId]);
$roster = array_column($rosterStmt->fetchAll(), 'student_uid');

$students = [];
foreach ($roster as $suid) {
    $students[$suid] = [
        'student_uid' => $suid,
        'insight' => null,
        'pendingRefresh' => 0,
        'changedAt' => null,
        'attendance' => ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0, 'rate' => null],
        'terms' => [],
        'latestTerm' => null,
    ];
}

if (!empty($students)) {
    // Cached AI insights (generated from the student side, read-only here)
    $insightStmt = $pdo->prepare("SELECT student_uid, insight_paragraph, insight_tips, created_at, pending_refresh, changed_at FROM ai_insights WHERE class_id = ?");
    $insightStmt->execute([$classId]);
    foreach ($insightStmt->fetchAll() as $row) {
        if (isset($students[$row['student_uid']])) {
            $students[$row['student_uid']]['insight'] = [
                'paragraph' => $row['insight_paragraph'],
                'tips' => $row['insight_tips'] ? json_decode($row['insight_tips'], true) : [],
                'analyzed_at' => $row['created_at'],
            ];
            $students[$row['student_uid']]['pendingRefresh'] = (int)$row['pending_refresh'];
            $students[$row['student_uid']]['changedAt'] = $row['changed_at'];
        }
    }

    // Attendance per student — one pass for the whole class
    $attStmt = $pdo->prepare("SELECT student_uid,
        COUNT(*) AS total,
        SUM(CASE WHEN status IN ('Present', 'Verified') THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) AS late,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent
        FROM attendance WHERE class_id = ? GROUP BY student_uid");
    $attStmt->execute([$classId]);
    foreach ($attStmt->fetchAll() as $a) {
        $suid = $a['student_uid'];
        if (!isset($students[$suid])) continue;
        $total = (int)$a['total'];
        $present = (int)$a['present'];
        $late = (int)$a['late'];
        $students[$suid]['attendance'] = [
            'total' => $total,
            'present' => $present,
            'late' => $late,
            'absent' => (int)$a['absent'],
            'rate' => $total > 0 ? round((($present + $late) / $total) * 100) : null,
        ];
    }

    // Weights are per class — identical for every student
    $weightsStmt = $pdo->prepare("SELECT category, weight_percent FROM grade_weights WHERE class_id = ?");
    $weightsStmt->execute([$classId]);
    $weights = ['written' => 0, 'performance' => 0, 'exam' => 0, 'attendance' => 0];
    foreach ($weightsStmt->fetchAll() as $w) {
        $weights[$w['category']] = (int)$w['weight_percent'];
    }

    // Per-term grade math for the whole class (mirrors ai_insight.php)
    for ($q = 1; $q <= 3; $q++) {
        $compStmt = $pdo->prepare("SELECT id, category, name, hps FROM grade_components WHERE class_id = ? AND quarter = ? ORDER BY category, id");
        $compStmt->execute([$classId, $q]);
        $components = $compStmt->fetchAll();
        if (empty($components)) continue;

        $ids = array_column($components, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $gradeStmt = $pdo->prepare("SELECT component_id, student_uid, score FROM grades WHERE component_id IN ($placeholders)");
        $gradeStmt->execute($ids);
        $grades = [];
        foreach ($gradeStmt->fetchAll() as $g) {
            $grades[$g['student_uid']][$g['component_id']] = (float)$g['score'];
        }

        foreach ($students as $suid => &$s) {
            $stuGrades = $grades[$suid] ?? [];

            $catAvgs = [];
            foreach (['written', 'performance', 'exam', 'attendance'] as $cat) {
                $comps = array_values(array_filter($components, fn($c) => $c['category'] === $cat));
                $ts = 0;
                $th = 0;
                foreach ($comps as $c) {
                    if (isset($stuGrades[$c['id']])) {
                        $ts += $stuGrades[$c['id']];
                        $th += (int)$c['hps'];
                    }
                }
                $catAvgs[$cat] = $th > 0 ? round(($ts / $th) * 100, 1) : null;
            }

            $pending = 0;
            foreach ($components as $c) {
                if (!isset($stuGrades[$c['id']])) $pending++;
            }

            $finalTotal = 0;
            $finalWeight = 0;
            foreach ($catAvgs as $cat => $avg) {
                $w = $weights[$cat] ?? 0;
                if ($avg !== null && $w > 0) {
                    $finalTotal += $avg * ($w / 100);
                    $finalWeight += $w;
                }
            }

            $weak = null;
            foreach ($catAvgs as $cat => $avg) {
                if ($avg !== null && ($weak === null || $avg < $weak['avg'])) {
                    $weak = ['cat' => $cat, 'avg' => $avg];
                }
            }

            $s['terms'][$q] = [
                'finalGrade' => $finalWeight > 0 ? round($finalTotal, 1) : null,
                'weakestCategory' => $weak ? $weak['cat'] : null,
                'weakestAverage' => $weak ? $weak['avg'] : null,
                'pendingCount' => $pending,
            ];
            if (!empty($stuGrades)) $s['latestTerm'] = $q;
        }
        unset($s);
    }
}

jsonResponse(['class_id' => $classId, 'students' => array_values($students)]);