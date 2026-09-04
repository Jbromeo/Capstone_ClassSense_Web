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

// Optional refresh=1 bypasses the cache and forces a live AI generation (web-only).
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';

$result = generateInsight($uid, $classId, $forceRefresh);

if ($result['ok']) {
    $response = [
        'insight' => $result['insight'],
        'cached' => $result['cached'],
        'analyzedAt' => $result['analyzedAt'],
    ];
    if (!empty($result['pending'])) {
        $response['pending'] = true;
        $response['pendingSeconds'] = (int)$result['pendingSeconds'];
    }
    jsonResponse($response);
}

$errorLabels = [
    'ai_not_configured' => 'AI not configured',
    'ai_request_failed' => 'AI request failed',
    'ai_unparseable' => 'AI response unreadable',
];
jsonResponse(['available' => false, 'error' => $errorLabels[$result['error'] ?? ''] ?? 'AI unavailable'], 200);