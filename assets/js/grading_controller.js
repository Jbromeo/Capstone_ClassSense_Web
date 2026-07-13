class GradingSystem {
    constructor(classId) {
        this.classId = classId;
        this.quarter = '1st';
        this.students = [];
        this.config = {
            written: { weight: 30, items: [] },
            performance: { weight: 50, items: [] },
            exam: { weight: 20, items: [] },
            attendance: { weight: 0, items: [] }
        };
        this.scores = {};
        this.isDirty = false;
        this.pollInterval = null;
        this.debounceTimer = null;
        this.attendanceRates = {};
    }

    async init(studentList) {
        this.students = studentList;
        await this.initialLoad();
        this.render();
        this.updateSaveStatus('Loaded');
        this.startPolling();
        this.setupKeyboardNavigation();
        this.setupBeforeUnload();
        this.updateMetaBar();
        await this.fetchAllAttendanceRates();
    }

    async initialLoad() {
        try {
            const data = await window.api(`/grading.php?class_id=${this.classId}&quarter=${this.quarter}`);
            if (data.config) {
                this.config.written = data.config.written || { weight: 30, items: [] };
                this.config.performance = data.config.performance || { weight: 50, items: [] };
                this.config.exam = data.config.exam || { weight: 20, items: [] };
                this.config.attendance = data.config.attendance || { weight: 0, items: [] };
            }
            if (data.scores) this.scores = data.scores;
        } catch (e) {
            console.warn('Grading initial load error:', e);
        }
    }

    async fetchAllAttendanceRates() {
        if (!this.classId || !this.students.length) return;
        const results = await Promise.allSettled(
            this.students.map(s =>
                window.api(`/attendance_rate.php?class_id=${this.classId}&student_uid=${s.uid}`)
                    .then(r => ({ uid: s.uid, rate: r.rate }))
                    .catch(() => ({ uid: s.uid, rate: null }))
            )
        );
        results.forEach(r => {
            if (r.status === 'fulfilled') this.attendanceRates[r.value.uid] = r.value.rate;
        });
        this.updateAllAttendanceInUI();
    }

    async fetchSingleAttendanceRate(studentUid) {
        try {
            const r = await window.api(`/attendance_rate.php?class_id=${this.classId}&student_uid=${studentUid}`);
            this.attendanceRates[studentUid] = r.rate;
        } catch (e) {
            this.attendanceRates[studentUid] = null;
        }
    }

    updateAllAttendanceInUI() {
        this.students.forEach(s => {
            const cell = document.getElementById(`percent-attendance-${s.uid}`);
            const rate = this.attendanceRates[s.uid];
            if (cell) cell.innerText = rate !== null && rate !== undefined ? rate.toFixed(1) + '%' : '--';
        });
        this.students.forEach(s => this.calculateRow(s.uid));
    }

    updateMetaBar() {
        ['written', 'performance', 'exam', 'attendance'].forEach(cat => {
            const el = document.getElementById(`metaWeight-${cat}`);
            if (el) el.innerText = `${this.config[cat].weight}% Weight`;
        });
    }

    populateWeightModal() {
        ['written', 'performance', 'exam', 'attendance'].forEach(cat => {
            const el = document.getElementById(`weight-${cat}`);
            if (el) el.value = this.config[cat].weight;
        });
    }

    async loadData() {
        if (this.isDirty) return;
        try {
            const data = await window.api(`/grading.php?class_id=${this.classId}&quarter=${this.quarter}`);
            if (this.isDirty) return;
            if (data.config) {
                const curStr = JSON.stringify(this.config);
                const srvStr = JSON.stringify(data.config);
                this.config.written = data.config.written || { weight: 30, items: [] };
                this.config.performance = data.config.performance || { weight: 50, items: [] };
                this.config.exam = data.config.exam || { weight: 20, items: [] };
                this.config.attendance = data.config.attendance || { weight: 0, items: [] };
                if (curStr !== JSON.stringify(this.config)) this.render();
            }
        } catch (e) {
            console.warn('Grading load error:', e);
        }
    }

    startPolling() {
        if (this.pollInterval) clearInterval(this.pollInterval);
        this.pollInterval = setInterval(() => this.loadData(), 10000);
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    async addComponent(category, name, hps) {
        const itemId = `${category}_${Date.now()}`;
        this.config[category].items.push({ id: itemId, name, hps: parseInt(hps) });
        await this.syncConfig();
        this.render();
    }

    async deleteComponent(category, itemId) {
        this.config[category].items = this.config[category].items.filter(i => i.id !== itemId);
        Object.keys(this.scores).forEach(studentUid => {
            if (this.scores[studentUid][itemId]) delete this.scores[studentUid][itemId];
        });
        await this.syncAll();
        this.render();
    }

    async syncConfig() {
        this.isDirty = true;
        await this.saveToServer();
    }

    async syncAll() {
        this.isDirty = true;
        await this.saveToServer();
    }

    async saveToServer() {
        try {
            this.updateSaveStatus('Saving...');
            await window.api(`/grading.php`, {
                method: 'PUT',
                body: JSON.stringify({
                    class_id: this.classId,
                    quarter: this.quarter,
                    config: this.config,
                    scores: this.scores
                })
            });
            this.isDirty = false;
            this.updateSaveStatus('All changes saved');
            this.updateMetaBar();
            document.getElementById('gradeSaveBtn')?.classList.add('opacity-50');
            document.querySelectorAll('.grade-input.text-primary-400').forEach(el => {
                el.classList.remove('text-primary-400');
                el.classList.add('text-green-400');
            });
        } catch (e) {
            this.updateSaveStatus('Save error');
            console.error('Save Error:', e);
            if (window.showToast) window.showToast('Failed to save grades', 'error');
        }
    }

    markDirty() {
        this.isDirty = true;
        const btn = document.getElementById('gradeSaveBtn');
        if (btn) btn.classList.remove('opacity-50');
        this.updateSaveStatus('Unsaved changes');
    }

    autoSave() {
        if (this.debounceTimer) clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            if (this.isDirty) this.saveToServer();
        }, 2000);
    }

    setupBeforeUnload() {
        window.addEventListener('beforeunload', (e) => {
            if (this.isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    updateSaveStatus(text) {
        const el = document.getElementById('gradeSaveStatus');
        if (el) el.innerText = text;
    }

    updateAllScoresInUI() {
        this.students.forEach(student => {
            const studentUid = student.uid;
            const studentScores = this.scores[studentUid] || {};
            let rowNeedsUpdate = false;
            Object.keys(studentScores).forEach(itemId => {
                const input = document.getElementById(`input-${studentUid}-${itemId}`);
                if (input && document.activeElement !== input) {
                    const val = studentScores[itemId] ?? '';
                    if (input.value !== String(val)) {
                        input.value = val;
                        rowNeedsUpdate = true;
                    }
                }
            });
            if (rowNeedsUpdate) this.calculateRow(studentUid);
        });
    }

    handleScoreChange(studentUid, itemId, value) {
        const input = document.getElementById(`input-${studentUid}-${itemId}`);
        const category = itemId.split('_')[0];
        const item = this.config[category].items.find(i => i.id === itemId);
        if (!item) return;

        let numericVal = value === '' ? null : parseFloat(value);

        if (numericVal !== null) {
            if (numericVal < 0) { numericVal = 0; if (input) input.value = 0; }
            if (numericVal > item.hps) {
                if (input) {
                    input.classList.add('border-primary-500', 'bg-primary-500/10', 'animate-shake');
                    setTimeout(() => input.classList.remove('animate-shake'), 500);
                    numericVal = item.hps;
                    input.value = item.hps;
                    if (window.showToast) window.showToast(`Score capped at ${item.hps} (Max HPS)`, 'error');
                }
            } else {
                if (input) input.classList.remove('border-primary-500', 'bg-primary-500/10');
            }
        }

        if (!this.scores[studentUid]) this.scores[studentUid] = {};
        if (this.scores[studentUid][itemId] === numericVal) return;

        this.scores[studentUid][itemId] = numericVal;
        this.calculateRow(studentUid);

        if (input) {
            input.classList.remove('text-green-400');
            input.classList.add('text-primary-400');
        }

        this.markDirty();
        this.autoSave();
    }

    calculateRow(studentUid) {
        const studentScores = this.scores[studentUid] || {};
        const categories = ['written', 'performance', 'exam', 'attendance'];
        let initialGrade = 0;

        categories.forEach(cat => {
            const catConfig = this.config[cat];
            let catTotal = 0;
            let catHPS = 0;

            if (cat === 'attendance') {
                // Auto-calculated from attendance records
                const rate = this.attendanceRates[studentUid];
                const percentage = rate !== null && rate !== undefined ? rate : 0;
                initialGrade += (percentage * (catConfig.weight / 100));
                const cell = document.getElementById(`percent-attendance-${studentUid}`);
                if (cell) cell.innerText = rate !== null && rate !== undefined ? percentage.toFixed(1) + '%' : '--';
                return;
            }

            catConfig.items.forEach(item => {
                catTotal += studentScores[item.id] || 0;
                catHPS += item.hps || 0;
            });
            const percentage = catHPS > 0 ? (catTotal / catHPS) * 100 : 0;
            initialGrade += (percentage * (catConfig.weight / 100));
            const cell = document.getElementById(`percent-${cat}-${studentUid}`);
            if (cell) cell.innerText = percentage.toFixed(1) + '%';
        });

        const transmuted = this.transmute(initialGrade);
        const initialCell = document.getElementById(`initial-${studentUid}`);
        const finalCell = document.getElementById(`final-${studentUid}`);
        if (initialCell) initialCell.innerText = initialGrade.toFixed(2);
        if (finalCell) {
            finalCell.innerText = transmuted;
            finalCell.className = `p-4 text-center font-black sticky right-0 bg-dark-bg z-10 ${transmuted >= 75 ? 'text-green-400' : 'text-primary-500'}`;
        }
    }

    transmute(score) {
        if (score >= 100) return 100;
        if (score >= 98.4) return 99;
        if (score >= 96.8) return 98;
        if (score >= 95.2) return 97;
        if (score >= 93.6) return 96;
        if (score >= 92.0) return 95;
        if (score >= 90.4) return 94;
        if (score >= 88.8) return 93;
        if (score >= 87.2) return 92;
        if (score >= 85.6) return 91;
        if (score >= 84.0) return 90;
        if (score >= 82.4) return 89;
        if (score >= 80.8) return 88;
        if (score >= 79.2) return 87;
        if (score >= 77.6) return 86;
        if (score >= 76.0) return 85;
        if (score >= 74.4) return 84;
        if (score >= 72.8) return 83;
        if (score >= 71.2) return 82;
        if (score >= 69.6) return 81;
        if (score >= 68.0) return 80;
        if (score >= 66.4) return 79;
        if (score >= 64.8) return 78;
        if (score >= 63.2) return 77;
        if (score >= 61.6) return 76;
        if (score >= 60.0) return 75;
        if (score >= 56.0) return 74;
        if (score >= 52.0) return 73;
        if (score >= 48.0) return 72;
        if (score >= 44.0) return 71;
        if (score >= 40.0) return 70;
        return 60;
    }

    render() {
        const container = document.getElementById('gradingTableContainer');
        if (!container) return;

        const categories = [
            { id: 'written', name: 'Written Works', color: 'blue' },
            { id: 'performance', name: 'Performance Tasks', color: 'purple' },
            { id: 'exam', name: 'Quarterly Exam', color: 'green' },
            { id: 'attendance', name: 'Attendance', color: 'orange' }
        ];

        let html = `
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <span id="gradeSaveStatus" class="text-[10px] font-bold text-gray-500 italic">Loaded</span>
                    <button id="gradeSaveBtn" onclick="window.gradingSystem.saveToServer()" class="opacity-50 px-4 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-[10px] font-black uppercase tracking-widest italic rounded-lg transition-all shadow-lg shadow-primary-500/20">Save Changes</button>
                </div>
            </div>
            <table class="w-full text-left text-[11px] border-collapse spreadsheet-table">
                <thead>
                    <tr class="bg-dark-bg/80 backdrop-blur-md">
                        <th class="p-4 bg-dark-surface sticky left-0 top-0 z-30 border-b border-r border-dark-border min-w-[200px]">
                            <div class="flex items-center justify-between">
                                <span class="font-black uppercase tracking-widest text-primary-500 italic">Student Name</span>
                            </div>
                        </th>
                        ${categories.map(cat => {
                            const count = cat.id === 'attendance' ? 2 : this.config[cat.id].items.length + 1;
                            return `<th colspan="${count}" class="p-3 text-center border-b border-r border-dark-border border-t-4 border-t-${cat.color}-500 bg-${cat.color}-500/5 sticky top-0 z-20">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="font-black uppercase tracking-widest text-${cat.color}-400">${cat.name} (${this.config[cat.id].weight}%)</span>
                                    <button onclick="window.gradingSystem.openAddComponent('${cat.id}')" class="w-4 h-4 rounded bg-${cat.color}-500/20 text-${cat.color}-400 hover:bg-${cat.color}-500 hover:text-white transition-all flex items-center justify-center"><i data-feather="plus" class="w-3 h-3"></i></button>
                                </div>
                            </th>`;
                        }).join('')}
                        <th rowspan="3" class="p-4 text-center border-b border-dark-border bg-primary-500/10 sticky right-0 top-0 z-30 min-w-[100px]">
                            <div class="font-black uppercase tracking-widest text-white leading-tight">Final<br>Grade</div>
                        </th>
                    </tr>
                    <tr class="bg-dark-bg/60">
                        <th class="p-2 bg-dark-bg sticky left-0 top-[52px] z-30 border-b border-r border-dark-border text-gray-500 font-bold italic text-center uppercase tracking-widest">Item Name</th>
                        ${categories.map(cat => {
                            if (cat.id === 'attendance') {
                                return `<th class="p-2 text-center border-b border-r border-dark-border min-w-[100px] bg-dark-surface/50 sticky top-[52px] z-20">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-white font-bold">Attendance Rate</span>
                                        <span class="text-gray-400 italic text-[9px]">auto-calculated</span>
                                    </div>
                                </th>
                                <th class="p-2 text-center border-b border-r border-dark-border min-w-[70px] bg-dark-surface/80 text-gray-400 font-black italic sticky top-[52px] z-20">SCORE %</th>`;
                            }
                            const items = this.config[cat.id].items;
                            return `${items.map(item => `
                                <th class="p-2 text-center border-b border-r border-dark-border min-w-[80px] bg-dark-surface/50 group sticky top-[52px] z-20">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-white truncate max-w-[70px]" title="${item.name}">${item.name}</span>
                                        <button onclick="window.gradingSystem.deleteComponent('${cat.id}', '${item.id}')" class="opacity-0 group-hover:opacity-100 text-[8px] text-red-500 hover:underline">Remove</button>
                                    </div>
                                </th>`).join('')}
                                <th class="p-2 text-center border-b border-r border-dark-border min-w-[70px] bg-dark-surface/80 text-gray-400 font-black italic sticky top-[52px] z-20">SCORE %</th>`;
                        }).join('')}
                    </tr>
                    <tr class="bg-primary-500/5">
                        <th class="p-2 bg-dark-bg sticky left-0 top-[96px] z-30 border-b-2 border-r border-dark-border text-primary-500 font-black italic text-center uppercase tracking-widest">HPS (Max)</th>
                        ${categories.map(cat => {
                            if (cat.id === 'attendance') {
                                return `<th class="p-2 text-center border-b-2 border-r border-dark-border font-black text-white bg-white/5 sticky top-[96px] z-20">100</th>
                                <th class="p-2 text-center border-b-2 border-r border-dark-border font-black text-gray-500 italic sticky top-[96px] z-20">100%</th>`;
                            }
                            const items = this.config[cat.id].items;
                            return `${items.map(item => `
                                <th class="p-2 text-center border-b-2 border-r border-dark-border font-black text-white bg-white/5 sticky top-[96px] z-20">${item.hps}</th>`).join('')}
                                <th class="p-2 text-center border-b-2 border-r border-dark-border font-black text-gray-500 italic sticky top-[96px] z-20">100%</th>`;
                        }).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${this.students.map((student, sIdx) => {
                        const studentUid = student.uid;
                        const studentScores = this.scores[studentUid] || {};
                        return `
                            <tr class="border-b border-dark-border hover:bg-white/5 transition-colors group">
                                <td class="p-3 bg-dark-bg sticky left-0 z-10 border-r border-dark-border">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded bg-dark-surface border border-white/5 flex items-center justify-center text-[9px] font-black text-gray-500">${sIdx + 1}</div>
                                        <div>
                                            <div class="text-white font-bold uppercase tracking-tight italic truncate max-w-[150px]">${student.firstName} ${student.lastName}</div>
                                            <div class="text-[8px] text-gray-500 font-bold tracking-widest">${student.studentId || 'N/A'}</div>
                                        </div>
                                    </div>
                                </td>
                                ${categories.map(cat => {
                                    if (cat.id === 'attendance') {
                                        const rate = this.attendanceRates[studentUid];
                                        const display = rate !== null && rate !== undefined ? rate.toFixed(1) + '%' : '--';
                                        return `<td class="p-1 border-r border-dark-border text-center font-bold text-white bg-orange-500/5">${display}</td>
                                            <td id="percent-attendance-${studentUid}" class="p-1 border-r border-dark-border text-center font-black text-gray-500 italic bg-white/5">${display}</td>`;
                                    }
                                    const items = this.config[cat.id].items;
                                    let catTotal = 0;
                                    let catHPS = 0;
                                    const scoreCells = items.map(item => {
                                        const score = studentScores[item.id] ?? '';
                                        catTotal += parseFloat(score) || 0;
                                        catHPS += item.hps || 0;
                                        return `<td class="p-1 border-r border-dark-border text-center">
                                            <input type="number"
                                                id="input-${studentUid}-${item.id}"
                                                class="grade-input w-full bg-transparent border-0 text-center py-1.5 text-white focus:bg-white/10 outline-none transition-all rounded"
                                                value="${score}"
                                                min="0" max="${item.hps}"
                                                placeholder="-"
                                                oninput="window.gradingSystem.handleScoreChange('${studentUid}', '${item.id}', this.value)"
                                                onkeydown="window.gradingSystem.handleNavigation(event)">
                                        </td>`;
                                    }).join('');
                                    const percentage = catHPS > 0 ? (catTotal / catHPS) * 100 : 0;
                                    return `${scoreCells}
                                        <td id="percent-${cat.id}-${studentUid}" class="p-1 border-r border-dark-border text-center font-black text-gray-500 italic bg-white/5">${percentage.toFixed(1)}%</td>`;
                                }).join('')}
                                <td id="final-${studentUid}" class="p-4 text-center font-black sticky right-0 bg-dark-bg z-10 border-l border-dark-border shadow-[-10px_0_15px_rgba(0,0,0,0.5)]">-</td>
                            </tr>`;
                    }).join('')}
                </tbody>
            </table>`;

        container.innerHTML = html;
        feather.replace();
        this.students.forEach(s => this.calculateRow(s.uid));
        this.updateMetaBar();
        const btn = document.getElementById('gradeSaveBtn');
        if (btn && this.isDirty) btn.classList.remove('opacity-50');
        else if (btn) btn.classList.add('opacity-50');
    }

    setupKeyboardNavigation() {}

    handleNavigation(e) {
        const currentInput = e.target;
        const allInputs = Array.from(document.querySelectorAll('.grade-input'));
        const currentIndex = allInputs.indexOf(currentInput);
        const itemsPerRow = this.config.written.items.length + this.config.performance.items.length + this.config.exam.items.length;
        let target;
        if (e.key === 'ArrowDown' || e.key === 'Enter') { e.preventDefault(); target = allInputs[currentIndex + itemsPerRow]; }
        else if (e.key === 'ArrowUp') { e.preventDefault(); target = allInputs[currentIndex - itemsPerRow]; }
        else if (e.key === 'ArrowRight' && currentInput.selectionStart === currentInput.value.length) { target = allInputs[currentIndex + 1]; }
        else if (e.key === 'ArrowLeft' && currentInput.selectionStart === 0) { target = allInputs[currentIndex - 1]; }
        if (target) { target.focus(); target.select(); }
    }

    exportToCSV() {
        let csv = "Student Name,Student ID,";
        const categories = ['written', 'performance', 'exam', 'attendance'];
        categories.forEach(cat => {
            if (cat === 'attendance') {
                csv += `Attendance Rate (100),ATTENDANCE %,`;
                return;
            }
            this.config[cat].items.forEach(item => { csv += `${item.name} (Max ${item.hps}),`; });
            csv += `${cat.toUpperCase()} %,`;
        });
        csv += "Initial Grade,Final Grade\n";
        this.students.forEach(student => {
            const studentUid = student.uid;
            const studentScores = this.scores[studentUid] || {};
            csv += `"${student.firstName} ${student.lastName}",${student.studentId || ''},`;
            let initialGrade = 0;
            categories.forEach(cat => {
                if (cat === 'attendance') {
                    const rate = this.attendanceRates[studentUid];
                    const attPct = rate !== null && rate !== undefined ? rate : 0;
                    initialGrade += (attPct * (this.config[cat].weight / 100));
                    csv += `${attPct.toFixed(1)},${attPct.toFixed(1)}%,`;
                    return;
                }
                let catTotal = 0;
                let catHPS = 0;
                this.config[cat].items.forEach(item => {
                    const s = studentScores[item.id] || 0;
                    csv += `${s},`;
                    catTotal += s;
                    catHPS += item.hps;
                });
                const percentage = catHPS > 0 ? (catTotal / catHPS) * 100 : 0;
                initialGrade += (percentage * (this.config[cat].weight / 100));
                csv += `${percentage.toFixed(2)}%,`;
            });
            csv += `${initialGrade.toFixed(2)},${this.transmute(initialGrade)}\n`;
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', `GradingSheet_${this.classId}_${this.quarter}.csv`);
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    openAddComponent(category) {
        document.getElementById('addCompCategory').value = category;
        const catName = { written: 'Written Work', performance: 'Performance Task', exam: 'Quarterly Exam', attendance: 'Attendance' }[category];
        document.getElementById('addCompModalTitle').innerText = `Add ${catName}`;
        window.openModal('addComponentModal');
    }
}

export { GradingSystem };
