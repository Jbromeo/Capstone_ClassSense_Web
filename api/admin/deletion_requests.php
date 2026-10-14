<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();
$callerRole = requireAdmin($pdo, $uid);

// --- helpers -----------------------------------------------------------

function targetSnapshot($pdo, $targetUid) {
    $stmt = $pdo->prepare("SELECT uid, username, role, first_name, last_name FROM users WHERE uid = ?");
    $stmt->execute([$targetUid]);
    $u = $stmt->fetch();
    if (!$u) return null;
    return [
        'uid' => $u['uid'],
        'username' => $u['username'] ?? '',
        'role' => $u['role'] ?? '',
        'name' => trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')),
    ];
}

function notifySuperAdmins($pdo, $title, $message, $link) {
    $stmt = $pdo->query("SELECT uid FROM users WHERE role = 'super_admin'");
    foreach ($stmt->fetchAll() as $row) {
        sendNotification($row['uid'], 'deletion_request', $title, $message, $link);
    }
}

// --- GET: list requests -------------------------------------------------
if ($method === 'GET') {
    $status = $_GET['status'] ?? null;
    $where = [];
    $params = [];

    if ($callerRole !== 'super_admin') {
        // Regular admins only see the requests they filed.
        $where[] = 'requested_by = ?';
        $params[] = $uid;
    }
    if ($status && in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    $sql = "SELECT * FROM deletion_requests";
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= " ORDER BY CASE WHEN status = 'pending' THEN 0 ELSE 1 END, requested_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $results = [];
    foreach ($rows as $r) {
        $results[] = [
            'id' => (int)$r['id'],
            'target_uid' => $r['target_uid'],
            'target_username' => $r['target_username'] ?? '',
            'target_role' => $r['target_role'] ?? '',
            'target_name' => $r['target_name'] ?? '',
            'type' => $r['type'] ?? 'delete',
            'requested_by' => $r['requested_by'],
            'requested_username' => $r['requested_username'] ?? '',
            'reason' => $r['reason'] ?? '',
            'status' => $r['status'],
            'requested_at' => $r['requested_at'],
            'resolved_by' => $r['resolved_by'] ?? '',
            'resolved_at' => $r['resolved_at'] ?? null,
        ];
    }
    jsonResponse($results);
}

// --- POST: file an approval request --------------------------------------
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['target_uid'])) {
        jsonResponse(['error' => 'target_uid required'], 400);
    }

    $type = isset($data['type']) ? (string)$data['type'] : 'delete';
    if (!in_array($type, ['delete', 'deactivate', 'reactivate', 'reset'], true)) {
        jsonResponse(['error' => 'Invalid request type'], 400);
    }

    $target = targetSnapshot($pdo, $data['target_uid']);
    if (!$target) jsonResponse(['error' => 'User not found'], 404);
    if ($target['role'] === 'super_admin') {
        jsonResponse(['error' => 'Super admin accounts cannot be requested'], 403);
    }

    $stmt = $pdo->prepare("SELECT 1 FROM deletion_requests WHERE target_uid = ? AND status = 'pending'");
    $stmt->execute([$target['uid']]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'A request for this account is already pending'], 409);
    }

    $reason = trim((string)($data['reason'] ?? ''));
    if (strlen($reason) > 500) $reason = substr($reason, 0, 500);

    $stmt = $pdo->prepare("SELECT username FROM users WHERE uid = ?");
    $stmt->execute([$uid]);
    $requesterName = $stmt->fetchColumn() ?: '';

    $stmt = $pdo->prepare("INSERT INTO deletion_requests (target_uid, target_username, target_role, target_name, requested_by, requested_username, reason, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$target['uid'], $target['username'], $target['role'], $target['name'], $uid, $requesterName, $reason ?: null, $type]);
    $requestId = (int)$pdo->lastInsertId();

    $actionLabel = ucfirst($type);
    notifySuperAdmins(
        $pdo,
        'Approval Request',
        "{$requesterName} requested {$actionLabel} for {$target['name']} ({$target['role']}).",
        '../admin_screen/deletion_requests.php'
    );

    jsonResponse(['success' => true, 'id' => $requestId], 201);
}

