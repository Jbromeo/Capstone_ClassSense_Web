<!-- teacher_screen/tabs/tab_grading.php -->
<div id="tab-grading" class="tab-content hidden h-full flex flex-col transition-all animate-fade-in animate-scale-in">
    
    <!-- Advanced Controls -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-primary-500/10 rounded-lg text-primary-500 shadow-lg shadow-primary-500/20"><i data-feather="monitor" class="w-5 h-5 animate-pulse"></i></div>
            <div>
                <h2 class="text-xl font-black text-white uppercase italic tracking-tighter">Grading Engine</h2>
                <div class="flex items-center gap-2 mt-1 opacity-70">
                    <span class="w-2 h-2 rounded-full bg-green-500 border border-dark-bg animate-pulse"></span>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest italic tracking-tighter">SHS Optimized Handshake (30/50/20 Split)</p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-2 bg-dark-bg/80 backdrop-blur-xl border border-white/5 p-1 rounded-2xl shadow-xl">
            <button onclick="setQuarter('Q1')" id="q-Q1" class="quarter-btn active px-5 py-2.5 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all bg-primary-600 text-white shadow-lg shadow-primary-500/20">1st</button>
            <button onclick="setQuarter('Q2')" id="q-Q2" class="quarter-btn px-5 py-2.5 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all text-gray-500 hover:text-white hover:bg-white/5">2nd</button>
            <button onclick="setQuarter('Q3')" id="q-Q3" class="quarter-btn px-5 py-2.5 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all text-gray-500 hover:text-white hover:bg-white/5">3rd</button>
            <button onclick="setQuarter('Q4')" id="q-Q4" class="quarter-btn px-5 py-2.5 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all text-gray-500 hover:text-white hover:bg-white/5">4th</button>
        </div>

        <div class="flex gap-2">
            <button onclick="exportToExcel()" class="flex items-center group gap-3 bg-dark-bg hover:bg-white/5 border border-white/5 text-gray-300 px-6 py-2.5 rounded-2xl text-[10px] font-black transition-all uppercase italic tracking-[0.2em] shadow-xl">
                <i data-feather="download" class="w-4 h-4 text-green-500 group-hover:scale-125 transition-transform"></i> Excel Export
            </button>
            <button onclick="openModal('weightConfigModal')" class="flex items-center group gap-3 bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-2xl text-[10px] font-black transition-all shadow-xl shadow-primary-500/20 uppercase italic tracking-[0.2em] active:scale-95">
                <i data-feather="settings" class="w-4 h-4 group-hover:rotate-180 transition-transform duration-700"></i> Weights
            </button>
        </div>
    </div>

    <!-- Master Spreadsheet Container -->
    <div class="flex-1 min-h-0 bg-dark-bg/80 backdrop-blur-2xl border border-white/5 rounded-3xl overflow-hidden relative shadow-2xl flex flex-col group/table">
        
        <!-- Spreadsheet Meta Bar -->
        <div class="px-8 py-4 border-b border-white/5 bg-white/5 flex items-center justify-between z-10 shrink-0">
            <div class="flex items-center gap-10">
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.5)]"></div>
                    <div>
                        <p class="text-[10px] font-black text-blue-400 uppercase italic tracking-tighter leading-none">Written Works</p>
                        <p class="text-[8px] text-gray-500 font-bold uppercase mt-1">30% Weight Distribution</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-purple-500 shadow-[0_0_10px_rgba(168,85,247,0.5)]"></div>
                    <div>
                        <p class="text-[10px] font-black text-purple-400 uppercase italic tracking-tighter leading-none">Performance Tasks</p>
                        <p class="text-[8px] text-gray-500 font-bold uppercase mt-1">50% Weight Distribution</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.5)]"></div>
                    <div>
                        <p class="text-[10px] font-black text-green-400 uppercase italic tracking-tighter leading-none">Quarterly Exam</p>
                        <p class="text-[8px] text-gray-500 font-bold uppercase mt-1">20% Weight Distribution</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4 text-[9px] font-black uppercase tracking-[0.2em] italic text-gray-600 transition-opacity group-hover/table:opacity-100 opacity-40">
                <span class="animate-pulse flex items-center gap-1"><i data-feather="terminal" class="w-3 h-3"></i> Syncing Engine</span>
            </div>
        </div>
        
        <!-- Table Scroll Area -->
        <div id="gradingTableContainer" class="flex-1 overflow-auto custom-scrollbar relative bg-[#0a0c10]/40">
            <!-- Dynamic Grading System Sheet Injected Here -->
            <div class="h-full flex flex-col items-center justify-center opacity-30 gap-6">
                <div class="relative">
                    <i data-feather="cpu" class="w-20 h-20 text-primary-500 animate-pulse"></i>
                    <div class="absolute -inset-4 bg-primary-600/10 blur-2xl rounded-full animate-ping opacity-20"></div>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.3em] italic tracking-tighter text-white">Initializing Hybrid Spreadsheet Protocol...</p>
                    <p class="text-[8px] font-bold text-gray-500 uppercase mt-2 text-center tracking-widest italic">Authenticating with Cloud Architecture</p>
                </div>
            </div>
        </div>
    </div>
</div>
