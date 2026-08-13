<!-- VIEW 2: Live Attendance (Hidden by default) -->
            <div id="liveAttendanceView" class="hidden h-full flex flex-col">
                <!-- Top Bar -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <button onclick="backToClassSelection()" class="p-2 bg-dark-bg hover:bg-white/10 rounded-lg text-gray-400 hover:text-white transition-colors">
                            <i data-feather="arrow-left" class="w-5 h-5"></i>
                        </button>
                        <div>
                            <h2 id="liveClassName" class="text-xl font-bold text-white">CS101: Intro to Programming</h2>
                            <div class="flex items-center gap-4">
                                <p class="text-xs text-green-400 flex items-center gap-1">
                                    <span id="liveModeDot" class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                    <span id="liveModeLabel">Live Session Active</span>
                                </p>
                                <div id="sessionCountdown" class="hidden flex items-center gap-2 px-3 py-1 bg-primary-500/10 border border-primary-500/20 rounded-full">
                                    <i data-feather="clock" class="w-3 h-3 text-primary-500"></i>
                                    <span id="timerValue" class="text-[10px] font-black text-white italic tracking-widest">--:--</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button id="lateWindowBtn" onclick="startLateWindow()" class="flex items-center gap-2 px-4 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-lg transition-colors">
                            <i data-feather="clock" class="w-4 h-4"></i>
                            LATE WINDOW
                        </button>
                        <button onclick="confirmEndSession()" class="flex items-center gap-2 px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 rounded-lg transition-colors">
                            <i data-feather="stop-circle" class="w-4 h-4"></i>
                            End Session
                        </button>
                    </div>
                </div>

                <!-- Live Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 h-full pb-8">
                    
                    <!-- Left Column: QR Code & Stats -->
                    <div class="glass-panel rounded-2xl p-8 flex flex-col items-center justify-between relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-primary-500 to-transparent opacity-50"></div>
                        
                        <h3 id="liveModeTitle" class="text-lg font-bold text-white">Scan to Join</h3>
                        
                        <!-- QR Code Container -->
                        <div class="relative w-48 h-48 bg-white rounded-2xl p-4 shadow-2xl shadow-primary-500/20 flex items-center justify-center">
                            <div id="qrcode" class="w-full h-full flex items-center justify-center overflow-hidden">
                                <div class="w-full h-full qr-placeholder opacity-50 flex items-center justify-center text-gray-800 text-[10px] font-black uppercase text-center italic">Generating Secure Hub...</div>
                            </div>
                            <div id="scanLine" class="absolute top-0 left-0 w-full h-1 bg-red-500/50 shadow-[0_0_15px_rgba(220,38,38,0.8)] animate-scan-line hidden"></div>
                            <div class="absolute top-2 left-2 w-6 h-6 border-t-4 border-l-4 border-gray-800 rounded-tl-lg"></div>
                            <div class="absolute top-2 right-2 w-6 h-6 border-t-4 border-r-4 border-gray-800 rounded-tr-lg"></div>
                            <div class="absolute bottom-2 left-2 w-6 h-6 border-b-4 border-l-4 border-gray-800 rounded-bl-lg"></div>
                            <div class="absolute bottom-2 right-2 w-6 h-6 border-b-4 border-r-4 border-gray-800 rounded-br-lg"></div>
                        </div>

                        <p class="text-center text-sm text-gray-400">
                            Students must scan this code via the ClassSense Mobile App to verify their identity.
                        </p>

                        <!-- Mini Stats -->
                        <div class="w-full grid grid-cols-2 gap-4">
                            <div class="bg-dark-bg rounded-lg p-4 text-center">
                                <div id="presentCount" class="text-3xl font-bold text-green-400">0</div>
                                <div class="text-[10px] uppercase text-gray-500 font-bold tracking-widest">Present</div>
                            </div>
                            <div class="bg-dark-bg rounded-lg p-4 text-center">
                                <div id="totalCount" class="text-3xl font-bold text-gray-300">0</div>
                                <div class="text-[10px] uppercase text-gray-500 font-bold tracking-widest">Total</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Spotlight / Idle List -->
                    <div class="lg:col-span-2 glass-panel rounded-2xl h-full flex flex-col relative border border-dark-border bg-gradient-to-b from-dark-surface to-dark-bg overflow-hidden">
                        
                        <!-- 1. Idle Empty State (Shown when 0 students & no active scan) -->
                        <div id="idleEmptyState" class="absolute inset-0 flex flex-col items-center justify-center text-center opacity-40 animate-pulse z-10">
                            <i data-feather="user-plus" class="w-16 h-16 mx-auto mb-4 text-gray-500"></i>
                            <h3 class="text-xl font-bold text-gray-400">Waiting for scan...</h3>
                            <p class="text-sm text-gray-600 mt-2">Live feed will appear here</p>
                        </div>
                        
                        <!-- 2. Idle List State (Shown when > 0 students & no active scan) -->
                        <div id="idleListState" class="hidden absolute inset-0 flex flex-col z-10 p-6">
                            <div class="flex items-center justify-between mb-4 pb-2 border-b border-dark-border">
                                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider">Class List</h3>
                                <span class="text-xs text-primary-400 animate-pulse">Waiting for next scan...</span>
                            </div>
                            <div id="liveRosterList" class="flex-1 overflow-y-auto space-y-3 pr-2">
                                <!-- Populated by JS -->
                            </div>
                        </div>

                        <!-- 3. Spotlight Content (Shown when scanning) -->
                        <div id="spotlightContent" class="hidden absolute inset-0 flex flex-col items-center justify-center z-20 p-8 bg-dark-surface/95 backdrop-blur-sm">
                            
                            <!-- Large Avatar Container -->
                            <div class="relative mb-8">
                                <div class="w-64 h-64 rounded-full overflow-hidden border-4 border-dark-bg shadow-2xl shadow-primary-500/20 relative">
                                    <img id="spotlightAvatar" src="" class="w-full h-full object-cover">
                                    
                                    <!-- Overlay Vignette -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                                </div>

                                <!-- VERIFIED BADGE -->
                                <div class="absolute -bottom-2 right-4 bg-green-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg animate-bounce flex items-center gap-2">
                                    <i data-feather="check" class="w-3 h-3"></i> VERIFIED
                                </div>
                            </div>

                            <!-- Text Info -->
                            <h2 id="spotlightName" class="text-5xl font-bold text-white mb-2 tracking-tight drop-shadow-lg">Student Name</h2>
                            <p id="spotlightTime" class="text-xl text-gray-300 font-light mb-8">Scanned at 00:00</p>

                            <!-- Countdown Bar -->
                            <div class="w-full max-w-2xl bg-dark-bg h-3 rounded-full overflow-hidden border border-white/5">
                                <div id="timerBar" class="h-full bg-gradient-to-r from-primary-600 to-primary-400 animate-timer-shrink shadow-[0_0_10px_rgba(234,38,40,0.5)]"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>