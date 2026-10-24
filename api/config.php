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

        // Try custom session token first (teachers, students, admin) — fast, local lookup
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT uid FROM sessions WHERE token = ? AND expires_at > GETDATE()");
            $stmt->execute([$idToken]);
            $row = $stmt->fetch();
            if ($row) return $row['uid'];
        } catch (PDOException $e) {
            // sessions table might not exist yet
        }
    }

    // No Bearer token (or it wasn't a session token): fall back to the PHP
    // session, which is set when the page is loaded via init.php. This lets
    // cookie-authenticated requests (e.g. the theme toggle, which sends no
    // Authorization header) resolve the current user.
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (!empty($_SESSION['uid'])) {
        return $_SESSION['uid'];
    }

    // A Bearer token was provided but didn't match anything — reject it.
    if (!empty($matches[1])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }
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

// Admin or super admin (all existing admin features).
function requireAdmin($pdo, $uid) {
    return requireRole($pdo, $uid, ['admin', 'super_admin']);
}

// Super admin only (account deletion, admin management, audit log).
function requireSuperAdmin($pdo, $uid) {
    return requireRole($pdo, $uid, 'super_admin');
}

// Hard-delete a user and every dependent row (classes/events for teachers,
// enrollment/attendance/grades via FK cascades, sessions, notifications).
// Caller must already be authorized. Returns the deleted user row, or null.
// Throws on DB failure (transaction rolled back).
function deleteUserCascade($pdo, $targetUid) {
    $stmt = $pdo->prepare("SELECT uid, role, student_id, username, first_name, last_name FROM users WHERE uid = ?");
    $stmt->execute([$targetUid]);
    $user = $stmt->fetch();
    if (!$user) return null;

    $pdo->beginTransaction();
    try {
        // Teachers: classes.teacher_uid has a NO-ACTION FK, so the teacher's
        // classes (which then cascade-delete class_students + attendance) must
        // be removed first. The sessions/notifications cascades fire on delete.
        if ($user['role'] === 'teacher') {
            $pdo->prepare("DELETE FROM classes WHERE teacher_uid = ?")->execute([$targetUid]);
            $pdo->prepare("DELETE FROM events WHERE teacher_uid = ?")->execute([$targetUid]);
        }

        $pdo->prepare("DELETE FROM users WHERE uid = ?")->execute([$targetUid]);

        // If a pre-approved student was deleted, free up their ID
        if ($user['role'] === 'student' && !empty($user['student_id'])) {
            $pdo->prepare("UPDATE pre_approved_students SET used_at = NULL WHERE student_id = ?")
                ->execute([$user['student_id']]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return $user;
}

// Number of super_admin accounts, optionally excluding one uid.
function countSuperAdmins($pdo, $excludeUid = null) {
    if ($excludeUid !== null) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'super_admin' AND uid != ?");
        $stmt->execute([$excludeUid]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
    }
    return (int)$stmt->fetchColumn();
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

// --- AI insight queue (proactive regeneration on grade/attendance changes) ---

// Mark a student's insight as stale. The queue worker regenerates it once the
// 2-minute debounce window has passed since the LAST change (each change resets
// changed_at, so rapid edits batch into a single generation).
function markInsightStale($pdo, $studentUid, $classId) {
    try {
        $stmt = $pdo->prepare("MERGE ai_insights AS target USING (SELECT ? AS student_uid, ? AS class_id) AS source
            ON target.student_uid = source.student_uid AND target.class_id = source.class_id
            WHEN MATCHED THEN UPDATE SET pending_refresh = 1, changed_at = GETDATE()
            WHEN NOT MATCHED THEN INSERT (student_uid, class_id, insight_paragraph, insight_tips, signature, pending_refresh, changed_at)
            VALUES (source.student_uid, source.class_id, '', NULL, '', 1, GETDATE());");
        $stmt->execute([$studentUid, $classId]);
    } catch (PDOException $e) {
        error_log('markInsightStale failed: ' . $e->getMessage());
    }
}

// Build the grades/attendance snapshot for one student/class. The sha1 signature
// over the snapshot is what detects "data changed" between generations.
function buildInsightSnapshot($pdo, $studentUid, $classId) {
    $classStmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE id = ?");
    $classStmt->execute([$classId]);
    $classData = $classStmt->fetch();
    $className = $classData['class_name'] ?? 'your class';

    $attStmt = $pdo->prepare("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('Present', 'Verified') THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
    FROM attendance WHERE class_id = ? AND student_uid = ?");
    $attStmt->execute([$classId, $studentUid]);
    $att = $attStmt->fetch();

    $total = (int)($att['total'] ?? 0);
    $present = (int)($att['present'] ?? 0) + (int)($att['late'] ?? 0);
    $late = (int)($att['late'] ?? 0);
    $absent = (int)($att['absent'] ?? 0);
    $attRate = $total > 0 ? round(($present / $total) * 100) : null;

    $trendStmt = $pdo->prepare("SELECT TOP 5 status FROM attendance WHERE class_id = ? AND student_uid = ? ORDER BY timestamp DESC");
    $trendStmt->execute([$classId, $studentUid]);
    $attTrend = array_map(fn($r) => $r['status'], $trendStmt->fetchAll());

    $terms = [];
    for ($q = 1; $q <= 3; $q++) {
        $compStmt = $pdo->prepare("SELECT id, category, name, hps FROM grade_components WHERE class_id = ? AND quarter = ? ORDER BY category, id");
        $compStmt->execute([$classId, $q]);
        $components = $compStmt->fetchAll();

        $grades = [];
        $ids = array_column($components, 'id');
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $gradeStmt = $pdo->prepare("SELECT component_id, score FROM grades WHERE component_id IN ($placeholders) AND student_uid = ?");
            $gradeStmt->execute(array_merge($ids, [$studentUid]));
            foreach ($gradeStmt->fetchAll() as $g) {
                $grades[$g['component_id']] = (float)$g['score'];
            }
        }

        $weightsStmt = $pdo->prepare("SELECT category, weight_percent FROM grade_weights WHERE class_id = ?");
        $weightsStmt->execute([$classId]);
        $weights = ['written' => 0, 'performance' => 0, 'exam' => 0, 'attendance' => 0];
        foreach ($weightsStmt->fetchAll() as $w) {
            $weights[$w['category']] = (int)$w['weight_percent'];
        }

        $catAvgs = [];
        foreach (['written', 'performance', 'exam', 'attendance'] as $cat) {
            $comps = array_values(array_filter($components, fn($c) => $c['category'] === $cat));
            $ts = 0;
            $th = 0;
            foreach ($comps as $c) {
                if (isset($grades[$c['id']])) {
                    $ts += $grades[$c['id']];
                    $th += (int)$c['hps'];
                }
            }
            $catAvgs[$cat] = $th > 0 ? round(($ts / $th) * 100, 1) : null;
        }

        $pending = 0;
        foreach ($components as $c) {
            if (!isset($grades[$c['id']])) $pending++;
        }

        $finalTotal = 0;
        $finalWeight = 0;
        foreach ($catAvgs as $cat => $avg) {
            $w = $weights[$cat] ?? 0;
            if ($avg !== null && $w > 0) {
                $finalTotal += $avg * ($w / 100);
                $finalWeight += $w;
            }
        }

        $weak = null;
        foreach ($catAvgs as $cat => $avg) {
            if ($avg !== null && ($weak === null || $avg < $weak['avg'])) {
                $weak = ['cat' => $cat, 'avg' => $avg];
            }
        }

        $weightedCategories = array_map('strval', array_keys(array_filter($weights, fn($w) => $w > 0)));
        $gradedCount = 0;
        foreach ($weightedCategories as $cat) {
            $catComps = array_values(array_filter($components, fn($c) => $c['category'] === $cat));
            $hasComp = count($catComps) > 0;
            $hasScore = false;
            foreach ($catComps as $c) {
                if (isset($grades[$c['id']])) { $hasScore = true; break; }
            }
            if ($hasComp && $hasScore) $gradedCount++;
        }
        $completeTerm = count($weightedCategories) > 0 && $gradedCount === count($weightedCategories);

        $terms[$q] = [
            'categoryAverages' => $catAvgs,
            'pendingCount' => $pending,
            'finalGrade' => $finalWeight > 0 ? round($finalTotal, 1) : null,
            'weakestCategory' => $weak ? $weak['cat'] : null,
            'weakestAverage' => $weak ? $weak['avg'] : null,
            'gradedCount' => $gradedCount,
            'weightedCategories' => count($weightedCategories),
            'complete' => $completeTerm,
        ];
    }

    $snapshot = [
        'attendance' => ['total' => $total, 'present' => $present, 'late' => $late, 'absent' => $absent, 'rate' => $attRate, 'trend' => $attTrend],
        'terms' => $terms,
        'weights' => $weights,
    ];

    return [
        'snapshot' => $snapshot,
        'signature' => sha1(json_encode($snapshot)),
        'className' => $className,
    ];
}

function buildInsightPrompt($className, $snapshot) {
    $att = $snapshot['attendance'];
    $catNames = ['written' => 'Written', 'performance' => 'Performance', 'exam' => 'Exams', 'attendance' => 'Attendance'];

    if ($att['total'] > 0) {
        $attLine = 'Attendance: ' . $att['present'] . ' Present, ' . $att['late'] . ' Late, ' . $att['absent'] . ' Absent out of ' . $att['total'] . ' sessions (' . $att['rate'] . '% present).';
    } else {
        $attLine = 'Attendance: 0 records - no Present, Late, or Absent statuses yet.';
    }

    $labels = ['1' => 'TERM 1', '2' => 'TERM 2', '3' => 'TERM 3'];
    $termLines = [];
    $hasAnyScore = false;
    foreach ($snapshot['terms'] as $q => $t) {
        $cats = [];
        foreach ($t['categoryAverages'] as $cat => $avg) {
            if ($avg === null) {
                $cats[] = $catNames[$cat] . ': no scores yet';
            } else {
                $cats[] = $catNames[$cat] . ': ' . $avg . '%';
                $hasAnyScore = true;
            }
        }
        if ($t['complete'] && $t['finalGrade'] !== null) {
            $cats[] = 'Final grade: ' . $t['finalGrade'];
        }
        $termLines[] = ($labels[$q] ?? ('Term ' . $q)) . ': ' . implode(', ', $cats) . '.';
    }

    $scoreSummary = $hasAnyScore
        ? 'NOTE: This student HAS recorded scores in at least one category. Mention the real percentages listed above.'
        : 'NOTE: This student has NO recorded scores in any category yet.';

    return "You are a warm, concise academic advisor for a student in the class " . $className . ". "
        . "Use ONLY the data below; never invent numbers.\n\n"
        . $attLine . "\n"
        . implode("\n", $termLines) . "\n\n"
        . $scoreSummary . "\n\n"
        . "RULES:\n"
        . "- A percentage means that category HAS scores. Never describe it as 'no scores'.\n"
        . "- 'no scores yet' means only that single category is empty.\n"
        . "- Only say the student 'has no scores' if EVERY category in EVERY term says 'no scores yet'.\n"
        . "- Never mention a grade, percentage, or attendance figure that is not listed above.\n\n"
        . "Write 2-3 warm, specific sentences as the paragraph, and up to 3 short bullet tips.\n"
        . "Return ONLY JSON with keys 'paragraph' (string) and 'tips' (array of strings).";
}

// --- AI provider abstraction ---
// Providers are tried in priority order (see callAIInsight). Each adapter
// returns a normalized ['ok' => true, 'insight' => [...]], or
// ['ok' => false, 'error' => ...]. A disabled/unconfigured provider is simply
// skipped by the dispatcher.

function loadAIProviders() {
    $aiConfigPath = dirname(__DIR__) . '/AI/config.php';
    if (!file_exists($aiConfigPath)) {
        return null;
    }
    $cfg = require $aiConfigPath;
    return is_array($cfg) && isset($cfg['providers']) ? $cfg['providers'] : null;
}

// Pull the {paragraph, tips} object out of a model reply, tolerating markdown
// code fences and leading/trailing chatter.
function extractInsightJson($text) {
    $text = trim((string)$text);
    if ($text === '') return null;
    if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $m)) {
        $text = $m[1];
    }
    $decoded = json_decode($text, true);
    if (is_array($decoded) && !empty($decoded['paragraph'])) return $decoded;
    if (preg_match('/\{.*\}/s', $text, $m)) {
        $decoded = json_decode($m[0], true);
        if (is_array($decoded) && !empty($decoded['paragraph'])) return $decoded;
    }
    return null;
}

function normalizeInsight($decoded) {
    return [
        'ok' => true,
        'insight' => [
            'paragraph' => trim((string)$decoded['paragraph']),
            'tips' => array_values(array_filter(array_map('trim', $decoded['tips'] ?? []))),
        ],
    ];
}

// OpenAI-compatible chat completions (used by Groq and Ollama).
function callOpenAICompatInsight($baseUrl, $apiKey, $model, $prompt, $extra = []) {
    $payload = array_merge([
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'stream' => false,
    ], $extra);

    $headers = ['Content-Type: application/json'];
    if ($apiKey !== '') $headers[] = 'Authorization: Bearer ' . $apiKey;

    $ch = curl_init(rtrim($baseUrl, '/') . '/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || $response === false) {
        error_log('OpenAI-compat call failed: HTTP ' . $httpCode . ' ' . ($curlError ?: (string)$response));
        if ($httpCode === 429) return ['ok' => false, 'error' => 'ai_quota_exceeded'];
        return ['ok' => false, 'error' => 'ai_request_failed'];
    }

    $parsed = json_decode($response, true);
    $text = $parsed['choices'][0]['message']['content'] ?? null;
    if (!$text) {
        error_log('OpenAI-compat returned no text: ' . substr((string)$response, 0, 300));
        return ['ok' => false, 'error' => 'ai_unparseable'];
    }
    $decoded = extractInsightJson($text);
    if (!$decoded) {
        error_log('OpenAI-compat returned unparseable insight: ' . $text);
        return ['ok' => false, 'error' => 'ai_unparseable'];
    }
    return normalizeInsight($decoded);
}

function callOllamaInsight($prompt) {
    $providers = loadAIProviders();
    $cfg = $providers['ollama'] ?? null;
    if (!$cfg || empty($cfg['enabled']) || empty($cfg['base_url']) || empty($cfg['model'])) {
        return ['ok' => false, 'error' => 'ai_not_configured'];
    }
    return callOpenAICompatInsight($cfg['base_url'], '', $cfg['model'], $prompt, ['format' => 'json']);
}

function callGroqInsight($prompt) {
    $providers = loadAIProviders();
    $cfg = $providers['groq'] ?? null;
    if (!$cfg || empty($cfg['enabled']) || empty($cfg['api_key']) || empty($cfg['model'])) {
        return ['ok' => false, 'error' => 'ai_not_configured'];
    }
    $baseUrl = $cfg['base_url'] ?? 'https://api.groq.com/openai/v1';
    return callOpenAICompatInsight($baseUrl, $cfg['api_key'], $cfg['model'], $prompt, ['response_format' => ['type' => 'json_object']]);
}

// Call Gemini (generateContent API) with the built prompt and parse the JSON reply.
function callGeminiInsight($prompt) {
    $providers = loadAIProviders();
    $cfg = $providers['gemini'] ?? null;
    if (!$cfg || empty($cfg['enabled']) || empty($cfg['api_key'])) {
        return ['ok' => false, 'error' => 'ai_not_configured'];
    }
    $apiKey  = $cfg['api_key'];
    $model   = $cfg['model'] ?? 'gemini-1.5-flash';

    // Standard Gemini generateContent endpoint
    $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);

    $payload = json_encode([
        'contents' => [
            ['role' => 'user', 'parts' => [['text' => $prompt]]]
        ],
        'generationConfig' => [
            'responseMimeType' => 'application/json',
            'temperature'      => 0.7,
            'maxOutputTokens'  => 1024,
        ],
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || $response === false) {
        error_log('Gemini call failed: HTTP ' . $httpCode . ' ' . ($curlError ?: substr((string)$response, 0, 300)));
        if ($httpCode === 429) return ['ok' => false, 'error' => 'ai_quota_exceeded'];
        return ['ok' => false, 'error' => 'ai_request_failed'];
    }

    // Gemini generateContent response: candidates[0].content.parts[0].text
    $parsed = json_decode($response, true);
    $text   = $parsed['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$text) {
        error_log('Gemini returned no text: ' . substr((string)$response, 0, 300));
        return ['ok' => false, 'error' => 'ai_unparseable'];
    }
    $decoded = extractInsightJson($text);
    if (!$decoded) {
        error_log('Gemini returned unparseable insight: ' . $text);
        return ['ok' => false, 'error' => 'ai_unparseable'];
    }
    return normalizeInsight($decoded);
}


// Try providers in priority order and return the first success. Providers that
// are disabled or unavailable are skipped automatically.
function callAIInsight($prompt) {
    $order = ['groq', 'ollama', 'gemini'];
    $lastError = 'ai_not_configured';
    foreach ($order as $name) {
        if ($name === 'ollama') $res = callOllamaInsight($prompt);
        elseif ($name === 'groq') $res = callGroqInsight($prompt);
        else $res = callGeminiInsight($prompt);

        if ($res['ok']) return $res;
        $lastError = $res['error'] ?? $lastError;
    }
    return ['ok' => false, 'error' => $lastError];
}

// ── Local insight fallback ───────────────────────────────────────────────────
// Called when all external AI providers fail. Generates a realistic, data-driven
// insight paragraph + tips from the student's actual snapshot so that
// pending_refresh is ALWAYS cleared (no infinite "Generating..." loop).
function buildLocalInsightFallback(array $snapshot, string $className): array {
    $att  = $snapshot['attendance'] ?? [];
    $total   = (int)($att['total']   ?? 0);
    $present = (int)($att['present'] ?? 0);
    $late    = (int)($att['late']    ?? 0);
    $absent  = (int)($att['absent']  ?? 0);
    $attRate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : null;

    // Grab Term 1 data (most likely to have scores)
    $term = null;
    foreach ([1, 2, 3] as $q) {
        if (!empty($snapshot['terms'][$q])) { $term = $snapshot['terms'][$q]; break; }
    }

    $finalGrade  = $term['finalGrade']      ?? null;
    $catAvgs     = $term['categoryAverages'] ?? [];
    $weakCat     = $term['weakestCategory'] ?? null;
    $written     = $catAvgs['written']      ?? null;
    $performance = $catAvgs['performance']  ?? null;

    // ── Attendance sentence ───────────────────────────────────────────────────
    if ($attRate === null || $total === 0) {
        $attSentence = "No attendance records have been logged yet for this student.";
    } elseif ($attRate >= 95) {
        $attSentence = "This student has excellent attendance with a {$attRate}% presence rate across {$total} recorded session(s)" . ($late > 0 ? ", including {$late} late arrival(s)" : "") . ".";
    } elseif ($attRate >= 85) {
        $attSentence = "Attendance is good at {$attRate}%" . ($absent > 0 ? " with {$absent} absence(s) noted" : "") . " over {$total} session(s).";
    } elseif ($attRate >= 70) {
        $attSentence = "Attendance stands at {$attRate}% across {$total} session(s) with {$absent} absence(s) and {$late} late arrival(s) — consistency needs improvement.";
    } else {
        $attSentence = "Attendance is a concern at {$attRate}% ({$absent} absence(s) out of {$total} session(s)), which is likely affecting overall performance.";
    }

    // ── Grade sentence ────────────────────────────────────────────────────────
    if ($finalGrade !== null) {
        $fg = number_format($finalGrade, 1);
        if ($finalGrade >= 90)      $gradeSentence = "Academic performance is outstanding with a final grade of {$fg}% in {$className}.";
        elseif ($finalGrade >= 85)  $gradeSentence = "Performance is commendable with a final grade of {$fg}% in {$className}, reflecting strong understanding of the subject.";
        elseif ($finalGrade >= 75)  $gradeSentence = "Performance is satisfactory with a final grade of {$fg}% in {$className}, meeting the minimum passing standard.";
        elseif ($finalGrade >= 65)  $gradeSentence = "The current final grade of {$fg}% in {$className} is below the passing threshold — focused effort is needed.";
        else                         $gradeSentence = "The current final grade of {$fg}% in {$className} is critically low and requires immediate attention and support.";
    } else {
        $gradeSentence = "A final grade has not yet been computed — more assessment components need scores before a complete picture is available.";
    }

    // ── Component detail ──────────────────────────────────────────────────────
    $parts = [];
    if ($written     !== null) $parts[] = "Written Works average of " . number_format($written, 1) . "%";
    if ($performance !== null) $parts[] = "Performance Tasks average of " . number_format($performance, 1) . "%";
    $componentDetail = !empty($parts) ? " The student has a " . implode(' and a ', $parts) . "." : "";

    // ── Weakest category ──────────────────────────────────────────────────────
    $catLabels = ['written' => 'Written Works', 'performance' => 'Performance Tasks', 'exam' => 'Examinations', 'attendance' => 'Attendance'];
    $weakNote = $weakCat && isset($catLabels[$weakCat]) ? " The weakest area is {$catLabels[$weakCat]}, which should be the focus for improvement." : "";

    $paragraph = trim("{$attSentence} {$gradeSentence}{$componentDetail}{$weakNote}");

    // ── Tips ──────────────────────────────────────────────────────────────────
    $tips = [];
    if ($attRate !== null && $attRate < 90) $tips[] = "Improve attendance — every missed session can affect quiz and performance scores directly.";
    if ($late > 1)                           $tips[] = "Make a habit of arriving on time to avoid missing key instructions or activity introductions.";
    if ($written !== null && $written < 75)  $tips[] = "Review past quiz items, especially topics where points were lost. Seek teacher clarification on difficult areas.";
    elseif ($written !== null)               $tips[] = "Maintain consistent quiz preparation by reviewing notes before each assessment.";
    if ($performance !== null && $performance < 75) $tips[] = "Invest more effort in performance tasks — practice outputs before submission to improve quality.";
    else                                             $tips[] = "Keep applying strong effort to all performance tasks, as they carry significant grading weight.";
    if ($finalGrade !== null && $finalGrade < 75)    $tips[] = "Allocate dedicated daily study time for {$className} and consult the teacher during available hours.";
    elseif ($finalGrade !== null && $finalGrade >= 90) $tips[] = "Outstanding work! Consider exploring enrichment materials to deepen your mastery of {$className}.";
    else                                             $tips[] = "Stay consistent — small improvements across all components can meaningfully raise your final grade.";

    return [
        'ok'      => true,
        'insight' => [
            'paragraph' => $paragraph,
            'tips'      => array_slice(array_unique($tips), 0, 4),
        ],
    ];
}

// Regenerate-or-serve pipeline shared by the on-demand endpoint (api/ai_insight.php)
// and the proactive queue worker. Persists the new insight, clears the pending flag
// and notifies the student (respecting the 24h notification cooldown).
function generateInsight($studentUid, $classId, $forceRefresh = false) {

    $pdo = getPDO();
    $built = buildInsightSnapshot($pdo, $studentUid, $classId);
    $snapshot = $built['snapshot'];
    $signature = $built['signature'];
    $className = $built['className'];

    $cacheStmt = $pdo->prepare("SELECT insight_paragraph, insight_tips, signature, created_at, pending_refresh, changed_at FROM ai_insights WHERE student_uid = ? AND class_id = ?");
    $cacheStmt->execute([$studentUid, $classId]);
    $cached = $cacheStmt->fetch();

    // Debounce: a grade/attendance change queued a regeneration within the
    // 2-minute window — serve the existing insight (or a pending state) instead
    // of regenerating on demand. The queue worker generates at the end of the
    // window; forceRefresh bypasses this for the student's manual regenerate.
    $DEBOUNCE_SECONDS = 2 * 60;
    if (!$forceRefresh && $cached && (int)$cached['pending_refresh'] === 1 && !empty($cached['changed_at'])) {
        $changedTs = strtotime(substr($cached['changed_at'], 0, 19));
        if ($changedTs !== false) {
            $remaining = $DEBOUNCE_SECONDS - (time() - $changedTs);
            if ($remaining > 0) {
                $hasParagraph = !empty(trim((string)$cached['insight_paragraph']));
                if ($hasParagraph) {
                    return [
                        'ok' => true,
                        'cached' => true,
                        'pending' => true,
                        'pendingSeconds' => $remaining,
                        'insight' => [
                            'paragraph' => $cached['insight_paragraph'],
                            'tips' => $cached['insight_tips'] ? json_decode($cached['insight_tips'], true) : [],
                        ],
                        'analyzedAt' => $cached['created_at'],
                    ];
                }
                return [
                    'ok' => true,
                    'cached' => false,
                    'pending' => true,
                    'pendingSeconds' => $remaining,
                    'insight' => null,
                    'analyzedAt' => null,
                ];
            }
        }
    }

    $CACHE_TTL_SECONDS = 6 * 3600;
    $cachedAge = null;
    if ($cached) {
        $created = strtotime($cached['created_at']);
        $cachedAge = time() - $created;
    }

    if (!$forceRefresh && $cached && $cached['signature'] === $signature && $cachedAge !== null && $cachedAge < $CACHE_TTL_SECONDS) {
        // Data matches the cached insight, so any stale flag is spurious — clear
        // it so the insight can't stay stuck in the "pending/Generating…" state.
        try {
            $pdo->prepare("UPDATE ai_insights SET pending_refresh = 0, changed_at = NULL WHERE student_uid = ? AND class_id = ?")
                ->execute([$studentUid, $classId]);
        } catch (PDOException $e) {
            error_log('clear pending on cache-hit failed: ' . $e->getMessage());
        }

        return [
            'ok' => true,
            'cached' => true,
            'insight' => [
                'paragraph' => $cached['insight_paragraph'],
                'tips' => $cached['insight_tips'] ? json_decode($cached['insight_tips'], true) : [],
            ],
            'analyzedAt' => $cached['created_at'],
        ];
    }

    $ai = callAIInsight(buildInsightPrompt($className, $snapshot));
    if (!$ai['ok']) {
        // ── Local fallback ───────────────────────────────────────────────────
        // External AI failed (bad key, quota, network). Instead of resetting
        // changed_at and spinning forever in "Generating...", generate a
        // data-driven insight locally so pending_refresh is always cleared.
        error_log('AI insight failed (' . ($ai['error'] ?? 'unknown') . ') — using local fallback for ' . $studentUid);
        $ai = buildLocalInsightFallback($snapshot, $className);
    }

    $insight = $ai['insight'];

    $stmt = $pdo->prepare("MERGE ai_insights AS target USING (SELECT ? AS student_uid, ? AS class_id) AS source
        ON target.student_uid = source.student_uid AND target.class_id = source.class_id
        WHEN MATCHED THEN UPDATE SET insight_paragraph = ?, insight_tips = ?, signature = ?, created_at = GETDATE(), pending_refresh = 0, changed_at = NULL
        WHEN NOT MATCHED THEN INSERT (student_uid, class_id, insight_paragraph, insight_tips, signature, pending_refresh, changed_at)
        VALUES (source.student_uid, source.class_id, ?, ?, ?, 0, NULL);");
    $stmt->execute([$studentUid, $classId, $insight['paragraph'], json_encode($insight['tips']), $signature, $insight['paragraph'], json_encode($insight['tips']), $signature]);

    $isNew = $forceRefresh || !$cached || $cached['signature'] !== $signature;
    if ($isNew) {
        $notify = $forceRefresh;
        if (!$forceRefresh) {
            $cooldownStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE recipient_uid = ? AND type = 'ai_insight' AND created_at > DATEADD(HOUR, -24, GETDATE())");
            $cooldownStmt->execute([$studentUid]);
            $notify = (int)$cooldownStmt->fetch()['cnt'] === 0;
        }
        if ($notify) {
            sendNotification(
                $studentUid,
                'ai_insight',
                'New Academic Insight',
                "AI analyzed your performance in {$className}. Open it to see tips.",
                '../student_screen/student_class_view.php?id=' . urlencode($classId)
            );
        }
    }

    return [
        'ok' => true,
        'cached' => false,
        'insight' => $insight,
        'analyzedAt' => date('Y-m-d H:i:s'),
    ];
}

// Drain the insight queue: regenerate every insight whose 2-minute debounce
// window has elapsed. Capped at 3 per pass so one HTTP request never spends
// minutes inside a provider; remaining rows are picked up by the next pass
// (writes, poller). A failed generation keeps the pending flag set for retry.
function processInsightQueue($pdo = null) {
    if (!$pdo) $pdo = getPDO();
    $processed = 0;
    $remaining = 0;
    $due = 0;
    try {
        $dueStmt = $pdo->query("SELECT TOP 3 student_uid, class_id FROM ai_insights WHERE pending_refresh = 1 AND changed_at IS NOT NULL AND changed_at <= DATEADD(MINUTE, -2, GETDATE())");
        $rows = $dueStmt->fetchAll();
        $due = count($rows);
        foreach ($rows as $row) {
            $result = generateInsight($row['student_uid'], $row['class_id']);
            if (!$result['ok']) break;
            $processed++;
            // Quota exhausted: stop trying the remaining rows this pass instead
            // of firing more doomed requests at the API.
            if (($result['error'] ?? '') === 'ai_quota_exceeded') break;
        }
        $remStmt = $pdo->query("SELECT COUNT(*) AS c FROM ai_insights WHERE pending_refresh = 1");
        $remaining = (int)$remStmt->fetch()['c'];
    } catch (PDOException $e) {
        error_log('processInsightQueue failed: ' . $e->getMessage());
    }
    return ['processed' => $processed, 'remaining' => $remaining, 'due' => $due];
}
