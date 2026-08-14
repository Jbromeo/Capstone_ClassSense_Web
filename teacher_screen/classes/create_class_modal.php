<!-- teacher_screen/classes/create_class_modal.php -->
    <!-- Create Class Modal -->
    <div id="createClassModal" class="modal fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="glass-panel w-full max-w-lg rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 border border-dark-border overflow-hidden" id="modalContent">
                
                <!-- STATE 1: FORM -->
                <div class="modal-form-state">
                    <div class="p-6 border-b border-dark-border flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-white">Create New Class</h3>
                            <p class="text-xs text-gray-500 mt-1">Fill details to generate a class code.</p>
                        </div>
                        <button onclick="closeModal()" class="p-2 text-gray-500 hover:text-white hover:bg-white/10 rounded-full transition-colors"><i data-feather="x" class="w-5 h-5"></i></button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">Class Name</label>
                            <input type="text" id="classNameInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition-all" placeholder="e.g. CS102: Advanced Python">
                        </div>
                        
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Level</label>
                                <select id="levelInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none italic font-medium">
                                    <option>Junior High School</option>
                                    <option selected>Senior High School</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Subject</label>
                                    <select id="subjectInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none italic font-medium"><option>Computer Science</option><option>Mathematics</option><option>Physics</option></select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Section Name</label>
                                    <input id="sectionNameInput" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none italic font-medium" placeholder="e.g. STEM 11-A">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Schedule Days</label>
                                    <div id="daySelector" class="flex gap-1.5 pt-1">
                                        <button type="button" data-day="M" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">M</button>
                                        <button type="button" data-day="T" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">T</button>
                                        <button type="button" data-day="W" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">W</button>
                                        <button type="button" data-day="TH" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">TH</button>
                                        <button type="button" data-day="F" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">F</button>
                                        <button type="button" data-day="S" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">S</button>
                                    </div>
                                    <input type="hidden" id="scheduleDaysInput">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Time Slot</label>
                                    <div class="flex items-center gap-2">
                                        <input type="time" id="startTimeInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-2 py-2 text-xs text-white focus:ring-2 focus:ring-primary-500 outline-none">
                                        <span class="text-gray-600 font-bold">-</span>
                                        <input type="time" id="endTimeInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-2 py-2 text-xs text-white focus:ring-2 focus:ring-primary-500 outline-none">
                                    </div>
                                </div>
                            </div>

                    </div>
                    <div class="p-6 bg-dark-bg/50 border-t border-dark-border flex justify-end gap-3">
                        <button onclick="closeModal()" class="px-5 py-2.5 rounded-lg text-gray-300 hover:text-white hover:bg-white/5 transition-colors text-sm font-medium">Cancel</button>
                        <button onclick="handleCreateClass()" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold shadow-lg shadow-primary-500/20 transition-all transform hover:scale-105">
                            Generate Code
                        </button>
                    </div>
                </div>

                <!-- STATE 2: SUCCESS -->
                <div class="modal-success-state p-8 text-center">
                    <div class="w-16 h-16 rounded-full bg-green-500/10 border border-green-500/20 flex items-center justify-center mx-auto mb-5">
                        <i data-feather="check-circle" class="w-8 h-8 text-green-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Class Created Successfully!</h3>
                    <p class="text-sm text-gray-400 mb-6">Share the code below with your students to let them join.</p>
                    <div class="bg-dark-bg border border-dark-border rounded-xl p-5 flex items-center justify-between mb-6 code-animate">
                        <div class="text-left">
                            <span class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Class Code</span>
                            <span id="generatedCodeDisplay" class="text-3xl font-mono font-bold text-white tracking-widest">XJZ-909</span>
                        </div>
                        <button onclick="copyGeneratedCode()" class="p-3 bg-primary-500/10 text-primary-400 rounded-lg hover:bg-primary-500/20 transition-colors border border-primary-500/20">
                            <i data-feather="clipboard" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <button onclick="closeModal()" class="w-full py-3 bg-dark-surface hover:bg-white/5 border border-dark-border rounded-xl text-white font-medium transition-colors">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>