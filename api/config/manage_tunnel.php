<?php
// api/config/manage_tunnel.php
// Admin-only endpoint for managing ngrok tunnel and publishing mobile endpoint URL.
// Actions: status (get current state), start (start ngrok if not running), stop (kill ngrok)

session_start();

// Security: allow PHP session admin OR bearer token with admin role
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
    $token = $m[1];
}

$allowed = false;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $allowed = true;
} elseif ($token !== '') {
    require_once __DIR__ . '/../config.php';
    try {
        $pdo = getPDO();
        // Fast path: check sessions table for the token
        $stmt = $pdo->prepare("SELECT s.uid FROM sessions s JOIN users u ON u.uid = s.uid WHERE s.token = ? AND s.expires_at > GETDATE() AND u.role = 'admin'");
        $stmt->execute([$token]);
        if ($stmt->fetch()) {
            $allowed = true;
        }
    } catch (Exception $e) {
        // Fall through to not allowed
    }
}

if (!$allowed) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized. Admin access required.']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'status';
$configFile = __DIR__ . '/app_url.json';
$ngrokExe = 'C:\\Users\\User\\AppData\\Local\\Microsoft\\WinGet\\Links\\ngrok.exe';

function check_ngrok_running() {
    $output = [];
    $code = 0;
    @exec('tasklist /FI "IMAGENAME eq ngrok.exe" 2>&1', $output, $code);
    return $code === 0 && count($output) > 1;
}

function read_ngrok_tunnel() {
    // ngrok's local API is available at 127.0.0.1:4040
    $resp = @file_get_contents('http://127.0.0.1:4040/api/tunnels');
    if (!$resp) return null;
    $data = json_decode($resp, true);
    if (!isset($data['tunnels'][0]['public_url'])) return null;
    $url = $data['tunnels'][0]['public_url'];
    // ngrok may return http:// for some tunnels; prefer https for mobile compatibility
    if (strpos($url, 'https://') === 0) return $url;
    if (strpos($url, 'http://') === 0) return 'https://' . substr($url, 7);
    return $url;
}

function server_ip() {
    $addr = $_SERVER['SERVER_ADDR'] ?? '';
    // When accessed via localhost/ngrok, SERVER_ADDR is loopback (::1 / 127.0.0.1),
    // which is useless to a phone. Resolve the machine's real LAN address instead.
    if ($addr === '::1' || $addr === '127.0.0.1' || $addr === '') {
        // The adapter owning the default route is the real LAN interface a phone
        // can reach. This ignores VPN/tunnel adapters (Tailscale, Radmin VPN, ...).
        $out = [];
        $code = 0;
        @exec('powershell -NoProfile -Command "if ($i = (Get-NetRoute -DestinationPrefix 0.0.0.0/0 -ErrorAction SilentlyContinue | Sort-Object RouteMetric | Select-Object -First 1).InterfaceIndex) { (Get-NetIPAddress -AddressFamily IPv4 -InterfaceIndex $i -ErrorAction SilentlyContinue).IPAddress }"', $out, $code);
        $ip = trim(implode('', $out));
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        // Last resort: hostname resolution (may be a VPN IP — harmless).
        $host = @gethostbyname(gethostname());
        if ($host && filter_var($host, FILTER_VALIDATE_IP) && $host !== '127.0.0.1') {
            return $host;
        }
    }
    return $addr ?: null;
}

function save_config($url, $online) {
    global $configFile;
    $config = [
        'url' => $url,
        'updated' => $online ? time() : 0,
        'online' => $online,
        'server_ip' => server_ip(),
    ];
    file_put_contents($configFile, json_encode($config));
    return $config;
}

if ($action === 'status') {
    $running = check_ngrok_running();
    if ($running) {
        $url = read_ngrok_tunnel();
        echo json_encode(save_config($url, $url !== null));
    } else {
        echo json_encode(save_config(null, false));
    }
    exit;
}

if ($action === 'start') {
    $running = check_ngrok_running();
    if ($running) {
        $url = read_ngrok_tunnel();
        $config = save_config($url, $url !== null);
        echo json_encode([
            'message' => 'ngrok was already running',
            'url' => $config['url'],
            'online' => $config['online']
        ]);
        exit;
    }

    // Start ngrok in the background.
    // On Windows, `exec('start ...')` fails in a web-server context (no console
    // session). `proc_open` creates a child process that survives PHP exit.
    // NOTE: Closing the pipes immediately prevents PHP from blocking on ngrok's
    // stdout/stderr. ngrok does not panic when its pipes are closed (as opposed
    // to being redirected to a file via `>`), because it detects the EOF and
    // falls back to its internal logging.
    $proc = proc_open(
        '"' . $ngrokExe . '" http 80',
        [["pipe", "r"], ["pipe", "w"], ["pipe", "w"]],
        $pipes
    );
    if (is_resource($proc)) {
        $procPid = proc_get_status($proc)["pid"];
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
    }

    // Wait for ngrok to initialize (give it up to 8 seconds)
    $url = null;
    for ($i = 0; $i < 8; $i++) {
        sleep(1);
        if (check_ngrok_running()) {
            $url = read_ngrok_tunnel();
            if ($url !== null) break;
        }
    }

    if ($url !== null) {
        $config = save_config($url, true);
        echo json_encode([
            'message' => 'ngrok started successfully',
            'url' => $url,
            'online' => true
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'error' => 'ngrok process started but tunnel URL could not be read. Check if ngrok is properly authenticated and port 80 is available.',
            'running' => check_ngrok_running()
        ]);
    }
    exit;
}

if ($action === 'stop') {
    @exec('taskkill /F /IM ngrok.exe 2>&1');
    echo json_encode(save_config(null, false));
    exit;
}

echo json_encode(['error' => 'Unknown action. Use ?action=status|start|stop']);
