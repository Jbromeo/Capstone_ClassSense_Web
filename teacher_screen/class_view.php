<?php 
// class_view.php
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>ClassSense | Class Hub</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .class-hub-bg { background-color: #0f1115; }
        .glass-panel { background: rgba(24, 27, 33, 0.82); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255,255,255,0.08); }
        .custom-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        .stat-card-glow { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .stat-card-glow:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -15px rgba(234, 38, 40, 0.25); border-color: rgba(234, 38, 40, 0.3); }
        .btn-tab.active { background: rgba(255, 255, 255, 0.08); color: white; border-color: rgba(255, 255, 255, 0.15); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(234, 38, 40, 0.2); border-radius: 10px; }
        .spreadsheet-table td:focus-within { background: rgba(234, 38, 40, 0.05); }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
            20%, 40%, 60%, 80% { transform: translateX(2px); }
        }
        .animate-shake { animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both; border: 1px solid #ea2628 !important; }
        .animate-fade-in { animation: fadeIn 0.4s ease-out; }
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type="number"] { -moz-appearance: textfield; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="bg-dark-bg text-gray-300 font-sans selection:bg-primary-500/30 selection:text-white antialiased h-screen overflow-hidden flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <div id="toastContainer" class="fixed top-5 right-5 z-[100] flex flex-col gap-3"></div>

    <!-- Main Content Container -->
    <div class="flex-1 flex flex-col min-w-0 bg-dark-bg transition-all overflow-hidden relative">
        <!-- Glass Header -->
        <header class="h-20 border-b border-dark-border bg-dark-bg/50 backdrop-blur-xl flex items-center justify-between px-4 md:px-8 z-30">
            <div class="flex items-center gap-4 min-w-0 flex-1">
                <button id="mobileMenuBtn" class="md:hidden p-2 text-gray-400 hover:text-white shrink-0"><i data-feather="menu"></i></button>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.2em] italic mb-0.5 truncate">
                        <span class="text-primary-500 opacity-60 shrink-0">Academic Hub</span>
                        <i data-feather="chevron-right" class="w-3 h-3 text-gray-700 shrink-0"></i>
                        <span id="breadcrumbClassName" class="text-gray-400 font-bold truncate">SY-2024</span>
                    </div>
                    <h1 id="viewClassName" class="text-xl md:text-2xl font-black text-white italic uppercase tracking-tighter leading-none truncate">Class Hub</h1>
                </div>
                <div id="statusControls" class="hidden md:flex items-center gap-3 ml-6 border-l border-white/5 pl-6 shrink-0">
                    <div class="flex items-center gap-1.5">
                        <span id="statusDot" class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest italic">Status</span>
                    </div>
                    <button onclick="window.setClassStatus('In Progress')" data-status="In Progress" class="status-btn text-[11px] px-4 py-1.5 rounded-lg font-black uppercase tracking-widest italic transition-all duration-200 border-2 border-dashed border-amber-500/25 text-amber-400/60 hover:border-amber-500/60 hover:text-amber-300 bg-transparent">In Progress</button>
                    <button onclick="window.setClassStatus('Completed')" data-status="Completed" class="status-btn text-[11px] px-4 py-1.5 rounded-lg font-black uppercase tracking-widest italic transition-all duration-200 border-2 border-dashed border-green-500/25 text-green-400/60 hover:border-green-500/60 hover:text-green-300 bg-transparent">Completed</button>
                </div>
            </div>
            
            <div class="flex items-center gap-2 md:gap-6 shrink-0">
                <!-- Search & Notification -->
                <div class="flex items-center gap-4">
                    <div class="relative hidden md:block group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="search" class="h-4 w-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i>
                        </div>
                        <input id="globalSearchInput" type="text" class="bg-dark-bg border border-dark-border text-gray-300 text-xs rounded-full focus:ring-primary-500 focus:border-primary-500 block w-48 pl-10 p-2.5 transition-all focus:w-64 placeholder-gray-600 font-bold uppercase italic" placeholder="Search roster...">
                    </div>
                    <div class="relative">
                        <button id="notificationBell" class="p-2 text-gray-400 hover:text-white transition-colors relative">
                            <i data-feather="bell" class="w-5 h-5"></i>
                            <span id="notificationBadge" class="absolute top-1 right-1 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                        </button>
                        <div id="notificationDropdown" class="absolute right-0 top-full mt-3 w-[380px] bg-[#181b21]/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl hidden animate-fade-in-up origin-top-right z-50 max-h-[480px] flex flex-col">
                            <div class="flex items-center justify-between px-5 py-4 border-b border-white/5 flex-shrink-0">
                                <h3 class="text-sm font-black text-white italic uppercase tracking-tighter">Notifications</h3>
                                <span id="notifDropdownCount" class="text-[9px] font-bold text-gray-500 uppercase tracking-widest italic"></span>
                            </div>
                            <div id="notifDropdownBody" class="overflow-y-auto flex-1 custom-scrollbar">
                                <div class="py-10 text-center opacity-40">
                                    <p class="text-xs text-gray-500 italic uppercase tracking-widest">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Class Sub-Navigation -->
        <nav class="shrink-0 px-8 py-3 bg-dark-bg/80 backdrop-blur-xl border-b border-dark-border flex items-center gap-2 relative z-20">
            <button id="nav-students" onclick="window.switchTab('students')" class="class-control-nav flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest italic transition-all text-gray-400 hover:text-white hover:bg-white/5">
                <i data-feather="users" class="w-4 h-4 text-gray-500"></i> Students
            </button>
            <button id="nav-grading" onclick="window.switchTab('grading')" class="class-control-nav flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest italic transition-all text-gray-400 hover:text-white hover:bg-white/5">
                <i data-feather="monitor" class="w-4 h-4 text-gray-500"></i> Grading Center
            </button>
            <button id="nav-attendance" onclick="window.switchTab('attendance')" class="class-control-nav flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest italic transition-all text-gray-400 hover:text-white hover:bg-white/5">
                <i data-feather="calendar" class="w-4 h-4 text-gray-500"></i> Attendance History
            </button>
        </nav>

        <!-- Scrollable Page Content -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-8 relative">
            <div id="tabs-container" class="h-full">
                <?php include 'tabs/tab_students.php'; ?>
                <?php include 'tabs/tab_grading.php'; ?>
                <?php include 'tabs/tab_attendance.php'; ?>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php include 'tabs/modals_class.php'; ?>

    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';
        window.api = api;

        const API_BASE = '../api';
        const urlParams = new URLSearchParams(window.location.search);
        const classId = urlParams.get('id');
        let cachedStudents = [];
        let lastClassSig = '';
        let lastAlertsSig = '';
        let searchTerm = '';
        let pendingAlerts = [];
        let classPollInterval = null;

        window.showToast = (message, type = 'success') => {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            const isErr = type === 'error';
            const isInfo = type === 'info';
            toast.className = `flex items-center w-full max-w-xs p-4 text-gray-200 bg-dark-bg border border-white/5 rounded-xl shadow-2xl animate-fade-in ${isErr ? 'border-l-4 border-l-primary-500' : isInfo ? 'border-l-4 border-l-blue-500' : 'border-l-4 border-l-green-500'}`;
            toast.innerHTML = `<div class="flex-shrink-0"><i data-feather="${isErr ? 'alert-circle' : isInfo ? 'info' : 'check-circle'}" class="w-4 h-4 ${isErr ? 'text-primary-500' : isInfo ? 'text-blue-500' : 'text-green-500'}"></i></div><div class="ml-3 text-[10px] font-black uppercase italic tracking-widest">${message}</div>`;
            container.appendChild(toast);
            feather.replace();
            setTimeout(() => { toast.classList.add('opacity-0'); setTimeout(() => toast.remove(), 500); }, 3000);
        };

        window.switchTab = (tabName) => {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            const target = document.getElementById('tab-' + tabName);
            if (target) target.classList.remove('hidden');
            document.querySelectorAll('.class-control-nav').forEach(el => {
                el.classList.remove('active', 'bg-white/5', 'text-white', 'shadow-lg', 'shadow-primary-500/10');
                el.classList.add('text-gray-400');
                const icon = el.querySelector('i');
                if (icon) { icon.classList.remove('text-primary-500'); icon.classList.add('text-gray-500'); }
            });
            ['nav-' + tabName, 'nav-' + tabName + '-sidebar'].forEach(id => {
                const activeNav = document.getElementById(id);
                if (activeNav) {
                    activeNav.classList.add('active', 'bg-white/5', 'text-white', 'shadow-lg', 'shadow-primary-500/10');
                    activeNav.classList.remove('text-gray-400');
                    const icon = activeNav.querySelector('i');
                    if (icon) { icon.classList.add('text-primary-500'); icon.classList.remove('text-gray-500'); }
                }
            });
        };

        window.openModal = (id) => {
            const modal = document.getElementById(id);
            const content = document.getElementById(id + 'Content');
            if (modal && content) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    content.classList.remove('scale-95', 'opacity-0');
                    content.classList.add('scale-100', 'opacity-100');
                }, 10);
            }
        };

        window.closeModal = (id) => {
            const modal = document.getElementById(id);
            const content = document.getElementById(id + 'Content');
            if (modal && content) {
                content.classList.remove('scale-100', 'opacity-100');
                content.classList.add('scale-95', 'opacity-0');
                setTimeout(() => modal.classList.add('hidden'), 300);
            }
        };

        window.setClassStatus = async (status) => {
            try {
                await window.api(`/classes.php?id=${classId}`, {
                    method: 'PUT',
                    body: JSON.stringify({ status })
                });
                window.showToast(`Status set to ${status}`, 'success');
            } catch (err) {
                window.showToast('Status update failed', 'error');
            }
        };

        window.processBulkAdd = () => {
            window.showToast("Bulk enrollment module pending.", "info");
            window.closeModal('addStudentModal');
        };

        const searchInput = document.getElementById('globalSearchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', () => {
                searchTerm = searchInput.value.toLowerCase().trim();
                if (!searchTerm) { renderRoster(cachedStudents); return; }
                const filtered = cachedStudents.filter(s =>
                    (s.firstName && s.firstName.toLowerCase().includes(searchTerm)) ||
                    (s.lastName && s.lastName.toLowerCase().includes(searchTerm)) ||
                    (s.studentId && s.studentId.toLowerCase().includes(searchTerm)) ||
                    (s.email && s.email.toLowerCase().includes(searchTerm))
                );
                renderRoster(filtered);
            });
        }

        const bell = document.getElementById('notificationBell');
        const dropdown = document.getElementById('notificationDropdown');
        if (bell && dropdown) {
            bell.addEventListener('click', (e) => {
                e.stopPropagation();
                const isHidden = dropdown.classList.contains('hidden');
                if (isHidden) { renderNotificationDropdown(pendingAlerts); dropdown.classList.remove('hidden'); }
                else { dropdown.classList.add('hidden'); }
            });
            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target) && !bell.contains(e.target)) dropdown.classList.add('hidden');
            });
        }

        function renderNotificationDropdown(alerts) {
            const body = document.getElementById('notifDropdownBody');
            const countLabel = document.getElementById('notifDropdownCount');
            if (!body) return;
            const total = alerts.reduce((sum, a) => sum + a.missingCount, 0);
            if (countLabel) countLabel.textContent = total > 0 ? `${total} pending` : '';
            if (alerts.length === 0) {
                body.innerHTML = `<div class="py-10 text-center"><div class="w-12 h-12 mx-auto mb-3 rounded-full bg-green-500/10 flex items-center justify-center"><i data-feather="check-circle" class="w-6 h-6 text-green-400"></i></div><p class="text-xs font-black text-green-400 uppercase tracking-widest italic">All Clear</p><p class="text-[9px] text-gray-600 mt-1 italic">No pending items.</p></div>`;
                feather.replace(); return;
            }
            const topAlerts = alerts.sort((a, b) => b.missingCount - a.missingCount).slice(0, 20);
            body.innerHTML = topAlerts.map(a => {
                const severity = a.pct >= 95 ? 'green' : (a.pct >= 75 ? 'amber' : 'red');
                return `<div class="flex items-start gap-3 px-5 py-3.5 hover:bg-white/5 transition-colors cursor-pointer border-b border-white/5 last:border-0" onclick="window.location.href='class_view.php?id=${a.classId}'"><div class="w-8 h-8 mt-0.5 rounded-full bg-${severity}-500/10 flex items-center justify-center flex-shrink-0"><i data-feather="${severity === 'green' ? 'check-circle' : (severity === 'amber' ? 'alert-octagon' : 'alert-circle')}" class="w-4 h-4 text-${severity}-400"></i></div><div class="flex-1 min-w-0"><p class="text-sm font-bold text-white truncate">${a.className}</p><p class="text-xs text-gray-400 truncate">${a.itemName} — <span class="text-${severity}-400 font-bold">${a.missingCount} ${a.missingCount === 1 ? 'student needs' : 'students need'} grading</span></p><div class="flex items-center gap-3 mt-1.5"><span class="text-[9px] text-gray-600 font-bold uppercase tracking-widest italic">${a.gradedCount}/${a.enrolledCount} graded</span><div class="flex-1 h-1 bg-dark-bg rounded-full max-w-[80px] overflow-hidden"><div class="h-full bg-${severity}-500 rounded-full" style="width: ${a.pct}%"></div></div></div></div></div>`;
            }).join('');
            feather.replace();
        }

        async function loadPendingAlerts() {
            try {
                const stats = await window.api('/stats.php');
                const sig = JSON.stringify(stats);
                if (sig === lastAlertsSig) return;
                lastAlertsSig = sig;
                pendingAlerts = stats.grade_alerts || [];
                const totalPending = stats.pending_grading || 0;
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    if (totalPending > 0) {
                        badge.textContent = totalPending > 9 ? '9+' : totalPending;
                        badge.className = 'absolute -top-1 -right-1 flex items-center justify-center w-4 h-4 text-[8px] font-black text-white bg-primary-500 rounded-full ring-2 ring-dark-bg';
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            } catch (e) { console.warn("Alert load failed:", e); }
        }

        async function fetchStudentDetails(uids) {
            if (!uids || uids.length === 0) return [];
            try {
                const students = await window.api('/fetch.php', {
                    method: 'POST',
                    body: JSON.stringify({ collection: 'students', uids })
                });
                return students.filter(s => s.exists !== false);
            } catch (err) {
                console.error("Critical Retrieval Fault:", err);
                return [];
            }
        }

        function renderRoster(students) {
            const tbody = document.getElementById('studentTableBody');
            const countSpan = document.getElementById('rosterCount');
            const countTop = document.getElementById('rosterCountTop');
            if (!students || students.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="p-32 text-center opacity-40"><div class="flex flex-col items-center gap-6"><i data-feather="user-x" class="w-12 h-12 text-gray-500"></i><p class="text-[10px] font-black uppercase tracking-widest italic tracking-tighter text-white">Hub Connection Empty</p></div></td></tr>`;
                if (countSpan) countSpan.innerText = "0 ENTITIES ENROLLED";
                if (countTop) countTop.innerText = "0 ENTITIES ENROLLED";
                feather.replace(); return;
            }
            function rosterAvatar(s) {
                const initials = ((s.firstName?.[0] || '') + (s.lastName?.[0] || '')).toUpperCase() || 'ST';
                if (s.profilePicture && s.profilePicture !== '' && !s.profilePicture.includes('ui-avatars')) {
                    return `<img src="${s.profilePicture}" alt="" class="w-10 h-10 rounded-2xl object-cover border border-primary-500/10 shadow-lg shadow-primary-500/5 group-hover:scale-110 transition-transform shrink-0">`;
                }
                if (s.profile_picture && s.profile_picture !== '' && !s.profile_picture.includes('ui-avatars')) {
                    return `<img src="${s.profile_picture}" alt="" class="w-10 h-10 rounded-2xl object-cover border border-primary-500/10 shadow-lg shadow-primary-500/5 group-hover:scale-110 transition-transform shrink-0">`;
                }
                return `<img src="https://ui-avatars.com/api/?name=${initials}&background=ea2628&color=fff&bold=true" alt="" class="w-10 h-10 rounded-2xl object-cover border border-primary-500/10 shadow-lg shadow-primary-500/5 group-hover:scale-110 transition-transform shrink-0">`;
            }

            tbody.innerHTML = students.map((s, index) => `
                <tr class="border-b border-white/5 hover:bg-white/5 transition-colors group">
                    <td class="p-5 text-gray-500 font-mono text-[10px] text-center">${index + 1}</td>
                    <td class="p-5">
                        <div class="flex items-center gap-4">
                            ${rosterAvatar(s)}
                            <div>
                                <p class="text-sm text-white font-black uppercase tracking-tight italic leading-none">${s.firstName || ''} ${s.lastName || 'IDENTITY_MISSING'}</p>
                                <p class="text-[9px] text-gray-500 font-bold uppercase italic tracking-widest mt-1.5 opacity-60">${s.email || 'ENCRYPTED'}</p>
                            </div>
                        </div>
                    </td>
                    <td class="p-5 text-gray-400 font-mono text-xs font-black uppercase tracking-widest italic">${s.studentId || 'PENDING'}</td>
                    <td class="p-5">
                        <div class="flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="w-9 h-9 flex items-center justify-center text-gray-500 hover:text-primary-500 hover:bg-primary-500/10 rounded-xl transition-all"><i data-feather="edit-2" class="w-3.5 h-3.5"></i></button>
                            <button class="w-9 h-9 flex items-center justify-center text-gray-500 hover:text-red-500 hover:bg-red-500/10 rounded-xl transition-all"><i data-feather="trash-2" class="w-3.5 h-3.5"></i></button>
                        </div>
                    </td>
                </tr>`).join('');
            if (countSpan) countSpan.innerText = `${students.length} STUDENT${students.length === 1 ? '' : 'S'} ENROLLED`;
            if (countTop) countTop.innerText = `${students.length} STUDENT${students.length === 1 ? '' : 'S'} ENROLLED`;
            feather.replace();
        }

        function applyStatusUI(classData) {
            document.getElementById('viewClassName').innerText = classData.class_name;
            document.getElementById('breadcrumbClassName').innerText = classData.subject || classData.class_name;
            document.querySelectorAll('.status-btn').forEach(btn => {
                const status = btn.dataset.status;
                const isActive = (status === classData.status) || (classData.status !== 'Completed' && classData.status !== 'In Progress' && status === 'In Progress');
                if (isActive) {
                    btn.classList.remove('bg-transparent', 'border-dashed', 'border-blue-500/25', 'border-amber-500/25', 'border-green-500/25', 'text-blue-400/60', 'text-amber-400/60', 'text-green-400/60');
                    btn.classList.add('text-white', 'shadow-lg');
                    if (status === 'In Progress') btn.classList.add('bg-amber-500', 'border-amber-500', 'shadow-amber-500/30');
                    else if (status === 'Completed') btn.classList.add('bg-green-600', 'border-green-600', 'shadow-green-600/30');
                } else {
                    btn.classList.add('bg-transparent', 'border-dashed');
                    btn.classList.remove('text-white', 'shadow-lg', 'bg-blue-600', 'bg-amber-500', 'bg-green-600', 'border-blue-600', 'border-amber-500', 'border-green-600', 'shadow-blue-600/30', 'shadow-amber-500/30', 'shadow-green-600/30', 'text-blue-400/60', 'text-amber-400/60', 'text-green-400/60');
                    if (status === 'In Progress') btn.classList.add('border-amber-500/25', 'text-amber-400/60');
                    else if (status === 'Completed') btn.classList.add('border-green-500/25', 'text-green-400/60');
                }
            });
            const dot = document.getElementById('statusDot');
            if (dot) {
                dot.className = 'w-2 h-2 rounded-full';
                if (classData.status === 'In Progress' || classData.status === 'Active' || !classData.status) dot.classList.add('bg-amber-500');
                else if (classData.status === 'Completed') dot.classList.add('bg-green-600');
                else dot.classList.add('bg-gray-500');
            }
            const codeSpan = document.getElementById('displayClassCode');
            if (codeSpan) codeSpan.innerText = classData.class_code || classData.id;
        }

        async function loadClassData() {
            if (!classId) return;
            try {
                const classData = await window.api(`/classes.php?id=${classId}`);
                const uids = classData.students || [];
                const fullStudentData = await fetchStudentDetails(uids);
                const sig = JSON.stringify([classData, fullStudentData]);
                if (sig === lastClassSig) return;
                lastClassSig = sig;
                cachedStudents = fullStudentData;
                applyStatusUI(classData);
                initGradingSystem(classData, fullStudentData);
                if (!searchTerm) renderRoster(fullStudentData);
                else {
                    const filtered = fullStudentData.filter(s =>
                        (s.firstName && s.firstName.toLowerCase().includes(searchTerm)) ||
                        (s.lastName && s.lastName.toLowerCase().includes(searchTerm)) ||
                        (s.studentId && s.studentId.toLowerCase().includes(searchTerm)) ||
                        (s.email && s.email.toLowerCase().includes(searchTerm))
                    );
                    renderRoster(filtered);
                }
            } catch (e) {
                console.error('Load class data error:', e);
                window.showToast('Hub Inaccessible or Offline.', 'error');
            }
        }

        function initGradingSystem(classData, students) {
            if (window.gradingSystem) {
                window.gradingSystem.init(classData.id, students);
            }
        }

        initPage(() => {
            setTimeout(() => loadClassData(), 500);
            classPollInterval = setInterval(loadClassData, 5000);
            loadPendingAlerts();
            setInterval(loadPendingAlerts, 30000);
        });

        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();
            window.switchTab('students');
        });
    </script>
</body>
</html>