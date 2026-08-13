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
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-lg font-black text-white uppercase tracking-tighter leading-none">Daily Timeline</h3>
                        <span id="tlDayChip" class="text-[9px] font-black text-primary-400 uppercase tracking-widest opacity-80">Syncing...</span>
                    </div>
                    <p id="tlNextUp" class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-5 min-h-[14px]">Loading your schedule...</p>
                    <div id="dailyTimeline" class="tl-root relative overflow-hidden">
                        <!-- Skeleton (default) -->
                        <div id="tlSkeleton" class="space-y-3 animate-pulse pt-4">
                            <div class="h-12 bg-white/[0.04] rounded-xl"></div>
                            <div class="h-12 bg-white/[0.04] rounded-xl"></div>
                            <div class="h-12 bg-white/[0.04] rounded-xl"></div>
                        </div>
                        <!-- Empty state (no classes today) -->
                        <div id="tlEmpty" class="hidden py-12 text-center">
                            <div class="mx-auto mb-4 w-14 h-14 rounded-2xl bg-white/[0.04] border border-white/5 flex items-center justify-center">
                                <i data-feather="calendar-off" class="w-6 h-6 text-gray-600"></i>
                            </div>
                            <p class="text-sm text-gray-400 font-bold">No subjects scheduled for today</p>
                            <p class="text-[10px] text-gray-600 font-bold uppercase tracking-widest mt-1">You're free — or check My Classes</p>
                        </div>
                        <!-- Time grid (rendered by JS) -->
                        <div id="tlGrid" class="hidden"></div>
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

    <!-- Daily Timeline Styles -->
    <style>
        .tl-root { min-height: 120px; }
        .tl-item {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 16px; border-radius: 14px;
            background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.015));
            border: 1px solid rgba(255,255,255,0.07);
            transition: border-color .2s, transform .2s, box-shadow .2s, opacity .2s;
        }
        .tl-item:hover { transform: translateX(4px); box-shadow: 0 8px 24px rgba(0,0,0,0.35); }
        .tl-item.tl-live { border-color: rgba(74,222,128,0.45); box-shadow: 0 0 20px rgba(74,222,128,0.08); }
        .tl-item.tl-done { opacity: 0.5; }
        .tl-item.tl-done:hover { opacity: 0.85; }
        .tl-time { display: flex; flex-direction: column; align-items: flex-end; min-width: 88px; }
        .tl-time-start { font-size: 13px; font-weight: 900; color: #fff; letter-spacing: 0.01em; line-height: 1.1; }
        .tl-time-end { font-size: 9px; font-weight: 700; color: rgba(255,255,255,0.38); letter-spacing: 0.08em; margin-top: 2px; }
        .tl-title { font-size: 13px; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tl-meta { font-size: 9px; font-weight: 700; color: rgba(255,255,255,0.40); letter-spacing: 0.08em; margin-top: 3px; text-transform: uppercase; }
        .tl-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 8px; font-weight: 900; letter-spacing: 0.12em;
            padding: 2px 7px; border-radius: 999px; text-transform: uppercase; white-space: nowrap;
        }
        .tl-badge-live { background: rgba(74,222,128,0.12); color: #4ade80; border: 1px solid rgba(74,222,128,0.25); }
        .tl-badge-up   { background: rgba(96,165,250,0.10); color: #60a5fa; border: 1px solid rgba(96,165,250,0.22); }
        .tl-badge-session { background: rgba(251,191,36,0.10); color: #fbbf24; border: 1px solid rgba(251,191,36,0.22); }
        .tl-badge-done { background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.40); border: 1px solid rgba(255,255,255,0.10); }
        .tl-badge-ok   { color: #4ade80; }
        .tl-badge-warn { color: #fbbf24; }
        .tl-badge-miss { color: #f87171; }
        .tl-dot {
            width: 6px; height: 6px; border-radius: 999px; display: inline-block;
            background: currentColor; animation: tlPulse 1.6s ease-in-out infinite;
        }
        @keyframes tlPulse { 0%,100% { opacity: 1; } 50% { opacity: 0.25; } }
    </style>

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

        // ===================== DAILY TIMELINE =====================
        const TL_DAYS = ['SU', 'M', 'T', 'W', 'TH', 'F', 'S'];
        let tlCache = [];
        let tlStarted = false;

        function tlDayCode(d) { return TL_DAYS[d.getDay()]; }

        function tlTokens(s) {
            const t = [];
            s = String(s || '');
            for (let j = 0; j < s.length;) {
                const two = s.substr(j, 2);
                if (two === 'TH' || two === 'SU') { t.push(two); j += 2; }
                else { t.push(s[j]); j++; }
            }
            return t;
        }

        function tlOnDay(schedule, dayCode) { return tlTokens(schedule).includes(dayCode); }

        function tlMin(t) {
            if (!t) return null;
            const p = String(t).split(':');
            return parseInt(p[0], 10) * 60 + (parseInt(p[1], 10) || 0);
        }

        function tlFmt(t) {
            if (!t) return 'TBA';
            const p = String(t).split(':');
            let h = parseInt(p[0], 10);
            const m = p[1];
            const ap = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return h + ':' + m + ' ' + ap;
        }

        function tlEsc(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        async function loadTimeline(user) {
            try {
                const classes = await api('/classes.php?student_uid=' + user.uid);
                const now = new Date();
                tlCache = (classes || []).filter(c => tlOnDay(c.schedule, tlDayCode(now)));
                renderTimeline(now);
            } catch (e) {
                console.error('[timeline]', e);
                const nextUp = document.getElementById('tlNextUp');
                if (nextUp) nextUp.textContent = 'Timeline sync failed — refresh the page';
            }
        }

        function renderTimeline(now) {
            const grid = document.getElementById('tlGrid');
            const skeleton = document.getElementById('tlSkeleton');
            const empty = document.getElementById('tlEmpty');
            const chip = document.getElementById('tlDayChip');
            const nextUp = document.getElementById('tlNextUp');
            if (!grid) return;

            if (chip) {
                chip.textContent = now.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
            }

            if (tlCache.length === 0) {
                skeleton.classList.add('hidden');
                empty.classList.remove('hidden');
                grid.classList.add('hidden');
                if (nextUp) nextUp.textContent = 'No classes today — you\'re free';
                return;
            }
            empty.classList.add('hidden');

            tlCache.sort((a, b) => tlMin(a.start_time) - tlMin(b.start_time));
            const nowMin = now.getHours() * 60 + now.getMinutes();
            const upcoming = [];

            let html = '<div class="space-y-3">';
            tlCache.forEach(c => {
                const s = tlMin(c.start_time);
                const e = tlMin(c.end_time);
                const end = (e === null || e <= s) ? (s === null ? nowMin : s + 60) : e;

                // Status is decided from the CLIENT clock + class times, so the
                // display never depends on the server's clock/timezone:
                //  - before start time        → Upcoming
                //  - inside the time window   → In Session (or Live Session when
                //    the teacher started attendance: session_active = 1)
                //  - after the end time       → Passed
                const inWindow = s !== null && nowMin >= s && nowMin < end;
                const live = inWindow && Number(c.session_active) === 1;
                const inSession = inWindow && !live;
                const done = !inWindow && end <= nowMin;
                if (!done && !inWindow && (s === null || s > nowMin)) upcoming.push(c);

                const accent = live ? 'border-l-green-500' : (inSession ? 'border-l-amber-500' : (done ? 'border-l-gray-700' : 'border-l-primary-500'));
                const badge = live
                    ? '<span class="tl-badge tl-badge-live"><span class="tl-dot"></span>Ongoing</span>'
                    : inSession
                        ? '<span class="tl-badge tl-badge-session"><span class="tl-dot"></span>In Progress</span>'
                        : done
                            ? '<span class="tl-badge tl-badge-done">Passed</span>'
                            : '<span class="tl-badge tl-badge-up">Upcoming</span>';

                let att = '';
                if (c.attendedToday) {
                    const late = c.todayStatus === 'Late';
                    att = '<span class="tl-badge ' + (late ? 'tl-badge-warn' : 'tl-badge-ok') + '">' + (late ? 'Late' : '\u2713 Present') + '</span>';
                } else if (done) {
                    att = '<span class="tl-badge tl-badge-miss">Missed</span>';
                }

                html += '<a href="student_class_view.php?id=' + encodeURIComponent(c.id) + '" class="tl-item border-l-4 ' + accent + ' '
                    + (live ? 'tl-live' : '') + (done ? ' tl-done' : '') + '">'
                    + '<div class="tl-time"><span class="tl-time-start">' + tlFmt(c.start_time) + '</span>'
                    + '<span class="tl-time-end">' + tlFmt(c.end_time) + '</span></div>'
                    + '<div class="flex-1 min-w-0">'
                    + '<div class="flex items-center justify-between gap-2">'
                    + '<span class="tl-title">' + tlEsc(c.class_name) + '</span>'
                    + '<span class="flex items-center gap-1.5">' + att + badge + '</span></div>'
                    + '<div class="tl-meta">' + tlEsc(c.subject) + '<span class="opacity-50">&nbsp;&bull;&nbsp;</span>' + tlEsc(c.teacher_name) + '&nbsp;&bull;&nbsp;' + tlEsc(c.section_name) + '</div>'
                    + '</div>'
                    + '<i data-feather="chevron-right" class="w-4 h-4 text-gray-600"></i>'
                    + '</a>';
            });
            html += '</div>';

            grid.innerHTML = html;
            grid.classList.remove('hidden');
            skeleton.classList.add('hidden');
            feather.replace();

            if (nextUp) {
                const anyLive = tlCache.some(c => {
                    const s = tlMin(c.start_time);
                    const e = tlMin(c.end_time);
                    const end = (e === null || e <= s) ? (s === null ? nowMin : s + 60) : e;
                    return s !== null && nowMin >= s && nowMin < end;
                });
                if (anyLive) {
                    nextUp.textContent = 'In session right now — tap a subject to enter class';
                } else if (upcoming.length) {
                    const n = upcoming[0];
                    nextUp.textContent = 'Next up: ' + n.class_name + ' at ' + tlFmt(n.start_time);
                } else {
                    nextUp.textContent = 'All subjects done for today';
                }
            }
        }

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

        window.addEventListener('profileLoaded', (e) => {
            loadStats();
            if (!tlStarted) {
                tlStarted = true;
                loadTimeline(e.detail || window.csProfile);
                setInterval(() => renderTimeline(new Date()), 60000);
                setInterval(() => loadTimeline(e.detail || window.csProfile), 30000);
            }
        });
        // Also try immediately if profile already loaded
        if (window.csProfile) {
            loadStats();
            if (!tlStarted) {
                tlStarted = true;
                loadTimeline(window.csProfile);
                setInterval(() => renderTimeline(new Date()), 60000);
                setInterval(() => loadTimeline(window.csProfile), 30000);
            }
        }
    </script>
</body>
</html>