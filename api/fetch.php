<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

// --- DELETE: remove user and all dependent data ---
if ($method === 'DELETE') {
    // Only the super admin may delete accounts directly. Regular admins file a
    // deletion request instead (api/admin/deletion_requests.php).
    requireSuperAdmin($pdo, $uid);

    $targetUid = $_GET['uid'] ?? null;
    if (!$targetUid) jsonResponse(['error' => 'Missing uid'], 400);

    $stmt = $pdo->prepare("SELECT role FROM users WHERE uid = ?");
    $stmt->execute([$targetUid]);
    $targetRole = $stmt->fetchColumn();
    if ($targetRole === false) jsonResponse(['error' => 'User not found'], 404);
    if ($targetRole === 'super_admin') {
        jsonResponse(['error' => 'Super admin accounts cannot be deleted'], 403);
    }

    try {
        $deleted = deleteUserCascade($pdo, $targetUid);
    } catch (Throwable $e) {
        jsonResponse(['error' => 'Delete failed: ' . $e->getMessage()], 500);
    }
    if (!$deleted) jsonResponse(['error' => 'User not found'], 404);

    // A direct delete settles any pending request for the same account.
    $pdo->prepare("UPDATE deletion_requests SET status = 'approved', resolved_by = ?, resolved_at = GETDATE() WHERE target_uid = ? AND status = 'pending'")
        ->execute([$uid, $targetUid]);

    jsonResponse(['success' => true]);
}

// --- POST: batch-fetch profiles or create/update single profile ---
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) jsonResponse(['error' => 'Invalid JSON'], 400);

    $collection = $data['collection'] ?? null;
    $uids = $data['uids'] ?? null;

    // Batch-fetch profiles by collection + uids
    if ($collection && is_array($uids)) {
        if (empty($uids)) jsonResponse([]);
        $placeholders = implode(',', array_fill(0, count($uids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM users WHERE uid IN ($placeholders)");
        $stmt->execute($uids);
        $users = $stmt->fetchAll();
        $foundUids = array_column($users, 'uid');

        $results = [];
        foreach ($users as $u) {
            $results[] = [
                'uid' => $u['uid'],
                'role' => $u['role'],
                'firstName' => $u['first_name'] ?? '',
                'lastName' => $u['last_name'] ?? '',
                'first_name' => $u['first_name'] ?? '',
                'last_name' => $u['last_name'] ?? '',
                'email' => $u['username'] ?? '',
                'studentId' => $u['student_id'] ?? '',
                'student_id' => $u['student_id'] ?? '',
                'employeeId' => $u['employee_id'] ?? '',
                'employee_id' => $u['employee_id'] ?? '',
                'profilePicture' => $u['profile_picture'] ?? '',
                'profile_picture' => $u['profile_picture'] ?? '',
                'phone' => $u['phone'] ?? '',
                'guardianPhone' => $u['guardian_phone'] ?? '',
                'exists' => true,
            ];
        }
        // Mark UIDs that have no matching user row instead of fabricating fake
        // "Unknown Student / No Email" entries, so callers can hide them.
        foreach ($uids as $ruid) {
            if (!in_array($ruid, $foundUids)) {
                $results[] = [
                    'uid' => $ruid, 'role' => '', 'firstName' => '', 'lastName' => '',
                    'first_name' => '', 'last_name' => $collection === 'students' ? 'Student' : '',
                    'email' => '', 'studentId' => '', 'student_id' => '',
                    'employeeId' => '', 'employee_id' => '',
                    'profilePicture' => '', 'profile_picture' => '',
                    'phone' => '', 'guardianPhone' => '',
                    'exists' => false,
                ];
            }
        }
        jsonResponse($results);
    }

    // Single profile upsert (create or update)
    if (!empty($data['uid']) && !empty($data['role'])) {
        // Only admins/super admins may create or edit accounts here.
        $callerRole = requireAdmin($pdo, $uid);
        $targetUid = $data['uid'];
        $role = $data['role'];

        // Role escalation guard: only the super admin may create admin accounts,
        // and super_admin accounts are never creatable through this endpoint.
        if (!in_array($role, ['teacher', 'student', 'admin'], true)) {
            jsonResponse(['error' => 'Invalid role'], 400);
        }
        if ($role === 'admin' && $callerRole !== 'super_admin') {
            jsonResponse(['error' => 'Forbidden: only a super admin can create admin accounts'], 403);
        }

        $firstName = capitalizeName($data['firstName'] ?? $data['first_name'] ?? null);
        $lastName = capitalizeName($data['lastName'] ?? $data['last_name'] ?? null);
        $username = array_key_exists('username', $data) ? trim($data['username']) : null;
        $studentId = array_key_exists('studentId', $data) ? $data['studentId'] :
                     (array_key_exists('student_id', $data) ? $data['student_id'] : null);
$employeeId = array_key_exists('employeeId', $data) ? $data['employeeId'] :
                       (array_key_exists('employee_id', $data) ? $data['employee_id'] : null);
$profilePicture = array_key_exists('profilePicture', $data) ? $data['profilePicture'] :
                          (array_key_exists('profile_picture', $data) ? $data['profile_picture'] : null);
        $phone = array_key_exists('phone', $data) ? trim($data['phone']) : null;
        $guardianPhone = array_key_exists('guardianPhone', $data) ? trim($data['guardianPhone']) :
                         (array_key_exists('guardian_phone', $data) ? trim($data['guardian_phone']) : null);

        $stmt = $pdo->prepare("SELECT role FROM users WHERE uid = ?");
        $stmt->execute([$targetUid]);
        $existingRole = $stmt->fetchColumn();
        $exists = $existingRole !== false;

        if ($exists) {
            // Never allow a role change through this endpoint (role_type is the
            // immutable stamp and core/init.php treats divergence as tampering).
            if ($existingRole !== $role) {
                jsonResponse(['error' => 'Role cannot be changed'], 400);
            }
            // Regular admins cannot edit admin or super admin accounts.
            if (in_array($existingRole, ['admin', 'super_admin'], true) && $callerRole !== 'super_admin') {
                jsonResponse(['error' => 'Forbidden: only a super admin can edit admin accounts'], 403);
            }

            // Partial update: only persist fields that were actually sent, so a
            // form that omits username/student_id cannot blank out the login name.
            $set = [];
            $params = [];
            if ($username !== null)    { $set[] = 'username = ?';       $params[] = $username; }
            if ($firstName !== null)   { $set[] = 'first_name = ?';     $params[] = $firstName; }
            if ($lastName !== null)    { $set[] = 'last_name = ?';      $params[] = $lastName; }
            if ($studentId !== null)   { $set[] = 'student_id = ?';     $params[] = $studentId; }
            if ($employeeId !== null)  { $set[] = 'employee_id = ?';    $params[] = $employeeId; }
            if ($profilePicture !== null) { $set[] = 'profile_picture = ?'; $params[] = $profilePicture; }
            if ($phone !== null)          { $set[] = 'phone = ?';          $params[] = $phone; }
            if ($guardianPhone !== null)  { $set[] = 'guardian_phone = ?'; $params[] = $guardianPhone; }

            if (!empty($set)) {
                $params[] = $targetUid;
                $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $set) . " WHERE uid = ?");
                $stmt->execute($params);
            }
        } else {
            $passwordHash = $data['password_hash'] ?? password_hash(bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (uid, username, password_hash, role, role_type, first_name, last_name, student_id, employee_id, profile_picture, phone, guardian_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $targetUid,
                $username ?? ($data['username'] ?? ''),
                $passwordHash,
                $role,
                $role,
                $firstName ?? '',
                $lastName ?? '',
                $studentId ?? '',
                $employeeId ?? null,
                $profilePicture ?? '',
                $phone ?? null,
                $guardianPhone ?? null,
            ]);
        }
        jsonResponse(['success' => true]);
    }

    jsonResponse(['error' => 'Missing collection+uids or uid+role'], 400);
}

