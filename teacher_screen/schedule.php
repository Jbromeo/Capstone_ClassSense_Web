<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- schedule.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | Schedule</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
        
        .glass-panel { background: rgba(24, 27, 33, 0.8); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
        
        input[type="time"]::-webkit-calendar-picker-indicator,
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1); opacity: 0.5; cursor: pointer; transition: all 0.3s;
        }
        input[type="time"]::-webkit-calendar-picker-indicator:hover,
        input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 1; }
    </style>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-purple-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 4s"></div>
    </div>

    <div id="toastContainer" class="fixed top-5 right-5 z-[100] flex flex-col gap-3"></div>

    <?php include 'sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative border-l border-dark-border/50">
        
        <!-- Header -->
        <header class="h-20 glass-panel border-b border-dark-border/60 flex items-center justify-between px-6 z-20 shadow-xl shadow-black/20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                    <i data-feather="menu"></i>
                </button>
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex w-10 h-10 rounded-xl bg-primary-500/10 border border-primary-500/20 items-center justify-center flex-shrink-0">
                        <i data-feather="calendar" class="w-5 h-5 text-primary-400"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-white uppercase tracking-wider italic leading-none">Schedule Hub</h2>
                        <p class="text-[9px] text-gray-500 font-black uppercase tracking-widest mt-0.5 italic">Synchronized Timeline</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Mobile View Toggle -->
                <div class="flex md:hidden bg-dark-bg border border-dark-border rounded-lg p-0.5">
                    <button onclick="toggleMobileView('agenda')" id="btn-agenda" class="px-3 py-1.5 text-[10px] font-black rounded text-white bg-white/10 uppercase tracking-widest italic">Agenda</button>
                    <button onclick="toggleMobileView('week')" id="btn-week" class="px-3 py-1.5 text-[10px] font-black rounded text-gray-400 hover:text-white uppercase tracking-widest italic">Week</button>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center bg-dark-bg border border-dark-border rounded-xl p-1 shadow-inner shadow-black/20">
                    <button onclick="changeWeek(-1)" class="p-2 hover:bg-white/10 rounded-lg text-gray-400 hover:text-white transition-colors"><i data-feather="chevron-left" class="w-4 h-4"></i></button>
                    <span id="currentWeekDisplay" class="mx-4 text-sm font-black text-white min-w-[160px] text-center tracking-widest uppercase text-[11px] italic">Loading...</span>
                    <button onclick="changeWeek(1)" class="p-2 hover:bg-white/10 rounded-lg text-gray-400 hover:text-white transition-colors"><i data-feather="chevron-right" class="w-4 h-4"></i></button>
                </div>

                <button onclick="goToday()" class="hidden sm:flex items-center gap-1.5 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-primary-400 bg-primary-500/10 hover:bg-primary-500 hover:text-white border border-primary-500/20 rounded-xl transition-all italic">
                    <i data-feather="target" class="w-3.5 h-3.5"></i>
                    <span>Today</span>
                </button>

                <button onclick="openModal('eventModal')" class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-primary-500/25 italic hover:shadow-primary-500/40 active:scale-95">
                    <i data-feather="plus" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Add Event</span>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-hidden flex flex-col lg:flex-row bg-dark-bg/30">
            
            <!-- CENTER: Calendar Grid -->
            <div id="weekViewContainer" class="hidden md:flex flex-1 flex-col overflow-y-auto overflow-x-auto relative custom-scroll">
                <div class="min-w-[800px] w-full p-5 lg:p-6 flex flex-col h-full bg-[#0a0c10]/10 backdrop-blur-3xl">
                    
                    <!-- Days Header (Dynamic) -->
                    <div id="weekHeader" class="grid grid-cols-8 mb-4 sticky top-0 z-30 bg-dark-bg/90 backdrop-blur-xl py-4 rounded-2xl border border-white/5 shadow-2xl">
                        <!-- Rendered by JS -->
                    </div>

                    <!-- Dynamic Time Grid -->
                    <div class="relative flex-1 bg-dark-surface/20 rounded-2xl border border-white/5 overflow-hidden shadow-inner shadow-black/40">
                        
                         <!-- Time Labels Column + Row Lines -->
                        <div id="timeGridLines" class="absolute inset-0 flex flex-col text-white/20">
                            <!-- Rendered by JS -->
                        </div>

                         <!-- Columns for Absolute Blocks -->
                        <div class="absolute inset-0 grid grid-cols-8 pointer-events-none">
                            <div class="border-r border-white/5"></div><!-- Time spacer -->
                            <div class="border-r border-white/5 relative day-col pointer-events-auto" data-dayindex="1"></div>
                            <div class="border-r border-white/5 relative day-col pointer-events-auto" data-dayindex="2"></div>
                            <div class="border-r border-white/5 relative day-col pointer-events-auto" data-dayindex="3"></div>
                            <div class="border-r border-white/5 relative day-col pointer-events-auto" data-dayindex="4"></div>
                            <div class="border-r border-white/5 relative day-col pointer-events-auto" data-dayindex="5"></div>
                            <div class="border-r border-white/5 relative day-col pointer-events-auto bg-primary-500/[0.02]" data-dayindex="6"></div>
                            <div class="relative day-col pointer-events-auto bg-primary-500/[0.02]" data-dayindex="0"></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- RIGHT: Agenda Sidebar -->
            <aside id="agendaViewContainer" class="w-full lg:w-[380px] border-t lg:border-t-0 lg:border-l border-white/5 bg-dark-surface/40 backdrop-blur-3xl flex flex-col overflow-hidden">
                
                <!-- Mini Calendar -->
                <div class="hidden md:block p-5 border-b border-white/5 shadow-2xl z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <i data-feather="calendar" class="w-4 h-4 text-primary-400"></i>
                            <h3 id="miniCalMonth" class="text-sm font-black text-white uppercase tracking-widest italic">October 2023</h3>
                        </div>
                        <div class="flex gap-1">
                            <button onclick="changeMonth(-1)" class="p-1.5 bg-dark-bg rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-colors border border-white/5"><i data-feather="chevron-left" class="w-4 h-4"></i></button>
                            <button onclick="changeMonth(1)" class="p-1.5 bg-dark-bg rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-colors border border-white/5"><i data-feather="chevron-right" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black uppercase text-gray-600 mb-2 tracking-widest">
                        <div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div><div>Su</div>
                    </div>
                    <div id="miniCalGrid" class="grid grid-cols-7 gap-1 text-center text-xs font-bold">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <!-- Today's Agenda -->
                <div class="flex-1 overflow-y-auto custom-scroll p-4 md:p-5 bg-gradient-to-b from-transparent to-dark-bg/30">
                    <div class="flex items-center justify-between mb-5 sticky top-0 bg-dark-surface/60 backdrop-blur-lg py-3 z-10 border-b border-white/5 -mx-4 md:-mx-5 px-4 md:px-5">
                        <div class="flex items-center gap-2.5">
                            <i data-feather="list" class="w-4 h-4 text-primary-400"></i>
                            <h3 id="agendaDateTitle" class="text-sm font-black text-white uppercase tracking-widest italic">Loading...</h3>
                        </div>
                        <span id="agendaCountBadge" class="text-[9px] font-black text-primary-400 bg-primary-500/10 border border-primary-500/20 px-3 py-1 rounded-full uppercase tracking-widest shadow-lg shadow-primary-500/10">0 Events</span>
                    </div>

                    <div id="agendaList" class="space-y-1 mt-1">
                        <!-- Rendered by JS -->
                    </div>
                </div>
            </aside>
        </main>
    </div>

    <!-- ADD EVENT MODAL -->
    <div id="eventModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md transition-opacity opacity-0" id="modalBackdrop" onclick="closeModal('eventModal')"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="glass-panel w-full max-w-md rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 border border-white/10 pointer-events-auto" id="modalContent">
                <div class="p-6 border-b border-white/5 flex justify-between items-center bg-white/5">
                    <h3 class="text-xl font-black text-white uppercase tracking-widest italic leading-none">Add Personal Event</h3>
                    <button onclick="closeModal('eventModal')" class="text-gray-400 hover:text-white transition-colors bg-white/5 p-2 rounded-full"><i data-feather="x" class="w-4 h-4"></i></button>
                </div>
                <form id="eventForm" class="p-6 space-y-5">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Event Title</label>
                        <input type="text" id="evTitle" required class="w-full bg-dark-bg border border-dark-border rounded-xl px-4 py-3 text-sm text-white font-bold focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all placeholder:text-gray-600" placeholder="e.g. Faculty Meeting">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Date</label>
                            <input type="date" id="evDate" required class="w-full bg-dark-bg border border-dark-border rounded-xl px-4 py-3 text-sm text-white font-bold focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Start</label>
                                <input type="time" id="evStart" required class="w-full bg-dark-bg border border-dark-border rounded-xl px-2 py-3 text-sm text-white font-bold focus:ring-2 focus:ring-primary-500 outline-none text-center">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">End</label>
                                <input type="time" id="evEnd" required class="w-full bg-dark-bg border border-dark-border rounded-xl px-2 py-3 text-sm text-white font-bold focus:ring-2 focus:ring-primary-500 outline-none text-center">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Description / Location</label>
                        <textarea id="evDesc" rows="2" class="w-full bg-dark-bg border border-dark-border rounded-xl px-4 py-3 text-sm text-white font-bold focus:ring-2 focus:ring-primary-500 outline-none resize-none placeholder:text-gray-600" placeholder="Room 205..."></textarea>
                    </div>
                    <div class="pt-4 mt-2 border-t border-white/5 flex justify-end gap-3">
                        <button type="button" onclick="closeModal('eventModal')" class="px-5 py-3 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition-colors text-[11px] font-black uppercase tracking-widest">Cancel</button>
                        <button type="submit" id="evSubmitBtn" class="px-6 py-3 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-[11px] font-black uppercase tracking-widest shadow-lg shadow-primary-500/20 transition-all active:scale-95 italic flex items-center gap-2">
                            <span id="evSubmitText">Save Event</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
        function openModal(id) {
            const modal = document.getElementById(id); const backdrop = document.getElementById('modalBackdrop'); const content = document.getElementById('modalContent');
            if(id === 'eventModal') document.getElementById('eventForm').reset();
            modal.classList.remove('hidden'); setTimeout(() => { backdrop.classList.remove('opacity-0'); content.classList.remove('opacity-0', 'scale-95'); }, 10);
        }
        function closeModal(id) {
            const modal = document.getElementById(id); const backdrop = document.getElementById('modalBackdrop'); const content = document.getElementById('modalContent');
            backdrop.classList.add('opacity-0'); content.classList.add('opacity-0', 'scale-95'); setTimeout(() => { modal.classList.add('hidden'); }, 300);
        }
        function showToast(message, type = 'info') { 
            const container = document.getElementById('toastContainer'); 
            const toast = document.createElement('div'); 
            toast.className = `toast flex items-center w-full max-w-xs p-4 space-x-3 text-gray-200 bg-dark-surface rounded-lg shadow-xl border border-dark-border transform translate-x-full transition-transform duration-300 ${type === 'success' ? 'border-l-4 border-l-green-500' : (type==='error'?'border-l-4 border-l-primary-500':'border-l-4 border-l-blue-500')}`; 
            const icon = type === 'success' ? 'check-circle' : (type === 'error' ? 'alert-triangle' : 'info');
            toast.innerHTML = `<div class="flex-shrink-0 text-${type === 'success' ? 'green' : (type==='error'?'primary':'blue')}-400"><i data-feather="${icon}" class="w-5 h-5"></i></div><div class="text-[10px] font-black uppercase tracking-widest italic text-white">${message}</div>`; 
            container.appendChild(toast); 
            feather.replace();
            /* Animate */
            requestAnimationFrame(() => { setTimeout(() => toast.classList.remove('translate-x-full'), 10); });
            setTimeout(() => { toast.classList.add('translate-x-full'); setTimeout(() => toast.remove(), 300); }, 3000); 
        }

        function toggleMobileView(view) {
            const a = document.getElementById('agendaViewContainer'), w = document.getElementById('weekViewContainer'), 
                  ba = document.getElementById('btn-agenda'), bw = document.getElementById('btn-week');
            if (view === 'agenda') { a.classList.remove('hidden'); w.classList.add('hidden'); ba.classList.add('bg-white/10', 'text-white'); ba.classList.remove('text-gray-400'); bw.classList.remove('bg-white/10', 'text-white'); bw.classList.add('text-gray-400');
            } else { a.classList.add('hidden'); w.classList.remove('hidden'); w.classList.add('flex'); bw.classList.add('bg-white/10', 'text-white'); bw.classList.remove('text-gray-400'); ba.classList.remove('bg-white/10', 'text-white'); ba.classList.add('text-gray-400'); }
        }
    </script>
    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';

        // GLOBAL STATE
        let activeDate = new Date();
        // Zero out time
        activeDate.setHours(0,0,0,0);
        
        let targetMonthDate = new Date();
        targetMonthDate.setHours(0,0,0,0);

        let currentClasses = [];
        let currentEvents = [];
        let lastScheduleSig = '';
        
        // CONFIG
        const START_HOUR = 6; // 06:00
        const END_HOUR = 22;  // 22:00
        const ROW_HEIGHT = 80;

        const dayCodeMap = {'M':1, 'T':2, 'W':3, 'TH':4, 'F':5, 'S':6};

        // EXPOSE GLOBALS for buttons
        window.changeWeek = (dir) => { activeDate.setDate(activeDate.getDate() + (dir * 7)); syncCalendars(); };
        window.changeMonth = (dir) => { targetMonthDate.setMonth(targetMonthDate.getMonth() + dir); renderMiniCalendar(); };
        window.goToday = () => { activeDate = new Date(); activeDate.setHours(0,0,0,0); targetMonthDate = new Date(); targetMonthDate.setHours(0,0,0,0); syncCalendars(); };
        window.selectAgendaDate = (dString) => { activeDate = new Date(dString); activeDate.setHours(0,0,0,0); syncCalendars(); };

        function getStartOfWeek(d) {
            const date = new Date(d);
            const day = date.getDay(); const diff = date.getDate() - day + (day === 0 ? -6 : 1);
            return new Date(date.setDate(diff));
        }
        function isValidDate(d) { return d instanceof Date && !isNaN(d); }
        function parseTimeStr(timeStr) {
            if(!timeStr) return null;
            const [h, m] = timeStr.split(':');
            return parseInt(h) + (parseInt(m)/60);
        }
        function formatStandardTime(military) {
             if(!military) return 'TBA'; 
             const p = military.split(':'); if(p.length<2) return military;
             let hrs = parseInt(p[0]), mins = p[1];
             const ampm = hrs >= 12 ? 'PM' : 'AM'; hrs = hrs % 12 || 12;
             return `${hrs}:${mins} ${ampm}`;
        }
        function getLocalIsoStr(d) {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // --- FETCHING ---
        async function loadScheduleData() {
            try {
                const [classes, events] = await Promise.all([
                    api('/classes.php'),
                    api('/events.php')
                ]);
                const sig = JSON.stringify([classes, events]);
                if (sig === lastScheduleSig) return;
                lastScheduleSig = sig;
                currentClasses = Array.isArray(classes) ? classes : [];
                currentEvents = Array.isArray(events) ? events : [];
                syncCalendars();
            } catch (e) {
                console.error('Failed to load schedule data', e);
            }
        }

        initPage(() => {
            setTimeout(() => loadScheduleData(), 500);
            setInterval(loadScheduleData, 10000);
        });

        // --- SUBMIT EVENT ---
        document.getElementById('eventForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const user = JSON.parse(sessionStorage.getItem('cs_user') || 'null'); if(!user) return window.showToast('Auth error', 'error');
            
            const title = document.getElementById('evTitle').value;
            const date = document.getElementById('evDate').value;
            const start = document.getElementById('evStart').value;
            const end = document.getElementById('evEnd').value;
            const desc = document.getElementById('evDesc').value;

            if(parseTimeStr(start) >= parseTimeStr(end)) return window.showToast('End time must be after start time!', 'error');

            const btn = document.getElementById('evSubmitBtn');
            const txt = document.getElementById('evSubmitText');
            btn.disabled = true; txt.innerText = 'Syncing...';

            try {
                await api('/events.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        teacher_uid: user.uid,
                        title: title,
                        date_str: date,
                        start_time: start,
                        end_time: end,
                        description: desc
                    })
                });
                window.showToast('Event synchronized to grid.', 'success');
                window.closeModal('eventModal');
            } catch(e) {
                console.error(e);
                window.showToast('Database Error', 'error');
            } finally {
                btn.disabled = false; txt.innerText = 'Save Event';
            }
        });

        // --- CORE RENDER ORCHESTRATOR ---
        function syncCalendars() {
            renderTimeLines();
            renderWeekHeader();
            renderWeekBlocks();
            renderMiniCalendar();
            renderAgenda();
        }

        // --- 1. RENDER BACKGROUND GRID LINES ---
        function renderTimeLines() {
            const c = document.getElementById('timeGridLines'); c.innerHTML = '';
            for(let h=START_HOUR; h<=END_HOUR; h++) {
                const isNoon = h===12;
                const hh = h.toString().padStart(2, '0')+ ':00';
                c.innerHTML += `
                    <div style="height: ${ROW_HEIGHT}px" class="grid grid-cols-8 border-b border-dark-border/20 w-full ${isNoon ? 'bg-dark-bg/40' : ''}">
                        <div class="text-[9px] font-black text-gray-600 text-right pr-3 pt-0.5 tracking-widest italic">${hh}</div>
                        <div class="col-span-7 flex items-center justify-center ${isNoon ? 'opacity-20' : ''}">
                            ${isNoon ? '<div class="flex items-center gap-2"><div class="w-16 h-px bg-gray-600/30"></div><span class="text-[9px] font-black tracking-[0.25em] uppercase text-gray-600 italic">LUNCH</span><div class="w-16 h-px bg-gray-600/30"></div></div>' : ''}
                        </div>
                    </div>`;
            }
        }

        // --- 2. RENDER WEEK VIEW (Dynamic Days) ---
        function renderWeekHeader() {
            const h = document.getElementById('weekHeader');
            const cwBtn = document.getElementById('currentWeekDisplay');
            
            const startNode = getStartOfWeek(activeDate);
            const shortMonths = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const daysArr = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            
            let htmlStr = `<div class="text-[9px] uppercase tracking-widest font-black text-gray-600 flex items-end ml-4 pb-1 italic opacity-50">GMT+8</div>`;
            
            const today = new Date(); today.setHours(0,0,0,0);
            let endNode = null;

            for(let i=0; i<7; i++) {
                let d = new Date(startNode); d.setDate(d.getDate() + i);
                if(i===6) endNode = new Date(d);

                const isToday = d.getTime() === today.getTime();
                const isSelected = d.getTime() === activeDate.getTime();
                
                const c_text = isToday ? 'text-primary-400' : 'text-gray-500';
                const c_num  = isToday ? 'text-white' : 'text-gray-400';
                
                htmlStr += `
                    <div class="text-center cursor-pointer group" onclick="selectAgendaDate('${getLocalIsoStr(d)}')">
                        <div class="text-[9px] ${c_text} uppercase font-black tracking-widest mb-1 italic transition-colors group-hover:text-primary-400">${daysArr[i]}</div>
                        <div class="inline-flex items-center justify-center w-9 h-9 rounded-xl transition-all group-hover:bg-white/5 group-hover:scale-110 ${isSelected ? 'bg-primary-500 shadow-lg shadow-primary-500/30 scale-110' : ''}">
                            <span class="text-lg font-black leading-none ${isSelected ? 'text-white' : c_num}">${d.getDate()}</span>
                        </div>
                        ${isToday ? '<div class="w-1 h-1 bg-primary-500 rounded-full mx-auto mt-1.5 shadow-[0_0_6px_rgba(234,38,40,0.6)]"></div>' : '<div class="h-[13px]"></div>'}
                    </div>`;
            }
            h.innerHTML = htmlStr;
            cwBtn.innerText = `${shortMonths[startNode.getMonth()]} ${startNode.getDate()} — ${shortMonths[endNode.getMonth()]} ${endNode.getDate()}`;
        }

        // --- 3. INJECT ABSOLUTE BLOCKS ---
        function renderWeekBlocks() {
            document.querySelectorAll('.day-col').forEach(col => col.innerHTML = '');
            
            const startNode = getStartOfWeek(activeDate);
            
            for(let i=0; i<7; i++) {
                let currentDayNode = new Date(startNode); currentDayNode.setDate(currentDayNode.getDate() + i);
                const dayIndex = currentDayNode.getDay();
                const yyyyMmDd = getLocalIsoStr(currentDayNode);
                
                const targetCol = document.querySelector(`.day-col[data-dayindex="${dayIndex}"]`);
                if(!targetCol) continue;

                const blocks = generateBlocksForDay(dayIndex, yyyyMmDd);
                
                blocks.forEach(b => {
                    const sTime = parseTimeStr(b.startTime);
                    const eTime = parseTimeStr(b.endTime);
                    if(sTime && eTime && sTime >= START_HOUR && sTime < END_HOUR) {
                        const topPx = (sTime - START_HOUR) * ROW_HEIGHT;
                        const heightPx = (eTime - sTime) * ROW_HEIGHT;
                        
                        const isClass = b.type === 'class';
                        const colorClass = isClass ? 'border-l-primary-500 bg-primary-500/[0.08] hover:bg-primary-500/[0.15] shadow-primary-500/5' 
                                                    : 'border-l-blue-500 bg-blue-500/[0.08] hover:bg-blue-500/[0.15] shadow-blue-500/5';
                        const borderClass = isClass ? 'hover:border-primary-500/40' : 'hover:border-blue-500/40';
                        
                        targetCol.innerHTML += `
                            <div class="absolute inset-x-1 p-2.5 rounded-xl border border-white/5 border-l-4 ${colorClass} ${borderClass} transition-all cursor-pointer z-10 overflow-hidden shadow-lg backdrop-blur-md flex flex-col group hover:z-20 hover:scale-[1.02] hover:shadow-xl" 
                                 style="top: ${topPx+2}px; height: ${Math.max(heightPx-4, 40)}px;" onclick="${isClass ? `window.location.href='class_view.php?id=${b.id}'` : `window.showToast('${b.title}')`}">
                                <div class="flex items-start justify-between gap-1">
                                    <h4 class="text-[11px] font-black text-white italic uppercase tracking-tighter truncate leading-tight group-hover:text-white transition-colors ${heightPx < 60 ? 'text-[10px]' : ''}">${b.title}</h4>
                                    <span class="shrink-0 text-[8px] font-bold text-white/40 uppercase tracking-wider mt-0.5 italic">${formatStandardTime(b.startTime)}</span>
                                </div>
                                ${heightPx >= 50 ? `<div class="flex items-center gap-1.5 mt-1">
                                    <i data-feather="${isClass ? 'book-open' : 'file-text'}" class="w-2.5 h-2.5 text-white/40"></i>
                                    <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest truncate italic">${b.subtitle}</span>
                                </div>` : ''}
                            </div>`;
                    }
                });
            }
            feather.replace();
        }

        // Generate normalized {type, title, subtitle, startTime, endTime}
        function generateBlocksForDay(jsDayIndex, dateStr) {
            let res = [];
            
            // Map JS getDay (0=Sun, 1=Mon) to Schedule String (M, T, W, TH, F, S)
            const mapFilter = ['SUN','M','T','W','TH','F','S'];
            const targetChar = mapFilter[jsDayIndex];

            // Filter Classes
            currentClasses.forEach(c => {
                 let sched = c.schedule || '';
                 // Match exact string to avoid matching T inside TH
                 let matches = false;
                 
                 let tokens = []; let j=0;
                 while(j<sched.length) {
                    if(sched.substr(j,2)==='TH') { tokens.push('TH'); j+=2; }
                    else { tokens.push(sched[j]); j++; }
                 }
                 if(tokens.includes(targetChar)) matches = true;

                  if(matches) {
                      res.push({
                          type: 'class', id: c.id,
                          title: c.class_name, subtitle: c.section_code,
                          startTime: c.start_time, endTime: c.end_time
                      });
                  }
             });

            // Filter Events
            currentEvents.filter(e => e.date_str === dateStr).forEach(e => {
                 res.push({
                     type: 'event', id: e.id,
                     title: e.title, subtitle: 'Personal Event',
                     startTime: e.start_time, endTime: e.end_time
                 });
            });

            return res;
        }

        // --- 4. RENDER MINI CALENDAR ---
        function renderMiniCalendar() {
            const grid = document.getElementById('miniCalGrid');
            const title = document.getElementById('miniCalMonth');
            const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            
            title.innerText = `${months[targetMonthDate.getMonth()]} ${targetMonthDate.getFullYear()}`;
            
            let firstDay = new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth(), 1);
            let offset = firstDay.getDay() - 1; if(offset < 0) offset = 6;
            
            let daysInMonth = new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth()+1, 0).getDate();
            let prevDaysInMonth = new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth(), 0).getDate();

            let html = ``;
            for(let i=0; i<offset; i++) {
                html += `<div class="p-1.5 text-gray-700/40 text-xs">${prevDaysInMonth - offset + 1 + i}</div>`;
            }
            const today = new Date();
            for(let i=1; i<=daysInMonth; i++) {
                let buildDate = new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth(), i);
                const isSelected = buildDate.getTime() === activeDate.getTime();
                const isToday = buildDate.setHours(0,0,0,0) === today.setHours(0,0,0,0);
                
                const dObj = new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth(), i);
                const dIso = getLocalIsoStr(dObj);

                let eventDots = ``;
                if(generateBlocksForDay(buildDate.getDay(), dIso).length > 0) {
                     eventDots = `<span class="absolute -bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 bg-primary-400 rounded-full"></span>`;
                }

                let cellClass = 'p-1.5 rounded-lg cursor-pointer transition-all relative text-xs ';
                if(isSelected) {
                    cellClass += 'bg-primary-500 text-white shadow-lg shadow-primary-500/30 font-black scale-110';
                } else if(isToday) {
                    cellClass += 'text-primary-400 font-black hover:bg-primary-500/10';
                } else {
                    cellClass += 'text-gray-400 hover:bg-white/5 hover:text-white font-bold';
                }
                html += `<div class="${cellClass}" onclick="selectAgendaDate('${dIso}')">${i}${eventDots}</div>`;
            }
            grid.innerHTML = html;
        }

        // --- 5. RENDER AGENDA ---
        function renderAgenda() {
            const list = document.getElementById('agendaList');
            const tDateStr = document.getElementById('agendaDateTitle');
            const badge = document.getElementById('agendaCountBadge');
            
            const shortMonths = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const isToday = activeDate.getTime() === new Date().setHours(0,0,0,0);
            
            tDateStr.innerText = isToday ? "Today's Agenda" : `${shortMonths[activeDate.getMonth()]} ${activeDate.getDate()} Agenda`;

            const blocks = generateBlocksForDay(activeDate.getDay(), getLocalIsoStr(activeDate));
            
            blocks.sort((a,b) => parseTimeStr(a.startTime) - parseTimeStr(b.startTime));
            
            badge.innerText = `${blocks.length} ITEM${blocks.length !== 1 ? 'S' : ''}`;
            
            if(blocks.length === 0) {
                 list.innerHTML = `<div class="flex flex-col items-center justify-center py-16 opacity-30"><i data-feather="calendar" class="w-12 h-12 mb-4 text-gray-500"></i><p class="text-[10px] font-black uppercase tracking-widest italic text-gray-400">No items for this day</p><p class="text-[8px] text-gray-600 mt-1 italic">Timeline is clear</p></div>`;
                 feather.replace(); return;
            }

            const currentActualTime = new Date().getHours() + (new Date().getMinutes()/60);

            list.innerHTML = blocks.map(b => {
                 const sT = parseTimeStr(b.startTime);
                 const eT = parseTimeStr(b.endTime);
                 
                 let state = 'Upcoming';
                 let stateClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
                 let dotColor = 'bg-gray-500';
                 let lineColor = 'bg-white/5';
                 let cardBorder = 'border-white/5';
                 
                 if(isToday) {
                     if(currentActualTime >= sT && currentActualTime < eT) {
                         state = 'Active Now'; stateClass = 'bg-primary-500 text-white border-primary-500'; dotColor = 'bg-primary-500 shadow-[0_0_8px_rgba(234,38,40,0.6)]'; lineColor = 'bg-primary-500/40'; cardBorder = 'border-primary-500/30 bg-primary-500/[0.03]';
                     } else if (currentActualTime >= eT) {
                         state = 'Done'; stateClass = 'bg-green-500/10 text-green-400 border-green-500/20'; dotColor = 'bg-green-500/40'; lineColor = 'bg-white/5 opacity-30';
                     }
                 }

                 const concluded = state === 'Done';
                 const ongoing = state === 'Active Now';
                 const cardAlpha = concluded ? 'opacity-60 hover:opacity-100' : '';
                 const titleDeco = concluded ? 'line-through' : '';

                 return `
                    <div class="relative flex items-start gap-4 pb-5 group last:pb-2">
                        <div class="flex flex-col items-center">
                            <div class="text-[9px] font-black ${ongoing ? 'text-primary-400' : 'text-gray-500'} w-14 text-right uppercase tracking-widest italic pt-0.5 leading-tight">${formatStandardTime(b.startTime)}</div>
                            <div class="w-[1px] h-full absolute top-[22px] left-[4.25rem] ${lineColor} group-last:hidden"></div>
                        </div>
                        <div class="w-2.5 h-2.5 rounded-full ${dotColor} border-2 border-dark-bg z-10 mt-2 flex-shrink-0 relative"></div>
                        <div class="flex-1 glass-panel rounded-2xl p-4 border ${cardBorder} ${cardAlpha} transition-all cursor-pointer shadow-lg shadow-black/10 hover:shadow-xl hover:-translate-y-0.5 ${ongoing ? 'shadow-primary-500/5' : 'hover:border-white/20'}" onclick="${b.type === 'class' ? `window.location.href='class_view.php?id=${b.id}'` : `window.showToast('${b.title}')`}">
                            <div class="flex items-start justify-between gap-2 mb-1.5">
                                <h4 class="text-sm font-black text-white uppercase tracking-tighter italic leading-snug ${titleDeco}">${b.title}</h4>
                                <span class="shrink-0 text-[8px] font-black uppercase tracking-widest italic px-2 py-0.5 rounded-md border ${stateClass}">${state}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[9px] text-gray-500 font-bold uppercase tracking-widest italic">
                                <i data-feather="${b.type === 'class' ? 'book-open' : 'file-text'}" class="w-3 h-3"></i>
                                <span>${b.subtitle}</span>
                                <span class="text-gray-700 mx-1">|</span>
                                <span>${formatStandardTime(b.startTime)} — ${formatStandardTime(b.endTime)}</span>
                            </div>
                        </div>
                    </div>`;
            }).join('');
            feather.replace();
        }

    </script>
</body>
</html>