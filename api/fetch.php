<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

// --- DELETE: remove user and all dependent data ---
if ($method === 'DELETE') {
    // Only an admin may delete users. (Any authenticated user could delete any
    // account otherwise, including admin's own.)
    requireRole($pdo, $uid, 'admin');

    $targetUid = $_GET['uid'] ?? null;
    if (!$targetUid) jsonResponse(['error' => 'Missing uid'], 400);

    // Fetch student_id/role before deleting so we can clear pre-approval
    $stmt = $pdo->prepare("SELECT role, student_id FROM users WHERE uid = ?");
    $stmt->execute([$targetUid]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(['error' => 'User not found'], 404);

    $pdo->beginTransaction();
    try {
        // Teachers: classes.teacher_uid has a NO-ACTION FK, so the teacher's
        // classes (which then cascade-delete class_students + attendance) must be
        // removed first. The sessions/notifications cascades fire on user delete.
        if ($user['role'] === 'teacher') {
            $pdo->prepare("DELETE FROM classes WHERE teacher_uid = ?")->execute([$targetUid]);
            $pdo->prepare("DELETE FROM events WHERE teacher_uid = ?")->execute([$targetUid]);
        }

        // student_enrollment + attendance are cleaned by the ON DELETE CASCADE
        // FKs on class_students(student_uid) and attendance(student_uid).
        $stmt = $pdo->prepare("DELETE FROM users WHERE uid = ?");
        $stmt->execute([$targetUid]);

        // If a pre-approved student was deleted, free up their ID
        if ($user['role'] === 'student' && !empty($user['student_id'])) {
            $pdo->prepare("UPDATE pre_approved_students SET used_at = NULL WHERE student_id = ?")
                ->execute([$user['student_id']]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Delete failed: ' . $e->getMessage()], 500);
    }

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
                'department' => $u['department'] ?? '',
                'profilePicture' => $u['profile_picture'] ?? '',
                'profile_picture' => $u['profile_picture'] ?? '',
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
                    'employeeId' => '', 'employee_id' => '', 'department' => '',
                    'profilePicture' => '', 'profile_picture' => '',
                    'exists' => false,
                ];
            }
        }
        jsonResponse($results);
    }

    // Single profile upsert (create or update)
    if (!empty($data['uid']) && !empty($data['role'])) {
        $firstName = capitalizeName($data['firstName'] ?? $data['first_name'] ?? null);
        $lastName = capitalizeName($data['lastName'] ?? $data['last_name'] ?? null);
        $username = array_key_exists('username', $data) ? trim($data['username']) : null;
        $studentId = array_key_exists('studentId', $data) ? $data['studentId'] :
                     (array_key_exists('student_id', $data) ? $data['student_id'] : null);
        $employeeId = array_key_exists('employeeId', $data) ? $data['employeeId'] :
                      (array_key_exists('employee_id', $data) ? $data['employee_id'] : null);
        $department = array_key_exists('department', $data) ? $data['department'] : null;
        $profilePicture = array_key_exists('profilePicture', $data) ? $data['profilePicture'] :
                          (array_key_exists('profile_picture', $data) ? $data['profile_picture'] : null);

        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE uid = ?");
        $stmt->execute([$data['uid']]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Partial update: only persist fields that were actually sent, so a
            // form that omits username/student_id cannot blank out the login name.
            $set = [];
            $params = [];
            if ($username !== null)    { $set[] = 'username = ?';       $params[] = $username; }
            if ($firstName !== null)   { $set[] = 'first_name = ?';     $params[] = $firstName; }
            if ($lastName !== null)    { $set[] = 'last_name = ?';      $params[] = $lastName; }
            if ($studentId !== null)   { $set[] = 'student_id = ?';     $params[] = $studentId; }
            if ($employeeId !== null)  { $set[] = 'employee_id = ?';    $params[] = $employeeId; }
            if ($department !== null)  { $set[] = 'department = ?';     $params[] = $department; }
            if ($profilePicture !== null) { $set[] = 'profile_picture = ?'; $params[] = $profilePicture; }

            if (!empty($set)) {
                $params[] = $data['uid'];
                $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $set) . " WHERE uid = ?");
                $stmt->execute($params);
            }
        } else {
            $passwordHash = $data['password_hash'] ?? password_hash(bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (uid, username, password_hash, role, first_name, last_name, student_id, employee_id, department, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['uid'],
                $username ?? ($data['username'] ?? ''),
                $passwordHash,
                $data['role'],
                $firstName ?? '',
                $lastName ?? '',
                $studentId ?? '',
                $employeeId ?? null,
                $department ?? null,
                $profilePicture ?? '',
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
            'department' => $user['department'] ?? '',
            'profilePicture' => $user['profile_picture'] ?? '', 'profile_picture' => $user['profile_picture'] ?? '',
        ]);
    }

    if ($collection === 'teachers') {
        $stmt = $pdo->query("SELECT uid, username, first_name, last_name, employee_id, department FROM users WHERE role = 'teacher' ORDER BY first_name, last_name");
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
                'department' => $u['department'] ?? '',
            ];
        }
        jsonResponse($results);
    }
    jsonResponse([]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
