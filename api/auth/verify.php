<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$uid = verifyToken();

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE uid = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }

    jsonResponse([
        'uid' => $user['uid'],
        'email' => $user['username'],
        'role' => $user['role'],
        'firstName' => $user['first_name'] ?? '',
        'lastName' => $user['last_name'] ?? '',
        'studentId' => $user['student_id'] ?? '',
        'profilePicture' => $user['profile_picture'] ?? '',
    ]);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Verification failed'], 500);
}
