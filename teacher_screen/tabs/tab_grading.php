<!-- teacher_screen/tabs/tab_grading.php -->
<div id="tab-grading" class="tab-content hidden h-full flex flex-col transition-all animate-fade-in animate-scale-in">

    <!-- Top Controls Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-primary-500/10 rounded-lg text-primary-500 shadow-lg shadow-primary-500/20"><i data-feather="bar-chart-2" class="w-5 h-5"></i></div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-black text-white uppercase italic tracking-tighter">Grading Center</h2>
                    <span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest bg-green-500/10 text-green-400 border border-green-500/20 italic">Live</span>
                </div>
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest italic mt-0.5">Auto-Saving Enabled &bull; Local Storage</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 bg-dark-bg/80 backdrop-blur-xl border border-white/5 p-1 rounded-2xl shadow-xl">
            <button onclick="window.gradingSystem.setQuarter(1)" id="q-1st" class="quarter-btn active px-4 py-2 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all bg-primary-600 text-white shadow-lg shadow-primary-500/20">1st Quarter</button>
            <button onclick="window.gradingSystem.setQuarter(2)" id="q-2nd" class="quarter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all text-gray-500 hover:text-white hover:bg-white/5">2nd Quarter</button>
            <button onclick="window.gradingSystem.setQuarter(3)" id="q-3rd" class="quarter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all text-gray-500 hover:text-white hover:bg-white/5">3rd Quarter</button>
            <button onclick="window.gradingSystem.setQuarter(4)" id="q-4th" class="quarter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all text-gray-500 hover:text-white hover:bg-white/5">4th Quarter</button>
        </div>

        <div class="flex gap-2">
            <button onclick="window.gradingSystem.exportToExcel()" class="flex items-center group gap-2 bg-dark-bg hover:bg-white/5 border border-white/5 text-gray-300 px-4 py-2.5 rounded-xl text-[10px] font-black transition-all uppercase italic tracking-[0.2em] shadow-xl">
                <i data-feather="download" class="w-4 h-4 text-green-500 group-hover:scale-125 transition-transform"></i> Export CSV
            </button>
            <button onclick="window.gradingSystem.openWeights()" class="flex items-center group gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2.5 rounded-xl text-[10px] font-black transition-all shadow-xl shadow-primary-500/20 uppercase italic tracking-[0.2em] active:scale-95">
                <i data-feather="settings" class="w-4 h-4 group-hover:rotate-180 transition-transform duration-700"></i> Weights
            </button>
        </div>
    </div>

    <!-- Weight Meta Bar -->
    <div class="flex items-center gap-4 md:gap-6 px-3 md:px-6 py-2.5 bg-dark-bg/80 backdrop-blur-xl border border-white/5 rounded-2xl mb-4 overflow-x-auto shrink-0" id="metaWeightsBar">
        <div class="flex items-center gap-2 shrink-0">
            <div class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.5)] shrink-0"></div>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-black text-blue-400 uppercase italic tracking-tighter leading-none">Written Works</p>
                <p id="metaWeight-written" class="text-[8px] text-gray-500 font-bold uppercase mt-0.5">30% Weight</p>
            </div>
        </div>
        <div class="w-px h-8 bg-white/5 shrink-0"></div>
        <div class="flex items-center gap-2 shrink-0">
            <div class="w-2.5 h-2.5 rounded-full bg-purple-500 shadow-[0_0_10px_rgba(168,85,247,0.5)] shrink-0"></div>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-black text-purple-400 uppercase italic tracking-tighter leading-none">Performance Tasks</p>
                <p id="metaWeight-performance" class="text-[8px] text-gray-500 font-bold uppercase mt-0.5">50% Weight</p>
            </div>
        </div>
        <div class="w-px h-8 bg-white/5 shrink-0"></div>
        <div class="flex items-center gap-2 shrink-0">
            <div class="w-2.5 h-2.5 rounded-full bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.5)] shrink-0"></div>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-black text-green-400 uppercase italic tracking-tighter leading-none">Quarterly Exam</p>
                <p id="metaWeight-exam" class="text-[8px] text-gray-500 font-bold uppercase mt-0.5">20% Weight</p>
            </div>
        </div>
        <div class="w-px h-8 bg-white/5 shrink-0"></div>
        <div class="flex items-center gap-2 shrink-0">
            <div class="w-2.5 h-2.5 rounded-full bg-orange-500 shadow-[0_0_10px_rgba(251,146,60,0.5)] shrink-0"></div>
            <div class="whitespace-nowrap">
                <p class="text-[10px] font-black text-orange-400 uppercase italic tracking-tighter leading-none">Attendance</p>
                <p id="metaWeight-attendance" class="text-[8px] text-gray-500 font-bold uppercase mt-0.5">0% Weight</p>
            </div>
        </div>
    </div>

    <!-- Spreadsheet Container -->
    <div class="flex-1 min-h-0 bg-dark-bg/80 backdrop-blur-2xl border border-white/5 rounded-3xl overflow-hidden relative shadow-2xl flex flex-col group/table">

        <!-- Student Count & Add Component Bar -->
        <div class="px-4 md:px-6 py-3 border-b border-white/5 bg-white/5 flex items-center justify-between gap-4 shrink-0 flex-wrap">
            <div class="flex items-center gap-2">
                <i data-feather="users" class="w-4 h-4 text-gray-500"></i>
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic"><span id="rosterCountGrading">0</span> Students Enrolled <span id="rosterQuarterLabel" class="text-primary-500">• 1st Quarter</span></span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button onclick="window.gradingSystem.openAddComponent('written')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/20 text-[9px] font-black uppercase tracking-widest italic transition-all"><i data-feather="plus" class="w-3 h-3"></i> Written</button>
                <button onclick="window.gradingSystem.openAddComponent('performance')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-500/10 text-purple-400 hover:bg-purple-500/20 border border-purple-500/20 text-[9px] font-black uppercase tracking-widest italic transition-all"><i data-feather="plus" class="w-3 h-3"></i> Performance</button>
                <button onclick="window.gradingSystem.openAddComponent('exam')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/20 text-[9px] font-black uppercase tracking-widest italic transition-all"><i data-feather="plus" class="w-3 h-3"></i> Exam</button>
                <button onclick="window.gradingSystem.openAddComponent('attendance')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-500/10 text-orange-400 hover:bg-orange-500/20 border border-orange-500/20 text-[9px] font-black uppercase tracking-widest italic transition-all"><i data-feather="plus" class="w-3 h-3"></i> Attendance</button>
            </div>
        </div>

        <!-- Table Scroll Area -->
        <div id="gradingTableContainer" class="flex-1 overflow-auto custom-scrollbar relative bg-[#0a0c10]/40">

            <!-- Empty State -->
            <div class="h-full flex flex-col items-center justify-center gap-8 p-8" id="gradingEmptyState">
                <div class="relative">
                    <div class="w-24 h-24 rounded-3xl bg-primary-500/5 border border-primary-500/10 flex items-center justify-center">
                        <i data-feather="grid" class="w-12 h-12 text-primary-500/40"></i>
                    </div>
                    <div class="absolute -inset-4 bg-primary-600/5 blur-3xl rounded-full"></div>
                </div>
                <div class="text-center max-w-xs">
                    <p class="text-[9px] font-black text-primary-500 uppercase tracking-[0.3em] italic mb-2" id="gradingEmptyQuarter">1st Quarter</p>
                    <p class="text-sm font-black text-white uppercase tracking-tight italic">No Grading Components Yet</p>
                    <p class="text-[10px] text-gray-500 font-bold mt-2 leading-relaxed tracking-widest italic">Add Written Works, Performance Tasks, Exams, or Attendance columns to start grading your students.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="window.gradingSystem.openAddComponent('written')" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-black uppercase tracking-widest italic shadow-lg shadow-blue-500/20 transition-all active:scale-95"><i data-feather="book" class="w-4 h-4"></i> Add Written Works</button>
                    <button onclick="window.gradingSystem.openAddComponent('performance')" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-[10px] font-black uppercase tracking-widest italic shadow-lg shadow-purple-500/20 transition-all active:scale-95"><i data-feather="zap" class="w-4 h-4"></i> Add Tasks</button>
                </div>
            </div>

            <!-- Live Table -->
            <table id="gradingTable" class="w-full table-fixed text-left text-[11px] border-collapse spreadsheet-table hidden">
                <colgroup id="gradingColgroup"></colgroup>
                <thead id="gradingThead"></thead>
                <tbody id="gradingTbody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
