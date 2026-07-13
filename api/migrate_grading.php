<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/config.php';

$pdo = getPDO();

echo "Migrating grading_sheets to normalized tables...\n\n";

// Check if old table exists
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM sysobjects WHERE name='grading_sheets' AND xtype='U'");
if ($stmt->fetch()['cnt'] == 0) {
    echo "No grading_sheets table found. Nothing to migrate.\n";
    exit;
}

// Check if new tables exist
foreach (['grading_categories', 'grading_components', 'student_scores'] as $tbl) {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM sysobjects WHERE name='$tbl' AND xtype='U'");
    if ($stmt->fetch()['cnt'] == 0) {
        echo "ERROR: Table $tbl does not exist. Run setup.php first.\n";
        exit;
    }
}

// Check if there's data in grading_sheets
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM grading_sheets");
$count = (int)$stmt->fetch()['cnt'];
echo "Found $count grading_sheets rows to migrate.\n";

if ($count == 0) {
    echo "Nothing to migrate.\n";
    exit;
}

$stmt = $pdo->query("SELECT * FROM grading_sheets");
$sheets = $stmt->fetchAll();
$migrated = 0;

$pdo->beginTransaction();

try {
    foreach ($sheets as $sheet) {
        $classId = $sheet['class_id'];
        $quarter = $sheet['quarter'];
        $config = json_decode($sheet['config_json'], true);
        $scores = json_decode($sheet['scores_json'], true);

        if (!$config) {
            echo "SKIP class=$classId quarter=$quarter (no config)\n";
            continue;
        }

        // Insert categories
        foreach (['written', 'performance', 'exam', 'attendance'] as $key) {
            $weight = isset($config[$key]['weight']) ? (int)$config[$key]['weight'] : 0;
            $stmtIns = $pdo->prepare("
                IF NOT EXISTS (SELECT 1 FROM grading_categories WHERE class_id=? AND quarter=? AND category_key=?)
                INSERT INTO grading_categories (class_id, quarter, category_key, weight) VALUES (?, ?, ?, ?)
            ");
            $stmtIns->execute([$classId, $quarter, $key, $classId, $quarter, $key, $weight]);
        }

        // Insert components
        foreach (['written', 'performance', 'exam', 'attendance'] as $key) {
            $items = $config[$key]['items'] ?? [];
            foreach ($items as $item) {
                $itemId = $item['id'];
                $name = $item['name'];
                $hps = (int)($item['hps'] ?? 0);
                $stmtIns = $pdo->prepare("
                    IF NOT EXISTS (SELECT 1 FROM grading_components WHERE id=?)
                    INSERT INTO grading_components (id, class_id, quarter, category_key, name, hps) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmtIns->execute([$itemId, $itemId, $classId, $quarter, $key, $name, $hps]);
            }
        }

        // Insert scores
        if ($scores) {
            foreach ($scores as $studentUid => $items) {
                foreach ($items as $componentId => $score) {
                    if ($score === null) continue;
                    // Only insert if component exists in grading_components
                    $chk = $pdo->prepare("SELECT 1 FROM grading_components WHERE id=?");
                    $chk->execute([$componentId]);
                    if (!$chk->fetch()) continue;

                    $stmtIns = $pdo->prepare("
                        IF NOT EXISTS (SELECT 1 FROM student_scores WHERE component_id=? AND student_uid=?)
                        INSERT INTO student_scores (component_id, student_uid, score) VALUES (?, ?, ?)
                    ");
                    $stmtIns->execute([$componentId, $studentUid, $componentId, $studentUid, (float)$score]);
                }
            }
        }

        $migrated++;
        echo "OK class=$classId quarter=$quarter\n";
    }

    $pdo->commit();
    echo "\nMigration complete. $migrated rows migrated.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
