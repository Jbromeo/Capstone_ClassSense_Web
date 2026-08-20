<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- student_screen/student_attendance_record.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | Attendance Records</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-purple-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 4s"></div>
    </div>

    <?php 
    // setActivePage is handled inside sidebar based on filename
    include 'student_sidebar.php'; 
    ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        <!-- HEADER -->
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                    <i data-feather="menu"></i>
                </button>
                <h2 class="text-xl font-bold text-white hidden sm:block">Attendance Records</h2>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative hidden md:block">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i data-feather="search" class="h-4 w-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i>
                    </div>
                    <input id="recordSearch" type="text" class="w-64 md:w-80 bg-dark-bg border border-dark-border text-white text-sm rounded-xl pl-10 pr-4 py-2.5 focus:ring-primary-500 focus:border-primary-500 placeholder-gray-600" placeholder="Search records..." autocomplete="off">
                </div>

                <div class="relative">
                    <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors">
                        <i data-feather="bell"></i>
                        <span class="notif-dot hidden absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                    </button>
                    <?php include '../includes/notification_popover.php'; ?>
                </div>
                <button id="mobileSearchBtn" class="p-2 text-gray-400 hover:text-white transition-colors rounded-lg hover:bg-white/5 md:hidden" aria-label="Toggle search">
                    <i data-feather="search" class="w-5 h-5"></i>
                </button>
            </div>
        </header>

        <!-- SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8">

            <!-- SUMMARY CARDS -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                <div class="glass-panel rounded-xl p-6 border-l-4 border-l-green-500 hover-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black text-gray-500 uppercase tracking-widest italic">Present</p>
                            <h3 id="statPresent" class="text-3xl font-bold text-green-400 mt-1">--</h3>
                        </div>
                        <div class="p-3 bg-green-500/10 rounded-xl text-green-500">
                            <i data-feather="check-circle" class="w-6 h-6"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-panel rounded-xl p-6 border-l-4 border-l-red-500 hover-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black text-gray-500 uppercase tracking-widest italic">Absent</p>
                            <h3 id="statAbsent" class="text-3xl font-bold text-red-400 mt-1">--</h3>
                        </div>
                        <div class="p-3 bg-red-500/10 rounded-xl text-red-500">
                            <i data-feather="x-circle" class="w-6 h-6"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-panel rounded-xl p-6 border-l-4 border-l-amber-500 hover-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black text-gray-500 uppercase tracking-widest italic">Late</p>
                            <h3 id="statLate" class="text-3xl font-bold text-amber-400 mt-1">--</h3>
                        </div>
                        <div class="p-3 bg-amber-500/10 rounded-xl text-amber-500">
                            <i data-feather="clock" class="w-6 h-6"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-panel rounded-xl p-6 border-l-4 border-l-primary-500 hover-card">
                    <div class="flex flex-col h-full justify-between">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-black text-gray-500 uppercase tracking-widest italic">Overall</p>
                                <h3 id="statOverall" class="text-3xl font-bold text-primary-400 mt-1">--%</h3>
                            </div>
                            <div class="p-3 bg-primary-500/10 rounded-xl text-primary-500">
                                <i data-feather="trending-up" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <div id="statBar" class="mt-4 h-2 bg-dark-border rounded-full overflow-hidden">
                            <div class="h-full bg-primary-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                        <p id="statStanding" class="text-[10px] font-black uppercase tracking-widest italic mt-1"></p>
                    </div>
                </div>
            </div>

            <!-- FILTER & SEARCH BAR -->
            <div class="glass-panel rounded-2xl p-4 md:p-6 mb-6 border border-dark-border">
                <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                    <div class="flex items-center gap-4 flex-wrap">
                        <div class="hidden md:block">
                            <label class="text-xs font-black text-gray-500 uppercase tracking-widest italic mb-2 block ml-1">Class</label>
                            <select id="classFilter" class="bg-dark-bg border border-dark-border text-white rounded-xl px-4 py-2.5 focus:ring-primary-500 focus:border-primary-500 uppercase font-black italic tracking-tighter text-sm">
                                <option value="">All Classes</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500 font-black uppercase tracking-widest italic">
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> Present</span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Late</span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Absent</span>
                        </div>
                    </div>
                    <span id="recordCountLabel" class="text-xs text-gray-500 font-black uppercase tracking-widest italic">Showing 0 of 0 records</span>
                </div>
            </div>

            <!-- ATTENDANCE TABLE WITH LAZY SCROLL -->
            <div class="glass-panel rounded-2xl border border-dark-border overflow-hidden">
                <div id="tableScrollArea" class="max-h-[60vh] overflow-y-auto custom-scroll">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-dark-surface text-gray-500 uppercase text-xs font-black tracking-widest border-b border-dark-border">
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Class</th>
                                <th class="px-6 py-4">Teacher</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceTableBody" class="text-sm divide-y divide-dark-border"></tbody>
                    </table>

                    <!-- Load More Sentinel (Lazy Scroll) -->
                    <div id="loadMoreSentinel" class="p-10 text-center border-t border-white/[0.02]">
                        <div id="lazyLoader" class="flex flex-col items-center gap-3 opacity-0 transition-opacity duration-300">
                            <div class="w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic">Loading more records...</p>
                        </div>
                        <p id="endOfList" class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic hidden">No more records</p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>

    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';

        initPage(async (user) => {
            const currentUid = user.uid;

            const tableBody = document.getElementById('attendanceTableBody');
            const recordSearch = document.getElementById('recordSearch');
            const classFilter = document.getElementById('classFilter');
            const recordCountLabel = document.getElementById('recordCountLabel');
            const sentinel = document.getElementById('loadMoreSentinel');
            const scrollArea = document.getElementById('tableScrollArea');
            const lazyLoader = document.getElementById('lazyLoader');
            const endOfList = document.getElementById('endOfList');

            let allRecords = [];
            let filteredRecords = [];
            let renderedCount = 0;
            let isLoading = false;
            let hasMore = false;
            const BATCH_SIZE = 20;
            let classMap = {};

            const formatDate = (val) => {
                if (!val) return '—';
                const d = new Date((val || '').toString().replace(' ', 'T'));
                if (isNaN(d.getTime())) return val;
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            };

            const getStatusConfig = (status) => {
                switch (status) {
                    case 'Present': return { label: 'Present', bg: 'bg-green-500/10', text: 'text-green-400', border: 'border-green-500/20' };
                    case 'Late':    return { label: 'Late', bg: 'bg-amber-500/10', text: 'text-amber-400', border: 'border-amber-500/20' };
                    case 'Absent':  return { label: 'Absent', bg: 'bg-red-500/10', text: 'text-red-400', border: 'border-red-500/20' };
                    default:        return { label: status, bg: 'bg-gray-500/10', text: 'text-gray-400', border: 'border-gray-500/20' };
                }
            };

            const renderRow = (r) => {
                const cls = classMap[r.class_id] || {};
                const classLabel = cls.class_name || 'Unknown Class';
                const subParts = [];
                if (cls.section_name) subParts.push(`Section ${cls.section_name}`);
                const classSub = subParts.join(' · ');
                const teacher = cls.teacher_name || '—';
                const cfg = getStatusConfig(r.status);
                return `<tr class="hover:bg-white/5 transition-colors group">
                    <td class="px-6 py-4 font-mono text-blue-400 text-xs font-black tracking-tight">${formatDate(r.date)}</td>
                    <td class="px-6 py-4">
                        <span class="text-white font-medium uppercase italic tracking-tighter">${classLabel}</span>
                        ${classSub ? `<div class="text-[10px] text-gray-500 font-black uppercase italic tracking-tighter mt-0.5">${classSub}</div>` : ''}
                    </td>
                    <td class="px-6 py-4 text-gray-300 font-medium italic">${teacher}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1.5 rounded-full ${cfg.bg} border ${cfg.border} ${cfg.text} text-[10px] font-black uppercase tracking-widest italic">${cfg.label}</span>
                    </td>
                </tr>`;
            };

            const renderBatch = () => {
                const batch = filteredRecords.slice(renderedCount, renderedCount + BATCH_SIZE);
                if (!batch.length) return;
                tableBody.insertAdjacentHTML('beforeend', batch.map(renderRow).join(''));
                renderedCount += batch.length;
                updateCountLabel();
                if (renderedCount >= filteredRecords.length) {
                    hasMore = false;
                    lazyLoader.style.opacity = '0';
                    endOfList.classList.remove('hidden');
                }
            };

            const renderInitialBatch = () => {
                renderedCount = 0;
                tableBody.innerHTML = '';
                if (!filteredRecords.length) {
                    tableBody.innerHTML = `<tr><td colspan="4" class="px-6 py-12 text-center text-gray-500 font-black uppercase italic tracking-widest text-xs">No attendance records found</td></tr>`;
                    hasMore = false;
                    lazyLoader.style.opacity = '0';
                    endOfList.classList.remove('hidden');
                    updateCountLabel();
                    return;
                }
                lazyLoader.style.opacity = '0';
                endOfList.classList.add('hidden');
                hasMore = true;
                renderBatch();
            };

            const updateCountLabel = () => {
                recordCountLabel.innerHTML = `<span>Showing ${renderedCount} of ${filteredRecords.length} record${filteredRecords.length !== 1 ? 's' : ''}</span>`;
            };

            const loadMore = () => {
                if (isLoading || !hasMore) return;
                isLoading = true;
                lazyLoader.style.opacity = '1';
                setTimeout(() => { renderBatch(); isLoading = false; }, 300);
            };

            const applyFilters = () => {
                const term = (recordSearch?.value || '').toLowerCase().trim();
                const classId = classFilter?.value || '';
                filteredRecords = allRecords.filter(r => {
                    if (classId && r.class_id !== classId) return false;
                    if (!term) return true;
                    const cls = classMap[r.class_id] || {};
                    const haystack = [r.date, r.status, cls.class_name || '', cls.class_code || '', cls.teacher_name || '', cls.section_name || ''].join(' ').toLowerCase();
                    return haystack.includes(term);
                });
                renderInitialBatch();
                scrollArea.scrollTo({ top: 0 });
            };

            const loadData = async () => {
                try {
                    const classes = await api('/classes.php?student_uid=' + currentUid);
                    classMap = {};
                    classes.forEach(c => { classMap[c.id] = c; });
                    classFilter.innerHTML = '<option value="">All Classes</option>' +
                        classes.map(c => `<option value="${c.id}">${c.class_name} (${c.section_name || '—'})</option>`).join('');

                    const stats = await api('/student_stats.php');
                    document.getElementById('statPresent').textContent = stats.present || 0;
                    document.getElementById('statAbsent').textContent = stats.absent || 0;
                    document.getElementById('statLate').textContent = stats.late || 0;
                    const rate = stats.attendanceRate !== null ? stats.attendanceRate + '%' : '—';
                    document.getElementById('statOverall').textContent = rate;
                    const bar = document.getElementById('statBar').firstElementChild;
                    const standing = document.getElementById('statStanding');
                    if (stats.attendanceRate !== null) {
                        bar.style.width = stats.attendanceRate + '%';
                        if (stats.attendanceRate >= 80) {
                            standing.textContent = 'Good Standing';
                            standing.className = 'text-[10px] font-black uppercase tracking-widest italic mt-1 text-green-400';
                        } else {
                            standing.textContent = 'Needs Attention';
                            standing.className = 'text-[10px] font-black uppercase tracking-widest italic mt-1 text-amber-400';
                        }
                    } else {
                        bar.style.width = '0%';
                        standing.textContent = '';
                    }

                    allRecords = await api('/attendance.php?student_uid=' + currentUid);
                    filteredRecords = [...allRecords];
                    renderInitialBatch();
                } catch (err) {
                    console.error('Attendance load error:', err);
                    tableBody.innerHTML = `<tr><td colspan="4" class="px-6 py-12 text-center text-primary-500 font-black uppercase italic tracking-widest text-xs">Failed to load: ${err.message || 'Connection error'}</td></tr>`;
                }
            };

            await loadData();

            if (recordSearch) {
                let searchTimer = null;
                recordSearch.addEventListener('input', () => {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(applyFilters, 150);
                });
            }
            if (classFilter) classFilter.addEventListener('change', applyFilters);

            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && hasMore) loadMore();
            }, { root: scrollArea, threshold: 0 });
            observer.observe(sentinel);

            const mobileSearchBtn = document.getElementById('mobileSearchBtn');
            if (mobileSearchBtn && recordSearch) {
                mobileSearchBtn.addEventListener('click', () => {
                    recordSearch.classList.toggle('hidden');
                    recordSearch.classList.toggle('block');
                    if (!recordSearch.classList.contains('hidden')) recordSearch.focus();
                });
            }

            feather.replace();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
    </script>
    <script src="../assets/js/theme-toggle.js" defer></script>
    <script type="module" src="student_auth.js"></script>
</body>
</html>
