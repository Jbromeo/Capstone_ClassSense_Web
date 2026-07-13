<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    jsonResponse(['error' => 'Invalid request body'], 400);
}
// Accept either 'username' or 'email' as the key
$data['username'] = $data['username'] ?? $data['email'] ?? null;
if (empty($data['username']) || empty($data['password'])) {
    jsonResponse(['error' => 'Username/Student ID and password required'], 400);
}

$login = trim($data['username']);
$password = $data['password'];

$pdo = getPDO();
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR student_id = ?");
$stmt->execute([$login, $login]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['error' => 'Invalid username/ID or password'], 401);
}

$token = generateToken();
$expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

$stmt = $pdo->prepare("INSERT INTO sessions (uid, token, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$user['uid'], $token, $expiresAt]);

jsonResponse([
    'token' => $token,
    'uid' => $user['uid'],
    'role' => $user['role'],
    'user' => [
        'uid' => $user['uid'],
        'email' => $user['username'],
        'role' => $user['role'],
        'firstName' => $user['first_name'] ?? '',
        'lastName' => $user['last_name'] ?? '',
        'studentId' => $user['student_id'] ?? '',
    ]
]);
