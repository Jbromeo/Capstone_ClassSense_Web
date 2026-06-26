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
<body class="antialiased min-h-screen overflow-hidden flex selection:bg-purple-500 selection:text-white bg-dark-bg">

    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-[25%] w-[600px] h-[600px] bg-purple-600/5 rounded-full mix-blend-screen filter blur-3xl animate-blob-slow transform -translate-y-1/2"></div>
    </div>

    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <header class="h-20 glass-panel border-b border-white/5 flex items-center justify-between px-8 z-20">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-black text-white italic tracking-tighter uppercase leading-none">Control Center <span class="text-[10px] text-purple-400 font-bold ml-4 uppercase tracking-[0.2em] opacity-60">System Core</span></h2>
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
                    <h3 class="text-4xl font-black text-white italic tracking-tighter leading-none uppercase">24</h3>
                    <div class="mt-4 flex items-center text-[10px] text-green-400 font-bold uppercase tracking-widest italic">
                        Live Courses
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl border border-white/5 relative group hover:border-primary-500/30 transition-all">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-primary-500/5 rounded-full -mr-10 -mt-10 blur-xl group-hover:bg-primary-500/10 transition-colors"></div>
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 italic">Network Load</p>
                    <h3 class="text-4xl font-black text-white italic tracking-tighter leading-none uppercase">Low</h3>
                    <div class="mt-4 flex items-center text-[10px] text-primary-400 font-bold uppercase tracking-widest italic">
                        System Optimized
                    </div>
                </div>
            </div>

            <!-- Dashboard Analytics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="glass-panel rounded-2xl p-10 border border-white/5 flex flex-col justify-center items-center text-center group hover:border-purple-500/20 transition-all cursor-pointer">
                    <div class="w-20 h-20 bg-purple-500/5 rounded-full flex items-center justify-center mb-6 ring-1 ring-purple-500/20 group-hover:scale-110 transition-transform shadow-xl shadow-purple-500/10">
                        <i data-feather="pie-chart" class="w-8 h-8 text-purple-500"></i>
                    </div>
                    <h4 class="text-sm font-black text-white uppercase tracking-widest italic mb-2">Attendance Matrix</h4>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-[0.2em] italic opacity-60">Visual Database Analytics</p>
                </div>

                <div class="glass-panel rounded-2xl p-10 border border-white/5 flex flex-col justify-center items-center text-center group hover:border-blue-500/20 transition-all cursor-pointer">
                    <div class="w-20 h-20 bg-blue-500/5 rounded-full flex items-center justify-center mb-6 ring-1 ring-blue-500/20 group-hover:scale-110 transition-transform shadow-xl shadow-blue-500/10">
                        <i data-feather="activity" class="w-8 h-8 text-blue-500"></i>
                    </div>
                    <h4 class="text-sm font-black text-white uppercase tracking-widest italic mb-2">Live Activity Node</h4>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-[0.2em] italic opacity-60">Real-time Backend Stream</p>
                </div>
            </div>
        </main>
    </div>

    <!-- LOGIC -->
    <script type="module">
        import { db } from '../assets/js/firebase-init.js';
        import { getCountFromServer, collection } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

        async function updateStats() {
            try {
                const tSnap = await getCountFromServer(collection(db, "teachers"));
                const sSnap = await getCountFromServer(collection(db, "students"));
                document.getElementById('statTeachers').textContent = tSnap.data().count.toString().padStart(2, '0');
                document.getElementById('statStudents').textContent = sSnap.data().count.toString().padStart(2, '0');
            } catch (err) {
                console.warn("Stats Sync Failure:", err);
            }
        }
        updateStats();
        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
    </script>
</body>
</html>
