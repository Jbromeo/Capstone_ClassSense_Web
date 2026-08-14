<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- student_view/student_classes.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | My Classes</title>
    <?php include '../includes/head.php'; ?>
    <style>
        /* Custom Animation for FAB */
        .fab-pulse {
            animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 0.8; }
            80%, 100% { transform: scale(1.3); opacity: 0; }
        }
        
        /* Modal Transition */
        .modal-overlay {
            transition: opacity 0.3s ease;
        }
        .modal-content {
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        .hidden-modal {
            opacity: 0;
            pointer-events: none;
        }
        .hidden-modal .modal-content {
            transform: scale(0.95);
            opacity: 0;
        }
    </style>
</head>
    <body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">
        <!-- Animated Background Blobs -->
        <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
        </div>

        <?php include 'student_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
            
            <!-- HEADER -->
            <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                        <i data-feather="menu"></i>
                    </button>
                    <h2 class="text-xl font-bold text-white hidden sm:block">My Classes</h2>
                </div>

                <div class="flex items-center gap-4">
                    <div class="relative hidden md:block group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="search" class="h-4 w-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i>
                        </div>
                        <input id="classSearchInput" type="text" class="bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-full focus:ring-primary-500 focus:border-primary-500 block w-64 pl-10 p-2.5 transition-all focus:w-80 placeholder-gray-600" placeholder="Search subjects...">
                    </div>

                    <div class="relative">
                        <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors">
                            <i data-feather="bell"></i>
                            <span class="notif-dot hidden absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                        </button>
                        <?php include '../includes/notification_popover.php'; ?>
                    </div>
                    <button id="mobileSearchBtn" class="p-2 text-gray-400 hover:text-white transition-colors md:hidden">
                        <i data-feather="search"></i>
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 md:p-8 pb-24"> <!-- Added pb-24 for FAB spacing -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-white">Enrolled Subjects</h3>
                    <p id="enrolledCount" class="text-sm text-gray-400">Loading...</p>
                </div>

                    <!-- Dynamic Class Grid -->
                <div id="studentClassGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <!-- Data will be injected here -->
                    <div class="col-span-full py-20 text-center opacity-40">
                        <div class="animate-pulse space-y-4">
                            <div class="glass-panel h-48 w-full rounded-2xl mx-auto"></div>
                            <p class="text-[10px] font-black uppercase tracking-widest tracking-tighter">Syncing Enrolled Modules...</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- JOIN CLASS FLOATING ACTION BUTTON (FAB) -->
        <div class="fixed bottom-6 right-6 z-40">
            <!-- Pulse Ring Effect -->
            <div class="absolute inset-0 bg-primary-500 rounded-full fab-pulse"></div>
            
            <button id="openJoinModal" class="relative flex items-center justify-center w-14 h-14 bg-primary-500 rounded-full shadow-lg shadow-primary-500/30 hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-offset-2 focus:ring-offset-dark-bg transition-all transform hover:scale-105 active:scale-95">
                <i data-feather="plus" class="w-6 h-6 text-white"></i>
            </button>
        </div>

        <!-- JOIN CLASS MODAL -->
        <div id="joinModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden-modal modal-overlay bg-black/60 backdrop-blur-sm">
            <div class="modal-container w-full max-w-md">
                
                <!-- Modal Content -->
                <div class="modal-content bg-dark-surface border border-dark-border rounded-2xl shadow-2xl overflow-hidden">
                    
                    <!-- Header -->
                    <div class="relative p-6 pb-0 flex justify-between items-start">
                        <div class="p-3 bg-primary-500/10 rounded-xl border border-primary-500/20">
                            <i data-feather="user-plus" class="w-6 h-6 text-primary-400"></i>
                        </div>
                        <button id="closeJoinModal" class="p-1 text-gray-500 hover:text-white hover:bg-white/10 rounded-full transition-colors">
                            <i data-feather="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-white mb-1">Join a Class</h3>
                        <p class="text-sm text-gray-400 mb-6">Enter the class code provided by your teacher to enroll.</p>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2 uppercase tracking-wider">Class Code</label>
                                <input type="text" id="classCodeInput" placeholder="e.g. XJY-342-ZQ" class="w-full bg-dark-bg border border-dark-border text-white text-lg font-mono tracking-widest rounded-lg p-4 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all placeholder:text-gray-600">
                            </div>

                            <div class="bg-amber-500/10 border border-amber-500/20 rounded-lg p-3 flex items-start gap-3">
                                <i data-feather="info" class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5"></i>
                                <p class="text-xs text-amber-200/80">
                                    Make sure the code is correct. You can usually find this on your syllabus or the class board.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-6 pt-2 flex gap-3">
                        <button id="cancelJoinModal" class="flex-1 py-2.5 px-4 text-sm font-medium text-gray-400 bg-dark-bg hover:bg-white/10 border border-dark-border rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button id="submitJoin" class="flex-1 py-2.5 px-4 text-sm font-medium text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-lg shadow-primary-500/20">
                            Join Class
                            <i data-feather="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script type="module">
            import { api, initPage } from '../assets/js/custom-auth.js';

            let currentStudent = null;
            let allClasses = [];
            let lastClassesKey = null;

            // --- UI Setup ---
            const standingFor = (c) => {
                const rate = c.attendanceRate;
                if (rate === null || rate === undefined) return { label: '—', cls: 'text-gray-500', bar: '0%', barCls: 'bg-gray-600' };
                if (rate >= 80) return { label: 'Good Standing', cls: 'text-green-400', bar: rate + '%', barCls: 'bg-green-500' };
                return { label: 'Needs Attention', cls: 'text-amber-400', bar: rate + '%', barCls: 'bg-amber-500' };
            };

            const renderClasses = (classes) => {
                const grid = document.getElementById('studentClassGrid');
                if (classes.length === 0) {
                    grid.innerHTML = `
                         <div class="col-span-full py-20 text-center opacity-40">
                            <i data-feather="layers" class="w-12 h-12 mx-auto mb-4"></i>
                            <p class="text-[10px] font-black uppercase tracking-widest">You haven't joined any classes yet.</p>
                        </div>`;
                } else {
                    grid.innerHTML = classes.map(c => {
                        const st = standingFor(c);
                        return `
                        <div class="glass-panel rounded-xl overflow-hidden border border-dark-border hover:border-primary-500/30 transition-all group flex flex-col cursor-pointer" onclick="window.location.href='student_class_view.php?id=${c.id}'">
                            <div class="p-5 border-b border-dark-border flex justify-between items-start">
                                <div>
                                    <span class="text-[10px] font-black text-primary-400 uppercase tracking-widest">${c.section_name || 'General'}</span>
                                    <h3 class="text-lg font-bold text-white uppercase tracking-tighter leading-none">${c.class_name}</h3>
                                    <p class="text-[9px] text-gray-500 font-bold uppercase mt-1 tracking-widest leading-none">${c.teacher_name || 'Faculty Account'}</p>
                                </div>
                                <div class="p-2 bg-primary-500/10 rounded-lg text-primary-400 border border-primary-500/20">
                                    <i data-feather="book-open" class="w-5 h-5"></i>
                                </div>
                            </div>
                            <div class="p-5 flex-1 bg-dark-bg/20">
                                <div class="grid grid-cols-2 gap-4 mb-4 text-[10px] uppercase font-black tracking-widest opacity-60">
                                    <div class="flex items-center gap-2 text-gray-400">
                                        <i data-feather="calendar" class="w-3.5 h-3.5 text-primary-400"></i> ${c.schedule || 'Days TBA'}
                                    </div>
                                    <div class="flex items-center gap-2 text-gray-400 text-right justify-end">
                                        <i data-feather="clock" class="w-3.5 h-3.5 text-primary-400"></i> ${c.time_slot || 'TBA'}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Academic Standing</span>
                                    <span class="text-[10px] font-black uppercase tracking-widest ${st.cls}">${st.label}</span>
                                </div>
                                <div class="w-full h-1 bg-dark-border rounded-full overflow-hidden">
                                    <div class="h-full ${st.barCls} rounded-full" style="width: ${st.bar}"></div>
                                </div>
                            </div>
                            <div class="p-4 border-t border-dark-border">
                                <a href="student_class_view.php?id=${c.id}" onclick="event.stopPropagation()" class="w-full py-2.5 text-center text-[10px] font-black uppercase tracking-widest text-white bg-dark-bg hover:bg-white/10 rounded-lg transition-colors flex items-center justify-center gap-2 border border-white/5">
                                    OPEN CLASS HUB <i data-feather="chevron-right" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>`;
                    }).join('');
                }
                feather.replace();
            };

            const filterClasses = (query) => {
                if (!query) {
                    renderClasses(allClasses);
                    document.getElementById('enrolledCount').textContent = allClasses.length + ' subject' + (allClasses.length !== 1 ? 's' : '');
                    return;
                }
                const q = query.toLowerCase();
                const filtered = allClasses.filter(c =>
                    (c.class_name || '').toLowerCase().includes(q) ||
                    (c.section_name || '').toLowerCase().includes(q) ||
                    (c.teacher_name || '').toLowerCase().includes(q)
                );
                renderClasses(filtered);
                document.getElementById('enrolledCount').textContent = filtered.length + ' subject' + (filtered.length !== 1 ? 's' : '') + ' found';
            };

            // --- Search ---
            const searchInput = document.getElementById('classSearchInput');
            if (searchInput) {
                // Handle ?search= query param
                const urlParams = new URLSearchParams(window.location.search);
                const searchParam = urlParams.get('search');
                if (searchParam) {
                    searchInput.value = searchParam;
                    filterClasses(searchParam);
                }
                searchInput.addEventListener('input', (e) => filterClasses(e.target.value));
            }

            // --- Mobile Search Toggle ---
            const mobileSearchBtn = document.getElementById('mobileSearchBtn');
            if (mobileSearchBtn && searchInput) {
                mobileSearchBtn.onclick = () => {
                    const parent = searchInput.closest('.relative');
                    parent.classList.toggle('hidden');
                    if (!parent.classList.contains('hidden')) searchInput.focus();
                };
            }

            // --- Joining Logic ---
            window.handleJoinClass = async () => {
                const codeInput = document.getElementById('classCodeInput');
                const code = codeInput.value.trim().toUpperCase();

                if (!code || !currentStudent) {
                    codeInput.classList.add('border-primary-500', 'shake');
                    setTimeout(() => codeInput.classList.remove('shake'), 500);
                    return;
                }

                try {
                    await api('/enroll.php', {
                        method: 'POST',
                        body: JSON.stringify({ class_code: code, student_uid: currentStudent.uid })
                    });

                    showStatus('Successfully joined class!', 'success');
                    codeInput.value = '';
                    window.closeModal();
                    loadStudentClasses();

                } catch (error) {
                    console.error('Join Error:', error);
                    if (error.message === 'Already enrolled') {
                        showStatus('You are already enrolled in this class.', 'error');
                    } else if (error.message === 'Invalid class code') {
                        showStatus('Class code not found. Please try again.', 'error');
                    } else {
                        showStatus('Sync Error: Failed to join class.', 'error');
                    }
                }
            };

            // --- Modal Helpers ---
            window.closeModal = () => document.getElementById('joinModal').classList.add('hidden-modal');
            window.openModal = () => {
                document.getElementById('joinModal').classList.remove('hidden-modal');
                feather.replace();
            };

            document.getElementById('openJoinModal').onclick = window.openModal;
            document.getElementById('closeJoinModal').onclick = window.closeModal;
            document.getElementById('cancelJoinModal').onclick = window.closeModal;
            document.getElementById('submitJoin').onclick = window.handleJoinClass;

            // --- Lifecycle ---
            initPage((user) => {
                currentStudent = user;
                window.loadStudentClasses = async () => {
                    try {
                        const classes = await api('/classes.php?student_uid=' + user.uid);
                        const key = JSON.stringify(classes);
                        if (key === lastClassesKey) return;
                        lastClassesKey = key;
                        allClasses = classes;
                        const q = searchInput ? searchInput.value : '';
                        if (q) filterClasses(q);
                        else renderClasses(classes);
                        document.getElementById('enrolledCount').textContent = classes.length + ' subject' + (classes.length !== 1 ? 's' : '');
                    } catch (err) {
                        console.error('Failed to load classes:', err);
                    }
                };
                loadStudentClasses();
                setInterval(loadStudentClasses, 10000);
            });
            
            feather.replace();
        </script>

        <style>
            .toast { transform: translateX(120%); transition: all 0.4s cubic-bezier(0.68, -0.55, 0.26, 1.55); opacity: 0; }
            .toast.show { transform: translateX(0); opacity: 1; }
        </style>

        <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>

        <script>
            function showStatus(message, type = 'error') {
                const container = document.getElementById('toastContainer');
                if (!container) return;
                const toast = document.createElement('div');
                const isError = type === 'error';
                toast.className = `toast flex items-center w-full max-w-xs p-4 space-x-4 text-gray-200 bg-dark-surface rounded-lg shadow-2xl border border-dark-border ${isError ? 'border-l-4 border-l-primary-500' : 'border-l-4 border-l-green-500'}`;
                toast.innerHTML = `<div class="flex-shrink-0"><i data-feather="${isError ? 'alert-circle' : 'check-circle'}" class="w-5 h-5 ${isError ? 'text-primary-500' : 'text-green-500'}"></i></div><div class="text-xs font-semibold">${message}</div>`;
                container.appendChild(toast);
                feather.replace();
                setTimeout(() => toast.classList.add('show'), 10);
                setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 4000);
            }
        </script>

    <script type="module" src="student_auth.js"></script>
    </body>
    </html>