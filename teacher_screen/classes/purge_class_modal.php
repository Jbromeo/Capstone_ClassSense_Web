<!-- teacher_screen/classes/purge_class_modal.php -->
    <!-- Purge Class Confirmation Modal -->
    <div id="purgeClassModal" class="modal fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md transition-opacity opacity-0" id="purgeBackdrop"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="glass-panel w-full max-w-sm rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 border border-primary-500/20 overflow-hidden" id="purgeContent">
                <div class="p-8 text-center">
                    <div class="w-20 h-20 rounded-full bg-primary-500/10 border border-primary-500/20 flex items-center justify-center mx-auto mb-6">
                        <i data-feather="trash-2" class="w-10 h-10 text-primary-500 animate-pulse"></i>
                    </div>
                    <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter leading-none mb-3">Confirm Purge?</h3>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-widest leading-relaxed mb-8 italic opacity-60 px-4">
                        Permanently decommission <span id="purgeClassName" class="text-primary-400">this class</span>? This will remove all student records and cannot be undone.
                    </p>
                    
                    <div class="flex flex-col gap-3">
                        <button id="confirmPurgeBtn" class="w-full py-4 bg-primary-600 hover:bg-primary-700 text-white font-black uppercase tracking-widest italic text-[10px] rounded-xl shadow-lg shadow-primary-500/20 transition-all active:scale-95">
                            Purge Records
                        </button>
                        <button onclick="closePurgeModal()" class="w-full py-4 bg-dark-surface hover:bg-white/5 border border-dark-border text-gray-400 hover:text-white font-black uppercase tracking-widest italic text-[10px] rounded-xl transition-all">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>