// --- PUT: approve / reject (super admin only) ---------------------------
if ($method === 'PUT') {
    requireSuperAdmin($pdo, $uid);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['id']) || empty($data['action'])) {
        jsonResponse(['error' => 'id and action required'], 400);
    }
    $action = $data['action'];
    if (!in_array($action, ['approve', 'reject'], true)) {
        jsonResponse(['error' => 'action must be approve or reject'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM deletion_requests WHERE id = ?");
    $stmt->execute([(int)$data['id']]);
    $req = $stmt->fetch();
    if (!$req) jsonResponse(['error' => 'Request not found'], 404);
    if ($req['status'] !== 'pending') jsonResponse(['error' => 'Request already resolved'], 409);

    if ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE deletion_requests SET status = 'rejected', resolved_by = ?, resolved_at = GETDATE() WHERE id = ?");
        $stmt->execute([$uid, (int)$req['id']]);

        if (!empty($req['requested_by'])) {
            sendNotification(
                $req['requested_by'],
                'deletion_request',
                'Deletion Request Rejected',
                "Your request to delete {$req['target_name']} was rejected.",
                '../admin_screen/deletion_requests.php'
            );
        }
        jsonResponse(['success' => true, 'action' => 'rejected']);
    }

    // Approve: run the action described by the request type.
    $type = $req['type'] ?? 'delete';

    $target = targetSnapshot($pdo, $req['target_uid']);
    if ($target && $target['role'] === 'super_admin') {
        jsonResponse(['error' => 'Super admin accounts cannot be modified'], 403);
    }

    if ($type === 'deactivate') {
        if ($target) {
            $pdo->prepare("UPDATE users SET active = 0 WHERE uid = ?")->execute([$target['uid']]);
            $pdo->prepare("DELETE FROM sessions WHERE uid = ?")->execute([$target['uid']]);
            $pdo->prepare("DELETE FROM pre_approved_students WHERE student_id IN (SELECT student_id FROM users WHERE uid = ?)")
                ->execute([$target['uid']]);
        }
    } elseif ($type === 'reactivate') {
        if ($target) {
            $pdo->prepare("UPDATE users SET active = 1 WHERE uid = ?")->execute([$target['uid']]);
        }
    } elseif ($type === 'reset') {
        if ($target) {
            $sidStmt = $pdo->prepare("SELECT student_id FROM users WHERE uid = ?");
            $sidStmt->execute([$target['uid']]);
            $studentId = $sidStmt->fetchColumn();
            try {
                deleteUserCascade($pdo, $target['uid']);
            } catch (Throwable $e) {
                jsonResponse(['error' => 'Reset failed: ' . $e->getMessage()], 500);
            }
            if (!empty($studentId)) {
                $pdo->prepare("UPDATE pre_approved_students SET used_at = NULL WHERE student_id = ?")
                    ->execute([$studentId]);
            }
        }
    } else {
        // 'delete' (legacy): hard delete.
        if ($target) {
            try {
                $deleted = deleteUserCascade($pdo, $target['uid']);
            } catch (Throwable $e) {
                jsonResponse(['error' => 'Delete failed: ' . $e->getMessage()], 500);
            }
            if ($deleted && $deleted['role'] === 'student' && !empty($deleted['student_id'])) {
                $pdo->prepare("DELETE FROM pre_approved_students WHERE student_id = ?")
                    ->execute([$deleted['student_id']]);
            }
        }
    }

    $pdo->prepare("UPDATE deletion_requests SET status = 'approved', resolved_by = ?, resolved_at = GETDATE() WHERE id = ?")
        ->execute([$uid, (int)$req['id']]);
    // Settle any other pending requests for the same account.
    $pdo->prepare("UPDATE deletion_requests SET status = 'approved', resolved_by = ?, resolved_at = GETDATE() WHERE target_uid = ? AND status = 'pending'")
        ->execute([$uid, $req['target_uid']]);

    if (!empty($req['requested_by'])) {
        sendNotification(
            $req['requested_by'],
            'deletion_request',
            'Approval Request Approved',
            "Your request for {$req['target_name']} was approved.",
            '../admin_screen/deletion_requests.php'
        );
    }
    jsonResponse(['success' => true, 'action' => 'approved']);
}

// --- DELETE: cancel a pending request (requester or super admin) --------
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) jsonResponse(['error' => 'Missing id'], 400);

    $stmt = $pdo->prepare("SELECT * FROM deletion_requests WHERE id = ?");
    $stmt->execute([(int)$id]);
    $req = $stmt->fetch();
    if (!$req) jsonResponse(['error' => 'Request not found'], 404);
    if ($req['status'] !== 'pending') jsonResponse(['error' => 'Request already resolved'], 409);
    if ($callerRole !== 'super_admin' && $req['requested_by'] !== $uid) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $stmt = $pdo->prepare("UPDATE deletion_requests SET status = 'cancelled', resolved_by = ?, resolved_at = GETDATE() WHERE id = ?");
    $stmt->execute([$uid, (int)$req['id']]);

    jsonResponse(['success' => true, 'action' => 'cancelled']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
