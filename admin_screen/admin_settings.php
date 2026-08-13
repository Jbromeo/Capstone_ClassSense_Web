<!-- admin_screen/admin_settings.php -->
<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense Admin | System Settings</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-purple-500 selection:text-white bg-dark-bg">

    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-[25%] w-[600px] h-[600px] bg-purple-600/5 rounded-full mix-blend-screen filter blur-3xl animate-blob-slow transform -translate-y-1/2"></div>
    </div>

    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <header class="h-20 glass-panel border-b border-white/5 flex items-center justify-between px-8 z-20">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-black text-white tracking-tighter uppercase leading-none">System Settings <span class="text-[10px] text-purple-400 font-bold ml-4 uppercase tracking-[0.2em] opacity-60">Control Center</span></h2>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-3xl mx-auto">

                <!-- System Preferences -->
                <div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
                    <div class="px-8 py-6 border-b border-dark-border flex items-center gap-3">
                        <div class="p-2.5 bg-purple-500/10 rounded-xl text-purple-500">
                            <i data-feather="settings" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">System Preferences</h3>
                            <p class="text-[10px] text-gray-500 uppercase tracking-widest italic font-bold">Personalize your admin console</p>
                        </div>
                    </div>

                    <div class="p-8 space-y-6">
                        <div class="flex items-center justify-between p-4 bg-dark-bg/40 rounded-xl border border-dark-border">
                            <div>
                                <h4 class="text-white font-medium">Dark Mode</h4>
                                <p class="text-sm text-gray-500">Toggle between light and dark themes.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="darkModeToggle" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-purple-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                            </label>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>

    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const isErr = type === 'error', isInfo = type === 'info';
            const toast = document.createElement('div');
            toast.className = `toast flex items-center w-full max-w-xs p-4 text-gray-200 bg-dark-surface rounded-xl shadow-2xl border border-dark-border ${isErr ? 'border-l-4 border-l-primary-500' : isInfo ? 'border-l-4 border-l-blue-500' : 'border-l-4 border-l-green-500'}`;
            toast.innerHTML = `<div class="flex-shrink-0"><i data-feather="${isErr ? 'alert-circle' : isInfo ? 'info' : 'check-circle'}" class="w-4 h-4 ${isErr ? 'text-primary-500' : isInfo ? 'text-blue-500' : 'text-green-500'}"></i></div><div class="ml-3 text-[10px] font-black uppercase italic tracking-widest">${message}</div>`;
            container.appendChild(toast);
            if (window.feather) { try { feather.replace(); } catch (e) {} }
            setTimeout(() => { toast.classList.add('opacity-0'); setTimeout(() => toast.remove(), 500); }, 3000);
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
    </script>
    <script src="../assets/js/theme-toggle.js" defer></script>
</body>
</html>
