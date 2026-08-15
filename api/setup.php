<?php
// =====================================================================
// ClassSense database setup
// ---------------------------------------------------------------------
// One-click bootstrap for the whole database schema.
//   - Creates the database itself if it does not exist yet.
//   - Auto-detects the SQL Server instance (config.json first, then
//     common local fallbacks) so it works on any device with zero
//     editing.
//   - Safe to re-run: every statement is idempotent (IF NOT EXISTS).
//
// Transfer to another device:
//   1. Copy this project folder into the new machine's htdocs.
//   2. Open http://localhost/ClassSense/api/setup.php in a browser.
//   3. It creates the database, all tables, indexes and constraints.
//   4. Done — delete or keep this file (keeping it is harmless).
//
// Optional manual overrides (instead of config.json):
//   ?host=YOURSERVER&user=sa&pass=secret
// =====================================================================

if (!extension_loaded('pdo_sqlsrv')) {
    die('<h2>PDO SQLSRV extension is missing</h2><p>Enable <code>extension=php_pdo_sqlsrv.dll</code> and <code>extension=php_sqlsrv.dll</code> in your php.ini, then retry.</p>');
}

$configPath = __DIR__ . '/config.json';
$config = (file_exists($configPath)) ? json_decode(file_get_contents($configPath), true) : [];

$dbName = trim((string)($config['db_name'] ?? 'ClassSense'));
$dbUser = trim((string)($_GET['user'] ?? $config['db_user'] ?? 'sa'));
$dbPass = (string)($_GET['pass'] ?? $config['db_pass'] ?? '');
$requestedHost = trim((string)($_GET['host'] ?? $config['db_host'] ?? ''));

