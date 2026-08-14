<!-- teacher_screen/classes/modals_class.php -->

<!-- MODAL: Add Component (Grading) -->
<div id="addComponentModal" class="fixed inset-0 z-50 hidden group-modal">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-xl transition-all duration-500" onclick="closeModal('addComponentModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="glass-panel w-full max-w-md rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 border border-gray-700 pointer-events-auto" id="addComponentModalContent">
            <div class="p-6 border-b border-white/5 flex justify-between items-center bg-white/5 rounded-t-2xl">
                <h3 class="text-xl font-bold text-white uppercase italic tracking-tighter" id="addCompModalTitle">Add Component</h3>
                <button onclick="closeModal('addComponentModal')" class="text-gray-400 hover:text-white transition-colors p-2"><i data-feather="x"></i></button>
            </div>
            <div class="p-6 space-y-6">
                <input type="hidden" id="addCompCategory">
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 italic">Entry Name</label>
                    <input type="text" id="addCompName" class="w-full bg-dark-bg border border-dark-border rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all font-bold placeholder:italic uppercase" placeholder="e.g. Midterm Quiz 1">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 italic">Highest Possible Score</label>
                    <input type="number" id="addCompHps" value="50" class="w-full bg-dark-bg border border-dark-border rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all font-black italic">
                </div>
            </div>
            <div class="p-6 border-t border-white/5 flex justify-end gap-3 bg-white/5 rounded-b-2xl">
                <button onclick="closeModal('addComponentModal')" class="px-6 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition-all text-[10px] font-black uppercase tracking-widest italic">Abort</button>
                <button onclick="window.saveNewComponent()" class="px-8 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-[10px] font-black uppercase tracking-widest italic shadow-lg shadow-primary-500/20 transition-all transform hover:scale-105">Deploy Entry</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Weight Config -->
<div id="weightConfigModal" class="fixed inset-0 z-50 hidden group-modal">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-xl transition-all duration-500" onclick="closeModal('weightConfigModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="glass-panel w-full max-w-sm rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 border border-gray-700 pointer-events-auto" id="weightConfigModalContent">
            <div class="p-6 border-b border-white/5 flex justify-between items-center bg-white/5 rounded-t-2xl">
                <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">Grading Weights</h3>
                <button onclick="closeModal('weightConfigModal')" class="text-gray-400 hover:text-white transition-colors p-2"><i data-feather="x"></i></button>
            </div>
            <div class="p-6 space-y-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4 py-2 border-b border-white/5">
                        <span class="text-[10px] font-black text-blue-400 uppercase italic tracking-widest">Written Works</span>
                        <div class="flex items-center gap-2">
                            <input type="number" id="weight-written" value="30" oninput="updateWeightTotal()" class="w-16 bg-dark-bg border border-dark-border rounded-lg p-2 text-center text-white font-black text-xs italic">
                            <span class="text-gray-500 font-bold">%</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-2 border-b border-white/5">
                        <span class="text-[10px] font-black text-purple-400 uppercase italic tracking-widest">Performance Tasks</span>
                        <div class="flex items-center gap-2">
                            <input type="number" id="weight-performance" value="50" oninput="updateWeightTotal()" class="w-16 bg-dark-bg border border-dark-border rounded-lg p-2 text-center text-white font-black text-xs italic">
                            <span class="text-gray-500 font-bold">%</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-2 border-b border-white/5">
                        <span class="text-[10px] font-black text-green-400 uppercase italic tracking-widest">Quarterly Exam</span>
                        <div class="flex items-center gap-2">
                            <input type="number" id="weight-exam" value="20" oninput="updateWeightTotal()" class="w-16 bg-dark-bg border border-dark-border rounded-lg p-2 text-center text-white font-black text-xs italic">
                            <span class="text-gray-500 font-bold">%</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-2">
                        <span class="text-[10px] font-black text-orange-400 uppercase italic tracking-widest">Attendance</span>
                        <div class="flex items-center gap-2">
                            <input type="number" id="weight-attendance" value="0" oninput="updateWeightTotal()" class="w-16 bg-dark-bg border border-dark-border rounded-lg p-2 text-center text-white font-black text-xs italic">
                            <span class="text-gray-500 font-bold">%</span>
                        </div>
                    </div>
                </div>
                <!-- Live Weight Total -->
                <div id="weightTotalBar" class="flex items-center gap-3 p-3 rounded-xl bg-dark-bg/60 border border-white/5">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[9px] font-black uppercase tracking-widest text-gray-500">Total</span>
                            <span id="weightTotalValue" class="text-xs font-black text-green-400">100%</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-dark-bg overflow-hidden">
                            <div id="weightTotalBarFill" class="h-full rounded-full bg-green-500 transition-all duration-300" style="width:100%"></div>
                        </div>
                    </div>
                    <span id="weightTotalIcon" class="flex-shrink-0 text-green-400"><i data-feather="check-circle" class="w-4 h-4"></i></span>
                </div>
            </div>
            <div class="p-6 border-t border-white/5 flex justify-end gap-3 bg-white/5 rounded-b-2xl">
                <button onclick="window.saveWeights()" class="w-full py-3 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-[10px] font-black uppercase tracking-widest italic shadow-lg shadow-primary-500/20 transition-all active:scale-95">Update Weights</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Add Student (Roster) -->
<div id="addStudentModal" class="fixed inset-0 z-50 hidden group-modal">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-xl transition-all duration-500" onclick="closeModal('addStudentModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="glass-panel w-full max-w-lg rounded-3xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 border border-gray-700 pointer-events-auto" id="addStudentModalContent">
            <div class="p-6 border-b border-white/5 flex justify-between items-center bg-white/5 rounded-t-3xl">
                <h3 class="text-xl font-black text-white uppercase italic tracking-tighter">Class Code</h3>
                <button onclick="closeModal('addStudentModal')" class="text-gray-400 hover:text-white transition-colors p-2"><i data-feather="x"></i></button>
            </div>
            <div class="p-6 space-y-6">
                <div class="p-4 bg-primary-500/10 border border-primary-500/20 rounded-2xl">
                    <p class="text-[9px] font-black text-primary-500 uppercase tracking-widest italic mb-2">Class Code</p>
                    <div class="flex items-center justify-between">
                        <span id="displayClassCode" class="text-3xl font-black text-white italic tracking-[0.2em] uppercase">XXXX-XX</span>
                        <button onclick="navigator.clipboard.writeText(document.getElementById('displayClassCode').innerText); window.showToast('Code Copied!', 'success')" class="p-2.5 bg-white/5 rounded-xl hover:bg-white/10 transition-all border border-white/5">
                            <i data-feather="copy" class="w-4 h-4 text-gray-400"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-white/5 flex justify-end bg-white/5 rounded-b-3xl">
                <button onclick="closeModal('addStudentModal')" class="px-8 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-[10px] font-black uppercase tracking-widest italic shadow-lg shadow-primary-500/20 transition-all active:scale-95">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
window.updateWeightTotal = function() {
    const w = parseInt(document.getElementById('weight-written')?.value || 0);
    const p = parseInt(document.getElementById('weight-performance')?.value || 0);
    const e = parseInt(document.getElementById('weight-exam')?.value || 0);
    const a = parseInt(document.getElementById('weight-attendance')?.value || 0);
    const total = w + p + e + a;
    const bar = document.getElementById('weightTotalBarFill');
    const val = document.getElementById('weightTotalValue');
    const icon = document.getElementById('weightTotalIcon');
    if (bar) {
        bar.style.width = Math.min(total, 100) + '%';
        bar.className = 'h-full rounded-full ' + (total === 100 ? 'bg-green-500' : total > 100 ? 'bg-red-500' : 'bg-amber-500');
    }
    if (val) {
        val.textContent = total + '%';
        val.className = 'text-xs font-black ' + (total === 100 ? 'text-green-400' : total > 100 ? 'text-red-400' : 'text-amber-400');
    }
    if (icon) {
        icon.innerHTML = total === 100 ? '<i data-feather="check-circle" class="w-4 h-4"></i>' : '<i data-feather="alert-circle" class="w-4 h-4"></i>';
        try { feather.replace(); } catch(e) {}
    }
};

window.saveNewComponent = function() {
    const name = document.getElementById('addCompName')?.value?.trim();
    const hps = parseInt(document.getElementById('addCompHps')?.value || 50);
    const category = document.getElementById('addCompCategory')?.value;
    if (!name) return window.showToast('Enter a component name', 'error');
    if (!category) return window.showToast('Category not set', 'error');
    if (window.gradingSystem) {
        window.gradingSystem.addComponent(category, name, hps);
    }
    window.closeModal('addComponentModal');
    document.getElementById('addCompName').value = '';
    document.getElementById('addCompHps').value = '50';
};

window.saveWeights = function() {
    const weights = {
        written: parseInt(document.getElementById('weight-written')?.value || 0),
        performance: parseInt(document.getElementById('weight-performance')?.value || 0),
        exam: parseInt(document.getElementById('weight-exam')?.value || 0),
        attendance: parseInt(document.getElementById('weight-attendance')?.value || 0)
    };
    const total = Object.values(weights).reduce((a, b) => a + b, 0);
    if (total !== 100) return window.showToast(`Weights must total 100% (currently ${total}%)`, 'error');
    if (window.gradingSystem) window.gradingSystem.saveWeights(weights);
    window.closeModal('weightConfigModal');
    window.showToast('Weights updated successfully', 'success');
};
</script>