window.gradingSystem = (() => {
    const CATEGORIES = {
        written: { label: 'Written Works', color: 'blue', weightKey: 'written', icon: 'book', modalTitle: 'Add Written Work Component' },
        performance: { label: 'Performance Tasks', color: 'purple', weightKey: 'performance', icon: 'zap', modalTitle: 'Add Performance Task Component' },
        exam: { label: 'Quarterly Exam', color: 'green', weightKey: 'exam', icon: 'clock', modalTitle: 'Add Quarterly Exam Component' },
        attendance: { label: 'Attendance', color: 'orange', weightKey: 'attendance', icon: 'check-square', modalTitle: 'Add Attendance Component' }
    };

    let state = {
        classId: null,
        quarter: 1,
        students: [],
        components: [],
        grades: {},
        weights: { written: 30, performance: 50, exam: 20, attendance: 0 },
        saveTimers: {}
    };

    function storageKey() {
        return `cs_grades_${state.classId}_q${state.quarter}`;
    }

    function weightsKey() {
        return `cs_grades_${state.classId}_weights`;
    }

    function defaultWeights() {
        return { written: 30, performance: 50, exam: 20, attendance: 0 };
    }

    function saveToLocal() {
        try {
            const data = { components: state.components, grades: state.grades };
            localStorage.setItem(storageKey(), JSON.stringify(data));
        } catch(e) {}
    }

    function loadFromLocal() {
        try {
            const raw = localStorage.getItem(storageKey());
            if (raw) {
                const data = JSON.parse(raw);
                state.components = data.components || [];
                state.grades = data.grades || {};
            } else {
                state.components = [];
                state.grades = {};
            }
            let w = JSON.parse(localStorage.getItem(weightsKey()) || 'null');
            if (!w || typeof w !== 'object') {
                try {
                    const q1 = JSON.parse(localStorage.getItem(`cs_grades_${state.classId}_q1`) || 'null');
                    w = (q1 && q1.weights) ? q1.weights : defaultWeights();
                    localStorage.setItem(weightsKey(), JSON.stringify(w));
                } catch(e) {
                    w = defaultWeights();
                }
            }
            state.weights = { ...defaultWeights(), ...w };
        } catch(e) {
            state.components = [];
            state.grades = {};
            state.weights = defaultWeights();
        }
    }

    function getGrade(componentId, studentUid) {
        return state.grades[componentId]?.[studentUid] ?? null;
    }

    function setGrade(componentId, studentUid, score) {
        if (!state.grades[componentId]) state.grades[componentId] = {};
        if (score === null || score === '') {
            delete state.grades[componentId][studentUid];
        } else {
            state.grades[componentId][studentUid] = parseFloat(score);
        }
        saveToLocal();
        renderTable();
    }

    function computeCategoryAverage(studentUid, category) {
        const comps = state.components.filter(c => c.category === category);
        if (comps.length === 0) return null;
        let totalScore = 0;
        let totalHps = 0;
        comps.forEach(c => {
            const s = getGrade(c.id, studentUid);
            if (s !== null && s !== undefined) {
                totalScore += s;
                totalHps += c.hps;
            }
        });
        if (totalHps === 0) return null;
        return (totalScore / totalHps) * 100;
    }

    function computeAttendanceScore(studentUid) {
        const attComps = state.components.filter(c => c.category === 'attendance');
        if (attComps.length === 0) return null;
        let totalScore = 0;
        let totalHps = 0;
        attComps.forEach(c => {
            const s = getGrade(c.id, studentUid);
            if (s !== null && s !== undefined) {
                totalScore += s;
                totalHps += c.hps;
            }
        });
        if (totalHps === 0) return null;
        return (totalScore / totalHps) * 100;
    }

    function computeFinalGrade(studentUid) {
        let total = 0;
        let totalWeight = 0;
        ['written', 'performance', 'exam', 'attendance'].forEach(cat => {
            const avg = computeCategoryAverage(studentUid, cat);
            const weight = state.weights[cat] || 0;
            if (avg !== null && weight > 0) {
                total += avg * (weight / 100);
                totalWeight += weight;
            }
        });
        if (totalWeight === 0) return null;
        return total;
    }

    function calcCategoryAverage(comps, grades, studentUid, category) {
        const catComps = comps.filter(c => c.category === category);
        if (catComps.length === 0) return null;
        let totalScore = 0;
        let totalHps = 0;
        catComps.forEach(c => {
            const s = grades[c.id]?.[studentUid];
            if (s !== null && s !== undefined) {
                totalScore += s;
                totalHps += c.hps;
            }
        });
        if (totalHps === 0) return null;
        return (totalScore / totalHps) * 100;
    }

    function calcFinalGrade(comps, grades, studentUid, weights) {
        let total = 0;
        let totalWeight = 0;
        ['written', 'performance', 'exam', 'attendance'].forEach(cat => {
            const avg = calcCategoryAverage(comps, grades, studentUid, cat);
            const weight = weights[cat] || 0;
            if (avg !== null && weight > 0) {
                total += avg * (weight / 100);
                totalWeight += weight;
            }
        });
        if (totalWeight === 0) return null;
        return total;
    }

    function formatScore(val, decimals = 1) {
        if (val === null || val === undefined) return '—';
        return val.toFixed(decimals);
    }

    function quarterName() {
        return ({1: '1st Quarter', 2: '2nd Quarter', 3: '3rd Quarter', 4: '4th Quarter'}[state.quarter] || '1st Quarter');
    }

    function renderTable() {
        const table = document.getElementById('gradingTable');
        const empty = document.getElementById('gradingEmptyState');
        const thead = document.getElementById('gradingThead');
        const tbody = document.getElementById('gradingTbody');
        const rosterCount = document.getElementById('rosterCountGrading');
        if (rosterCount) rosterCount.textContent = state.students.length;
        const qLabel = document.getElementById('rosterQuarterLabel');
        if (qLabel) qLabel.textContent = `• ${quarterName()}`;
        const emptyQ = document.getElementById('gradingEmptyQuarter');
        if (emptyQ) emptyQ.textContent = quarterName();

        if (state.components.length === 0) {
            table.classList.add('hidden');
            empty.classList.remove('hidden');
            return;
        }

        table.classList.remove('hidden');
        empty.classList.add('hidden');

        const cats = ['written', 'performance', 'exam', 'attendance'];
        const compsByCat = {};
        cats.forEach(c => { compsByCat[c] = state.components.filter(cp => cp.category === c); });
        const hasAttendance = compsByCat.attendance.length > 0;

        let colgroupHtml = '<col style="width:220px">';
        cats.forEach(cat => {
            const comps = compsByCat[cat];
            comps.forEach(() => { colgroupHtml += '<col style="width:110px">'; });
            if (comps.length > 0) colgroupHtml += '<col style="width:80px">';
        });
        colgroupHtml += '<col style="width:110px">';
        document.getElementById('gradingColgroup').innerHTML = colgroupHtml;

        let hpsRow = '<tr class="bg-primary-500/5"><th class="p-2 bg-dark-bg sticky left-0 top-[96px] z-30 border-b-2 border-r border-dark-border text-primary-500 font-black italic text-center uppercase tracking-widest text-[10px]">HPS (Max)</th>';

        cats.forEach(cat => {
            const comps = compsByCat[cat];
            comps.forEach(c => {
                hpsRow += `<th class="p-2 text-center border-b-2 border-r border-dark-border font-black text-white bg-white/5 sticky top-[96px] z-20 text-[10px]">${c.hps}</th>`;
            });
            if (comps.length > 0) {
                hpsRow += `<th class="p-2 text-center border-b-2 border-r border-dark-border font-black text-gray-500 italic bg-white/5 sticky top-[96px] z-20 text-[10px]">Score %</th>`;
            }
        });
        hpsRow += '<th class="p-2 text-center border-b-2 border-r border-dark-border bg-dark-bg sticky top-[96px] z-20 min-w-[90px]"></th></tr>';

        let headerRows = '';
        cats.forEach(cat => {
            const comps = compsByCat[cat];
            const info = CATEGORIES[cat];
            const colCount = comps.length > 0 ? comps.length + 1 : 0;
            if (colCount === 0) return;
            const bColor = info.color === 'blue' ? 'border-t-blue-500 bg-blue-500/5' :
                           info.color === 'purple' ? 'border-t-purple-500 bg-purple-500/5' :
                           info.color === 'green' ? 'border-t-green-500 bg-green-500/5' :
                           'border-t-orange-500 bg-orange-500/5';
            headerRows += `<th colspan="${colCount}" class="p-3 text-center border-b border-r border-dark-border border-t-4 ${bColor} sticky top-0 z-20">
                <div class="flex items-center justify-center gap-2">
                    <span class="font-black uppercase tracking-widest text-${info.color}-400 text-[10px]">${info.label} (${state.weights[info.weightKey]}%)</span>
                    <button onclick="window.gradingSystem.openAddComponent('${cat}')" class="w-4 h-4 rounded bg-${info.color}-500/20 text-${info.color}-400 hover:bg-${info.color}-500 hover:text-white transition-all flex items-center justify-center"><i data-feather="plus" class="w-3 h-3"></i></button>
                </div>
            </th>`;
        });
        headerRows += `<th class="p-4 text-center border-b border-dark-border bg-dark-bg sticky top-0 z-20">
            <div class="font-black uppercase tracking-widest text-white leading-tight text-[10px]">Final<br>Grade</div>
        </th>`;

        let itemRow = '<tr class="bg-dark-bg/60"><th class="p-2 bg-dark-bg sticky left-0 top-[52px] z-30 border-b border-r border-dark-border text-gray-500 font-bold italic text-center uppercase tracking-widest text-[10px]">Component</th>';
        cats.forEach(cat => {
            const comps = compsByCat[cat];
            comps.forEach(c => {
                itemRow += `<th class="p-2 text-center border-b border-r border-dark-border min-w-[110px] bg-dark-surface/50 group sticky top-[52px] z-20"><div class="flex flex-col items-center gap-1"><span class="text-white font-bold text-[10px] truncate max-w-[100px]">${c.name}</span><button onclick="window.gradingSystem.deleteComponent(${c.id})" class="opacity-0 group-hover:opacity-100 text-[8px] text-red-500 hover:underline">Remove</button></div></th>`;
            });
            if (comps.length > 0) {
                itemRow += `<th class="p-2 text-center border-b border-r border-dark-border min-w-[70px] bg-dark-surface/80 text-gray-400 font-black italic sticky top-[52px] z-20 text-[10px]">Score %</th>`;
            }
        });
        itemRow += '<th class="p-2 bg-dark-bg sticky top-[52px] z-20 border-b border-r border-dark-border"></th></tr>';

        const theadHtml = `<tr class="bg-dark-bg/80 backdrop-blur-md">
            <th class="p-4 bg-dark-surface sticky left-0 top-0 z-30 border-b border-r border-dark-border min-w-[200px]">
                <div class="flex items-center justify-between">
                    <span class="font-black uppercase tracking-widest text-primary-500 italic text-[10px]">Student</span>
                </div>
            </th>
            ${headerRows}
        </tr>${itemRow}${hpsRow}`;

        thead.innerHTML = theadHtml;

        let tbodyHtml = '';
        if (state.students.length === 0) {
            tbodyHtml = `<tr><td colspan="20" class="p-16 text-center"><p class="text-[11px] font-black text-gray-500 uppercase tracking-widest italic">No students enrolled in this class</p></td></tr>`;
        } else {
            function studentAvatar(s) {
                const initials = ((s.firstName?.[0] || '') + (s.lastName?.[0] || '')).toUpperCase() || 'ST';
                if (s.profilePicture && s.profilePicture !== '' && !s.profilePicture.includes('ui-avatars')) {
                    return `<img src="${s.profilePicture}" alt="" class="w-8 h-8 rounded-xl object-cover border border-white/5 shrink-0">`;
                }
                if (s.profile_picture && s.profile_picture !== '' && !s.profile_picture.includes('ui-avatars')) {
                    return `<img src="${s.profile_picture}" alt="" class="w-8 h-8 rounded-xl object-cover border border-white/5 shrink-0">`;
                }
                return `<img src="https://ui-avatars.com/api/?name=${initials}&background=ea2628&color=fff&bold=true" alt="" class="w-8 h-8 rounded-xl object-cover border border-white/5 shrink-0">`;
            }

            state.students.forEach((s, idx) => {
                const finalGrade = computeFinalGrade(s.uid);
                const isLow = finalGrade !== null && finalGrade < 75;
                tbodyHtml += `<tr class="border-b border-dark-border hover:bg-white/[0.03] transition-colors group ${isLow ? 'bg-red-500/[0.04]' : ''}">
                    <td class="p-3 bg-dark-bg sticky left-0 z-10 border-r border-dark-border">
                        <div class="flex items-center gap-3">
                            ${studentAvatar(s)}
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-white uppercase tracking-tight truncate max-w-[160px]">${s.firstName || ''} ${s.lastName || 'Unknown'}</div>
                                <div class="text-[8px] text-gray-500 font-bold tracking-widest">${s.studentId || ''}</div>
                            </div>
                        </div>
                    </td>`;

                cats.forEach(cat => {
                    const comps = compsByCat[cat];
                    comps.forEach(c => {
                        const val = getGrade(c.id, s.uid);
                        const hasVal = val !== null && val !== undefined;
                        tbodyHtml += `<td class="p-1 border-r border-dark-border text-center">
                            <input type="number" class="grade-input w-full max-w-[76px] bg-transparent border-0 text-center py-1.5 text-white focus:bg-white/[0.06] focus:ring-1 focus:ring-primary-500/30 outline-none transition-all rounded-lg text-xs font-bold ${hasVal ? '' : 'text-gray-600'}" value="${hasVal ? val : ''}" min="0" max="${c.hps}" placeholder="-" data-cid="${c.id}" data-uid="${s.uid}">
                        </td>`;
                    });
                    if (comps.length > 0) {
                        const avg = computeCategoryAverage(s.uid, cat);
                        const avgColor = avg !== null ? (avg >= 75 ? 'text-green-400' : 'text-red-400') : 'text-gray-600';
                        tbodyHtml += `<td class="p-1 border-r border-dark-border text-center font-black italic bg-white/[0.02] ${avgColor} text-[10px]">${formatScore(avg)}%</td>`;
                    }
                });

                const fgColor = finalGrade !== null ? (finalGrade >= 75 ? 'text-green-400' : 'text-red-400') : 'text-gray-500';
                tbodyHtml += `<td class="p-4 text-center font-black bg-primary-500/5 ${fgColor} text-sm">${formatScore(finalGrade)}</td>`;
                tbodyHtml += '</tr>';
            });
        }

        tbody.innerHTML = tbodyHtml;

        tbody.querySelectorAll('.grade-input').forEach(inp => {
            const cid = parseInt(inp.dataset.cid);
            const uid = inp.dataset.uid;
            const max = parseInt(inp.max);

            inp.addEventListener('input', () => {
                let val = inp.value.trim();
                if (val === '') { setGrade(cid, uid, null); return; }
                let num = parseFloat(val);
                if (isNaN(num)) return;
                if (num > max) { inp.value = max; num = max; }
                if (num < 0) { inp.value = 0; num = 0; }
                if (state.saveTimers[`${cid}_${uid}`]) clearTimeout(state.saveTimers[`${cid}_${uid}`]);
                state.saveTimers[`${cid}_${uid}`] = setTimeout(() => setGrade(cid, uid, num), 400);
            });

            inp.addEventListener('blur', () => {
                let val = inp.value.trim();
                if (val === '') { setGrade(cid, uid, null); return; }
                let num = parseFloat(val);
                if (!isNaN(num)) setGrade(cid, uid, num);
            });
        });

        try { feather.replace(); } catch(e) {}
    }

    function updateMetaWeights() {
        ['written', 'performance', 'exam', 'attendance'].forEach(k => {
            const el = document.getElementById(`metaWeight-${k}`);
            if (el) el.textContent = `${state.weights[k]}% Weight`;
        });
    }

    return {
        init(classId, students) {
            state.classId = classId;
            state.students = students || [];
            loadFromLocal();
            const q = parseInt(sessionStorage.getItem(`cs_grading_quarter_${classId}`) || '1');
            state.quarter = q;
            document.querySelectorAll('.quarter-btn').forEach(b => b.classList.remove('active', 'bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20'));
            const names = {1:'1st',2:'2nd',3:'3rd',4:'4th'};
            const activeBtn = document.getElementById(`q-${names[q]}`);
            if (activeBtn) activeBtn.classList.add('active', 'bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20');
            updateMetaWeights();
            renderTable();
        },

        setQuarter(q) {
            state.quarter = q;
            sessionStorage.setItem(`cs_grading_quarter_${state.classId}`, q);
            loadFromLocal();
            document.querySelectorAll('.quarter-btn').forEach(b => b.classList.remove('active', 'bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20'));
            const names = {1:'1st',2:'2nd',3:'3rd',4:'4th'};
            const activeBtn = document.getElementById(`q-${names[q]}`);
            if (activeBtn) activeBtn.classList.add('active', 'bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20');
            updateMetaWeights();
            renderTable();
        },

        openAddComponent(category) {
            const info = CATEGORIES[category];
            document.getElementById('addCompCategory').value = category;
            document.getElementById('addCompModalTitle').textContent = info.modalTitle;
            document.getElementById('addCompName').value = '';
            document.getElementById('addCompHps').value = '50';
            window.openModal('addComponentModal');
        },

        addComponent(category, name, hps) {
            const catInfo = CATEGORIES[category];
            const newComp = {
                id: Date.now() + Math.floor(Math.random() * 1000),
                category: category,
                name: name,
                hps: parseInt(hps) || 50,
                quarter: state.quarter
            };
            state.components.push(newComp);
            saveToLocal();
            renderTable();
            window.showToast(`Added "${name}" to ${catInfo.label}`, 'success');
        },

        deleteComponent(componentId) {
            if (!confirm('Remove this component and all its scores?')) return;
            const comp = state.components.find(c => c.id === componentId);
            state.components = state.components.filter(c => c.id !== componentId);
            delete state.grades[componentId];
            saveToLocal();
            renderTable();
            if (comp) window.showToast(`Removed "${comp.name}"`, 'info');
        },

        openWeights() {
            document.getElementById('weight-written').value = state.weights.written || 0;
            document.getElementById('weight-performance').value = state.weights.performance || 0;
            document.getElementById('weight-exam').value = state.weights.exam || 0;
            document.getElementById('weight-attendance').value = state.weights.attendance || 0;
            window.updateWeightTotal();
            window.openModal('weightConfigModal');
        },

        saveWeights(weights) {
            state.weights = { ...state.weights, ...weights };
            try { localStorage.setItem(weightsKey(), JSON.stringify(state.weights)); } catch(e) {}
            updateMetaWeights();
            renderTable();
        },

        exportToExcel() {
            const rows = [];
            const cats = ['written', 'performance', 'exam', 'attendance'];
            const qNames = {1: '1st Quarter', 2: '2nd Quarter', 3: '3rd Quarter', 4: '4th Quarter'};
            let weights = defaultWeights();
            try { weights = { ...defaultWeights(), ...(JSON.parse(localStorage.getItem(weightsKey()) || 'null') || {}) }; } catch(e) {}

            for (let q = 1; q <= 4; q++) {
                let comps = [], grades = {};
                if (q === state.quarter) {
                    comps = state.components;
                    grades = state.grades;
                } else {
                    try {
                        const d = JSON.parse(localStorage.getItem(`cs_grades_${state.classId}_q${q}`) || 'null');
                        comps = (d && d.components) || [];
                        grades = (d && d.grades) || {};
                    } catch(e) {}
                }

                rows.push([qNames[q]]);
                if (comps.length === 0) {
                    rows.push(['No grading data for this quarter']);
                    rows.push([]);
                    continue;
                }

                let header = ['#', 'Student Name', 'Student ID'];
                cats.forEach(cat => {
                    const cc = comps.filter(c => c.category === cat);
                    cc.forEach(c => header.push(`${c.name} (${cat})`));
                    if (cc.length > 0) header.push(`${CATEGORIES[cat].label} %`);
                });
                header.push('Final Grade');
                rows.push(header);

                state.students.forEach((s, idx) => {
                    const row = [idx + 1, `${s.firstName || ''} ${s.lastName || ''}`.trim(), s.studentId || ''];
                    cats.forEach(cat => {
                        const cc = comps.filter(c => c.category === cat);
                        cc.forEach(c => {
                            const g = grades[c.id]?.[s.uid];
                            row.push(g !== null && g !== undefined ? g : '');
                        });
                        if (cc.length > 0) {
                            const avg = calcCategoryAverage(comps, grades, s.uid, cat);
                            row.push(avg !== null ? avg.toFixed(1) + '%' : '');
                        }
                    });
                    const fg = calcFinalGrade(comps, grades, s.uid, weights);
                    row.push(fg !== null ? fg.toFixed(1) : '');
                    rows.push(row);
                });
                rows.push([]);
            }

            const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `grades_all-quarters_${state.classId}.csv`;
            a.click();
            URL.revokeObjectURL(url);
            window.showToast('All quarters exported as CSV', 'success');
        }
    };
})();
</script>