<!-- student_screen/student_dashboard.php -->
<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | Student Dashboard</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white bg-dark-bg">

    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-purple-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 4s"></div>
    </div>

    <?php include 'student_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        <!-- HEADER -->
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white transition-colors">
                    <i data-feather="menu"></i>
                </button>
                <h2 class="text-xl font-black text-white hidden sm:block tracking-tight uppercase shadow-sm">Dashboard</h2>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative hidden md:block group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i data-feather="search" class="h-4 w-4 text-gray-600 group-focus-within:text-primary-500 transition-colors"></i>
                    </div>
                    <input id="globalSearchInput" type="text" class="bg-dark-bg/50 border border-white/10 text-gray-300 text-sm rounded-full focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 block w-64 pl-10 p-2.5 transition-all focus:w-80 placeholder:text-gray-600 font-medium italic" placeholder="Search classes...">
                </div>

                <div class="relative">
                    <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors">
                        <i data-feather="bell"></i>
                        <span class="notif-dot hidden absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                    </button>
                    <?php include '../includes/notification_popover.php'; ?>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            
            <!-- Top Row: Profile & Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                
                <!-- Profile Card -->
                <div class="glass-panel rounded-2xl p-6 flex items-center gap-5 border border-white/5 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-blue-500/10 transition-colors"></div>
                <div id="dashStudentPhoto" class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-600 to-primary-900 flex items-center justify-center text-white font-black border border-white/10 shadow-2xl relative z-10 transition-transform group-hover:scale-105" style="font-size:1.5rem">
                    
                </div>
                    <div class="relative z-10 min-w-0">
                        <h3 id="dashStudentName" class="text-xl font-black text-white truncate tracking-tighter uppercase leading-none overflow-hidden" style="white-space:nowrap">Loading Identity...</h3>
                        <p id="dashStudentCourse" class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-2 leading-none">Establishing Sync</p>
                        <div class="mt-3">
                             <span id="dashStudentYear" class="inline-flex items-center px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest bg-blue-500/10 text-blue-400 border border-blue-500/20">---</span>
                        </div>
                    </div>
                </div>

                <!-- Attendance Rate -->
                <div class="glass-panel p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-green-500/30 transition-all">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/5 rounded-full -mr-12 -mt-12 blur-2xl group-hover:bg-green-500/10 transition-colors"></div>
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2 opacity-60 leading-none">Attendance Rate</p>
                            <h3 id="dashAttendanceRate" class="text-4xl font-black text-white tracking-tighter uppercase leading-none">--</h3>
                        </div>
                        <div class="p-3 bg-green-500/10 rounded-xl text-green-500 group-hover:scale-110 transition-transform">
                            <i data-feather="trending-up" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <p id="dashAttendanceLabel" class="text-[10px] text-green-400 mt-4 font-black uppercase tracking-widest leading-none opacity-80">Loading...</p>
                </div>

                <!-- Enrolled Classes -->
                <div class="glass-panel p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-purple-500/30 transition-all">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/5 rounded-full -mr-12 -mt-12 blur-2xl group-hover:bg-purple-500/10 transition-colors"></div>
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2 opacity-60 leading-none">Current Load</p>
                            <h3 id="dashEnrolledCount" class="text-4xl font-black text-white tracking-tighter uppercase leading-none">--</h3>
                        </div>
                        <div class="p-3 bg-purple-500/10 rounded-xl text-purple-500 group-hover:scale-110 transition-transform">
                            <i data-feather="book-open" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <p id="dashEnrolledLabel" class="text-[10px] text-gray-400 mt-4 font-black uppercase tracking-widest leading-none opacity-80">Loading...</p>
                </div>
            </div>

            <!-- Dashboard Content Panels -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                 <!-- Schedule -->
                 <div class="glass-panel rounded-2xl p-6 border border-white/5">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-black text-white uppercase tracking-tighter leading-none">Daily Timeline</h3>
                        <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest opacity-60">Today's Schedule</span>
                    </div>
                    <div class="space-y-4">
                        <div class="p-6 text-center">
                            <p class="text-sm text-gray-500 font-medium">No classes scheduled for today</p>
                        </div>
                    </div>
                 </div>

                 <!-- Alerts -->
                 <div class="glass-panel rounded-2xl p-6 border border-white/5">
                    <h3 class="text-lg font-black text-white uppercase tracking-tighter leading-none mb-6 text-center lg:text-left">Intelligence Alerts</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-amber-500/5 border border-amber-500/10 group hover:bg-amber-500/10 transition-all cursor-pointer">
                            <div class="p-2.5 bg-amber-500/10 rounded-xl text-amber-500 group-hover:scale-110 transition-transform shadow-lg shadow-amber-500/10">
                                <i data-feather="alert-octagon" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xs font-black text-amber-500 uppercase tracking-widest leading-none mb-2 underline decoration-amber-500/30 underline-offset-4">Attendance Alert</h4>
                                <p class="text-[11px] text-gray-300 font-medium leading-relaxed opacity-80">Identity participation in mathematics is currently below threshold (78%). Immediate sync required.</p>
                            </div>
                        </div>
                    </div>
                 </div>
            </div>
        </main>
    </div>

    <!-- LOGIC -->
    <script>
        // Use Global Auth Controller event to populate Identity Profile
        window.addEventListener('profileLoaded', (e) => {
            const data = e.detail;
            
            // 1. Sidebar Updates (via auth_controller.js defaults)
            const sideName = document.getElementById('sideStudentName');
            if (sideName) {
                sideName.textContent = `${data.firstName} ${data.lastName}`;
                sideName.classList.remove('italic');
            }

            // 2. Dashboard Specific Identity Display
            const dashName = document.getElementById('dashStudentName');
            const dashCourse = document.getElementById('dashStudentCourse');
            const dashYear = document.getElementById('dashStudentYear');

            if (dashName) {
                dashName.textContent = `${data.firstName} ${data.lastName}`;
                dashName.classList.remove('italic');
            }
            if (dashCourse) {
                dashCourse.textContent = data.course || data.yearLevel || '';
                dashCourse.classList.remove('italic');
            }
            if (dashYear) {
                dashYear.textContent = data.yearLevel || data.studentId || '';
            }
        });

        document.addEventListener('DOMContentLoaded', () => { 
            feather.replace();

            // Search redirect to classes page
            const searchInput = document.getElementById('globalSearchInput');
            if (searchInput) {
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        const q = searchInput.value.trim();
                        window.location.href = 'student_classes.php' + (q ? '?search=' + encodeURIComponent(q) : '');
                    }
                });
            }
        });
    </script>
    <script type="module" src="student_auth.js"></script>

    <script type="module">
        import { api } from '../assets/js/custom-auth.js';

        async function loadStats() {
            try {
                const stats = await api('/student_stats.php');

                // Attendance rate card
                const rateEl = document.getElementById('dashAttendanceRate');
                const rateLabel = document.getElementById('dashAttendanceLabel');
                if (rateEl) {
                    rateEl.textContent = stats.attendanceRate !== null ? stats.attendanceRate + '%' : '--';
                }
                if (rateLabel) {
                    if (stats.attendanceRate === null) {
                        rateLabel.textContent = 'No records yet';
                    } else if (stats.attendanceRate >= 80) {
                        rateLabel.textContent = 'Good Standing';
                    } else if (stats.attendanceRate >= 60) {
                        rateLabel.textContent = 'Needs Improvement';
                    } else {
                        rateLabel.textContent = 'At Risk';
                    }
                }

                // Enrolled classes card
                const enrollEl = document.getElementById('dashEnrolledCount');
                const enrollLabel = document.getElementById('dashEnrolledLabel');
                if (enrollEl) {
                    enrollEl.textContent = String(stats.enrolledCount).padStart(2, '0');
                }
                if (enrollLabel) {
                    enrollLabel.textContent = stats.enrolledCount === 1 ? '1 Subject Enrolled' : stats.enrolledCount + ' Subjects Enrolled';
                }
            } catch (e) {
                console.error('[dash stats]', e);
            }
        }

        window.addEventListener('profileLoaded', () => loadStats());
        // Also try immediately if profile already loaded
        if (window.csProfile) loadStats();
    </script>
</body>
</html>