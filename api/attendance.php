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
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE class_id = ? AND CAST(date AS DATE) = ? AND student_uid = ?");
        $stmt->execute([$classId, $date, $studentUid]);
        jsonResponse($stmt->fetchAll());
    }

    // Student-specific attendance history (no class_id supplied):
    //  - a student may read their OWN records,
    //  - a teacher may read a student enrolled in one of their classes,
    //  - an admin may read any student's records.
    if ($studentUid && !$classId) {
        if ($studentUid !== $uid && !$isAdmin) {
            if (!$uid) {
                jsonResponse(['error' => 'Unauthorized'], 403);
            }
            $chk = $pdo->prepare("SELECT 1 FROM class_students cs JOIN classes c ON cs.class_id = c.id WHERE cs.student_uid = ? AND c.teacher_uid = ?");
            $chk->execute([$studentUid, $uid]);
            if (!$chk->fetch()) {
                jsonResponse(['error' => 'Unauthorized'], 403);
            }
        }
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_uid = ? ORDER BY date DESC");
        $stmt->execute([$studentUid]);
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
        $sessionId = $_GET['session_id'] ?? null;
        if ($sessionId) {
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE class_id = ? AND CAST(date AS DATE) = ? AND session_id = ? ORDER BY timestamp ASC");
            $stmt->execute([$classId, $date, $sessionId]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE class_id = ? AND CAST(date AS DATE) = ? ORDER BY timestamp ASC");
            $stmt->execute([$classId, $date]);
        }
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

    // --- Teacher manual marking (teacher sets Present/Late/Absent for any enrolled student) ---
    // Bypasses QR/nonce/session checks; authorized only to the owning teacher (or admin).
    // Accepts a single student (student_uid + status) or a bulk list
    // (students: [{ student_uid, status }, ...]) so session-end absence for an
    // entire roster can be persisted in one round-trip.
    if (!empty($data['manual'])) {
        $manualDate = $data['date'] ?? date('Y-m-d');
        $entries = [];
        if (!empty($data['students']) && is_array($data['students'])) {
            foreach ($data['students'] as $s) {
                $suid = $s['student_uid'] ?? null;
                $sstatus = $s['status'] ?? null;
                if ($suid && in_array($sstatus, ['Present', 'Late', 'Absent'], true)) {
                    $entries[] = ['student_uid' => $suid, 'status' => $sstatus];
                }
            }
        } else {
            $manualStudentUid = $data['student_uid'] ?? null;
            $manualStatus = $data['status'] ?? null;
            if ($manualStudentUid && in_array($manualStatus, ['Present', 'Late', 'Absent'], true)) {
                $entries[] = ['student_uid' => $manualStudentUid, 'status' => $manualStatus];
            }
        }
        if (!$entries) {
            jsonResponse(['error' => 'manual requires student_uid and a valid status (Present/Late/Absent)'], 400);
        }

        $isAdmin = ($uid && fetchUserRole($pdo, $uid) === 'admin');
        $stmt = $pdo->prepare("SELECT teacher_uid FROM classes WHERE id = ?");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        if (!$class) {
            jsonResponse(['error' => 'Class not found'], 404);
        }
        if (!$isAdmin && $class['teacher_uid'] !== $uid) {
            jsonResponse(['error' => 'Unauthorized'], 403);
        }

        // Bulk session-end sync (students[] array) must NEVER overwrite a QR
        // scan: marking a whole roster Absent must not clobber a student who
        // already scanned Present/Late today. Single-student updates (the
        // history picker) are deliberate corrections and still allowed.
        $isBulk = !empty($data['students']);

        // UPSERT: if the student already has a record for this class/date, only
        // the status changes — the scan's audit trail (ip, device, location,
        // suspicious flag) is preserved. Otherwise insert a fresh manual record.
        // Resolve the session_id for this class (last ended session) so manual
        // absence rows reference the correct session in the registry.
        // Client may supply session_id explicitly (JS captures it before the PUT
        // clears it), which takes priority over the server-resolved value.
        $clientSessionId = $data['session_id'] ?? null;
        $sessionIdRow = $pdo->prepare("SELECT last_session_id, session_id FROM classes WHERE id = ?");
        $sessionIdRow->execute([$classId]);
        $sessionIdData = $sessionIdRow->fetch();
        $serverSessionId = $sessionIdData ? ($sessionIdData['last_session_id'] ?? $sessionIdData['session_id'] ?? null) : null;
        $resolvedSessionId = $clientSessionId ?? $serverSessionId;

        $updated = 0;
        $inserted = 0;
        $lastId = null;
        $lastStatus = null;
        $existing = $pdo->prepare("SELECT id, session_id, status FROM attendance WHERE class_id = ? AND student_uid = ? AND CAST(date AS DATE) = ?");
        $upd = $pdo->prepare("UPDATE attendance SET status = ? WHERE id = ?");
        $ip = clientIp();
        $ins = $pdo->prepare("INSERT INTO attendance (student_uid, class_id, date, timestamp, status, ip_address, session_id, lat, lng, device_uuid, is_mock, distance_m, is_suspicious) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($entries as $entry) {
            $existing->execute([$classId, $entry['student_uid'], $manualDate]);
            $row = $existing->fetch();
            if ($row) {
                // Never let a bulk sync flip an existing scan to Absent.
                if ($isBulk && $entry['status'] === 'Absent' && in_array($row['status'], ['Present', 'Late'], true)) {
                    continue;
                }
                // Preserve the existing session_id if already set (QR scan row);
                // for new manual rows (Absent) attach the resolved session_id.
                $upd->execute([$entry['status'], $row['id']]);
                $updated++;
                $lastId = (int)$row['id'];
            } else {
                $ins->execute([$entry['student_uid'], $classId, $manualDate, date('Y-m-d H:i:s'), $entry['status'], $ip, $resolvedSessionId, null, null, null, null, null, 0]);
                $inserted++;
                $lastId = (int)$pdo->lastInsertId();
            }
            $lastStatus = $entry['status'];
        }

        jsonResponse(['success' => true, 'id' => $lastId, 'status' => $lastStatus, 'updated' => $updated, 'inserted' => $inserted], 201);
    }

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
    $stmt = $pdo->prepare("SELECT 1 FROM attendance WHERE class_id = ? AND student_uid = ? AND CAST(date AS DATE) = ?");
    $stmt->execute([$classId, $studentUid, $date]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Attendance already recorded for today'], 409);
    }

    // 7. Optional GPS geofence (only when the teacher enabled it for this session).
    //    Missing/spoofed location is still rejected — the phone must supply a real
    //    fix. But a scan that IS supplied yet falls outside the radius is accepted
    //    and flagged SUSPICIOUS so the teacher can review it in the live feed.
    $distanceM = null;
    $isSuspicious = 0;
    if ((int)$class['require_location'] === 1) {
        if ($clientLat === null || $clientLng === null || $isMock === 1) {
            jsonResponse(['error' => 'Location is required for this session. Enable GPS and scan again.'], 422);
        }
        $radius = (int)($class['session_radius_m'] ?? 150);
        $distanceM = round(haversineMeters(
            (float)$class['session_lat'], (float)$class['session_lng'],
            $clientLat, $clientLng
        ), 2);
        $isSuspicious = ($distanceM > $radius) ? 1 : 0;
    }

    // 8. Status is decided by the SERVER, never by the client:
    //    'Late' after the 30-second on-time window (or when session_mode was
    //    flipped to 'late'), otherwise 'Present'. A scan that arrives after
    //    the on-time window is recorded as Late even if the teacher's
    //    page has been refreshed or closed.
    $isLate = ($class['session_mode'] === 'late');
    if (!$isLate && !empty($class['session_started_at'])) {
        $isLate = ($nowTs - strtotime($class['session_started_at'])) >= 30; // 30 sec
    }
    $status = $isLate ? 'Late' : 'Present';

    // 9. Record it with the audit trail
    $timestamp = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO attendance (student_uid, class_id, date, timestamp, status, ip_address, session_id, lat, lng, device_uuid, is_mock, distance_m, is_suspicious) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$studentUid, $classId, $date, $timestamp, $status, $ip, $class['session_id'], $clientLat, $clientLng, $deviceUuid, $isMock, $distanceM, $isSuspicious]);

    jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'status' => $status, 'distance_m' => $distanceM, 'is_suspicious' => $isSuspicious], 201);
}

