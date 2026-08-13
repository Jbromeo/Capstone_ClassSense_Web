<!-- teacher_screen/classes/confirm_modal_class.php -->
<!-- Generic Confirm Modal (promise-based) for the Class Hub -->

<div id="csConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <div class="absolute inset-0 bg-dark-bg/40 backdrop-blur-md"></div>
    <div class="glass-panel w-full max-w-sm rounded-[2.5rem] p-10 border border-white/10 shadow-2xl transform scale-95 transition-transform duration-300 relative z-10 text-center">
        <div id="csConfirmIconWrap" class="w-16 h-16 bg-primary-500/10 rounded-full flex items-center justify-center mx-auto mb-6 border border-primary-500/10">
            <i data-feather="alert-triangle" class="w-8 h-8 text-primary-500"></i>
        </div>
        <h3 id="csConfirmTitle" class="text-2xl font-black text-white italic mb-2 tracking-tight">Are you sure?</h3>
        <p id="csConfirmMessage" class="text-gray-400 text-sm mb-10 leading-relaxed font-bold italic opacity-80 uppercase tracking-widest text-[10px]">This action cannot be undone.</p>
        <div class="grid grid-cols-2 gap-4">
            <button id="csConfirmCancel" class="w-full py-4 bg-white/5 hover:bg-white/10 rounded-2xl font-black text-gray-500 hover:text-white transition-all text-xs uppercase tracking-widest leading-none">
                Cancel
            </button>
            <button id="csConfirmOk" class="w-full py-4 bg-primary-500 hover:bg-primary-600 rounded-2xl font-black text-white transition-all shadow-lg shadow-primary-500/20 uppercase tracking-[0.2em] italic text-xs leading-none">
                Confirm
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
    #csWeightsAlertModal.show { opacity: 1; }
    #csWeightsAlertModal.show > div:last-child { transform: scale(1); }
</style>

<!-- Weights-Required Alert Modal (shown when adding a component before weights are configured) -->
<div id="csWeightsAlertModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <div class="absolute inset-0 bg-dark-bg/40 backdrop-blur-md"></div>
    <div class="glass-panel w-full max-w-sm rounded-[2.5rem] p-10 border border-white/10 shadow-2xl transform scale-95 transition-transform duration-300 relative z-10 text-center">
        <div class="w-16 h-16 bg-amber-500/10 rounded-full flex items-center justify-center mx-auto mb-6 border border-amber-500/20">
            <i data-feather="sliders" class="w-8 h-8 text-amber-400"></i>
        </div>
        <h3 class="text-2xl font-black text-white italic mb-2 tracking-tight">Set Weights First</h3>
        <p class="text-gray-400 text-sm mb-10 leading-relaxed font-bold italic opacity-80 uppercase tracking-widest text-[10px]">Configure your grading weights (Written, Performance, Exams, Attendance) before adding components. Weights must total 100%.</p>
        <div class="grid grid-cols-2 gap-4">
            <button id="csWeightsAlertCancel" class="w-full py-4 bg-white/5 hover:bg-white/10 rounded-2xl font-black text-gray-500 hover:text-white transition-all text-xs uppercase tracking-widest leading-none">
                Not Now
            </button>
            <button id="csWeightsAlertOpen" class="w-full py-4 bg-primary-600 hover:bg-primary-700 rounded-2xl font-black text-white transition-all shadow-lg shadow-primary-500/20 uppercase tracking-[0.2em] italic text-xs leading-none">
                Open Weights
            </button>
        </div>
    </div>
</div>

<script>
window.csWeightsAlert = () => new Promise((resolve) => {
    const modal = document.getElementById('csWeightsAlertModal');
    if (!modal) { resolve(false); return; }
    try { feather.replace(); } catch(e) {}

    let settled = false;
    const done = (openWeights) => {
        if (settled) return;
        settled = true;
        modal.classList.remove('show');
        setTimeout(() => modal.classList.add('hidden'), 300);
        document.removeEventListener('keydown', onKey);
        resolve(openWeights);
    };
    const onKey = (e) => { if (e.key === 'Escape') done(false); };

    document.getElementById('csWeightsAlertCancel').onclick = () => done(false);
    document.getElementById('csWeightsAlertOpen').onclick = () => done(true);
    modal.querySelector('.absolute').onclick = () => done(false);
    document.addEventListener('keydown', onKey);

    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.add('show'), 10);
});
</script>

<script>
window.csConfirm = (opts = {}) => new Promise((resolve) => {
    const modal = document.getElementById('csConfirmModal');
    if (!modal) { resolve(true); return; }
    const titleEl = document.getElementById('csConfirmTitle');
    const msgEl = document.getElementById('csConfirmMessage');
    const okBtn = document.getElementById('csConfirmOk');
    const cancelBtn = document.getElementById('csConfirmCancel');

    titleEl.innerText = opts.title || 'Are you sure?';
    msgEl.innerText = opts.message || '';
    okBtn.innerText = opts.okText || 'Confirm';
    cancelBtn.innerText = opts.cancelText || 'Cancel';
    modal.classList.toggle('danger', !!opts.danger);
    try { feather.replace(); } catch(e) {}

    let settled = false;
    const done = (val) => {
        if (settled) return;
        settled = true;
        modal.classList.remove('show');
        setTimeout(() => modal.classList.add('hidden'), 300);
        document.removeEventListener('keydown', onKey);
        resolve(val);
    };
    const onKey = (e) => { if (e.key === 'Escape') done(false); };

    okBtn.onclick = () => done(true);
    cancelBtn.onclick = () => done(false);
    modal.querySelector('.absolute').onclick = () => done(false);
    document.addEventListener('keydown', onKey);

    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.add('show'), 10);
});
</script>
