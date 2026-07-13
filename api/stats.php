<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$teacherUid = $_GET['teacher_uid'] ?? $uid;

// Class count
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM classes WHERE teacher_uid = ?");
$stmt->execute([$teacherUid]);
$classCount = (int)$stmt->fetch()['cnt'];

// Total students enrolled across all classes
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT cs.student_uid) as cnt FROM class_students cs JOIN classes c ON cs.class_id = c.id WHERE c.teacher_uid = ?");
$stmt->execute([$teacherUid]);
$studentCount = (int)$stmt->fetch()['cnt'];

// Pending grading: count components with missing scores
$stmt = $pdo->prepare("
    SELECT COUNT(*) as cnt
    FROM grading_components gc
    JOIN classes c ON gc.class_id = c.id
    WHERE c.teacher_uid = ?
");
$stmt->execute([$teacherUid]);
$totalComponents = (int)$stmt->fetch()['cnt'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as cnt
    FROM student_scores ss
    JOIN grading_components gc ON ss.component_id = gc.id
    JOIN classes c ON gc.class_id = c.id
    WHERE c.teacher_uid = ? AND ss.score IS NOT NULL
");
$stmt->execute([$teacherUid]);
$filledScores = (int)$stmt->fetch()['cnt'];

// Grade alerts: classes with missing scores
$stmt = $pdo->prepare("SELECT c.id, c.class_name, c.subject FROM classes c WHERE c.teacher_uid = ?");
$stmt->execute([$teacherUid]);
$classes = $stmt->fetchAll();

$gradeAlerts = [];
$pendingGrading = 0;

foreach ($classes as $cls) {
    // Get all students in this class
    $stmt = $pdo->prepare("SELECT student_uid FROM class_students WHERE class_id = ?");
    $stmt->execute([$cls['id']]);
    $studentUids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get all components for this class across all quarters
    $stmt = $pdo->prepare("SELECT id, name FROM grading_components WHERE class_id = ?");
    $stmt->execute([$cls['id']]);
    $components = $stmt->fetchAll();

    foreach ($components as $comp) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as cnt FROM student_scores
            WHERE component_id = ? AND score IS NOT NULL
        ");
        $stmt->execute([$comp['id']]);
        $filled = (int)$stmt->fetch()['cnt'];

        $totalStudents = count($studentUids);
        $missing = $totalStudents - $filled;
        if ($missing > 0) {
            $pendingGrading += $missing;
            $gradeAlerts[] = [
                'classId' => $cls['id'],
                'className' => $cls['class_name'],
                'subject' => $cls['subject'],
                'itemName' => $comp['name'],
                'missingCount' => $missing
            ];
        }
    }
}

jsonResponse([
    'class_count' => $classCount,
    'student_count' => $studentCount,
    'pending_grading' => $pendingGrading,
    'grade_alerts' => $gradeAlerts
]);
