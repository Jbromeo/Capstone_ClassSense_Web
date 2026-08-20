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
                <!-- Notification -->
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors group">
                            <i data-feather="bell" class="w-5 h-5"></i>
                            <span class="notif-dot hidden absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                        </button>
                        <?php include '../includes/notification_popover.php'; ?>
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
        </nav>

        <!-- Scrollable Page Content -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-8 relative">
            <div id="tabs-container" class="h-full">
                <?php include 'classes/class_roster.php'; ?>
                <?php include 'classes/grading_center.php'; ?>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php include 'classes/modals_class.php'; ?>
    <?php include 'classes/confirm_modal_class.php'; ?>

    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';
        window.api = api;

        const API_BASE = '../api';
        const urlParams = new URLSearchParams(window.location.search);
        const classId = urlParams.get('id');
        let cachedStudents = [];
        let lastClassSig = '';
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

            // Grading center is always a live view of the SQL database: opening
            // the tab re-fetches the current term's components/grades/weights.
            if (tabName === 'grading' && window.gradingSystem) {
                window.gradingSystem.refresh();
            }
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

        const ROSTER_BATCH = 20;
        let rosterData = [];
        let rosterVisibleCount = 0;

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

        function rosterRowHTML(s, index) {
            return `
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
                        <div class="flex justify-center">
                            <button onclick="window.removeStudentFromClass('${s.uid}')" title="Remove from class" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest italic text-red-400 bg-red-500/10 border border-red-500/20 hover:bg-red-500/20 hover:text-red-300 transition-all"><i data-feather="user-x" class="w-3.5 h-3.5"></i> Remove</button>
                        </div>
                    </td>
                </tr>`;
        }

        function renderRoster(students) {
            const tbody = document.getElementById('studentTableBody');
            const countTop = document.getElementById('rosterCountTop');
            rosterData = students || [];
            rosterVisibleCount = Math.min(ROSTER_BATCH, rosterData.length);
            if (rosterData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="p-32 text-center opacity-40"><div class="flex flex-col items-center gap-6"><i data-feather="user-x" class="w-12 h-12 text-gray-500"></i><p class="text-[10px] font-black uppercase tracking-widest italic tracking-tighter text-white">Hub Connection Empty</p></div></td></tr>`;
                if (countTop) countTop.innerText = "0 STUDENTS ENROLLED";
                feather.replace(); return;
            }
            tbody.innerHTML = rosterData.slice(0, rosterVisibleCount).map((s, i) => rosterRowHTML(s, i)).join('');
            if (countTop) countTop.innerText = `${rosterData.length} STUDENT${rosterData.length === 1 ? '' : 'S'} ENROLLED`;
            feather.replace();
        }

        function loadMoreRoster() {
            if (rosterVisibleCount >= rosterData.length) return;
            const tbody = document.getElementById('studentTableBody');
            const next = rosterData.slice(rosterVisibleCount, rosterVisibleCount + ROSTER_BATCH);
            tbody.insertAdjacentHTML('beforeend', next.map((s, i) => rosterRowHTML(s, rosterVisibleCount + i)).join(''));
            rosterVisibleCount += next.length;
            feather.replace();
        }

        window.removeStudentFromClass = async (uid) => {
            const student = rosterData.find(s => s.uid === uid);
            const name = student ? `${student.firstName || ''} ${student.lastName || ''}`.trim() || 'this student' : 'this student';
            const ok = await window.csConfirm({
                title: 'Remove Student',
                message: `Remove ${name} from this class?`,
                okText: 'Remove',
                cancelText: 'Cancel',
                danger: true
            });
            if (!ok) return;
            try {
                await window.api('/enroll.php', {
                    method: 'DELETE',
                    body: JSON.stringify({ class_id: classId, student_uid: uid })
                });
                rosterData = rosterData.filter(s => s.uid !== uid);
                cachedStudents = rosterData;
                if (rosterVisibleCount > rosterData.length) rosterVisibleCount = rosterData.length;
                renderRoster(rosterData);
                window.showToast('Student removed from class', 'success');
            } catch (err) {
                console.error('Remove student failed:', err);
                window.showToast('Failed to remove student', 'error');
            }
        };

        const rosterScroll = document.getElementById('rosterScrollContainer');
        if (rosterScroll) {
            rosterScroll.addEventListener('scroll', () => {
                if (rosterScroll.scrollTop + rosterScroll.clientHeight >= rosterScroll.scrollHeight - 120) {
                    loadMoreRoster();
                }
            });
        }

        function applyStatusUI(classData) {
            document.getElementById('viewClassName').innerText = classData.class_name;
            document.getElementById('breadcrumbClassName').innerText = classData.class_name;
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
                renderRoster(fullStudentData);
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
            // Realtime grading sync: while the Grading tab is visible, re-pull
            // the current term straight from SQL every 20s so attendance/scores/
            // weights changed elsewhere show up without a reload.
            setInterval(() => {
                const tab = document.getElementById('tab-grading');
                if (tab && !tab.classList.contains('hidden') && window.gradingSystem) {
                    window.gradingSystem.refresh();
                }
            }, 20000);
        });

        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();
            const tabParam = urlParams.get('tab');
            window.switchTab(tabParam === 'grading' || tabParam === 'students' ? tabParam : 'students');
        });
    </script>
</body>
</html>