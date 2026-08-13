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

// Enrollment gate: students may only read insights for classes they are enrolled in.
$stmt = $pdo->prepare("SELECT 1 FROM class_students WHERE class_id = ? AND student_uid = ?");
$stmt->execute([$classId, $uid]);
if (!$stmt->fetch()) {
    jsonResponse(['error' => 'Class not found'], 404);
}

$classStmt = $pdo->prepare("SELECT id, class_name, subject, schedule FROM classes WHERE id = ?");
$classStmt->execute([$classId]);
$classData = $classStmt->fetch();
$className = $classData['class_name'] ?? 'your class';

// ---------- Data snapshot ----------

// Attendance
$attStmt = $pdo->prepare("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('Present', 'Verified') THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
FROM attendance WHERE class_id = ? AND student_uid = ?");
$attStmt->execute([$classId, $uid]);
$att = $attStmt->fetch();

$total = (int)($att['total'] ?? 0);
$present = (int)($att['present'] ?? 0) + (int)($att['late'] ?? 0);
$late = (int)($att['late'] ?? 0);
$absent = (int)($att['absent'] ?? 0);
$attRate = $total > 0 ? round(($present / $total) * 100) : null;

$trendStmt = $pdo->prepare("SELECT TOP 5 status FROM attendance WHERE class_id = ? AND student_uid = ? ORDER BY timestamp DESC");
$trendStmt->execute([$classId, $uid]);
$attTrend = array_map(fn($r) => $r['status'], $trendStmt->fetchAll());

// Grades across all terms
$terms = [];
for ($q = 1; $q <= 3; $q++) {
    $compStmt = $pdo->prepare("SELECT id, category, name, hps FROM grade_components WHERE class_id = ? AND quarter = ? ORDER BY category, id");
    $compStmt->execute([$classId, $q]);
    $components = $compStmt->fetchAll();

    $grades = [];
    $ids = array_column($components, 'id');
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $gradeStmt = $pdo->prepare("SELECT component_id, score FROM grades WHERE component_id IN ($placeholders) AND student_uid = ?");
        $gradeStmt->execute(array_merge($ids, [$uid]));
        foreach ($gradeStmt->fetchAll() as $g) {
            $grades[$g['component_id']] = (float)$g['score'];
        }
    }

    $weightsStmt = $pdo->prepare("SELECT category, weight_percent FROM grade_weights WHERE class_id = ?");
    $weightsStmt->execute([$classId]);
    $weights = ['written' => 0, 'performance' => 0, 'exam' => 0, 'attendance' => 0];
    foreach ($weightsStmt->fetchAll() as $w) {
        $weights[$w['category']] = (int)$w['weight_percent'];
    }

    $catAvgs = [];
    foreach (['written', 'performance', 'exam', 'attendance'] as $cat) {
        $comps = array_values(array_filter($components, fn($c) => $c['category'] === $cat));
        $ts = 0;
        $th = 0;
        foreach ($comps as $c) {
            if (isset($grades[$c['id']])) {
                $ts += $grades[$c['id']];
                $th += (int)$c['hps'];
            }
        }
        $catAvgs[$cat] = $th > 0 ? round(($ts / $th) * 100, 1) : null;
    }

    $pending = 0;
    foreach ($components as $c) {
        if (!isset($grades[$c['id']])) $pending++;
    }

    // Final grade (same math as the teacher's grading center)
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

    $weightedCategories = array_map('strval', array_keys(array_filter($weights, fn($w) => $w > 0)));
    $gradedCount = 0;
    foreach ($weightedCategories as $cat) {
        $catComps = array_values(array_filter($components, fn($c) => $c['category'] === $cat));
        $hasComp = count($catComps) > 0;
        $hasScore = false;
        foreach ($catComps as $c) {
            if (isset($grades[$c['id']])) { $hasScore = true; break; }
        }
        if ($hasComp && $hasScore) $gradedCount++;
    }
    $completeTerm = count($weightedCategories) > 0 && $gradedCount === count($weightedCategories);

    $terms[$q] = [
        'categoryAverages' => $catAvgs,
        'pendingCount' => $pending,
        'finalGrade' => $finalWeight > 0 ? round($finalTotal, 1) : null,
        'weakestCategory' => $weak ? $weak['cat'] : null,
        'weakestAverage' => $weak ? $weak['avg'] : null,
        'gradedCount' => $gradedCount,
        'weightedCategories' => count($weightedCategories),
        'complete' => $completeTerm,
    ];
}

