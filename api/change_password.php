<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) jsonResponse(['error' => 'Invalid body'], 400);

$current = (string)($data['current_password'] ?? '');
$new = (string)($data['new_password'] ?? '');

if ($current === '' || $new === '') {
    jsonResponse(['error' => 'Current and new password are required'], 400);
}
if (strlen($new) < 6) {
    jsonResponse(['error' => 'New password must be at least 6 characters'], 400);
}

// Identify this device's token so we can keep it while revoking all others.
$currentToken = '';
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
    $currentToken = $m[1];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $ah = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $ah, $m)) $currentToken = $m[1];
}

$uid = verifyToken();

$pdo = getPDO();
$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE uid = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user || !password_verify($current, $user['password_hash'])) {
    jsonResponse(['error' => 'Current password is incorrect'], 401);
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE uid = ?");
$stmt->execute([$hash, $uid]);

// Log out every other device, but keep this one signed in.
if ($currentToken !== '') {
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE uid = ? AND token <> ?");
    $stmt->execute([$uid, $currentToken]);
}

jsonResponse(['success' => true]);
