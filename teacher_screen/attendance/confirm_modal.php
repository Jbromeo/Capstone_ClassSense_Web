<!-- Leave Live Session Confirm Modal -->
    <div id="csConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="absolute inset-0 bg-dark-bg/40 backdrop-blur-md"></div>
        <div class="glass-panel w-full max-w-sm rounded-[2.5rem] p-10 border border-white/10 shadow-2xl transform scale-95 transition-transform duration-300 relative z-10 text-center">
            <div id="csConfirmIconWrap" class="w-16 h-16 bg-primary-500/10 rounded-full flex items-center justify-center mx-auto mb-6 border border-primary-500/10">
                <i data-feather="arrow-left" class="w-8 h-8 text-primary-500"></i>
            </div>
            <h3 id="csConfirmTitle" class="text-2xl font-black text-white italic mb-2 tracking-tight">Leave Live Session?</h3>
            <p id="csConfirmMessage" class="text-gray-400 text-sm mb-10 leading-relaxed font-bold italic opacity-80 uppercase tracking-widest text-[10px]">The session stays active.</p>
            <div class="grid grid-cols-2 gap-4">
                <button id="csConfirmCancel" class="w-full py-4 bg-white/5 hover:bg-white/10 rounded-2xl font-black text-gray-500 hover:text-white transition-all text-xs uppercase tracking-widest leading-none">
                    Cancel
                </button>
                <button id="csConfirmOk" class="w-full py-4 bg-primary-500 hover:bg-primary-600 rounded-2xl font-black text-white transition-all shadow-lg shadow-primary-500/20 uppercase tracking-[0.2em] italic text-xs leading-none">
                    Leave
                </button>
            </div>
        </div>
    </div>

    <style>
        #csConfirmModal.show { opacity: 1; }
        #csConfirmModal.show > div:last-child { transform: scale(1); }
        #csConfirmModal.danger #csConfirmIconWrap { background: rgba(220, 38, 38, 0.1); border-color: rgba(220, 38, 38, 0.1); }
        #csConfirmModal.danger #csConfirmIconWrap i { color: #ef4444; }
        #csConfirmModal.danger #csConfirmOk { background: #dc2626; }
        #csConfirmModal.danger #csConfirmOk:hover { background: #b91c1c; }
    </style>