$snapshot = [
    'attendance' => ['total' => $total, 'present' => $present, 'late' => $late, 'absent' => $absent, 'rate' => $attRate, 'trend' => $attTrend],
    'terms' => $terms,
    'weights' => $weights,
];
$signature = sha1(json_encode($snapshot));

// ---------- Cached insight lookup ----------

$cacheStmt = $pdo->prepare("SELECT insight_paragraph, insight_tips, signature, created_at FROM ai_insights WHERE student_uid = ? AND class_id = ?");
$cacheStmt->execute([$uid, $classId]);
$cached = $cacheStmt->fetch();

$CACHE_TTL_SECONDS = 6 * 3600;
$cachedAge = null;
if ($cached) {
    $created = strtotime($cached['created_at']);
    $cachedAge = time() - $created;
}

if ($cached && $cached['signature'] === $signature && $cachedAge !== null && $cachedAge < $CACHE_TTL_SECONDS) {
    jsonResponse([
        'insight' => [
            'paragraph' => $cached['insight_paragraph'],
            'tips' => $cached['insight_tips'] ? json_decode($cached['insight_tips'], true) : [],
        ],
        'cached' => true,
        'analyzedAt' => $cached['created_at'],
    ]);
}

// ---------- Gemini (Interactions API) ----------

$aiConfigPath = dirname(__DIR__) . '/AI/config.php';
if (!file_exists($aiConfigPath)) {
    error_log('AI config missing at ' . $aiConfigPath);
    if ($cached) {
        jsonResponse([
            'insight' => ['paragraph' => $cached['insight_paragraph'], 'tips' => $cached['insight_tips'] ? json_decode($cached['insight_tips'], true) : []],
            'cached' => true,
            'analyzedAt' => $cached['created_at'],
        ]);
    }
    jsonResponse(['available' => false, 'error' => 'AI not configured'], 200);
}

$aiConfig = require $aiConfigPath;
$apiKey = $aiConfig['gemini_api_key'] ?? '';
$model = $aiConfig['gemini_model'] ?? 'gemini-3.6-flash';
$endpoint = $aiConfig['gemini_endpoint'] ?? 'https://generativelanguage.googleapis.com/v1beta/interactions';
$apiRevision = $aiConfig['gemini_api_revision'] ?? '2026-05-20';

if ($apiKey === '') {
    if ($cached) {
        jsonResponse([
            'insight' => ['paragraph' => $cached['insight_paragraph'], 'tips' => $cached['insight_tips'] ? json_decode($cached['insight_tips'], true) : []],
            'cached' => true,
            'analyzedAt' => $cached['created_at'],
        ]);
    }
    jsonResponse(['available' => false, 'error' => 'AI not configured'], 200);
}

$prompt = buildInsightPrompt($className, $snapshot);

$payload = json_encode([
    'model' => $model,
    'store' => false,
    'input' => $prompt,
    'response_format' => [[
        'type' => 'object',
        'properties' => [
            'paragraph' => ['type' => 'string'],
            'tips' => ['type' => 'array', 'items' => ['type' => 'string']],
        ],
        'required' => ['paragraph', 'tips'],
    ]],
]);

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
        'Api-Revision: ' . $apiRevision,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 40,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $response === false) {
    error_log('Gemini call failed: HTTP ' . $httpCode . ' ' . ($curlError ?: $response));
    if ($cached) {
        jsonResponse([
            'insight' => ['paragraph' => $cached['insight_paragraph'], 'tips' => $cached['insight_tips'] ? json_decode($cached['insight_tips'], true) : []],
            'cached' => true,
            'analyzedAt' => $cached['created_at'],
        ]);
    }
    jsonResponse(['available' => false, 'error' => 'AI request failed'], 200);
}

$parsed = json_decode($response, true);
$text = null;
if (isset($parsed['steps']) && is_array($parsed['steps'])) {
    foreach ($parsed['steps'] as $step) {
        if (($step['type'] ?? '') === 'model_output' && !empty($step['content']) && is_array($step['content'])) {
            foreach ($step['content'] as $part) {
                if (isset($part['text'])) {
                    $text = $part['text'];
                    break 2;
                }
            }
        }
    }
}

$insight = null;
if ($text) {
    $decoded = json_decode($text, true);
    if (is_array($decoded) && !empty($decoded['paragraph'])) {
        $insight = [
            'paragraph' => trim($decoded['paragraph']),
            'tips' => array_values(array_filter(array_map('trim', $decoded['tips'] ?? []))),
        ];
    }
}

