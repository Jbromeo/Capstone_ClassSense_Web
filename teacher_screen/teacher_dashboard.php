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
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

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
                <div class="relative hidden md:block">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i data-feather="search" class="h-4 w-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i>
                    </div>
                    <input id="classSearchInput" type="text" class="bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-full focus:ring-primary-500 focus:border-primary-500 block w-64 pl-10 p-2.5 transition-all focus:w-80 placeholder-gray-600" placeholder="Search classes...">
                    <div id="searchPanel" class="hidden absolute top-full right-0 mt-3 w-96 glass-panel rounded-2xl border border-white/10 shadow-2xl z-50 overflow-hidden" style="max-height: 480px;">
                        <div class="p-4 border-b border-dark-border flex items-center justify-between">
                            <h4 class="text-xs font-black text-white uppercase tracking-widest">Classes</h4>
                            <span id="searchResultCount" class="hidden px-2 py-0.5 bg-primary-500/20 text-primary-400 text-[9px] font-black rounded-full"></span>
                        </div>
                        <div id="searchResultsList" class="overflow-y-auto" style="max-height: 360px;">
                            <div class="p-8 text-center">
                                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest italic">Type to search classes...</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors group">
                        <i data-feather="bell"></i>
                        <span class="notif-dot hidden absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                    </button>
                    <?php include '../includes/notification_popover.php'; ?>
                </div>
                <button class="p-2 text-gray-400 hover:text-white transition-colors md:hidden">
                    <i data-feather="search"></i>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            <!-- Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
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
                            <button class="flex flex-col items-center justify-center p-4 bg-dark-bg/50 hover:bg-dark-bg border border-dark-border rounded-xl transition-all hover:-translate-y-1 group" onclick="window.location.href='students.php'">
                                <div class="p-2 bg-blue-500/10 rounded-full group-hover:bg-blue-500/20 transition-colors mb-2"><i data-feather="user-plus" class="w-5 h-5 text-blue-500"></i></div>
                                <span class="text-xs font-medium">Add Student</span>
                            </button>
                            <button class="flex flex-col items-center justify-center p-4 bg-dark-bg/50 hover:bg-dark-bg border border-dark-border rounded-xl transition-all hover:-translate-y-1 group" onclick="window.location.href='classes.php'">
                                <div class="p-2 bg-amber-500/10 rounded-full group-hover:bg-amber-500/20 transition-colors mb-2"><i data-feather="clipboard" class="w-5 h-5 text-amber-500"></i></div>
                                <span class="text-xs font-medium">Grade Submissions</span>
                            </button>
                            <button class="flex flex-col items-center justify-center p-4 bg-dark-bg/50 hover:bg-dark-bg border border-dark-border rounded-xl transition-all hover:-translate-y-1 group" onclick="window.location.href='attendance.php'">
                                <div class="p-2 bg-green-500/10 rounded-full group-hover:bg-green-500/20 transition-colors mb-2"><i data-feather="check-square" class="w-5 h-5 text-green-500"></i></div>
                                <span class="text-xs font-medium">Take Attendance</span>
                            </button>
                            <button class="flex flex-col items-center justify-center p-4 bg-dark-bg/50 hover:bg-dark-bg border border-dark-border rounded-xl transition-all hover:-translate-y-1 group" onclick="window.location.href='classes.php'">
                                <div class="p-2 bg-purple-500/10 rounded-full group-hover:bg-purple-500/20 transition-colors mb-2"><i data-feather="layers" class="w-5 h-5 text-purple-500"></i></div>
                                <span class="text-xs font-medium">My Classes</span>
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
<script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';

        const DAYS_MAP = { 'M': 'Monday', 'T': 'Tuesday', 'W': 'Wednesday', 'TH': 'Thursday', 'F': 'Friday', 'S': 'Saturday', 'SU': 'Sunday' };
        const CURRENT_DAY_CODE = (() => {
            const days = ['SU', 'M', 'T', 'W', 'TH', 'F', 'S'];
            return days[new Date().getDay()];
        })();

        let currentTeacher = null;
        let allClasses = [];
        let lastDashboardSig = '';

        // Search bar + popover
        const searchInput = document.getElementById('classSearchInput');
        const searchPanel = document.getElementById('searchPanel');
        const searchResultsList = document.getElementById('searchResultsList');
        const searchResultCount = document.getElementById('searchResultCount');

        function renderSearchResults(term) {
            term = (term || '').toLowerCase().trim();
            const filtered = term
                ? allClasses.filter(c =>
                    (c.class_name && c.class_name.toLowerCase().includes(term)) ||
                    (c.subject && c.subject.toLowerCase().includes(term)) ||
                    (c.section_name && c.section_name.toLowerCase().includes(term)))
                : allClasses;

            if (searchResultCount) {
                searchResultCount.textContent = filtered.length;
                searchResultCount.classList.toggle('hidden', filtered.length === 0);
            }

            if (filtered.length === 0) {
                searchResultsList.innerHTML = `<div class="p-8 text-center"><div class="w-10 h-10 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-3"><i data-feather="search" class="w-5 h-5 text-gray-600"></i></div><p class="text-[11px] text-gray-500 font-medium">No classes match "${term}"</p></div>`;
                feather.replace();
                return;
            }

            const showAll = !term && allClasses.length <= 8;
            const results = showAll ? filtered : filtered.slice(0, 8);

            searchResultsList.innerHTML = results.map(c => `
                <div class="search-item px-4 py-3 border-b border-white/5 cursor-pointer hover:bg-white/5 transition-colors" onclick="window.location.href='class_view.php?id=${c.id}'">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-primary-500/10 rounded-full flex items-center justify-center mt-0.5">
                            <i data-feather="book-open" class="w-4 h-4 text-primary-400"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-bold text-white truncate">${c.class_name}</p>
                                <span class="flex-shrink-0 px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[9px] uppercase font-black">${c.section_name || 'N/A'}</span>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-0.5 truncate">${c.subject || ''} &bull; ${c.schedule || 'TBA'} &bull; ${c.time_slot || 'TBA'}</p>
                        </div>
                    </div>
                </div>
            `).join('');

            if (!showAll && filtered.length > 8) {
                searchResultsList.innerHTML += `<div class="px-4 py-3 text-center"><p class="text-[9px] text-gray-600 font-black uppercase tracking-widest italic">${filtered.length - 8} more &mdash; keep typing to narrow</p></div>`;
            }
            feather.replace();
        }

        if (searchInput) {
            searchInput.addEventListener('focus', () => {
                renderSearchResults(searchInput.value);
                searchPanel.classList.remove('hidden');
            });
            searchInput.addEventListener('keyup', () => {
                renderSearchResults(searchInput.value);
                searchPanel.classList.remove('hidden');
                const term = searchInput.value.toLowerCase().trim();
                if (!term) {
                    renderClassesOverview(allClasses);
                    return;
                }
                const filtered = allClasses.filter(c =>
                    (c.class_name && c.class_name.toLowerCase().includes(term)) ||
                    (c.subject && c.subject.toLowerCase().includes(term)) ||
                    (c.section_name && c.section_name.toLowerCase().includes(term))
                );
                renderClassesOverview(filtered);
            });
            document.addEventListener('click', (e) => {
                if (!searchPanel.contains(e.target) && !searchInput.contains(e.target)) {
                    searchPanel.classList.add('hidden');
                }
            });
        }

        initPage(() => {
            setTimeout(() => loadDashboardData(), 500);
            setInterval(loadDashboardData, 5000);
        });

        async function loadDashboardData() {
            try {
                const classes = await api('/classes.php');
                const sig = JSON.stringify(classes.map(c => [
                    c.id, c.class_name, c.level, c.subject, c.section_name, c.class_code,
                    c.schedule, c.start_time, c.end_time, c.time_slot, c.session_limit, c.status,
                    (c.students || []).slice().sort().join(',')
                ]));
                if (sig === lastDashboardSig) return;
                lastDashboardSig = sig;
                allClasses = classes;
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
                                    <span class="text-[9px] font-mono">${error.message}</span>
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
                return;
            }

            try {
                const recordsPerClass = await Promise.all(
                    classes.map(c => api('/attendance.php?class_id=' + c.id).catch(() => []))
                );
                const allRecords = recordsPerClass.flat();

                const now = new Date();
                const lastWeek = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                const prevWeek = new Date(now.getTime() - 14 * 24 * 60 * 60 * 1000);

                const lastWeekRecords = allRecords.filter(r => {
                    const d = r.date ? new Date(r.date) : null;
                    return d && d >= lastWeek;
                });
                const prevWeekRecords = allRecords.filter(r => {
                    const d = r.date ? new Date(r.date) : null;
                    return d && d >= prevWeek && d < lastWeek;
                });

                const calcAvg = (records) => {
                    if (records.length === 0) return 0;

                    const sessions = {};
                    records.forEach(r => {
                        const key = `${r.class_id}_${r.date}`;
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

                avgDisplay.textContent = `${Math.round(lastWeekAvg)}%`;
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

        const DAY_CHIP_ORDER = ['M', 'T', 'W', 'TH', 'F', 'S', 'SU'];

        function renderDayChips(schedule) {
            if (!schedule) return '';
            const chips = DAY_CHIP_ORDER.map(code => {
                const active = schedule.includes(code);
                if (!active) return '';
                const isToday = code === CURRENT_DAY_CODE;
                return `<span class="px-1.5 py-0.5 rounded ${isToday ? 'bg-primary-500 text-white font-black shadow-[0_0_8px_rgba(220,38,38,0.5)]' : 'bg-white/10 text-gray-400 font-bold'}">${code}</span>`;
            }).join('');
            return chips ? `<div class="flex items-center gap-1.5">${chips}</div>` : '';
        }

        function renderTodaySchedule(classes) {
            const container = document.getElementById('todayScheduleList');
            const todayClasses = classes.filter(c => c.schedule && c.schedule.includes(CURRENT_DAY_CODE))
                                       .sort((a, b) => a.start_time.localeCompare(b.start_time));

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

            todayClasses.forEach((c) => {
                const isNow = nowStr >= c.start_time && nowStr <= c.end_time;
                const isPast = nowStr > c.end_time;
                
                html += `
                    <div class="relative pl-12 pb-8 group">
                        <div class="absolute left-[15px] top-1.5 w-4 h-4 rounded-full ${isNow ? 'bg-primary-500 shadow-[0_0_10px_rgba(220,38,38,0.5)]' : (isPast ? 'bg-gray-700' : 'bg-gray-500')} ring-4 ring-dark-bg z-10 transition-all"></div>
                        <div class="glass-panel border ${isNow ? 'border-primary-500/30 bg-primary-500/5' : 'border-dark-border'} rounded-xl p-4 hover:bg-white/5 transition-all cursor-pointer" onclick="window.location.href='class_view.php?id=${c.id}'">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="text-[10px] font-bold ${isNow ? 'text-primary-400' : 'text-gray-500'} uppercase tracking-wide italic">${isNow ? 'Now' : (isPast ? 'Completed' : 'Upcoming')}</span>
                                    <h4 class="text-md font-bold ${isPast ? 'text-gray-500' : 'text-white'}">${c.class_name}</h4>
                                </div>
                                <span class="text-sm ${isPast ? 'text-gray-600' : 'text-gray-300'} font-mono">${formatTime(c.start_time)} - ${formatTime(c.end_time)}</span>
                            </div>
                            <div class="flex items-center gap-4 text-[10px] text-gray-500 font-black uppercase tracking-widest italic tracking-tighter">
                                <div class="flex items-center gap-1"><i data-feather="book-open" class="w-3 h-3"></i> ${c.section_name || 'Section TBA'}</div>
                                <div class="flex items-center gap-1"><i data-feather="users" class="w-3 h-3"></i> ${c.students ? c.students.length : 0} Students</div>
                            </div>
                            <div class="mt-3 flex items-center gap-1.5">
                                <i data-feather="repeat" class="w-3 h-3 text-gray-600"></i>
                                ${renderDayChips(c.schedule)}
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
                        <div class="font-bold text-white">${c.class_name}</div>
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