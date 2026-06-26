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
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="bg-dark-bg text-gray-300 font-sans selection:bg-primary-500/30 selection:text-white antialiased min-h-screen overflow-hidden flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content Container -->
    <div class="flex-1 flex flex-col min-w-0 bg-dark-bg transition-all overflow-hidden relative">
        <!-- Glass Header -->
        <header class="h-20 border-b border-dark-border bg-dark-bg/50 backdrop-blur-xl flex items-center justify-between px-8 z-30">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 text-gray-400 hover:text-white"><i data-feather="menu"></i></button>
                <div>
                    <div class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.2em] italic mb-0.5">
                        <span class="text-primary-500 opacity-60">Academic Hub</span>
                        <i data-feather="chevron-right" class="w-3 h-3 text-gray-700"></i>
                        <span id="breadcrumbClassName" class="text-gray-400 font-bold">SY-2024</span>
                    </div>
                    <h1 id="viewClassName" class="text-2xl font-black text-white italic uppercase tracking-tighter leading-none">Class Hub</h1>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <!-- Search & Notification -->
                <div class="flex items-center gap-4">
                    <div class="relative hidden md:block group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="search" class="h-4 w-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i>
                        </div>
                        <input type="text" class="bg-dark-bg border border-dark-border text-gray-300 text-xs rounded-full focus:ring-primary-500 focus:border-primary-500 block w-48 pl-10 p-2.5 transition-all focus:w-64 placeholder-gray-600 font-bold uppercase italic" placeholder="Search roster...">
                    </div>
                    <button class="relative p-2 text-gray-400 hover:text-white transition-colors">
                        <i data-feather="bell" class="w-5 h-5"></i>
                        <span class="absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Class Sub-Navigation -->
        <nav class="shrink-0 px-8 py-3 bg-dark-bg/80 backdrop-blur-xl border-b border-dark-border flex items-center gap-2 relative z-20">
            <button id="nav-students" onclick="window.switchTab('students')" class="class-control-nav active flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest italic transition-all bg-white/5 text-white shadow-lg shadow-primary-500/10">
                <i data-feather="users" class="w-4 h-4 text-primary-500"></i> Students
            </button>
            <button id="nav-grading" onclick="window.switchTab('grading')" class="class-control-nav flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest italic transition-all text-gray-400 hover:text-white hover:bg-white/5">
                <i data-feather="monitor" class="w-4 h-4 text-gray-500"></i> Grading Engine
            </button>
            <button id="nav-attendance" onclick="window.switchTab('attendance')" class="class-control-nav flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest italic transition-all text-gray-400 hover:text-white hover:bg-white/5">
                <i data-feather="calendar" class="w-4 h-4 text-gray-500"></i> Attendance
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

    <!-- Unified Logic Module -->
    <script type="module">
        import { GradingSystem } from '../assets/js/grading_controller.js';
        import { db, auth } from '../assets/js/firebase-init.js';
        import { doc, getDoc, onSnapshot } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

        // Global State & UI Helpers
        const urlParams = new URLSearchParams(window.location.search);
        const classId = urlParams.get('id');
        window.gradingSystem = new GradingSystem(classId);

        // Toast Helper
        window.showToast = (message, type = 'success') => {
            const container = document.getElementById('toastContainer');
            if(!container) return;
            const toast = document.createElement('div');
            const isErr = type === 'error';
            const isInfo = type === 'info';
            toast.className = `flex items-center w-full max-w-xs p-4 text-gray-200 bg-dark-bg border border-white/5 rounded-xl shadow-2xl animate-fade-in ${isErr ? 'border-l-4 border-l-primary-500' : isInfo ? 'border-l-4 border-l-blue-500' : 'border-l-4 border-l-green-500'}`;
            toast.innerHTML = `<div class="flex-shrink-0"><i data-feather="${isErr ? 'alert-circle' : isInfo ? 'info' : 'check-circle'}" class="w-4 h-4 ${isErr ? 'text-primary-500' : isInfo ? 'text-blue-500' : 'text-green-500'}"></i></div><div class="ml-3 text-[10px] font-black uppercase italic tracking-widest">${message}</div>`;
            container.appendChild(toast);
            feather.replace();
            setTimeout(() => { toast.classList.add('opacity-0'); setTimeout(() => toast.remove(), 500); }, 3000);
        };

        // Tab Switching Logic
        window.switchTab = (tabName) => {
            console.log("Switching to tab:", tabName);
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            const target = document.getElementById('tab-' + tabName);
            if(target) target.classList.remove('hidden');
            
            document.querySelectorAll('.class-control-nav').forEach(el => {
                el.classList.remove('active', 'bg-white/5', 'text-white', 'shadow-lg', 'shadow-primary-500/10');
                el.classList.add('text-gray-400');
                const icon = el.querySelector('i');
                if(icon) { icon.classList.remove('text-primary-500'); icon.classList.add('text-gray-500'); }
            });

            const activeNav = document.getElementById('nav-' + tabName);
            if(activeNav) {
                activeNav.classList.add('active', 'bg-white/5', 'text-white', 'shadow-lg', 'shadow-primary-500/10');
                activeNav.classList.remove('text-gray-400');
                const icon = activeNav.querySelector('i');
                if(icon) { icon.classList.add('text-primary-500'); icon.classList.remove('text-gray-500'); }
            }
        };

        // Modal and UI Interactions
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

        window.setQuarter = (quarter) => {
            document.querySelectorAll('.quarter-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20');
                btn.classList.add('text-gray-500');
            });
            const activeBtn = document.getElementById('q-' + quarter);
            if (activeBtn) {
                activeBtn.classList.add('active', 'bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20');
                activeBtn.classList.remove('text-gray-500');
            }
            window.gradingSystem.quarter = quarter;
            window.gradingSystem.setupRealtimeListener();
        };

        window.exportToExcel = () => {
            window.gradingSystem.exportToCSV();
        };

        window.saveNewComponent = async () => {
            const category = document.getElementById('addCompCategory').value;
            const name = document.getElementById('addCompName').value.trim();
            const hps = parseInt(document.getElementById('addCompHps').value);
            if (!name || !hps || hps <= 0) {
                window.showToast("Invalid Component Data", "error");
                return;
            }
            await window.gradingSystem.addComponent(category, name, hps);
            window.closeModal('addComponentModal');
            window.showToast("Component Deployed", "success");
            document.getElementById('addCompName').value = '';
        };

        window.saveWeights = async () => {
            const w = parseInt(document.getElementById('weight-written').value) || 0;
            const p = parseInt(document.getElementById('weight-performance').value) || 0;
            const e = parseInt(document.getElementById('weight-exam').value) || 0;
            
            if (w + p + e !== 100) {
                window.showToast("Weights must total 100%", "error");
                return;
            }
            
            window.gradingSystem.config.written.weight = w;
            window.gradingSystem.config.performance.weight = p;
            window.gradingSystem.config.exam.weight = e;
            await window.gradingSystem.syncConfig();
            window.closeModal('weightConfigModal');
            window.showToast("Weights Updated", "success");
        };

        window.processBulkAdd = () => {
            window.showToast("Bulk enrollment module pending.", "info");
            window.closeModal('addStudentModal');
        };

        // Data Management Logic
        async function fetchStudentDetails(uids) {
            if (!uids || uids.length === 0) return [];
            console.log("Fetching details for", uids.length, "students...");
            try {
                const results = await Promise.allSettled(uids.map(uid => getDoc(doc(db, "students", uid))));
                return results
                    .filter(res => res.status === 'fulfilled' && res.value.exists())
                    .map(res => ({ uid: res.value.id, ...res.value.data() }));
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
                if(countSpan) countSpan.innerText = "0 ENTITIES ENROLLED";
                if(countTop) countTop.innerText = "0 ENTITIES ENROLLED";
                feather.replace();
                return;
            }

            tbody.innerHTML = students.map((s, index) => `
                <tr class="border-b border-white/5 hover:bg-white/5 transition-colors group">
                    <td class="p-5 text-gray-500 font-mono text-[10px] text-center">${index + 1}</td>
                    <td class="p-5">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-2xl bg-primary-500/10 flex items-center justify-center border border-primary-500/10 shadow-lg shadow-primary-500/5 group-hover:scale-110 transition-transform">
                                <span class="text-[10px] font-black text-primary-500 uppercase italic">${(s.firstName?.charAt(0) || '') + (s.lastName?.charAt(0) || '')}</span>
                            </div>
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
            
            if(countSpan) countSpan.innerText = `${students.length} STUDENT${students.length === 1 ? '' : 'S'} ENROLLED`;
            if(countTop) countTop.innerText = `${students.length} STUDENT${students.length === 1 ? '' : 'S'} ENROLLED`;
            feather.replace();
        }

        async function initClassHub() {
            if (!classId) { 
                window.showToast('Terminal Error: No Hub ID found.', 'error');
                setTimeout(() => window.location.href = 'classes.php', 2000);
                return; 
            }
            
            try {
                console.log("Initializing Hub for ID:", classId);
                const classRef = doc(db, "classes", classId);
                
                onSnapshot(classRef, async (snapshot) => {
                    if (snapshot.exists()) {
                        const classData = snapshot.data();
                        document.getElementById('viewClassName').innerText = classData.className;
                        document.getElementById('breadcrumbClassName').innerText = classData.subject || classData.className;
                        
                        const codeSpan = document.getElementById('displayClassCode');
                        if (codeSpan) codeSpan.innerText = classData.classCode || classData.id;
                        
                        const uids = classData.students || [];
                        const fullStudentData = await fetchStudentDetails(uids);
                        renderRoster(fullStudentData);
                        window.gradingSystem.init(fullStudentData);
                    } else { 
                        window.showToast('Hub Inaccessible or Offline.', 'error');
                        setTimeout(() => window.location.href = 'classes.php', 3000);
                    }
                }, (error) => {
                    console.error("Hub Sync Failure:", error);
                    window.showToast('Security Violation or Hub Offline.', 'error');
                });
            } catch (err) {
                console.error("Execution Failure:", err);
                window.showToast('Major Connection Error. Check Console.', 'error');
            }
        }

        // Boot
        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();
            initClassHub();
            // Start on Students Tab
            window.switchTab('students');
        });
    </script>
</body>
</html>