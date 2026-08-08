<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$teacherUid = $_GET['teacher_uid'] ?? $uid;

$pdo = getPDO();

// Class count
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM classes WHERE teacher_uid = ?");
$stmt->execute([$teacherUid]);
$classCount = (int)$stmt->fetch()['cnt'];

// Total students enrolled across all classes (only students that still exist in users)
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT cs.student_uid) as cnt FROM class_students cs JOIN classes c ON cs.class_id = c.id JOIN users u ON u.uid = cs.student_uid WHERE c.teacher_uid = ?");
$stmt->execute([$teacherUid]);
$studentCount = (int)$stmt->fetch()['cnt'];

jsonResponse([
    'class_count' => $classCount,
    'student_count' => $studentCount,
    'pending_grading' => 0,
    'grade_alerts' => []
]);