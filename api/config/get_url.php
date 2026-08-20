<?php
// Public endpoint — NO authentication required
// Mobile app calls this to discover the current server URL on the same LAN.
// Allows CORS from any origin (mobile app + web admin both need this)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Resolve the machine's real LAN address that phones on the same WiFi can reach.
// When accessed via localhost, SERVER_ADDR is loopback (::1 / 127.0.0.1), which
// is useless to a phone, so resolve the LAN interface directly instead.
function server_ip() {
    $addr = $_SERVER['SERVER_ADDR'] ?? '';
    if ($addr === '::1' || $addr === '127.0.0.1' || $addr === '') {
        // The adapter owning the default route is the LAN interface a phone can
        // reach. This ignores VPN adapters (Tailscale, Radmin, ...).
        $out = [];
        $code = 0;
        @exec('powershell -NoProfile -Command "if ($i = (Get-NetRoute -DestinationPrefix 0.0.0.0/0 -ErrorAction SilentlyContinue | Sort-Object RouteMetric | Select-Object -First 1).InterfaceIndex) { (Get-NetIPAddress -AddressFamily IPv4 -InterfaceIndex $i -ErrorAction SilentlyContinue).IPAddress }"', $out, $code);
        $ip = trim(implode('', $out));
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        // Last resort: hostname resolution (may return a VPN IP — harmless).
        $host = @gethostbyname(gethostname());
        if ($host && filter_var($host, FILTER_VALIDATE_IP) && $host !== '127.0.0.1') {
            return $host;
        }
    }
    return $addr ?: null;
}

$ip = server_ip();
$port = $_SERVER['SERVER_PORT'] ?? 80;

echo json_encode([
    'server_ip' => $ip,
    'server_port' => $port,
    'url' => ($ip !== null ? 'http://' . $ip . ':' . $port : null),
]);