if ($method === 'DELETE') {
    // Discard attendance records (wrong scan / fraud correction).
    // A single record when student_uid is given; ALL records for the
    // class/date when student_uid is omitted. When session_id is supplied the
    // deletion is scoped to that session only, so discarding one session never
    // wipes another session's records from the same day.
    // Only the owning teacher (or an admin) may discard records.
    $classId = $_GET['class_id'] ?? null;
    $studentUid = $_GET['student_uid'] ?? null;
    $sessionId = $_GET['session_id'] ?? null;
    if (!$classId) {
        jsonResponse(['error' => 'Missing class_id'], 400);
    }

    $isAdmin = ($uid && (fetchUserRole($pdo, $uid) === 'admin'));
    if (!$isAdmin) {
        $t = $pdo->prepare("SELECT teacher_uid FROM classes WHERE id = ?");
        $t->execute([$classId]);
        $c = $t->fetch();
        if (!$c || $c['teacher_uid'] !== $uid) {
            jsonResponse(['error' => 'Unauthorized'], 403);
        }
    }

    $date = $_GET['date'] ?? date('Y-m-d');
    if ($sessionId) {
        if ($studentUid) {
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE class_id = ? AND student_uid = ? AND session_id = ?");
            $stmt->execute([$classId, $studentUid, $sessionId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE class_id = ? AND session_id = ?");
            $stmt->execute([$classId, $sessionId]);
        }
    } elseif ($studentUid) {
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE class_id = ? AND student_uid = ? AND CAST(date AS DATE) = ?");
        $stmt->execute([$classId, $studentUid, $date]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE class_id = ? AND CAST(date AS DATE) = ?");
        $stmt->execute([$classId, $date]);
    }

    jsonResponse(['success' => true, 'deleted' => $stmt->rowCount()]);
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
