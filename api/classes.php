<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

if ($method === 'GET') {
    $studentUid = $_GET['student_uid'] ?? null;
    if ($studentUid) {
        if ($studentUid !== $uid) {
            jsonResponse(['error' => 'Unauthorized'], 403);
        }
$stmt = $pdo->prepare("SELECT c.* FROM classes c JOIN class_students cs ON c.id = cs.class_id WHERE cs.student_uid = ? ORDER BY c.created_at DESC");
    $stmt->execute([$studentUid]);
    $classes = $stmt->fetchAll();
    // Only include students that still exist in users (defends against orphan rows)
    $todayAtt = $pdo->prepare("SELECT status FROM attendance WHERE class_id = ? AND student_uid = ? AND date = CONVERT(date, GETDATE())");
    $classAtt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status IN ('Present','Verified') THEN 1 ELSE 0 END) AS present, SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) AS late FROM attendance WHERE class_id = ? AND student_uid = ?");
    foreach ($classes as &$c) {
        $stmt = $pdo->prepare("SELECT cs.student_uid FROM class_students cs JOIN users u ON u.uid = cs.student_uid WHERE cs.class_id = ?");
        $stmt->execute([$c['id']]);
        $c['students'] = array_column($stmt->fetchAll(), 'student_uid');
        // Today's attendance snapshot for the student timeline (student branch only)
        $todayAtt->execute([$c['id'], $studentUid]);
        $attRow = $todayAtt->fetch();
        $c['attendedToday'] = (bool)$attRow;
        $c['todayStatus'] = $attRow ? $attRow['status'] : null;
        // Per-class attendance rate (mirrors student_stats.php, scoped to this class)
        $classAtt->execute([$c['id'], $studentUid]);
        $att = $classAtt->fetch();
        if ($att && (int)$att['total'] > 0) {
            $present = (int)$att['present'] + (int)$att['late'];
            $c['attendanceRate'] = round(($present / (int)$att['total']) * 100);
        } else {
            $c['attendanceRate'] = null;
        }
    }
    jsonResponse($classes);
    }

    $teacherUid = $_GET['teacher_uid'] ?? $uid;
    $classId = $_GET['id'] ?? null;

    if ($classId) {
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        if (!$class) {
            jsonResponse(['error' => 'Class not found'], 404);
        }
        // Authorize: requester must be the teacher or an enrolled student
        if ($class['teacher_uid'] !== $uid) {
            $stmt = $pdo->prepare("SELECT 1 FROM class_students WHERE class_id = ? AND student_uid = ?");
            $stmt->execute([$classId, $uid]);
            if (!$stmt->fetch()) {
                jsonResponse(['error' => 'Class not found'], 404);
            }
        }
        // Only count students that still exist in users (defends against orphan rows)
        $stmt = $pdo->prepare("SELECT cs.student_uid FROM class_students cs JOIN users u ON u.uid = cs.student_uid WHERE cs.class_id = ?");
        $stmt->execute([$classId]);
        $class['students'] = array_column($stmt->fetchAll(), 'student_uid');
        $class['window'] = classWindow($class);
        jsonResponse($class);
    }

    $stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_uid = ? ORDER BY created_at DESC");
    $stmt->execute([$teacherUid]);
    $classes = $stmt->fetchAll();

    foreach ($classes as &$c) {
        // Only count students that still exist in users (defends against orphan rows)
        $stmt = $pdo->prepare("SELECT cs.student_uid FROM class_students cs JOIN users u ON u.uid = cs.student_uid WHERE cs.class_id = ?");
        $stmt->execute([$c['id']]);
        $c['students'] = array_column($stmt->fetchAll(), 'student_uid');
        $c['window'] = classWindow($c);
    }
    jsonResponse($classes);
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['class_name']) || empty($data['section_name'])) {
        jsonResponse(['error' => 'Missing required fields: class_name, section_name'], 400);
    }

