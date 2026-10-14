<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();
$callerRole = requireAdmin($pdo, $uid);

// --- GET: list all pre-approved student IDs ---
if ($method === 'GET') {
    $stmt = $pdo->query("
        SELECT p.id, p.student_id, p.created_at, p.used_at,
               u.first_name, u.last_name, u.username, u.uid,
               CASE WHEN EXISTS (
                   SELECT 1 FROM deletion_requests dr
                   WHERE dr.target_uid = u.uid AND dr.status = 'pending'
               ) THEN 1 ELSE 0 END AS deletion_pending
        FROM pre_approved_students p
        LEFT JOIN users u ON u.student_id = p.student_id AND u.role = 'student'
        ORDER BY p.created_at DESC
    ");
    $rows = $stmt->fetchAll();
    $results = [];
    foreach ($rows as $r) {
        $results[] = [
            'id' => (int)$r['id'],
            'student_id' => $r['student_id'],
            'created_at' => $r['created_at'],
            'used_at' => $r['used_at'],
            'is_used' => $r['used_at'] !== null,
            'first_name' => $r['first_name'] ?? '',
            'last_name' => $r['last_name'] ?? '',
            'email' => $r['username'] ?? '',
            'uid' => $r['uid'] ?? '',
            'deletion_pending' => (bool)$r['deletion_pending'],
        ];
    }
    jsonResponse($results);
}

// --- POST: add student IDs (bulk) ---
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['student_ids'])) {
        jsonResponse(['error' => 'student_ids required'], 400);
    }

    $ids = $data['student_ids'];
    if (is_string($ids)) {
        $ids = array_map('trim', explode(',', $ids));
    }
    if (!is_array($ids) || empty($ids)) {
        jsonResponse(['error' => 'student_ids must be a comma-separated string or array'], 400);
    }

    $inserted = 0;
    $skipped = 0;
    $stmt = $pdo->prepare("
        IF NOT EXISTS (SELECT 1 FROM pre_approved_students WHERE student_id = ?)
        INSERT INTO pre_approved_students (student_id) VALUES (?)
    ");
    foreach ($ids as $sid) {
        $sid = trim($sid);
        if ($sid === '') continue;
        $stmt->execute([$sid, $sid]);
        if ($stmt->rowCount() > 0) $inserted++;
        else $skipped++;
    }

    jsonResponse([
        'success' => true,
        'inserted' => $inserted,
        'skipped' => $skipped,
        'total' => count($ids),
    ], 201);
}
// --- DELETE: remove/reset a pre-approved ID (or file an approval request) ---
if ($method === 'DELETE') {
    $targetId = $_GET['id'] ?? null;
    if (!$targetId) jsonResponse(['error' => 'Missing id'], 400);

    $reset = isset($_GET['reset']) && $_GET['reset'] === '1';

    $entryStmt = $pdo->prepare("SELECT id, student_id FROM pre_approved_students WHERE id = ?");
    $entryStmt->execute([(int)$targetId]);
    $entry = $entryStmt->fetch();
    if (!$entry) jsonResponse(['error' => 'Not found'], 404);

    $linkedStudent = null;
    if (!empty($entry['student_id'])) {
        $ls = $pdo->prepare("SELECT uid, username, first_name, last_name FROM users WHERE student_id = ? AND role = 'student'");
        $ls->execute([$entry['student_id']]);
        $linkedStudent = $ls->fetch() ?: null;
    }

    $alreadyPending = function () use ($pdo, $linkedStudent) {
        if (!$linkedStudent) return false;
        $d = $pdo->prepare("SELECT 1 FROM deletion_requests WHERE target_uid = ? AND status = 'pending'");
        $d->execute([$linkedStudent['uid']]);
        return (bool)$d->fetch();
    };

    $fileRequest = function ($type, $reason) use ($pdo, $uid, $linkedStudent) {
        $rq = $pdo->prepare("SELECT username FROM users WHERE uid = ?");
        $rq->execute([$uid]);
        $requesterName = $rq->fetchColumn() ?: '';
        $targetName = trim(($linkedStudent['first_name'] ?? '') . ' ' . ($linkedStudent['last_name'] ?? ''));
        $ins = $pdo->prepare("INSERT INTO deletion_requests (target_uid, target_username, target_role, target_name, requested_by, requested_username, reason, type) VALUES (?, ?, 'student', ?, ?, ?, ?, ?)");
        $ins->execute([$linkedStudent['uid'], $linkedStudent['username'], $targetName, $uid, $requesterName, $reason, $type]);
        $saStmt = $pdo->query("SELECT uid FROM users WHERE role = 'super_admin'");
        foreach ($saStmt->fetchAll() as $row) {
            sendNotification($row['uid'], 'deletion_request', 'Approval Request',
                "{$requesterName} requested " . ucfirst($type) . " for {$targetName} (student).",
                '../admin_screen/deletion_requests.php');
        }
    };

    if ($reset) {
        if ($callerRole === 'super_admin') {
            if ($linkedStudent) deleteUserCascade($pdo, $linkedStudent['uid']);
            $pdo->prepare("UPDATE pre_approved_students SET used_at = NULL WHERE id = ?")->execute([(int)$targetId]);
            jsonResponse(['success' => true, 'action' => 'reset']);
        }
        if ($linkedStudent) {
            if ($alreadyPending()) jsonResponse(['error' => 'A request for this student is already pending'], 409);
            $fileRequest('reset', 'Reset pre-approved Student ID ' . $entry['student_id']);
            jsonResponse(['success' => true, 'action' => 'requested']);
        }
        $pdo->prepare("UPDATE pre_approved_students SET used_at = NULL WHERE id = ?")->execute([(int)$targetId]);
        jsonResponse(['success' => true, 'action' => 'reset']);
    }

    if ($linkedStudent) {
        if ($callerRole === 'super_admin') {
            $pdo->prepare("UPDATE users SET active = 0 WHERE uid = ?")->execute([$linkedStudent['uid']]);
            $pdo->prepare("DELETE FROM sessions WHERE uid = ?")->execute([$linkedStudent['uid']]);
            $pdo->prepare("DELETE FROM pre_approved_students WHERE id = ?")->execute([(int)$targetId]);
            jsonResponse(['success' => true, 'action' => 'deactivated']);
        }
        if ($alreadyPending()) jsonResponse(['error' => 'A request for this student is already pending'], 409);
        $fileRequest('deactivate', 'Removed from pre-approved list (Student ID ' . $entry['student_id'] . ')');
        jsonResponse(['success' => true, 'action' => 'requested']);
    }

    $pdo->prepare("DELETE FROM pre_approved_students WHERE id = ?")->execute([(int)$targetId]);
    jsonResponse(['success' => true, 'action' => 'deleted']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
