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
        foreach ($classes as &$c) {
            $stmt = $pdo->prepare("SELECT student_uid FROM class_students WHERE class_id = ?");
            $stmt->execute([$c['id']]);
            $c['students'] = array_column($stmt->fetchAll(), 'student_uid');
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
        $stmt = $pdo->prepare("SELECT student_uid FROM class_students WHERE class_id = ?");
        $stmt->execute([$classId]);
        $class['students'] = array_column($stmt->fetchAll(), 'student_uid');
        jsonResponse($class);
    }

    $stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_uid = ? ORDER BY created_at DESC");
    $stmt->execute([$teacherUid]);
    $classes = $stmt->fetchAll();

    foreach ($classes as &$c) {
        $stmt = $pdo->prepare("SELECT student_uid FROM class_students WHERE class_id = ?");
        $stmt->execute([$c['id']]);
        $c['students'] = array_column($stmt->fetchAll(), 'student_uid');
    }

    jsonResponse($classes);
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['class_name']) || empty($data['section_code'])) {
        jsonResponse(['error' => 'Missing required fields: class_name, section_code'], 400);
    }

    $id = $data['id'] ?? strtolower(bin2hex(random_bytes(16)));
    $classCode = $data['class_code'] ?? substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
    $timeSlot = $data['time_slot'] ?? '';
    if (empty($timeSlot) && !empty($data['start_time']) && !empty($data['end_time'])) {
        $timeSlot = formatTime($data['start_time']) . ' — ' . formatTime($data['end_time']);
    }

    $stmt = $pdo->prepare("INSERT INTO classes (id, class_name, level, subject, section_code, class_code, schedule, start_time, end_time, time_slot, session_limit, teacher_uid, teacher_name, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())");
    $stmt->execute([
        $id,
        $data['class_name'],
        $data['level'] ?? 'Senior High School',
        $data['subject'] ?? 'Computer Science',
        strtoupper($data['section_code']),
        $classCode,
        $data['schedule'] ?? '',
        $data['start_time'] ?? '',
        $data['end_time'] ?? '',
        $timeSlot,
        (int)($data['session_limit'] ?? 15),
        $uid,
        $data['teacher_name'] ?? 'Faculty Account',
        $data['status'] ?? 'Active'
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

    $allowedFields = ['class_name', 'level', 'subject', 'section_code', 'schedule', 'start_time', 'end_time', 'time_slot', 'session_limit', 'status', 'session_active', 'session_started_at', 'current_nonce'];
    foreach ($allowedFields as $f) {
        if (array_key_exists($f, $data)) {
            $fields[] = "$f = ?";
            $params[] = $data[$f];
        }
    }

    if (empty($fields)) {
        jsonResponse(['error' => 'No valid fields to update'], 400);
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
