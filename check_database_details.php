<?php
/**
 * Detailed database check with ID validation
 * Access via: http://localhost/ClassSense/check_database_details.php
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Detailed Database Analysis\n";
echo "===========================================\n\n";

try {
    $pdo = getPDO();

    // Check pre_approved_students table
    echo "PRE_APPROVED_STUDENTS TABLE:\n";
    echo "----------------------------\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pre_approved_students");
    $preapprovedTotal = $stmt->fetch()['count'];
    echo "Total rows: {$preapprovedTotal}\n";

    // Check for 8-digit student IDs only
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pre_approved_students WHERE LEN(student_id) = 8");
    $valid8Digit = $stmt->fetch()['count'];
    echo "Valid 8-digit IDs: {$valid8Digit}\n";

    // Check for invalid IDs (not 8 digits)
    $stmt = $pdo->query("SELECT student_id FROM pre_approved_students WHERE LEN(student_id) != 8");
    $invalidIds = $stmt->fetchAll();
    if (count($invalidIds) > 0) {
        echo "\n⚠ INVALID IDs (not 8 digits): " . count($invalidIds) . "\n";
        foreach ($invalidIds as $row) {
            echo "  - '{$row['student_id']}' (length: " . strlen($row['student_id']) . ")\n";
        }
    }

    // Available vs Used (8-digit only)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pre_approved_students WHERE used_at IS NULL AND LEN(student_id) = 8");
    $available = $stmt->fetch()['count'];
    $used = $valid8Digit - $available;
    echo "\nStatus (8-digit IDs only):\n";
    echo "  Available: {$available}\n";
    echo "  Used: {$used}\n";

    // Check users table
    echo "\n";
    echo "USERS TABLE:\n";
    echo "------------\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $studentsCount = $stmt->fetch()['count'];
    echo "Total students registered: {$studentsCount}\n";

    // Check for students with 8-digit IDs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND LEN(student_id) = 8");
    $students8Digit = $stmt->fetch()['count'];
    echo "Students with 8-digit IDs: {$students8Digit}\n";

    // Check for students with invalid IDs
    $stmt = $pdo->query("SELECT student_id, username, first_name, last_name FROM users WHERE role = 'student' AND LEN(student_id) != 8");
    $invalidStudents = $stmt->fetchAll();
    if (count($invalidStudents) > 0) {
        echo "\n⚠ Students with invalid IDs: " . count($invalidStudents) . "\n";
        foreach (array_slice($invalidStudents, 0, 10) as $row) {
            echo "  - ID: '{$row['student_id']}' | {$row['first_name']} {$row['last_name']} | @{$row['username']}\n";
        }
        if (count($invalidStudents) > 10) {
            echo "  ... and " . (count($invalidStudents) - 10) . " more\n";
        }
    }

    // Sample 8-digit IDs from pre_approved_students
    echo "\n";
    echo "SAMPLE 8-DIGIT PRE-APPROVED IDs:\n";
    echo "---------------------------------\n";
    $stmt = $pdo->query("SELECT TOP 20 student_id, used_at FROM pre_approved_students WHERE LEN(student_id) = 8 ORDER BY student_id");
    $samples = $stmt->fetchAll();
    foreach ($samples as $sample) {
        $status = $sample['used_at'] ? '(USED)' : '(AVAILABLE)';
        echo "{$sample['student_id']} {$status}\n";
    }

    // Summary
    echo "\n";
    echo "===========================================\n";
    echo "SUMMARY:\n";
    echo "===========================================\n";
    echo "Pre-approved IDs (8-digit): {$valid8Digit}/270\n";
    echo "Available for registration: {$available}\n";
    echo "Students registered: {$studentsCount}\n";

    if ($valid8Digit < 270) {
        $needed = 270 - $valid8Digit;
        echo "\n⚠ ACTION NEEDED: Generate {$needed} more valid 8-digit IDs\n";
    } else {
        echo "\n✓ You have enough 8-digit pre-approved IDs\n";
    }

    // Recommendation
    if (count($invalidIds) > 0) {
        echo "\n⚠ RECOMMENDATION: Clean up invalid IDs from database\n";
        echo "   Run cleanup script to remove IDs that are not 8 digits\n";
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
