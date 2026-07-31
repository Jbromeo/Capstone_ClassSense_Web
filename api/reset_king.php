<?php
require_once 'C:/xampp/htdocs/ClassSense/api/config.php';
$pdo = getPDO();

$hash = '$2y$10$/rkTuq46AJQq0dRVpFA/3eYDircFA/gfhKFXuMJE1ft4GjChI7b.S';
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'kingnoel1'");
$stmt->execute([$hash]);
echo "kingnoel1 updated: " . $stmt->rowCount() . " rows\n";