$id = $data['id'] ?? strtolower(bin2hex(random_bytes(16)));
    $classCode = $data['class_code'] ?? substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
    $level = $data['level'] ?? 'Senior High School';
    if (!in_array($level, ['Junior High School', 'Senior High School'], true)) {
        jsonResponse(['error' => 'Level must be Junior High School or Senior High School'], 400);
    }
    $timeSlot = $data['time_slot'] ?? '';
    if (empty($timeSlot) && !empty($data['start_time']) && !empty($data['end_time'])) {
        $timeSlot = formatTime($data['start_time']) . ' — ' . formatTime($data['end_time']);
    }

    $stmt = $pdo->prepare("INSERT INTO classes (id, class_name, level, section_name, class_code, schedule, start_time, end_time, time_slot, session_limit, teacher_uid, teacher_name, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())");
    $stmt->execute([
$id,
        $data['class_name'],
        $level,
        $data['section_name'],
        $classCode,
        $data['schedule'] ?? '',
        $data['start_time'] ?? '',
        $data['end_time'] ?? '',
$timeSlot,
        (int)($data['session_limit'] ?? 0),
        $uid,
        $data['teacher_name'] ?? 'Faculty Account',
        $data['status'] ?? 'In Progress'
    ]);

    jsonResponse(['id' => $id, 'class_code' => $classCode], 201);
}

if ($method === 'PUT') {
    $classId = $_GET['id'] ?? null;
    if (!$classId) {
        jsonResponse(['error' => 'Missing class id'], 400);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        jsonResponse(['error' => 'Invalid JSON'], 400);
    }

    $fields = [];
    $params = [];

    // Client inputs are filtered through an allowlist; session expiry/limit are
    // SERVER-controlled (not settable by clients) so a scan window can't be
    // forged or extended remotely.
$allowedFields = ['class_name', 'level', 'section_name', 'schedule', 'start_time', 'end_time', 'time_slot', 'session_limit', 'status', 'session_active', 'session_started_at', 'current_nonce', 'session_expires_at', 'last_nonce', 'nonce_issued_at', 'session_mode', 'require_location', 'session_lat', 'session_lng', 'session_radius_m', 'last_session_id', 'last_session_ended_at'];
   foreach ($allowedFields as $f) {
        if (array_key_exists($f, $data)) {
            if ($f === 'level' && !in_array($data[$f], ['Junior High School', 'Senior High School'], true)) {
                jsonResponse(['error' => 'Level must be Junior High School or Senior High School'], 400);
            }
            $fields[] = "$f = ?";
            $params[] = $data[$f];
        }
    }
    if (empty($fields)) {
        jsonResponse(['error' => 'No valid fields to update'], 400);
    }

    // Load the current row: needed to distinguish a fresh start from a resume.
    // NOTE: this SQL Server rejects comparison operators (`=`,`<`,`IS NULL`) in
    // the SELECT list, so we cannot emit `(session_expires_at IS NULL OR
    // session_expires_at < GETDATE())`. Instead fetch the signed expiry age via
    // DATEDIFF (a function call, which is allowed) and compare it to zero in
    // PHP. DATEDIFF uses GETDATE() on the server, so the PHP/server
    // clock+timezone skew is irrelevant here.
    $cur = $pdo->prepare("SELECT schedule, start_time, end_time, session_active, session_id, session_expires_at, DATEDIFF(SECOND, session_expires_at, GETDATE()) AS expires_age FROM classes WHERE id = ? AND teacher_uid = ?");
    $cur->execute([$classId, $uid]);
    $row = $cur->fetch();
    if (!$row) {
        jsonResponse(['error' => 'Class not found or no changes'], 404);
    }

    // "Fresh start" = client wants active=1 but there's NOT a live session right now.
    // A stale-but-expired session (session_active=1 with a past expiry) counts as
    // NOT live, so a fresh session is minted instead of silently resuming.
    $incomingActive = array_key_exists('session_active', $data) ? (int)$data['session_active'] : null;
    $now = time();
    $curActive      = (int)$row['session_active'];
    $expiresAge     = (int)($row['expires_age'] ?? 0); // >0 => already expired; NULL => treated as not expired
    $currentlyLive  = ($curActive === 1 && $expiresAge <= 0);
    $freshStart     = ($incomingActive === 1 && !$currentlyLive);

    // Remove client-supplied session expiry/limit so the server owns them
    // (prevents a client from forging an extended window).
    $serverOwned = ['session_expires_at', 'session_limit'];
    $cleaned = []; $cleanedParams = [];
    foreach ($fields as $i => $f) {
        $col = preg_replace('/^(.*\S)\s*=\s*\?$/', '$1', $f);
        if (in_array($col, $serverOwned, true)) continue;
        $cleaned[] = $f; $cleanedParams[] = $params[$i];
    }
    $fields = $cleaned; $params = $cleanedParams;

    // Session start: server generates a unique session_id for audit traceability,
    // but ONLY on a genuinely fresh start (active 0->1) so resuming a live session
    // does not mint a new session_id and orphan the audit trail.
    if ($freshStart && empty($data['session_id'])) {
        $fields[] = "session_id = ?";
        $params[] = strtolower(bin2hex(random_bytes(16)));
    }

// Session end: clear the transient session state. Remember the ended
    // session's id/time so the teacher can reopen today's report after reload.
    if ($incomingActive === 0) {
        if (!empty($row['session_id'])) {
            $fields[] = "last_session_id = ?";
            $params[] = $row['session_id'];
            $fields[] = "last_session_ended_at = GETDATE()";
        }
        $fields[] = "session_id = NULL";
        $fields[] = "last_nonce = NULL";
        $fields[] = "nonce_issued_at = NULL";
        $fields[] = "current_nonce = NULL";
        $fields[] = "session_expires_at = NULL";
        $fields[] = "session_mode = 'open'";
        $fields[] = "session_limit = 0";
    }

// Fresh-start + authoritative expiry. Schedule gate removed — teachers can
    // start attendance at any time regardless of the class schedule window.
    // The session has NO auto-expiry server-side: the 30-second late-window
    // auto-end is driven by the teacher UI. The 30-second on-time window is
    // enforced server-side in attendance.php, so scans recorded after it
    // expires are simply marked Late.
    if ($freshStart) {
        if (!array_key_exists('session_started_at', $data)) {
            $fields[] = "session_started_at = GETDATE()";
        }
        $fields[] = "session_expires_at = NULL";
        $fields[] = "session_limit = 0";
    }

    // Late-mode switch: drop the previous nonce so only the late-window QR works.
    // The session stays open until the teacher stops it — no 3-minute cap.
    if (array_key_exists('session_mode', $data) && $data['session_mode'] === 'late') {
        $fields[] = "last_nonce = NULL";
        $fields[] = "session_expires_at = NULL";
    }

    if (array_key_exists('start_time', $data) && array_key_exists('end_time', $data) && !array_key_exists('time_slot', $data)) {
        $fields[] = "time_slot = ?";
        $params[] = formatTime($data['start_time']) . ' — ' . formatTime($data['end_time']);
    }

    $params[] = $classId;
    $params[] = $uid;

    $sql = "UPDATE classes SET " . implode(', ', $fields) . " WHERE id = ? AND teacher_uid = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'Class not found or no changes'], 404);
    }

    jsonResponse(['success' => true]);
}

