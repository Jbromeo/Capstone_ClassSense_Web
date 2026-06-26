import { db } from './firebase-init.js';
import { doc, getDoc, setDoc, onSnapshot, updateDoc, serverTimestamp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

class GradingSystem {
    constructor(classId) {
        this.classId = classId;
        this.quarter = 'Q1'; 
        this.students = [];
        this.config = {
            written: { weight: 30, items: [] },
            performance: { weight: 50, items: [] },
            exam: { weight: 20, items: [] }
        };
        this.scores = {}; 
        this.unsubscribe = null;
        this.isSaving = false;
        this.saveTimeout = null;
        this.lastConfigHash = null; 
        this.validationTimeout = null;
    }

    async init(studentList) {
        // Only re-init if students changed
        const currentUids = this.students.map(s => s.uid).sort().join(',');
        const nextUids = studentList.map(s => s.uid).sort().join(',');
        
        if (currentUids !== nextUids) {
            this.students = studentList;
            if (this.lastConfigHash) {
                // If already rendered once, just update metadata or re-render structure if needed
                this.render();
            }
        }
        
        this.setupRealtimeListener();
        this.setupKeyboardNavigation();
    }

    setupRealtimeListener() {
        const path = `classes/${this.classId}/grading_sheets/${this.quarter}`;
        if (this.currentPath === path && this.unsubscribe) return;
        
        if (this.unsubscribe) this.unsubscribe();
        this.currentPath = path;
        
        const docRef = doc(db, "classes", this.classId, "grading_sheets", this.quarter);
        this.unsubscribe = onSnapshot(docRef, (snapshot) => {
            if (snapshot.exists()) {
                const data = snapshot.data();
                const newConfig = data.config || this.config;
                const newScores = data.scores || {};
                
                // Only re-render the whole table if columns changed
                const configHash = JSON.stringify(newConfig);
                const structureChanged = configHash !== this.lastConfigHash;
                
                this.config = newConfig;
                this.scores = newScores;
                this.lastConfigHash = configHash;

                if (structureChanged) {
                    // Safety check: Don't re-render whole table if teacher is typing!
                    if (document.activeElement && document.activeElement.classList.contains('grade-input')) {
                        // Just update what we can for now, postpone full render
                        this.updateAllScoresInUI();
                    } else {
                        this.render();
                    }
                } else {
                    this.updateAllScoresInUI();
                }
            } else {
                this.initializeDefaultSheet();
            }
        });
    }

    async initializeDefaultSheet() {
        const docRef = doc(db, "classes", this.classId, "grading_sheets", this.quarter);
        await setDoc(docRef, {
            config: this.config,
            scores: {},
            lastUpdated: serverTimestamp()
        });
    }

    async addComponent(category, name, hps) {
        const itemId = `${category}_${Date.now()}`;
        this.config[category].items.push({ id: itemId, name, hps: parseInt(hps) });
        await this.syncConfig();
    }

    async deleteComponent(category, itemId) {
        this.config[category].items = this.config[category].items.filter(i => i.id !== itemId);
        // Also cleanup scores
        Object.keys(this.scores).forEach(studentUid => {
            if (this.scores[studentUid][itemId]) delete this.scores[studentUid][itemId];
        });
        await this.syncAll();
    }

    async syncConfig() {
        const docRef = doc(db, "classes", this.classId, "grading_sheets", this.quarter);
        await updateDoc(docRef, { config: this.config, lastUpdated: serverTimestamp() });
    }

    async syncAll() {
        const docRef = doc(db, "classes", this.classId, "grading_sheets", this.quarter);
        await updateDoc(docRef, { config: this.config, scores: this.scores, lastUpdated: serverTimestamp() });
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
        
        // --- REAL-TIME VALIDATION ENGINE ---
        if (numericVal !== null) {
            if (numericVal < 0) {
                numericVal = 0;
                if (input) input.value = 0;
            }
            
            if (numericVal > item.hps) {
                // Visual Alert: Red Border & Shake
                if (input) {
                    input.classList.add('border-primary-500', 'bg-primary-500/10', 'animate-shake');
                    setTimeout(() => input.classList.remove('animate-shake'), 500);
                    
                    // Auto-Cap Logic
                    numericVal = item.hps;
                    input.value = item.hps;
                    
                    // User Notification
                    if (window.showToast) {
                        window.showToast(`Score capped at ${item.hps} (Max HPS)`, 'error');
                    }
                }
            } else {
                // Clear validation state
                if (input) input.classList.remove('border-primary-500', 'bg-primary-500/10');
            }
        }

        if (!this.scores[studentUid]) this.scores[studentUid] = {};
        
        // Prevent redundant updates
        if (this.scores[studentUid][itemId] === numericVal) return;

        this.scores[studentUid][itemId] = numericVal;
        this.calculateRow(studentUid);
        
        // --- VISUAL FEEDBACK: CELL SAVING STATE ---
        if (input) {
            input.classList.remove('text-green-400');
            input.classList.add('text-primary-400', 'animate-pulse');
        }

        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(() => this.saveScores(), 500); // Debounce at 500ms
    }

    async saveScores() {
        if (this.isSaving) return;
        this.isSaving = true;
        this.updateSaveStatus('Autosaving...');
        
        try {
            const docRef = doc(db, "classes", this.classId, "grading_sheets", this.quarter);
            await updateDoc(docRef, { scores: this.scores, lastUpdated: serverTimestamp() });
            this.updateSaveStatus('All changes synced');
            
            // Clear cell-level saving indicators
            document.querySelectorAll('.grade-input.animate-pulse').forEach(el => {
                el.classList.remove('animate-pulse', 'text-primary-400');
                el.classList.add('text-green-400'); 
                setTimeout(() => el.classList.remove('text-green-400'), 1500);
            });

        } catch (e) {
            this.updateSaveStatus('Sync error');
            console.error("Firebase Sync Error:", e);
        } finally {
            this.isSaving = false;
        }
    }

    updateSaveStatus(text) {
        const el = document.getElementById('gradeSaveStatus');
        if (el) el.innerText = text;
    }

    calculateRow(studentUid) {
        const studentScores = this.scores[studentUid] || {};
        const categories = ['written', 'performance', 'exam'];
        let initialGrade = 0;

        categories.forEach(cat => {
            const catConfig = this.config[cat];
            let catTotal = 0;
            let catHPS = 0;
            
            catConfig.items.forEach(item => {
                catTotal += studentScores[item.id] || 0;
                catHPS += item.hps || 0;
            });

            const percentage = catHPS > 0 ? (catTotal / catHPS) * 100 : 0;
            initialGrade += (percentage * (catConfig.weight / 100));
            
            // Update individual category percentage cell
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
        // Below Passing
        if (score >= 56.0) return 74;
        if (score >= 52.0) return 73;
        if (score >= 48.0) return 72;
        if (score >= 44.0) return 71;
        if (score >= 40.0) return 70;
        return 60; // Floor
    }

    render() {
        const container = document.getElementById('gradingTableContainer');
        if (!container) return;

        const categories = [
            { id: 'written', name: 'Written Works', color: 'blue' },
            { id: 'performance', name: 'Performance Tasks', color: 'purple' },
            { id: 'exam', name: 'Quarterly Exam', color: 'green' }
        ];

        let html = `
            <table class="w-full text-left text-[11px] border-collapse spreadsheet-table">
                <thead>
                    <!-- Category Headers -->
                    <tr class="bg-dark-bg/80 backdrop-blur-md">
                        <th class="p-4 bg-dark-surface sticky left-0 top-0 z-30 border-b border-r border-dark-border min-w-[200px]">
                            <div class="flex items-center justify-between">
                                <span class="font-black uppercase tracking-widest text-primary-500 italic">Student Name</span>
                                <div id="gradeSaveStatus" class="text-[9px] font-bold text-gray-500 animate-pulse">Synced</div>
                            </div>
                        </th>
                        ${categories.map(cat => {
                            const count = this.config[cat.id].items.length + 1; // +1 for the Percentage/Total column
                            return `
                                <th colspan="${count}" class="p-3 text-center border-b border-r border-dark-border border-t-4 border-t-${cat.color}-500 bg-${cat.color}-500/5 sticky top-0 z-20">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="font-black uppercase tracking-widest text-${cat.color}-400">${cat.name} (${this.config[cat.id].weight}%)</span>
                                        <button onclick="window.gradingSystem.openAddComponent('${cat.id}')" class="w-4 h-4 rounded bg-${cat.color}-500/20 text-${cat.color}-400 hover:bg-${cat.color}-500 hover:text-white transition-all flex items-center justify-center">
                                            <i data-feather="plus" class="w-3 h-3"></i>
                                        </button>
                                    </div>
                                </th>
                            `;
                        }).join('')}
                        <th rowspan="3" class="p-4 text-center border-b border-dark-border bg-primary-500/10 sticky right-0 top-0 z-30 min-w-[100px]">
                            <div class="font-black uppercase tracking-widest text-white leading-tight">Final<br>Grade</div>
                        </th>
                    </tr>
                    
                    <!-- Item Headers -->
                    <tr class="bg-dark-bg/60">
                        <th class="p-2 bg-dark-bg sticky left-0 top-[52px] z-30 border-b border-r border-dark-border text-gray-500 font-bold italic text-center uppercase tracking-widest">Item Name</th>
                        ${categories.map(cat => {
                            const items = this.config[cat.id].items;
                            return `
                                ${items.map(item => `
                                    <th class="p-2 text-center border-b border-r border-dark-border min-w-[80px] bg-dark-surface/50 group sticky top-[52px] z-20">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="text-white truncate max-w-[70px]" title="${item.name}">${item.name}</span>
                                            <button onclick="window.gradingSystem.deleteComponent('${cat.id}', '${item.id}')" class="opacity-0 group-hover:opacity-100 text-[8px] text-red-500 hover:underline">Remove</button>
                                        </div>
                                    </th>
                                `).join('')}
                                <th class="p-2 text-center border-b border-r border-dark-border min-w-[70px] bg-dark-surface/80 text-gray-400 font-black italic sticky top-[52px] z-20">SCORE %</th>
                            `;
                        }).join('')}
                    </tr>

                    <!-- HPS Row (Highest Possible Score) -->
                    <tr class="bg-primary-500/5">
                        <th class="p-2 bg-dark-bg sticky left-0 top-[96px] z-30 border-b-2 border-r border-dark-border text-primary-500 font-black italic text-center uppercase tracking-widest">HPS (Max)</th>
                        ${categories.map(cat => {
                            const items = this.config[cat.id].items;
                            return `
                                ${items.map(item => `
                                    <th class="p-2 text-center border-b-2 border-r border-dark-border font-black text-white bg-white/5 sticky top-[96px] z-20">${item.hps}</th>
                                `).join('')}
                                <th class="p-2 text-center border-b-2 border-r border-dark-border font-black text-gray-500 italic sticky top-[96px] z-20">100%</th>
                            `;
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
                                    const items = this.config[cat.id].items;
                                    let catTotal = 0;
                                    let catHPS = 0;
                                    
                                    const scoreCells = items.map(item => {
                                        const score = studentScores[item.id] ?? '';
                                        catTotal += parseFloat(score) || 0;
                                        catHPS += item.hps || 0;
                                        
                                        return `
                                            <td class="p-1 border-r border-dark-border text-center">
                                                <input type="number" 
                                                    id="input-${studentUid}-${item.id}"
                                                    class="grade-input w-full bg-transparent border-0 text-center py-1.5 text-white focus:bg-white/10 outline-none transition-all rounded" 
                                                    value="${score}" 
                                                    min="0" 
                                                    max="${item.hps}"
                                                    placeholder="-"
                                                    oninput="window.gradingSystem.handleScoreChange('${studentUid}', '${item.id}', this.value)"
                                                    onkeydown="window.gradingSystem.handleNavigation(event)"
                                                >
                                            </td>
                                        `;
                                    }).join('');
                                    
                                    const percentage = catHPS > 0 ? (catTotal / catHPS) * 100 : 0;
                                    
                                    return `
                                        ${scoreCells}
                                        <td id="percent-${cat.id}-${studentUid}" class="p-1 border-r border-dark-border text-center font-black text-gray-500 italic bg-white/5">
                                            ${percentage.toFixed(1)}%
                                        </td>
                                    `;
                                }).join('')}
                                <td id="final-${studentUid}" class="p-4 text-center font-black sticky right-0 bg-dark-bg z-10 border-l border-dark-border shadow-[-10px_0_15px_rgba(0,0,0,0.5)]">
                                    -
                                </td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `;

        container.innerHTML = html;
        feather.replace();

        // Calculate all rows after initial render
        this.students.forEach(s => this.calculateRow(s.uid));
    }

    setupKeyboardNavigation() {
        // Handled via onkeydown in render
    }

    handleNavigation(e, studentIndex, itemId) {
        const currentInput = e.target;
        const allInputs = Array.from(document.querySelectorAll('.grade-input'));
        const currentIndex = allInputs.indexOf(currentInput);
        
        let target;
        const itemsPerRow = this.config.written.items.length + this.config.performance.items.length + this.config.exam.items.length;

        if (e.key === 'ArrowDown' || e.key === 'Enter') {
            e.preventDefault();
            target = allInputs[currentIndex + itemsPerRow];
        } else if (e.key === 'ArrowUp') {
             e.preventDefault();
            target = allInputs[currentIndex - itemsPerRow];
        } else if (e.key === 'ArrowRight' && currentInput.selectionStart === currentInput.value.length) {
            target = allInputs[currentIndex + 1];
        } else if (e.key === 'ArrowLeft' && currentInput.selectionStart === 0) {
            target = allInputs[currentIndex - 1];
        } else if (e.key === 'Tab') {
            // Excel-style Tab handle is native, but we can wrap around if needed
            // For now, let native Tab handle horizontal, we handle others
        }

        if (target) {
            target.focus();
            target.select();
        }
    }

    exportToCSV() {
        let csv = "Student Name,Student ID,";
        const categories = ['written', 'performance', 'exam'];
        
        categories.forEach(cat => {
            this.config[cat].items.forEach(item => {
                csv += `${item.name} (Max ${item.hps}),`;
            });
            csv += `${cat.toUpperCase()} %,`;
        });
        csv += "Initial Grade,Final Grade\n";

        this.students.forEach(student => {
            const studentUid = student.uid;
            const studentScores = this.scores[studentUid] || {};
            csv += `"${student.firstName} ${student.lastName}",${student.studentId || ''},`;
            
            let initialGrade = 0;
            categories.forEach(cat => {
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
        const catName = { written: 'Written Work', performance: 'Performance Task', exam: 'Quarterly Exam' }[category];
        document.getElementById('addCompModalTitle').innerText = `Add ${catName}`;
        openModal('addComponentModal');
    }
}

export { GradingSystem };
