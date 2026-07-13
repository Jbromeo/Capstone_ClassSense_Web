<?php
header('Content-Type: application/json');
// api/logout.php
// The Final Step in the Logout Auth Process: Clearing local data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();

// Clear the cookie specifically from the ClassSense path
$project_folder = 'ClassSense';
setcookie(session_name(), '', time() - 3600, '/' . $project_folder . '/');

echo json_encode(['status' => 'success', 'message' => 'Backend Session Erased']);
?>
