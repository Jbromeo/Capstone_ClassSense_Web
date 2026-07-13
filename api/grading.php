<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$uid = verifyToken();
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

if ($method === 'GET') {
    $classId = $_GET['class_id'] ?? null;
    $quarter = $_GET['quarter'] ?? null;

    if (!$classId || !$quarter) {
        jsonResponse(['error' => 'Missing class_id or quarter'], 400);
    }

    // Build config from grading_categories + grading_components
    $config = [];

    $stmt = $pdo->prepare("SELECT * FROM grading_categories WHERE class_id = ? AND quarter = ?");
    $stmt->execute([$classId, $quarter]);
    $categories = $stmt->fetchAll();

    foreach ($categories as $cat) {
        $key = $cat['category_key'];
        $config[$key] = ['weight' => (int)$cat['weight'], 'items' => []];
    }

    // Ensure all 4 keys exist even if not yet saved
    foreach (['written', 'performance', 'exam', 'attendance'] as $key) {
        if (!isset($config[$key])) {
            $config[$key] = ['weight' => 0, 'items' => []];
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM grading_components WHERE class_id = ? AND quarter = ?");
    $stmt->execute([$classId, $quarter]);
    $components = $stmt->fetchAll();

    foreach ($components as $comp) {
        $config[$comp['category_key']]['items'][] = [
            'id' => $comp['id'],
            'name' => $comp['name'],
            'hps' => (int)$comp['hps']
        ];
    }

    // Build scores from student_scores
    $scores = [];

    $stmt = $pdo->prepare("
        SELECT ss.student_uid, ss.component_id, ss.score
        FROM student_scores ss
        JOIN grading_components gc ON ss.component_id = gc.id
        WHERE gc.class_id = ? AND gc.quarter = ?
    ");
    $stmt->execute([$classId, $quarter]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $sUid = $row['student_uid'];
        $cId = $row['component_id'];
        if (!isset($scores[$sUid])) $scores[$sUid] = [];
        $scores[$sUid][$cId] = $row['score'] !== null ? (float)$row['score'] : null;
    }

    jsonResponse(['config' => $config, 'scores' => $scores]);
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['class_id']) || empty($data['quarter'])) {
        jsonResponse(['error' => 'Missing class_id or quarter'], 400);
    }

    $classId = $data['class_id'];
    $quarter = $data['quarter'];
    $config = $data['config'] ?? [];
    $pdo->beginTransaction();

    try {
        // Replace categories: delete old, insert new
        $stmt = $pdo->prepare("DELETE FROM grading_categories WHERE class_id = ? AND quarter = ?");
        $stmt->execute([$classId, $quarter]);

        foreach (['written', 'performance', 'exam', 'attendance'] as $key) {
            $weight = isset($config[$key]['weight']) ? (int)$config[$key]['weight'] : 0;
            $stmt = $pdo->prepare("INSERT INTO grading_categories (class_id, quarter, category_key, weight) VALUES (?, ?, ?, ?)");
            $stmt->execute([$classId, $quarter, $key, $weight]);
        }

        // Replace components: delete old (cascades to student_scores), insert new
        $stmt = $pdo->prepare("DELETE FROM grading_components WHERE class_id = ? AND quarter = ?");
        $stmt->execute([$classId, $quarter]);

        foreach (['written', 'performance', 'exam', 'attendance'] as $key) {
            $items = $config[$key]['items'] ?? [];
            foreach ($items as $item) {
                $itemId = $item['id'];
                $name = $item['name'];
                $hps = (int)($item['hps'] ?? 0);
                $stmt = $pdo->prepare("INSERT INTO grading_components (id, class_id, quarter, category_key, name, hps) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$itemId, $classId, $quarter, $key, $name, $hps]);
            }
        }

        // Insert scores
        $scores = $data['scores'] ?? [];
        foreach ($scores as $studentUid => $items) {
            foreach ($items as $componentId => $score) {
                if ($score === null || $score === '') continue;
                $stmt = $pdo->prepare("INSERT INTO student_scores (component_id, student_uid, score) VALUES (?, ?, ?)");
                $stmt->execute([$componentId, $studentUid, (float)$score]);
            }
        }

        $pdo->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Save failed: ' . $e->getMessage()], 500);
    }
}

if ($method === 'DELETE') {
    $classId = $_GET['class_id'] ?? null;
    $quarter = $_GET['quarter'] ?? null;
    if (!$classId || !$quarter) {
        jsonResponse(['error' => 'Missing class_id or quarter'], 400);
    }

    $pdo->beginTransaction();
    try {
        // Delete scores via components cascade
        $stmt = $pdo->prepare("DELETE FROM grading_components WHERE class_id = ? AND quarter = ?");
        $stmt->execute([$classId, $quarter]);

        $stmt = $pdo->prepare("DELETE FROM grading_categories WHERE class_id = ? AND quarter = ?");
        $stmt->execute([$classId, $quarter]);

        $pdo->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Delete failed: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
