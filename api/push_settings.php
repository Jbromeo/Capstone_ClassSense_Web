<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

// --- GET: current push preference + registered device tokens ---
if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT push_enabled FROM users WHERE uid = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();

    $tokStmt = $pdo->prepare("SELECT token FROM push_subscriptions WHERE uid = ?");
    $tokStmt->execute([$uid]);
    $tokens = array_column($tokStmt->fetchAll(), 'token');

    jsonResponse([
        'pushEnabled' => !empty($user['push_enabled']),
        'deviceCount' => count($tokens),
        'tokens' => $tokens,
    ]);
}

// --- POST: enable/disable push and register/remove a device token ---
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) jsonResponse(['error' => 'Invalid body'], 400);

    // Rebind: reassign a device token to the current user. FCM tokens are
    // per-browser, so on login the token must follow whoever is logged in now —
    // never a previously-logged-in account on the same shared device.
    if (($data['action'] ?? '') === 'rebind') {
        $token = trim((string)($data['token'] ?? ''));
        if ($token === '') jsonResponse(['error' => 'Missing token'], 400);
        $del = $pdo->prepare("DELETE FROM push_subscriptions WHERE token = ?");
        $del->execute([$token]);
        $ins = $pdo->prepare("INSERT INTO push_subscriptions (uid, token) VALUES (?, ?)");
        $ins->execute([$uid, $token]);
        jsonResponse(['success' => true]);
    }

    $enabled = !empty($data['enabled']);
    $token = trim((string)($data['token'] ?? ''));

    $stmt = $pdo->prepare("UPDATE users SET push_enabled = ? WHERE uid = ?");
    $stmt->execute([$enabled ? 1 : 0, $uid]);

    if ($enabled) {
        if ($token === '') jsonResponse(['error' => 'Missing token'], 400);
        // Whoever enables last on this device owns the token — remove any other
        // account's ownership of it, then bind it to this user.
        $del = $pdo->prepare("DELETE FROM push_subscriptions WHERE token = ?");
        $del->execute([$token]);
        $ins = $pdo->prepare("INSERT INTO push_subscriptions (uid, token) VALUES (?, ?)");
        $ins->execute([$uid, $token]);
    } else {
        // Master switch OFF: no device receives pushes. Clear all registered tokens.
        $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE uid = ?");
        $stmt->execute([$uid]);
    }

    jsonResponse(['success' => true, 'pushEnabled' => $enabled]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
