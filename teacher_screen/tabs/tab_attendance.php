<!-- teacher_screen/tabs/tab_attendance.php -->
<div id="tab-attendance" class="tab-content hidden h-full flex flex-col transition-all animate-fade-in animate-scale-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-primary-500/10 rounded-lg text-primary-500 shadow-lg shadow-primary-500/20"><i data-feather="calendar" class="w-5 h-5"></i></div>
            <div>
                <h2 class="text-xl font-black text-white italic uppercase tracking-tighter">Attendance History</h2>
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest italic tracking-tighter opacity-70">Real-time Presence Tracking</p>
            </div>
        </div>
        
        <div class="flex gap-2">
            <button class="flex items-center gap-3 bg-dark-bg hover:bg-white/5 border border-white/5 text-gray-300 px-6 py-2.5 rounded-2xl text-[10px] font-black uppercase italic tracking-[0.2em] shadow-xl transition-all active:scale-95">
                <i data-feather="file" class="w-4 h-4 text-primary-500"></i> Generation Report
            </button>
            <button onclick="window.showToast('Attendance logging coming soon!', 'info')" class="flex items-center group gap-3 bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-2xl text-[10px] font-black transition-all shadow-xl shadow-primary-500/20 uppercase italic tracking-[0.2em] active:scale-95">
                <i data-feather="plus-circle" class="w-4 h-4"></i> New Entry
            </button>
        </div>
    </div>

    <!-- Attendance Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-panel p-6 rounded-3xl border border-white/5 bg-white/5 shadow-2xl relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-125 transition-transform duration-700">
                <i data-feather="users" class="w-24 h-24 text-white"></i>
            </div>
            <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 italic">Average Presence</p>
            <p class="text-3xl font-black text-white italic tracking-tighter">94.2%</p>
            <div class="flex items-center gap-1 mt-2 text-[8px] font-bold text-green-500 uppercase italic">
                <i data-feather="trending-up" class="w-3 h-3"></i> +2.4% vs Last Month
            </div>
        </div>
        <div class="glass-panel p-6 rounded-3xl border border-white/5 bg-white/5 shadow-2xl relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-125 transition-transform duration-700">
                <i data-feather="clock" class="w-24 h-24 text-white"></i>
            </div>
            <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 italic">Tardy Frequency</p>
            <p class="text-3xl font-black text-white italic tracking-tighter">3.1%</p>
            <div class="flex items-center gap-1 mt-2 text-[8px] font-bold text-red-500 uppercase italic">
                <i data-feather="trending-down" class="w-3 h-3"></i> -0.8% Target Gap
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="flex-1 min-h-0 bg-dark-bg/80 backdrop-blur-2xl border border-white/5 rounded-3xl overflow-hidden relative shadow-2xl flex flex-col">
        <div class="h-full flex flex-col items-center justify-center opacity-30 gap-6">
            <i data-feather="package" class="w-16 h-16 text-gray-400"></i>
            <div class="text-center">
                <p class="text-[10px] font-black uppercase tracking-[0.3em] italic tracking-tighter text-white">Archive Under Construction</p>
                <p class="text-[8px] font-bold text-gray-500 uppercase mt-2 italic tracking-widest leading-relaxed">System logs for this Hub are temporarily secured.<br>Biometrics integration pending.</p>
            </div>
        </div>
    </div>
</div>
