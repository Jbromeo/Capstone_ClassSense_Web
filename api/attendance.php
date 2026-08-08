<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

if ($method === 'GET') {
    $classId = $_GET['class_id'] ?? null;
    $date = $_GET['date'] ?? null;
    $studentUid = $_GET['student_uid'] ?? null;

    // Authorization for read access:
    //  - a student may only read their OWN attendance,
    //  - a teacher may only read attendance for classes they own,
    //  - any enrolled student may read their own records within a class.
    $isAdmin = ($uid && (fetchUserRole($pdo, $uid) === 'admin'));

    if ($classId && $date && $studentUid) {
        if (!$isAdmin && $studentUid !== $uid) {
            // Allow a student to read only their own; teachers must go class-only below
            jsonResponse(['error' => 'Unauthorized'], 403);
        }
        $ok = $isAdmin || ($studentUid === $uid);
        if (!$ok) {
            $t = $pdo->prepare("SELECT teacher_uid FROM classes WHERE id = ?");
            $t->execute([$classId]);
            $c = $t->fetch();
            $ok = $c && $c['teacher_uid'] === $uid;
        }
        if (!$ok) jsonResponse(['error' => 'Unauthorized'], 403);
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE class_id = ? AND date = ? AND student_uid = ?");
        $stmt->execute([$classId, $date, $studentUid]);
        jsonResponse($stmt->fetchAll());
    }

    if ($classId && $date) {
        // Scope by class ownership
        if (!$isAdmin) {
            $t = $pdo->prepare("SELECT teacher_uid FROM classes WHERE id = ?");
            $t->execute([$classId]);
            $c = $t->fetch();
            if (!$c || $c['teacher_uid'] !== $uid) {
                jsonResponse(['error' => 'Unauthorized'], 403);
            }
        }
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE class_id = ? AND date = ?");
        $stmt->execute([$classId, $date]);
        jsonResponse($stmt->fetchAll());
    }

    if ($classId) {
        // Class-wide history: only the owning teacher (or admin). Students read
        // only their own records via the classId+date+studentUid branch above.
        if (!$isAdmin) {
            $t = $pdo->prepare("SELECT teacher_uid FROM classes WHERE id = ?");
            $t->execute([$classId]);
            $c = $t->fetch();
            if (!$c || $c['teacher_uid'] !== $uid) {
                jsonResponse(['error' => 'Unauthorized'], 403);
            }
        }
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE class_id = ? ORDER BY date DESC");
        $stmt->execute([$classId]);
        jsonResponse($stmt->fetchAll());
    }

    jsonResponse(['error' => 'Missing class_id'], 400);
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['class_id'])) {
        jsonResponse(['error' => 'Missing required fields: class_id'], 400);
    }

    $classId = $data['class_id'];
    // Security: the authenticated uid is ALWAYS the recorded student — clients cannot
    // record attendance for other accounts by forging student_uid.
    $studentUid = $uid;
    $nonce = $data['nonce'] ?? null;
    $clientLat = isset($data['lat']) && is_numeric($data['lat']) ? (float)$data['lat'] : null;
    $clientLng = isset($data['lng']) && is_numeric($data['lng']) ? (float)$data['lng'] : null;
    $deviceUuid = $data['device_uuid'] ?? null;
    $isMock = isset($data['is_mock']) ? ((int)$data['is_mock'] === 1 ? 1 : 0) : null;
    $ip = clientIp();

    // 1. Class must exist
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    $class = $stmt->fetch();
    if (!$class) {
        jsonResponse(['error' => 'Class not found'], 404);
    }

    // 2. Session must be active
    if ((int)$class['session_active'] !== 1) {
        jsonResponse(['error' => 'No active attendance session for this class'], 409);
    }

    // 3. Session must not be expired (server-authoritative expiry)
    $nowTs = time();
    if (!empty($class['session_expires_at'])) {
        if ($nowTs > strtotime($class['session_expires_at'])) {
            jsonResponse(['error' => 'The attendance session has ended'], 410);
        }
    } else {
        // Legacy fallback: compute from session_started_at + session_limit
        $limit = (int)$class['session_limit'];
        if ($limit > 0 && !empty($class['session_started_at'])) {
            $expiry = strtotime($class['session_started_at']) + ($limit * 60);
            if ($nowTs > $expiry) {
                jsonResponse(['error' => 'The attendance session has ended'], 410);
            }
        }
    }

    // 4. Nonce must match the live code, or the immediately-previous code
    //    (grace window: previous nonce accepted only within 25s of issue,
    //     so a photo of an old code still fails but a slow camera doesn't)
    $nonceOk = ($class['current_nonce'] !== null && $class['current_nonce'] === $nonce);
    if (!$nonceOk && !empty($class['last_nonce']) && $class['last_nonce'] === $nonce) {
        if (!empty($class['nonce_issued_at']) && ($nowTs - strtotime($class['nonce_issued_at'])) <= 25) {
            $nonceOk = true;
        }
    }
    if (!$nonceOk) {
        jsonResponse(['error' => 'QR code has expired. Scan the current code on the screen.'], 409);
    }

    // 5. Student must be enrolled in the class
    $stmt = $pdo->prepare("SELECT 1 FROM class_students WHERE class_id = ? AND student_uid = ?");
    $stmt->execute([$classId, $studentUid]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'You are not enrolled in this class'], 403);
    }

    // 6. No duplicate scan for the same day
    $date = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT 1 FROM attendance WHERE class_id = ? AND student_uid = ? AND date = ?");
    $stmt->execute([$classId, $studentUid, $date]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Attendance already recorded for today'], 409);
    }

    // 7. Optional GPS geofence (only when the teacher enabled it for this session)
    if ((int)$class['require_location'] === 1) {
        if ($clientLat === null || $clientLng === null || $isMock === 1) {
            jsonResponse(['error' => 'Location is required for this session. Enable GPS and scan again.'], 422);
        }
        $radius = (int)($class['session_radius_m'] ?? 150);
        $distance = haversineMeters(
            (float)$class['session_lat'], (float)$class['session_lng'],
            $clientLat, $clientLng
        );
        if ($distance > $radius) {
            jsonResponse(['error' => 'You are outside the class location'], 422);
        }
    }

    // 8. Status is decided by the SERVER, never by the client:
    //    'Late' during a late-arrivals window, otherwise 'Present'
    $status = ($class['session_mode'] === 'late') ? 'Late' : 'Present';

    // 9. Record it with the audit trail
    $timestamp = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO attendance (student_uid, class_id, date, timestamp, status, ip_address, session_id, lat, lng, device_uuid, is_mock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$studentUid, $classId, $date, $timestamp, $status, $ip, $class['session_id'], $clientLat, $clientLng, $deviceUuid, $isMock]);

    jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'status' => $status], 201);
}

jsonResponse(['error' => 'Method not allowed'], 405);

function clientIp() {
    $fwd = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($fwd !== '') {
        $parts = explode(',', $fwd);
        $ip = trim($parts[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function haversineMeters($lat1, $lng1, $lat2, $lng2) {
    $r = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}