function connectTo($host, $dbName, $dbUser, $dbPass) {
    $dsn = 'sqlsrv:Server=' . $host . ($dbName !== '' ? ';Database=' . $dbName : '');
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

$queries = [
    "-- Create ClassSense database tables (SQL-only schema)",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='users' AND xtype='U')
    CREATE TABLE users (
        uid VARCHAR(128) PRIMARY KEY,
        username NVARCHAR(255) UNIQUE NOT NULL,
        password_hash NVARCHAR(255) NOT NULL,
        role NVARCHAR(20) NOT NULL,
        first_name NVARCHAR(255),
        last_name NVARCHAR(255),
        student_id NVARCHAR(50),
        employee_id NVARCHAR(50),
        profile_picture NVARCHAR(500),
        created_at DATETIME DEFAULT GETDATE()
    )",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='sessions' AND xtype='U')
    CREATE TABLE sessions (
        id INT IDENTITY PRIMARY KEY,
        uid VARCHAR(128) NOT NULL,
        token NVARCHAR(255) NOT NULL UNIQUE,
        created_at DATETIME DEFAULT GETDATE(),
        expires_at DATETIME NOT NULL
    )",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='classes' AND xtype='U')
    CREATE TABLE classes (
        id VARCHAR(36) PRIMARY KEY,
        class_name NVARCHAR(255) NOT NULL,
        level NVARCHAR(100),
        subject NVARCHAR(255),
        section_name NVARCHAR(50),
        class_code NVARCHAR(10),
        schedule NVARCHAR(50),
        start_time NVARCHAR(10),
        end_time NVARCHAR(10),
        time_slot NVARCHAR(50),
        session_limit INT DEFAULT 0,
        teacher_uid VARCHAR(128) NOT NULL,
        teacher_name NVARCHAR(255),
        status NVARCHAR(50) DEFAULT 'In Progress',
        session_active INT DEFAULT 0,
        session_started_at DATETIME NULL,
        current_nonce NVARCHAR(10) NULL,
        created_at DATETIME DEFAULT GETDATE()
    )",

    "-- Section name migration: rename classes.section_code -> section_name (keeps existing values)",
    "IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('classes') AND name = 'section_code')
    EXEC sp_rename 'classes.section_code', 'section_name', 'COLUMN'",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='class_students' AND xtype='U')
    CREATE TABLE class_students (
        class_id VARCHAR(36) NOT NULL,
        student_uid VARCHAR(128) NOT NULL,
        enrolled_at DATETIME DEFAULT GETDATE(),
        PRIMARY KEY (class_id, student_uid),
        CONSTRAINT fk_class_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        CONSTRAINT fk_class_students_student FOREIGN KEY (student_uid) REFERENCES users(uid) ON DELETE CASCADE
    )",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='attendance' AND xtype='U')
    CREATE TABLE attendance (
        id INT IDENTITY(1,1) PRIMARY KEY,
        student_uid VARCHAR(128) NOT NULL,
        class_id VARCHAR(36) NOT NULL,
        date NVARCHAR(20) NOT NULL,
        timestamp DATETIME DEFAULT GETDATE(),
        status NVARCHAR(20) DEFAULT 'Present'
    )",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='events' AND xtype='U')
    CREATE TABLE events (
        id INT IDENTITY(1,1) PRIMARY KEY,
        teacher_uid VARCHAR(128) NOT NULL,
        title NVARCHAR(255) NOT NULL,
        date_str DATE NOT NULL,
        start_time NVARCHAR(10),
        end_time NVARCHAR(10),
        description NTEXT,
        created_at DATETIME DEFAULT GETDATE()
    )",

    "-- Session-management columns for classes (live attendance state machine)",
    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('classes') AND name = 'session_id')
    ALTER TABLE classes ADD session_id VARCHAR(36) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('classes') AND name = 'session_expires_at')
    ALTER TABLE classes ADD session_expires_at DATETIME NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('classes') AND name = 'last_nonce')
    ALTER TABLE classes ADD last_nonce NVARCHAR(10) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('classes') AND name = 'nonce_issued_at')
    ALTER TABLE classes ADD nonce_issued_at DATETIME NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('classes') AND name = 'session_mode')
    ALTER TABLE classes ADD session_mode NVARCHAR(10) DEFAULT 'open'",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('classes') AND name = 'require_location')
    ALTER TABLE classes ADD require_location INT DEFAULT 0",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('classes') AND name = 'session_lat')
    ALTER TABLE classes ADD session_lat DECIMAL(10,7) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('classes') AND name = 'session_lng')
    ALTER TABLE classes ADD session_lng DECIMAL(10,7) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('classes') AND name = 'session_radius_m')
    ALTER TABLE classes ADD session_radius_m INT DEFAULT 150",

    "-- Pointer to the most recently ended session so the report can be reopened
    -- after a reload (survives the live session_id being cleared on end).",
    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('classes') AND name = 'last_session_id')
    ALTER TABLE classes ADD last_session_id VARCHAR(36) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('classes') AND name = 'last_session_ended_at')
    ALTER TABLE classes ADD last_session_ended_at DATETIME NULL",

    "-- Contact columns for users (guardian contact for students)",
    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('users') AND name = 'phone')
    ALTER TABLE users ADD phone NVARCHAR(20) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('users') AND name = 'guardian_phone')
    ALTER TABLE users ADD guardian_phone NVARCHAR(20) NULL",

    "-- Per-account theme preference ('light' / 'dark' / NULL = system-aware)",
    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('users') AND name = 'theme')
    ALTER TABLE users ADD theme NVARCHAR(10) NULL",

    "-- Audit columns for attendance (fraud detection trail)",
    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('attendance') AND name = 'ip_address')
    ALTER TABLE attendance ADD ip_address VARCHAR(45) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('attendance') AND name = 'session_id')
    ALTER TABLE attendance ADD session_id VARCHAR(36) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('attendance') AND name = 'lat')
    ALTER TABLE attendance ADD lat DECIMAL(10,7) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('attendance') AND name = 'lng')
    ALTER TABLE attendance ADD lng DECIMAL(10,7) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('attendance') AND name = 'device_uuid')
    ALTER TABLE attendance ADD device_uuid VARCHAR(64) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('attendance') AND name = 'is_mock')
    ALTER TABLE attendance ADD is_mock INT NULL",

    "-- Geofence audit columns for attendance (distance + suspicious flag)",
    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('attendance') AND name = 'distance_m')
    ALTER TABLE attendance ADD distance_m DECIMAL(10,2) NULL",

    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('attendance') AND name = 'is_suspicious')
    ALTER TABLE attendance ADD is_suspicious INT DEFAULT 0",

    "-- Add indexes for performance",
    "IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_users_username')
    CREATE INDEX idx_users_username ON users(username)",

    "IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_sessions_token')
    CREATE INDEX idx_sessions_token ON sessions(token)",

    "IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_classes_teacher')
    CREATE INDEX idx_classes_teacher ON classes(teacher_uid)",

    "IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_class_students_class')
    CREATE INDEX idx_class_students_class ON class_students(class_id)",

    "IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_class_students_student')
    CREATE INDEX idx_class_students_student ON class_students(student_uid)",

    "IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_events_teacher')
    CREATE INDEX idx_events_teacher ON events(teacher_uid)",

    "IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_attendance_class_date')
    CREATE INDEX idx_attendance_class_date ON attendance(class_id, date)",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='pre_approved_students' AND xtype='U')
    CREATE TABLE pre_approved_students (
        id INT IDENTITY PRIMARY KEY,
        student_id NVARCHAR(50) UNIQUE NOT NULL,
        created_at DATETIME DEFAULT GETDATE(),
        used_at DATETIME NULL
    )",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='notifications' AND xtype='U')
    CREATE TABLE notifications (
        id INT IDENTITY PRIMARY KEY,
        recipient_uid VARCHAR(128) NOT NULL,
        type NVARCHAR(50) NOT NULL,
        title NVARCHAR(255) NOT NULL,
        message NTEXT,
        link NVARCHAR(500),
        is_read INT DEFAULT 0,
        created_at DATETIME DEFAULT GETDATE()
    )",

    "IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_notifications_recipient')
    CREATE INDEX idx_notifications_recipient ON notifications(recipient_uid)",

    "-- Grading center tables (components, scores, and weights per class/term)",
    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='grade_components' AND xtype='U')
    CREATE TABLE grade_components (
        id INT IDENTITY(1,1) PRIMARY KEY,
        class_id VARCHAR(36) NOT NULL,
        category NVARCHAR(20) NOT NULL,
        name NVARCHAR(255) NOT NULL,
        hps INT NOT NULL DEFAULT 50,
        quarter INT NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT GETDATE(),
        CONSTRAINT fk_grade_components_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
    )",

    "-- Link each attendance grading column to its audit session (one column per session)",
    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('grade_components') AND name = 'session_id')
    ALTER TABLE grade_components ADD session_id VARCHAR(36) NULL",

    "IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_grade_components_session')
    CREATE INDEX idx_grade_components_session ON grade_components(class_id, category, session_id)",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='grades' AND xtype='U')
    CREATE TABLE grades (
        component_id INT NOT NULL,
        student_uid VARCHAR(128) NOT NULL,
        score DECIMAL(10,2) NOT NULL,
        updated_at DATETIME DEFAULT GETDATE(),
        PRIMARY KEY (component_id, student_uid),
        CONSTRAINT fk_grades_component FOREIGN KEY (component_id) REFERENCES grade_components(id) ON DELETE CASCADE,
        CONSTRAINT fk_grades_student FOREIGN KEY (student_uid) REFERENCES users(uid) ON DELETE CASCADE
    )",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='grade_weights' AND xtype='U')
    CREATE TABLE grade_weights (
        class_id VARCHAR(36) NOT NULL,
        category NVARCHAR(20) NOT NULL,
        weight_percent INT NOT NULL DEFAULT 0,
        PRIMARY KEY (class_id, category),
        CONSTRAINT fk_grade_weights_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
    )",

    "-- AI Academic Insight cache (one analyzed insight per student/class)",
    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='ai_insights' AND xtype='U')
    CREATE TABLE ai_insights (
        id INT IDENTITY PRIMARY KEY,
        student_uid VARCHAR(128) NOT NULL,
        class_id VARCHAR(36) NOT NULL,
        insight_paragraph NTEXT NOT NULL,
        insight_tips NTEXT NULL,
        signature NVARCHAR(64) NOT NULL,
        created_at DATETIME DEFAULT GETDATE(),
        CONSTRAINT uq_ai_insights_student_class UNIQUE (student_uid, class_id),
        CONSTRAINT fk_ai_insights_student FOREIGN KEY (student_uid) REFERENCES users(uid) ON DELETE CASCADE,
        CONSTRAINT fk_ai_insights_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
    )",

    "IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_grade_components_class')
    CREATE INDEX idx_grade_components_class ON grade_components(class_id, quarter)",

    "-- Forensic / integrity constraints: cascade deletes so no orphan rows survive.
    -- NOTE: classes.teacher_uid is intentionally NO ACTION (block) — cascading it
    -- would create multiple cascade paths into class_students (user->classes->cs and
    -- user->cs). Teachers are cleaned up in application code (api/fetch.php DELETE
    -- deletes the teacher's classes first). Run db_repair.sql once to add these
    -- to an existing database.",

    "IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_class_students_student' AND parent_object_id = OBJECT_ID('class_students'))
    ALTER TABLE class_students ADD CONSTRAINT fk_class_students_student FOREIGN KEY (student_uid) REFERENCES users(uid) ON DELETE CASCADE",

    "IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_class_students_class' AND parent_object_id = OBJECT_ID('class_students'))
    ALTER TABLE class_students ADD CONSTRAINT fk_class_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE",

    "IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_attendance_student' AND parent_object_id = OBJECT_ID('attendance'))
    ALTER TABLE attendance ADD CONSTRAINT fk_attendance_student FOREIGN KEY (student_uid) REFERENCES users(uid) ON DELETE CASCADE",

    "IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_attendance_class' AND parent_object_id = OBJECT_ID('attendance'))
    ALTER TABLE attendance ADD CONSTRAINT fk_attendance_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE",

    "IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_classes_teacher' AND parent_object_id = OBJECT_ID('classes'))
    ALTER TABLE classes ADD CONSTRAINT fk_classes_teacher FOREIGN KEY (teacher_uid) REFERENCES users(uid)",

    "IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_events_teacher' AND parent_object_id = OBJECT_ID('events'))
    ALTER TABLE events ADD CONSTRAINT fk_events_teacher FOREIGN KEY (teacher_uid) REFERENCES users(uid) ON DELETE CASCADE",

    "IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_sessions_user' AND parent_object_id = OBJECT_ID('sessions'))
    ALTER TABLE sessions ADD CONSTRAINT fk_sessions_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE",

    "-- Push notifications: per-user master switch + registered device tokens",
    "IF NOT EXISTS (SELECT * FROM syscolumns WHERE id = OBJECT_ID('users') AND name = 'push_enabled')
    ALTER TABLE users ADD push_enabled INT DEFAULT 0",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='push_subscriptions' AND xtype='U')
    CREATE TABLE push_subscriptions (
        id INT IDENTITY PRIMARY KEY,
        uid VARCHAR(128) NOT NULL,
        token NVARCHAR(255) NOT NULL UNIQUE,
        created_at DATETIME DEFAULT GETDATE(),
        CONSTRAINT fk_push_subscriptions_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
    )",

    "IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_push_subscriptions_uid')
    CREATE INDEX idx_push_subscriptions_uid ON push_subscriptions(uid)",

    "IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_notifications_recipient' AND parent_object_id = OBJECT_ID('notifications'))
    ALTER TABLE notifications ADD CONSTRAINT fk_notifications_recipient FOREIGN KEY (recipient_uid) REFERENCES users(uid) ON DELETE CASCADE",
];

