<?php
/**
 * Script to insert 270 pre-approved students into the database
 * Distribution: 26(60), 25(70), 24(60), 23(40), 22(25), 21(15)
 *
 * Access via: http://localhost/ClassSense/insert_preapproved_students.php
 */

require_once __DIR__ . '/api/config.php';

// Set content type to plain text for better readability
header('Content-Type: text/plain; charset=utf-8');

echo "Starting pre-approved students insertion...\n";
echo "===========================================\n\n";

// Distribution: More students in recent years
$distribution = [
    '26' => 60,  // 2026
    '25' => 70,  // 2025
    '24' => 60,  // 2024
    '23' => 40,  // 2023
    '22' => 25,  // 2022
    '21' => 15   // 2021
];

// Insert into database
try {
    $pdo = getPDO();

    // Get all existing student IDs from database
    echo "Checking existing student IDs in database...\n";
    $stmt = $pdo->query("SELECT student_id FROM pre_approved_students");
    $existingInDb = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $existingCount = count($existingInDb);

    echo "Existing pre-approved students: {$existingCount}\n\n";

    if ($existingCount >= 270) {
        echo "✓ Database already has {$existingCount} pre-approved students (target: 270)\n";
        echo "No action needed.\n";
        exit(0);
    }

    $needed = 270 - $existingCount;
    echo "Need to generate: {$needed} more student IDs\n";
    echo "===========================================\n\n";

// Generate unique student IDs (avoiding existing ones)
$studentIds = [];
$usedIds = $existingInDb; // Start with existing IDs to avoid duplicates

foreach ($distribution as $prefix => $count) {
    // Check how many we already have for this prefix
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pre_approved_students WHERE student_id LIKE ?");
    $stmt->execute([$prefix . '%']);
    $existingForPrefix = (int)$stmt->fetch()['count'];

    $needForPrefix = max(0, $count - $existingForPrefix);

    if ($needForPrefix > 0) {
        echo "Generating {$needForPrefix} student IDs starting with {$prefix}... (already have {$existingForPrefix})\n";

        for ($i = 0; $i < $needForPrefix; $i++) {
            $attempts = 0;
            do {
                // Generate 6 random digits
                $randomPart = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $studentId = $prefix . $randomPart;
                $attempts++;

                if ($attempts > 10000) {
                    throw new Exception("Unable to generate unique ID for prefix {$prefix} after 10000 attempts");
                }
            } while (in_array($studentId, $usedIds));

            $usedIds[] = $studentId;
            $studentIds[] = $studentId;
        }
    } else {
        echo "Prefix {$prefix}: Already have {$existingForPrefix}/{$count} - skipping\n";
    }
}

sort($studentIds);

echo "\nTotal to insert: " . count($studentIds) . " student IDs\n";
echo "===========================================\n\n";

    // Batch insert NEW IDs only
    $insertedCount = 0;
    echo "Inserting new student IDs...\n";

    foreach ($studentIds as $studentId) {
        try {
            $stmt = $pdo->prepare("INSERT INTO pre_approved_students (student_id) VALUES (?)");
            $stmt->execute([$studentId]);
            $insertedCount++;

            if ($insertedCount % 50 == 0) {
                echo "Progress: {$insertedCount}/" . count($studentIds) . " inserted...\n";
                flush();
            }
        } catch (PDOException $e) {
            // Should not happen since we checked existing IDs
            echo "Warning: Failed to insert {$studentId}: {$e->getMessage()}\n";
        }
    }

    echo "\n===========================================\n";
    echo "Insertion completed!\n";
    echo "===========================================\n";
    echo "Successfully inserted: {$insertedCount} student IDs\n";
    echo "\nDistribution breakdown:\n";

    // Verify distribution
    foreach ($distribution as $prefix => $expectedCount) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pre_approved_students WHERE student_id LIKE ?");
        $stmt->execute([$prefix . '%']);
        $actualCount = $stmt->fetch()['count'];
        echo "  {$prefix}xxxxxx: {$actualCount} students (expected: {$expectedCount})\n";
    }

    // Total count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pre_approved_students");
    $totalCount = $stmt->fetch()['count'];
    echo "\nTotal pre-approved students in database: {$totalCount}\n";

    // Sample IDs from each prefix
    echo "\n===========================================\n";
    echo "Sample student IDs:\n";
    echo "===========================================\n";
    foreach ($distribution as $prefix => $count) {
        $stmt = $pdo->prepare("SELECT TOP 3 student_id FROM pre_approved_students WHERE student_id LIKE ? ORDER BY student_id");
        $stmt->execute([$prefix . '%']);
        $samples = $stmt->fetchAll();
        echo "\n{$prefix}xxxxxx examples:\n";
        foreach ($samples as $sample) {
            echo "  - {$sample['student_id']}\n";
        }
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✓ Done! You can now delete this file for security.\n";
echo "\nNOTE: Remember to update the validation in pre_approve_students.php\n";
echo "to enforce exactly 8 digits if needed:\n";
echo "const isValidStudentId = (id) => /^\\d{8}\$/.test(id);\n";
