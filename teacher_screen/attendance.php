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
<body class="antialiased min-h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

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

                <!-- NEW: Session Timer Settings -->
                <div class="mb-10 flex items-center justify-center gap-6 animate-fade-in-up" style="animation-delay: 100ms">
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic opacity-60">Session Limit:</span>
                    <div class="flex bg-dark-surface/50 p-1 rounded-xl border border-white/5 backdrop-blur-md shadow-2xl">
                        <button onclick="setGlobalTimer(15)" id="btn-15" class="timer-opt px-6 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all bg-primary-500 text-white shadow-lg shadow-primary-500/20 italic">15 MIN</button>
                        <button onclick="setGlobalTimer(30)" id="btn-30" class="timer-opt px-6 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all text-gray-500 hover:text-white italic">30 MIN</button>
                        <button onclick="setGlobalTimer(60)" id="btn-60" class="timer-opt px-6 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all text-gray-500 hover:text-white italic">60 MIN</button>
                        <button onclick="setGlobalTimer(0)" id="btn-inf" class="timer-opt px-6 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all text-gray-500 hover:text-white italic">∞ Live</button>
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
                                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Live Session Active
                                </p>
                                <div id="sessionCountdown" class="hidden flex items-center gap-2 px-3 py-1 bg-primary-500/10 border border-primary-500/20 rounded-full">
                                    <i data-feather="clock" class="w-3 h-3 text-primary-500"></i>
                                    <span id="timerValue" class="text-[10px] font-black text-white italic tracking-widest">--:--</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button onclick="confirmEndSession()" class="flex items-center gap-2 px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 rounded-lg transition-colors">
                        <i data-feather="stop-circle" class="w-4 h-4"></i>
                        End Session
                    </button>
                </div>

                <!-- Live Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 h-full pb-8">
                    
                    <!-- Left Column: QR Code & Stats -->
                    <div class="glass-panel rounded-2xl p-8 flex flex-col items-center justify-between relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-primary-500 to-transparent opacity-50"></div>
                        
                        <h3 class="text-lg font-bold text-white">Scan to Join</h3>
                        
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
                                <p class="text-[10px] uppercase text-gray-500 font-bold tracking-wider">Total Present</p>
                                <p id="finalPresentCount" class="text-2xl font-bold text-white">0</p>
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

    <!-- Global Identity Orchestrator -->
    <script type="module" src="../assets/js/firebase-init.js"></script>
    <script type="module">
        import { db, auth } from '../assets/js/firebase-init.js';
        import { collection, query, where, onSnapshot, doc, getDoc, getDocs, updateDoc, serverTimestamp, addDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
        import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

        let currentTeacher = null;
        let attendanceListener = null;
        let spotlightTimeout = null;
        let currentClassData = null;
        let verifiedStudentsList = []; // Array of { name, id, avatar, time }
        let processedUids = new Set();
        
        const TODAY_STR = new Date().toISOString().split('T')[0];
        let selectedDuration = 15; // default
        let sessionTimerInterval = null;
        let qrRefreshInterval = null;
        let currentNonce = null;

        window.setGlobalTimer = (mins) => {
            selectedDuration = mins;
            document.querySelectorAll('.timer-opt').forEach(btn => {
                btn.classList.remove('bg-primary-500', 'text-white', 'shadow-lg', 'shadow-primary-500/20');
                btn.classList.add('text-gray-500');
            });
            const activeId = mins === 0 ? 'btn-inf' : `btn-${mins}`;
            const activeBtn = document.getElementById(activeId);
            if(activeBtn) {
                activeBtn.classList.add('bg-primary-500', 'text-white', 'shadow-lg', 'shadow-primary-500/20');
                activeBtn.classList.remove('text-gray-500');
            }
        };

        // 1. Identity Handshake
        window.addEventListener('profileLoaded', (e) => {
            currentTeacher = e.detail;
            initClassSelection(currentTeacher.uid);
        });

        onAuthStateChanged(auth, (user) => {
            if (user && !currentTeacher) {
                console.log("Attendance: Direct Auth Fallback");
                initClassSelection(user.uid);
            }
        });

        async function initClassSelection(teacherUid) {
            const grid = document.getElementById('classSelectionGrid');
            if (!grid) return;
            
            try {
                // 🛡️ Safety Check: Ensure we have a UID
                const uid = teacherUid || currentTeacher?.uid || auth.currentUser?.uid;
                if (!uid) {
                    console.warn("Attendance: No UID available for registry fetch.");
                    return;
                }

                console.log("Attendance: Initializing Registry for UID:", uid);
                const q = query(collection(db, "classes"), where("teacherUid", "==", uid));
                const snapshot = await getDocs(q);
                const classes = snapshot.docs.map(d => ({ id: d.id, ...d.data() }));
                
                if (classes.length === 0) {
                    grid.innerHTML = `<div class="col-span-full py-20 text-center opacity-40 italic text-gray-500">No classes found. Please create one in the Dashboard.</div>`;
                    feather.replace();
                    return;
                }

                grid.innerHTML = classes.map(c => `
                    <div class="glass-panel p-6 rounded-xl border border-dark-border hover:border-primary-500/50 transition-all cursor-pointer group" 
                         onclick="window.startAttendanceSession('${c.id}')">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-3 bg-primary-500/10 rounded-lg">
                                <i data-feather="book-open" class="w-6 h-6 text-primary-500"></i>
                            </div>
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic">${c.classCode}</span>
                        </div>
                        <h3 class="text-lg font-bold text-white mb-1 uppercase tracking-tighter italic">${c.className}</h3>
                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-widest mb-4 opacity-60">${c.subject} &bull; ${c.sectionCode}</p>
                        <div class="flex items-center gap-2 text-[10px] font-black text-primary-400 uppercase tracking-widest italic">
                            <span>Initialize Hub</span>
                            <i data-feather="arrow-right" class="w-3 h-3 group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                `).join('');
                feather.replace();
            } catch (error) {
                console.error("Attendance Sync Error:", error);
                grid.innerHTML = `
                    <div class="col-span-full py-20 text-center">
                        <i data-feather="alert-octagon" class="w-12 h-12 mx-auto mb-4 text-primary-500 animate-pulse"></i>
                        <p class="text-xs font-black uppercase tracking-widest italic text-primary-400">Registry Sync Denied</p>
                        <p class="text-[10px] text-gray-500 mt-2 font-mono">${error.code}</p>
                    </div>`;
                feather.replace();
            }
        }

        // 2. Start Live Session
        window.startAttendanceSession = async (classId) => {
            try {
                // Fetch class details to get full student roster mapping
                const classSnap = await getDoc(doc(db, "classes", classId));
                if (!classSnap.exists()) return;
                currentClassData = { id: classSnap.id, ...classSnap.data() };
                
                // Use class-specific session limit
                selectedDuration = currentClassData.sessionLimit !== undefined ? currentClassData.sessionLimit : 15;

                // Switch View
                switchView('liveAttendanceView');
                document.getElementById('liveClassName').innerText = currentClassData.className;
                document.getElementById('reportClassTitle').innerText = currentClassData.className;
                document.getElementById('totalCount').innerText = currentClassData.students ? currentClassData.students.length : 0;
                
                // Clear State
                verifiedStudentsList = [];
                processedUids.clear();
                document.getElementById('presentCount').innerText = '0';
                document.getElementById('liveRosterList').innerHTML = '';
                document.getElementById('spotlightContent').classList.add('hidden');
                document.getElementById('idleListState').classList.add('hidden');
                document.getElementById('idleEmptyState').classList.remove('hidden');

                // NEW: Generate QR Code
                generateAttendanceQR(classId);

                // NEW: Handle Timer Countdown
                if (selectedDuration > 0) {
                    startSessionCountdown(selectedDuration);
                } else {
                    document.getElementById('sessionCountdown').classList.add('hidden');
                }

                // NEW: Update Firestore with session start
                const nonce = Math.random().toString(36).substring(2, 10).toUpperCase();
                currentNonce = nonce;
                
                await updateDoc(doc(db, "classes", classId), {
                    sessionActive: true,
                    sessionStartedAt: serverTimestamp(),
                    currentNonce: nonce
                });

                // NEW: Start QR Refresh Cycle
                startQRRefreshCycle(classId);

                // Start Listener
                initAttendanceListener(classId);
            } catch (err) {
                console.error("Session Init Failure:", err);
            }
        };

        function initAttendanceListener(classId) {
            if (attendanceListener) attendanceListener(); // Unsubscribe prev
            
            // Query for records in this class for today
            const q = query(
                collection(db, "attendance"), 
                where("classId", "==", classId),
                where("date", "==", TODAY_STR)
            );

            attendanceListener = onSnapshot(q, async (snapshot) => {
                const changes = snapshot.docChanges();
                for (const change of changes) {
                    if (change.type === "added") {
                        const data = change.doc.data();
                        if (!processedUids.has(data.studentUid)) {
                            processedUids.add(data.studentUid);
                            await processNewAttendance(data);
                        }
                    }
                }
            });
        }

        function generateAttendanceQR(classId) {
            const qrContainer = document.getElementById('qrcode');
            qrContainer.innerHTML = '';
            
            // Re-show Scan Line
            document.getElementById('scanLine').classList.remove('hidden');

            new QRCode(qrContainer, {
                text: JSON.stringify({ 
                    classId: classId, 
                    type: 'attendance', 
                    date: TODAY_STR,
                    nonce: currentNonce
                }),
                width: 160,
                height: 160,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        function startQRRefreshCycle(classId) {
            if (qrRefreshInterval) clearInterval(qrRefreshInterval);
            qrRefreshInterval = setInterval(async () => {
                const nonce = Math.random().toString(36).substring(2, 10).toUpperCase();
                currentNonce = nonce;
                
                try {
                    await updateDoc(doc(db, "classes", classId), {
                        currentNonce: nonce
                    });
                    generateAttendanceQR(classId);
                    console.log("QR Refreshed with Nonce:", nonce);
                } catch (err) {
                    console.error("QR Refresh Failure:", err);
                }
            }, 10000); // Refresh every 10 seconds
        }

        async function processNewAttendance(record) {
            try {
                // Fetch student info
                const sSnap = await getDoc(doc(db, "students", record.studentUid));
                if (!sSnap.exists()) return;
                const student = sSnap.data();
                
                // Resolve Avatar / Initials
                let avatarUrl = student.profilePhoto;
                if (!avatarUrl) {
                    const initials = `${student.firstName?.[0] || 'S'}${student.lastName?.[0] || 'T'}`.toUpperCase();
                    avatarUrl = `https://ui-avatars.com/api/?name=${initials}&background=ea2628&color=fff&bold=true`;
                }
                
                const entry = {
                    name: student.full_name || `${student.firstName} ${student.lastName}`,
                    id: student.studentId || 'N/A',
                    avatar: avatarUrl,
                    time: record.timestamp ? record.timestamp.toDate().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}) : new Date().toLocaleTimeString()
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
            container.innerHTML = [...verifiedStudentsList].reverse().map(s => `
                <div class="flex items-center p-3 bg-dark-bg/40 border border-dark-border rounded-xl hover:bg-white/5 transition-colors">
                    <img src="${s.avatar}" class="w-10 h-10 rounded-full object-cover ring-2 ring-dark-bg mr-3">
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-white uppercase italic tracking-tighter">${s.name}</h4>
                        <p class="text-[9px] text-gray-500 font-black uppercase tracking-widest italic opacity-60">${s.time}</p>
                    </div>
                </div>`).join('');
        }

        // Navigation Functions
        window.switchView = (viewId) => {
            document.getElementById('classSelectionView').classList.add('hidden');
            document.getElementById('liveAttendanceView').classList.add('hidden');
            document.getElementById('sessionSummaryView').classList.add('hidden');
            document.getElementById(viewId).classList.remove('hidden');
        };

        window.confirmEndSession = async () => {
             // NEW: Clear session state in Firestore
            if (currentClassData) {
                try {
                    await updateDoc(doc(db, "classes", currentClassData.id), {
                        sessionActive: false
                    });
                } catch (err) {
                    console.error("Session Clearance Failure:", err);
                }
            }

            if (attendanceListener) attendanceListener(); // Stop listening
            if (sessionTimerInterval) clearInterval(sessionTimerInterval);
            if (qrRefreshInterval) clearInterval(qrRefreshInterval);
            generateSummaryReport();
            switchView('sessionSummaryView');
        };

        function startSessionCountdown(mins) {
            let totalSeconds = mins * 60;
            const timerDisplay = document.getElementById('sessionCountdown');
            const timerSpan = document.getElementById('timerValue');
            
            timerDisplay.classList.remove('hidden');
            
            if (sessionTimerInterval) clearInterval(sessionTimerInterval);
            
            sessionTimerInterval = setInterval(() => {
                const displayMins = Math.floor(totalSeconds / 60);
                const displaySecs = totalSeconds % 60;
                
                timerSpan.innerText = `${displayMins}:${displaySecs.toString().padStart(2, '0')}`;
                
                if (totalSeconds <= 60) {
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
            document.getElementById('finalPresentCount').innerText = verifiedStudentsList.length;

            if(verifiedStudentsList.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-gray-500 italic">No records captured.</td></tr>`;
                return;
            }

            tbody.innerHTML = verifiedStudentsList.map((s, idx) => `
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
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-green-500/10 text-green-400 border border-green-500/20 italic">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Verified
                        </span>
                    </td>
                </tr>`).join('');
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