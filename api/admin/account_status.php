<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();
requireSuperAdmin($pdo, $uid);

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['target_uid']) || !array_key_exists('active', $data)) {
    jsonResponse(['error' => 'target_uid and active required'], 400);
}

$targetUid = $data['target_uid'];
$active = ((int)$data['active'] === 1) ? 1 : 0;

$stmt = $pdo->prepare("SELECT role FROM users WHERE uid = ?");
$stmt->execute([$targetUid]);
$role = $stmt->fetchColumn();
if ($role === false) jsonResponse(['error' => 'User not found'], 404);
if ($role === 'super_admin') jsonResponse(['error' => 'Super admin accounts cannot be deactivated'], 403);

$stmt = $pdo->prepare("UPDATE users SET active = ? WHERE uid = ?");
$stmt->execute([$active, $targetUid]);

if ($active === 0) {
    // Deactivate: revoke every active session so they are logged out immediately.
    $pdo->prepare("DELETE FROM sessions WHERE uid = ?")->execute([$targetUid]);
}

jsonResponse(['success' => true, 'active' => $active]);
