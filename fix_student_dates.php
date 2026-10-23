<?php
/**
 * Fix student account creation dates
 * Updates existing students to proper distribution without milliseconds
 * Access via: http://localhost/ClassSense/fix_student_dates.php
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Fixing Student Account Creation Dates\n";
echo "===========================================\n\n";

try {
    $pdo = getPDO();

    // Get all students ordered by student_id
    $stmt = $pdo->query("
        SELECT uid, student_id
        FROM users
        WHERE role = 'student'
        ORDER BY student_id ASC
    ");
    $students = $stmt->fetchAll();

    $total = count($students);
    echo "Found {$total} students to update\n";
    echo "Date distribution (with random times 07:00 - 13:00):\n";
    echo "  - 80% ({" . floor($total * 0.80) . "} students) → 2026-10-20\n";
    echo "  - 15% ({" . floor($total * 0.15) . "} students) → 2026-10-21\n";
    echo "  - 5% ({" . ceil($total * 0.05) . "} students) → 2026-10-22\n\n";

    echo "Updating...\n";
    echo "===========================================\n\n";

    $updated = 0;

    foreach ($students as $index => $student) {
        // Calculate percentage
        $percentage = ($index / $total) * 100;

        // Determine date based on distribution
        if ($percentage < 80) {
            $date = '2026-10-20';
        } elseif ($percentage < 95) {
            $date = '2026-10-21';
        } else {
            $date = '2026-10-22';
        }

        // Generate random time between 07:00:00 and 13:00:00 (7 AM to 1 PM)
        $hour = str_pad(rand(7, 13), 2, '0', STR_PAD_LEFT);
        $minute = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
        $second = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
        $createdAt = "{$date} {$hour}:{$minute}:{$second}";

        // Update user created_at
        $stmt = $pdo->prepare("UPDATE users SET created_at = ? WHERE uid = ?");
        $stmt->execute([$createdAt, $student['uid']]);

        // Update pre_approved_students used_at
        $stmt = $pdo->prepare("UPDATE pre_approved_students SET used_at = ? WHERE student_id = ?");
        $stmt->execute([$createdAt, $student['student_id']]);

        $updated++;

        if ($updated % 50 == 0) {
            echo "Progress: {$updated}/{$total} updated...\n";
            flush();
        }
    }

    echo "\n===========================================\n";
    echo "Update completed!\n";
    echo "===========================================\n";
    echo "Total updated: {$updated} students\n\n";

    // Verify the changes
    echo "Verification:\n";
    echo "-------------\n";
    $stmt = $pdo->query("
        SELECT
            CONVERT(VARCHAR, created_at, 120) as date,
            COUNT(*) as count
        FROM users
        WHERE role = 'student'
        GROUP BY CONVERT(VARCHAR, created_at, 120)
        ORDER BY date
    ");
    $verification = $stmt->fetchAll();

    foreach ($verification as $row) {
        $percentage = round(($row['count'] / $total) * 100, 1);
        echo "{$row['date']}: {$row['count']} students ({$percentage}%)\n";
    }

    // Show sample
    echo "\n";
    echo "Sample Students (first 10):\n";
    echo "---------------------------\n";
    $stmt = $pdo->query("
        SELECT TOP 10
            student_id,
            CONCAT(first_name, ' ', last_name) as name,
            CONVERT(VARCHAR, created_at, 120) as created
        FROM users
        WHERE role = 'student'
        ORDER BY student_id
    ");
    $samples = $stmt->fetchAll();

    foreach ($samples as $row) {
        echo "{$row['student_id']} | {$row['name']} | {$row['created']}\n";
    }

    echo "\n✓ All dates updated successfully!\n";
    echo "✓ Format: YYYY-MM-DD HH:MM:SS (no milliseconds)\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n===========================================\n";
echo "✓ Done! You can now delete this file.\n";
