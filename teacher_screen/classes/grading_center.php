<!-- teacher_screen/classes/grading_center.php -->
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
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest italic mt-0.5">Term-Based Grading &bull; Summative Test 1 &bull; Summative Test 2 &bull; Term Exam</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 bg-dark-bg/80 backdrop-blur-xl border border-white/5 p-1 rounded-2xl shadow-xl">
            <button onclick="window.gradingSystem.setTerm(1)" id="t-1st" class="term-btn active px-4 py-2 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all bg-primary-600 text-white shadow-lg shadow-primary-500/20">1st Term</button>
            <button onclick="window.gradingSystem.setTerm(2)" id="t-2nd" class="term-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all text-gray-500 hover:text-white hover:bg-white/5">2nd Term</button>
            <button onclick="window.gradingSystem.setTerm(3)" id="t-3rd" class="term-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all text-gray-500 hover:text-white hover:bg-white/5">3rd Term</button>
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
                <p class="text-[10px] font-black text-green-400 uppercase italic tracking-tighter leading-none">Examinations</p>
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

    <!-- Exam Add Dropdown -->
    <div id="examAddDropdown" class="hidden fixed z-[60] w-64 rounded-xl bg-dark-surface/95 backdrop-blur-xl border border-white/10 shadow-2xl overflow-hidden" style="right:-9999px;top:-9999px;">
        <div class="px-4 py-2.5 border-b border-white/5 text-[8px] font-black uppercase tracking-widest text-gray-400 italic">Examination</div>
        <div id="examAddDropdownOptions" class="py-1"></div>
    </div>

    <!-- Spreadsheet Container -->
    <div class="flex-1 min-h-0 bg-dark-bg/80 backdrop-blur-2xl border border-white/5 rounded-3xl overflow-hidden relative shadow-2xl flex flex-col group/table">

        <!-- Student Count & Add Component Bar -->
        <div class="px-4 md:px-6 py-3 border-b border-white/5 bg-white/5 flex items-center justify-between gap-4 shrink-0 flex-wrap">
            <div class="flex items-center gap-2">
                <i data-feather="users" class="w-4 h-4 text-gray-500"></i>
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic"><span id="rosterCountGrading">0</span> Students Enrolled <span id="rosterTermLabel" class="text-primary-500">• 1st Term</span></span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button onclick="window.gradingSystem.openAddComponent('written')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/20 text-[9px] font-black uppercase tracking-widest italic transition-all"><i data-feather="plus" class="w-3 h-3"></i> Written</button>
                <button onclick="window.gradingSystem.openAddComponent('performance')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-500/10 text-purple-400 hover:bg-purple-500/20 border border-purple-500/20 text-[9px] font-black uppercase tracking-widest italic transition-all"><i data-feather="plus" class="w-3 h-3"></i> Performance</button>
                <button onclick="window.gradingSystem.openAddComponent('attendance')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-500/10 text-orange-400 hover:bg-orange-500/20 border border-orange-500/20 text-[9px] font-black uppercase tracking-widest italic transition-all"><i data-feather="plus" class="w-3 h-3"></i> Attendance</button>
                <button onclick="window.gradingSystem.openExamDropdown(event)" class="btn-add-exam-dd flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/20 text-[9px] font-black uppercase tracking-widest italic transition-all"><i data-feather="plus" class="w-3 h-3"></i> Examination</button>
            </div>
        </div>

        <!-- Table Scroll Area -->
        <div id="gradingTableContainer" class="flex-1 overflow-auto custom-scrollbar relative bg-dark-bg/40">

            <!-- Empty State -->
            <div class="h-full flex flex-col items-center justify-center gap-8 p-8" id="gradingEmptyState">
                <div class="relative">
                    <div class="w-24 h-24 rounded-3xl bg-primary-500/5 border border-primary-500/10 flex items-center justify-center">
                        <i data-feather="grid" class="w-12 h-12 text-primary-500/40"></i>
                    </div>
                    <div class="absolute -inset-4 bg-primary-600/5 blur-3xl rounded-full"></div>
                </div>
                <div class="text-center max-w-xs">
                    <p class="text-[9px] font-black text-primary-500 uppercase tracking-[0.3em] italic mb-2" id="gradingEmptyTerm">1st Term</p>
                    <p class="text-sm font-black text-white uppercase tracking-tight italic">No Students Enrolled Yet</p>
                    <p class="text-[10px] text-gray-500 font-bold mt-2 leading-relaxed tracking-widest italic">Enroll students into this class to start recording their scores. Each term records Written Works, Performance Tasks, Summative Test 1, Summative Test 2, and Term Exam.</p>
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
        exam: { label: 'Examinations', color: 'green', weightKey: 'exam', icon: 'clock', modalTitle: 'Add Examination Component' },
        attendance: { label: 'Attendance', color: 'orange', weightKey: 'attendance', icon: 'check-square', modalTitle: 'Add Attendance Component' }
    };
    const FIXED_EXAMS = [
        { key: 'st1', label: 'Summative Test 1' },
        { key: 'st2', label: 'Summative Test 2' },
        { key: 'term', label: 'Term Exam' },
    ];
    const DEFAULT_WEIGHTS = { written: 0, performance: 0, exam: 0, attendance: 0 };

    let state = {
        classId: null,
        term: 1,
        students: [],
        components: [],
        grades: {},
        weights: { ...DEFAULT_WEIGHTS },
        saveTimers: {}
    };

    async function loadFromServer() {
        try {
            const data = await window.api(`/grades.php?class_id=${state.classId}&quarter=${state.term}`);
            state.components = (data.components || []).map(c => ({ id: c.id, category: c.category, name: c.name, hps: Number(c.hps) }));
            state.grades = {};
            Object.keys(data.grades || {}).forEach(compId => {
                state.grades[compId] = {};
                Object.keys(data.grades[compId] || {}).forEach(uid => {
                    const score = Number.parseFloat(data.grades[compId][uid]);
                    if (!Number.isNaN(score)) state.grades[compId][uid] = score;
                });
            });
            state.weights = { ...DEFAULT_WEIGHTS, ...(data.weights || {}) };
        } catch (e) {
            console.error('Grade sheet load failed:', e);
            // Keep the last-known-good state on a transient failure so a
            // background poll never blanks the sheet.
        }
    }

    async function persistGrade(compId, studentUid, score) {
        try {
            await window.api('/grades.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'save_score',
                    component_id: compId,
                    student_uid: studentUid,
                    score: score === null || score === '' ? null : parseFloat(score)
                })
            });
        } catch (e) {
            console.error('Grade save failed:', e);
            window.showToast('Grade save failed', 'error');
        }
    }

    function termName() {
        return ({1: '1st Term', 2: '2nd Term', 3: '3rd Term'}[state.term] || '1st Term');
    }

    function highlightTermBtn() {
        document.querySelectorAll('.term-btn').forEach(b => b.classList.remove('active', 'bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20'));
        const names = {1:'1st',2:'2nd',3:'3rd'};
        const btn = document.getElementById(`t-${names[state.term]}`);
        if (btn) btn.classList.add('active', 'bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20');
    }

    function compsByCategory(category) {
        return state.components.filter(c => c.category === category);
    }

    function getGrade(compId, studentUid) {
        return state.grades[compId]?.[studentUid] ?? null;
    }

    function setGrade(compId, studentUid, score) {
        if (!state.grades[compId]) state.grades[compId] = {};
        if (score === null || score === '') {
            delete state.grades[compId][studentUid];
        } else {
            state.grades[compId][studentUid] = parseFloat(score);
        }
        renderTable();
        const key = `${compId}_${studentUid}`;
        if (state.saveTimers[key]) clearTimeout(state.saveTimers[key]);
        state.saveTimers[key] = setTimeout(() => persistGrade(compId, studentUid, score), 400);
    }

    function computeCategoryAverage(studentUid, category) {
        const comps = compsByCategory(category);
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

    function formatScore(val, decimals = 1) {
        if (val === null || val === undefined) return '—';
        const n = Number(val);
        if (isNaN(n)) return '—';
        return n.toFixed(decimals);
    }

    function updateMetaWeights() {
        ['written', 'performance', 'exam', 'attendance'].forEach(k => {
            const el = document.getElementById(`metaWeight-${k}`);
            if (el) el.textContent = `${state.weights[k]}% Weight`;
        });
    }

    function weightsConfigured() {
        const total = ['written', 'performance', 'exam', 'attendance'].reduce((sum, k) => sum + (state.weights[k] || 0), 0);
        return total === 100;
    }

    function renderTable() {
        const table = document.getElementById('gradingTable');
        const empty = document.getElementById('gradingEmptyState');
        const thead = document.getElementById('gradingThead');
        const tbody = document.getElementById('gradingTbody');
        const rosterCount = document.getElementById('rosterCountGrading');
        if (rosterCount) rosterCount.textContent = state.students.length;
        const tLabel = document.getElementById('rosterTermLabel');
        if (tLabel) tLabel.textContent = `• ${termName()}`;
        const emptyT = document.getElementById('gradingEmptyTerm');
        if (emptyT) emptyT.textContent = termName();

        if (state.students.length === 0) {
            table.classList.add('hidden');
            empty.classList.remove('hidden');
            return;
        }

        table.classList.remove('hidden');
        empty.classList.add('hidden');

        const cats = ['written', 'performance', 'exam', 'attendance'];
        const compsByCat = {};
        cats.forEach(c => { compsByCat[c] = compsByCategory(c); });

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
                    ${cat === 'exam'
                        ? `<button onclick="window.gradingSystem.openExamDropdown(event)" class="btn-add-exam-dd w-4 h-4 rounded bg-${info.color}-500/20 text-${info.color}-400 hover:bg-${info.color}-500 hover:text-white transition-all flex items-center justify-center"><i data-feather="plus" class="w-3 h-3"></i></button>`
                        : `<button onclick="window.gradingSystem.openAddComponent('${cat}')" class="w-4 h-4 rounded bg-${info.color}-500/20 text-${info.color}-400 hover:bg-${info.color}-500 hover:text-white transition-all flex items-center justify-center"><i data-feather="plus" class="w-3 h-3"></i></button>`}
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
                itemRow += `<th class="p-2 text-center border-b border-r border-dark-border min-w-[110px] bg-dark-surface/50 group sticky top-[52px] z-20"><div class="flex flex-col items-center gap-1"><span class="text-white font-bold text-[10px] truncate max-w-[100px]">${c.name}</span><button onclick="window.gradingSystem.deleteComponent('${c.id}')" class="opacity-0 group-hover:opacity-100 text-[8px] text-red-500 hover:underline">Remove</button></div></th>`;
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
        state.students.forEach(s => {
            const finalGrade = computeFinalGrade(s.uid);
            const isLow = finalGrade !== null && finalGrade < 75;
            tbodyHtml += `<tr class="border-b border-dark-border hover:bg-white/[0.03] transition-colors group ${isLow ? 'bg-red-500/[0.04]' : ''}">
                <td class="p-3 bg-dark-bg sticky left-0 z-10 border-r border-dark-border">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-600 to-primary-900 flex items-center justify-center text-white font-black text-xs border border-white/10 uppercase italic">
                            ${(s.firstName?.[0] || '') + (s.lastName?.[0] || '')}
                        </div>
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
                    if (cat === 'attendance') {
                        let badge = '—', badgeCls = 'text-gray-600 bg-white/5 border-white/10', tip = 'No record';
                        if (val === 10) { badge = 'P'; badgeCls = 'text-green-400 bg-green-500/10 border-green-500/30'; tip = 'Present — 10/10'; }
                        else if (val === 5) { badge = 'L'; badgeCls = 'text-orange-400 bg-orange-500/10 border-orange-500/30'; tip = 'Late — 5/10'; }
                        else if (val === 0) { badge = 'A'; badgeCls = 'text-red-400 bg-red-500/10 border-red-500/30'; tip = 'Absent — 0/10'; }
                        else if (val !== null && val !== undefined) { badge = formatScore(val); badgeCls = 'text-gray-400 bg-white/5 border-white/10'; tip = `Score — ${val}/${c.hps}`; }
                        tbodyHtml += `<td class="p-1 border-r border-dark-border text-center"><span title="${tip}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border font-black italic text-[10px] ${badgeCls}">${badge}</span></td>`;
                    } else {
                        const hasVal = val !== null && val !== undefined;
                        tbodyHtml += `<td class="p-1 border-r border-dark-border text-center">
                            <input type="number" class="grade-input w-full max-w-[76px] bg-transparent border-0 text-center py-1.5 text-white focus:bg-white/[0.06] focus:ring-1 focus:ring-primary-500/30 outline-none transition-all rounded-lg text-xs font-bold ${hasVal ? '' : 'text-gray-600'}" value="${hasVal ? val : ''}" min="0" max="${c.hps}" placeholder="-" data-cid="${c.id}" data-uid="${s.uid}">
                        </td>`;
                    }
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

        tbody.innerHTML = tbodyHtml;

        tbody.querySelectorAll('.grade-input').forEach(inp => {
            const cid = inp.dataset.cid;
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

    function calcCategoryAvg(comps, grades, studentUid, hps) {
        if (comps.length === 0) return null;
        let totalScore = 0;
        let totalHps = 0;
        comps.forEach(c => {
            const s = grades[c.id]?.[studentUid];
            if (s !== null && s !== undefined) {
                totalScore += s;
                totalHps += c.hps;
            }
        });
        if (totalHps === 0) return null;
        return (totalScore / totalHps) * 100;
    }

    function closeExamDropdown() {
        const dd = document.getElementById('examAddDropdown');
        if (dd) dd.classList.add('hidden');
    }

    function openExamDropdown(ev) {
        if (ev) ev.stopPropagation();
        const dd = document.getElementById('examAddDropdown');
        if (!dd) return;
        if (!dd.classList.contains('hidden')) { closeExamDropdown(); return; }
        const list = document.getElementById('examAddDropdownOptions');
        list.innerHTML = '';
        FIXED_EXAMS.forEach(fx => {
            const opt = document.createElement('button');
            opt.type = 'button';
            opt.className = 'w-full text-left px-4 py-2.5 hover:bg-white/5 text-[10px] font-black text-white uppercase italic tracking-wider flex items-center gap-2 transition-colors';
            opt.innerHTML = `<i data-feather="file-text" class="w-3 h-3 text-green-400"></i>${fx.label}`;
            opt.addEventListener('click', () => {
                closeExamDropdown();
                window.gradingSystem.openAddComponent('exam', fx.label);
            });
            list.appendChild(opt);
        });
        try { feather.replace(); } catch(e) {}
        dd.classList.remove('hidden');
        const rect = ev.currentTarget.getBoundingClientRect();
        dd.style.top = (rect.bottom + 8) + 'px';
        dd.style.right = (window.innerWidth - rect.right) + 'px';
    }

    document.addEventListener('click', (e) => {
        const dd = document.getElementById('examAddDropdown');
        if (!dd || dd.classList.contains('hidden')) return;
        if (!dd.contains(e.target) && !e.target.closest('.btn-add-exam-dd')) closeExamDropdown();
    });

    return {
        async init(classId, students) {
            state.classId = classId;
            state.students = students || [];
            state.components = [];
            state.grades = {};
            state.weights = { ...DEFAULT_WEIGHTS };
            const t = parseInt(sessionStorage.getItem(`cs_grading_term_${classId}`) || '1');
            state.term = Math.min(3, Math.max(1, t));
            highlightTermBtn();
            await loadFromServer();
            updateMetaWeights();
            renderTable();
        },

        async refresh() {
            // Realtime refresh straight from the SQL database for the current
            // term. Skipped while the teacher is typing in a cell so a poll
            // never clobbers an in-progress edit (saves are debounced anyway).
            if (!state.classId) return;
            const editing = document.querySelector('.grade-input:focus');
            if (editing) return;
            await loadFromServer();
            updateMetaWeights();
            renderTable();
        },

        async setTerm(t) {
            state.term = t;
            sessionStorage.setItem(`cs_grading_term_${state.classId}`, t);
            highlightTermBtn();
            await loadFromServer();
            updateMetaWeights();
            renderTable();
        },

        openAddComponent(category, presetName) {
            if (!weightsConfigured()) {
                window.csWeightsAlert().then(openWeights => {
                    if (openWeights) this.openWeights();
                });
                return;
            }
            const info = CATEGORIES[category];
            document.getElementById('addCompCategory').value = category;
            document.getElementById('addCompModalTitle').textContent = info.modalTitle;
            document.getElementById('addCompName').value = presetName || '';
            document.getElementById('addCompHps').value = '50';
            window.openModal('addComponentModal');
        },

        async addComponent(category, name, hps) {
            const catInfo = CATEGORIES[category];
            try {
                const res = await window.api('/grades.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'save_component',
                        class_id: state.classId,
                        category: category,
                        name: name,
                        hps: parseInt(hps) || 50,
                        quarter: state.term
                    })
                });
                const comp = res.component || { id: Date.now(), category: category, name: name, hps: parseInt(hps) || 50 };
                state.components.push({ id: comp.id, category: comp.category, name: comp.name, hps: comp.hps });
                renderTable();
                window.showToast(`Added "${name}" to ${catInfo.label}`, 'success');
            } catch (e) {
                console.error('Add component failed:', e);
                window.showToast(`Failed to add "${name}"`, 'error');
            }
        },

        // Convert an attendance component's M/D/YY name (e.g. "8/14/26") to a
        // YYYY-MM-DD date for deleting matching attendance registry records.
        // Returns null when the name isn't a valid date (manual components are
        // left alone so only auto-created per-day columns cascade).
        attendanceDateFromName(name) {
            if (!name) return null;
            const m = String(name).trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/);
            if (!m) return null;
            const month = parseInt(m[1], 10), day = parseInt(m[2], 10), year = 2000 + parseInt(m[3], 10);
            return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        },

        deleteComponent(componentId) {
            const comp = state.components.find(c => String(c.id) === String(componentId));
            const scoreCount = comp ? Object.keys(state.grades[comp.id] || {}).length : 0;
            const isAttendance = !!(comp && comp.category === 'attendance');
            const attDate = isAttendance ? this.attendanceDateFromName(comp.name) : null;
            const attWarn = isAttendance && attDate
                ? ` This will ALSO permanently delete ALL attendance registry records for ${comp.name} in the database.`
                : '';
            window.csConfirm({
                title: 'Remove Component?',
                message: `Removing "${comp?.name || 'this component'}" will permanently delete all ${scoreCount} student score${scoreCount === 1 ? '' : 's'} for it.${attWarn} This cannot be undone.`,
                okText: 'Remove',
                danger: true
            }).then(async ok => {
                if (!ok) return;
                try {
                    if (isAttendance && attDate && state.classId) {
                        await window.api(`/attendance.php?class_id=${encodeURIComponent(state.classId)}&date=${attDate}`, { method: 'DELETE' });
                    }
                    await window.api(`/grades.php?component_id=${encodeURIComponent(componentId)}`, { method: 'DELETE' });
                } catch (e) {
                    window.showToast('Remove failed', 'error');
                    return;
                }
                state.components = state.components.filter(c => String(c.id) !== String(componentId));
                delete state.grades[componentId];
                renderTable();
                if (comp) window.showToast(
                    isAttendance && attDate ? `Removed "${comp.name}" and its attendance records` : `Removed "${comp.name}"`,
                    'info'
                );
            });
        },

        openWeights() {
            document.getElementById('weight-written').value = state.weights.written || 0;
            document.getElementById('weight-performance').value = state.weights.performance || 0;
            document.getElementById('weight-exam').value = state.weights.exam || 0;
            document.getElementById('weight-attendance').value = state.weights.attendance || 0;
            window.updateWeightTotal();
            window.openModal('weightConfigModal');
        },

        async saveWeights(weights) {
            state.weights = { ...state.weights, ...weights };
            updateMetaWeights();
            renderTable();
            try {
                await window.api('/grades.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'save_weights', class_id: state.classId, weights: state.weights })
                });
            } catch (e) {
                console.error('Weights save failed:', e);
                window.showToast('Weights save failed', 'error');
            }
        },

        openExamDropdown,
        closeExamDropdown,

        async exportToExcel() {
            const rows = [];
            const termNames = {1: '1st Term', 2: '2nd Term', 3: '3rd Term'};
            const sheets = {};

            try {
                for (let t = 1; t <= 3; t++) {
                    const data = await window.api(`/grades.php?class_id=${state.classId}&quarter=${t}`);
                    sheets[t] = {
                        components: data.components || [],
                        grades: data.grades || {},
                        weights: { ...DEFAULT_WEIGHTS, ...(data.weights || {}) }
                    };
                }
            } catch (e) {
                console.error('Export fetch failed:', e);
                window.showToast('Export failed', 'error');
                return;
            }

            for (let t = 1; t <= 3; t++) {
                const { components, grades, weights } = sheets[t];

                rows.push([termNames[t]]);
                const examComps = components.filter(c => c.category === 'exam');
                let header = ['#', 'Student Name', 'Student ID'];
                ['written', 'performance', 'exam', 'attendance'].forEach(cat => {
                    const cc = cat === 'exam' ? examComps : components.filter(c => c.category === cat);
                    cc.forEach(c => header.push(`${c.name} (${cat})`));
                    if (cc.length > 0) header.push(`${CATEGORIES[cat].label} %`);
                });
                header.push('Final Grade');
                rows.push(header);

                const hasAny = state.students.some(s =>
                    components.some(c => grades[c.id]?.[s.uid] !== undefined) ||
                    examComps.some(c => grades[c.id]?.[s.uid] !== undefined)
                );
                if (!hasAny) {
                    rows.push(['No grades recorded for this term']);
                    rows.push([]);
                    continue;
                }

                state.students.forEach((s, idx) => {
                    const row = [idx + 1, `${s.firstName || ''} ${s.lastName || ''}`.trim(), s.studentId || ''];
                    let total = 0, totalWeight = 0;
                    ['written', 'performance', 'exam', 'attendance'].forEach(cat => {
                        const cc = cat === 'exam' ? examComps : components.filter(c => c.category === cat);
                        if (cc.length === 0) return;
                        cc.forEach(c => {
                            const g = grades[c.id]?.[s.uid];
                            row.push(g !== undefined && g !== null ? g : '');
                        });
                        const avg = calcCategoryAvg(cc, grades, s.uid);
                        row.push(avg !== null ? avg.toFixed(1) + '%' : '');
                        if (avg !== null && weights[cat] > 0) {
                            total += avg * (weights[cat] / 100);
                            totalWeight += weights[cat];
                        }
                    });
                    row.push(totalWeight > 0 ? total.toFixed(1) : '');
                    rows.push(row);
                });
                rows.push([]);
            }

            rows.push(['Overall Term Average']);
            rows.push(['#', 'Student Name', 'Student ID', '1st Term', '2nd Term', '3rd Term', 'Overall']);
            state.students.forEach((s, idx) => {
                const termGrades = [];
                for (let t = 1; t <= 3; t++) {
                    const { components, grades, weights } = sheets[t];
                    if (components.length === 0) continue;
                    const examComps = components.filter(c => c.category === 'exam');
                    let total = 0, totalWeight = 0;
                    ['written', 'performance', 'exam', 'attendance'].forEach(cat => {
                        const cc = cat === 'exam' ? examComps : components.filter(c => c.category === cat);
                        const avg = calcCategoryAvg(cc, grades, s.uid);
                        if (avg !== null && weights[cat] > 0) {
                            total += avg * (weights[cat] / 100);
                            totalWeight += weights[cat];
                        }
                    });
                    if (totalWeight > 0) termGrades.push(total);
                }
                const overall = termGrades.length > 0 ? termGrades.reduce((a, b) => a + b, 0) / termGrades.length : null;
                const row = [idx + 1, `${s.firstName || ''} ${s.lastName || ''}`.trim(), s.studentId || ''];
                for (let t = 1; t <= 3; t++) {
                    row.push(termGrades[t - 1] !== undefined ? termGrades[t - 1].toFixed(1) : '');
                }
                row.push(overall !== null ? overall.toFixed(1) : '');
                rows.push(row);
            });

            const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `grades_all-terms_${state.classId}.csv`;
            a.click();
            URL.revokeObjectURL(url);
            window.showToast('All terms exported as CSV', 'success');
        }
    };
})();
</script>