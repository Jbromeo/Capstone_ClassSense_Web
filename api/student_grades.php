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
$quarter = (int)($_GET['quarter'] ?? 1);
$quarter = max(1, min(3, $quarter));

if (!$classId) {
    jsonResponse(['error' => 'Missing class_id'], 400);
}

// Enrollment gate: students may only read grades for classes they are enrolled in.
$stmt = $pdo->prepare("SELECT 1 FROM class_students WHERE class_id = ? AND student_uid = ?");
$stmt->execute([$classId, $uid]);
if (!$stmt->fetch()) {
    jsonResponse(['error' => 'Class not found'], 404);
}

$components = [];
$stmt = $pdo->prepare("SELECT id, category, name, hps, quarter FROM grade_components WHERE class_id = ? AND quarter = ? ORDER BY category, id");
$stmt->execute([$classId, $quarter]);
$components = $stmt->fetchAll();

// Only the calling student's own scores.
$grades = [];
$componentIds = array_column($components, 'id');
if (!empty($componentIds)) {
    $placeholders = implode(',', array_fill(0, count($componentIds), '?'));
    $stmt = $pdo->prepare("SELECT component_id, score FROM grades WHERE component_id IN ($placeholders) AND student_uid = ?");
    $stmt->execute(array_merge($componentIds, [$uid]));
    foreach ($stmt->fetchAll() as $g) {
        $grades[$g['component_id']] = $g['score'];
    }
}

$weights = [];
$stmt = $pdo->prepare("SELECT category, weight_percent FROM grade_weights WHERE class_id = ?");
$stmt->execute([$classId]);
foreach ($stmt->fetchAll() as $w) {
    $weights[$w['category']] = (int)$w['weight_percent'];
}

if (empty($weights)) {
    $weights = ['written' => 0, 'performance' => 0, 'exam' => 0, 'attendance' => 0];
}

$classStmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE id = ?");
$classStmt->execute([$classId]);
$classData = $classStmt->fetch();

jsonResponse([
    'class' => $classData,
    'quarter' => $quarter,
    'components' => $components,
    'grades' => $grades,
    'weights' => $weights,
]);
