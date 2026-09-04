<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

verifyToken();

jsonResponse(processInsightQueue());