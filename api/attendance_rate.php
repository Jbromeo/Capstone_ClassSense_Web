<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$classId = $_GET['class_id'] ?? null;
$studentUid = $_GET['student_uid'] ?? $uid;

if (!$classId) {
    jsonResponse(['error' => 'Missing class_id'], 400);
}

$pdo = getPDO();

$stmt = $pdo->prepare("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('Present', 'Verified') THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late
FROM attendance WHERE class_id = ? AND student_uid = ?");
$stmt->execute([$classId, $studentUid]);
$att = $stmt->fetch();

$total = (int)($att['total'] ?? 0);
$present = (int)($att['present'] ?? 0) + (int)($att['late'] ?? 0);
$rate = $total > 0 ? round(($present / $total) * 100, 1) : null;

jsonResponse([
    'studentUid' => $studentUid,
    'classId' => $classId,
    'rate' => $rate,
    'present' => $present,
    'total' => $total,
]);
