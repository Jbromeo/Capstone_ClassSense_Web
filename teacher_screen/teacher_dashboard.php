<?php 
// teacher_screen/teacher_dashboard.php
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | Teacher Dashboard</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased min-h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-purple-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 4s"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LCAyNTUsIDI1NSwgMC4wNSkiLz48L3N2Zz4=')] [mask-image:linear-gradient(to_bottom,white,transparent)]"></div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                    <i data-feather="menu"></i>
                </button>
                <h2 class="text-xl font-bold text-white hidden sm:block">Dashboard</h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative hidden md:block group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i data-feather="search" class="h-4 w-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i>
                    </div>
                    <input type="text" class="bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-full focus:ring-primary-500 focus:border-primary-500 block w-64 pl-10 p-2.5 transition-all focus:w-80 placeholder-gray-600" placeholder="Search classes, students...">
                </div>
                <button class="relative p-2 text-gray-400 hover:text-white transition-colors">
                    <i data-feather="bell"></i>
                    <span class="absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                </button>
                <button class="p-2 text-gray-400 hover:text-white transition-colors md:hidden">
                    <i data-feather="search"></i>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            <!-- Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-blue-500 hover-card">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Total Students</p>
                            <h3 id="statTotalStudents" class="text-2xl font-bold text-white mt-1">---</h3>
                        </div>
                        <div class="p-2 bg-blue-500/10 rounded-lg text-blue-500">
                            <i data-feather="users" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-xs text-green-400 mt-2 flex items-center gap-1"><i data-feather="trending-up" class="w-3 h-3"></i> +12% from last semester</p>
                </div>

                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-primary-500 hover-card">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Active Classes</p>
                            <h3 id="statActiveClasses" class="text-2xl font-bold text-white mt-1">---</h3>
                        </div>
                        <div class="p-2 bg-primary-500/10 rounded-lg text-primary-500">
                            <i data-feather="book-open" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Admin assigned</p>
                </div>

                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-purple-500 hover-card">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Avg. Attendance</p>
                            <h3 id="statAvgAttendance" class="text-2xl font-bold text-white mt-1">---</h3>
                        </div>
                        <div class="p-2 bg-purple-500/10 rounded-lg text-purple-500">
                            <i data-feather="check-circle" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p id="statAttendanceTrend" class="text-xs text-gray-400 mt-2 italic">Calculating...</p>
                </div>

                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-amber-500 hover-card">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Pending Grading</p>
                            <h3 class="text-2xl font-bold text-white mt-1">12</h3>
                        </div>
                        <div class="p-2 bg-amber-500/10 rounded-lg text-amber-500">
                            <i data-feather="clock" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-xs text-amber-400 mt-2">Due this week</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <!-- Today's Schedule -->
                    <div class="glass-panel rounded-xl p-6 relative overflow-hidden">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                <i data-feather="calendar" class="w-5 h-5 text-primary-500"></i> Today's Schedule
                            </h3>
                            <button class="text-xs text-primary-400 hover:text-primary-300 transition-colors">View Calendar</button>
                        </div>
                        <div id="todayScheduleList" class="relative pl-2 space-y-0">
                            <!-- Populated by JS -->
                            <div class="absolute left-[23px] top-8 bottom-0 w-0.5 bg-dark-border"></div>
                            <div class="py-10 text-center opacity-40">
                                <p class="text-xs text-gray-500 italic uppercase tracking-widest">No classes scheduled for today.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Class Overview -->
                    <div class="glass-panel rounded-xl p-6">
                        <h3 class="text-lg font-bold text-white mb-4">Your Classes Overview</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-gray-400">
                                <thead class="text-xs uppercase bg-dark-bg/50 text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 rounded-l-lg">Class</th>
                                        <th class="px-4 py-3">Students</th>
                                        <th class="px-4 py-3">Progress</th>
                                        <th class="px-4 py-3 rounded-r-lg">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="classesOverviewBody" class="divide-y divide-dark-border">
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-600 italic">Syncing class registry...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="glass-panel rounded-xl p-6 bg-gradient-to-br from-primary-900/20 to-transparent border-primary-500/20">
                        <h3 class="text-lg font-bold text-white mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button class="flex flex-col items-center justify-center p-4 bg-dark-bg/50 hover:bg-dark-bg border border-dark-border rounded-xl transition-all hover:-translate-y-1 group" onclick="showToast('Add Student form opened')">
                                <div class="p-2 bg-blue-500/10 rounded-full group-hover:bg-blue-500/20 transition-colors mb-2"><i data-feather="user-plus" class="w-5 h-5 text-blue-500"></i></div>
                                <span class="text-xs font-medium">Add Student</span>
                            </button>
                            <button class="flex flex-col items-center justify-center p-4 bg-dark-bg/50 hover:bg-dark-bg border border-dark-border rounded-xl transition-all hover:-translate-y-1 group" onclick="showToast('Navigating to Grading Queue')">
                                <div class="p-2 bg-amber-500/10 rounded-full group-hover:bg-amber-500/20 transition-colors mb-2"><i data-feather="clipboard" class="w-5 h-5 text-amber-500"></i></div>
                                <span class="text-xs font-medium">Grade Submissions</span>
                            </button>
                            <button class="flex flex-col items-center justify-center p-4 bg-dark-bg/50 hover:bg-dark-bg border border-dark-border rounded-xl transition-all hover:-translate-y-1 group" onclick="showToast('Opening Attendance Tool')">
                                <div class="p-2 bg-green-500/10 rounded-full group-hover:bg-green-500/20 transition-colors mb-2"><i data-feather="check-square" class="w-5 h-5 text-green-500"></i></div>
                                <span class="text-xs font-medium">Take Attendance</span>
                            </button>
                            <button class="flex flex-col items-center justify-center p-4 bg-dark-bg/50 hover:bg-dark-bg border border-dark-border rounded-xl transition-all hover:-translate-y-1 group" onclick="showToast('Create Announcement modal')">
                                <div class="p-2 bg-purple-500/10 rounded-full group-hover:bg-purple-500/20 transition-colors mb-2"><i data-feather="megaphone" class="w-5 h-5 text-purple-500"></i></div>
                                <span class="text-xs font-medium">Announcement</span>
                            </button>
                        </div>
                    </div>

                    <div class="glass-panel rounded-xl p-6 flex-1">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-white">Quiz & Grade Alerts</h3>
                            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        </div>
                        <div class="space-y-5">
                            <div class="group cursor-pointer" onclick="showToast('Viewing CS101 missing submissions')">
                                <div class="flex justify-between items-end mb-2">
                                    <div>
                                        <div class="text-sm font-bold text-white">CS101</div>
                                        <div class="text-xs text-gray-400">Midterm Quiz</div>
                                    </div>
                                    <div class="flex items-center gap-1.5 px-2 py-1 rounded bg-amber-500/10 border border-amber-500/20">
                                        <i data-feather="alert-octagon" class="w-3 h-3 text-amber-400"></i>
                                        <span class="text-xs font-bold text-amber-400">4 Missing</span>
                                    </div>
                                </div>
                                <div class="relative w-full h-2 bg-dark-bg rounded-full overflow-hidden">
                                    <div class="absolute h-full bg-amber-500 rounded-full transition-all duration-500" style="width: 87.5%"></div>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span class="text-[10px] text-gray-500">28 Taken</span>
                                    <span class="text-[10px] text-amber-400">4 Not Taken</span>
                                </div>
                            </div>
                            <div class="group cursor-pointer" onclick="showToast('Viewing CS201 ungraded submissions')">
                                <div class="flex justify-between items-end mb-2">
                                    <div>
                                        <div class="text-sm font-bold text-white">CS201</div>
                                        <div class="text-xs text-gray-400">Homework 3</div>
                                    </div>
                                    <div class="flex items-center gap-1.5 px-2 py-1 rounded bg-red-500/10 border border-red-500/20">
                                        <i data-feather="alert-circle" class="w-3 h-3 text-red-400"></i>
                                        <span class="text-xs font-bold text-red-400">3 No Grade</span>
                                    </div>
                                </div>
                                <div class="relative w-full h-2 bg-dark-bg rounded-full overflow-hidden">
                                    <div class="absolute h-full bg-red-500 rounded-full transition-all duration-500" style="width: 89%"></div>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span class="text-[10px] text-gray-500">25 Graded</span>
                                    <span class="text-[10px] text-red-400">3 Pending</span>
                                </div>
                            </div>
                            <div class="group">
                                <div class="flex justify-between items-end mb-2">
                                    <div>
                                        <div class="text-sm font-bold text-white">CS301</div>
                                        <div class="text-xs text-gray-400">Lab Assignment</div>
                                    </div>
                                    <div class="flex items-center gap-1.5 px-2 py-1 rounded bg-green-500/10 border border-green-500/20">
                                        <i data-feather="check-circle" class="w-3 h-3 text-green-400"></i>
                                        <span class="text-xs font-bold text-green-400">All Done</span>
                                    </div>
                                </div>
                                <div class="relative w-full h-2 bg-dark-bg rounded-full overflow-hidden">
                                    <div class="absolute h-full bg-green-500 rounded-full transition-all duration-500" style="width: 100%"></div>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span class="text-[10px] text-gray-500">24/24 Submitted</span>
                                    <span class="text-[10px] text-green-400">24 Graded</span>
                                </div>
                            </div>
                        </div>
                        <button class="w-full mt-6 py-2 text-xs text-center text-gray-400 hover:text-white transition-colors border border-dark-border rounded-lg hover:bg-white/5" onclick="showToast('Viewing all alerts')">Manage All Alerts</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();

            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileOverlay = document.getElementById('mobileOverlay');
            const sidebar = document.getElementById('sidebar');

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
        });

        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast flex items-center w-full max-w-xs p-4 space-x-4 text-gray-200 bg-gray-800 rounded-lg shadow-lg border border-gray-700 ${type === 'success' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-primary-500'}`;
            toast.innerHTML = `
                <div class="flex-shrink-0">
                    ${type === 'success' 
                        ? '<svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>' 
                        : '<i data-feather="info" class="w-5 h-5 text-primary-500"></i>'}
                </div>
                <div class="text-sm font-normal">${message}</div>
            `;
            container.appendChild(toast);
            feather.replace();
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }
    </script>
    
    <!-- Global Identity Orchestrator -->
    <script type="module" src="../assets/js/firebase-init.js"></script>
    <script type="module">
        import { db, auth } from '../assets/js/firebase-init.js';
        import { collection, query, where, onSnapshot, getDocs, Timestamp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
        import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

        // Constants for Schedule
        const DAYS_MAP = { 'M': 'Monday', 'T': 'Tuesday', 'W': 'Wednesday', 'TH': 'Thursday', 'F': 'Friday', 'S': 'Saturday', 'SU': 'Sunday' };
        const CURRENT_DAY_CODE = (() => {
            const days = ['SU', 'M', 'T', 'W', 'TH', 'F', 'S'];
            return days[new Date().getDay()];
        })();

        let currentTeacher = null;

        // 1. Listen for Identity Sync
        window.addEventListener('profileLoaded', (e) => {
            const data = e.detail;
            currentTeacher = data;
            initDashboard(data.uid);
        });

        // 🛡️ Fallback: Direct Auth Check
        onAuthStateChanged(auth, (user) => {
            if (user && !currentTeacher) {
                console.log("Dashboard: Fallback Auth Active");
                currentTeacher = { uid: user.uid };
                initDashboard(user.uid);
            }
        });

        async function initDashboard(teacherId) {
            // Real-time Class Monitor
            const q = query(collection(db, "classes"), where("teacherUid", "==", teacherId));
            
            try {
                const snapshot = await getDocs(q);
                const classes = snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
                renderStats(classes);
                renderTodaySchedule(classes);
                renderClassesOverview(classes);
                calculateAttendanceStats(classes);
            } catch (error) {
                console.error("Dashboard Sync Error:", error);
                showToast(`Dashboard Sync Failure: ${error.message}`, 'error');
                
                const tbody = document.getElementById('classesOverviewBody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center gap-2 opacity-50">
                                    <i data-feather="alert-octagon" class="w-8 h-8 text-primary-500 animate-pulse"></i>
                                    <span class="text-xs font-black uppercase tracking-widest italic text-primary-400">Registry Sync Failure</span>
                                    <span class="text-[9px] font-mono">${error.code}</span>
                                </div>
                            </td>
                        </tr>`;
                    feather.replace();
                }
            }
        }

        async function calculateAttendanceStats(classes) {
            if (classes.length === 0) {
                document.getElementById('statAvgAttendance').textContent = '0%';
                document.getElementById('statAttendanceTrend').textContent = 'No records found';
                return;
            }

            const classIds = classes.map(c => c.id);
            const attendanceRef = collection(db, "attendance");
            
            // To calculate "weekly average", we need records from the last 14 days
            const now = new Date();
            const lastWeek = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            const prevWeek = new Date(now.getTime() - 14 * 24 * 60 * 60 * 1000);

            try {
                // Since Firestore doesn't support 'in' with more than 30 IDs, we might need to batch or just fetch all for teacher
                // For performance, we'll fetch all attendance for the last 14 days and filter in JS
                const q = query(attendanceRef, where("classId", "in", classIds.slice(0, 30))); // Max 30 for 'in' query
                const snapshot = await getDocs(q);
                const allRecords = snapshot.docs.map(d => d.data());

                const lastWeekRecords = allRecords.filter(r => r.timestamp?.toDate() >= lastWeek);
                const prevWeekRecords = allRecords.filter(r => r.timestamp?.toDate() >= prevWeek && r.timestamp?.toDate() < lastWeek);

                // Helper to calc avg presence: present_scans / total_possible_scans
                // total_possible_scans = (number of students in class) * (number of days sessions were held)
                const calcAvg = (records) => {
                    if (records.length === 0) return 0;
                    
                    // Group records by [classId, date] to determine how many sessions were held
                    const sessions = {};
                    records.forEach(r => {
                        const key = `${r.classId}_${r.date}`;
                        if (!sessions[key]) sessions[key] = 0;
                        sessions[key]++;
                    });

                    let totalPossible = 0;
                    Object.keys(sessions).forEach(key => {
                        const classId = key.split('_')[0];
                        const classData = classes.find(c => c.id === classId);
                        if (classData && classData.students) {
                            totalPossible += classData.students.length;
                        }
                    });

                    return totalPossible > 0 ? (records.length / totalPossible) * 100 : 0;
                };

                const lastWeekAvg = calcAvg(lastWeekRecords);
                const prevWeekAvg = calcAvg(prevWeekRecords);

                const avgDisplay = document.getElementById('statAvgAttendance');
                const trendDisplay = document.getElementById('statAttendanceTrend');

                avgDisplay.textContent = `${Math.round(lastWeekAvg)}%`;
                
                if (prevWeekAvg > 0) {
                    const diff = lastWeekAvg - prevWeekAvg;
                    const isUp = diff >= 0;
                    trendDisplay.className = `text-xs mt-2 flex items-center gap-1 ${isUp ? 'text-green-400' : 'text-primary-400'}`;
                    trendDisplay.innerHTML = `<i data-feather="trending-${isUp ? 'up' : 'down'}" class="w-3 h-3"></i> ${isUp ? '+' : ''}${diff.toFixed(1)}% vs last week`;
                } else {
                    trendDisplay.textContent = "Insufficient historic data";
                }
                feather.replace();

            } catch (err) {
                console.error("Attendance Stats Error:", err);
                document.getElementById('statAvgAttendance').textContent = '---';
            }
        }

        function renderStats(classes) {
            const uniqueStudents = new Set();
            classes.forEach(c => {
                if (c.students && Array.isArray(c.students)) {
                    c.students.forEach(sId => uniqueStudents.add(sId));
                }
            });
            document.getElementById('statTotalStudents').textContent = uniqueStudents.size;
            document.getElementById('statActiveClasses').textContent = classes.length;
        }

        function renderTodaySchedule(classes) {
            const container = document.getElementById('todayScheduleList');
            const todayClasses = classes.filter(c => c.schedule && c.schedule.includes(CURRENT_DAY_CODE))
                                       .sort((a, b) => a.startTime.localeCompare(b.startTime));

            if (todayClasses.length === 0) {
                container.innerHTML = `
                    <div class="absolute left-[23px] top-8 bottom-0 w-0.5 bg-dark-border"></div>
                    <div class="py-10 text-center opacity-40">
                        <p class="text-xs text-gray-500 italic uppercase tracking-widest">No classes scheduled for today.</p>
                    </div>`;
                return;
            }

            let html = `<div class="absolute left-[23px] top-8 bottom-0 w-0.5 bg-dark-border"></div>`;
            const now = new Date();
            const nowStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

            todayClasses.forEach((c, idx) => {
                const isNow = nowStr >= c.startTime && nowStr <= c.endTime;
                const isPast = nowStr > c.endTime;
                
                html += `
                    <div class="relative pl-12 pb-8 group">
                        <div class="absolute left-[15px] top-1.5 w-4 h-4 rounded-full ${isNow ? 'bg-primary-500 shadow-[0_0_10px_rgba(220,38,38,0.5)]' : (isPast ? 'bg-gray-700' : 'bg-gray-500')} ring-4 ring-dark-bg z-10 transition-all"></div>
                        <div class="glass-panel border ${isNow ? 'border-primary-500/30 bg-primary-500/5' : 'border-dark-border'} rounded-xl p-4 hover:bg-white/5 transition-all cursor-pointer" onclick="window.location.href='class_view.php?id=${c.id}'">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="text-[10px] font-bold ${isNow ? 'text-primary-400' : 'text-gray-500'} uppercase tracking-wide italic">${isNow ? 'Now' : (isPast ? 'Completed' : 'Upcoming')}</span>
                                    <h4 class="text-md font-bold ${isPast ? 'text-gray-500' : 'text-white'}">${c.className}</h4>
                                </div>
                                <span class="text-sm ${isPast ? 'text-gray-600' : 'text-gray-300'} font-mono">${formatTime(c.startTime)} - ${formatTime(c.endTime)}</span>
                            </div>
                            <div class="flex items-center gap-4 text-[10px] text-gray-500 font-black uppercase tracking-widest italic tracking-tighter">
                                <div class="flex items-center gap-1"><i data-feather="map-pin" class="w-3 h-3"></i> ${c.room || 'Room TBA'}</div>
                                <div class="flex items-center gap-1"><i data-feather="users" class="w-3 h-3"></i> ${c.students ? c.students.length : 0} Students</div>
                            </div>
                        </div>
                    </div>`;
            });

            container.innerHTML = html;
            feather.replace();
        }

        function renderClassesOverview(classes) {
            const tbody = document.getElementById('classesOverviewBody');
            if (classes.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="px-4 py-8 text-center text-gray-600 italic">No classes found in registry.</td></tr>`;
                return;
            }

            tbody.innerHTML = classes.map(c => `
                <tr class="group hover:bg-white/5 transition-colors cursor-pointer" onclick="window.location.href='class_view.php?id=${c.id}'">
                    <td class="px-4 py-3">
                        <div class="font-bold text-white">${c.className}</div>
                        <div class="text-[10px] text-gray-500 uppercase tracking-widest italic">
                            ${Array.isArray(c.schedule) ? c.schedule.map(s => DAYS_MAP[s] || s).join(', ') : (c.schedule || 'No Schedule')}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-300 font-bold">${c.students ? c.students.length : 0}</td>
                    <td class="px-4 py-3 w-1/3">
                        <div class="w-full bg-dark-bg rounded-full h-1 mb-1">
                            <div class="bg-primary-500 h-1 rounded-full shadow-lg shadow-primary-500/20" style="width: 100%"></div>
                        </div>
                        <span class="text-[9px] text-gray-600 font-black uppercase tracking-widest italic tracking-tighter">Module Active</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button class="p-2 text-gray-500 group-hover:text-primary-400 transition-all transform group-hover:translate-x-1">
                            <i data-feather="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            feather.replace();
        }

        function formatTime(t) {
            if (!t) return '--:--';
            const [h, m] = t.split(':');
            const hh = parseInt(h);
            const ampm = hh >= 12 ? 'PM' : 'AM';
            const h12 = hh % 12 || 12;
            return `${h12}:${m} ${ampm}`;
        }
    </script>
</body>
</html>