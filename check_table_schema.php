<?php
/**
 * Check users table schema for created_at column
 * Access via: http://localhost/ClassSense/check_table_schema.php
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Users Table Schema Check\n";
echo "===========================================\n\n";

try {
    $pdo = getPDO();

    // Get column information for users table
    echo "Checking 'created_at' column in users table:\n";
    echo "--------------------------------------------\n";

    $stmt = $pdo->query("
        SELECT
            COLUMN_NAME,
            DATA_TYPE,
            IS_NULLABLE,
            COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'users'
        AND COLUMN_NAME IN ('created_at', 'updated_at')
        ORDER BY COLUMN_NAME
    ");

    $columns = $stmt->fetchAll();

    foreach ($columns as $col) {
        echo "Column: {$col['COLUMN_NAME']}\n";
        echo "  Type: {$col['DATA_TYPE']}\n";
        echo "  Nullable: {$col['IS_NULLABLE']}\n";
        echo "  Default: " . ($col['COLUMN_DEFAULT'] ?: 'NULL') . "\n\n";
    }

    // Check pre_approved_students table too
    echo "\nChecking 'used_at' column in pre_approved_students table:\n";
    echo "---------------------------------------------------------\n";

    $stmt = $pdo->query("
        SELECT
            COLUMN_NAME,
            DATA_TYPE,
            IS_NULLABLE,
            COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'pre_approved_students'
        AND COLUMN_NAME = 'used_at'
    ");

    $cols = $stmt->fetchAll();

    foreach ($cols as $col) {
        echo "Column: {$col['COLUMN_NAME']}\n";
        echo "  Type: {$col['DATA_TYPE']}\n";
        echo "  Nullable: {$col['IS_NULLABLE']}\n";
        echo "  Default: " . ($col['COLUMN_DEFAULT'] ?: 'NULL') . "\n\n";
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