// --- GET: single user or teachers list ---
if ($method === 'GET') {
    $singleUid = $_GET['uid'] ?? null;
    $collection = $_GET['collection'] ?? null;

    if ($singleUid) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE uid = ?");
        $stmt->execute([$singleUid]);
        $user = $stmt->fetch();
        if (!$user) jsonResponse(['error' => 'User not found'], 404);
        jsonResponse([
            'uid' => $user['uid'], 'role' => $user['role'],
            'firstName' => $user['first_name'] ?? '', 'lastName' => $user['last_name'] ?? '',
            'first_name' => $user['first_name'] ?? '', 'last_name' => $user['last_name'] ?? '',
            'email' => $user['username'] ?? '',
            'studentId' => $user['student_id'] ?? '', 'student_id' => $user['student_id'] ?? '',
            'employeeId' => $user['employee_id'] ?? '', 'employee_id' => $user['employee_id'] ?? '',
            'profilePicture' => $user['profile_picture'] ?? '', 'profile_picture' => $user['profile_picture'] ?? '',
            'phone' => $user['phone'] ?? '', 'guardianPhone' => $user['guardian_phone'] ?? '',
        ]);
    }

    if ($collection === 'teachers') {
        requireAdmin($pdo, $uid);
        $stmt = $pdo->query("SELECT uid, username, first_name, last_name, employee_id FROM users WHERE role = 'teacher' ORDER BY first_name, last_name");
        $users = $stmt->fetchAll();
        $results = [];
        foreach ($users as $u) {
            $results[] = [
                'uid' => $u['uid'],
                'firstName' => $u['first_name'] ?? '',
                'lastName' => $u['last_name'] ?? '',
                'email' => $u['username'] ?? '',
                'employeeId' => $u['employee_id'] ?? '',
                'employee_id' => $u['employee_id'] ?? '',
            ];
        }
        jsonResponse($results);
    }
    jsonResponse([]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
