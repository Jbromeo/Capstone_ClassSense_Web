<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

// --- DELETE: remove user from users table ---
if ($method === 'DELETE') {
    $targetUid = $_GET['uid'] ?? null;
    if (!$targetUid) jsonResponse(['error' => 'Missing uid'], 400);

    // Fetch student_id before deleting so we can clear pre-approval
    $stmt = $pdo->prepare("SELECT role, student_id FROM users WHERE uid = ?");
    $stmt->execute([$targetUid]);
    $user = $stmt->fetch();

    $stmt = $pdo->prepare("DELETE FROM users WHERE uid = ?");
    $stmt->execute([$targetUid]);
    if ($stmt->rowCount() === 0) jsonResponse(['error' => 'User not found'], 404);

    // If a pre-approved student was deleted, free up their ID
    if ($user && $user['role'] === 'student' && !empty($user['student_id'])) {
        $pdo->prepare("UPDATE pre_approved_students SET used_at = NULL WHERE student_id = ?")
            ->execute([$user['student_id']]);
    }

    // Clean up orphaned enrollments
    $pdo->prepare("DELETE FROM class_students WHERE student_uid = ?")
        ->execute([$targetUid]);

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
            ];
        }
        // Placeholder for unmatched UIDs
        foreach ($uids as $ruid) {
            if (!in_array($ruid, $foundUids)) {
                $results[] = [
                    'uid' => $ruid, 'role' => '', 'firstName' => 'Unknown',
                    'lastName' => $collection === 'students' ? 'Student' : 'User',
                    'first_name' => 'Unknown', 'last_name' => $collection === 'students' ? 'Student' : 'User',
                    'email' => '', 'studentId' => '', 'student_id' => '',
                    'employeeId' => '', 'employee_id' => '', 'department' => '',
                    'profilePicture' => '', 'profile_picture' => '',
                ];
            }
        }
        jsonResponse($results);
    }

    // Single profile upsert (create or update)
    if (!empty($data['uid']) && !empty($data['role'])) {
        $firstName = $data['firstName'] ?? $data['first_name'] ?? '';
        $lastName = $data['lastName'] ?? $data['last_name'] ?? '';
        $username = $data['username'] ?? '';
        $studentId = $data['studentId'] ?? $data['student_id'] ?? '';
        $employeeId = $data['employeeId'] ?? $data['employee_id'] ?? null;
        $department = $data['department'] ?? null;
        $profilePicture = $data['profilePicture'] ?? $data['profile_picture'] ?? '';

        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE uid = ?");
        $stmt->execute([$data['uid']]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ?, student_id = ?, employee_id = ?, department = ?, profile_picture = ? WHERE uid = ?");
            $stmt->execute([$username, $firstName, $lastName, $studentId, $employeeId, $department, $profilePicture, $data['uid']]);
        } else {
            $passwordHash = $data['password_hash'] ?? password_hash(bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (uid, username, password_hash, role, first_name, last_name, student_id, employee_id, department, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$data['uid'], $username, $passwordHash, $data['role'], $firstName, $lastName, $studentId, $employeeId, $department, $profilePicture]);
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
