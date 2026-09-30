<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();
requireSuperAdmin($pdo, $uid);

// --- GET: list admin accounts ------------------------------------------
if ($method === 'GET') {
    $stmt = $pdo->query("
        SELECT uid, username, role, first_name, last_name, created_at
        FROM users
        WHERE role IN ('admin', 'super_admin')
        ORDER BY CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END, first_name, last_name
    ");
    $rows = $stmt->fetchAll();
    $results = [];
    foreach ($rows as $r) {
        $results[] = [
            'uid' => $r['uid'],
            'username' => $r['username'],
            'role' => $r['role'],
            'firstName' => $r['first_name'] ?? '',
            'lastName' => $r['last_name'] ?? '',
            'createdAt' => $r['created_at'] ?? null,
            'isSelf' => $r['uid'] === $uid,
        ];
    }
    jsonResponse($results);
}

// --- POST: reset an admin password + revoke their sessions --------------
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['uid']) || empty($data['password'])) {
        jsonResponse(['error' => 'uid and password required'], 400);
    }
    if (strlen($data['password']) < 6) {
        jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
    }

    $stmt = $pdo->prepare("SELECT role, username FROM users WHERE uid = ?");
    $stmt->execute([$data['uid']]);
    $target = $stmt->fetch();
    if (!$target) jsonResponse(['error' => 'Account not found'], 404);
    if (!in_array($target['role'], ['admin', 'super_admin'], true)) {
        jsonResponse(['error' => 'Not an admin account'], 400);
    }

    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE uid = ?");
    $stmt->execute([$hash, $data['uid']]);

    // Kill every active token for that account (including the current one if
    // the super admin changed their own password).
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE uid = ?");
    $stmt->execute([$data['uid']]);
    $revoked = $stmt->rowCount();

    jsonResponse(['success' => true, 'sessions_revoked' => $revoked]);
}

// --- DELETE: remove an admin account ------------------------------------
if ($method === 'DELETE') {
    $targetUid = $_GET['uid'] ?? null;
    if (!$targetUid) jsonResponse(['error' => 'Missing uid'], 400);
    if ($targetUid === $uid) jsonResponse(['error' => 'You cannot delete your own account'], 403);

    $stmt = $pdo->prepare("SELECT role, username FROM users WHERE uid = ?");
    $stmt->execute([$targetUid]);
    $target = $stmt->fetch();
    if (!$target) jsonResponse(['error' => 'Account not found'], 404);
    if (!in_array($target['role'], ['admin', 'super_admin'], true)) {
        jsonResponse(['error' => 'Not an admin account'], 400);
    }
    if ($target['role'] === 'super_admin' && countSuperAdmins($pdo, $targetUid) === 0) {
        jsonResponse(['error' => 'The last super admin cannot be deleted'], 403);
    }

    try {
        deleteUserCascade($pdo, $targetUid);
    } catch (Throwable $e) {
        jsonResponse(['error' => 'Delete failed: ' . $e->getMessage()], 500);
    }

    $pdo->prepare("UPDATE deletion_requests SET status = 'approved', resolved_by = ?, resolved_at = GETDATE() WHERE target_uid = ? AND status = 'pending'")
        ->execute([$uid, $targetUid]);

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
