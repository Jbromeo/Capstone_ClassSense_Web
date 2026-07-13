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