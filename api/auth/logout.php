<?php
header('Content-Type: application/json');

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $authHeader;
}
preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches);
$token = $matches[1] ?? '';

if ($token) {
    require_once __DIR__ . '/../config.php';
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE token = ?");
        $stmt->execute([$token]);
    } catch (Exception $e) {}
}

jsonResponse(['success' => true]);
