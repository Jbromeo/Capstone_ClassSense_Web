<!-- teacher_screen/classes/edit_class_modal.php -->
    <!-- Edit Class Modal -->
    <div id="editClassModal" class="modal fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity opacity-0" id="editModalBackdrop"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="glass-panel w-full max-w-lg rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 border border-dark-border overflow-hidden" id="editModalContent">
                <div class="p-6 border-b border-dark-border flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white">Edit Class Details</h3>
                        <p class="text-xs text-gray-500 mt-1">Changes will reflect for all enrolled students.</p>
                    </div>
                    <button onclick="closeEditModal()" class="p-2 text-gray-500 hover:text-white hover:bg-white/10 rounded-full transition-colors"><i data-feather="x" class="w-5 h-5"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" id="editClassId">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Class Name</label>
                        <input type="text" id="editClassNameInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Level</label>
                        <select id="editLevelInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none">
                            <option>Junior High School</option>
                            <option selected>Senior High School</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Section Name</label>
                        <input id="editSectionNameInput" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Schedule Days</label>
                            <div id="editDaySelector" class="flex gap-1.5 pt-1">
                                <button type="button" data-day="M" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">M</button>
                                <button type="button" data-day="T" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">T</button>
                                <button type="button" data-day="W" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">W</button>
                                <button type="button" data-day="TH" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">TH</button>
                                <button type="button" data-day="F" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">F</button>
                                <button type="button" data-day="S" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">S</button>
                            </div>
                            <input type="hidden" id="editScheduleDaysInput">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Time Slot</label>
                            <div class="flex items-center gap-2">
                                <input type="time" id="editStartTimeInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-2 py-2 text-xs text-white focus:ring-2 focus:ring-primary-500 outline-none">
                                <span class="text-gray-600 font-bold">-</span>
                                <input type="time" id="editEndTimeInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-2 py-2 text-xs text-white focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Status</label>
                        <select id="editStatusInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none italic font-medium">
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="p-6 bg-dark-bg/50 border-t border-dark-border flex justify-end gap-3">
                    <button onclick="closeEditModal()" class="px-5 py-2.5 rounded-lg text-gray-300 hover:text-white hover:bg-white/5 transition-colors text-sm font-medium">Cancel</button>
                    <button onclick="handleUpdateClass()" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold shadow-lg shadow-primary-500/20 transition-all transform hover:scale-105">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>