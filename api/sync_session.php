<?php
// api/sync_session.php
// The Identity Bridge: Firebase -> PHP Session Sync
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/ClassSense/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

$project_folder = 'ClassSense';
$ROOT_URL = '/' . $project_folder . '/';

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['uid']) && isset($data['role'])) {
    $_SESSION['uid'] = $data['uid'];
    $_SESSION['user_id'] = $data['uid'];
    $_SESSION['role'] = $data['role'];
    $_SESSION['last_activity'] = time();

    $dashboard = 'login.php?error=unauthorized_role';
    if ($data['role'] === 'admin')
        $dashboard = 'admin_screen/admin_dashboard.php';
    if ($data['role'] === 'teacher')
        $dashboard = 'teacher_screen/teacher_dashboard.php';
    if ($data['role'] === 'student')
        $dashboard = 'student_screen/student_dashboard.php';

    $_SESSION['dashboard'] = $ROOT_URL . $dashboard;
    session_write_close();

    echo json_encode([
        'status' => 'success',
        'redirect' => $dashboard,
        'role_detected' => $data['role'],
        'message' => 'Identity Handshake Complete'
    ]);
} else {
    session_unset();
    session_destroy();
    echo json_encode(['status' => 'error', 'message' => 'Identity Handshake Failed - Missing Data']);
}
?>