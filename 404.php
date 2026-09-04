<!-- 404.php -->
<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | 404 Not Found</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        // Theme bootstrapper: localStorage (logged out only) -> system-aware default.
        (function () {
            var theme = null;
            try {
                var localVal = localStorage.getItem('cs_theme');
                if (localVal) {
                    try {
                        var parsed = JSON.parse(localVal);
                        if (parsed && (parsed.theme === 'light' || parsed.theme === 'dark')) theme = parsed.theme;
                    } catch (e) {
                        if (localVal === 'light' || localVal === 'dark') theme = localVal;
                    }
                }
            } catch (e) { }
            theme = theme ||
                (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
            document.documentElement.classList.toggle('dark', theme !== 'light');
            window.csThemeIsLight = theme === 'light';
        })();
    </script>
    <link rel="icon" type="image/png" href="/ClassSense/assets/classsense-logo.png">
    <link rel="stylesheet" href="/ClassSense/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script>
        window.CS_ROOT = '/ClassSense/';

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
</head>
<body class="antialiased min-h-screen overflow-x-hidden selection:bg-primary-500 selection:text-white bg-dark-bg">
    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-[10%] left-[15%] w-[500px] h-[500px] bg-primary-600/20 rounded-full mix-blend-screen filter blur-[120px] animate-blob-slow"></div>
        <div class="absolute bottom-[10%] right-[15%] w-[400px] h-[400px] bg-blue-600/10 rounded-full mix-blend-screen filter blur-[100px] animate-blob-slow" style="animation-delay: 3s"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-purple-600/5 rounded-full mix-blend-screen filter blur-[150px] animate-blob-slow" style="animation-delay: 6s"></div>
    </div>

    <div class="container mx-auto px-4 min-h-screen flex flex-col">
        <!-- Header -->
        <header class="py-8">
            <div class="flex items-center justify-center md:justify-start">
                <a href="/ClassSense/login.php" class="flex items-center space-x-3 group">
                    <img src="/ClassSense/assets/classsense-logo.png" class="w-10 h-10 rounded-lg object-cover group-hover:scale-110 transition-transform">
                    <span class="text-2xl font-bold tracking-tight text-white">ClassSense</span>
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center py-8">
            <div class="relative w-full max-w-md animate-fade-in-up">
                <div class="absolute -inset-1 bg-gradient-to-r from-primary-600/30 to-blue-600/30 rounded-2xl blur-2xl opacity-30"></div>
                <div class="relative glass-panel rounded-2xl overflow-hidden border border-white/10 shadow-2xl text-center">
                    <div class="p-8 border-b border-white/5 bg-white/5">
                        <div class="w-16 h-16 bg-primary-500/10 rounded-full flex items-center justify-center mx-auto mb-5 border border-primary-500/10">
                            <i data-feather="alert-triangle" class="w-8 h-8 text-primary-500"></i>
                        </div>
                        <h1 class="text-6xl font-black text-white mb-2 tracking-tight">404</h1>
                        <p class="text-primary-400 text-sm font-bold uppercase tracking-widest">Page Not Found</p>
                    </div>
                    <div class="p-8 space-y-6">
                        <p class="text-gray-400 text-sm leading-relaxed font-medium">The page you're looking for doesn't exist, or your account's access level no longer matches this area.</p>
                        <div class="grid grid-cols-2 gap-4">
                            <button id="tryAgainBtn" class="w-full py-4 bg-white/5 hover:bg-white/10 rounded-2xl font-black text-gray-500 hover:text-white transition-all text-xs uppercase tracking-widest leading-none flex items-center justify-center gap-2">
                                <i data-feather="refresh-cw" class="w-4 h-4"></i>
                                Try Again
                            </button>
                            <button id="logoutBtn" class="w-full py-4 bg-primary-500 hover:bg-primary-600 rounded-2xl font-black text-white transition-all shadow-lg shadow-primary-500/20 uppercase tracking-[0.2em] italic text-xs leading-none flex items-center justify-center gap-2">
                                <i data-feather="log-out" class="w-4 h-4"></i>
                                Log Out
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();
            const root = window.CS_ROOT || '/ClassSense/';

            document.getElementById('tryAgainBtn').addEventListener('click', () => {
                window.location.reload();
            });

            document.getElementById('logoutBtn').addEventListener('click', async () => {
                try {
                    await fetch(root + 'api/logout.php');
                    window.location.replace(root + 'login.php?status=session_terminated');
                } catch (err) {
                    window.location.replace(root + 'login.php?error=logout_failure');
                }
            });
        });
    </script>
</body>
</html>