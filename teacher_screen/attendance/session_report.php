<!-- VIEW 3: Session Summary (Hidden by default) -->
            <div id="sessionSummaryView" class="hidden animate-fade-in-up pb-8">
                <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 text-gray-400 text-sm mb-1">
                            <span class="hover:text-white cursor-pointer" onclick="goToClassSelection({ finalize: false })">Classes</span>
                            <i data-feather="chevron-right" class="w-4 h-4"></i>
                            <span class="hover:text-white cursor-pointer" onclick="goToLiveView()">Live Session</span>
                            <i data-feather="chevron-right" class="w-4 h-4"></i>
                            <span class="text-white">Report</span>
                        </div>
                        <h1 class="text-3xl font-bold text-white">Session Report</h1>
                        <p id="reportClassTitle" class="text-gray-400">CS101: Intro to Programming</p>
                    </div>
                    
                    <div class="flex gap-3">
                         <div class="glass-panel px-6 py-3 rounded-xl flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-[10px] uppercase text-gray-500 font-bold tracking-wider">Present</p>
                                <p id="finalPresentCount" class="text-2xl font-bold text-green-400">0</p>
                            </div>
                             <div class="w-px h-8 bg-white/10"></div>
                             <div class="text-right">
                                <p class="text-[10px] uppercase text-gray-500 font-bold tracking-wider">Late</p>
                                <p id="finalLateCount" class="text-2xl font-bold text-amber-400">0</p>
                            </div>
                            <div class="w-px h-8 bg-white/10"></div>
                            <div class="text-right">
                                <p class="text-[10px] uppercase text-gray-500 font-bold tracking-wider">Absent</p>
                                <p id="finalAbsentCount" class="text-2xl font-bold text-red-400">0</p>
                            </div>
                            <div class="w-px h-8 bg-white/10"></div>
                            <div class="text-right">
                                <p class="text-[10px] uppercase text-gray-500 font-bold tracking-wider">Status</p>
                                <p class="text-xl font-bold text-white">Ended</p>
                            </div>
                         </div>
                         <button onclick="discardAllRecords()" class="px-6 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 rounded-xl transition-all shadow-lg flex items-center gap-2">
                            <i data-feather="trash-2"></i> Discard
                         </button>
                         <button id="recordDoneBtn" onclick="goToClassSelection()" class="px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-primary-500/25 flex items-center gap-2">
                            <i data-feather="check"></i> Done
                         </button>
                    </div>
                </div>

                <!-- Summary List -->
                <div class="glass-panel rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-dark-border text-xs uppercase text-gray-500 font-bold tracking-wider bg-dark-bg/30">
                                    <th class="p-4 pl-6">Student</th>
                                    <th class="p-4">ID</th>
                                    <th class="p-4">Scan Time</th>
                                    <th class="p-4">Distance</th>
                                    <th class="p-4">Status</th>
                                </tr>
                            </thead>
                            <tbody id="summaryTableBody" class="text-sm">
                                <!-- JS Populated -->
                            </tbody>
                        </table>
                    </div>
                </div>

                </div>

            <!-- Shared status picker popover (placed OUTSIDE sessionSummaryView so its
                 position:fixed is viewport-relative, not hijacked by the retained
                 translateY(0) transform from animate-fade-in-up) -->
            <div id="statusPicker" class="hidden fixed z-50 min-w-[150px] glass-panel rounded-xl p-1.5 shadow-2xl animate-fade-in">
                <div class="flex flex-col gap-1">
                    <button data-status="Present" class="status-option flex items-center gap-2.5 px-3 py-2 rounded-lg text-[11px] font-black uppercase tracking-widest italic text-left transition-colors hover:bg-green-500/10 text-green-400">
                        <i data-feather="check-circle" class="w-4 h-4"></i> Present
                        <i data-feather="check" class="status-check w-3.5 h-3.5 ml-auto hidden"></i>
                    </button>
                    <button data-status="Late" class="status-option flex items-center gap-2.5 px-3 py-2 rounded-lg text-[11px] font-black uppercase tracking-widest italic text-left transition-colors hover:bg-amber-500/10 text-amber-400">
                        <i data-feather="clock" class="w-4 h-4"></i> Late
                        <i data-feather="check" class="status-check w-3.5 h-3.5 ml-auto hidden"></i>
                    </button>
                    <button data-status="Absent" class="status-option flex items-center gap-2.5 px-3 py-2 rounded-lg text-[11px] font-black uppercase tracking-widest italic text-left transition-colors hover:bg-red-500/10 text-red-400">
                        <i data-feather="x-circle" class="w-4 h-4"></i> Absent
                        <i data-feather="check" class="status-check w-3.5 h-3.5 ml-auto hidden"></i>
                    </button>
                </div>
            </div>