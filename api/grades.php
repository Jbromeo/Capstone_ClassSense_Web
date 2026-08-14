<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

// Ensure the class exists and is owned by the requesting teacher, else 403/404.
function requireClassOwner($pdo, $uid, $classId) {
    $stmt = $pdo->prepare("SELECT teacher_uid FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    $row = $stmt->fetch();
    if (!$row) {
        jsonResponse(['error' => 'Class not found'], 404);
    }
    if ($row['teacher_uid'] !== $uid) {
        jsonResponse(['error' => 'Forbidden: you do not own this class'], 403);
    }
}

// Resolve the owning class of a component and enforce teacher ownership.
function requireComponentOwner($pdo, $uid, $componentId) {
    $stmt = $pdo->prepare("SELECT gc.class_id, c.teacher_uid FROM grade_components gc JOIN classes c ON c.id = gc.class_id WHERE gc.id = ?");
    $stmt->execute([$componentId]);
    $row = $stmt->fetch();
    if (!$row) {
        jsonResponse(['error' => 'Component not found'], 404);
    }
    if ($row['teacher_uid'] !== $uid) {
        jsonResponse(['error' => 'Forbidden: you do not own this class'], 403);
    }
    return $row['class_id'];
}

if ($method === 'GET') {
    $classId = $_GET['class_id'] ?? null;
    $quarter = (int)($_GET['quarter'] ?? 1);

    if (!$classId) {
        jsonResponse(['error' => 'Missing class_id'], 400);
    }

    requireClassOwner($pdo, $uid, $classId);

    $components = [];
    $stmt = $pdo->prepare("SELECT id, category, name, hps, quarter, session_id FROM grade_components WHERE class_id = ? AND quarter = ? ORDER BY category, id");
    $stmt->execute([$classId, $quarter]);
    $components = $stmt->fetchAll();

    $componentIds = array_column($components, 'id');
    $grades = [];
    if (!empty($componentIds)) {
        $placeholders = implode(',', array_fill(0, count($componentIds), '?'));
        $stmt = $pdo->prepare("SELECT component_id, student_uid, score FROM grades WHERE component_id IN ($placeholders)");
        $stmt->execute($componentIds);
        foreach ($stmt->fetchAll() as $g) {
            $grades[$g['component_id']][$g['student_uid']] = $g['score'];
        }
    }

    $weights = [];
    $stmt = $pdo->prepare("SELECT category, weight_percent FROM grade_weights WHERE class_id = ?");
    $stmt->execute([$classId]);
    foreach ($stmt->fetchAll() as $w) {
        $weights[$w['category']] = (int)$w['weight_percent'];
    }

    if (empty($weights)) {
        $weights = ['written' => 0, 'performance' => 0, 'exam' => 0, 'attendance' => 0];
    }

    $classStmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE id = ?");
    $classStmt->execute([$classId]);
    $classData = $classStmt->fetch();

    jsonResponse([
        'class' => $classData,
        'quarter' => $quarter,
        'components' => $components,
        'grades' => $grades,
        'weights' => $weights,
    ]);
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) jsonResponse(['error' => 'Invalid JSON'], 400);

    $action = $data['action'] ?? 'save_score';

    if ($action === 'save_score') {
        $componentId = $data['component_id'] ?? null;
        $studentUid = $data['student_uid'] ?? null;
        $score = $data['score'] ?? null;

        if (!$componentId || !$studentUid) {
            jsonResponse(['error' => 'Missing component_id or student_uid'], 400);
        }

        $classId = requireComponentOwner($pdo, $uid, $componentId);

        $stmt = $pdo->prepare("SELECT 1 FROM class_students WHERE class_id = ? AND student_uid = ?");
        $stmt->execute([$classId, $studentUid]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Student is not enrolled in this class'], 403);
        }

        if ($score === null || $score === '') {
            $stmt = $pdo->prepare("DELETE FROM grades WHERE component_id = ? AND student_uid = ?");
            $stmt->execute([$componentId, $studentUid]);
        } else {
            $stmt = $pdo->prepare("MERGE grades AS target USING (SELECT ? AS component_id, ? AS student_uid) AS source ON target.component_id = source.component_id AND target.student_uid = source.student_uid WHEN MATCHED THEN UPDATE SET score = ?, updated_at = GETDATE() WHEN NOT MATCHED THEN INSERT (component_id, student_uid, score) VALUES (?, ?, ?);");
            $stmt->execute([$componentId, $studentUid, $score, $componentId, $studentUid, $score]);
        }

        jsonResponse(['success' => true]);
    }

    if ($action === 'save_component') {
        $classId = $data['class_id'] ?? null;
        $category = $data['category'] ?? null;
        $name = $data['name'] ?? null;
        $hps = (int)($data['hps'] ?? 50);
        $quarter = (int)($data['quarter'] ?? 1);
        $sessionId = $data['session_id'] ?? null;

        if (!$classId || !$category || !$name) {
            jsonResponse(['error' => 'Missing class_id, category, or name'], 400);
        }

        requireClassOwner($pdo, $uid, $classId);

        // Weights must be configured (total 100%) before any component can be
        // added — otherwise scores would be recorded with no weight to compute
        // a final grade. This also gates the attendance auto-sync.
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(weight_percent), 0) AS total FROM grade_weights WHERE class_id = ?");
        $stmt->execute([$classId]);
        if ((int)$stmt->fetch()['total'] !== 100) {
            jsonResponse(['error' => 'Set grading weights (total 100%) before adding components'], 400);
        }

        // Idempotent per day: an Attendance component is named M/D/YY (one
        // column per day), so the same (class, category, name) is reused instead
        // of duplicated when a session reopens and syncs again. The component's
        // session_id is refreshed to the latest run for reference.
        if ($category === 'attendance') {
            $stmt = $pdo->prepare("SELECT id, category, name, hps, quarter FROM grade_components WHERE class_id = ? AND category = ? AND name = ?");
            $stmt->execute([$classId, $category, $name]);
            $existing = $stmt->fetch();
            if ($existing) {
                if ($sessionId) {
                    $upd = $pdo->prepare("UPDATE grade_components SET session_id = ? WHERE id = ?");
                    $upd->execute([$sessionId, $existing['id']]);
                }
                jsonResponse(['success' => true, 'component' => ['id' => (int)$existing['id'], 'category' => $existing['category'], 'name' => $existing['name'], 'hps' => (int)$existing['hps'], 'quarter' => (int)$existing['quarter']]]);
            }
        }

        $stmt = $pdo->prepare("INSERT INTO grade_components (class_id, category, name, hps, quarter, session_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$classId, $category, $name, $hps, $quarter, $sessionId]);
        $id = $pdo->lastInsertId();

        jsonResponse(['success' => true, 'component' => ['id' => (int)$id, 'category' => $category, 'name' => $name, 'hps' => $hps, 'quarter' => $quarter]], 201);
    }

    if ($action === 'save_weights') {
        $classId = $data['class_id'] ?? null;
        $weights = $data['weights'] ?? null;

        if (!$classId || !$weights) {
            jsonResponse(['error' => 'Missing class_id or weights'], 400);
        }

        requireClassOwner($pdo, $uid, $classId);

        $allowed = ['written', 'performance', 'exam', 'attendance'];
        $total = 0;
        foreach ($allowed as $cat) {
            if (isset($weights[$cat])) {
                $total += (int)$weights[$cat];
            }
        }
        if ($total !== 100) {
            jsonResponse(['error' => 'Weights must total 100%'], 400);
        }

        $stmt = $pdo->prepare("MERGE grade_weights AS target USING (SELECT ? AS class_id, ? AS category, ? AS weight_percent) AS source ON target.class_id = source.class_id AND target.category = source.category WHEN MATCHED THEN UPDATE SET weight_percent = source.weight_percent WHEN NOT MATCHED THEN INSERT (class_id, category, weight_percent) VALUES (source.class_id, source.category, source.weight_percent);");
        foreach ($allowed as $cat) {
            if (isset($weights[$cat])) {
                $stmt->execute([$classId, $cat, (int)$weights[$cat]]);
            }
        }

        jsonResponse(['success' => true]);
    }

    if ($action === 'save_bulk') {
        $classId = $data['class_id'] ?? null;
        $quarter = (int)($data['quarter'] ?? 1);
        $rows = $data['rows'] ?? [];

        if (!$classId || empty($rows)) {
            jsonResponse(['error' => 'Missing class_id or rows'], 400);
        }

        requireClassOwner($pdo, $uid, $classId);

        $stmt = $pdo->prepare("MERGE grades AS target USING (SELECT ? AS component_id, ? AS student_uid, ? AS score) AS source ON target.component_id = source.component_id AND target.student_uid = source.student_uid WHEN MATCHED THEN UPDATE SET score = source.score, updated_at = GETDATE() WHEN NOT MATCHED THEN INSERT (component_id, student_uid, score) VALUES (source.component_id, source.student_uid, source.score);");
        foreach ($rows as $r) {
            if (!empty($r['component_id']) && !empty($r['student_uid']) && isset($r['score'])) {
                $score = $r['score'] === '' || $r['score'] === null ? null : (float)$r['score'];
                if ($score === null) {
                    $del = $pdo->prepare("DELETE FROM grades WHERE component_id = ? AND student_uid = ?");
                    $del->execute([$r['component_id'], $r['student_uid']]);
                } else {
                    $stmt->execute([$r['component_id'], $r['student_uid'], $score]);
                }
            }
        }

        jsonResponse(['success' => true]);
    }

    jsonResponse(['error' => 'Unknown action'], 400);
}

if ($method === 'DELETE') {
    $componentId = $_GET['component_id'] ?? null;

    // Session-scoped delete: remove the attendance component(s) belonging to a
    // single session (used by "Discard All Records" so only that session's
    // grading column is removed, not the whole day's).
    $classId = $_GET['class_id'] ?? null;
    $category = $_GET['category'] ?? null;
    $sessionId = $_GET['session_id'] ?? null;

    if ($classId && $category && $sessionId) {
        requireClassOwner($pdo, $uid, $classId);
        $stmt = $pdo->prepare("DELETE FROM grade_components WHERE class_id = ? AND category = ? AND session_id = ?");
        $stmt->execute([$classId, $category, $sessionId]);
        jsonResponse(['success' => true, 'deleted' => $stmt->rowCount()]);
    }

    if (!$componentId) jsonResponse(['error' => 'Missing component_id'], 400);

    requireComponentOwner($pdo, $uid, $componentId);

    $stmt = $pdo->prepare("DELETE FROM grade_components WHERE id = ?");
    $stmt->execute([$componentId]);

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);