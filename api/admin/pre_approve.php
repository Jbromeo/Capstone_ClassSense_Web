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
// --- DELETE: remove or reset a pre-approved ID ---
if ($method === 'DELETE') {
    $targetId = $_GET['id'] ?? null;
    if (!$targetId) jsonResponse(['error' => 'Missing id'], 400);

    $reset = isset($_GET['reset']) && $_GET['reset'] === '1';

    if ($reset) {
        // Resetting deletes the linked student account, so it is super-admin only.
        requireSuperAdmin($pdo, $uid);

        // Get the student_id before clearing
        $stmt = $pdo->prepare("SELECT student_id FROM pre_approved_students WHERE id = ?");
        $stmt->execute([(int)$targetId]);
        $entry = $stmt->fetch();
        if (!$entry) jsonResponse(['error' => 'Not found'], 404);

        $deletedStudent = null;

        // Delete the linked student account (if one exists)
        if (!empty($entry['student_id'])) {
            // Get the student UID before deleting
            $stmt = $pdo->prepare("SELECT uid, username FROM users WHERE student_id = ? AND role = 'student'");
            $stmt->execute([$entry['student_id']]);
            $student = $stmt->fetch();

            $del = $pdo->prepare("DELETE FROM users WHERE student_id = ? AND role = 'student'");
            $del->execute([$entry['student_id']]);

            // Clean up orphaned enrollments
            if ($student && !empty($student['uid'])) {
                $deletedStudent = $student;
                $pdo->prepare("DELETE FROM class_students WHERE student_uid = ?")
                    ->execute([$student['uid']]);
            }
        }

        // Clear used_at so the ID can be reused
        $stmt = $pdo->prepare("UPDATE pre_approved_students SET used_at = NULL WHERE id = ?");
        $stmt->execute([(int)$targetId]);

        jsonResponse(['success' => true, 'action' => 'reset']);
    } else {
        // Fetch the entry and its linked student (if any) before deleting.
        $stmt = $pdo->prepare("SELECT student_id FROM pre_approved_students WHERE id = ?");
        $stmt->execute([(int)$targetId]);
        $entry = $stmt->fetch();
        if (!$entry) jsonResponse(['error' => 'Not found'], 404);

        $linkedStudent = null;
        if (!empty($entry['student_id'])) {
            $stmt = $pdo->prepare("SELECT uid, username, first_name, last_name FROM users WHERE student_id = ? AND role = 'student'");
            $stmt->execute([$entry['student_id']]);
            $linkedStudent = $stmt->fetch();
        }

        // Used entry + regular admin: do not silently remove. File a deletion
        // request instead; the super admin's approval deletes the account and
        // this entry.
        if ($linkedStudent && $callerRole !== 'super_admin') {
            $stmt = $pdo->prepare("SELECT 1 FROM deletion_requests WHERE target_uid = ? AND status = 'pending'");
            $stmt->execute([$linkedStudent['uid']]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => 'A deletion request for this student is already pending'], 409);
            }

            $stmt = $pdo->prepare("SELECT username FROM users WHERE uid = ?");
            $stmt->execute([$uid]);
            $requesterName = $stmt->fetchColumn() ?: '';

            $targetName = trim(($linkedStudent['first_name'] ?? '') . ' ' . ($linkedStudent['last_name'] ?? ''));
            $reason = 'Removed from pre-approved list by admin (Student ID ' . $entry['student_id'] . ')';

            $stmt = $pdo->prepare("INSERT INTO deletion_requests (target_uid, target_username, target_role, target_name, requested_by, requested_username, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$linkedStudent['uid'], $linkedStudent['username'], 'student', $targetName, $uid, $requesterName, $reason]);
            $requestId = (int)$pdo->lastInsertId();

            $saStmt = $pdo->query("SELECT uid FROM users WHERE role = 'super_admin'");
            foreach ($saStmt->fetchAll() as $row) {
                sendNotification(
                    $row['uid'],
                    'deletion_request',
                    'Deletion Request',
                    "{$requesterName} requested deletion of {$targetName} (student).",
                    '../admin_screen/deletion_requests.php'
                );
            }

            jsonResponse(['success' => true, 'action' => 'requested', 'request_id' => $requestId], 201);
        }

        $stmt = $pdo->prepare("DELETE FROM pre_approved_students WHERE id = ?");
        $stmt->execute([(int)$targetId]);
        if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Not found'], 404);

        jsonResponse(['success' => true, 'action' => 'deleted']);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
