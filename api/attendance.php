<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

if ($method === 'GET') {
    $classId = $_GET['class_id'] ?? null;
    $date = $_GET['date'] ?? null;
    $studentUid = $_GET['student_uid'] ?? null;

    if ($classId && $date && $studentUid) {
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE class_id = ? AND date = ? AND student_uid = ?");
        $stmt->execute([$classId, $date, $studentUid]);
        jsonResponse($stmt->fetchAll());
    }

    if ($classId && $date) {
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE class_id = ? AND date = ?");
        $stmt->execute([$classId, $date]);
        jsonResponse($stmt->fetchAll());
    }

    if ($classId) {
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE class_id = ? ORDER BY date DESC");
        $stmt->execute([$classId]);
        jsonResponse($stmt->fetchAll());
    }

    jsonResponse(['error' => 'Missing class_id'], 400);
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['class_id']) || empty($data['student_uid'])) {
        jsonResponse(['error' => 'Missing required fields: class_id, student_uid'], 400);
    }

    $date = $data['date'] ?? date('Y-m-d');
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    $status = $data['status'] ?? 'Verified';

    $stmt = $pdo->prepare("INSERT INTO attendance (student_uid, class_id, date, timestamp, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data['student_uid'], $data['class_id'], $date, $timestamp, $status]);

    jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId()], 201);
}

jsonResponse(['error' => 'Method not allowed'], 405);
