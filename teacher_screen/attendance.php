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

                <button class="relative p-2 text-gray-400 hover:text-white transition-colors">
                    <i data-feather="bell"></i>
                </button>
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
                    <span id="sessionWindowLabel" class="text-[11px] font-black text-gray-400 uppercase tracking-widest italic opacity-80">Session Limit:</span>
                </div>

                <!-- NEW: GPS Geofence Setting -->
                <div class="mb-10 flex items-center justify-center gap-6 animate-fade-in-up" style="animation-delay: 150ms">
                    <label class="flex items-center gap-3 cursor-pointer select-none group">
                        <input type="checkbox" id="requireLocationToggle" class="peer sr-only">
                        <span class="w-10 h-6 bg-dark-bg border border-dark-border rounded-full relative transition-colors peer-checked:bg-primary-500/30 peer-checked:border-primary-500/50">
                            <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-gray-500 rounded-full transition-all peer-checked:left-[18px] peer-checked:bg-primary-400"></span>
                        </span>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic group-hover:text-white transition-colors">Require GPS Location</span>
                    </label>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic opacity-60">Radius:</span>
                    <input type="number" id="sessionRadiusInput" value="150" min="50" max="2000" class="w-24 bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic opacity-60">meters</span>
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

            <!-- VIEW 2: Live Attendance (Hidden by default) -->
            <div id="liveAttendanceView" class="hidden h-full flex flex-col">
                <!-- Top Bar -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <button onclick="confirmEndSession()" class="p-2 bg-dark-bg hover:bg-white/10 rounded-lg text-gray-400 hover:text-white transition-colors">
                            <i data-feather="arrow-left" class="w-5 h-5"></i>
                        </button>
                        <div>
                            <h2 id="liveClassName" class="text-xl font-bold text-white">CS101: Intro to Programming</h2>
                            <div class="flex items-center gap-4">
                                <p class="text-xs text-green-400 flex items-center gap-1">
                                    <span id="liveModeDot" class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                    <span id="liveModeLabel">Live Session Active</span>
                                </p>
                                <div id="sessionCountdown" class="hidden flex items-center gap-2 px-3 py-1 bg-primary-500/10 border border-primary-500/20 rounded-full">
                                    <i data-feather="clock" class="w-3 h-3 text-primary-500"></i>
                                    <span id="timerValue" class="text-[10px] font-black text-white italic tracking-widest">--:--</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button id="lateWindowBtn" onclick="startLateWindow()" class="flex items-center gap-2 px-4 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-lg transition-colors">
                            <i data-feather="clock" class="w-4 h-4"></i>
                            LATE WINDOW
                        </button>
                        <button onclick="confirmEndSession()" class="flex items-center gap-2 px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 rounded-lg transition-colors">
                            <i data-feather="stop-circle" class="w-4 h-4"></i>
                            End Session
                        </button>
                    </div>
                </div>

                <!-- Live Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 h-full pb-8">
                    
                    <!-- Left Column: QR Code & Stats -->
                    <div class="glass-panel rounded-2xl p-8 flex flex-col items-center justify-between relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-primary-500 to-transparent opacity-50"></div>
                        
                        <h3 id="liveModeTitle" class="text-lg font-bold text-white">Scan to Join</h3>
                        
                        <!-- QR Code Container -->
                        <div class="relative w-48 h-48 bg-white rounded-2xl p-4 shadow-2xl shadow-primary-500/20 flex items-center justify-center">
                            <div id="qrcode" class="w-full h-full flex items-center justify-center overflow-hidden">
                                <div class="w-full h-full qr-placeholder opacity-50 flex items-center justify-center text-gray-800 text-[10px] font-black uppercase text-center italic">Generating Secure Hub...</div>
                            </div>
                            <div id="scanLine" class="absolute top-0 left-0 w-full h-1 bg-red-500/50 shadow-[0_0_15px_rgba(220,38,38,0.8)] animate-scan-line hidden"></div>
                            <div class="absolute top-2 left-2 w-6 h-6 border-t-4 border-l-4 border-gray-800 rounded-tl-lg"></div>
                            <div class="absolute top-2 right-2 w-6 h-6 border-t-4 border-r-4 border-gray-800 rounded-tr-lg"></div>
                            <div class="absolute bottom-2 left-2 w-6 h-6 border-b-4 border-l-4 border-gray-800 rounded-bl-lg"></div>
                            <div class="absolute bottom-2 right-2 w-6 h-6 border-b-4 border-r-4 border-gray-800 rounded-br-lg"></div>
                        </div>

                        <p class="text-center text-sm text-gray-400">
                            Students must scan this code via the ClassSense Mobile App to verify their identity.
                        </p>

                        <!-- Mini Stats -->
                        <div class="w-full grid grid-cols-2 gap-4">
                            <div class="bg-dark-bg rounded-lg p-4 text-center">
                                <div id="presentCount" class="text-3xl font-bold text-green-400">0</div>
                                <div class="text-[10px] uppercase text-gray-500 font-bold tracking-widest">Present</div>
                            </div>
                            <div class="bg-dark-bg rounded-lg p-4 text-center">
                                <div id="totalCount" class="text-3xl font-bold text-gray-300">0</div>
                                <div class="text-[10px] uppercase text-gray-500 font-bold tracking-widest">Total</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Spotlight / Idle List -->
                    <div class="lg:col-span-2 glass-panel rounded-2xl h-full flex flex-col relative border border-dark-border bg-gradient-to-b from-dark-surface to-dark-bg overflow-hidden">
                        
                        <!-- 1. Idle Empty State (Shown when 0 students & no active scan) -->
                        <div id="idleEmptyState" class="absolute inset-0 flex flex-col items-center justify-center text-center opacity-40 animate-pulse z-10">
                            <i data-feather="user-plus" class="w-16 h-16 mx-auto mb-4 text-gray-500"></i>
                            <h3 class="text-xl font-bold text-gray-400">Waiting for scan...</h3>
                            <p class="text-sm text-gray-600 mt-2">Live feed will appear here</p>
                        </div>
                        
                        <!-- 2. Idle List State (Shown when > 0 students & no active scan) -->
                        <div id="idleListState" class="hidden absolute inset-0 flex flex-col z-10 p-6">
                            <div class="flex items-center justify-between mb-4 pb-2 border-b border-dark-border">
                                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider">Class List</h3>
                                <span class="text-xs text-primary-400 animate-pulse">Waiting for next scan...</span>
                            </div>
                            <div id="liveRosterList" class="flex-1 overflow-y-auto space-y-3 pr-2">
                                <!-- Populated by JS -->
                            </div>
                        </div>

                        <!-- 3. Spotlight Content (Shown when scanning) -->
                        <div id="spotlightContent" class="hidden absolute inset-0 flex flex-col items-center justify-center z-20 p-8 bg-dark-surface/95 backdrop-blur-sm">
                            
                            <!-- Large Avatar Container -->
                            <div class="relative mb-8">
                                <div class="w-64 h-64 rounded-full overflow-hidden border-4 border-dark-bg shadow-2xl shadow-primary-500/20 relative">
                                    <img id="spotlightAvatar" src="" class="w-full h-full object-cover">
                                    
                                    <!-- Overlay Vignette -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                                </div>

                                <!-- VERIFIED BADGE -->
                                <div class="absolute -bottom-2 right-4 bg-green-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg animate-bounce flex items-center gap-2">
                                    <i data-feather="check" class="w-3 h-3"></i> VERIFIED
                                </div>
                            </div>

                            <!-- Text Info -->
                            <h2 id="spotlightName" class="text-5xl font-bold text-white mb-2 tracking-tight drop-shadow-lg">Student Name</h2>
                            <p id="spotlightTime" class="text-xl text-gray-300 font-light mb-8">Scanned at 00:00</p>

                            <!-- Countdown Bar -->
                            <div class="w-full max-w-2xl bg-dark-bg h-3 rounded-full overflow-hidden border border-white/5">
                                <div id="timerBar" class="h-full bg-gradient-to-r from-primary-600 to-primary-400 animate-timer-shrink shadow-[0_0_10px_rgba(234,38,40,0.5)]"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 3: Session Summary (Hidden by default) -->
            <div id="sessionSummaryView" class="hidden animate-fade-in-up pb-8">
                <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 text-gray-400 text-sm mb-1">
                            <span class="hover:text-white cursor-pointer" onclick="goToClassSelection()">Classes</span>
                            <i data-feather="chevron-right" class="w-4 h-4"></i>
                            <span class="hover:text-white cursor-pointer" onclick="goToLiveView()">Live Session</span>
                            <i data-feather="chevron-right" class="w-4 h-4"></i>
                            <span class="text-white">Report</span>
                        </div>
                        <h1 class="text-3xl font-bold text-white">Session Report</h1>
                        <p id="reportClassTitle" class="text-gray-400">CS101: Intro to Programming</p>
                    </div>
                    
                    <div class="flex gap-3">
                         <div class="glass-panel px-6 py-3 rounded-xl flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-[10px] uppercase text-gray-500 font-bold tracking-wider">Present</p>
                                <p id="finalPresentCount" class="text-2xl font-bold text-green-400">0</p>
                            </div>
                            <div class="w-px h-8 bg-white/10"></div>
                            <div class="text-right">
                                <p class="text-[10px] uppercase text-gray-500 font-bold tracking-wider">Late</p>
                                <p id="finalLateCount" class="text-2xl font-bold text-amber-400">0</p>
                            </div>
                            <div class="w-px h-8 bg-white/10"></div>
                            <div class="text-right">
                                <p class="text-[10px] uppercase text-gray-500 font-bold tracking-wider">Status</p>
                                <p class="text-xl font-bold text-white">Ended</p>
                            </div>
                         </div>
                         <button onclick="goToClassSelection()" class="px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-primary-500/25 flex items-center gap-2">
                            <i data-feather="check"></i> Done
                         </button>
                    </div>
                </div>

                <!-- Summary List -->
                <div class="glass-panel rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-dark-border text-xs uppercase text-gray-500 font-bold tracking-wider bg-dark-bg/30">
                                    <th class="p-4 pl-6">Student</th>
                                    <th class="p-4">ID</th>
                                    <th class="p-4">Scan Time</th>
                                    <th class="p-4">Status</th>
                                </tr>
                            </thead>
                            <tbody id="summaryTableBody" class="text-sm">
                                <!-- JS Populated -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';

        let currentTeacher = null;
        let attendanceListener = null;
        let spotlightTimeout = null;
        let currentClassData = null;
        let verifiedStudentsList = []; // Array of { name, id, avatar, time }
        let processedUids = new Set();
        
        const now = new Date();
        const TODAY_STR = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        const nowSql = () => {
            const d = new Date();
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
        };
        let sessionTimerInterval = null;
        let qrRefreshInterval = null;
        let currentNonce = null;
        let currentMode = 'open'; // 'open' | 'late'
        let flagMap = new Map();  // student_uid -> [fraud reason strings]
        const LATE_WINDOW_SECONDS = 180; // 3-minute late-arrivals window
        const NONCE_GRACE_SECONDS = 25;  // previous nonce accepted within this window
        const POLL_INTERVAL_MS = 3000;   // live attendance poll: scan appears within ~3s

        const randNonce = () => Math.random().toString(36).substring(2, 10).toUpperCase();
        const parseSql = (s) => s ? new Date(s.replace(' ', 'T')) : null;
        const formatSqlTime = (s) => {
            const t = (s || '').trim();
            if (!t) return '';
            const d = new Date(t.replace(' ', 'T'));
            if (isNaN(d.getTime())) return t;
            let h = d.getHours(), m = String(d.getMinutes()).padStart(2, '0');
            const ampm = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12;
            return `${h}:${m} ${ampm}`;
        };
        const sqlFromDate = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
        const isClassLive = (c) => {
            if (c.session_active != 1) return false;
            const expiresAt = parseSql(c.session_expires_at);
            if (!expiresAt) return true;
            return expiresAt > new Date();
        };
        const getLocation = () => new Promise((resolve) => {
            if (!navigator.geolocation) return resolve(null);
            navigator.geolocation.getCurrentPosition(
                (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
                () => resolve(null),
                { timeout: 8000, maximumAge: 30000 }
            );
        });

        function setModeUI(mode) {
            currentMode = mode;
            const late = mode === 'late';
            document.getElementById('lateWindowBtn').classList.toggle('hidden', late);
            document.getElementById('liveModeTitle').innerText = late ? 'Late Arrivals Window' : 'Scan to Join';
            document.getElementById('liveModeLabel').innerText = late ? 'LATE WINDOW ACTIVE' : 'Live Session Active';
            const dot = document.getElementById('liveModeDot');
            dot.classList.toggle('bg-green-500', !late);
            dot.classList.toggle('bg-amber-500', late);
            const countdown = document.getElementById('sessionCountdown');
            countdown.classList.toggle('bg-amber-500/10', late);
            countdown.classList.toggle('border-amber-500/20', late);
            countdown.classList.toggle('bg-primary-500/10', !late);
            countdown.classList.toggle('border-primary-500/20', !late);
        }


        // 1. Identity Handshake
        initPage((user) => {
            console.log("Attendance: Direct Auth Fallback");
            setTimeout(() => initClassSelection(user.uid), 500);
        });

        async function initClassSelection(teacherUid) {
            const grid = document.getElementById('classSelectionGrid');
            if (!grid) return;
            
            try {
                // 🛡️ Safety Check: Ensure we have a UID
                const uid = teacherUid || currentTeacher?.uid;
                if (!uid) {
                    console.warn("Attendance: No UID available for registry fetch.");
                    return;
                }

                console.log("Attendance: Initializing Registry for UID:", uid);
                const classes = await api('/classes.php');
                
                if (classes.length === 0) {
                    grid.innerHTML = `<div class="col-span-full py-20 text-center opacity-40 italic text-gray-500">No classes found. Please create one in the Dashboard.</div>`;
                    feather.replace();
                    return;
                }

                grid.innerHTML = classes.map(c => {
                    const live = isClassLive(c);
                    const win = c.window || {};
                    const canOpen = live || !!win.startable;
                    const locked = !live && !win.startable;
                    const nextLabel = win.nextOpenLabel || (win.windowLabel ? ('Outside ' + win.windowLabel) : 'Not scheduled');
                    return `
                    <div class="glass-panel p-6 rounded-xl border ${live ? 'border-green-500/40' : (locked ? 'border-gray-600/40' : 'border-dark-border')} hover:border-primary-500/50 transition-all ${locked ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer group'}">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-3 bg-primary-500/10 rounded-lg">
                                <i data-feather="book-open" class="w-6 h-6 text-primary-500"></i>
                            </div>
                            <span class="flex items-center gap-2">
                                ${live ? `<span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-green-500/10 text-green-400 border border-green-500/30 italic"><span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> LIVE</span>` : ''}
                                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic">${c.class_code}</span>
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-white mb-1 uppercase tracking-tighter italic">${c.class_name}</h3>
                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-widest mb-4 opacity-60">${c.subject} &bull; ${c.section_code}</p>
                        <div class="flex items-center gap-2 text-[10px] font-black text-primary-400 uppercase tracking-widest italic">
                            <span>${live ? 'Resume Live Session' : (locked ? 'Locked' : 'Initialize Hub')}</span>
                            ${locked ? `<span class="text-[9px] font-black text-gray-400 uppercase opacity-60">(${nextLabel})</span>` : ''}
                            <i data-feather="arrow-right" class="w-3 h-3 ${locked ? '' : 'group-hover:translate-x-1'} transition-transform"></i>
                        </div>
                    </div>`;
                }).join('');
                feather.replace();
            } catch (error) {
                console.error("Attendance Sync Error:", error);
                grid.innerHTML = `
                    <div class="col-span-full py-20 text-center">
                        <i data-feather="alert-octagon" class="w-12 h-12 mx-auto mb-4 text-primary-500 animate-pulse"></i>
                        <p class="text-xs font-black uppercase tracking-widest italic text-primary-400">Registry Sync Denied</p>
                        <p class="text-[10px] text-gray-500 mt-2 font-mono">${error.message}</p>
                    </div>`;
                feather.replace();
            }
        }

        // 2. Start / Resume Live Session
        window.startAttendanceSession = async (classId) => {
            try {
                // Fetch class details (now includes .window)
                currentClassData = await api('/classes.php?id=' + classId);
                if (!currentClassData) return;

                const win = currentClassData.window || {};
                const live = isClassLive(currentClassData);
                const labelEl = document.getElementById('sessionWindowLabel');
                if (win.windowLabel) labelEl.innerText = win.windowLabel;

                // Gate: if not already live, the schedule window must be open.
                if (!live && !win.startable) {
                    labelEl.innerText = win.nextOpenLabel || 'Locked';
                    switchView('classSelectionView');
                    alert(win.nextOpenLabel
                        ? ('Attendance only opens during the class window: ' + (win.windowLabel || '') +
                           '\n\nNext session: ' + win.nextOpenLabel)
                        : 'This class has no schedule set; contact your administrator.');
                    return;
                }

                // Switch View
                switchView('liveAttendanceView');
                document.getElementById('liveClassName').innerText = currentClassData.class_name;
                document.getElementById('reportClassTitle').innerText = currentClassData.class_name;
                document.getElementById('totalCount').innerText = currentClassData.students ? currentClassData.students.length : 0;
                
                // Clear State
                verifiedStudentsList = [];
                processedUids.clear();
                document.getElementById('presentCount').innerText = '0';
                document.getElementById('liveRosterList').innerHTML = '';
                document.getElementById('spotlightContent').classList.add('hidden');
                document.getElementById('idleListState').classList.add('hidden');
                document.getElementById('idleEmptyState').classList.remove('hidden');

                // RESUME: class already has a live (unexpired) session — attach to it
                // instead of restarting.
                if (live) {
                    currentNonce = currentClassData.current_nonce;
                    setModeUI(currentClassData.session_mode === 'late' ? 'late' : 'open');
                    const remainingSec = Math.max(0, Math.floor((parseSql(currentClassData.session_expires_at) - new Date()) / 1000));
                    generateAttendanceQR(classId);
                    startQRRefreshCycle(classId);
                    initAttendanceListener(classId);
                    labelEl.innerText = (currentClassData.session_expires_at ? 'Until ' + formatSqlTime(currentClassData.session_expires_at) : 'Live session');
                    if (remainingSec > 0) {
                        startSessionCountdownFrom(remainingSec);
                    } else {
                        document.getElementById('sessionCountdown').classList.add('hidden');
                    }
                    return;
                }

                // FRESH START: capture the GPS anchor BEFORE opening the session
                const requireLocation = document.getElementById('requireLocationToggle').checked;
                const radius = Math.max(50, parseInt(document.getElementById('sessionRadiusInput').value) || 150);
                let anchor = null;
                if (requireLocation) {
                    anchor = await getLocation();
                    if (!anchor) {
                        alert('GPS location is required but unavailable. Check browser location permissions and try again.');
                        switchView('classSelectionView');
                        return;
                    }
                }

                // Generate nonce + start session via API. Session expiry is owned by
                // the server (= class-window end); we do not send a TTL client-side.
                const nonce = randNonce();
                currentNonce = nonce;
                const started = nowSql();

                const body = {
                    session_active: 1,
                    session_started_at: started,
                    current_nonce: nonce,
                    nonce_issued_at: started,
                    session_mode: 'open',
                    require_location: requireLocation ? 1 : 0
                };
                if (anchor) {
                    body.session_lat = anchor.lat;
                    body.session_lng = anchor.lng;
                    body.session_radius_m = radius;
                }

                await api('/classes.php?id=' + classId, {
                    method: 'PUT',
                    body: JSON.stringify(body)
                });

                setModeUI('open');

                // Generate QR Code (with the live nonce)
                generateAttendanceQR(classId);

                // Refresh to read the server-authoritative session_expires_at
                // (window end) and count down to it. A 0/Live session runs until
                // the teacher stops it manually.
                const refreshed = await api('/classes.php?id=' + classId);
                const exp = refreshed.session_expires_at ? parseSql(refreshed.session_expires_at) : null;
                labelEl.innerText = win.windowLabel || 'Live session';
                if (exp) {
                    labelEl.innerText = 'Until ' + formatSqlTime(refreshed.session_expires_at);
                    startSessionCountdownFrom(Math.max(0, Math.floor((exp - new Date()) / 1000)));
                } else {
                    document.getElementById('sessionCountdown').classList.add('hidden');
                }

                // Start QR Refresh Cycle
                startQRRefreshCycle(classId);

                // Start Listener
                initAttendanceListener(classId);
            } catch (err) {
                console.error("Session Init Failure:", err);
            }
        };

        function initAttendanceListener(classId) {
            if (attendanceListener) clearInterval(attendanceListener);
            
            async function pollAttendance() {
                try {
                    const records = await api('/attendance.php?class_id=' + classId + '&date=' + TODAY_STR);
                    for (const record of records) {
                        if (!processedUids.has(record.student_uid)) {
                            processedUids.add(record.student_uid);
                            await processNewAttendance(record);
                        }
                    }
                    renderFraudFlags(records);
                } catch (err) {
                    console.error("Attendance Poll Error:", err);
                }
            }
            
            pollAttendance();
            attendanceListener = setInterval(pollAttendance, POLL_INTERVAL_MS);

            // Immediate catch-up when the tab regains focus (browsers throttle
            // setInterval to ~1/min in background tabs, which otherwise hides
            // recent scans until the next throttled tick).
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && typeof pollAttendance === 'function') {
                    pollAttendance();
                }
            });
        }

        function generateAttendanceQR(classId) {
            const qrContainer = document.getElementById('qrcode');
            qrContainer.innerHTML = '';
            
            // Re-show Scan Line
            document.getElementById('scanLine').classList.remove('hidden');

            // v2 payload: t = unix issue time, lets the app reject stale codes instantly
            new QRCode(qrContainer, {
                text: JSON.stringify({ 
                    v: 2,
                    classId: classId, 
                    type: 'attendance', 
                    date: TODAY_STR,
                    nonce: currentNonce,
                    t: Math.floor(Date.now() / 1000)
                }),
                width: 160,
                height: 160,
                colorDark: currentMode === 'late' ? "#B45309" : "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        function startQRRefreshCycle(classId) {
            if (qrRefreshInterval) clearInterval(qrRefreshInterval);
            qrRefreshInterval = setInterval(async () => {
                const newNonce = randNonce();
                const prevNonce = currentNonce;
                currentNonce = newNonce;
                
                try {
                    await api('/classes.php?id=' + classId, {
                        method: 'PUT',
                        body: JSON.stringify({ 
                            current_nonce: newNonce,
                            nonce_issued_at: nowSql(),
                            last_nonce: prevNonce
                        })
                    });
                    generateAttendanceQR(classId);
                    console.log("QR Refreshed with Nonce:", newNonce);
                } catch (err) {
                    console.error("QR Refresh Failure:", err);
                }
            }, 10000); // Refresh every 10 seconds
        }

        // 2b. Late Arrivals Window: switch the projected QR to a late-only code.
        // Every scan during the window is recorded with status 'Late' by the server.
        window.startLateWindow = async () => {
            if (!currentClassData || currentMode === 'late') return;
            if (!confirm('Open the 3-minute Late Arrivals window?\nScans of the new code will be recorded as LATE.')) return;

            const nonce = randNonce();
            currentNonce = nonce;

            try {
                await api('/classes.php?id=' + currentClassData.id, {
                    method: 'PUT',
                    body: JSON.stringify({
                        session_mode: 'late',
                        current_nonce: nonce,
                        nonce_issued_at: nowSql(),
                        session_expires_at: sqlFromDate(new Date(Date.now() + LATE_WINDOW_SECONDS * 1000))
                    })
                });

                setModeUI('late');
                generateAttendanceQR(currentClassData.id);
                startSessionCountdownFrom(LATE_WINDOW_SECONDS);
            } catch (err) {
                console.error("Late Window Failure:", err);
            }
        };

        async function processNewAttendance(record) {
            try {
                // Fetch student info via fetch.php (reads from SQL)
                const students = await api('/fetch.php', {
                    method: 'POST',
                    body: JSON.stringify({ collection: 'students', uids: [record.student_uid] })
                });
                if (!students || students.length === 0) return;
                const student = students[0];
                
                // Resolve Avatar / Initials
                let avatarUrl = student.profilePicture || student.profile_picture;
                if (!avatarUrl) {
                    const initials = `${student.firstName?.[0] || 'S'}${student.lastName?.[0] || 'T'}`.toUpperCase();
                    avatarUrl = `https://ui-avatars.com/api/?name=${initials}&background=ea2628&color=fff&bold=true`;
                }
                
                const entry = {
                    uid: record.student_uid,
                    name: `${student.firstName || ''} ${student.lastName || ''}`.trim() || 'Unknown Student',
                    id: student.studentId || 'N/A',
                    avatar: avatarUrl,
                    time: record.timestamp ? new Date(record.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}) : new Date().toLocaleTimeString(),
                    status: record.status || 'Present',
                    deviceUuid: record.device_uuid || null,
                    ip: record.ip_address || null
                };

                verifiedStudentsList.push(entry);
                
                // Update UI Counters
                document.getElementById('presentCount').innerText = verifiedStudentsList.length;
                
                // Trigger Spotlight
                updateSpotlight(entry);
            } catch (err) {
                console.warn("Student Context Fetch Error:", err);
            }
        }

        // Fraud detection: same device OR same IP recording 2+ different students
        function renderFraudFlags(records) {
            if (!records || records.length === 0) return;
            const byDevice = new Map();
            const byIp = new Map();
            for (const r of records) {
                if (!r.student_uid) continue;
                if (r.device_uuid) {
                    if (!byDevice.has(r.device_uuid)) byDevice.set(r.device_uuid, new Set());
                    byDevice.get(r.device_uuid).add(r.student_uid);
                }
                if (r.ip_address) {
                    if (!byIp.has(r.ip_address)) byIp.set(r.ip_address, new Set());
                    byIp.get(r.ip_address).add(r.student_uid);
                }
            }
            const nextFlags = new Map();
            for (const [dev, uids] of byDevice) {
                if (uids.size > 1) for (const u of uids) pushFlag(nextFlags, u, 'Same device scanned multiple students');
            }
            for (const [ip, uids] of byIp) {
                if (uids.size > 1) for (const u of uids) pushFlag(nextFlags, u, 'Same connection scanned multiple students');
            }
            flagMap = nextFlags;
            // Re-render roster only when the spotlight is not covering it
            if (!document.getElementById('spotlightContent').classList.contains('hidden')) return;
            if (verifiedStudentsList.length > 0) {
                document.getElementById('idleListState').classList.remove('hidden');
                renderIdleList();
            }
        }

        function pushFlag(map, uid, reason) {
            if (!map.has(uid)) map.set(uid, []);
            map.get(uid).push(reason);
        }

        function updateSpotlight(student) {
            const emptyState = document.getElementById('idleEmptyState');
            const listState = document.getElementById('idleListState');
            const contentState = document.getElementById('spotlightContent');
            
            const timeStr = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

            emptyState.classList.add('hidden');
            listState.classList.add('hidden');
            contentState.classList.remove('hidden');
            
            const timerBar = document.getElementById('timerBar');
            timerBar.classList.remove('animate-timer-shrink');
            void timerBar.offsetWidth; 
            timerBar.classList.add('animate-timer-shrink');

            document.getElementById('spotlightAvatar').src = student.avatar;
            document.getElementById('spotlightName').innerText = student.name;
            document.getElementById('spotlightTime').innerText = `Verified at ${student.time}`;

            feather.replace();

            clearTimeout(spotlightTimeout);
            spotlightTimeout = setTimeout(() => {
                showIdleView();
            }, 5000);
        }

        function showIdleView() {
            document.getElementById('spotlightContent').classList.add('hidden');
            if (verifiedStudentsList.length > 0) {
                document.getElementById('idleListState').classList.remove('hidden');
                renderIdleList();
            } else {
                document.getElementById('idleEmptyState').classList.remove('hidden');
            }
        }

        function renderIdleList() {
            const container = document.getElementById('liveRosterList');
            container.innerHTML = [...verifiedStudentsList].reverse().map(s => {
                const late = s.status === 'Late';
                return `
                <div class="p-3 bg-dark-bg/40 border ${late ? 'border-amber-500/30' : 'border-dark-border'} rounded-xl hover:bg-white/5 transition-colors">
                    <div class="flex items-center">
                        <img src="${s.avatar}" class="w-10 h-10 rounded-full object-cover ring-2 ring-dark-bg mr-3">
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-white uppercase italic tracking-tighter">${s.name}</h4>
                            <p class="text-[9px] text-gray-500 font-black uppercase tracking-widest italic opacity-60">${s.time}</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${late ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-green-500/10 text-green-400 border border-green-500/20'} italic">
                            <span class="w-1.5 h-1.5 rounded-full ${late ? 'bg-amber-500' : 'bg-green-500'}"></span> ${late ? 'Late' : 'Verified'}
                        </span>
                    </div>
                    ${(flagMap.get(s.uid) || []).map(r => `
                        <div class="mt-2 flex items-center gap-1.5 text-[9px] font-bold text-amber-400 italic">
                            <i data-feather="alert-triangle" class="w-3 h-3"></i> ${r}
                        </div>`).join('')}
                </div>`;
            }).join('');
            feather.replace();
        }

        // Navigation Functions
        window.switchView = (viewId) => {
            document.getElementById('classSelectionView').classList.add('hidden');
            document.getElementById('liveAttendanceView').classList.add('hidden');
            document.getElementById('sessionSummaryView').classList.add('hidden');
            document.getElementById(viewId).classList.remove('hidden');
        };

        window.confirmEndSession = async () => {
            if (currentClassData) {
                try {
                    await api('/classes.php?id=' + currentClassData.id, {
                        method: 'PUT',
                        body: JSON.stringify({ session_active: 0 })
                    });
                } catch (err) {
                    console.error("Session Clearance Failure:", err);
                }
            }

            if (attendanceListener) clearInterval(attendanceListener);
            if (sessionTimerInterval) clearInterval(sessionTimerInterval);
            if (qrRefreshInterval) clearInterval(qrRefreshInterval);
            
            // Reset mode UI for next session
            setModeUI('open');
            
            generateSummaryReport();
            switchView('sessionSummaryView');
        };

        function startSessionCountdownFrom(totalSeconds) {
            totalSeconds = Math.max(0, Math.floor(totalSeconds));
            const timerDisplay = document.getElementById('sessionCountdown');
            const timerSpan = document.getElementById('timerValue');
            
            timerDisplay.classList.remove('hidden');
            timerSpan.classList.remove('text-primary', 'animate-pulse');
            
            if (sessionTimerInterval) clearInterval(sessionTimerInterval);
            
            sessionTimerInterval = setInterval(() => {
                const displayMins = Math.floor(totalSeconds / 60);
                const displaySecs = totalSeconds % 60;
                
                timerSpan.innerText = `${displayMins}:${displaySecs.toString().padStart(2, '0')}`;
                
                if (totalSeconds <= 60 && currentMode === 'open') {
                    timerSpan.classList.add('text-primary', 'animate-pulse');
                }

                if (totalSeconds <= 0) {
                    clearInterval(sessionTimerInterval);
                    confirmEndSession(); 
                }
                
                totalSeconds--;
            }, 1000);
        }

        function generateSummaryReport() {
            const tbody = document.getElementById('summaryTableBody');
            const presentCount = verifiedStudentsList.filter(s => s.status !== 'Late').length;
            const lateCount = verifiedStudentsList.length - presentCount;
            document.getElementById('finalPresentCount').innerText = presentCount;
            document.getElementById('finalLateCount').innerText = lateCount;

            if(verifiedStudentsList.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-gray-500 italic">No records captured.</td></tr>`;
                return;
            }

            tbody.innerHTML = verifiedStudentsList.map((s, idx) => {
                const late = s.status === 'Late';
                return `
                <tr class="border-b border-dark-border hover:bg-white/5 transition-colors animate-pop-in" style="animation-delay: ${idx * 50}ms">
                    <td class="p-4 pl-6">
                        <div class="flex items-center gap-3">
                            <img src="${s.avatar}" class="w-10 h-10 rounded-full object-cover ring-2 ring-dark-bg">
                            <span class="font-bold text-white uppercase italic tracking-tighter">${s.name}</span>
                        </div>
                    </td>
                    <td class="p-4 text-gray-400 font-mono text-xs uppercase">${s.id}</td>
                    <td class="p-4 text-gray-400 text-xs font-bold uppercase tracking-widest italic opacity-60">${s.time}</td>
                    <td class="p-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${late ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-green-500/10 text-green-400 border border-green-500/20'} italic">
                            <span class="w-1.5 h-1.5 rounded-full ${late ? 'bg-amber-500' : 'bg-green-500'}"></span> ${late ? 'Late' : 'Verified'}
                        </span>
                    </td>
                </tr>
                ${(flagMap.get(s.uid) || []).map(r => `
                    <tr class="bg-amber-500/5 border-b border-dark-border">
                        <td colspan="4" class="p-2 pl-6 flex items-center gap-1.5 text-[9px] font-bold text-amber-400 italic">
                            <i data-feather="alert-triangle" class="w-3 h-3"></i> FLAG: ${r} — ${s.name}
                        </td>
                    </tr>`).join('')}`;
            }).join('');
        }

        window.goToClassSelection = () => switchView('classSelectionView');

        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
        
        // Sidebar Toggle Mobile
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        if (mobileMenuBtn) {
            mobileMenuBtn.onclick = () => {
                sidebar.classList.remove('hidden');
                sidebar.classList.add('fixed', 'inset-0', 'z-50', 'w-64');
                mobileOverlay.classList.remove('hidden');
            }
        }
        if (mobileOverlay) {
            mobileOverlay.onclick = () => {
                sidebar.classList.add('hidden');
                mobileOverlay.classList.add('hidden');
            }
        }
    </script>
</body>
</html>