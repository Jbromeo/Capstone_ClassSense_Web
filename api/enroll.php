<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        jsonResponse(['error' => 'Invalid JSON'], 400);
    }

    // Student joining by class code (open enrollment)
    if (!empty($data['class_code']) && !empty($data['student_uid'])) {
        // A student may only enroll their own account via class code.
        if ($data['student_uid'] !== $uid) {
            jsonResponse(['error' => 'You can only enroll yourself'], 403);
        }

        $stmt = $pdo->prepare("SELECT id, class_name, teacher_uid FROM classes WHERE class_code = ?");
        $stmt->execute([$data['class_code']]);
        $class = $stmt->fetch();

        if (!$class) {
            jsonResponse(['error' => 'Invalid class code'], 404);
        }

        // Verify the target student still exists (FK would otherwise 500)
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE uid = ? AND role = 'student'");
        $stmt->execute([$data['student_uid']]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Student account not found'], 404);
        }

        $classId = $class['id'];
        $className = $class['class_name'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM class_students WHERE class_id = ? AND student_uid = ?");
        $stmt->execute([$classId, $data['student_uid']]);
        if ($stmt->fetch()['cnt'] > 0) {
            jsonResponse(['error' => 'Already enrolled'], 409);
        }

        $stmt = $pdo->prepare("INSERT INTO class_students (class_id, student_uid) VALUES (?, ?)");
        $stmt->execute([$classId, $data['student_uid']]);

        // Notify student
        sendNotification($data['student_uid'], 'enrollment', 'Joined Class',
            "You have successfully enrolled in $className.", null);

        // Notify teacher
        if (!empty($class['teacher_uid'])) {
            $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE uid = ?");
            $stmt->execute([$data['student_uid']]);
            $student = $stmt->fetch();
            $studentName = $student ? trim($student['first_name'] . ' ' . $student['last_name']) : 'A student';
            sendNotification($class['teacher_uid'], 'enrollment', 'New Enrollment',
                "$studentName has enrolled in $className.",
                '../teacher_screen/students.php');
        }

        jsonResponse(['success' => true, 'class_id' => $classId], 201);
    }

    // Teacher enrolling a student by student_uid
    if (!empty($data['class_id']) && !empty($data['student_uid'])) {
        $stmt = $pdo->prepare("SELECT class_name, teacher_uid FROM classes WHERE id = ?");
        $stmt->execute([$data['class_id']]);
        $class = $stmt->fetch();
        if (!$class) {
            jsonResponse(['error' => 'Class not found'], 404);
        }

        // Authorization: only the owning teacher can enroll students into this class
        if ($class['teacher_uid'] !== $uid) {
            jsonResponse(['error' => 'You do not own this class'], 403);
        }

        // Verify the target student still exists
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE uid = ? AND role = 'student'");
        $stmt->execute([$data['student_uid']]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Student account not found'], 404);
        }

        $className = $class['class_name'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM class_students WHERE class_id = ? AND student_uid = ?");
        $stmt->execute([$data['class_id'], $data['student_uid']]);
        if ($stmt->fetch()['cnt'] > 0) {
            jsonResponse(['error' => 'Already enrolled'], 409);
        }

        $stmt = $pdo->prepare("INSERT INTO class_students (class_id, student_uid) VALUES (?, ?)");
        $stmt->execute([$data['class_id'], $data['student_uid']]);

        // Notify student
        sendNotification($data['student_uid'], 'enrollment', 'Enrolled',
            "You have been enrolled in $className.", null);

        jsonResponse(['success' => true], 201);
    }

    jsonResponse(['error' => 'Missing class_code/class_id or student_uid'], 400);
}

if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['class_id']) || empty($data['student_uid'])) {
        jsonResponse(['error' => 'Missing class_id or student_uid'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM class_students WHERE class_id = ? AND student_uid = ?");
    $stmt->execute([$data['class_id'], $data['student_uid']]);

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
