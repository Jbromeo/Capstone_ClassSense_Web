<?php
// includes/head.php
require_once dirname(__DIR__) . '/core/init.php'; 

// Dynamically handle asset paths based on folder depth
$pathPrefix = (strpos($_SERVER['SCRIPT_NAME'], 'admin_screen') !== false || 
               strpos($_SERVER['SCRIPT_NAME'], 'student_screen') !== false || 
               strpos($_SERVER['SCRIPT_NAME'], 'teacher_screen') !== false) ? '../' : '';

// Per-account theme: resolve the logged-in user's DB preference server-side
// ('' = never chosen -> system-aware fallback in the bootstrapper). Self-
// contained PDO so a DB hiccup can never take down the page rendering.
$csThemeServer = '';
$csThemeUid = $_SESSION['uid'] ?? null;
if ($csThemeUid) {
    try {
        $csConfigPath = dirname(__DIR__) . '/api/config.json';
        if (file_exists($csConfigPath)) {
            $csCfg = json_decode(file_get_contents($csConfigPath), true);
            if ($csCfg) {
                $csPdo = new PDO('sqlsrv:Server=' . $csCfg['db_host'] . ';Database=' . $csCfg['db_name'], $csCfg['db_user'], $csCfg['db_pass']);
                $csStmt = $csPdo->prepare("SELECT theme FROM users WHERE uid = ?");
                $csStmt->execute([$csThemeUid]);
                $csVal = $csStmt->fetchColumn();
                if ($csVal === 'light' || $csVal === 'dark') $csThemeServer = $csVal;
            }
        }
    } catch (Throwable $e) {
        // Theme is cosmetic — never block the page on a lookup failure.
    }
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>
    // Theme bootstrapper: DB preference (logged in) -> localStorage (logged
    // out only) -> system-aware default. Runs before first paint so there is
    // no theme flash.
    (function () {
        var serverTheme = '<?php echo $csThemeServer; ?>';
        var isLoggedIn = <?php echo $csThemeUid ? 'true' : 'false'; ?>;
        var uid = <?php echo $csThemeUid ? json_encode($csThemeUid) : 'null'; ?>;
        try {
            var theme = serverTheme;
            if (!theme && !isLoggedIn) {
                var localVal = localStorage.getItem('cs_theme');
                if (localVal) {
                    try {
                        var parsed = JSON.parse(localVal);
                        if (parsed && (parsed.theme === 'light' || parsed.theme === 'dark')) theme = parsed.theme;
                    } catch (e) {
                        if (localVal === 'light' || localVal === 'dark') theme = localVal;
                    }
                }
            }
            theme = theme ||
                (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
            document.documentElement.classList.toggle('dark', theme !== 'light');
            window.csThemeIsLight = theme === 'light';
            window.csThemeUid = uid;
        } catch (e) {
            document.documentElement.classList.add('dark');
            window.csThemeIsLight = false;
            window.csThemeUid = uid;
        }
    })();
</script>
<link rel="icon" type="image/png" href="<?php echo $pathPrefix; ?>assets/classsense-logo.png">
<link rel="stylesheet" href="<?php echo $pathPrefix; ?>style.css?v=<?php echo time(); ?>">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>
<script>
    // Global Configuration for JavaScript stability across folders
    window.CS_ROOT = '<?php echo ROOT_URL; ?>';
    
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    primary: { DEFAULT: '#ea2628', 50: '#fef2f2', 100: '#fee2e2', 500: '#ea2628', 600: '#dc2626', 700: '#b91c1c', 900: '#7f1d1d' },
                    secondary: { 500: '#9d8989', 600: '#826a6a' },
                    dark: { bg: '#0f1115', surface: '#181b21', border: '#2a2e35' }
                },
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
            }
        }
    };
</script>

<!-- 🛡️ Global Identity Orchestrator: Syncs PHP Session with Firebase Auth project-wide -->
<script type="module" src="<?php echo $pathPrefix; ?>assets/js/auth_controller.js?v=<?php echo time(); ?>"></script>
