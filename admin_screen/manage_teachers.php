<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- admin_screen/manage_teachers.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense Admin | Manage Teachers</title>
    <?php include '../includes/head.php'; ?>
    <style>
        #confirmModal { pointer-events: none; }
        #confirmModal.show { opacity: 1; pointer-events: auto; }
        #confirmModal.show > div:last-child { transform: scale(1); }
        .animate-scale-up { transform: scale(0.95); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .toast { transform: translateX(120%); transition: all 0.4s cubic-bezier(0.68, -0.55, 0.26, 1.55); opacity: 0; }
        .toast.show { transform: translateX(0); opacity: 1; }
        @keyframes flashGreen {
            0% { background: rgba(34, 197, 94, 0.2); border-color: rgba(34, 197, 94, 0.5); }
            100% { background: transparent; border-color: rgba(255, 255, 255, 0.05); }
        }
        .new-entry-highlight { animation: flashGreen 4s ease-out forwards; border-left: 4px solid #22c55e !important; }
        @keyframes idShake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }
        .shake { animation: idShake 0.4s ease-in-out; }
    </style>
</head>
<body class="antialiased h-screen overflow-hidden flex bg-dark-bg selection:bg-primary-500 selection:text-white">
    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-[20%] w-[500px] h-[500px] bg-purple-600/10 rounded-full mix-blend-screen filter blur-3xl animate-blob-slow transform -translate-y-1/2"></div>
        <div class="absolute bottom-0 left-[20%] w-[500px] h-[500px] bg-primary-600/10 rounded-full mix-blend-screen filter blur-3xl animate-blob-slow transform translate-y-1/2" style="animation-delay: 4s"></div>
    </div>

    <?php include 'admin_sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 h-full relative">
        <header class="h-20 flex-shrink-0 glass-panel border-b-0 border-dark-border flex items-center justify-between px-8 z-20">
            <h2 class="text-xl font-bold text-white italic">Teacher Management <span class="text-xs text-gray-500 font-normal ml-3 tracking-widest uppercase">Admin Panel</span></h2>
            <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                
                <!-- Create Teacher Form -->
                <div class="xl:col-span-1 space-y-6">
                    <div class="glass-panel rounded-2xl p-6 border-l-4 border-l-purple-500 animate-fade-in-up">
                        <h3 class="text-lg font-bold text-white mb-2">Assign New Teacher</h3>
                        <p class="text-gray-400 text-sm mb-6 font-medium leading-relaxed italic uppercase tracking-tighter shadow-sm">Generate a secure teacher account instantly.</p>
                        
                        <form id="addTeacherForm" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-xs font-bold text-gray-500 uppercase mb-2">First Name</label><input type="text" name="fname" required class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-purple-500 outline-none transition-all" placeholder="Ada"></div>
                                <div><label class="block text-xs font-bold text-gray-500 uppercase mb-2">Last Name</label><input type="text" name="lname" required class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-purple-500 outline-none transition-all" placeholder="Lovelace"></div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Login Username</label>
                                <input type="text" name="username" required class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-purple-500 outline-none transition-all" placeholder="ada_lovelace">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label id="employeeIdLabel" class="block text-xs font-bold text-gray-500 uppercase mb-2">Employee ID</label><input type="text" name="employee_id" id="employeeIdInput" required class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-purple-500 outline-none transition-all" placeholder="20250001"></div>
                                <div><label class="block text-xs font-bold text-gray-500 uppercase mb-2">Password</label><input type="password" name="password" required class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-purple-500 outline-none transition-all" placeholder="••••••••"></div>
                            </div>
                            
                            <button type="submit" id="submitBtn" class="w-full py-3 bg-purple-600 hover:bg-purple-700 rounded-xl font-bold text-white transition-all shadow-lg shadow-purple-500/20 mt-4 flex items-center justify-center gap-2 group">
                                <span id="btnText">Establish Account</span>
                                <div id="btnLoader" class="hidden w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></div>
                                <i data-feather="plus" class="w-4 h-4 transition-transform group-hover:rotate-90"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Teacher List Table -->
                <div class="xl:col-span-2 space-y-6">
                    <div class="glass-panel rounded-2xl overflow-hidden animate-fade-in-up flex flex-col h-[700px]" style="animation-delay: 0.1s">
                        <div class="p-6 border-b border-dark-border bg-white/5 space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-bold text-white tracking-tight leading-none uppercase">Academic Registry</h3>
                                <div class="flex items-center gap-3">
                                    <div class="px-3 py-1 bg-purple-500/10 border border-purple-500/20 rounded-lg shadow-inner">
                                        <span id="teacherCount" class="text-purple-400 font-bold text-[10px] uppercase tracking-widest leading-none italic italic">Syncing...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="relative group">
                                <i data-feather="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 group-focus-within:text-purple-500 transition-colors"></i>
                                <input type="text" id="teacherSearch" placeholder="Search by Username..." class="w-full bg-dark-bg border border-dark-border rounded-xl pl-11 pr-4 py-3 text-sm text-white focus:ring-2 focus:ring-purple-500/50 outline-none transition-all placeholder-gray-600 font-medium italic uppercase tracking-tighter">
                            </div>
                        </div>

                        <div id="tableScrollArea" class="flex-1 overflow-y-auto custom-scroll">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-dark-surface text-gray-500 uppercase text-xs font-black tracking-widest border-b border-dark-border">
                                        <th class="px-8 py-5">Educator</th>
                                        <th class="px-8 py-5">Employee ID</th>
                                        <th class="px-8 py-5">Username</th>
                                        <th class="px-8 py-5 text-center">Settings</th>
                                    </tr>
                                </thead>
                                <tbody id="teacherTableBody" class="divide-y divide-dark-border text-sm italic"></tbody>
                            </table>
                            <div id="loadMoreSentinel" class="p-10 text-center border-t border-white/[0.02]">
                                <div id="lazyLoader" class="flex flex-col items-center gap-3 opacity-0 transition-opacity duration-300">
                                    <div class="w-8 h-8 border-2 border-purple-500/20 border-t-purple-500 rounded-full animate-spin"></div>
                                    <p class="text-[9px] text-gray-500 font-black uppercase tracking-widest italic animate-pulse">Expanding Registry...</p>
                                </div>
                                <div id="endOfList" class="hidden py-4 text-[9px] text-gray-600 font-bold uppercase tracking-[0.2em] italic">No more educators in registry</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden opacity-0 transition-all duration-300">
        <div class="absolute inset-0 bg-dark-bg/60 backdrop-blur-md"></div>
        <div class="glass-panel w-full max-w-sm rounded-[2.5rem] p-8 border border-white/10 shadow-[0_20px_50px_rgba(234,38,40,0.2)] animate-scale-up relative z-10 text-center">
            <div class="w-20 h-20 bg-primary-500/10 rounded-full flex items-center justify-center mx-auto mb-6 border border-primary-500/20">
                <i data-feather="alert-triangle" class="w-10 h-10 text-primary-500"></i>
            </div>
            <h3 class="text-2xl font-black text-white italic mb-2 tracking-tight uppercase tracking-tighter">Final Warning</h3>
            <p class="text-gray-400 text-sm mb-8 leading-relaxed font-bold">You are about to permanently erase <span id="targetTeacherName" class="text-white italic underline underline-offset-4 decoration-primary-500/50 underline-bold font-black">Teacher</span> from the entire platform identity registry.</p>
            <div class="space-y-3">
                <button id="confirmPurgeBtn" class="w-full py-4 bg-primary-500 hover:bg-primary-600 rounded-2xl font-black text-white transition-all shadow-lg shadow-primary-500/20 uppercase tracking-[0.2em] italic text-xs leading-none">Confirm Purge</button>
                <button id="cancelPurgeBtn" class="w-full py-4 bg-white/5 hover:bg-white/10 rounded-2xl font-bold text-gray-500 hover:text-white transition-all text-xs uppercase tracking-widest leading-none">Cancel Action</button>
            </div>
        </div>
    </div>

    <script type="module" src="../assets/js/controllers/admin_teacher_controller.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => { 
            feather.replace(); 
            // Expose showStatus to the window for the modules to use
            window.showStatus = (message, type = 'error') => {
                const container = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                const isError = type === 'error';
                toast.className = `toast flex items-center w-full max-w-xs p-4 space-x-4 text-gray-200 bg-dark-surface rounded-lg shadow-2xl border border-dark-border ${isError ? 'border-l-4 border-l-primary-500' : 'border-l-4 border-l-purple-500'}`;
                toast.innerHTML = `<div class="flex-shrink-0"><i data-feather="${isError ? 'alert-circle' : 'check-circle'}" class="w-5 h-5 ${isError ? 'text-primary-500' : 'text-purple-500'}"></i></div><div class="text-xs font-semibold">${message}</div>`;
                container.appendChild(toast);
                feather.replace();
                setTimeout(() => toast.classList.add('show'), 10);
                setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 4000);
            };
        });
    </script>
</body>
</html>
