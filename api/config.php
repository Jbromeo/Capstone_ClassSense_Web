<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$configPath = __DIR__ . '/config.json';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'config.json not found']);
    exit;
}

$config = json_decode(file_get_contents($configPath), true);
if (!$config) {
    http_response_code(500);
    echo json_encode(['error' => 'config.json parse error']);
    exit;
}

function getPDO() {
    global $config;
    try {
        $dsn = "sqlsrv:Server={$config['db_host']};Database={$config['db_name']}";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
        exit;
    }
}

function verifyToken() {
    global $config;
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches);
        }
    }

    // If a Bearer token was provided, try to verify it
    if (!empty($matches[1])) {
        $idToken = $matches[1];

        // Try custom session token first (teachers + students) — fast, local lookup
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT uid FROM sessions WHERE token = ? AND expires_at > GETDATE()");
            $stmt->execute([$idToken]);
            $row = $stmt->fetch();
            if ($row) return $row['uid'];
        } catch (PDOException $e) {
            // sessions table might not exist yet
        }

        // Fallback: try Firebase token verification (admin accounts)
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$config['firebase_api_key']}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['idToken' => $idToken]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $uid = $data['users'][0]['localId'] ?? null;
            if ($uid) return $uid;
        }
    }

    // Last resort: check PHP session (works when page loaded via init.php)
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (!empty($_SESSION['uid'])) {
        return $_SESSION['uid'];
    }

    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function sendNotification($recipientUid, $type, $title, $message = '', $link = '') {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("INSERT INTO notifications (recipient_uid, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$recipientUid, $type, $title, $message, $link]);
    } catch (PDOException $e) {
        error_log('sendNotification failed: ' . $e->getMessage());
    }
}
