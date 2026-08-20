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
// Accept either 'username' or 'email' as the key during transition
$data['username'] = $data['username'] ?? $data['email'] ?? null;
if (empty($data['username']) || empty($data['password']) || empty($data['role'])) {
    jsonResponse(['error' => 'Username, password, and role required'], 400);
}

$username = trim($data['username']);
$password = $data['password'];
$role = $data['role'];
$firstName = capitalizeName($data['firstName'] ?? $data['first_name'] ?? '');
$lastName = capitalizeName($data['lastName'] ?? $data['last_name'] ?? '');
$studentId = $data['studentId'] ?? $data['student_id'] ?? null;
$employeeId = $data['employeeId'] ?? $data['employee_id'] ?? null;
$phone = $data['phone'] ?? null;
$guardianPhone = $data['guardianPhone'] ?? $data['guardian_phone'] ?? null;

if (!in_array($role, ['teacher', 'student'])) {
    jsonResponse(['error' => 'Role must be teacher or student'], 400);
}

$pdo = getPDO();

// Check by username OR student_id for uniqueness
$stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? OR (student_id IS NOT NULL AND student_id = ?)");
$stmt->execute([$username, $username]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'Username already taken'], 409);
}
if ($studentId) {
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE student_id = ?");
    $stmt->execute([$studentId]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Student ID already registered'], 409);
    }
}

$uid = strtolower(bin2hex(random_bytes(16)));
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// For students: check pre-approval before creating account
if ($role === 'student' && $studentId) {
    $stmt = $pdo->prepare("SELECT 1 FROM pre_approved_students WHERE student_id = ? AND used_at IS NULL");
    $stmt->execute([$studentId]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'This Student ID cannot be used for registration. Please contact your administrator.'], 403);
    }
}

$stmt = $pdo->prepare("INSERT INTO users (uid, username, password_hash, role, first_name, last_name, student_id, employee_id, phone, guardian_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$uid, $username, $passwordHash, $role, $firstName, $lastName, $studentId, $employeeId, $phone, $guardianPhone]);

// Mark pre-approved student ID as used
if ($role === 'student' && $studentId) {
    $stmt = $pdo->prepare("UPDATE pre_approved_students SET used_at = GETDATE() WHERE student_id = ?");
    $stmt->execute([$studentId]);
}

$token = generateToken();
$expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

$stmt = $pdo->prepare("INSERT INTO sessions (uid, token, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$uid, $token, $expiresAt]);

jsonResponse([
    'token' => $token,
    'uid' => $uid,
    'role' => $role,
    'user' => [
        'uid' => $uid,
        'username' => $username,
        'email' => $username,
        'role' => $role,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'studentId' => $studentId,
        'employeeId' => $employeeId,
        'phone' => $phone,
    ]
], 201);