// ---------------------------------------------------------------------
// Candidate server list: requested host first, then common local names.
// ---------------------------------------------------------------------
$candidates = [];
foreach ([$requestedHost, '(local)', 'localhost', '.', '.\SQLEXPRESS', '127.0.0.1', $config['db_host'] ?? ''] as $h) {
    $h = trim((string)$h);
    if ($h !== '' && !in_array($h, $candidates, true)) {
        $candidates[] = $h;
    }
}

$serverPdo = null;
$usedHost = null;
$connectionErrors = [];

foreach ($candidates as $h) {
    try {
        $serverPdo = connectTo($h, '', $dbUser, $dbPass);
        $usedHost = $h;
        break;
    } catch (PDOException $e) {
        $connectionErrors[] = $h . ' -> ' . $e->getMessage();
    }
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>ClassSense Setup</title>
<style>
body { font-family: Consolas, monospace; background: #0f172a; color: #e2e8f0; padding: 30px; }
.wrap { max-width: 900px; margin: auto; }
h1 { color: #38bdf8; } h2 { color: #94a3b8; margin-top: 28px; }
.ok { color: #4ade80; } .err { color: #f87171; } .info { color: #fbbf24; }
pre { background: #1e293b; padding: 16px; border-radius: 8px; overflow-x: auto; }
code { background: #1e293b; padding: 1px 5px; border-radius: 4px; }
table { border-collapse: collapse; } td, th { border: 1px solid #334155; padding: 4px 10px; }
</style></head><body><div class='wrap'>";

echo "<h1>ClassSense Database Setup</h1>";

if (!$serverPdo) {
    echo "<h2 class='err'>Could not connect to SQL Server</h2><p>I tried these servers:</p><pre>";
    foreach ($connectionErrors as $err) {
        echo htmlspecialchars($err) . "\n";
    }
    echo "</pre><p>Fix <code>api/config.json</code> (db_host / db_user / db_pass) on this machine, or open "
       . "<code>setup.php?host=YOURSERVER&user=sa&pass=YOURPASS</code>. "
       . "On a local machine try <code>?host=localhost</code> or <code>?host=.\\SQLEXPRESS</code>.</p></div></body></html>";
    exit;
}

echo "<p class='ok'>Connected to SQL Server instance: <code>" . htmlspecialchars($usedHost) . "</code></p>";

// ---------------------------------------------------------------------
// STEP 1: create the database if it does not exist yet.
// ---------------------------------------------------------------------
try {
    $stmt = $serverPdo->query("SELECT DB_ID('" . $dbName . "') AS db_id");
    $existing = $stmt->fetchColumn();
    if ($existing) {
        echo "<p class='info'>Database <code>$dbName</code> already exists.</p>";
    } else {
        $serverPdo->exec("CREATE DATABASE " . $dbName);
        echo "<p class='ok'>Database <code>$dbName</code> created.</p>";
    }
} catch (PDOException $e) {
    echo "<p class='err'>Database step failed: " . htmlspecialchars($e->getMessage()) . "</p></div></body></html>";
    exit;
}

// ---------------------------------------------------------------------
// STEP 2: reconnect to the database and apply the full schema.
// ---------------------------------------------------------------------
try {
    $pdo = connectTo($usedHost, $dbName, $dbUser, $dbPass);
} catch (PDOException $e) {
    echo "<p class='err'>Could not connect to database <code>$dbName</code>: "
       . htmlspecialchars($e->getMessage()) . "</p></div></body></html>";
    exit;
}

echo "<h2>Applying schema (idempotent — safe to re-run)</h2><pre>";

$okCount = 0;
$errCount = 0;

foreach ($queries as $sql) {
    if (strpos(trim($sql), '--') === 0) {
        echo "<span class='info'>$sql</span>\n";
        continue;
    }
    try {
        $pdo->exec($sql);
        echo "<span class='ok'>OK:   </span>" . htmlspecialchars(substr($sql, 0, 80)) . "...\n";
        $okCount++;
    } catch (PDOException $e) {
        echo "<span class='err'>ERROR:</span> " . htmlspecialchars($e->getMessage()) . "\n"
           . "       SQL: " . htmlspecialchars(str_replace("\n", " ", $sql)) . "\n\n";
        $errCount++;
    }
}

echo "</pre>";

// ---------------------------------------------------------------------
// STEP 3: verification — list every table that exists now.
// ---------------------------------------------------------------------
echo "<h2>Verification — tables present in <code>$dbName</code></h2>";
try {
    $tables = $pdo->query("SELECT name FROM sysobjects WHERE xtype='U' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    if (count($tables) > 0) {
        echo "<table><tr><th>Table</th></tr>";
        foreach ($tables as $t) {
            echo "<tr><td>" . htmlspecialchars($t) . "</td></tr>";
        }
        echo "</table><p>Total: <b>" . count($tables) . "</b> tables.</p>";
    } else {
        echo "<p class='err'>No tables found — something went wrong.</p>";
    }
} catch (PDOException $e) {
    echo "<p class='err'>Verification failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Summary</h2><pre>";
echo "<span class='ok'>$okCount</span> schema statements succeeded, <span class='err'>$errCount</span> failed.\n";
echo $errCount === 0 ? "DATABASE READY.\n" : "CHECK THE ERRORS ABOVE.\n";
echo "</pre>";

echo "<h2>Transfer to another device</h2><pre>";
echo "1. Copy this whole project folder to the other machine's htdocs.\n";
echo "2. Open http://localhost/ClassSense/api/setup.php in a browser.\n";
echo "3. It auto-detects the local SQL Server, creates the database,\n";
echo "   all tables, indexes and constraints. No manual SQL needed.\n";
echo "4. If auto-detection misses your instance, use:\n";
echo "   setup.php?host=.\\SQLEXPRESS   (or your server name)\n";
echo "</pre></div></body></html>";
