<!-- admin_screen/admin_dashboard.php -->
<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense Admin | Control Center</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-purple-500 selection:text-white bg-dark-bg">

    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-[25%] w-[600px] h-[600px] bg-purple-600/5 rounded-full mix-blend-screen filter blur-3xl animate-blob-slow transform -translate-y-1/2"></div>
    </div>

    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <header class="h-20 glass-panel border-b border-white/5 flex items-center justify-between px-8 z-20">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-black text-white tracking-tighter uppercase leading-none">Control Center <span class="text-[10px] text-purple-400 font-bold ml-4 uppercase tracking-[0.2em] opacity-60">System Core</span></h2>
            </div>
            
            <div class="flex items-center gap-4">
                 <div class="px-4 py-1.5 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-400 text-[9px] font-black uppercase tracking-widest italic animate-pulse">
                    Live System Monitor
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <!-- Stat Cards -->
                <div class="glass-panel p-6 rounded-2xl border border-white/5 relative group hover:border-purple-500/30 transition-all">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/5 rounded-full -mr-10 -mt-10 blur-xl group-hover:bg-purple-500/10 transition-colors"></div>
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 italic">Faculty Registry</p>
                    <h3 id="statTeachers" class="text-4xl font-black text-white italic tracking-tighter leading-none uppercase">---</h3>
                    <div class="mt-4 flex items-center text-[10px] text-purple-400 font-bold uppercase tracking-widest italic">
                        Verified Professors
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl border border-white/5 relative group hover:border-blue-500/30 transition-all">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full -mr-10 -mt-10 blur-xl group-hover:bg-blue-500/10 transition-colors"></div>
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 italic">Student Population</p>
                    <h3 id="statStudents" class="text-4xl font-black text-white italic tracking-tighter leading-none uppercase">---</h3>
                    <div class="mt-4 flex items-center text-[10px] text-blue-400 font-bold uppercase tracking-widest italic">
                        Enrolled Accounts
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl border border-white/5 relative group hover:border-green-500/30 transition-all">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-green-500/5 rounded-full -mr-10 -mt-10 blur-xl group-hover:bg-green-500/10 transition-colors"></div>
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 italic">Active Modules</p>
                    <h3 id="statClasses" class="text-4xl font-black text-white italic tracking-tighter leading-none uppercase">---</h3>
                    <div class="mt-4 flex items-center text-[10px] text-green-400 font-bold uppercase tracking-widest italic">
                        Live Courses
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl border border-white/5 relative group hover:border-primary-500/30 transition-all">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-primary-500/5 rounded-full -mr-10 -mt-10 blur-xl group-hover:bg-primary-500/10 transition-colors"></div>
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 italic">Total Enrollments</p>
                    <h3 id="statEnrollments" class="text-4xl font-black text-white italic tracking-tighter leading-none uppercase">---</h3>
                    <div class="mt-4 flex items-center text-[10px] text-primary-400 font-bold uppercase tracking-widest italic">
                        Student Slots Filled
                    </div>
                </div>
            </div>

            <!-- Dashboard Analytics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="glass-panel rounded-2xl p-8 border border-white/5 group hover:border-purple-500/30 transition-all">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-purple-500/10 rounded-xl flex items-center justify-center ring-1 ring-purple-500/20">
                            <i data-feather="user-plus" class="w-5 h-5 text-purple-500"></i>
                        </div>
                        <h4 class="text-sm font-black text-white uppercase tracking-widest italic">Recent Teachers</h4>
                    </div>
                    <div id="recentTeachersList" class="space-y-3">
                        <p class="text-[11px] text-gray-500 italic">Loading...</p>
                    </div>
                    <a href="manage_teachers.php" class="mt-4 inline-flex items-center gap-2 text-[10px] font-black text-purple-400 uppercase tracking-widest hover:text-white transition-colors">
                        View All <i data-feather="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>

                <div class="glass-panel rounded-2xl p-8 border border-white/5 group hover:border-blue-500/30 transition-all">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center ring-1 ring-blue-500/20">
                            <i data-feather="link" class="w-5 h-5 text-blue-500"></i>
                        </div>
                        <h4 class="text-sm font-black text-white uppercase tracking-widest italic">Quick Links</h4>
                    </div>
                    <div class="space-y-2">
                        <a href="manage_teachers.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/5 hover:bg-purple-500/10 border border-white/5 hover:border-purple-500/30 transition-all group/link">
                            <i data-feather="users" class="w-4 h-4 text-purple-400"></i>
                            <span class="text-xs font-bold text-gray-300 group-hover/link:text-white transition-colors">Manage Teachers</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- LOGIC -->
    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';

        async function updateStats() {
            try {
                const data = await api('/admin_stats.php');
                document.getElementById('statTeachers').textContent = String(data.teachers).padStart(2, '0');
                document.getElementById('statStudents').textContent = String(data.students).padStart(2, '0');
                document.getElementById('statClasses').textContent = String(data.classes).padStart(2, '0');
                document.getElementById('statEnrollments').textContent = String(data.enrollments).padStart(2, '0');

                // Recent Teachers
                const list = document.getElementById('recentTeachersList');
                if (data.recent_teachers?.length) {
                    list.innerHTML = data.recent_teachers.map(t => `
                        <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-white/5 border border-white/5">
                            <div class="w-8 h-8 rounded-lg bg-purple-600/20 text-purple-400 flex items-center justify-center font-bold text-xs border border-purple-500/20 uppercase">${(t.firstName || '')[0] || 'T'}</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-white truncate uppercase tracking-tight">${t.firstName || ''} ${t.lastName || ''}</p>
                                <p class="text-[9px] text-gray-500 truncate">${t.email || ''}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = '<p class="text-[11px] text-gray-500 italic">No teachers registered yet</p>';
                }
            } catch (err) {
                console.warn("Stats Sync Failure:", err);
            }
        }

        initPage(() => {
            updateStats();
            setInterval(updateStats, 30000);
        });

        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
    </script>
</body>
</html>
