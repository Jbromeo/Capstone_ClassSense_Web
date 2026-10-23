<?php
/**
 * Check recently created student accounts
 * Access via: http://localhost/ClassSense/check_student_dates.php
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Checking Student Account Creation Dates\n";
echo "===========================================\n\n";

try {
    $pdo = getPDO();

    // Check total students
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $total = $stmt->fetch()['count'];
    echo "Total students: {$total}\n\n";

    // Check date distribution
    echo "Date Distribution:\n";
    echo "------------------\n";
    $stmt = $pdo->query("
        SELECT
            CONVERT(DATE, created_at) as date,
            COUNT(*) as count
        FROM users
        WHERE role = 'student'
        GROUP BY CONVERT(DATE, created_at)
        ORDER BY date
    ");
    $dates = $stmt->fetchAll();

    foreach ($dates as $row) {
        $percentage = round(($row['count'] / $total) * 100, 1);
        echo "{$row['date']}: {$row['count']} students ({$percentage}%)\n";
    }

    // Show sample created_at values with full timestamp
    echo "\n";
    echo "Sample Timestamps (first 20 students):\n";
    echo "--------------------------------------\n";
    $stmt = $pdo->query("
        SELECT TOP 20
            student_id,
            CONCAT(last_name, ', ', first_name) as name,
            created_at
        FROM users
        WHERE role = 'student'
        ORDER BY student_id
    ");
    $samples = $stmt->fetchAll();

    foreach ($samples as $row) {
        echo "{$row['student_id']} | {$row['name']} | {$row['created_at']}\n";
    }

    // Check if created_at has time component
    echo "\n";
    echo "Timestamp Format Analysis:\n";
    echo "--------------------------\n";
    $stmt = $pdo->query("
        SELECT TOP 5
            created_at,
            CONVERT(VARCHAR, created_at, 120) as formatted_datetime
        FROM users
        WHERE role = 'student'
        ORDER BY created_at
    ");
    $formats = $stmt->fetchAll();

    foreach ($formats as $row) {
        echo "Raw: {$row['created_at']}\n";
        echo "Formatted: {$row['formatted_datetime']}\n\n";
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