if ($insight === null) {
    error_log('Gemini returned unparseable insight: ' . $text);
    if ($cached) {
        jsonResponse([
            'insight' => ['paragraph' => $cached['insight_paragraph'], 'tips' => $cached['insight_tips'] ? json_decode($cached['insight_tips'], true) : []],
            'cached' => true,
            'analyzedAt' => $cached['created_at'],
        ]);
    }
    jsonResponse(['available' => false, 'error' => 'AI response unreadable'], 200);
}

// ---------- Persist + notify ----------

$stmt = $pdo->prepare("MERGE ai_insights AS target USING (SELECT ? AS student_uid, ? AS class_id) AS source
    ON target.student_uid = source.student_uid AND target.class_id = source.class_id
    WHEN MATCHED THEN UPDATE SET insight_paragraph = ?, insight_tips = ?, signature = ?, created_at = GETDATE()
    WHEN NOT MATCHED THEN INSERT (student_uid, class_id, insight_paragraph, insight_tips, signature)
    VALUES (source.student_uid, source.class_id, ?, ?, ?);");
$stmt->execute([$uid, $classId, $insight['paragraph'], json_encode($insight['tips']), $signature, $insight['paragraph'], json_encode($insight['tips']), $signature]);

$isNew = !$cached || $cached['signature'] !== $signature;
if ($isNew) {
    $cooldownStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE recipient_uid = ? AND type = 'ai_insight' AND created_at > DATEADD(HOUR, -24, GETDATE())");
    $cooldownStmt->execute([$uid]);
    if ((int)$cooldownStmt->fetch()['cnt'] === 0) {
        sendNotification(
            $uid,
            'ai_insight',
            'New Academic Insight',
            "AI analyzed your performance in {$className}. Open it to see tips.",
            '../student_screen/student_class_view.php?id=' . urlencode($classId)
        );
    }
}

jsonResponse([
    'insight' => $insight,
    'cached' => false,
    'analyzedAt' => date('Y-m-d H:i:s'),
]);

// ---------- Prompt builder ----------

function buildInsightPrompt($className, $snapshot) {
    $att = $snapshot['attendance'];
    if ($att['total'] > 0) {
        $attLine = 'Attendance (QR log): ' . $att['present'] . ' Present, ' . $att['late'] . ' Late, ' . $att['absent'] . ' Absent out of ' . $att['total'] . ' sessions (rate ' . $att['rate'] . '%). Recent trend: ' . implode(', ', array_map('strtolower', $att['trend'])) . '.';
    } else {
        $attLine = 'Attendance (QR log): 0 records - the student has no Present, Late, or Absent statuses yet.';
    }

    $labels = ['1' => '1st Term', '2' => '2nd Term', '3' => '3rd Term'];
    $catNames = ['written' => 'Written', 'performance' => 'Performance', 'exam' => 'Exams', 'attendance' => 'Attendance'];
    $termLines = [];
    foreach ($snapshot['terms'] as $q => $t) {
        $catStr = [];
        foreach ($t['categoryAverages'] as $cat => $avg) {
            $catStr[] = $avg === null ? ($catNames[$cat] . ': no scores') : ($catNames[$cat] . ': ' . $avg . '%');
        }
        $line = ($labels[$q] ?? ('Term ' . $q)) . ' - ' . implode(', ', $catStr) . '; pending components: ' . $t['pendingCount'];
        if (!$t['complete']) {
            $line .= '; final grade: not computed yet (incomplete data - only ' . $t['gradedCount'] . ' of ' . $t['weightedCategories'] . ' weighted categories have scores)';
        } elseif ($t['finalGrade'] !== null) {
            $line .= '; final grade: ' . $t['finalGrade'];
        }
        if ($t['weakestCategory'] !== null) $line .= '; weakest: ' . ($catNames[$t['weakestCategory']] ?? $t['weakestCategory']);
        $termLines[] = $line;
    }

    return "You are a warm, concise academic advisor for a student in the class " . $className . ". "
        . "Analyze this real student data ONLY - never invent numbers:\n"
        . $attLine . "\n"
        . implode("\n", $termLines) . "\n"
        . "IMPORTANT RULES:\n"
        . "- If any category shows 'no scores' or a term is marked 'incomplete data', do NOT call any category the weakest or strongest; instead say scores are still being recorded and keep the advice neutral.\n"
        . "- If attendance has 0 records, the student has no Present, Late, or Absent statuses yet - say exactly that and never speculate about school policies, rules, or record-keeping.\n"
        . "- Only mention concerns or praise when the given numbers support it.\n"
        . "Write 2-3 warm, specific, actionable sentences as a paragraph, and up to 3 short bullet tips. "
        . "Return JSON with keys 'paragraph' (string) and 'tips' (array of strings).";
}