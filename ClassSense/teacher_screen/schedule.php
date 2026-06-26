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
<body class="antialiased min-h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

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
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20 shadow-xl shadow-black/20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                    <i data-feather="menu"></i>
                </button>
                <div>
                    <h2 class="text-xl font-bold text-white uppercase tracking-wider italic">Schedule Hub</h2>
                    <p class="text-xs text-gray-500 hidden sm:block font-bold">Synchronized Timeline Validation</p>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Mobile View Toggle -->
                <div class="flex md:hidden bg-dark-bg border border-dark-border rounded-lg p-1">
                    <button onclick="toggleMobileView('agenda')" id="btn-agenda" class="px-3 py-1.5 text-xs font-medium rounded text-white bg-white/10">Agenda</button>
                    <button onclick="toggleMobileView('week')" id="btn-week" class="px-3 py-1.5 text-xs font-medium rounded text-gray-400 hover:text-white">Week</button>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center bg-dark-bg border border-dark-border rounded-lg p-1">
                    <button onclick="changeWeek(-1)" class="p-1.5 hover:bg-white/10 rounded text-gray-400 hover:text-white transition-colors"><i data-feather="chevron-left" class="w-4 h-4"></i></button>
                    <span id="currentWeekDisplay" class="mx-3 text-sm font-bold text-white min-w-[140px] text-center tracking-widest uppercase text-[11px] italic">Loading...</span>
                    <button onclick="changeWeek(1)" class="p-1.5 hover:bg-white/10 rounded text-gray-400 hover:text-white transition-colors"><i data-feather="chevron-right" class="w-4 h-4"></i></button>
                </div>

                <button onclick="goToday()" class="hidden sm:block px-4 py-2 text-[11px] font-black uppercase tracking-widest text-primary-400 bg-primary-500/10 hover:bg-primary-500 hover:text-white border border-primary-500/20 rounded-lg transition-all italic">Today</button>

                <button onclick="openModal('eventModal')" class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-[11px] font-black uppercase tracking-widest transition-all shadow-lg shadow-primary-500/20 italic">
                    <i data-feather="plus" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Add Event</span>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-hidden flex flex-col lg:flex-row bg-dark-bg/20">
            
            <!-- CENTER: Calendar Grid -->
            <div id="weekViewContainer" class="hidden md:flex flex-1 flex-col overflow-y-auto overflow-x-auto relative custom-scroll">
                <div class="min-w-[800px] w-full p-6 flex flex-col h-full bg-[#0a0c10]/20 backdrop-blur-3xl">
                    
                    <!-- Days Header (Dynamic) -->
                    <div id="weekHeader" class="grid grid-cols-8 mb-4 sticky top-0 z-30 bg-dark-bg/90 backdrop-blur-xl py-4 rounded-2xl border border-white/5 shadow-2xl">
                        <!-- Rendered by JS -->
                    </div>

                    <!-- Dynamic Time Grid -->
                    <div class="relative flex-1 bg-dark-surface/30 rounded-2xl border border-white/5 overflow-hidden">
                        
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
                            <div class="border-r border-white/5 relative day-col pointer-events-auto bg-white/[0.01]" data-dayindex="6"></div>
                            <div class="relative day-col pointer-events-auto bg-white/[0.01]" data-dayindex="0"></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- RIGHT: Agenda Sidebar -->
            <aside id="agendaViewContainer" class="w-full lg:w-96 border-t lg:border-t-0 lg:border-l border-white/5 bg-dark-surface/50 backdrop-blur-3xl flex flex-col overflow-hidden">
                
                <!-- Mini Calendar -->
                <div class="hidden md:block p-6 border-b border-light/5 shadow-2xl z-10">
                    <div class="flex items-center justify-between mb-4">
                        <h3 id="miniCalMonth" class="text-sm font-black text-white uppercase tracking-widest italic">October 2023</h3>
                        <div class="flex gap-1">
                            <button onclick="changeMonth(-1)" class="p-1.5 bg-dark-bg rounded text-gray-400 hover:text-white transition-colors border border-white/5"><i data-feather="chevron-left" class="w-4 h-4"></i></button>
                            <button onclick="changeMonth(1)" class="p-1.5 bg-dark-bg rounded text-gray-400 hover:text-white transition-colors border border-white/5"><i data-feather="chevron-right" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-black uppercase text-gray-600 mb-2 tracking-widest">
                        <div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div><div>Su</div>
                    </div>
                    <div id="miniCalGrid" class="grid grid-cols-7 gap-1 text-center text-xs font-bold">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <!-- Today's Agenda -->
                <div class="flex-1 overflow-y-auto custom-scroll p-4 md:p-6 bg-gradient-to-b from-transparent to-dark-bg/50">
                    <div class="flex items-center justify-between mb-8 sticky top-0 bg-dark-surface/50 backdrop-blur-lg py-2 z-10 border-b border-white/5">
                        <h3 id="agendaDateTitle" class="text-base font-black text-white uppercase tracking-widest italic">Loading...</h3>
                        <span id="agendaCountBadge" class="text-[9px] font-black text-primary-400 bg-primary-500/10 border border-primary-500/20 px-3 py-1 rounded-full uppercase tracking-widest shadow-lg shadow-primary-500/10">0 Events</span>
                    </div>

                    <div id="agendaList" class="space-y-0 mt-2">
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
        import { db, auth } from '../assets/js/firebase-init.js';
        import { collection, addDoc, onSnapshot, query, where, serverTimestamp, orderBy } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
        import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

        // GLOBAL STATE
        let activeDate = new Date();
        // Zero out time
        activeDate.setHours(0,0,0,0);
        
        let targetMonthDate = new Date();
        targetMonthDate.setHours(0,0,0,0);

        let currentClasses = [];
        let currentEvents = [];
        
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
        onAuthStateChanged(auth, (user) => {
            if (user) {
                // Fetch Classes
                const qC = query(collection(db, "classes"), where("teacherUid", "==", user.uid));
                onSnapshot(qC, (snap) => {
                    currentClasses = snap.docs.map(doc => ({id: doc.id, ...doc.data()}));
                    syncCalendars();
                });
                
                // Fetch Events
                const qE = query(collection(db, "events"), where("teacherUid", "==", user.uid));
                onSnapshot(qE, (snap) => {
                    currentEvents = snap.docs.map(doc => ({id: doc.id, ...doc.data()}));
                    syncCalendars();
                });
            } else {
                window.location.replace('../login.php?error=session_cleared');
            }
        });

        // --- SUBMIT EVENT ---
        document.getElementById('eventForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const user = auth.currentUser; if(!user) return window.showToast('Auth error', 'error');
            
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
                await addDoc(collection(db, "events"), {
                    teacherUid: user.uid,
                    title: title,
                    dateStr: date, // YYYY-MM-DD
                    startTime: start,
                    endTime: end,
                    description: desc,
                    createdAt: serverTimestamp()
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
                    <div style="height: ${ROW_HEIGHT}px" class="grid grid-cols-8 border-b border-dark-border/30 w-full ${isNoon ? 'bg-dark-bg/30' : ''}">
                        <div class="text-[10px] font-black text-gray-600 text-right pr-3 pt-1 uppercase italic tracking-widest">${hh}</div>
                        <div class="col-span-7 ${isNoon ? 'flex items-center justify-center opacity-30 font-black tracking-[0.3em] uppercase text-xs italic' : ''}">${isNoon ? 'LUNCH BREAK' : ''}</div>
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
            
            let htmlStr = `<div class="text-[10px] uppercase tracking-widest font-black text-gray-600 flex items-end ml-4 pb-1 italic opacity-60">Time Zone</div>`;
            
            const today = new Date(); today.setHours(0,0,0,0);
            let endNode = null;

            for(let i=0; i<7; i++) {
                let d = new Date(startNode); d.setDate(d.getDate() + i);
                if(i===6) endNode = new Date(d);

                const isToday = d.getTime() === today.getTime();
                const isSelected = d.getTime() === activeDate.getTime();
                
                const c_text = isToday ? 'text-primary-500' : 'text-gray-500';
                const c_num  = isToday ? 'text-white' : 'text-gray-400';
                
                htmlStr += `
                    <div class="text-center cursor-pointer group" onclick="selectAgendaDate('${getLocalIsoStr(d)}')">
                        <div class="text-[10px] ${c_text} uppercase font-black tracking-widest mb-1 italic transition-colors group-hover:text-primary-400 ${isSelected? 'text-primary-400 glow':''}">${daysArr[i]}</div>
                        <div class="text-xl font-bold ${c_num} transition-transform group-hover:scale-110 ${isSelected?'text-white':''} ${isSelected && !isToday ? 'bg-white/10 rounded-lg mx-auto w-8' : ''}">${d.getDate()}</div>
                        ${isToday ? '<div class="w-1.5 h-1.5 bg-primary-500 rounded-full mx-auto mt-1 shadow-[0_0_8px_rgba(234,38,40,0.8)] glow-pulse"></div>' : '<div class="h-2.5"></div>'}
                    </div>`;
            }
            h.innerHTML = htmlStr;
            cwBtn.innerText = `${shortMonths[startNode.getMonth()]} ${startNode.getDate()} - ${shortMonths[endNode.getMonth()]} ${endNode.getDate()}`;
        }

        // --- 3. INJECT ABSOLUTE BLOCKS ---
        function renderWeekBlocks() {
            document.querySelectorAll('.day-col').forEach(col => col.innerHTML = ''); // clear all
            
            const startNode = getStartOfWeek(activeDate);
            
            // Loop days inside week
            for(let i=0; i<7; i++) {
                let currentDayNode = new Date(startNode); currentDayNode.setDate(currentDayNode.getDate() + i);
                const dayIndex = currentDayNode.getDay(); // 0 is Sunday
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
                        
                        const colorClass = b.type === 'class' ? 'border-primary-500 bg-primary-500/10 hover:bg-primary-500/20 shadow-primary-500/10' 
                                                              : 'border-blue-500 bg-blue-500/10 hover:bg-blue-500/20 shadow-blue-500/10';
                        const textIcon = b.type === 'class' ? 'book' : 'calendar';
                        
                        targetCol.innerHTML += `
                            <div class="absolute inset-x-1 p-2 rounded-lg border-l-4 ${colorClass} transition-all cursor-pointer z-10 overflow-hidden shadow-lg backdrop-blur-md flex flex-col group hover:z-20 hover:scale-[1.02]" 
                                 style="top: ${topPx+2}px; height: ${heightPx-4}px;" onclick="${b.type === 'class' ? `window.location.href='class_view.php?id=${b.id}'` : `window.showToast('${b.title}')`}">
                                <h4 class="text-[11px] font-black text-white italic uppercase tracking-tighter truncate leading-none group-hover:text-white transition-colors">${b.title}</h4>
                                <span class="text-[8px] font-bold text-white/50 uppercase mt-0.5 mb-0.5">${formatStandardTime(b.startTime)}</span>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest truncate italic flex items-center gap-1 opacity-70"><i data-feather="${textIcon}" class="w-3 h-3"></i> ${b.subtitle}</p>
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
                         title: c.className, subtitle: c.sectionCode,
                         startTime: c.startTime, endTime: c.endTime
                     });
                 }
            });

            // Filter Events
            currentEvents.filter(e => e.dateStr === dateStr).forEach(e => {
                 res.push({
                     type: 'event', id: e.id,
                     title: e.title, subtitle: 'Personal Event',
                     startTime: e.startTime, endTime: e.endTime
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
            
            // Calculate days
            let firstDay = new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth(), 1);
            let offset = firstDay.getDay() - 1; if(offset < 0) offset = 6; // Mon is 0
            
            let daysInMonth = new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth()+1, 0).getDate();
            let prevDaysInMonth = new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth(), 0).getDate();

            let html = ``;
            // Prev month blanks
            for(let i=0; i<offset; i++) {
                html += `<div class="p-1.5 text-gray-700 opacity-50">${prevDaysInMonth - offset + 1 + i}</div>`;
            }
            // Current month days
            const today = new Date();
            for(let i=1; i<=daysInMonth; i++) {
                let buildDate = new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth(), i);
                const isSelected = buildDate.getTime() === activeDate.getTime();
                const isToday = buildDate.setHours(0,0,0,0) === today.setHours(0,0,0,0);
                
                const dObj = new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth(), i);
                const dIso = getLocalIsoStr(dObj);

                // Check active dots (Is there event/class?)
                let eventDots = ``;
                if(generateBlocksForDay(buildDate.getDay(), dIso).length > 0) {
                     eventDots = `<span class="absolute bottom-1 right-1 w-1.5 h-1.5 bg-blue-500 rounded-full shadow-lg"></span>`;
                }

                if(isSelected) {
                    html += `<div class="p-1.5 bg-primary-500 text-white rounded-lg shadow-lg shadow-primary-500/20 font-black transform scale-110 italic transition-transform cursor-pointer relative" onclick="selectAgendaDate('${dIso}')">${i}${eventDots}</div>`;
                } else if(isToday) {
                    html += `<div class="p-1.5 text-primary-400 hover:bg-white/5 rounded-lg font-black italic cursor-pointer relative" onclick="selectAgendaDate('${dIso}')">${i}${eventDots}</div>`;
                } else {
                    html += `<div class="p-1.5 text-gray-400 hover:bg-white/5 hover:text-white rounded-lg cursor-pointer transition-colors relative" onclick="selectAgendaDate('${dIso}')">${i}${eventDots}</div>`;
                }
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
            
            // Sort by time
            blocks.sort((a,b) => parseTimeStr(a.startTime) - parseTimeStr(b.startTime));
            
            badge.innerText = `${blocks.length} SCHEDULES`;
            
            if(blocks.length === 0) {
                 list.innerHTML = `<div class="text-center py-10 opacity-30"><i data-feather="calendar" class="w-10 h-10 mx-auto mb-3"></i><p class="text-[10px] font-black uppercase tracking-widest italic text-gray-400">Timeline Clear</p></div>`;
                 feather.replace(); return;
            }

            const currentActualTime = new Date().getHours() + (new Date().getMinutes()/60);

            list.innerHTML = blocks.map(b => {
                 const sT = parseTimeStr(b.startTime);
                 const eT = parseTimeStr(b.endTime);
                 
                 let state = 'Upcoming'; let stateClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20'; let lineClass = 'bg-dark-border'; let iconCol='gray-500';
                 if(isToday) {
                     if(currentActualTime >= sT && currentActualTime < eT) {
                         state = 'Active Now'; stateClass = 'bg-primary-500 text-white border-primary-500 glow-pulse'; lineClass = 'bg-primary-500/50'; iconCol='primary-500 shadow-[0_0_10px_rgba(234,38,40,0.6)] animate-pulse';
                     } else if (currentActualTime >= eT) {
                         state = 'Concluded'; stateClass = 'bg-green-500/10 text-green-400 border-green-500/20'; iconCol='blue-500/40 opacity-50'; lineClass = 'bg-dark-border opacity-50';
                     }
                 }

                 const cardAlpha = state === 'Concluded' ? 'opacity-50 grayscale hover:grayscale-0' : (state === 'Active Now' ? 'border-primary-500/30 bg-primary-500/5' : 'hover:border-white/10');
                 const titleSt = state === 'Concluded' ? 'line-through' : '';
                 const iconStr = b.type === 'class' ? 'map-pin' : 'file-text';

                 return `
                    <div class="relative flex items-start gap-4 pb-6 group">
                        <div class="flex flex-col items-center">
                            <div class="text-[9px] font-black ${state==='Active Now'?'text-primary-400':'text-gray-500'} w-14 text-right uppercase tracking-widest italic pt-1">${formatStandardTime(b.startTime)}</div>
                            <div class="w-3 h-3 rounded-full bg-${iconCol} border-[3px] border-dark-bg z-10 -ml-0 absolute top-5 right-[calc(100%-3rem-0.3rem)]"></div>
                            <div class="w-px h-full absolute top-[28px] left-[calc(3rem)] ${lineClass} group-last:hidden"></div>
                        </div>
                        <div class="flex-1 glass-panel rounded-2xl p-5 border border-dark-border ${cardAlpha} transition-all cursor-pointer shadow-lg shadow-black/20 hover:-translate-y-1" onclick="${b.type === 'class' ? `window.location.href='class_view.php?id=${b.id}'` : `window.showToast('${b.title}')`}">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="text-sm font-black text-white uppercase tracking-tighter italic ${titleSt}">${b.title}</h4>
                                <span class="text-[9px] font-black uppercase tracking-widest italic px-2 py-0.5 rounded border ${stateClass}">${state}</span>
                            </div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest flex items-center gap-1.5 italic opacity-80">
                                <i data-feather="${iconStr}" class="w-3 h-3"></i> ${b.subtitle}
                            </p>
                        </div>
                    </div>`;
            }).join('');
            feather.replace();
        }

    </script>
</body>
</html>