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
            </div>
        </header>

        <!-- Scrollable Content -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8 relative">
            
            <div class="max-w-6xl mx-auto">
                <!-- Records Control Bar -->
                <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="flex-1">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic mb-2 block ml-1">Academic Unit / Hub</label>
                        <select id="classFilter" class="w-full md:w-80 bg-dark-surface border border-dark-border text-white text-sm rounded-xl p-4 focus:ring-primary-500 focus:border-primary-500 uppercase font-black italic tracking-tighter">
                            <option value="">Loading Hubs...</option>
                        </select>
                    </div>

                    <div class="flex gap-4">
                        <div class="glass-panel px-6 py-3 rounded-xl flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-[10px] uppercase text-gray-500 font-bold tracking-wider">Scanned Today</p>
                                <p id="logCount" class="text-2xl font-bold text-white">0</p>
                            </div>
                        </div>
                    </div>
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

        let loadInterval = null;
        const dateInput = document.getElementById('dateFilter');
        const classSelect = document.getElementById('classFilter');

        dateInput.value = new Date().toISOString().split('T')[0];

        initPage(() => {
            setTimeout(() => loadData(), 500);
            if (loadInterval) clearInterval(loadInterval);
            loadInterval = setInterval(loadData, 10000);
        });

        async function loadData() {
            try {
                const user = JSON.parse(sessionStorage.getItem('cs_user') || 'null');
                const uid = user?.uid;
                if (!uid) return;

                const classes = await api('/classes.php');

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

        async function refreshLogs() {
            const classId = classSelect.value;
            const date = dateInput.value;
            const tbody = document.getElementById('logsTableBody');

            if (!classId) return;

            tbody.innerHTML = `<tr><td colspan="5" class="p-20 text-center animate-pulse text-gray-500 italic">Syncing with Registry...</td></tr>`;

            try {
                const records = await api('/attendance.php?class_id=' + classId);
                const filtered = records.filter(r => r.date === date);

                document.getElementById('logCount').innerText = filtered.length;

                if (filtered.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="p-20 text-center text-gray-500 italic">No records found for this date.</td></tr>`;
                    return;
                }

                const uids = [...new Set(filtered.map(r => r.student_uid))];
                const students = await api('/fetch.php', { method: 'POST', body: JSON.stringify({ collection: 'students', uids }) });
                const studentMap = {};
                students.forEach(s => { studentMap[s.uid] = s; });

                const rows = [];
                for (const rec of filtered) {
                    const s = studentMap[rec.student_uid];
                    if (!s) continue;

                    let avatarUrl = s.profilePhoto;
                    if (!avatarUrl) {
                        const initials = `${s.firstName?.[0] || 'S'}${s.lastName?.[0] || 'T'}`.toUpperCase();
                        avatarUrl = `https://ui-avatars.com/api/?name=${initials}&background=ea2628&color=fff&bold=true`;
                    }

                    rows.push({
                        docId: rec.id,
                        name: s.full_name || `${s.firstName} ${s.lastName}`,
                        id: s.studentId || 'N/A',
                        avatar: avatarUrl,
                        time: rec.timestamp ? new Date(rec.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}) : '--:--',
                        status: rec.status || 'Verified'
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
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-green-500/10 text-green-400 border border-green-500/20 italic">
                            <div class="w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_5px_#22c55e]"></div> ${r.status}
                        </span>
                    </td>
                    <td class="p-5 text-right pr-8">
                        <button onclick="deleteRecord('${r.docId}')" class="p-2 text-gray-600 hover:text-red-500 transition-colors">
                            <i data-feather="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>`).join('');

                feather.replace();
            } catch (error) {
                console.error("Log Sync Error:", error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="p-20 text-center">
                            <div class="flex flex-col items-center gap-2 opacity-50">
                                <i data-feather="alert-octagon" class="w-8 h-8 text-primary-500 animate-pulse"></i>
                                <span class="text-xs font-black uppercase tracking-widest italic text-primary-400">Registry Sync Denied</span>
                                <span class="text-[9px] font-mono">${error.message}</span>
                            </div>
                        </td>
                    </tr>`;
                feather.replace();
            }
        }

        window.deleteRecord = (id) => {
            alert('Delete not yet implemented in the SQL API. Record ID: ' + id);
        }

        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
    </script>
</body>
</html>
