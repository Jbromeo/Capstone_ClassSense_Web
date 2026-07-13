<?php require_once dirname(__DIR__) . '/core/init.php'; ?>
<!-- student_screen/student_class_view.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassSense | Class Record</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
    </div>

    <?php include 'student_sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        <!-- HEADER (Matching Teacher Dashboard Layout) -->
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                    <i data-feather="menu"></i>
                </button>
                <div>
                    <h2 id="hubClassName" class="text-xl font-bold text-white hidden sm:block uppercase tracking-tighter italic">Loading Hub...</h2>
                    <p id="hubClassDetails" class="text-[10px] font-bold text-gray-500 hidden sm:block uppercase tracking-widest italic tracking-tighter italic">Identifying Instructor • Accessing Grid</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative">
                    <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors group">
                        <i data-feather="bell" class="group-hover:scale-110 transition-transform"></i>
                        <span class="notif-dot hidden absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                    </button>
                    <?php include '../includes/notification_popover.php'; ?>
                </div>
                <a href="student_classes.php" class="text-[10px] font-black text-gray-400 hover:text-white flex items-center gap-1 transition-colors uppercase tracking-widest italic tracking-tighter">
                    <i data-feather="arrow-left" class="w-3.5 h-3.5"></i> Back to Classes
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 md:p-8 custom-scrollbar">
            
            <!-- Teacher Profile & AI Insights -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Teacher Card -->
                <div class="glass-panel rounded-xl p-6 flex items-center gap-4 border-l-4 border-l-primary-500 bg-white/5 border border-white/5 group">
                    <img id="instructorImage" src="https://ui-avatars.com/api/?name=ST&background=181b21&color=fff" class="w-16 h-16 rounded-xl object-cover ring-2 ring-dark-border group-hover:ring-primary-500/50 transition-all shadow-xl shadow-black/50">
                    <div>
                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic tracking-tighter italic opacity-60">Instructor</p>
                        <h3 id="instructorName" class="text-lg font-bold text-white uppercase tracking-tighter italic leading-none mt-1">Syncing Identity...</h3>
                        <p id="instructorEmail" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-2 italic tracking-tighter italic">Contact Not Established</p>
                    </div>
                </div>

                <!-- AI Insights Card -->
                <div class="glass-panel rounded-xl p-6 border border-primary-500/20 bg-primary-500/5 col-span-1 lg:col-span-2">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="p-1.5 bg-primary-500/10 rounded text-primary-400">
                            <i data-feather="cpu" class="w-4 h-4"></i>
                        </div>
                        <h3 class="text-sm font-bold text-primary-400 uppercase tracking-wider">AI Academic Insight</h3>
                    </div>
                    <p class="text-sm text-gray-300">
                        Your attendance is excellent, but your quiz scores in <span class="font-bold text-white">Chapter 3</span> are slightly below your average. 
                        I recommend reviewing the materials for <span class="font-bold text-white">Loops & Iterations</span> before the upcoming midterm.
                    </p>
                </div>
            </div>

            <!-- Grade Breakdown -->
            <div class="glass-panel rounded-xl p-6 mb-8">
                <h3 class="text-lg font-bold text-white mb-6">Grade Breakdown</h3>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6 items-center">
                    
                    <div class="text-center p-4 bg-dark-bg/50 rounded-xl border border-dark-border">
                        <div class="text-3xl font-bold text-white">1.25</div>
                        <div class="text-xs text-gray-500 uppercase mt-1">Final Grade</div>
                        <span class="mt-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-500/10 text-green-400 border border-green-500/20">Passed</span>
                    </div>

                    <div class="col-span-4 space-y-3">
                        <!-- Quizzes -->
                        <div class="flex items-center gap-4">
                            <div class="w-24 text-xs text-gray-400">Quizzes (20%)</div>
                            <div class="flex-1 h-3 bg-dark-border rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: 92%"></div>
                            </div>
                            <div class="w-12 text-sm font-bold text-white text-right">92%</div>
                        </div>
                        <!-- Exams -->
                        <div class="flex items-center gap-4">
                            <div class="w-24 text-xs text-gray-400">Exams (40%)</div>
                            <div class="flex-1 h-3 bg-dark-border rounded-full overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full" style="width: 95%"></div>
                            </div>
                            <div class="w-12 text-sm font-bold text-white text-right">95%</div>
                        </div>
                        <!-- Activities -->
                        <div class="flex items-center gap-4">
                            <div class="w-24 text-xs text-gray-400">Activities (20%)</div>
                            <div class="flex-1 h-3 bg-dark-border rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: 98%"></div>
                            </div>
                            <div class="w-12 text-sm font-bold text-white text-right">98%</div>
                        </div>
                         <!-- Attendance -->
                         <div class="flex items-center gap-4">
                            <div class="w-24 text-xs text-gray-400">Attendance (20%)</div>
                            <div class="flex-1 h-3 bg-dark-border rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: 96%"></div>
                            </div>
                            <div class="w-12 text-sm font-bold text-white text-right">96%</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Tabs -->
            <div class="mb-4 border-b border-dark-border">
                <nav class="flex gap-4">
                    <button onclick="switchTab('attendance')" id="tab-attendance" class="px-4 py-2 text-sm font-medium text-white border-b-2 border-primary-500 uppercase tracking-widest italic tracking-tighter italic">QR Attendance Log</button>
                    <button onclick="switchTab('scores')" id="tab-scores" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-white transition-colors border-b-2 border-transparent uppercase tracking-widest italic tracking-tighter italic">Assessments</button>
                </nav>
            </div>

            <!-- Attendance Content -->
            <div id="content-attendance" class="glass-panel rounded-xl overflow-hidden border border-dark-border">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-dark-bg/50 border-b border-dark-border">
                            <tr>
                                <th class="px-6 py-3 font-black tracking-widest italic tracking-tighter italic">Date</th>
                                <th class="px-6 py-3 font-black tracking-widest italic tracking-tighter italic">Time-In</th>
                                <th class="px-6 py-3 font-black tracking-widest italic tracking-tighter italic">Time-Out</th>
                                <th class="px-6 py-3 font-black tracking-widest italic tracking-tighter italic">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-300">
                             <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500 animate-pulse italic uppercase tracking-widest text-[10px] font-black">Establishing Neural Link...</td>
                             </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Assessments Content -->
            <div id="content-scores" class="hidden grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Quizzes -->
                <div class="glass-panel rounded-xl p-6 border border-dark-border">
                    <h3 class="text-md font-bold text-white mb-4 flex items-center gap-2 uppercase tracking-tighter italic"><i data-feather="check-circle" class="w-4 h-4 text-blue-400"></i> Quizzes</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-xs font-bold italic uppercase tracking-widest">
                            <span class="text-gray-400">Quiz 1: Variables</span>
                            <span class="text-white">15/15</span>
                        </div>
                         <div class="flex justify-between items-center text-xs font-bold italic uppercase tracking-widest">
                            <span class="text-gray-400">Quiz 2: Loops</span>
                            <span class="text-white">12/15</span>
                        </div>
                         <div class="flex justify-between items-center text-xs font-bold italic uppercase tracking-widest">
                            <span class="text-gray-400">Quiz 3: Arrays</span>
                            <span class="text-amber-400">Pending</span>
                        </div>
                    </div>
                </div>
                <!-- Exams -->
                 <div class="glass-panel rounded-xl p-6 border border-dark-border text-xs font-bold italic uppercase tracking-widest">
                    <h3 class="text-md font-bold text-white mb-4 flex items-center gap-2 uppercase tracking-tighter italic"><i data-feather="file-text" class="w-4 h-4 text-purple-400"></i> Exams</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">Prelims</span>
                            <span class="text-white">45/50</span>
                        </div>
                         <div class="flex justify-between items-center">
                            <span class="text-gray-400">Midterms</span>
                            <span class="text-gray-600">Locked</span>
                        </div>
                    </div>
                </div>
                 <!-- Activities -->
                 <div class="glass-panel rounded-xl p-6 border border-dark-border text-xs font-bold italic uppercase tracking-widest">
                    <h3 class="text-md font-bold text-white mb-4 flex items-center gap-2 uppercase tracking-tighter italic"><i data-feather="briefcase" class="w-4 h-4 text-emerald-400"></i> Activities</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">Activity 1</span>
                            <span class="text-white">100%</span>
                        </div>
                         <div class="flex justify-between items-center">
                            <span class="text-gray-400">Activity 2</span>
                            <span class="text-white">95%</span>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';

        const urlParams = new URLSearchParams(window.location.search);
        const classId = urlParams.get('id');

        async function loadAttendance(uid) {
            if (!uid || !classId) return;
            const tbody = document.querySelector('#content-attendance tbody');
            if (!tbody) return;

            const todayStr = new Date().toISOString().split('T')[0];

            try {
                const records = await api(`/attendance.php?class_id=${classId}&date=${todayStr}&student_uid=${uid}`);
                if (!records || records.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-12 text-center text-gray-500 italic uppercase tracking-widest text-[10px] font-black">No scan records found in this hub.</td></tr>`;
                    return;
                }

                const sorted = records.sort((a, b) => {
                    const tsA = a.timestamp ? new Date(a.timestamp) : new Date(0);
                    const tsB = b.timestamp ? new Date(b.timestamp) : new Date(0);
                    return tsB - tsA;
                });

                tbody.innerHTML = sorted.map(rec => {
                    const ts = rec.timestamp ? new Date(rec.timestamp) : new Date();
                    const dateStr = ts.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const timeIn = ts.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                    const timeOut = rec.time_out ? new Date(rec.time_out).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '--:-- --';
                    const status = rec.status || 'Verified';
                    const statusClass = status.toLowerCase() === 'late' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' :
                                      status.toLowerCase() === 'absent' ? 'bg-red-500/10 text-red-400 border-red-500/20' :
                                      'bg-green-500/10 text-green-400 border-green-500/20';

                    return `
                        <tr class="border-b border-dark-border hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 font-black text-white italic truncate uppercase tracking-tighter">${dateStr}</td>
                            <td class="px-6 py-4 text-gray-300 font-bold italic text-xs">${timeIn}</td>
                            <td class="px-6 py-4 text-gray-500 italic text-xs">${timeOut}</td>
                            <td class="px-6 py-4"><span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border italic ${statusClass}">${status}</span></td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-12 text-center text-primary-500 italic text-xs font-bold">Failed to load attendance: ${err.message}</td></tr>`;
            }
        }

        async function loadClassData() {
            if (!classId) {
                window.location.href = 'student_classes.php';
                return;
            }

            try {
                const classData = await api(`/classes.php?id=${classId}`);

                const hubName = document.getElementById('hubClassName');
                const hubDetails = document.getElementById('hubClassDetails');
                if (hubName) {
                    hubName.innerText = classData.class_name;
                    hubName.classList.remove('italic');
                }
                if (hubDetails) {
                    hubDetails.innerText = `${classData.teacher_name || 'Faculty'} • ${classData.schedule || 'TBA'} • ${classData.time_slot || 'TBA'} • ${classData.section_code}`;
                    hubDetails.classList.remove('italic');
                }

                const instrName = document.getElementById('instructorName');
                const instrEmail = document.getElementById('instructorEmail');
                const instrImg = document.getElementById('instructorImage');

                const displayName = classData.teacher_name || 'Faculty';
                if (instrName) { instrName.innerText = displayName; instrName.classList.remove('italic'); }
                if (instrEmail) { instrEmail.innerText = 'Official University Email'; instrEmail.classList.remove('italic'); }
                if (instrImg) {
                    instrImg.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=ea2628&color=fff&bold=true`;
                }
            } catch (err) {
                console.error('[Class] Failed to load:', err);
            }
        }

        initPage((user) => {
            setTimeout(() => {
                loadClassData();
                loadAttendance(user.uid);
            }, 500);
            setInterval(loadClassData, 10000);
            setInterval(() => loadAttendance(user.uid), 10000);
        });
    </script>
    <script type="module" src="student_auth.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
        
        // Tab Switching
        function switchTab(tabName) {
            const attendanceContent = document.getElementById('content-attendance');
            const scoresContent = document.getElementById('content-scores');
            const attendanceTab = document.getElementById('tab-attendance');
            const scoresTab = document.getElementById('tab-scores');

            if(!attendanceContent || !scoresContent || !attendanceTab || !scoresTab) return;

            attendanceContent.classList.add('hidden');
            scoresContent.classList.add('hidden');
            attendanceTab.classList.remove('text-white', 'border-primary-500');
            attendanceTab.classList.add('text-gray-500', 'border-transparent');
            scoresTab.classList.remove('text-white', 'border-primary-500');
            scoresTab.classList.add('text-gray-500', 'border-transparent');

            if(tabName === 'attendance') {
                attendanceContent.classList.remove('hidden');
                attendanceTab.classList.add('text-white', 'border-primary-500');
                attendanceTab.classList.remove('text-gray-500', 'border-transparent');
            } else {
                scoresContent.classList.remove('hidden');
                scoresTab.classList.add('text-white', 'border-primary-500');
                scoresTab.classList.remove('text-gray-500', 'border-transparent');
            }
        }

        // Mobile Menu Logic
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');

        if(mobileMenuBtn && sidebar && mobileOverlay) {
            mobileMenuBtn.addEventListener('click', () => {
                if(sidebar.style.display === 'flex') { 
                    sidebar.style.display = ''; 
                    sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64'); 
                    mobileOverlay.classList.remove('open'); 
                } else { 
                    sidebar.style.display = 'flex'; 
                    sidebar.classList.add('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64'); 
                    mobileOverlay.classList.add('open'); 
                }
            });
            mobileOverlay.addEventListener('click', () => { 
                sidebar.style.display = ''; 
                sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64'); 
                mobileOverlay.classList.remove('open'); 
            });
        }
    </script>
</body>
</html>