<!-- VIEW 3: Session Summary (Hidden by default) -->
            <div id="sessionSummaryView" class="hidden animate-fade-in-up pb-8">
                <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 text-gray-400 text-sm mb-1">
                            <span class="hover:text-white cursor-pointer" onclick="goToClassSelection()">Classes</span>
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
                                <p class="text-[10px] uppercase text-gray-500 font-bold tracking-wider">Status</p>
                                <p class="text-xl font-bold text-white">Ended</p>
                            </div>
                         </div>
                         <button onclick="discardAllRecords()" class="px-6 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 rounded-xl transition-all shadow-lg flex items-center gap-2">
                            <i data-feather="trash-2"></i> Discard
                         </button>
                         <button onclick="goToClassSelection()" class="px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-primary-500/25 flex items-center gap-2">
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
                                    <th class="p-4">Status</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="summaryTableBody" class="text-sm">
                                <!-- JS Populated -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>