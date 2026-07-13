<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- grades.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | Grades & Reports</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-purple-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 4s"></div>
    </div>

    <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        <!-- Header -->
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                    <i data-feather="menu"></i>
                </button>
                <div>
                    <h2 class="text-xl font-bold text-white">Grades & Reports</h2>
                    <p class="text-xs text-gray-500 hidden sm:block">Overview of all student performance</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="showToast('Exporting data...')" class="hidden sm:flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-300 border border-dark-border hover:border-gray-600 hover:text-white rounded-lg transition-colors">
                    <i data-feather="download" class="w-4 h-4"></i> Export
                </button>
                <button onclick="showToast('Generating Report...')" class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all shadow-lg shadow-primary-500/20">
                    <i data-feather="file-text" class="w-4 h-4"></i> <span class="hidden sm:inline">Generate Report</span>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8 relative custom-scroll">
            
            <!-- Filters Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-3 overflow-x-auto pb-2 md:pb-0 scrollbar-hide">
                    <button class="px-4 py-2 text-sm font-medium rounded-lg bg-white/10 text-white border border-white/10 whitespace-nowrap">All Classes</button>
                    <button class="px-4 py-2 text-sm font-medium rounded-lg bg-dark-bg text-gray-400 border border-dark-border hover:border-gray-600 whitespace-nowrap">CS101</button>
                    <button class="px-4 py-2 text-sm font-medium rounded-lg bg-dark-bg text-gray-400 border border-dark-border hover:border-gray-600 whitespace-nowrap">CS201</button>
                    <button class="px-4 py-2 text-sm font-medium rounded-lg bg-dark-bg text-gray-400 border border-dark-border hover:border-gray-600 whitespace-nowrap">CS301</button>
                </div>
                <div class="relative">
                    <i data-feather="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                    <input type="text" placeholder="Search student..." class="w-full md:w-64 bg-dark-bg border border-dark-border rounded-lg pl-9 pr-4 py-2 text-sm text-white focus:ring-1 focus:ring-primary-500 outline-none">
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-bold">Class Average</p>
                            <h3 class="text-2xl font-bold text-white mt-1">87.5%</h3>
                        </div>
                        <div class="p-2 bg-green-500/10 rounded-lg text-green-500">
                            <i data-feather="trending-up" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-bold">Passing Rate</p>
                            <h3 class="text-2xl font-bold text-white mt-1">94%</h3>
                        </div>
                        <div class="p-2 bg-blue-500/10 rounded-lg text-blue-500">
                            <i data-feather="check-circle" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-amber-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-bold">Missing Grades</p>
                            <h3 class="text-2xl font-bold text-white mt-1">12</h3>
                        </div>
                        <div class="p-2 bg-amber-500/10 rounded-lg text-amber-500">
                            <i data-feather="alert-triangle" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-bold">Total Students</p>
                            <h3 class="text-2xl font-bold text-white mt-1">142</h3>
                        </div>
                        <div class="p-2 bg-purple-500/10 rounded-lg text-purple-500">
                            <i data-feather="users" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Master Table -->
            <div class="glass-panel rounded-xl overflow-hidden border border-dark-border">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm min-w-[800px]">
                        <thead class="bg-dark-bg/50 text-xs uppercase text-gray-500 font-bold tracking-wider border-b border-dark-border">
                            <tr>
                                <th class="p-4 sticky left-0 bg-dark-bg/95 z-20 min-w-[200px]">Student Name</th>
                                <th class="p-4 text-center">Class</th>
                                <th class="p-4 text-center bg-blue-500/5">Midterm</th>
                                <th class="p-4 text-center bg-blue-500/5">Final</th>
                                <th class="p-4 text-center bg-green-500/5">Project</th>
                                <th class="p-4 text-center">Average</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-300">
                            <!-- Row 1 -->
                            <tr class="border-b border-dark-border hover:bg-white/5 transition-colors group">
                                <td class="p-4 sticky left-0 bg-dark-surface/95 z-10 group-hover:bg-dark-surface">
                                    <div class="flex items-center gap-3">
                                        <img src="https://picsum.photos/seed/st1/40/40" class="w-9 h-9 rounded-full object-cover">
                                        <div>
                                            <p class="font-bold text-white">Alice Johnson</p>
                                            <p class="text-xs text-gray-500">ID: ST-001</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-center"><span class="px-2 py-1 rounded text-xs bg-blue-500/10 text-blue-400">CS101</span></td>
                                <td class="p-4 text-center bg-blue-500/5 font-mono text-white">92</td>
                                <td class="p-4 text-center bg-blue-500/5 font-mono text-white">88</td>
                                <td class="p-4 text-center bg-green-500/5 font-mono text-white">95</td>
                                <td class="p-4 text-center font-bold text-white">91.6</td>
                                <td class="p-4 text-center"><span class="px-2 py-1 rounded-full text-xs font-bold bg-green-500/10 text-green-400 border border-green-500/20">Passed</span></td>
                                <td class="p-4 text-center">
                                    <button onclick="showToast('Editing Grade...')" class="p-2 text-gray-500 hover:text-white hover:bg-white/10 rounded transition-colors">
                                        <i data-feather="edit-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Row 2 -->
                            <tr class="border-b border-dark-border hover:bg-white/5 transition-colors group">
                                <td class="p-4 sticky left-0 bg-dark-surface/95 z-10 group-hover:bg-dark-surface">
                                    <div class="flex items-center gap-3">
                                        <img src="https://picsum.photos/seed/st2/40/40" class="w-9 h-9 rounded-full object-cover">
                                        <div>
                                            <p class="font-bold text-white">Michael Chen</p>
                                            <p class="text-xs text-gray-500">ID: ST-002</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-center"><span class="px-2 py-1 rounded text-xs bg-blue-500/10 text-blue-400">CS101</span></td>
                                <td class="p-4 text-center bg-blue-500/5 font-mono text-white">78</td>
                                <td class="p-4 text-center bg-blue-500/5 font-mono text-white">85</td>
                                <td class="p-4 text-center bg-green-500/5 font-mono text-white">80</td>
                                <td class="p-4 text-center font-bold text-white">81.0</td>
                                <td class="p-4 text-center"><span class="px-2 py-1 rounded-full text-xs font-bold bg-green-500/10 text-green-400 border border-green-500/20">Passed</span></td>
                                <td class="p-4 text-center">
                                    <button class="p-2 text-gray-500 hover:text-white hover:bg-white/10 rounded transition-colors">
                                        <i data-feather="edit-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Row 3 (At Risk) -->
                            <tr class="border-b border-dark-border hover:bg-white/5 transition-colors group bg-red-500/5">
                                <td class="p-4 sticky left-0 bg-dark-surface/95 z-10 group-hover:bg-dark-surface border-l-4 border-red-500">
                                    <div class="flex items-center gap-3">
                                        <img src="https://picsum.photos/seed/st3/40/40" class="w-9 h-9 rounded-full object-cover opacity-80">
                                        <div>
                                            <p class="font-bold text-white">James Wilson</p>
                                            <p class="text-xs text-red-400">At Risk</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-center"><span class="px-2 py-1 rounded text-xs bg-purple-500/10 text-purple-400">CS301</span></td>
                                <td class="p-4 text-center bg-blue-500/5 font-mono text-red-400">62</td>
                                <td class="p-4 text-center bg-blue-500/5 font-mono text-red-400">65</td>
                                <td class="p-4 text-center bg-green-500/5 font-mono text-red-400">60</td>
                                <td class="p-4 text-center font-bold text-red-400">62.3</td>
                                <td class="p-4 text-center"><span class="px-2 py-1 rounded-full text-xs font-bold bg-red-500/10 text-red-400 border border-red-500/20">Failed</span></td>
                                <td class="p-4 text-center">
                                    <button class="p-2 text-red-400 hover:text-white hover:bg-red-500/10 rounded transition-colors">
                                        <i data-feather="edit-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Row 4 -->
                            <tr class="border-b border-dark-border hover:bg-white/5 transition-colors group">
                                <td class="p-4 sticky left-0 bg-dark-surface/95 z-10 group-hover:bg-dark-surface">
                                    <div class="flex items-center gap-3">
                                        <img src="https://picsum.photos/seed/st4/40/40" class="w-9 h-9 rounded-full object-cover">
                                        <div>
                                            <p class="font-bold text-white">Sarah Davis</p>
                                            <p class="text-xs text-gray-500">ID: ST-003</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-center"><span class="px-2 py-1 rounded text-xs bg-purple-500/10 text-purple-400">CS201</span></td>
                                <td class="p-4 text-center bg-blue-500/5 font-mono text-white">88</td>
                                <td class="p-4 text-center bg-blue-500/5 font-mono text-white">92</td>
                                <td class="p-4 text-center bg-green-500/5 font-mono text-white">96</td>
                                <td class="p-4 text-center font-bold text-green-400">92.0</td>
                                <td class="p-4 text-center"><span class="px-2 py-1 rounded-full text-xs font-bold bg-green-500/10 text-green-400 border border-green-500/20">Passed</span></td>
                                <td class="p-4 text-center">
                                    <button class="p-2 text-gray-500 hover:text-white hover:bg-white/10 rounded transition-colors">
                                        <i data-feather="edit-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="p-4 border-t border-dark-border flex justify-between items-center bg-dark-bg/30">
                    <span class="text-sm text-gray-500">Showing 1-4 of 142 students</span>
                    <div class="flex gap-1">
                        <button class="px-3 py-1 rounded bg-dark-bg text-gray-400 border border-dark-border hover:bg-white/5">Prev</button>
                        <button class="px-3 py-1 rounded bg-primary-600 text-white">1</button>
                        <button class="px-3 py-1 rounded bg-dark-bg text-gray-400 border border-dark-border hover:bg-white/5">2</button>
                        <button class="px-3 py-1 rounded bg-dark-bg text-gray-400 border border-dark-border hover:bg-white/5">Next</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });

        // Mobile Menu Logic
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileOverlay = document.getElementById('mobileOverlay');
        const sidebar = document.getElementById('sidebar');

        mobileMenuBtn.addEventListener('click', () => {
            if(sidebar.style.display === 'flex') {
                sidebar.style.display = ''; 
                sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64');
                mobileOverlay.classList.remove('open');
            } else {
                sidebar.style.display = 'flex';
                sidebar.classList.add('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64');
                mobileOverlay.classList.add('open');
            }
        });

        mobileOverlay.addEventListener('click', () => {
            sidebar.style.display = '';
            sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64');
            mobileOverlay.classList.remove('open');
        });

        // Toast Logic
        function showToast(message, type = 'info') { 
            const container = document.getElementById('toastContainer'); 
            const toast = document.createElement('div'); 
            toast.className = `toast flex items-center w-full max-w-xs p-4 space-x-4 text-gray-200 bg-gray-800 rounded-lg shadow-lg border border-gray-700 ${type === 'success' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-primary-500'}`; 
            toast.innerHTML = `<div class="text-sm font-normal">${message}</div>`; 
            container.appendChild(toast); 
            requestAnimationFrame(() => toast.classList.add('show')); 
            setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 3000); 
        }
    </script>
    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';
    </script>
</body>
</html>