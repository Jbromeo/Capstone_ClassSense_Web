<!-- teacher_screen/classes/attendance_history.php -->
<div id="tab-attendance" class="tab-content hidden h-full flex flex-col transition-all animate-fade-in animate-scale-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-primary-500/10 rounded-lg text-primary-500 shadow-lg shadow-primary-500/20"><i data-feather="calendar" class="w-5 h-5"></i></div>
            <div>
                <h2 class="text-xl font-black text-white italic uppercase tracking-tighter">Attendance History</h2>
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest italic tracking-tighter opacity-70">Real-time Presence Tracking</p>
            </div>
        </div>
    </div>

    <!-- Attendance Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-panel p-6 rounded-3xl border border-white/5 bg-white/5 shadow-2xl relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-125 transition-transform duration-700">
                <i data-feather="users" class="w-24 h-24 text-white"></i>
            </div>
            <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 italic">Average Presence</p>
            <p id="attAvgPresence" class="text-3xl font-black text-white italic tracking-tighter">—</p>
            <div id="attAvgPresenceTrend" class="flex items-center gap-1 mt-2 text-[8px] font-bold text-green-500 uppercase italic">
                <i data-feather="trending-up" class="w-3 h-3"></i> No records yet
            </div>
        </div>
        <div class="glass-panel p-6 rounded-3xl border border-white/5 bg-white/5 shadow-2xl relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-125 transition-transform duration-700">
                <i data-feather="clock" class="w-24 h-24 text-white"></i>
            </div>
            <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 italic">Tardy Frequency</p>
            <p id="attTardyFreq" class="text-3xl font-black text-white italic tracking-tighter">—</p>
            <div id="attTardyFreqTrend" class="flex items-center gap-1 mt-2 text-[8px] font-bold text-red-500 uppercase italic">
                <i data-feather="alert-circle" class="w-3 h-3"></i> No records yet
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="flex-1 min-h-0 bg-dark-bg/80 backdrop-blur-2xl border border-white/5 rounded-3xl overflow-hidden relative shadow-2xl flex flex-col">
        <div class="h-full flex flex-col items-center justify-center opacity-30 gap-6">
            <i data-feather="package" class="w-16 h-16 text-gray-400"></i>
            <div class="text-center">
                <p class="text-[10px] font-black uppercase tracking-[0.3em] italic tracking-tighter text-white">Archive Under Construction</p>
                <p class="text-[8px] font-bold text-gray-500 uppercase mt-2 italic tracking-widest leading-relaxed">System logs for this Hub are temporarily secured.<br>Biometrics integration pending.</p>
            </div>
        </div>
    </div>
</div>

<script>
window.attendanceHistory = (() => {
    let lastSig = '';

    function monthKey(ts) {
        return `${ts.getFullYear()}-${String(ts.getMonth() + 1).padStart(2, '0')}`;
    }

    // Session date: prefer the YYYY-MM-DD `date` column (parsed manually to
    // avoid timezone drift), fall back to the record timestamp.
    function sessionDate(r) {
        const d = r.date || r.timestamp;
        if (typeof d === 'string' && /^\d{4}-\d{2}-\d{2}/.test(d)) {
            const parts = d.split(' ')[0].split('-').map(Number);
            return new Date(parts[0], parts[1] - 1, parts[2]);
        }
        return new Date(d);
    }

    function presenceRate(records) {
        if (records.length === 0) return null;
        const present = records.filter(r => String(r.status || '').toLowerCase() === 'present').length;
        const late = records.filter(r => String(r.status || '').toLowerCase() === 'late').length;
        return ((present + late) / records.length) * 100;
    }

    function render(records) {
        const avgEl = document.getElementById('attAvgPresence');
        const tardyEl = document.getElementById('attTardyFreq');
        const avgTrendEl = document.getElementById('attAvgPresenceTrend');
        const tardyTrendEl = document.getElementById('attTardyFreqTrend');
        if (!avgEl || !tardyEl) return;

        if (!records || records.length === 0) {
            avgEl.textContent = '—';
            avgEl.className = 'text-3xl font-black text-white italic tracking-tighter';
            tardyEl.textContent = '—';
            tardyEl.className = 'text-3xl font-black text-white italic tracking-tighter';
            avgTrendEl.innerHTML = '<i data-feather="trending-up" class="w-3 h-3"></i> No records yet';
            tardyTrendEl.innerHTML = '<i data-feather="alert-circle" class="w-3 h-3"></i> No records yet';
            try { feather.replace(); } catch (e) {}
            return;
        }

        const total = records.length;
        const present = records.filter(r => String(r.status || '').toLowerCase() === 'present').length;
        const late = records.filter(r => String(r.status || '').toLowerCase() === 'late').length;
        const avg = ((present + late) / total) * 100;
        const tardy = (late / total) * 100;

        avgEl.textContent = `${avg.toFixed(1)}%`;
        tardyEl.textContent = `${tardy.toFixed(1)}%`;

        // Month-over-month presence comparison (current vs previous calendar month)
        const now = new Date();
        const curMonth = records.filter(r => monthKey(sessionDate(r)) === monthKey(now));
        const prevMonthDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const prevMonth = records.filter(r => monthKey(sessionDate(r)) === monthKey(prevMonthDate));

        if (prevMonth.length > 0) {
            const curRate = presenceRate(curMonth);
            const prevRate = presenceRate(prevMonth);
            if (curRate !== null && prevRate !== null) {
                const delta = curRate - prevRate;
                const up = delta >= 0;
                avgTrendEl.innerHTML = `<i data-feather="${up ? 'trending-up' : 'trending-down'}" class="w-3 h-3"></i> ${up ? '+' : ''}${delta.toFixed(1)}% vs Last Month`;
                avgTrendEl.className = `flex items-center gap-1 mt-2 text-[8px] font-bold uppercase italic ${up ? 'text-green-500' : 'text-red-500'}`;
            } else {
                avgTrendEl.innerHTML = `<i data-feather="users" class="w-3 h-3"></i> ${present + late} of ${total} sessions present`;
                avgTrendEl.className = 'flex items-center gap-1 mt-2 text-[8px] font-bold text-gray-500 uppercase italic';
            }
        } else {
            avgTrendEl.innerHTML = `<i data-feather="users" class="w-3 h-3"></i> ${present + late} of ${total} sessions present`;
            avgTrendEl.className = 'flex items-center gap-1 mt-2 text-[8px] font-bold text-gray-500 uppercase italic';
        }

        tardyTrendEl.innerHTML = `<i data-feather="clock" class="w-3 h-3"></i> ${late} of ${total} sessions late`;
        tardyTrendEl.className = 'flex items-center gap-1 mt-2 text-[8px] font-bold text-red-500 uppercase italic';

        try { feather.replace(); } catch (e) {}
    }

    async function init(classId) {
        if (!classId) return;
        try {
            const records = await window.api(`/attendance.php?class_id=${classId}`);
            const sig = JSON.stringify(records);
            if (sig === lastSig) return;
            lastSig = sig;
            render(records);
        } catch (e) {
            console.error('Attendance history load failed:', e);
        }
    }

    return { init };
})();
</script>
