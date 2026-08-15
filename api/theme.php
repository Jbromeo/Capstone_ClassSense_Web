<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT theme FROM users WHERE uid = ?");
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    $theme = $row ? $row['theme'] : null;
    if ($theme !== 'light' && $theme !== 'dark') $theme = null;
    jsonResponse(['theme' => $theme]);
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $theme = $data['theme'] ?? null;
    if (!in_array($theme, ['light', 'dark'], true)) {
        jsonResponse(['error' => 'theme must be "light" or "dark"'], 400);
    }
    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE uid = ?");
    $stmt->execute([$theme, $uid]);
    jsonResponse(['success' => true, 'theme' => $theme]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
