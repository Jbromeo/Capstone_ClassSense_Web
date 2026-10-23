<?php
/**
 * Cleanup script - Deletes all student accounts and resets pre-approved IDs
 * Access via: http://localhost/ClassSense/cleanup_students.php
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Student Accounts Cleanup Script\n";
echo "===========================================\n\n";

echo "⚠️  WARNING: This will delete ALL student accounts!\n";
echo "    Press Ctrl+C to cancel, or wait 3 seconds...\n\n";

// Simple countdown (won't work in web context, but good for CLI)
// In web, just proceed with confirmation message
sleep(1);

try {
    $pdo = getPDO();

    // Start transaction
    $pdo->beginTransaction();

    // Count current students
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $studentCount = $stmt->fetch()['count'];

    echo "Found {$studentCount} student accounts to delete\n";
    echo "Processing...\n\n";

    // Step 1: Delete all student sessions
    echo "1. Deleting student sessions...\n";
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE uid IN (SELECT uid FROM users WHERE role = 'student')");
    $stmt->execute();
    $deletedSessions = $stmt->rowCount();
    echo "   ✓ Deleted {$deletedSessions} sessions\n\n";

    // Step 2: Delete student enrollments (class_students)
    echo "2. Deleting student enrollments...\n";
    $stmt = $pdo->prepare("DELETE FROM class_students WHERE student_uid IN (SELECT uid FROM users WHERE role = 'student')");
    $stmt->execute();
    $deletedEnrollments = $stmt->rowCount();
    echo "   ✓ Deleted {$deletedEnrollments} enrollments\n\n";

    // Step 3: Delete student attendance records
    echo "3. Deleting attendance records...\n";
    $stmt = $pdo->prepare("DELETE FROM attendance WHERE student_uid IN (SELECT uid FROM users WHERE role = 'student')");
    $stmt->execute();
    $deletedAttendance = $stmt->rowCount();
    echo "   ✓ Deleted {$deletedAttendance} attendance records\n\n";

    // Step 4: Delete student grades
    echo "4. Deleting grade records...\n";
    $stmt = $pdo->prepare("DELETE FROM grades WHERE student_uid IN (SELECT uid FROM users WHERE role = 'student')");
    $stmt->execute();
    $deletedGrades = $stmt->rowCount();
    echo "   ✓ Deleted {$deletedGrades} grade records\n\n";

    // Step 5: Delete AI insights for students
    echo "5. Deleting AI insights...\n";
    $stmt = $pdo->prepare("DELETE FROM ai_insights WHERE student_uid IN (SELECT uid FROM users WHERE role = 'student')");
    $stmt->execute();
    $deletedInsights = $stmt->rowCount();
    echo "   ✓ Deleted {$deletedInsights} AI insights\n\n";

    // Step 6: Delete student notifications
    echo "6. Deleting notifications...\n";
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE recipient_uid IN (SELECT uid FROM users WHERE role = 'student')");
    $stmt->execute();
    $deletedNotifications = $stmt->rowCount();
    echo "   ✓ Deleted {$deletedNotifications} notifications\n\n";

    // Step 7: Delete push subscriptions
    echo "7. Deleting push subscriptions...\n";
    $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE uid IN (SELECT uid FROM users WHERE role = 'student')");
    $stmt->execute();
    $deletedPushSubs = $stmt->rowCount();
    echo "   ✓ Deleted {$deletedPushSubs} push subscriptions\n\n";

    // Step 8: Reset pre_approved_students (set used_at to NULL for valid 8-digit IDs)
    echo "8. Resetting pre-approved student IDs...\n";
    $stmt = $pdo->prepare("UPDATE pre_approved_students SET used_at = NULL WHERE LEN(student_id) = 8");
    $stmt->execute();
    $resetIds = $stmt->rowCount();
    echo "   ✓ Reset {$resetIds} pre-approved IDs to available\n\n";

    // Step 9: Delete invalid pre-approved IDs (not 8 digits)
    echo "9. Cleaning up invalid pre-approved IDs...\n";
    $stmt = $pdo->prepare("DELETE FROM pre_approved_students WHERE LEN(student_id) != 8");
    $stmt->execute();
    $deletedInvalidIds = $stmt->rowCount();
    echo "   ✓ Deleted {$deletedInvalidIds} invalid pre-approved IDs\n\n";

    // Step 10: Delete all student user accounts
    echo "10. Deleting student user accounts...\n";
    $stmt = $pdo->prepare("DELETE FROM users WHERE role = 'student'");
    $stmt->execute();
    $deletedUsers = $stmt->rowCount();
    echo "    ✓ Deleted {$deletedUsers} student accounts\n\n";

    // Commit transaction
    $pdo->commit();

    // Verify cleanup
    echo "===========================================\n";
    echo "Cleanup completed successfully!\n";
    echo "===========================================\n\n";

    // Check final state
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $remainingStudents = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pre_approved_students WHERE used_at IS NULL AND LEN(student_id) = 8");
    $availableIds = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pre_approved_students WHERE LEN(student_id) = 8");
    $totalValidIds = $stmt->fetch()['count'];

    echo "Final Status:\n";
    echo "-------------\n";
    echo "Students remaining: {$remainingStudents}\n";
    echo "Valid 8-digit pre-approved IDs: {$totalValidIds}\n";
    echo "Available for registration: {$availableIds}\n\n";

    if ($remainingStudents == 0 && $availableIds == 270) {
        echo "✓ Database is clean and ready!\n";
        echo "✓ You can now run register_students.php\n";
    } else {
        echo "⚠ Warning: Cleanup may be incomplete\n";
    }

} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "\n❌ Error during cleanup: " . $e->getMessage() . "\n";
    echo "\nTransaction rolled back - no changes made\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n===========================================\n";
echo "✓ Done! You can now delete this file.\n";
