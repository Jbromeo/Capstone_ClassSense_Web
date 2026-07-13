<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$pdo = getPDO();

$teacherCount = (int)$pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'teacher'")->fetch()['cnt'];
$studentCount = (int)$pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'student'")->fetch()['cnt'];
$classCount = (int)$pdo->query("SELECT COUNT(*) as cnt FROM classes")->fetch()['cnt'];
$enrollmentCount = (int)$pdo->query("SELECT COUNT(*) as cnt FROM class_students")->fetch()['cnt'];

// Recent teachers (last 5 registered)
$recentTeachers = [];
$stmt = $pdo->query("SELECT uid, first_name, last_name, username, created_at FROM users WHERE role = 'teacher' ORDER BY created_at DESC");
$allTeachers = $stmt->fetchAll();
foreach (array_slice($allTeachers, 0, 5) as $t) {
    $recentTeachers[] = [
        'uid' => $t['uid'],
        'firstName' => $t['first_name'] ?? '',
        'lastName' => $t['last_name'] ?? '',
        'email' => $t['username'] ?? '',
    ];
}

echo json_encode([
    'teachers' => $teacherCount,
    'students' => $studentCount,
    'classes' => $classCount,
    'enrollments' => $enrollmentCount,
    'recent_teachers' => $recentTeachers,
]);
