<?php
// Public endpoint — NO authentication required
// Mobile app calls this to discover the current server URL.
// Allows CORS from any origin (mobile app + web admin both need this)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$configFile = __DIR__ . '/app_url.json';

// Build response
$config = ['url' => null, 'updated' => 0, 'online' => false];

if (file_exists($configFile)) {
    $saved = json_decode(file_get_contents($configFile), true);
    if (is_array($saved)) {
        $config = array_merge($config, $saved);
    }
}

// Include the server's local IP for discovery by physical devices.
// On a LAN, phones can reach this IP directly (no ngrok needed for config fetch).
function server_ip() {
    $addr = $_SERVER['SERVER_ADDR'] ?? '';
    // When accessed via localhost/ngrok, SERVER_ADDR is loopback (::1 / 127.0.0.1),
    // which is useless to a phone. Resolve the machine's real LAN address instead.
    if ($addr === '::1' || $addr === '127.0.0.1' || $addr === '') {
        // The adapter owning the default route is the LAN interface a phone can
        // reach. This ignores VPN/tunnel adapters (Tailscale, Radmin, ...).
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

$config['server_ip'] = server_ip();
$config['server_port'] = $_SERVER['SERVER_PORT'] ?? 80;

echo json_encode($config);
