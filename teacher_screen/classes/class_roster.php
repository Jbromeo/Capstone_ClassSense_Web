<!-- teacher_screen/classes/class_roster.php -->
<div id="tab-students" class="tab-content transition-all animate-fade-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-primary-500/10 rounded-lg text-primary-500"><i data-feather="users" class="w-5 h-5"></i></div>
            <div>
                <h2 class="text-xl font-black text-white italic uppercase tracking-tighter">Student Roster</h2>
                <p id="rosterCountTop" class="text-[9px] text-gray-500 font-bold uppercase tracking-widest italic tracking-tighter opacity-70">Loading Class Data...</p>
            </div>
        </div>
        <div class="flex gap-3">
            <div class="relative group">
                <i data-feather="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i>
                <input type="text" placeholder="Filter Roster..." class="bg-dark-bg border border-dark-border rounded-xl pl-10 pr-4 py-2.5 text-xs text-white font-bold focus:ring-1 focus:ring-primary-500 outline-none w-48 transition-all placeholder:italic placeholder:uppercase placeholder:opacity-30">
            </div>
            <button onclick="openModal('addStudentModal')" class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all shadow-lg shadow-primary-500/20 active:scale-95">
                <i data-feather="user-plus" class="w-4 h-4"></i> Add Students
            </button>
        </div>
    </div>

    <!-- Roster Grid -->
    <div class="glass-panel rounded-2xl overflow-hidden border border-white/5 shadow-2xl bg-dark-bg/40">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-white/5 border-b border-white/5">
                        <th class="p-5 text-gray-400 font-bold uppercase tracking-[0.2em] text-[9px] italic opacity-60 w-16 text-center">Pos</th>
                        <th class="p-5 text-gray-400 font-bold uppercase tracking-[0.2em] text-[9px] italic opacity-60">Identity & Credentials</th>
                        <th class="p-5 text-gray-400 font-bold uppercase tracking-[0.2em] text-[9px] italic opacity-60">Official ID</th>
                        <th class="p-5 text-gray-400 font-bold uppercase tracking-[0.2em] text-[9px] italic opacity-60 text-center">Hub Controls</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <!-- Dynamic Students will be injected here -->
                    <tr>
                        <td colspan="4" class="p-32 text-center">
                            <div class="flex flex-col items-center gap-6 animate-pulse opacity-40">
                                <i data-feather="loader" class="w-12 h-12 text-primary-500 animate-spin"></i>
                                <p class="text-[10px] font-black uppercase tracking-widest italic tracking-tighter">Locating Class Entities...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Roster Footer -->
        <div class="p-4 px-8 border-t border-white/5 flex justify-between items-center bg-white/5">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                <span id="rosterCount" class="text-[9px] font-black uppercase tracking-widest italic text-gray-500">Connecting to Hub...</span>
            </div>
            <div class="flex gap-2" id="pagination">
                <button class="p-2 border border-white/5 rounded-lg text-gray-600 hover:text-white transition-colors cursor-not-allowed"><i data-feather="chevron-left" class="w-4 h-4"></i></button>
                <div class="flex items-center px-4 bg-white/5 rounded-lg text-[10px] font-black text-white italic">PAGE 01</div>
                <button class="p-2 border border-white/5 rounded-lg text-gray-600 hover:text-white transition-colors cursor-not-allowed"><i data-feather="chevron-right" class="w-4 h-4"></i></button>
            </div>
        </div>
    </div>
</div>
