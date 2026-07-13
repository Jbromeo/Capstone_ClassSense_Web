<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

if ($method === 'GET') {
    $teacherUid = $_GET['teacher_uid'] ?? $uid;
    $stmt = $pdo->prepare("SELECT * FROM events WHERE teacher_uid = ? ORDER BY date_str ASC, start_time ASC");
    $stmt->execute([$teacherUid]);
    jsonResponse($stmt->fetchAll());
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['title']) || empty($data['date_str'])) {
        jsonResponse(['error' => 'Missing title or date_str'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO events (teacher_uid, title, date_str, start_time, end_time, description, created_at) VALUES (?, ?, ?, ?, ?, ?, GETDATE())");
    $stmt->execute([
        $uid,
        $data['title'],
        $data['date_str'],
        $data['start_time'] ?? '',
        $data['end_time'] ?? '',
        $data['description'] ?? ''
    ]);

    $eventId = $pdo->lastInsertId();
    jsonResponse(['id' => $eventId], 201);
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'Missing event id'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND teacher_uid = ?");
    $stmt->execute([$id, $uid]);

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
