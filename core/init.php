<?php
// core/init.php
// MASTER IDENTITY ORCHESTRATOR: Session Handler & Security Guard

// 1. Force Identity Refresh (No-Cache Policy)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    // 🛡️ GLOBAL SESSION LOCK: Ensures identity persists across every folder
    session_set_cookie_params([
        'path' => '/ClassSense/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

error_log('[init] PHP_SELF=' . ($_SERVER['PHP_SELF'] ?? '?') . ' SESSION_uid=' . ($_SESSION['uid'] ?? 'null') . ' SESSION_role=' . ($_SESSION['role'] ?? 'null') . ' SESSION_dashboard=' . ($_SESSION['dashboard'] ?? 'null') . ' REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? '?'));

// 2. Dynamic Path Configuration
$project_folder = 'ClassSense';
define('ROOT_URL', '/' . $project_folder . '/');

// Absolute timeout check (30m)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    error_log('[init] session expired by timeout');
    session_unset();
    session_destroy();
    header("Location: " . ROOT_URL . "login.php?status=session_expired");
    exit();
}
$_SESSION['last_activity'] = time();

// 3. Identification Guard & Navigation Loop Protection
$current_path = $_SERVER['PHP_SELF'];
$current_page = basename($current_path);
$public_pages = ['login.php', 'register.php', 'index.php'];

// --- THE PUBLIC GATE (Login, Index, etc.) ---
if (in_array($current_page, $public_pages)) {
    // Only redirect AWAY from the login page if the identity is 100% verified (ID, Role, and Destination)
    if (isset($_SESSION['uid']) && isset($_SESSION['role']) && isset($_SESSION['dashboard'])) {

        // LOOP BREAKER: Only redirect to the dashboard if it is DIFFERENT than the current page
        $dashboard_path = $_SESSION['dashboard'];
        if (strpos($current_path, basename($dashboard_path)) === false) {
            error_log('[init] PUBLIC GATE: redirecting to ' . $dashboard_path);
            header("Location: " . $dashboard_path);
            exit();
        }
    }
    // If identity is incomplete, allow them to stay on the login page (Prevents loops)
}
// --- THE PROTECTED GATE (Dashboards, Screens, etc.) ---
else {
    // Ensure the basic identity exists
    if (!isset($_SESSION['uid'])) {
        error_log('[init] PROTECTED GATE: no uid, redirecting to login');
        header("Location: " . ROOT_URL . "login.php?error=identity_missing");
        exit();
    }

    // 4. Role Integrity Check: re-verify the session role against the users
    // table on every request. Two layers:
    //   - session role vs DB role  -> catches mid-session role changes made
    //     directly in the database (e.g. teacher -> student in SSMS).
    //   - DB role vs role_type     -> the immutable role stamped at account
    //     creation. A manual SQL edit to 'role' that diverges from 'role_type'
    //     is treated as tampering and keeps 404ing even after re-login, until
    //     the DB row is re-aligned.
    // null = check skipped (DB unavailable, fail-open), false = user row gone.
    $db_role = null;
    $db_role_type = null;
    $cfg_path = __DIR__ . '/../api/config.json';
    $cfg = file_exists($cfg_path) ? json_decode(file_get_contents($cfg_path), true) : null;
    if ($cfg) {
        $connect = function () use ($cfg) {
            return new PDO('sqlsrv:Server=' . $cfg['db_host'] . ';Database=' . $cfg['db_name'], $cfg['db_user'], $cfg['db_pass']);
        };
        try {
            $stmt = $connect()->prepare("SELECT role, role_type FROM users WHERE uid = ?");
            $stmt->execute([$_SESSION['uid']]);
            $row = $stmt->fetch();
            if ($row) {
                $db_role = $row['role'];
                $db_role_type = $row['role_type'] ?? null;
            } else {
                $db_role = false;
            }
        } catch (Throwable $e) {
            if (stripos($e->getMessage(), 'role_type') !== false) {
                // role_type column not migrated yet (setup.php not re-run):
                // fall back to the role-only check so the stale-session guard
                // still works.
                try {
                    $stmt = $connect()->prepare("SELECT role FROM users WHERE uid = ?");
                    $stmt->execute([$_SESSION['uid']]);
                    $row = $stmt->fetch();
                    $db_role = $row ? $row['role'] : false;
                } catch (Throwable $e2) {
                    error_log('[init] role integrity check skipped: ' . $e2->getMessage());
                }
            } else {
                error_log('[init] role integrity check skipped: ' . $e->getMessage());
            }
        }
    }

    if ($db_role !== null) {
        $session_role = $_SESSION['role'] ?? null;
        $valid_roles = ['admin', 'teacher', 'student'];
        $mismatch = !in_array($db_role, $valid_roles, true) || $db_role !== $session_role;
        // Tamper layer: only compare when role_type holds a valid reference
        // (NULL/invalid = no evidence of tampering, don't false-positive).
        if (!$mismatch && in_array($db_role_type, $valid_roles, true) && $db_role_type !== $db_role) {
            $mismatch = true;
        }
        if ($mismatch) {
            error_log('[init] ROLE MISMATCH: session=' . var_export($session_role, true) . ' db=' . var_export($db_role, true) . ' role_type=' . var_export($db_role_type, true) . ' uri=' . ($_SERVER['REQUEST_URI'] ?? '?'));
            http_response_code(404);
            include __DIR__ . '/../404.php';
            exit();
        }
    }

    // Role-Based Router Filter: Ensures you can only access your own folder
    $path = $_SERVER['REQUEST_URI'];
    $role = $_SESSION['role'] ?? 'guest'; // Default to guest if not synced yet

    if (strpos($path, 'admin_screen') !== false && $role !== 'admin') {
        error_log('[init] PROTECTED GATE: forbidden_admin');
        header("Location: " . ROOT_URL . "login.php?error=forbidden_admin");
        exit();
    }

    if (strpos($path, 'student_screen') !== false && $role !== 'student') {
        error_log('[init] PROTECTED GATE: forbidden_student');
        header("Location: " . ROOT_URL . "login.php?error=forbidden_student");
        exit();
    }

    if (strpos($path, 'teacher_screen') !== false && $role !== 'teacher') {
        error_log("[init] PROTECTED GATE: forbidden_faculty");
        header("Location: " . ROOT_URL . "login.php?error=forbidden_faculty");
        exit();
    }
}
?>