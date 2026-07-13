<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

// --- GET: fetch notifications for the current user ---
if ($method === 'GET') {
    $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';

    $sql = "SELECT id, type, title, message, link, is_read, created_at
            FROM notifications
            WHERE recipient_uid = ?";
    if ($unreadOnly) {
        $sql .= " AND is_read = 0";
    }
    $sql .= " ORDER BY created_at DESC";

    $limit = max(1, min((int)($_GET['limit'] ?? 20), 50));
    $offset = max(0, (int)($_GET['offset'] ?? 0));

    $stmt = $pdo->prepare("$sql OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY");
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll();

    $results = [];
    foreach ($rows as $r) {
        $results[] = [
            'id' => (int)$r['id'],
            'type' => $r['type'],
            'title' => $r['title'],
            'message' => $r['message'],
            'link' => $r['link'],
            'isRead' => (bool)$r['is_read'],
            'createdAt' => $r['created_at'],
        ];
    }

    // Count unread
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE recipient_uid = ? AND is_read = 0");
    $stmt->execute([$uid]);
    $unreadCount = (int)$stmt->fetch()['cnt'];

    jsonResponse([
        'notifications' => $results,
        'unreadCount' => $unreadCount,
    ]);
}

// --- POST: mark as read ---
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) jsonResponse(['error' => 'Invalid body'], 400);

    if (!empty($data['id'])) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND recipient_uid = ?");
        $stmt->execute([(int)$data['id'], $uid]);
    } elseif (!empty($data['markAll']) && $data['markAll'] === true) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_uid = ?");
        $stmt->execute([$uid]);
    } else {
        jsonResponse(['error' => 'Missing id or markAll'], 400);
    }

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
