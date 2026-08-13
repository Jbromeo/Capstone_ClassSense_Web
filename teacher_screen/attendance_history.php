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
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic mb-2 block ml-1">Academic Unit / Hub</label>
                            <select id="classFilter" class="w-full md:w-80 bg-dark-surface border border-dark-border text-white text-sm rounded-xl p-4 focus:ring-primary-500 focus:border-primary-500 uppercase font-black italic tracking-tighter">
                                <option value="">Loading Hubs...</option>
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

                <!-- Stats Strip -->
                <div id="statsStrip" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="glass-panel px-4 py-3 rounded-xl flex items-center gap-3"><i data-feather="check-circle" class="w-5 h-5 text-green-400"></i><div><p class="text-xs text-gray-500 font-black uppercase tracking-widest">Present</p><p id="statPresent" class="text-xl font-bold text-white">0</p></div></div>
                    <div class="glass-panel px-4 py-3 rounded-xl flex items-center gap-3"><i data-feather="clock" class="w-5 h-5 text-yellow-400"></i><div><p class="text-xs text-gray-500 font-black uppercase tracking-widest">Late</p><p id="statLate" class="text-xl font-bold text-white">0</p></div></div>
                    <div class="glass-panel px-4 py-3 rounded-xl flex items-center gap-3"><i data-feather="x-circle" class="w-5 h-5 text-red-400"></i><div><p class="text-xs text-gray-500 font-black uppercase tracking-widest">Absent</p><p id="statAbsent" class="text-xl font-bold text-white">0</p></div></div>
                    <div class="glass-panel px-4 py-3 rounded-xl flex items-center gap-3"><i data-feather="minus-circle" class="w-5 h-5 text-gray-600"></i><div><p class="text-xs text-gray-500 font-black uppercase tracking-widest">No Record</p><p id="statNoRecord" class="text-xl font-bold text-white">0</p></div></div>
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
                                    <th class="p-5">Hub Status</th>
                                    <th class="p-5 text-right pr-8">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody" class="text-sm">
                                <tr>
                                    <td colspan="5" class="p-20 text-center">
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

        dateInput.value = new Date().toISOString().split('T')[0];

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

                classSelect.innerHTML = `<option value="">Select a Hub</option>` +
                    classes.map(c => `<option value="${c.id}">${c.class_name}</option>`).join('');

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

            if (!classId) {
                tbody.innerHTML = `<tr><td colspan="5" class="p-20 text-center"><div class="flex flex-col items-center opacity-40 italic"><i data-feather="database" class="w-12 h-12 mb-4 text-gray-600"></i><p class="text-sm font-bold uppercase tracking-widest">Select a class to load registry data</p></div></td></tr>`;
                resetStats();
                feather.replace();
                return;
            }

            tbody.innerHTML = `<tr><td colspan="5" class="p-20 text-center animate-pulse text-gray-500 italic">Syncing with Registry...</td></tr>`;
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
                updateStats(records, rosterUids, studentMap);
                feather.replace();
            } catch (error) {
                console.error("Log Sync Error:", error);
                tbody.innerHTML = `<tr><td colspan="5" class="p-20 text-center"><div class="flex flex-col items-center gap-2 opacity-50"><i data-feather="alert-octagon" class="w-8 h-8 text-primary-500 animate-pulse"></i><span class="text-xs font-black uppercase tracking-widest italic text-primary-400">Registry Sync Denied</span><span class="text-[9px] font-mono">${error.message}</span></div></td></tr>`;
                feather.replace();
            }
        }

        function resetStats() {
            ['statPresent','statLate','statAbsent','statNoRecord'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '0';
            });
        }

        function updateStats(records, rosterUids, studentMap) {
            let present = 0, late = 0, absent = 0;
            const recordedUids = [];
            records.forEach(r => {
                recordedUids.push(r.student_uid);
                if (r.status === 'Late') late++;
                else if (r.status === 'Absent') absent++;
                else present++;
            });
            const recorded = new Set(recordedUids);
            const noRecord = rosterUids.filter(uid => !recorded.has(uid) && studentMap[uid]).length;

            document.getElementById('statPresent').textContent = present;
            document.getElementById('statLate').textContent = late;
            document.getElementById('statAbsent').textContent = absent;
            document.getElementById('statNoRecord').textContent = noRecord;
        }

        const STATUS_COLORS = {
            Present: 'bg-green-500/10 text-green-400 border-green-500/20',
            Late: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
            Absent: 'bg-red-500/10 text-red-400 border-red-500/20'
        };

        function buildRows(records, studentMap) {
            const tbody = document.getElementById('logsTableBody');
            if (!records.length) {
                tbody.innerHTML = `<tr><td colspan="5" class="p-20 text-center text-gray-500 italic">No records found for this date.</td></tr>`;
                return;
            }

            const rows = [];
            for (const rec of records) {
                const s = studentMap[rec.student_uid];
                if (!s) continue;

                let avatarUrl = s.profilePhoto;
                if (!avatarUrl) {
                    const initials = `${s.firstName?.[0] || 'S'}${s.lastName?.[0] || 'T'}`.toUpperCase();
                    avatarUrl = `https://ui-avatars.com/api/?name=${initials}&background=ea2628&color=fff&bold=true`;
                }
                const status = rec.status || 'Present';
                const time = rec.timestamp ? new Date(rec.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}) : '--:--';

                rows.push({
                    docId: rec.id,
                    studentUid: rec.student_uid,
                    name: s.full_name || `${s.firstName} ${s.lastName}`,
                    id: s.studentId || 'N/A',
                    avatar: avatarUrl,
                    time: time,
                    status: status
                });
            }

            rows.sort((a, b) => b.time.localeCompare(a.time));

            tbody.innerHTML = rows.map((r, idx) => `
                <tr class="border-b border-dark-border hover:bg-white/5 transition-colors animate-pop-in" style="animation-delay: ${idx * 50}ms">
                    <td class="p-5 pl-8">
                        <div class="flex items-center gap-4">
                            <img src="${r.avatar}" class="w-10 h-10 rounded-full object-cover ring-2 ring-dark-bg border border-white/10 shadow-lg">
                            <span class="font-black text-white uppercase italic tracking-tighter">${r.name}</span>
                        </div>
                    </td>
                    <td class="p-5 text-gray-400 font-mono text-xs uppercase tracking-tighter">${r.id}</td>
                    <td class="p-5 text-gray-400 text-xs font-black uppercase tracking-widest italic opacity-60">${r.time}</td>
                    <td class="p-5">
                        <div class="flex gap-2">
                            <span class="${STATUS_COLORS[r.status] || STATUS_COLORS.Present} inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest italic">
                                <div class="w-1.5 h-1.5 rounded-full ${r.status === 'Present' ? 'bg-green-500' : r.status === 'Late' ? 'bg-yellow-500' : 'bg-red-500'} shadow-[0_0_5px_rgba(255,255,255,0.3)]"></div> ${r.status}
                            </span>
                            <select onchange="changeStatus('${r.studentUid}', this.value)" class="status-select appearance-none w-28 bg-dark-bg/60 border border-dark-border text-white text-[9px] font-black uppercase rounded-lg px-2 py-1 focus:ring-primary-500 focus:border-primary-500">
                                <option value="Present" ${r.status === 'Present' ? 'selected' : ''}>Present</option>
                                <option value="Late" ${r.status === 'Late' ? 'selected' : ''}>Late</option>
                                <option value="Absent" ${r.status === 'Absent' ? 'selected' : ''}>Absent</option>
                            </select>
                        </div>
                    </td>
                    <td class="p-5 text-right pr-8">
                        <button onclick="deleteRecord('${r.studentUid}')" class="p-2 text-gray-600 hover:text-red-500 transition-colors">
                            <i data-feather="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>`).join('');
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

        // --- Per-row status switcher ---
        window.changeStatus = async function(studentUid, newStatus) {
            const classId = classSelect.value;
            const date = dateInput.value;
            try {
                await api('/attendance.php', {
                    method: 'POST',
                    body: JSON.stringify({ manual: true, class_id: classId, student_uid: studentUid, status: newStatus, date: date })
                });
                showToast(`Updated to ${newStatus}`, 'success');
                refreshLogs();
            } catch (error) {
                console.error("Status Change Error:", error);
                showToast('Update failed: ' + error.message, 'error');
            }
        };

        // --- Delete (wired to real API) ---
        window.deleteRecord = async function(studentUid) {
            if (!confirm('Remove this student\'s attendance for the selected date?')) return;
            try {
                await api('/attendance.php?class_id=' + classSelect.value + '&date=' + dateInput.value + '&student_uid=' + studentUid, { method: 'DELETE' });
                showToast('Record removed', 'success');
                refreshLogs();
            } catch (error) {
                console.error("Delete Error:", error);
                showToast('Delete failed: ' + error.message, 'error');
            }
        };

        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
    </script>
</body>
</html>
