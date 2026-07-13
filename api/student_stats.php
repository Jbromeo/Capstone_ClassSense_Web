<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$pdo = getPDO();

// Enrolled classes count
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM class_students WHERE student_uid = ?");
$stmt->execute([$uid]);
$enrolledCount = (int)$stmt->fetch()['cnt'];

// Attendance stats
$stmt = $pdo->prepare("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('Present', 'Verified') THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
FROM attendance WHERE student_uid = ?");
$stmt->execute([$uid]);
$att = $stmt->fetch();

if ($att && $att['total'] > 0) {
    $total = (int)$att['total'];
    $present = (int)$att['present'] + (int)$att['late'];
    $rate = round(($present / $total) * 100);
} else {
    $total = 0;
    $present = 0;
    $rate = null;
}

$rate = $rate !== null ? $rate : null;

jsonResponse([
    'enrolledCount' => $enrolledCount,
    'attendanceRate' => $rate,
    'attendanceTotal' => $total,
    'present' => $present,
    'absent' => (int)($att['absent'] ?? 0),
    'late' => (int)($att['late'] ?? 0),
]);
