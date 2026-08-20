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

// Resolve the role for a given uid. Many API endpoints need role-based access
// control, but verifyToken() only returns the uid (no role). Use this helper
// to enforce admin/teacher/student boundaries where required.
function fetchUserRole($pdo, $uid) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE uid = ?");
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    return $row ? $row['role'] : null;
}

// Ensure the caller has one of the required roles, else 403.
function requireRole($pdo, $uid, $roles) {
    $role = fetchUserRole($pdo, $uid);
    if (!is_array($roles) ? ($role !== $roles) : !in_array($role, $roles, true)) {
        jsonResponse(['error' => 'Forbidden: insufficient role'], 403);
    }
    return $role;
}

// Capitalize the first letter of each word of a name, e.g.
// "nel john" -> "Nel John", "la jos" -> "La Jos", empty values stay empty.
function capitalizeName($name) {
    if ($name === null || $name === '') return '';
    $t = trim((string)$name);
    return $t === '' ? '' : ucfirst(ucwords(strtolower($t)));
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function sendNotification($recipientUid, $type, $title, $message = '', $link = '') {
    try {
        $pdo = getPDO();
        $userStmt = $pdo->prepare("SELECT push_enabled FROM users WHERE uid = ?");
        $userStmt->execute([$recipientUid]);
        $user = $userStmt->fetch();
        $pushEnabled = !empty($user['push_enabled']);

        // The row is born marked as pushed when we intend to push, so a
        // concurrent poll can never see a pushed row as unpushed (race-free).
        // If the push fails, the flag is cleared below so the polling banners
        // can surface the row as a fallback.
        $stmt = $pdo->prepare("INSERT INTO notifications (recipient_uid, type, title, message, link, push_sent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$recipientUid, $type, $title, $message, $link, $pushEnabled ? 1 : 0]);
        $notifId = $pdo->lastInsertId();

        // Best-effort device push. Failures here never break the in-app flow.
        if ($pushEnabled) {
            $pushed = false;
            try {
                $tokStmt = $pdo->prepare("SELECT token FROM push_subscriptions WHERE uid = ?");
                $tokStmt->execute([$recipientUid]);
                $tokens = $tokStmt->fetchAll();
                foreach ($tokens as $t) {
                    $result = fcmSend($t['token'], $title, $message, $link, $type, $notifId);
                    if ($result === true) {
                        $pushed = true;
                        error_log("fcm: push sent to " . $t['token'] . " (type={$type})");
                    } else if ($result === 400 || $result === 404 || $result === 410) {
                        // 400 invalid token, 404 = token not found, 410 = device unregistered: drop it
                        $del = $pdo->prepare("DELETE FROM push_subscriptions WHERE token = ?");
                        $del->execute([$t['token']]);
                        error_log("fcm: dropped invalid token ({$result}) " . $t['token']);
                    } else {
                        error_log("fcm: send failed result={$result} for token " . $t['token']);
                    }
                }
            } catch (PDOException $e) {
                error_log('push dispatch failed: ' . $e->getMessage());
            }
            // Nothing actually pushed — let the polling banners show it.
            if (!$pushed) {
                $flag = $pdo->prepare("UPDATE notifications SET push_sent = 0 WHERE id = ?");
                $flag->execute([$notifId]);
            }
        }
    } catch (PDOException $e) {
        error_log('sendNotification failed: ' . $e->getMessage());
    }
}

// --- Firebase Cloud Messaging (HTTP v1) helpers ---

// URL-safe base64 for JWT encoding.
function base64url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Exchange the service-account credentials for a short-lived OAuth2 access token
// (JWT signed with RS256 via PHP's built-in OpenSSL).
function fcmAccessToken() {
    global $config;
    $path = $config['fcm_service_account_path'] ?? '';
    if (!$path || !file_exists($path)) {
        error_log('fcm: service account not found at ' . $path);
        return null;
    }
    $sa = json_decode(file_get_contents($path), true);
    if (!$sa || empty($sa['client_email']) || empty($sa['private_key'])) {
        error_log('fcm: invalid service account json');
        return null;
    }

    $now = time();
    $header  = base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claims  = base64url(json_encode([
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud'   => $sa['token_uri'],
        'iat'   => $now,
        'exp'   => $now + 3600,
    ]));
    $signingInput = $header . '.' . $claims;

    $signature = '';
    if (!openssl_sign($signingInput, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256)) {
        error_log('fcm: openssl_sign failed');
        return null;
    }
    $assertion = $signingInput . '.' . base64url($signature);

    $ch = curl_init($sa['token_uri']);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $assertion,
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log('fcm: token exchange failed http=' . $httpCode . ' body=' . substr((string)$response, 0, 300));
        return null;
    }
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// Send a single web-push notification to one device token.
// Returns true on success, the HTTP status code on failure.
function fcmSend($token, $title, $message, $link = '', $type = '', $notifId = '') {
    global $config;
    $accessToken = fcmAccessToken();
    if (!$accessToken) return false;

    $payload = [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => mb_substr($title ?? '', 0, 100),
                'body'  => mb_substr($message ?? '', 0, 200),
            ],
            'data' => [
                'link' => $link ?? '',
                'type' => $type ?? '',
                'id'   => (string)($notifId ?? ''),
            ],
        ],
    ];

    $url = 'https://fcm.googleapis.com/v1/projects/' . urlencode($config['firebase_project_id']) . '/messages:send';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log('fcm: send failed http=' . $httpCode . ' body=' . substr((string)$response, 0, 300));
        return $httpCode;
    }
    return true;
}
