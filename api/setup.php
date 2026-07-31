<?php
require_once __DIR__ . '/config.php';

$pdo = getPDO();

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
        department NVARCHAR(100),
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
        section_code NVARCHAR(50),
        class_code NVARCHAR(10),
        schedule NVARCHAR(50),
        start_time NVARCHAR(10),
        end_time NVARCHAR(10),
        time_slot NVARCHAR(50),
        session_limit INT DEFAULT 15,
        teacher_uid VARCHAR(128) NOT NULL,
        teacher_name NVARCHAR(255),
        status NVARCHAR(50) DEFAULT 'In Progress',
        session_active INT DEFAULT 0,
        session_started_at DATETIME NULL,
        current_nonce NVARCHAR(10) NULL,
        created_at DATETIME DEFAULT GETDATE()
    )",

    "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='class_students' AND xtype='U')
    CREATE TABLE class_students (
        class_id VARCHAR(36) NOT NULL,
        student_uid VARCHAR(128) NOT NULL,
        enrolled_at DATETIME DEFAULT GETDATE(),
        PRIMARY KEY (class_id, student_uid),
        FOREIGN KEY (class_id) REFERENCES classes(id)
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
];

echo "<pre>\n";

foreach ($queries as $sql) {
    if (strpos(trim($sql), '--') === 0) {
        echo "$sql\n";
        continue;
    }
    try {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 80) . "...\n";
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "SQL: $sql\n\n";
    }
}

echo "\nDone. All tables created.\n";
echo "</pre>";
