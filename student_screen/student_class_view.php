<?php require_once dirname(__DIR__) . '/core/init.php'; ?>
<!-- student_screen/student_class_view.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassSense | Class Record</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
    </div>

    <?php include 'student_sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        <!-- HEADER (Matching Teacher Dashboard Layout) -->
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                    <i data-feather="menu"></i>
                </button>
                <div>
                    <h2 id="hubClassName" class="text-xl font-bold text-white hidden sm:block uppercase tracking-tighter italic">Loading Hub...</h2>
                    <p id="hubClassDetails" class="text-[10px] font-bold text-gray-500 hidden sm:block uppercase tracking-widest italic tracking-tighter italic">Identifying Instructor • Accessing Grid</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative">
                    <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors group">
                        <i data-feather="bell" class="group-hover:scale-110 transition-transform"></i>
                        <span class="notif-dot hidden absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                    </button>
                    <?php include '../includes/notification_popover.php'; ?>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 md:p-8 custom-scrollbar">
            
            <!-- Teacher Profile & AI Insights -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Teacher Card -->
                <div class="glass-panel rounded-xl p-6 flex items-center gap-4 border-l-4 border-l-primary-500 bg-white/5 border border-white/5 group">
                    <img id="instructorImage" src="https://ui-avatars.com/api/?name=ST&background=181b21&color=fff" class="w-16 h-16 rounded-xl object-cover ring-2 ring-dark-border group-hover:ring-primary-500/50 transition-all shadow-xl shadow-black/50">
                    <div>
                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic tracking-tighter italic opacity-60">Instructor</p>
                        <h3 id="instructorName" class="text-lg font-bold text-white uppercase tracking-tighter italic leading-none mt-1">Syncing Identity...</h3>
                        <p id="instructorEmail" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-2 italic tracking-tighter italic">Contact Not Established</p>
                    </div>
                </div>

                <!-- AI Insights Card -->
                <div class="glass-panel rounded-xl p-6 border border-primary-500/20 bg-primary-500/5 col-span-1 lg:col-span-2">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="p-1.5 bg-primary-500/10 rounded text-primary-400">
                            <i data-feather="cpu" class="w-4 h-4"></i>
                        </div>
                        <h3 class="text-sm font-bold text-primary-400 uppercase tracking-wider">AI Academic Insight</h3>
                        <span id="aiInsightMeta" class="hidden ml-auto text-[9px] font-black text-gray-500 uppercase tracking-widest italic whitespace-nowrap"></span>
                    </div>
                    <div id="aiInsightBody">
                        <p id="aiInsightLoading" class="text-sm text-gray-400 animate-pulse"><i data-feather="loader" class="w-3.5 h-3.5 inline mr-1"></i> Analyzing your performance...</p>
                        <p id="aiInsightText" class="hidden text-sm text-gray-300 leading-relaxed"></p>
                        <ul id="aiInsightTips" class="hidden mt-4 space-y-2"></ul>
                        <p id="aiInsightFallback" class="hidden text-sm text-gray-500 italic"></p>
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="mb-4 border-b border-dark-border">
                <nav class="flex gap-4">
                    <button onclick="switchTab('attendance')" id="tab-attendance" class="px-4 py-2 text-sm font-medium text-white border-b-2 border-primary-500 uppercase tracking-widest italic tracking-tighter italic">QR Attendance Log</button>
                    <button onclick="switchTab('scores')" id="tab-scores" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-white transition-colors border-b-2 border-transparent uppercase tracking-widest italic tracking-tighter italic">Assessments</button>
                </nav>
            </div>

            <!-- Attendance Content -->
            <div id="content-attendance" class="glass-panel rounded-xl overflow-hidden border border-dark-border">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-dark-bg/50 border-b border-dark-border">
                            <tr>
                                <th class="px-6 py-3 font-black tracking-widest italic tracking-tighter italic">Date</th>
                                <th class="px-6 py-3 font-black tracking-widest italic tracking-tighter italic">Time-In</th>
                                <th class="px-6 py-3 font-black tracking-widest italic tracking-tighter italic">Time-Out</th>
                                <th class="px-6 py-3 font-black tracking-widest italic tracking-tighter italic">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-300">
                             <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500 animate-pulse italic uppercase tracking-widest text-[10px] font-black">Establishing Neural Link...</td>
                             </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Assessments Content -->
            <div id="content-scores" class="hidden">
                <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-2 bg-dark-bg/80 backdrop-blur-xl border border-white/5 p-1 rounded-2xl shadow-xl w-fit">
                        <button onclick="window.studentGrades.setTerm(1)" id="st-1st" class="term-btn active px-4 py-2 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all bg-primary-600 text-white shadow-lg shadow-primary-500/20">1st Term</button>
                        <button onclick="window.studentGrades.setTerm(2)" id="st-2nd" class="term-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all text-gray-500 hover:text-white hover:bg-white/5">2nd Term</button>
                        <button onclick="window.studentGrades.setTerm(3)" id="st-3rd" class="term-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase italic tracking-widest transition-all text-gray-500 hover:text-white hover:bg-white/5">3rd Term</button>
                    </div>
                    <div class="glass-panel rounded-xl px-6 py-3 border-l-4 border-l-green-500 w-fit">
                        <div class="flex items-center gap-4">
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic">Final Grade</span>
                            <span id="finalGradeValue" class="text-2xl font-black text-white italic leading-none">&#8212;</span>
                        </div>
                        <span id="finalGradeNote" class="hidden block mt-1 text-[8px] font-bold text-gray-500 uppercase tracking-widest italic">Final grade appears once all weighted categories have scores</span>
                    </div>
                </div>
                <div id="assessmentsGrid" class="grid grid-cols-1 md:grid-cols-3 gap-6"></div>
                <div id="assessmentsEmpty" class="hidden glass-panel rounded-xl p-12 text-center border border-dark-border">
                    <i data-feather="file-text" class="w-10 h-10 text-gray-600 mx-auto mb-4"></i>
                    <p class="text-gray-400 italic uppercase tracking-widest text-[10px] font-black">No assessments recorded for this term yet.</p>
                </div>
            </div>

        </main>
    </div>

    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';

        const urlParams = new URLSearchParams(window.location.search);
        const classId = urlParams.get('id');

        async function loadAttendance(uid) {
            if (!uid || !classId) return;
            const tbody = document.querySelector('#content-attendance tbody');
            if (!tbody) return;

            const todayStr = new Date().toISOString().split('T')[0];

            try {
                const records = await api(`/attendance.php?class_id=${classId}&date=${todayStr}&student_uid=${uid}`);
                if (!records || records.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-12 text-center text-gray-500 italic uppercase tracking-widest text-[10px] font-black">No scan records found in this hub.</td></tr>`;
                    return;
                }

                const sorted = records.sort((a, b) => {
                    const tsA = a.timestamp ? new Date(a.timestamp) : new Date(0);
                    const tsB = b.timestamp ? new Date(b.timestamp) : new Date(0);
                    return tsB - tsA;
                });

                tbody.innerHTML = sorted.map(rec => {
                    const ts = rec.timestamp ? new Date(rec.timestamp) : new Date();
                    const dateStr = ts.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const timeIn = ts.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                    const timeOut = rec.time_out ? new Date(rec.time_out).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '--:-- --';
                    const status = rec.status || 'Verified';
                    const statusClass = status.toLowerCase() === 'late' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' :
                                      status.toLowerCase() === 'absent' ? 'bg-red-500/10 text-red-400 border-red-500/20' :
                                      'bg-green-500/10 text-green-400 border-green-500/20';

                    return `
                        <tr class="border-b border-dark-border hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 font-black text-white italic truncate uppercase tracking-tighter">${dateStr}</td>
                            <td class="px-6 py-4 text-gray-300 font-bold italic text-xs">${timeIn}</td>
                            <td class="px-6 py-4 text-gray-500 italic text-xs">${timeOut}</td>
                            <td class="px-6 py-4"><span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border italic ${statusClass}">${status}</span></td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-12 text-center text-primary-500 italic text-xs font-bold">Failed to load attendance: ${err.message}</td></tr>`;
            }
        }

        async function loadClassData() {
            if (!classId) {
                window.location.href = 'student_classes.php';
                return;
            }

            try {
                const classData = await api(`/classes.php?id=${classId}`);

                const hubName = document.getElementById('hubClassName');
                const hubDetails = document.getElementById('hubClassDetails');
                if (hubName) {
                    hubName.innerText = classData.class_name;
                    hubName.classList.remove('italic');
                }
                if (hubDetails) {
                    hubDetails.innerText = `${classData.teacher_name || 'Faculty'} • ${classData.schedule || 'TBA'} • ${classData.time_slot || 'TBA'} • ${classData.section_name}`;
                    hubDetails.classList.remove('italic');
                }

                const instrName = document.getElementById('instructorName');
                const instrEmail = document.getElementById('instructorEmail');
                const instrImg = document.getElementById('instructorImage');

                const displayName = classData.teacher_name || 'Faculty';
                if (instrName) { instrName.innerText = displayName; instrName.classList.remove('italic'); }
                if (instrEmail) { instrEmail.innerText = 'Official University Email'; instrEmail.classList.remove('italic'); }
                if (instrImg) {
                    instrImg.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=ea2628&color=fff&bold=true`;
                }
            } catch (err) {
                console.error('[Class] Failed to load:', err);
            }
        }

        const GRADE_CATEGORIES = {
            written: { label: 'Written Works', color: 'blue', icon: 'book' },
            performance: { label: 'Performance Tasks', color: 'purple', icon: 'zap' },
            exam: { label: 'Examinations', color: 'green', icon: 'clock' }
        };

        function renderAssessments(data) {
            const grid = document.getElementById('assessmentsGrid');
            const empty = document.getElementById('assessmentsEmpty');
            const finalEl = document.getElementById('finalGradeValue');
            const noteEl = document.getElementById('finalGradeNote');
            if (!grid) return;

            const comps = data.components || [];
            const weights = data.weights || {};
            const cats = ['written', 'performance', 'exam'];
            const compsByCat = {};
            cats.forEach(c => compsByCat[c] = comps.filter(x => x.category === c));
            const hasAny = cats.some(c => compsByCat[c].length > 0);

            empty.classList.toggle('hidden', hasAny);
            grid.classList.toggle('hidden', !hasAny);

            const catAvg = (list) => {
                if (!list.length) return null;
                let ts = 0, th = 0;
                list.forEach(c => {
                    const s = parseFloat(data.grades[c.id]);
                    if (!isNaN(s)) { ts += s; th += parseFloat(c.hps); }
                });
                if (th === 0) return null;
                return (ts / th) * 100;
            };

            if (!hasAny) {
                grid.innerHTML = '';
                finalEl.textContent = '—';
                finalEl.className = 'text-2xl font-black italic leading-none text-gray-600';
                if (noteEl) noteEl.classList.add('hidden');
                return;
            }

            grid.innerHTML = cats.map(cat => {
                const info = GRADE_CATEGORIES[cat];
                const list = compsByCat[cat];
                const avg = catAvg(list);
                const weight = weights[cat] || 0;
                const rows = list.length
                    ? list.map(c => {
                        const s = data.grades[c.id];
                        const num = parseFloat(s);
                        const has = s !== undefined && s !== null && s !== '' && !isNaN(num);
                        return `
                            <div class="flex justify-between items-center text-xs font-bold italic uppercase tracking-widest">
                                <span class="text-gray-400">${c.name}</span>
                                ${has ? `<span class="text-white">${num}/${c.hps}</span>` : `<span class="text-amber-400">Pending</span>`}
                            </div>`;
                    }).join('')
                    : `<p class="text-[10px] text-gray-600 font-bold italic uppercase tracking-widest">No components yet</p>`;

                return `
                    <div class="glass-panel rounded-xl p-6 border border-dark-border border-t-4 border-t-${info.color}-500">
                        <div class="flex items-center justify-between mb-4 gap-2">
                            <h3 class="text-md font-bold text-white flex items-center gap-2 uppercase tracking-tighter italic">
                                <i data-feather="${info.icon}" class="w-4 h-4 text-${info.color}-400"></i> ${info.label}
                            </h3>
                            <span class="text-[9px] font-black text-${info.color}-400 uppercase tracking-widest italic whitespace-nowrap">${weight}% Weight</span>
                        </div>
                        <div class="space-y-3">${rows}</div>
                        <div class="mt-4 pt-3 border-t border-white/5 flex justify-between items-center">
                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest italic">Score</span>
                            <span class="font-black italic text-sm ${avg === null ? 'text-gray-600' : avg >= 75 ? 'text-green-400' : 'text-red-400'}">${avg === null ? '—' : avg.toFixed(1) + '%'}</span>
                        </div>
                    </div>`;
            }).join('');

            const catsAll = ['written', 'performance', 'exam', 'attendance'];
            const weightTotal = catsAll.reduce((s, cat) => s + (weights[cat] || 0), 0);
            const complete = weightTotal > 0 && catsAll.every(cat => {
                if ((weights[cat] || 0) === 0) return true;
                const list = comps.filter(x => x.category === cat);
                return list.length > 0 && list.some(x => !isNaN(parseFloat(data.grades[x.id])));
            });

            let total = 0, totalWeight = 0;
            catsAll.forEach(cat => {
                const avg = catAvg(comps.filter(x => x.category === cat));
                const w = weights[cat] || 0;
                if (avg !== null && w > 0) { total += avg * (w / 100); totalWeight += w; }
            });
            const fg = complete ? total : null;
            finalEl.textContent = fg === null ? '—' : fg.toFixed(1);
            finalEl.className = `text-2xl font-black italic leading-none ${fg === null ? 'text-gray-600' : fg >= 75 ? 'text-green-400' : 'text-red-400'}`;
            if (noteEl) noteEl.classList.toggle('hidden', fg !== null);
        }

        window.studentGrades = (() => {
            let state = { classId: null, uid: null, term: 1 };

            function highlight() {
                const names = {1:'1st',2:'2nd',3:'3rd'};
                document.querySelectorAll('#content-scores .term-btn').forEach(b => b.classList.remove('active', 'bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20'));
                const btn = document.getElementById(`st-${names[state.term]}`);
                if (btn) btn.classList.add('active', 'bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20');
            }

            async function load() {
                if (!state.classId || !state.uid) return;
                const grid = document.getElementById('assessmentsGrid');
                const empty = document.getElementById('assessmentsEmpty');
                if (!grid) return;
                grid.classList.remove('hidden');
                empty.classList.add('hidden');
                grid.innerHTML = `<div class="md:col-span-3 text-center py-12 text-gray-500 italic uppercase tracking-widest text-[10px] font-black animate-pulse">Syncing Assessment Grid...</div>`;
                try {
                    const data = await api(`/student_grades.php?class_id=${state.classId}&quarter=${state.term}`);
                    renderAssessments(data);
                    try { feather.replace(); } catch (e) {}
                } catch (err) {
                    grid.innerHTML = `<div class="md:col-span-3 text-center py-12 text-primary-500 italic text-xs font-bold">Failed to load assessments: ${err.message}</div>`;
                }
            }

            return {
                init(classId, uid) {
                    state.classId = classId;
                    state.uid = uid;
                    state.term = Math.min(3, Math.max(1, parseInt(sessionStorage.getItem(`cs_grading_term_${classId}`) || '1') || 1));
                    highlight();
                    load();
                },
                async setTerm(t) {
                    state.term = t;
                    sessionStorage.setItem(`cs_grading_term_${state.classId}`, t);
                    highlight();
                    load();
                },
                reload: load
            };
        })();

        async function loadAIInsight() {
            const loading = document.getElementById('aiInsightLoading');
            const textEl = document.getElementById('aiInsightText');
            const tipsEl = document.getElementById('aiInsightTips');
            const fallbackEl = document.getElementById('aiInsightFallback');
            const metaEl = document.getElementById('aiInsightMeta');
            if (!classId || !loading) return;

            loading.classList.remove('hidden');
            textEl.classList.add('hidden');
            tipsEl.classList.add('hidden');
            fallbackEl.classList.add('hidden');
            metaEl.classList.add('hidden');

            try {
                const data = await api(`/ai_insight.php?class_id=${classId}`);
                if (data.available === false) {
                    loading.classList.add('hidden');
                    fallbackEl.classList.remove('hidden');
                    fallbackEl.textContent = 'AI insights will appear here once configured.';
                    return;
                }
                if (!data.insight || !data.insight.paragraph) throw new Error('Empty insight');

                textEl.textContent = data.insight.paragraph;
                tipsEl.innerHTML = (data.insight.tips || []).map(tip =>
                    `<li class="flex items-start gap-2 text-xs text-gray-400">
                        <i data-feather="check-circle" class="w-3.5 h-3.5 text-primary-400 mt-0.5 shrink-0"></i>
                        <span class="font-medium">${tip}</span>
                    </li>`
                ).join('');
                loading.classList.add('hidden');
                textEl.classList.remove('hidden');
                if (data.insight.tips && data.insight.tips.length) tipsEl.classList.remove('hidden');

                if (data.analyzedAt) {
                    const mins = Math.max(1, Math.floor((Date.now() - new Date(data.analyzedAt.replace(' ', 'T'))) / 60000));
                    metaEl.textContent = `Auto-analyzed • ${mins < 60 ? mins + 'm ago' : Math.floor(mins / 60) + 'h ago'}`;
                    metaEl.classList.remove('hidden');
                }
                try { feather.replace(); } catch (e) {}
            } catch (err) {
                console.error('AI Insight Error:', err);
                loading.classList.add('hidden');
                fallbackEl.classList.remove('hidden');
                fallbackEl.textContent = 'AI insights are temporarily unavailable.';
            }
        }

        initPage((user) => {
            setTimeout(() => {
                loadClassData();
                loadAttendance(user.uid);
                window.studentGrades.init(classId, user.uid);
                loadAIInsight();
            }, 500);
            setInterval(loadClassData, 10000);
            setInterval(() => loadAttendance(user.uid), 10000);
        });
    </script>
    <script type="module" src="student_auth.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
        
        // Tab Switching
        function switchTab(tabName) {
            const attendanceContent = document.getElementById('content-attendance');
            const scoresContent = document.getElementById('content-scores');
            const attendanceTab = document.getElementById('tab-attendance');
            const scoresTab = document.getElementById('tab-scores');

            if(!attendanceContent || !scoresContent || !attendanceTab || !scoresTab) return;

            attendanceContent.classList.add('hidden');
            scoresContent.classList.add('hidden');
            attendanceTab.classList.remove('text-white', 'border-primary-500');
            attendanceTab.classList.add('text-gray-500', 'border-transparent');
            scoresTab.classList.remove('text-white', 'border-primary-500');
            scoresTab.classList.add('text-gray-500', 'border-transparent');

            if(tabName === 'attendance') {
                attendanceContent.classList.remove('hidden');
                attendanceTab.classList.add('text-white', 'border-primary-500');
                attendanceTab.classList.remove('text-gray-500', 'border-transparent');
            } else {
                scoresContent.classList.remove('hidden');
                scoresTab.classList.add('text-white', 'border-primary-500');
                scoresTab.classList.remove('text-gray-500', 'border-transparent');
                if (window.studentGrades) window.studentGrades.reload();
            }
        }

        // Mobile Menu Logic
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');

        if(mobileMenuBtn && sidebar && mobileOverlay) {
            mobileMenuBtn.addEventListener('click', () => {
                if(sidebar.style.display === 'flex') { 
                    sidebar.style.display = ''; 
                    sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64'); 
                    mobileOverlay.classList.remove('open'); 
                } else { 
                    sidebar.style.display = 'flex'; 
                    sidebar.classList.add('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64'); 
                    mobileOverlay.classList.add('open'); 
                }
            });
            mobileOverlay.addEventListener('click', () => { 
                sidebar.style.display = ''; 
                sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64'); 
                mobileOverlay.classList.remove('open'); 
            });
        }
    </script>
</body>
</html>