if ($method === 'DELETE') {
    $classId = $_GET['id'] ?? null;
    if (!$classId) {
        jsonResponse(['error' => 'Missing class id'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ? AND teacher_uid = ?");
    $stmt->execute([$classId, $uid]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'Class not found'], 404);
    }

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);

function formatTime($military) {
    if (!$military) return 'TBA';
    $parts = explode(':', $military);
    if (count($parts) < 2) return $military;
    $hours = (int)$parts[0];
    $minutes = $parts[1];
    $ampm = $hours >= 12 ? 'PM' : 'AM';
    $hours = $hours % 12 ?: 12;
    return "$hours:$minutes $ampm";
}

// Map PHP wday (0=Sun..6=Sat) to the schedule day code used by the UI.
function dayCodeForWday($wday) {
    $map = ['0'=>'SU','1'=>'M','2'=>'T','3'=>'W','4'=>'TH','5'=>'F','6'=>'S'];
    return $map[(string)$wday] ?? 'SU';
}

// Attach the schedule-window info to a class record (GET helpers).
function classWindow($c) {
    return classScheduleWindow($c['schedule'] ?? '', $c['start_time'] ?? '', $c['end_time'] ?? '');
}

// One-char/TH/SU name lookup.
function dayCodeName($code) {
    $names = ['M'=>'Mon','T'=>'Tue','W'=>'Wed','TH'=>'Thu','F'=>'Fri','S'=>'Sat','SU'=>'Sun'];
    return $names[$code] ?? $code;
}

// Split the concatenated schedule string ("MT", "MTH") into tokens,
// recognizing two-char codes (TH, SU) before one-char codes.
function scheduleTokens($schedule) {
    $tokens = [];
    $s = (string)$schedule;
    $j = 0; $len = strlen($s);
    while ($j < $len) {
        $two = substr($s, $j, 2);
        if ($two === 'TH' || $two === 'SU') { $tokens[] = $two; $j += 2; }
        else { $tokens[] = $s[$j]; $j++; }
    }
    return $tokens;
}

// Weekday-name string like "Mon-Tue" for a schedule string.
function dayCodeToName($schedule) {
    $tokens = scheduleTokens($schedule);
    return implode('-', array_map('dayCodeName', $tokens));
}

// Rough human delta for "in 2h 3m" labels.
function humanDelta($from, $to) {
    $diff = max(0, (int)($to - $from));
    $d = (int)floor($diff / 86400);
    $h = (int)floor(($diff % 86400) / 3600);
    $m = (int)floor(($diff % 3600) / 60);
    if ($d > 0) return "{$d}d {$h}h {$m}m";
    if ($h > 0) return "{$h}h {$m}m";
    return "{$m}m";
}

// Compute the schedule window for a class given a reference time (epoch).
// scheduled: class has a schedule+times. startable: within window (incl. 5m lead).
// openNow: alias. closesAt: epoch when an active session auto-expires (window end).
// nextOpenAt/nextOpenLabel: next time it becomes startable (for locked cards).
function classScheduleWindow($schedule, $start_time, $end_time, $now = null) {
    $now = $now ?? time();
    $res = ['scheduled'=>false,'startable'=>false,'openNow'=>false,
            'closesAt'=>null,'nextOpenAt'=>null,'nextOpenLabel'=>'',
            'windowLabel'=>''];

    $schedule = trim((string)$schedule);
    $start_time = trim((string)$start_time);
    $end_time = trim((string)$end_time);
    if ($schedule === '' || $start_time === '' || $end_time === '') {
        return $res; // unconstrained class -> caller treats as always startable
    }

    $tokens = scheduleTokens($schedule);
    if (empty($tokens)) return $res;

    $res['scheduled'] = true;
    $res['windowLabel'] = dayCodeToName($schedule) . ' ' . formatTime($start_time) . ' — ' . formatTime($end_time);

    $todayWday = (int)date('w', $now);
    $todayCode = dayCodeForWday($todayWday);
    $todayDate = date('Y-m-d', $now);

    $startTs = strtotime($todayDate . ' ' . $start_time);
    $endTs   = strtotime($todayDate . ' ' . $end_time);
    if ($endTs <= $startTs) $endTs += 86400; // overnight window
    $unlockTs = $startTs - (5 * 60); // 5-min lead-in before class starts

    if (in_array($todayCode, $tokens, true) && $now >= $unlockTs && $now < $endTs) {
        $res['openNow'] = true;
        $res['startable'] = true;
        $res['closesAt'] = $endTs;
        return $res;
    }

    // Find the next day this class is scheduled.
    for ($i = 1; $i <= 7; $i++) {
        $d = date('Y-m-d', $now + ($i - 1) * 86400);
        $w = (int)date('w', strtotime($d));
        $dc = dayCodeForWday($w);
        if (!in_array($dc, $tokens, true)) continue;
        $s = strtotime($d . ' ' . $start_time);
        $e = strtotime($d . ' ' . $end_time);
        if ($e <= $s) $e += 86400;
        $u = $s - (5 * 60);
        if ($u > $now) {
            $res['nextOpenAt']    = $u;
            $res['nextOpenLabel'] = dayCodeName($dc) . ' ' . formatTime($start_time) . ', in ' . humanDelta($now, $u);
            return $res;
        }
    }
    return $res;
}
