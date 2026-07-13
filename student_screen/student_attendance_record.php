<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- student_view/student_attendance_record.php -->
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
        
        <!-- HEADER (Consistent with Teacher Dashboard) -->
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                    <i data-feather="menu"></i>
                </button>
                <h2 class="text-xl font-bold text-white hidden sm:block">Attendance Records</h2>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative hidden md:block group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i data-feather="search" class="h-4 w-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i>
                    </div>
                    <input id="attendanceSearchInput" type="text" class="bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-full focus:ring-primary-500 focus:border-primary-500 block w-64 pl-10 p-2.5 transition-all focus:w-80 placeholder-gray-600" placeholder="Search records...">
                </div>

                <div class="relative">
                    <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors">
                        <i data-feather="bell"></i>
                        <span class="notif-dot hidden absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                    </button>
                    <?php include '../includes/notification_popover.php'; ?>
                </div>
                <button id="attMobileSearchBtn" class="p-2 text-gray-400 hover:text-white transition-colors md:hidden">
                    <i data-feather="search"></i>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                
                <!-- Total Present -->
                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-green-500 hover-card">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Present</p>
                            <h3 class="text-2xl font-bold text-white mt-1">45</h3>
                        </div>
                        <div class="p-2 bg-green-500/10 rounded-lg text-green-500">
                            <i data-feather="check-circle" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Absent -->
                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-red-500 hover-card">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Absent</p>
                            <h3 class="text-2xl font-bold text-white mt-1">3</h3>
                        </div>
                        <div class="p-2 bg-red-500/10 rounded-lg text-red-500">
                            <i data-feather="x-circle" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Late -->
                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-amber-500 hover-card">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Late</p>
                            <h3 class="text-2xl font-bold text-white mt-1">5</h3>
                        </div>
                        <div class="p-2 bg-amber-500/10 rounded-lg text-amber-500">
                            <i data-feather="clock" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>

                <!-- Early Leave -->
                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-orange-500 hover-card">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Early Leave</p>
                            <h3 class="text-2xl font-bold text-white mt-1">2</h3>
                        </div>
                        <div class="p-2 bg-orange-500/10 rounded-lg text-orange-500">
                            <i data-feather="log-out" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>

                <!-- Percentage -->
                <div class="glass-panel p-5 rounded-xl border-l-4 border-l-blue-500 hover-card col-span-2 lg:col-span-1">
                    <div class="flex flex-col h-full justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Overall</p>
                                <h3 class="text-2xl font-bold text-white mt-1">92%</h3>
                            </div>
                        </div>
                        <div class="w-full bg-dark-border rounded-full h-1.5 mt-3">
                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: 92%"></div>
                        </div>
                        <p class="text-xs text-green-400 mt-2 font-medium">Good Standing</p>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="filter" class="w-4 h-4 text-gray-500"></i>
                        </div>
                        <select class="bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block pl-9 pr-10 py-2.5 appearance-none cursor-pointer min-w-[200px]">
                            <option value="all">All Subjects</option>
                            <option value="CS101">CS101 - Programming</option>
                            <option value="MATH202">MATH202 - Calculus</option>
                            <option value="IT101">IT101 - Networking</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                            <i data-feather="chevron-down" class="w-4 h-4 text-gray-500"></i>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-sm text-gray-400">
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-green-500"></span> Present</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Late</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span> Absent</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-orange-500"></span> Early Leave</span>
                </div>
            </div>

            <!-- Attendance Log Table -->
            <div class="glass-panel rounded-xl overflow-hidden border border-dark-border">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs text-gray-500 uppercase bg-dark-bg/50 border-b border-dark-border">
                            <tr>
                                <th class="p-4 font-medium">Date</th>
                                <th class="p-4 font-medium">Subject</th>
                                <th class="p-4 font-medium">Teacher</th>
                                <th class="p-4 font-medium text-center">Status</th>
                                <th class="p-4 font-medium">Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceTableBody" class="text-gray-300">
                            <!-- Row 1: Present -->
                            <tr class="border-b border-dark-border hover:bg-white/5 transition-colors">
                                <td class="p-4 whitespace-nowrap font-medium text-white">Oct 23, 2023</td>
                                <td class="p-4">
                                    <span class="text-xs font-mono text-primary-400">CS101</span>
                                    <span class="block text-white">Intro to Programming</span>
                                </td>
                                <td class="p-4 text-gray-400">Prof. Sarah Johnson</td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-green-500/10 text-green-400 border border-green-500/20">Present</span>
                                </td>
                                <td class="p-4 text-gray-500">-</td>
                            </tr>

                            <!-- Row 2: Late -->
                            <tr class="border-b border-dark-border hover:bg-white/5 transition-colors">
                                <td class="p-4 whitespace-nowrap font-medium text-white">Oct 20, 2023</td>
                                <td class="p-4">
                                    <span class="text-xs font-mono text-primary-400">MATH202</span>
                                    <span class="block text-white">Calculus II</span>
                                </td>
                                <td class="p-4 text-gray-400">Prof. John Smith</td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">Late</span>
                                </td>
                                <td class="p-4 text-amber-400 text-xs">Arrived 7:15 AM</td>
                            </tr>

                            <!-- Row 3: Absent -->
                            <tr class="border-b border-dark-border hover:bg-white/5 transition-colors bg-red-500/5">
                                <td class="p-4 whitespace-nowrap font-medium text-white">Oct 18, 2023</td>
                                <td class="p-4">
                                    <span class="text-xs font-mono text-primary-400">CS101</span>
                                    <span class="block text-white">Intro to Programming</span>
                                </td>
                                <td class="p-4 text-gray-400">Prof. Sarah Johnson</td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-500/10 text-red-400 border border-red-500/20">Absent</span>
                                </td>
                                <td class="p-4 text-red-400 text-xs">Medical Leave</td>
                            </tr>

                            <!-- Row 4: Early Leave -->
                            <tr class="border-b border-dark-border hover:bg-white/5 transition-colors">
                                <td class="p-4 whitespace-nowrap font-medium text-white">Oct 16, 2023</td>
                                <td class="p-4">
                                    <span class="text-xs font-mono text-primary-400">IT101</span>
                                    <span class="block text-white">Networking</span>
                                </td>
                                <td class="p-4 text-gray-400">Prof. Alex Reyes</td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-orange-500/10 text-orange-400 border border-orange-500/20">Early Leave</span>
                                </td>
                                <td class="p-4 text-orange-400 text-xs">Family Emergency</td>
                            </tr>

                            <!-- Row 5: Present -->
                            <tr class="border-b border-dark-border hover:bg-white/5 transition-colors">
                                <td class="p-4 whitespace-nowrap font-medium text-white">Oct 15, 2023</td>
                                <td class="p-4">
                                    <span class="text-xs font-mono text-primary-400">MATH202</span>
                                    <span class="block text-white">Calculus II</span>
                                </td>
                                <td class="p-4 text-gray-400">Prof. John Smith</td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-green-500/10 text-green-400 border border-green-500/20">Present</span>
                                </td>
                                <td class="p-4 text-gray-500">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t border-dark-border flex justify-between items-center">
                    <span class="text-sm text-gray-500">Showing 1 to 5 of 55 records</span>
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
        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();

            // Search filter
            const searchInput = document.getElementById('attendanceSearchInput');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const q = e.target.value.trim().toLowerCase();
                    document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(q) ? '' : 'none';
                    });
                });
            }

            // Mobile search toggle
            const mobileBtn = document.getElementById('attMobileSearchBtn');
            if (mobileBtn && searchInput) {
                mobileBtn.onclick = () => {
                    const parent = searchInput.closest('.relative');
                    parent.classList.toggle('hidden');
                    if (!parent.classList.contains('hidden')) searchInput.focus();
                };
            }
        });
    </script>
    <script type="module" src="student_auth.js"></script>
</body>
</html>