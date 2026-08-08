<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

// --- GET: list students ---
if ($method === 'GET') {
    $search = $_GET['search'] ?? '';
    if ($search) {
        $stmt = $pdo->prepare("
            SELECT uid, username, first_name, last_name, student_id
            FROM users
            WHERE role = 'student'
              AND (first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR student_id LIKE ?)
            ORDER BY first_name, last_name
        ");
        $like = "%$search%";
        $stmt->execute([$like, $like, $like, $like]);
    } else {
        $stmt = $pdo->query("
            SELECT uid, username, first_name, last_name, student_id
            FROM users
            WHERE role = 'student'
            ORDER BY first_name, last_name
        ");
    }
    $rows = $stmt->fetchAll();
    $results = [];
    foreach ($rows as $r) {
        $results[] = [
            'uid' => $r['uid'],
            'username' => $r['username'],
            'firstName' => $r['first_name'] ?? '',
            'lastName' => $r['last_name'] ?? '',
            'studentId' => $r['student_id'] ?? '',
        ];
    }
    jsonResponse($results);
}

// --- PUT: update student details ---
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['uid'])) {
        jsonResponse(['error' => 'Missing uid'], 400);
    }

    $username = trim($data['username'] ?? '');
    $firstName = capitalizeName($data['firstName'] ?? '');
    $lastName = capitalizeName($data['lastName'] ?? '');

    if (!$username) jsonResponse(['error' => 'Username is required'], 400);
    if (!$firstName || !$lastName) jsonResponse(['error' => 'First and last name are required'], 400);

    // Check username uniqueness (exclude current user)
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? AND uid != ?");
    $stmt->execute([$username, $data['uid']]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Username already taken'], 409);
    }

    $stmt = $pdo->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ? WHERE uid = ? AND role = 'student'");
    $stmt->execute([$username, $firstName, $lastName, $data['uid']]);
    if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Student not found'], 404);

    jsonResponse(['success' => true]);
}

// --- POST: reset password ---
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['uid']) || empty($data['password'])) {
        jsonResponse(['error' => 'Missing uid or password'], 400);
    }
    if (strlen($data['password']) < 6) {
        jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
    }

    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE uid = ? AND role = 'student'");
    $stmt->execute([$hash, $data['uid']]);
    if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Student not found'], 404);

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
