-- ClassSense: super admin + deletion requests + audit log
-- Idempotent: safe to re-run against an existing ClassSense database.
-- Run in SQL Server Management Studio (or sqlcmd).
--
-- Seeds the super admin account:
--   username: super_admin
--   password: 123456   <-- change it after first login
--   role / role_type: super_admin

PRINT '======================================== ClassSense super admin setup START';

-- role_type must exist before the seed (older databases may not have it yet)
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('users') AND name = 'role_type')
BEGIN
    PRINT '-- users: adding role_type';
    ALTER TABLE users ADD role_type NVARCHAR(20) NULL;
    UPDATE users SET role_type = role WHERE role_type IS NULL;
END
ELSE PRINT '-- users: role_type already exists';

-- Account deletion requests: admins file them, the super admin approves/rejects.
-- Snapshot columns (no FK) so a direct super-admin delete cannot orphan the row.
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='deletion_requests' AND xtype='U')
BEGIN
    PRINT '-- deletion_requests: creating table';
    CREATE TABLE deletion_requests (
        id INT IDENTITY(1,1) PRIMARY KEY,
        target_uid VARCHAR(128) NOT NULL,
        target_username NVARCHAR(255) NULL,
        target_role NVARCHAR(20) NULL,
        target_name NVARCHAR(255) NULL,
        requested_by VARCHAR(128) NOT NULL,
        requested_username NVARCHAR(255) NULL,
        reason NVARCHAR(500) NULL,
        status NVARCHAR(20) NOT NULL DEFAULT 'pending',
        requested_at DATETIME DEFAULT GETDATE(),
        resolved_by VARCHAR(128) NULL,
        resolved_at DATETIME NULL
    );
END
ELSE PRINT '-- deletion_requests: already exists';

IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_deletion_requests_status')
    CREATE INDEX idx_deletion_requests_status ON deletion_requests(status, requested_at);

IF NOT EXISTS (SELECT * FROM sysindexes WHERE name='idx_deletion_requests_target')
    CREATE INDEX idx_deletion_requests_target ON deletion_requests(target_uid);

-- Audit trail feature removed. Drop the legacy table (idempotent).
IF OBJECT_ID('audit_logs', 'U') IS NOT NULL
BEGIN
    PRINT '-- audit_logs: dropping table';
    DROP TABLE audit_logs;
END
ELSE PRINT '-- audit_logs: already absent';

-- Seed the super admin account (idempotent).
IF NOT EXISTS (SELECT 1 FROM users WHERE username = 'super_admin')
BEGIN
    PRINT '-- users: seeding super_admin';
    INSERT INTO users (uid, username, password_hash, role, role_type, first_name, last_name)
    VALUES ('superadmin', 'super_admin', '$2y$10$hYjLhAbqvtxt/C7mprA8yOOdw9a2eRMkAvOGs2byje34eMbfeldBK', 'super_admin', 'super_admin', 'Super', 'Admin');
END
ELSE PRINT '-- users: super_admin already exists';

PRINT '--[verify] super admin account --';
SELECT uid, username, role, role_type, first_name, last_name, created_at
FROM users WHERE username = 'super_admin';

PRINT '======================================== ClassSense super admin setup END';
