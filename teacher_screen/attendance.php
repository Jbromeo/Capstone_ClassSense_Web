<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- attendance.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | Attendance</title>
    <?php include '../includes/head.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        .qr-placeholder {
            background: radial-gradient(circle, #000 10%, transparent 10%),
                        radial-gradient(circle, #000 10%, transparent 10%);
            background-size: 15px 15px;
            background-position: 0 0, 7.5px 7.5px;
        }
    </style>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-purple-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 4s"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LCAyNTUsIDI1NSwgMC4wNSkiLz48L3N2Zz4=')] [mask-image:linear-gradient(to_bottom,white,transparent)]"></div>
    </div>

    <?php include 'sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        <!-- Header -->
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                    <i data-feather="menu"></i>
                </button>
                <h2 class="text-xl font-bold text-white hidden sm:block">Attendance Manager</h2>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative hidden md:block group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i data-feather="search" class="h-4 w-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i>
                    </div>
                    <input type="text" 
                           class="bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-full focus:ring-primary-500 focus:border-primary-500 block w-64 pl-10 p-2.5 transition-all focus:w-80 placeholder-gray-600" 
                           placeholder="Search students...">
                </div>

                <div class="relative">
                    <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors group">
                        <i data-feather="bell"></i>
                        <span class="notif-dot hidden absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                    </button>
                    <?php include '../includes/notification_popover.php'; ?>
                </div>
            </div>
        </header>

        <!-- Scrollable Content -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8 relative">
            
            <!-- VIEW 1: Class Selection -->
            <div id="classSelectionView" class="animate-fade-in-up">
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-white mb-1 italic">Select Class</h1>
                    <p class="text-sm text-gray-400 font-medium uppercase tracking-tighter opacity-60 italic">Choose a class to start the live attendance session.</p>
                </div>

                <!-- Session window info (replaces the manual timer buttons) -->
                <div class="mb-4 flex items-center justify-center gap-6 animate-fade-in-up" style="animation-delay: 100ms">
                    <span id="sessionWindowLabel" class="text-[11px] font-black text-gray-400 uppercase tracking-widest italic opacity-80">30-Second On-Time Window</span>
                </div>

                <!-- NEW: GPS Geofence Setting -->
                <div class="mb-10 flex items-center justify-center gap-6 animate-fade-in-up" style="animation-delay: 150ms">
                    <label class="relative inline-flex items-center cursor-pointer group select-none">
                        <input type="checkbox" id="requireLocationToggle" class="sr-only peer">
                        <div class="w-11 h-6 bg-dark-bg border border-dark-border rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-gray-500 after:border-dark-border after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-500/30 peer-checked:border-primary-500/50 peer-checked:after:bg-primary-400"></div>
                        <span class="ml-3 text-[10px] font-black text-gray-400 uppercase tracking-widest italic group-hover:text-white transition-colors">Require GPS Location</span>
                    </label>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic opacity-60">Radius:</span>
                    <input type="number" id="sessionRadiusInput" value="150" min="50" max="2000" class="w-24 bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic opacity-60">meters</span>
                </div>

                <!-- Start Attendance Bar (revealed when a class is selected) -->
                <div id="startAttendanceBar" class="hidden mb-6 sticky top-0 z-30 animate-fade-in-up" style="animation-delay: 100ms">
                    <div class="glass-panel rounded-2xl p-4 border-primary-500/40 shadow-xl shadow-primary-500/5 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="p-2.5 bg-primary-500/10 rounded-xl shrink-0">
                                <i data-feather="book-open" class="w-5 h-5 text-primary-500"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 id="selectedClassName" class="text-base font-black text-white uppercase tracking-tighter italic truncate">Class Name</h3>
                                    <span id="selectedClassLiveBadge" class="hidden items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-green-500/10 text-green-400 border border-green-500/30 italic"><span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> LIVE</span>
                                </div>
                                <p id="selectedClassMeta" class="text-[10px] text-gray-400 font-medium uppercase tracking-widest opacity-60 truncate">Subject &bull; Section</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span id="gradingTermBadge" title="Term is set in the Grading Center" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-500/10 border border-primary-500/20 text-primary-400 text-[9px] font-black uppercase tracking-widest italic whitespace-nowrap">Recording to 1st Term</span>
                            <span id="selectedClassCode" class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic"></span>
                            <button id="startAttendanceBtn" onclick="window.startSelectedClass()" class="flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 active:scale-95 text-white font-black uppercase tracking-widest italic text-xs rounded-xl shadow-lg shadow-primary-500/25 transition-all">
                                <i data-feather="play" class="w-4 h-4"></i> Start Attendance
                            </button>
                            <button onclick="window.clearSelection()" class="p-2.5 bg-dark-bg hover:bg-white/10 rounded-xl text-gray-400 hover:text-white transition-colors" title="Clear selection">
                                <i data-feather="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="classSelectionGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div class="col-span-full py-20 text-center opacity-40">
                        <div class="animate-pulse space-y-4">
                            <div class="glass-panel h-48 w-full rounded-2xl mx-auto"></div>
                            <p class="text-[10px] font-black uppercase tracking-widest italic tracking-tighter">Syncing Teaching Registry...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 2: Live Attendance -->
            <?php include 'attendance/live_session.php'; ?>

            <!-- VIEW 3: Session Summary -->
            <?php include 'attendance/session_report.php'; ?>

        </main>
    </div>

    <?php include 'attendance/confirm_modal.php'; ?>

    <div id="toastContainer" class="fixed top-5 right-5 z-[100] flex flex-col gap-3"></div>
    <script>
        window.showToast = (message, type = 'success') => {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const isErr = type === 'error', isInfo = type === 'info';
            const toast = document.createElement('div');
            toast.className = `flex items-center w-full max-w-xs p-4 text-gray-200 bg-dark-bg border border-white/5 rounded-xl shadow-2xl animate-fade-in ${isErr ? 'border-l-4 border-l-primary-500' : isInfo ? 'border-l-4 border-l-blue-500' : 'border-l-4 border-l-green-500'}`;
            toast.innerHTML = `<div class="flex-shrink-0"><i data-feather="${isErr ? 'alert-circle' : isInfo ? 'info' : 'check-circle'}" class="w-4 h-4 ${isErr ? 'text-primary-500' : isInfo ? 'text-blue-500' : 'text-green-500'}"></i></div><div class="ml-3 text-[10px] font-black uppercase italic tracking-widest">${message}</div>`;
            container.appendChild(toast);
            if (window.feather) { try { feather.replace(); } catch (e) {} }
            setTimeout(() => { toast.classList.add('opacity-0'); setTimeout(() => toast.remove(), 500); }, 3000);
        };
    </script>

    <script type="module" src="attendance/attendance.js?v=12"></script>
</body>
</html>