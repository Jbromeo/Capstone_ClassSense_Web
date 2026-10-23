<?php
/**
 * Script to create classes for teachers
 * Access via: http://localhost/ClassSense/create_classes.php
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Creating Teacher Classes\n";
echo "===========================================\n\n";

try {
    $pdo = getPDO();

    // Step 1: Get teacher UIDs by name
    echo "Step 1: Finding teachers...\n";
    echo "-----------------------------\n";

    $teachers = [
        'Arlyn Joy Añora',
        'Chrisha Mae Reyes',
        'Jan Eve Caparoso',
        'Ivan Capuz',
        'Judeizle Melijor',
        'Des U. Mendoza'
    ];

    $teacherMap = [];
    foreach ($teachers as $name) {
        $parts = explode(' ', $name);
        $firstName = $parts[0];
        $lastName = end($parts);

        $stmt = $pdo->prepare("
            SELECT TOP 1 uid, username, CONCAT(first_name, ' ', last_name) as full_name
            FROM users
            WHERE role = 'teacher'
            AND first_name LIKE ?
            AND last_name LIKE ?
        ");
        $stmt->execute(["%$firstName%", "%$lastName%"]);
        $teacher = $stmt->fetch();

        if ($teacher) {
            $teacherMap[$name] = $teacher;
            echo "✓ Found: {$teacher['full_name']} (@{$teacher['username']})\n";
        } else {
            echo "✗ NOT FOUND: {$name}\n";
        }
    }

    echo "\n";

    // Step 2: Define classes
    $classes = [
        [
            'teacher_name' => 'Arlyn Joy Añora',
            'class_name' => 'ICT - 7',
            'level' => 'junior high school',
            'section' => 'Callisto',
            'days' => 'M,T,W,Th,F',
            'time_start' => '07:30',
            'time_end' => '08:30'
        ],
        [
            'teacher_name' => 'Chrisha Mae Reyes',
            'class_name' => 'TLE - 8',
            'level' => 'junior high school',
            'section' => 'Mercury',
            'days' => 'M,T,W,Th,F',
            'time_start' => '07:30',
            'time_end' => '08:30'
        ],
        [
            'teacher_name' => 'Jan Eve Caparoso',
            'class_name' => 'Math - 9',
            'level' => 'junior high school',
            'section' => 'Polaris',
            'days' => 'M,T,W,Th,F',
            'time_start' => '07:30',
            'time_end' => '08:30'
        ],
        [
            'teacher_name' => 'Ivan Capuz',
            'class_name' => 'Science - 10',
            'level' => 'junior high school',
            'section' => 'Circinus',
            'days' => 'M,T,W,Th,F',
            'time_start' => '07:30',
            'time_end' => '08:30'
        ],
        [
            'teacher_name' => 'Judeizle Melijor',
            'class_name' => 'Oral Communication - 11',
            'level' => 'senior high school',
            'section' => 'Circinus',
            'days' => 'M,T,W,Th,F',
            'time_start' => '07:30',
            'time_end' => '08:30'
        ],
        [
            'teacher_name' => 'Des U. Mendoza',
            'class_name' => '21st Century - 12',
            'level' => 'senior high school',
            'section' => 'Circinus',
            'days' => 'M,T,W,Th,F',
            'time_start' => '07:30',
            'time_end' => '08:30'
        ]
    ];

    // Step 2: Create classes
    echo "Step 2: Creating classes...\n";
    echo "----------------------------\n";

    $successCount = 0;
    $failedCount = 0;
    $createdClasses = [];

    foreach ($classes as $classData) {
        $teacherName = $classData['teacher_name'];

        if (!isset($teacherMap[$teacherName])) {
            echo "✗ Skipped: {$classData['class_name']} - Teacher not found\n";
            $failedCount++;
            continue;
        }

        $teacher = $teacherMap[$teacherName];
        $teacherUid = $teacher['uid'];

        try {
            // Generate class code (random 6-character alphanumeric)
            $classCode = strtoupper(substr(md5(uniqid()), 0, 6));

            $stmt = $pdo->prepare("
                INSERT INTO classes (
                    teacher_uid,
                    class_name,
                    class_code,
                    level,
                    section,
                    schedule_days,
                    time_start,
                    time_end
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $teacherUid,
                $classData['class_name'],
                $classCode,
                $classData['level'],
                $classData['section'],
                $classData['days'],
                $classData['time_start'],
                $classData['time_end']
            ]);

            $classId = $pdo->lastInsertId();
            $createdClasses[] = [
                'id' => $classId,
                'code' => $classCode,
                'teacher' => $teacherName,
                'class' => $classData['class_name'],
                'section' => $classData['section']
            ];

            echo "✓ Created: {$classData['class_name']} ({$classData['section']}) - Code: {$classCode}\n";
            $successCount++;

        } catch (PDOException $e) {
            echo "✗ Failed: {$classData['class_name']} - {$e->getMessage()}\n";
            $failedCount++;
        }
    }

    echo "\n===========================================\n";
    echo "Class Creation Summary\n";
    echo "===========================================\n";
    echo "Successfully created: {$successCount} classes\n";
    echo "Failed: {$failedCount}\n";

    // Step 3: Display created classes
    if (!empty($createdClasses)) {
        echo "\n";
        echo "Created Classes Details:\n";
        echo "------------------------\n";

        foreach ($createdClasses as $class) {
            echo "\n📚 {$class['class']}\n";
            echo "   Teacher: {$class['teacher']}\n";
            echo "   Section: {$class['section']}\n";
            echo "   Class Code: {$class['code']}\n";
            echo "   Class ID: {$class['id']}\n";
        }
    }

    // Step 4: Verify in database
    echo "\n";
    echo "Verification:\n";
    echo "-------------\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
    $totalClasses = $stmt->fetch()['count'];
    echo "Total classes in database: {$totalClasses}\n";

    echo "\n✓ Done! You can now delete this file for security.\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
