<?php
require_once dirname(__DIR__) . '/core/init.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | Attendance Logs</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-32 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LCAyNTUsIDI1NSwgMC4wNSkiLz48L3N2Zz4=')] [mask-image:linear-gradient(to_bottom,white,transparent)]"></div>
    </div>

    <?php include 'sidebar.php'; ?>
    <div id="toastContainer" class="fixed top-5 right-5 z-[100] flex flex-col gap-3"></div>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">

        <!-- Header -->
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-bold text-white hidden sm:block">Attendance Registry</h2>
            </div>
            <div class="flex items-center gap-3">
                <input type="date" id="dateFilter" class="bg-dark-surface border border-dark-border text-white text-xs rounded-lg p-2.5 focus:ring-primary-500 focus:border-primary-500 italic font-black uppercase tracking-widest">
                <button onclick="refreshLogs()" class="p-2.5 bg-dark-surface border border-dark-border rounded-lg text-gray-400 hover:text-white transition-all">
                    <i data-feather="refresh-cw" class="w-4 h-4"></i>
                </button>
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

            <div class="max-w-6xl mx-auto">
                <!-- Records Control Bar -->
                <div class="mb-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div class="flex-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic mb-2 block ml-1">Class</label>
                            <select id="classFilter" class="w-full md:w-80 bg-dark-surface border border-dark-border text-white text-sm rounded-xl p-4 focus:ring-primary-500 focus:border-primary-500 uppercase font-black italic tracking-tighter">
                                <option value="">Loading classes...</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="relative hidden md:block">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-feather="search" class="h-4 w-4 text-gray-500"></i>
                                </div>
                                <input id="rowSearch" type="text" class="bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-full focus:ring-primary-500 focus:border-primary-500 block w-64 pl-10 p-2.5 transition-all focus:w-80 placeholder-gray-600" placeholder="Search student...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Class Header -->
                <div id="selectedClassHeader" class="hidden glass-panel rounded-2xl px-6 py-4 mb-6 border border-primary-500/20 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-500/15 border border-primary-500/20 flex items-center justify-center shrink-0">
                        <i data-feather="book-open" class="w-6 h-6 text-primary-400"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[9px] font-black uppercase tracking-widest italic text-primary-400 mb-1">Selected Class</p>
                        <h3 id="selectedClassName" class="text-lg font-black text-white uppercase tracking-tight italic truncate leading-none">Class Name</h3>
                        <p id="selectedClassSection" class="text-[11px] text-gray-400 font-bold uppercase tracking-wider italic mt-1">Section</p>
                    </div>
                </div>

                <!-- Stats Strip -->
                <div id="statsStrip" class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                    <div class="glass-panel px-4 py-3 rounded-xl flex items-center gap-3"><i data-feather="check-circle" class="w-5 h-5 text-green-400"></i><div><p class="text-xs text-gray-500 font-black uppercase tracking-widest">Present</p><p id="statPresent" class="text-xl font-bold text-white">0</p></div></div>
                    <div class="glass-panel px-4 py-3 rounded-xl flex items-center gap-3"><i data-feather="clock" class="w-5 h-5 text-yellow-400"></i><div><p class="text-xs text-gray-500 font-black uppercase tracking-widest">Late</p><p id="statLate" class="text-xl font-bold text-white">0</p></div></div>
                    <div class="glass-panel px-4 py-3 rounded-xl flex items-center gap-3"><i data-feather="x-circle" class="w-5 h-5 text-red-400"></i><div><p class="text-xs text-gray-500 font-black uppercase tracking-widest">Absent</p><p id="statAbsent" class="text-xl font-bold text-white">0</p></div></div>
                </div>

                <!-- Logs Table -->
                <div class="glass-panel rounded-2xl overflow-hidden border border-dark-border shadow-2xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-dark-border text-xs uppercase text-gray-500 font-bold tracking-wider bg-dark-bg/30">
                                    <th class="p-5 pl-8">Verified Student</th>
                                    <th class="p-5">Registry ID</th>
                                    <th class="p-5">Timestamp</th>
                                    <th class="p-5">Status</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody" class="text-sm">
                                <tr>
                                    <td colspan="4" class="p-20 text-center">
                                        <div class="flex flex-col items-center opacity-40 italic">
                                            <i data-feather="database" class="w-12 h-12 mb-4 text-gray-600"></i>
                                            <p class="text-sm font-bold uppercase tracking-widest">Select a class to load registry data</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Hub Status Picker Popover (fixed so it's never clipped by scrollable tables) -->
    <div id="historyStatusPicker" class="hidden fixed z-[200] min-w-[160px] glass-panel rounded-xl p-1.5 shadow-2xl animate-fade-in">
        <div class="flex flex-col gap-1">
            <button data-status="Present" class="hist-status-option flex items-center gap-2.5 px-3 py-2 rounded-lg text-[11px] font-black uppercase tracking-widest italic text-left transition-colors hover:bg-green-500/10 text-green-400">
                <i data-feather="check-circle" class="w-4 h-4"></i> Present
                <i data-feather="check" class="hist-status-check w-3.5 h-3.5 ml-auto hidden"></i>
            </button>
            <button data-status="Late" class="hist-status-option flex items-center gap-2.5 px-3 py-2 rounded-lg text-[11px] font-black uppercase tracking-widest italic text-left transition-colors hover:bg-amber-500/10 text-amber-400">
                <i data-feather="clock" class="w-4 h-4"></i> Late
                <i data-feather="check" class="hist-status-check w-3.5 h-3.5 ml-auto hidden"></i>
            </button>
            <button data-status="Absent" class="hist-status-option flex items-center gap-2.5 px-3 py-2 rounded-lg text-[11px] font-black uppercase tracking-widest italic text-left transition-colors hover:bg-red-500/10 text-red-400">
                <i data-feather="x-circle" class="w-4 h-4"></i> Absent
                <i data-feather="check" class="hist-status-check w-3.5 h-3.5 ml-auto hidden"></i>
            </button>
        </div>
    </div>

    <!-- Firebase Logic -->
    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';

        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer') || document.body;
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

        let allClasses = [];
        let lastClassesSig = '';
        const dateInput = document.getElementById('dateFilter');
        const classSelect = document.getElementById('classFilter');
        const rowSearch = document.getElementById('rowSearch');

        const _d = new Date();
        const todayStr = `${_d.getFullYear()}-${String(_d.getMonth() + 1).padStart(2, '0')}-${String(_d.getDate()).padStart(2, '0')}`;
        dateInput.value = todayStr;
        dateInput.max = todayStr;

        initPage(() => {
            setTimeout(() => loadClasses(), 500);
        });

        async function loadClasses() {
            try {
                const user = JSON.parse(sessionStorage.getItem('cs_user') || 'null');
                if (!user?.uid) {
                    classSelect.innerHTML = `<option value="">Session lost</option>`;
                    return;
                }
                const classes = await api('/classes.php');
                const sig = JSON.stringify(classes);
                if (sig === lastClassesSig) return;
                lastClassesSig = sig;
                allClasses = classes;

                classSelect.innerHTML = `<option value="">Select a class</option>` +
                    classes.map(c => `<option value="${c.id}">${c.class_name} — ${c.section_name || 'No Section'}</option>`).join('');

                feather.replace();
            } catch (error) {
                console.error("Hub Sync Error:", error);
                classSelect.innerHTML = `<option value="">Sync Protocol Denied</option>`;
            }
        }

        classSelect.onchange = refreshLogs;
        dateInput.onchange = refreshLogs;
        window.refreshLogs = refreshLogs;

        let lastStudentMap = {};

        async function refreshLogs() {
            const classId = classSelect.value;
            const date = dateInput.value;
            const tbody = document.getElementById('logsTableBody');
            const headerEl = document.getElementById('selectedClassHeader');
            const nameEl = document.getElementById('selectedClassName');
            const sectionEl = document.getElementById('selectedClassSection');

            if (!classId) {
                tbody.innerHTML = `<tr><td colspan="4" class="p-20 text-center"><div class="flex flex-col items-center opacity-40 italic"><i data-feather="database" class="w-12 h-12 mb-4 text-gray-600"></i><p class="text-sm font-bold uppercase tracking-widest">Select a class to load registry data</p></div></td></tr>`;
                resetStats();
                if (headerEl) headerEl.classList.add('hidden');
                feather.replace();
                return;
            }

            // Show selected class header
            const cls = allClasses.find(c => String(c.id) === String(classId));
            if (cls && headerEl && nameEl && sectionEl) {
                nameEl.textContent = cls.class_name;
                sectionEl.textContent = cls.section_name || 'No Section';
                headerEl.classList.remove('hidden');
            }

            tbody.innerHTML = `<tr><td colspan="4" class="p-20 text-center animate-pulse text-gray-500 italic">Syncing with Registry...</td></tr>`;
            resetStats();

            try {
                const cls = allClasses.find(c => String(c.id) === String(classId));
                const rosterUids = (cls && Array.isArray(cls.students)) ? cls.students : [];

                const records = await api('/attendance.php?class_id=' + classId + '&date=' + date);

                const needUids = [...new Set([...records.map(r => r.student_uid), ...rosterUids])];
                let studentMap = {};
                if (needUids.length) {
                    const students = await api('/fetch.php', { method: 'POST', body: JSON.stringify({ collection: 'students', uids: needUids }) });
                    students.forEach(s => { if (s.exists !== false) studentMap[s.uid] = s; });
                }
                lastStudentMap = studentMap;

                buildRows(records, studentMap);
                updateStats(records);
                feather.replace();
            } catch (error) {
                console.error("Log Sync Error:", error);
                tbody.innerHTML = `<tr><td colspan="4" class="p-20 text-center"><div class="flex flex-col items-center gap-2 opacity-50"><i data-feather="alert-octagon" class="w-8 h-8 text-primary-500 animate-pulse"></i><span class="text-xs font-black uppercase tracking-widest italic text-primary-400">Registry Sync Denied</span><span class="text-[9px] font-mono">${error.message}</span></div></td></tr>`;
                feather.replace();
            }
        }

        function resetStats() {
            ['statPresent','statLate','statAbsent'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '0';
            });
        }

        function updateStats(records) {
            let present = 0, late = 0, absent = 0;
            records.forEach(r => {
                const st = (r.status || '').toLowerCase();
                if (st === 'late') late++;
                else if (st === 'absent') absent++;
                else present++;
            });

            document.getElementById('statPresent').textContent = present;
            document.getElementById('statLate').textContent = late;
            document.getElementById('statAbsent').textContent = absent;
        }

        const STATUS_COLORS = {
            Present: 'bg-green-500/10 text-green-400 border-green-500/20',
            Verified: 'bg-green-500/10 text-green-400 border-green-500/20',
            Late: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
            Absent: 'bg-red-500/10 text-red-400 border-red-500/20'
        };

        function buildRows(records, studentMap) {
            const tbody = document.getElementById('logsTableBody');
            if (!records.length) {
                tbody.innerHTML = `<tr><td colspan="4" class="p-20 text-center text-gray-500 italic"><i data-feather="calendar" class="w-8 h-8 mx-auto mb-3 opacity-40"></i>No attendance recorded for <span class="font-black text-gray-400">${dateInput.value || 'this date'}</span> — select an earlier date.</td></tr>`;
                return;
            }

            const BADGE = {
                Present:  { bg: 'bg-green-500/10 text-green-400 border border-green-500/20', dot: 'bg-green-500',  label: 'Present' },
                Verified: { bg: 'bg-green-500/10 text-green-400 border border-green-500/20', dot: 'bg-green-500',  label: 'Present' },
                Late:     { bg: 'bg-amber-500/10 text-amber-400 border border-amber-500/30', dot: 'bg-amber-500',  label: 'Late'    },
                Absent:   { bg: 'bg-red-500/10   text-red-400   border border-red-500/30',   dot: 'bg-red-500',    label: 'Absent'  },
            };

            const rows = [];
            for (const rec of records) {
                const s = studentMap[rec.student_uid] || {
                    uid: rec.student_uid,
                    full_name: 'Student (' + rec.student_uid.substring(0, 6) + ')',
                    firstName: 'Student',
                    lastName: rec.student_uid.substring(0, 6),
                    studentId: 'N/A'
                };

                let avatarUrl = s.profilePhoto || s.profile_picture;
                if (!avatarUrl) {
                    const initials = `${s.firstName?.[0] || 'S'}${s.lastName?.[0] || 'T'}`.toUpperCase();
                    avatarUrl = `https://ui-avatars.com/api/?name=${initials}&background=ea2628&color=fff&bold=true`;
                }
                const status = rec.status || 'Present';
                const time = rec.timestamp ? new Date(rec.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}) : '--:--';

                rows.push({
                    docId: rec.id,
                    studentUid: rec.student_uid,
                    name: s.full_name || `${s.firstName || ''} ${s.lastName || ''}`.trim(),
                    id: s.studentId || s.student_id || 'N/A',
                    avatar: avatarUrl,
                    time: time,
                    status: status
                });
            }

            rows.sort((a, b) => b.time.localeCompare(a.time));

            tbody.innerHTML = rows.map((r, idx) => {
                const badge = BADGE[r.status] || BADGE.Present;
                return `
                <tr class="border-b border-dark-border hover:bg-white/5 transition-colors animate-pop-in"
                    style="animation-delay: ${idx * 50}ms"
                    data-student-uid="${r.studentUid}"
                    data-status="${r.status}">
                    <td class="p-5 pl-8">
                        <div class="flex items-center gap-4">
                            <img src="${r.avatar}" class="w-10 h-10 rounded-full object-cover ring-2 ring-dark-bg border border-white/10 shadow-lg">
                            <span class="font-black text-white uppercase italic tracking-tighter">${r.name}</span>
                        </div>
                    </td>
                    <td class="p-5 text-gray-400 font-mono text-xs uppercase tracking-tighter">${r.id}</td>
                    <td class="p-5 text-gray-400 text-xs font-black uppercase tracking-widest italic opacity-60">${r.time}</td>
                    <td class="p-5">
                        <button type="button"
                            class="hist-status-badge cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full ${badge.bg} text-[9px] font-black uppercase tracking-widest italic transition-all hover:ring-2 hover:ring-white/15"
                            onclick="openHistoryPicker(this, '${r.studentUid}', '${r.status}')"
                            title="Click to change status">
                            <span class="w-1.5 h-1.5 rounded-full ${badge.dot}"></span>
                            ${badge.label}
                            <i data-feather="chevron-down" class="w-3 h-3 opacity-50"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
        }

        function filterRows() {
            const term = (rowSearch?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('#logsTableBody tr');
            rows.forEach(row => {
                if (!term) { row.style.display = ''; return; }
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        }
        if (rowSearch) rowSearch.addEventListener('keyup', filterRows);

        // ── History Status Picker ─────────────────────────────────────────────
        const histPicker    = document.getElementById('historyStatusPicker');
        let   histPickerUid = null;
        let   histPickerBtn = null;

        const BADGE_MAP = {
            Present:  { bg: 'bg-green-500/10 text-green-400 border border-green-500/20', dot: 'bg-green-500',  label: 'Present' },
            Late:     { bg: 'bg-amber-500/10 text-amber-400 border border-amber-500/30', dot: 'bg-amber-500',  label: 'Late'    },
            Absent:   { bg: 'bg-red-500/10   text-red-400   border border-red-500/30',   dot: 'bg-red-500',    label: 'Absent'  },
        };

        window.openHistoryPicker = function(btn, studentUid, currentStatus) {
            histPickerUid = studentUid;
            histPickerBtn = btn;

            // Mark active option with a tick
            histPicker.querySelectorAll('.hist-status-option').forEach(opt => {
                const active = opt.dataset.status === currentStatus;
                opt.querySelector('.hist-status-check').classList.toggle('hidden', !active);
                opt.classList.toggle('bg-white/5', active);
            });

            // Position below badge, flip up if near bottom of viewport
            const rect = btn.getBoundingClientRect();
            histPicker.classList.remove('hidden');
            histPicker.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - 180)) + 'px';
            histPicker.style.top  = (rect.bottom + 6) + 'px';
            const pRect = histPicker.getBoundingClientRect();
            if (pRect.bottom > window.innerHeight - 8) {
                histPicker.style.top = Math.max(8, rect.top - pRect.height - 6) + 'px';
            }
            feather.replace();
        };

        function closeHistoryPicker() {
            histPicker.classList.add('hidden');
            histPickerUid = null;
            histPickerBtn = null;
        }

        // Apply the chosen status
        histPicker.addEventListener('click', async (e) => {
            const opt = e.target.closest('.hist-status-option');
            if (!opt || !histPickerUid) return;
            const newStatus = opt.dataset.status;
            const uid       = histPickerUid;
            const btn       = histPickerBtn;
            closeHistoryPicker();

            // Optimistic update: swap badge classes + label + row data-status
            if (btn) {
                const b = BADGE_MAP[newStatus] || BADGE_MAP.Present;
                btn.className = `hist-status-badge cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full ${b.bg} text-[9px] font-black uppercase tracking-widest italic transition-all hover:ring-2 hover:ring-white/15`;
                btn.setAttribute('onclick', `openHistoryPicker(this, '${uid}', '${newStatus}')`);
                btn.innerHTML = `<span class="w-1.5 h-1.5 rounded-full ${b.dot}"></span>${b.label}<i data-feather="chevron-down" class="w-3 h-3 opacity-50"></i>`;
                feather.replace();
                const row = btn.closest('tr[data-student-uid]');
                if (row) row.dataset.status = newStatus;
                recalcStatsFromDom();
            }

            // Persist to API
            try {
                await api('/attendance.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        manual: true,
                        class_id: classSelect.value,
                        student_uid: uid,
                        status: newStatus,
                        date: dateInput.value
                    })
                });
                showToast(`Status updated to ${newStatus}`, 'success');
            } catch (err) {
                console.error('Status update failed:', err);
                showToast('Update failed: ' + err.message, 'error');
                refreshLogs(); // revert on error
                return;
            }

            // Keep the Grading Center's Attendance component in sync with the
            // status change (Present=10, Late=5, Absent=0). Non-blocking: a
            // grade-sync failure never rolls back the attendance update.
            await syncStatusToGrading(classSelect.value, dateInput.value, uid, newStatus);
        });

        // Mirror a status change into the Grading Center's Attendance component
        // for the matching date. Finds the component by name (M/D/YY) across all
        // three terms; auto-creates it if it doesn't exist yet.
        async function syncStatusToGrading(classId, date, studentUid, status) {
            const score = status === 'Present' ? 10 : status === 'Late' ? 5 : 0;
            const dateLabel = dateLabelFromInput(date);
            if (!dateLabel) return;

            let compId = null;
            let quarter = 1;
            try {
                quarter = Math.min(3, Math.max(1, parseInt(sessionStorage.getItem(`cs_grading_term_${classId}`) || '1') || 1));
            } catch (e) {}

            try {
                // 1. Search every term for an Attendance component named exactly
                //    M/D/YY (one attendance column per day).
                for (let q = 1; q <= 3 && !compId; q++) {
                    const data = await api(`/grades.php?class_id=${classId}&quarter=${q}`);
                    const match = (data.components || []).find(c =>
                        c.category === 'attendance' && c.name && c.name === dateLabel
                    );
                    if (match) compId = match.id;
                }

                // 2. None found — auto-create the day's Attendance component in the
                //    active term, named just M/D/YY.
                if (!compId) {
                    const compName = dateLabel;
                    const res = await api('/grades.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            action: 'save_component',
                            class_id: classId,
                            category: 'attendance',
                            name: compName,
                            hps: 10,
                            quarter
                        })
                    });
                    compId = (res.component || {}).id;
                    if (!compId) throw new Error('Component id missing');
                }

                // 3. Write the score for the changed student.
                await api('/grades.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'save_score',
                        component_id: compId,
                        student_uid: studentUid,
                        score
                    })
                });
            } catch (err) {
                console.error('Grade sync failed for status change:', err);
                const msg = err.message || 'Grade sync failed';
                if (msg.includes('weights') || msg.includes('Weights')) {
                    showToast('Set grading weights (total 100%) to sync attendance', 'error');
                } else {
                    showToast('Attendance saved, but grading sync failed', 'error');
                }
            }
        }

        // YYYY-MM-DD (from <input type="date">) -> M/D/YY, e.g. 2026-08-13 -> 8/13/26
        function dateLabelFromInput(dateStr) {
            if (!dateStr) return '';
            const parts = String(dateStr).split('-');
            if (parts.length < 3) return '';
            const m = parseInt(parts[1], 10);
            const d = parseInt(parts[2], 10);
            const y = String(parts[0]).slice(-2);
            if (isNaN(m) || isNaN(d)) return '';
            return `${m}/${d}/${y}`;
        }

        // Re-count stats from the current DOM (avoids a full API round-trip after each change)
        function recalcStatsFromDom() {
            let present = 0, late = 0, absent = 0;
            document.querySelectorAll('#logsTableBody tr[data-student-uid]').forEach(row => {
                const st = (row.dataset.status || '').toLowerCase();
                if (st === 'late') late++;
                else if (st === 'absent') absent++;
                else present++;
            });
            document.getElementById('statPresent').textContent  = present;
            document.getElementById('statLate').textContent     = late;
            document.getElementById('statAbsent').textContent   = absent;
        }

        // Close on outside-click, Escape, scroll, resize
        document.addEventListener('click', (e) => {
            if (!histPicker.classList.contains('hidden') &&
                !histPicker.contains(e.target) &&
                !e.target.closest('.hist-status-badge')) {
                closeHistoryPicker();
            }
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeHistoryPicker(); });
        window.addEventListener('scroll', closeHistoryPicker, true);
        window.addEventListener('resize', closeHistoryPicker);

        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
    </script>
</body>
</html>
