<?php
// api/repair.php
// Admin-only database hygiene tool.
//   GET  -> dry-run report (no changes)
//   POST {"fix": true} -> performs cleanup + resets stale live sessions, returns before/after counts
// All other roles -> 403. verifyToken() only returns a uid (no role check), so we
// authorize against the users table directly here.

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

// --- Authorize: must be an actual admin user ---
$stmt = $pdo->prepare("SELECT role FROM users WHERE uid = ?");
$stmt->execute([$uid]);
$caller = $stmt->fetch();
if (!$caller || ($caller['role'] ?? null) !== 'admin') {
    jsonResponse(['error' => 'Forbidden: admin access required'], 403);
}

// --- Collect orphan / stale counts (the same checks as db_repair.sql) ---
function collectReport($pdo) {
    $r = [];

    $r['orphan_class_students'] =
        (int)$pdo->query("SELECT COUNT(*) FROM class_students cs LEFT JOIN users u ON u.uid = cs.student_uid WHERE u.uid IS NULL")->fetchColumn();

    $r['orphan_attendance'] =
        (int)$pdo->query("SELECT COUNT(*) FROM attendance a LEFT JOIN users u ON u.uid = a.student_uid WHERE u.uid IS NULL")->fetchColumn();

    $r['orphan_sessions'] =
        (int)$pdo->query("SELECT COUNT(*) FROM sessions s LEFT JOIN users u ON u.uid = s.uid WHERE u.uid IS NULL")->fetchColumn();

    $r['expired_sessions'] =
        (int)$pdo->query("SELECT COUNT(*) FROM sessions WHERE expires_at < GETDATE()")->fetchColumn();

    $r['orphan_notifications'] =
        (int)$pdo->query("SELECT COUNT(*) FROM notifications n LEFT JOIN users u ON u.uid = n.recipient_uid WHERE u.uid IS NULL")->fetchColumn();

    $r['stale_live_sessions'] =
        (int)$pdo->query("SELECT COUNT(*) FROM classes WHERE session_active = 1 AND (session_expires_at IS NULL OR session_expires_at < GETDATE())")->fetchColumn();

    return $r;
}

// --- GET: dry-run report only ---
if ($method === 'GET') {
    jsonResponse(['status' => 'dry_run', 'counts' => collectReport($pdo)]);
}

// --- POST: optional fix ---
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $fix = !empty($data['fix']);

    $before = collectReport($pdo);

    if (!$fix) {
        jsonResponse(['status' => 'dry_run', 'counts' => $before]);
    }

    $pdo->beginTransaction();
    try {
        $deleted = [];
        $deleted['class_students']   = (int)$pdo->exec("DELETE cs FROM class_students cs LEFT JOIN users u ON u.uid = cs.student_uid WHERE u.uid IS NULL");
        $deleted['attendance']       = (int)$pdo->exec("DELETE a FROM attendance a LEFT JOIN users u ON u.uid = a.student_uid WHERE u.uid IS NULL");
        $deleted['sessions_orphan']  = (int)$pdo->exec("DELETE s FROM sessions s LEFT JOIN users u ON u.uid = s.uid WHERE u.uid IS NULL");
        $deleted['sessions_expired'] = (int)$pdo->exec("DELETE FROM sessions WHERE expires_at < GETDATE()");
        $deleted['notifications']    = (int)$pdo->exec("DELETE n FROM notifications n LEFT JOIN users u ON u.uid = n.recipient_uid WHERE u.uid IS NULL");
        $deleted['stale_live_sessions'] = (int)$pdo->exec(
            "UPDATE classes SET session_active = 0, session_id = NULL, current_nonce = NULL, last_nonce = NULL, nonce_issued_at = NULL, session_expires_at = NULL, session_mode = 'open'
             WHERE session_active = 1 AND (session_expires_at IS NULL OR session_expires_at < GETDATE())"
        );

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Repair failed: ' . $e->getMessage()], 500);
    }

    jsonResponse([
        'status' => 'success',
        'before' => $before,
        'deleted' => $deleted,
        'after'  => collectReport($pdo),
    ]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
