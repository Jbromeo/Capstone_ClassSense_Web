<?php
/**
 * Check pre_approved_students table status
 * Access via: http://localhost/ClassSense/check_preapproved.php
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Checking pre_approved_students table...\n";
echo "===========================================\n\n";

try {
    $pdo = getPDO();

    // Total count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pre_approved_students");
    $total = $stmt->fetch()['count'];
    echo "Total pre-approved students: {$total}\n\n";

    // Count by prefix
    echo "Distribution by year:\n";
    echo "---------------------\n";
    $prefixes = ['26', '25', '24', '23', '22', '21'];

    foreach ($prefixes as $prefix) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pre_approved_students WHERE student_id LIKE ?");
        $stmt->execute([$prefix . '%']);
        $count = $stmt->fetch()['count'];
        echo "{$prefix}xxxxxx: {$count} students\n";
    }

    // Count used vs available
    echo "\n";
    echo "Status breakdown:\n";
    echo "-----------------\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pre_approved_students WHERE used_at IS NULL");
    $available = $stmt->fetch()['count'];
    $used = $total - $available;
    echo "Available (unused): {$available}\n";
    echo "Used (registered): {$used}\n";

    // Sample IDs
    echo "\n";
    echo "Sample student IDs:\n";
    echo "-------------------\n";
    $stmt = $pdo->query("SELECT TOP 10 student_id, used_at FROM pre_approved_students ORDER BY student_id");
    $samples = $stmt->fetchAll();
    foreach ($samples as $sample) {
        $status = $sample['used_at'] ? '(USED)' : '(AVAILABLE)';
        echo "{$sample['student_id']} {$status}\n";
    }

    // Check if we need to generate more
    echo "\n";
    echo "===========================================\n";
    if ($total < 270) {
        $needed = 270 - $total;
        echo "⚠ Need {$needed} more student IDs to reach 270\n";
    } else if ($total == 270) {
        echo "✓ Target reached: 270 student IDs\n";
    } else {
        echo "✓ Have {$total} student IDs (exceeds target of 270)\n";
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
