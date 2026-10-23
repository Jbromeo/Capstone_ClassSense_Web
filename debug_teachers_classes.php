<?php
require_once __DIR__ . '/api/config.php';
header('Content-Type: text/plain; charset=utf-8');

$pdo = getPDO();

echo "=== TEACHERS IN USERS TABLE ===\n";
$stmt = $pdo->query("SELECT uid, username, first_name, last_name, role FROM users WHERE role = 'teacher'");
$teachers = $stmt->fetchAll();
foreach ($teachers as $t) {
    echo "UID: {$t['uid']} | Username: {$t['username']} | Name: {$t['first_name']} {$t['last_name']}\n";
}

echo "\n=== COLUMNS IN CLASSES TABLE ===\n";
$stmt = $pdo->query("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'classes'");
foreach ($stmt->fetchAll() as $col) {
    echo "{$col['COLUMN_NAME']} ({$col['DATA_TYPE']}, nullable: {$col['IS_NULLABLE']})\n";
}

echo "\n=== EXISTING ROWS IN CLASSES TABLE ===\n";
$stmt = $pdo->query("SELECT * FROM classes");
$classes = $stmt->fetchAll();
echo "Total classes in table: " . count($classes) . "\n";
foreach ($classes as $c) {
    print_r($c);
}
