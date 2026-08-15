import { api, initPage } from '../../assets/js/custom-auth.js?v=2';

let currentTeacher = null;
let attendanceListener = null;
let spotlightTimeout = null;
let currentClassData = null;
let verifiedStudentsList = []; // Array of { name, id, avatar, time }
let processedUids = new Set();
let initialSyncDone = false;
let todayStatusByUid = new Map(); // uid -> today's existing {status,time,distance,suspicious}
let selectedClassId = null;
let classesCache = [];

const now = new Date();
const TODAY_STR = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
const nowSql = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
};
let sessionTimerInterval = null;
let qrRefreshInterval = null;
let currentNonce = null;
let currentMode = 'open'; // 'open' | 'late'
let flagMap = new Map();  // student_uid -> [fraud reason strings]
let sessionGradingPending = false; // set when a session ends; consumed by syncAttendanceToGrading()
let reportEntries = [];        // full-roster rows (scanned + never-scanned) for the session report
let reportReadyPromise = null; // resolves when the report table is fully rendered
let endedSessionId = null;     // session_id captured at confirmEndSession(), passed to manual sync

let attendanceCompId = null;   // id of the ATT grading component auto-created at session end
let attendanceCompName = null; // display name (e.g. "8/14/26 #2") of that component
let pickerUid = null;          // uid whose status popover is currently open
let recordViewMode = false;    // true when viewing a reopened session record (button reads "Update")
const ON_TIME_WINDOW_SECONDS = 30;  // on-time window; after it, scans are Late
const LATE_WINDOW_SECONDS = 30;      // late window; when it expires, the session auto-ends
const SESSION_TOTAL_SECONDS = ON_TIME_WINDOW_SECONDS + LATE_WINDOW_SECONDS;
const NONCE_GRACE_SECONDS = 25;  // previous nonce accepted within this window
const POLL_INTERVAL_MS = 3000;   // live attendance poll: scan appears within ~3s

const randNonce = () => Math.random().toString(36).substring(2, 10).toUpperCase();
const parseSql = (s) => s ? new Date(s.replace(' ', 'T')) : null;
const formatSqlTime = (s) => {
    const t = (s || '').trim();
    if (!t) return '';
    const d = new Date(t.replace(' ', 'T'));
    if (isNaN(d.getTime())) return t;
    let h = d.getHours(), m = String(d.getMinutes()).padStart(2, '0');
    const ampm = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12;
    return `${h}:${m} ${ampm}`;
};
const sqlFromDate = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
const isClassLive = (c) => {
    if (c.session_active != 1) return false;
    const expiresAt = parseSql(c.session_expires_at);
    if (!expiresAt) return true;
    return expiresAt > new Date();
};
const getLocation = () => new Promise((resolve) => {
    if (!navigator.geolocation) return resolve(null);
    navigator.geolocation.getCurrentPosition(
        (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
        () => resolve(null),
        { timeout: 8000, maximumAge: 30000 }
    );
});

function setModeUI(mode) {
    currentMode = mode;
    const late = mode === 'late';
    document.getElementById('liveModeTitle').innerText = late ? 'Late Arrivals' : 'Scan to Join';
    document.getElementById('liveModeLabel').innerText = late ? 'RECORDING LATE' : 'Live Session Active';
    const dot = document.getElementById('liveModeDot');
    dot.classList.toggle('bg-green-500', !late);
    dot.classList.toggle('bg-amber-500', late);
    const countdown = document.getElementById('sessionCountdown');
    countdown.classList.toggle('bg-amber-500/10', late);
    countdown.classList.toggle('border-amber-500/20', late);
    countdown.classList.toggle('bg-primary-500/10', !late);
    countdown.classList.toggle('border-primary-500/20', !late);
    if (late) document.getElementById('timerValue').innerText = 'LATE';
}

function applyGeofenceUI(cls) {
    const label = document.getElementById('geofenceRadiusLabel');
    if (!label) return;
    const on = cls && parseInt(cls.require_location) === 1;
    label.classList.toggle('hidden', !on);
    if (!on) return;
    const radius = parseInt(cls.session_radius_m) || 150;
    const text = document.getElementById('geofenceRadiusText');
    if (text) text.innerText = `Geofence active — ${radius}m radius`;
}


// 1. Identity Handshake
initPage((user) => {
    console.log("Attendance: Direct Auth Fallback");
    setTimeout(() => initClassSelection(user.uid), 500);
});

async function initClassSelection(teacherUid) {
    const grid = document.getElementById('classSelectionGrid');
    if (!grid) return;
    
    try {
        // 🛡️ Safety Check: Ensure we have a UID
        const uid = teacherUid || currentTeacher?.uid;
        if (!uid) {
            console.warn("Attendance: No UID available for registry fetch.");
            return;
        }

        console.log("Attendance: Initializing Registry for UID:", uid);
        const classes = await api('/classes.php');
        
        if (classes.length === 0) {
            classesCache = [];
            clearSelection();
            grid.innerHTML = `<div class="col-span-full py-20 text-center opacity-40 italic text-gray-500">No classes found. Please create one in the Dashboard.</div>`;
            feather.replace();
            return;
        }

        classesCache = classes;
        if (selectedClassId && !classes.some(c => c.id === selectedClassId)) {
            clearSelection();
        }
        renderClassGrid(classes);
        if (selectedClassId) updateSelectionBar();
    } catch (error) {
        console.error("Attendance Sync Error:", error);
        grid.innerHTML = `
            <div class="col-span-full py-20 text-center">
                <i data-feather="alert-octagon" class="w-12 h-12 mx-auto mb-4 text-primary-500 animate-pulse"></i>
                <p class="text-xs font-black uppercase tracking-widest italic text-primary-400">Registry Sync Denied</p>
                <p class="text-[10px] text-gray-500 mt-2 font-mono">${error.message}</p>
            </div>`;
        feather.replace();
    }
}

function renderClassGrid(classes) {
    const grid = document.getElementById('classSelectionGrid');
    if (!grid) return;

    grid.innerHTML = classes.map(c => {
        const live = isClassLive(c);
        const win = c.window || {};
        const nextLabel = win.nextOpenLabel || (win.windowLabel ? ('Outside ' + win.windowLabel) : 'Not scheduled');
        const selected = c.id === selectedClassId;
        const modified = !live && !!c.last_session_id && isTodayDate(c.last_session_ended_at);
        return `
        <div class="glass-panel p-6 rounded-xl border ${live ? 'border-green-500/40' : modified ? 'border-amber-500/40' : 'border-dark-border'} hover:border-primary-500/50 transition-all cursor-pointer group ${selected ? 'ring-2 ring-primary-500/60 border-primary-500/60 bg-primary-500/5' : ''}" onclick="window.handleClassCardClick('${c.id}')">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-primary-500/10 rounded-lg">
                    <i data-feather="book-open" class="w-6 h-6 text-primary-500"></i>
                </div>
                <span class="flex items-center gap-2">
                    ${live ? `<span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-green-500/10 text-green-400 border border-green-500/30 italic"><span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> LIVE</span>` : ''}
                    ${modified ? `<span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-amber-500/15 text-amber-400 border border-amber-500/30 italic"><i data-feather="edit-3" class="w-3 h-3"></i> Modified</span>` : ''}
                    ${selected ? `<span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-primary-500/15 text-primary-400 border border-primary-500/30 italic">Selected</span>` : ''}
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic">${c.class_code}</span>
                </span>
            </div>
            <h3 class="text-lg font-bold text-white mb-1 uppercase tracking-tighter italic">${c.class_name}</h3>
            <p class="text-[10px] text-gray-400 font-medium uppercase tracking-widest mb-4 opacity-60">${c.subject} &bull; ${c.section_name}</p>
            <div class="flex items-center gap-2 text-[10px] font-black text-primary-400 uppercase tracking-widest italic">
                <span>${selected ? 'Tap Start to begin session' : (live ? 'Resume Live Session' : (modified ? 'Tap to view session record' : 'Select to Start'))}</span>
                ${!(live || selected) ? `<span class="text-[9px] font-black text-gray-400 uppercase opacity-60">(${nextLabel})</span>` : ''}
                <i data-feather="arrow-right" class="w-3 h-3 group-hover:translate-x-1 transition-transform"></i>
            </div>
            ${modified ? `
            <div class="mt-5 pt-4 border-t border-white/5">
                <button type="button" onclick="event.stopPropagation(); window.selectClass('${c.id}')" class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest italic bg-white/5 border border-white/10 text-primary-400 hover:bg-primary-500/10 hover:border-primary-500/40 transition-all">
                    <i data-feather="play" class="w-3.5 h-3.5"></i> Start New Session
                </button>
            </div>` : ''}
        </div>`;
    }).join('');
    feather.replace();
}

// Card click routing: a Modified card (ended session today) opens its session
// record; any other card selects it for a new live session.
window.handleClassCardClick = (classId) => {
    const c = classesCache.find(x => x.id === classId);
    if (!c) return;
    const modified = !isClassLive(c) && !!c.last_session_id && isTodayDate(c.last_session_ended_at);
    if (modified) {
        window.loadSessionReportFromServer(classId, c.last_session_id);
    } else {
        window.selectClass(classId);
    }
};

// 1b. Select a class — reveals the Start Attendance bar
window.selectClass = (classId) => {
    selectedClassId = classId;
    renderClassGrid(classesCache);
    updateSelectionBar();
};

window.clearSelection = () => {
    selectedClassId = null;
    const bar = document.getElementById('startAttendanceBar');
    if (bar) bar.classList.add('hidden');
    renderClassGrid(classesCache);
};

window.startSelectedClass = () => {
    if (selectedClassId) window.startAttendanceSession(selectedClassId);
};

function updateSelectionBar() {
    const bar = document.getElementById('startAttendanceBar');
    if (!bar) return;
    const cls = classesCache.find(c => c.id === selectedClassId);
    if (!cls) {
        clearSelection();
        return;
    }
    document.getElementById('selectedClassName').innerText = cls.class_name;
    document.getElementById('selectedClassMeta').innerText = (cls.subject || '') + ' • ' + (cls.section_name || '');
    document.getElementById('selectedClassCode').innerText = cls.class_code || '';
    const termBadge = document.getElementById('gradingTermBadge');
    if (termBadge) {
        let t = 1;
        try {
            t = Math.min(3, Math.max(1, parseInt(sessionStorage.getItem(`cs_grading_term_${cls.id}`) || '1') || 1));
        } catch (e) {}
        termBadge.innerText = `Recording to ${({1: '1st Term', 2: '2nd Term', 3: '3rd Term'}[t] || '1st Term')}`;
    }
    const live = isClassLive(cls);
    const badge = document.getElementById('selectedClassLiveBadge');
    badge.classList.toggle('hidden', !live);
    badge.classList.toggle('inline-flex', live);
    const btn = document.getElementById('startAttendanceBtn');
    btn.innerHTML = `<i data-feather="play" class="w-4 h-4"></i> ${live ? 'Resume Live Session' : 'Start Attendance'}`;
    bar.classList.remove('hidden');
    feather.replace();
}

// 2. Start / Resume Live Session
window.startAttendanceSession = async (classId) => {
    try {
        // Fetch class details (now includes .window)
        currentClassData = await api('/classes.php?id=' + classId);
        if (!currentClassData) return;
        upsertClassCache(currentClassData);

        // Grading weights gate (start): the auto-created attendance column
        // requires weights totaling 100%, so warn the teacher up front.
        let weightsTotal = null;
        try {
            const gres = await api('/grades.php?class_id=' + classId + '&quarter=1');
            weightsTotal = Object.values(gres.weights || {}).reduce((s, w) => s + (parseInt(w) || 0), 0);
        } catch (e) { /* leave null — don't block the session on a fetch failure */ }
        if (weightsTotal !== null && weightsTotal !== 100) {
            await showWeightsRequiredAlert(classId);
            return;
        }

        const win = currentClassData.window || {};
        const live = isClassLive(currentClassData);
        const labelEl = document.getElementById('sessionWindowLabel');
        if (win.windowLabel) labelEl.innerText = win.windowLabel;

        // Schedule gate removed — attendance can be started at any time

        // Switch View
        switchView('liveAttendanceView');
        document.getElementById('liveClassName').innerText = currentClassData.class_name;
        const subjectBadge = document.getElementById('liveClassSubject');
        if (subjectBadge) {
            const subject = (currentClassData.subject || '').trim();
            subjectBadge.innerText = subject;
            subjectBadge.classList.toggle('hidden', !subject);
        }
        document.getElementById('reportClassTitle').innerText = currentClassData.class_name;
        document.getElementById('totalCount').innerText = currentClassData.students ? currentClassData.students.length : 0;
        
    // Clear State
    verifiedStudentsList = [];
    processedUids.clear();
    flagMap.clear();
    initialSyncDone = false;
    sessionGradingPending = false;
    recordViewMode = false;
    attendanceCompId = null;
    reportEntries = [];
    reportReadyPromise = null;
        document.getElementById('presentCount').innerText = '0';
        updateLateCount();
        updateSuspiciousCount();
        document.getElementById('liveRosterList').innerHTML = '';
        resetLiveSpotlight();
        document.getElementById('idleListState').classList.add('hidden');
        document.getElementById('idleEmptyState').classList.remove('hidden');

        // RESUME: class already has a live (unexpired) session — attach to it
        // instead of restarting.
        if (live) {
            currentNonce = currentClassData.current_nonce;
            applyGeofenceUI(currentClassData);
            generateAttendanceQR(classId);
            startQRRefreshCycle(classId);
            initAttendanceListener(classId, { loadExisting: true });
            labelEl.innerText = (currentClassData.session_expires_at ? 'Until ' + formatSqlTime(currentClassData.session_expires_at) : 'Live session');
            const started = parseSql(currentClassData.session_started_at);
            const elapsed = started && !isNaN(started.getTime())
                ? Math.max(0, Math.floor((Date.now() - started.getTime()) / 1000))
                : 0;
            if (elapsed < ON_TIME_WINDOW_SECONDS) {
                setModeUI('open');
                startCountdown(remainingOnTimeWindow(currentClassData.session_started_at), flipToLate);
            } else {
                setModeUI('late');
                const lateRemaining = remainingLateWindow(currentClassData.session_started_at);
                if (lateRemaining > 0) {
                    startCountdown(lateRemaining, () => window.confirmEndSession());
                } else {
                    window.confirmEndSession();
                }
            }
            return;
        }

        // FRESH START: capture the GPS anchor BEFORE opening the session
        const requireLocation = document.getElementById('requireLocationToggle').checked;
        const radius = Math.max(50, parseInt(document.getElementById('sessionRadiusInput').value) || 150);
        let anchor = null;
        if (requireLocation) {
            anchor = await getLocation();
            if (!anchor) {
                alert('GPS location is required but unavailable. Check browser location permissions and try again.');
                switchView('classSelectionView');
                return;
            }
        }

        // Generate nonce + start session via API. The session has NO client-side
        // TTL — it runs until the 30-second late window expires (auto-end). The
        // on-time window is tracked via session_started_at on the server.
        const nonce = randNonce();
        currentNonce = nonce;
        const started = nowSql();

        const body = {
            session_active: 1,
            session_started_at: started,
            current_nonce: nonce,
            nonce_issued_at: started,
            session_mode: 'open',
            require_location: requireLocation ? 1 : 0
        };
        if (anchor) {
            body.session_lat = anchor.lat;
            body.session_lng = anchor.lng;
            body.session_radius_m = radius;
        }

        await api('/classes.php?id=' + classId, {
            method: 'PUT',
            body: JSON.stringify(body)
        });

        setModeUI('open');

        // Generate QR Code (with the live nonce)
        generateAttendanceQR(classId);

        // Refresh to read the server-authoritative session state and start the
        // 30-second on-time countdown. When it hits 0 the session flips to a
        // 30-second late window and then auto-ends.
        const refreshed = await api('/classes.php?id=' + classId);
        upsertClassCache(refreshed);
        currentClassData = refreshed;
        applyGeofenceUI(refreshed);
        labelEl.innerText = win.windowLabel || 'Live session';
        startCountdown(remainingOnTimeWindow(refreshed.session_started_at), flipToLate);

        // Start QR Refresh Cycle
        startQRRefreshCycle(classId);

        // Start Listener
        initAttendanceListener(classId, { loadExisting: false });
    } catch (err) {
        console.error("Session Init Failure:", err);
    }
};

function initAttendanceListener(classId, opts = {}) {
    // loadExisting=false (fresh session): today's pre-existing rows are only
    // seeded into processedUids so the list starts clean; new scans still show.
    const loadExisting = opts.loadExisting !== false;
    if (attendanceListener) clearInterval(attendanceListener);
    
    async function pollAttendance() {
        try {
            const records = await api('/attendance.php?class_id=' + classId + '&date=' + TODAY_STR);
            const fresh = [];
            for (const record of records) {
                if (!processedUids.has(record.student_uid)) {
                    processedUids.add(record.student_uid);
                    fresh.push(record);
                }
            }
            if (!initialSyncDone) {
                initialSyncDone = true;
                if (loadExisting) {
                    for (const record of fresh) {
                        await processNewAttendance(record, true);
                    }
                    if (verifiedStudentsList.length > 0) {
                        document.getElementById('idleListState').classList.remove('hidden');
                        document.getElementById('idleEmptyState').classList.add('hidden');
                        renderIdleList();
                    } else {
                        document.getElementById('idleListState').classList.add('hidden');
                        document.getElementById('idleEmptyState').classList.remove('hidden');
                    }
                } else {
                    // Fresh session: pre-seed processedUids with today's rows
                    // silently — the list stays empty until a new scan arrives.
                    document.getElementById('idleListState').classList.add('hidden');
                    document.getElementById('idleEmptyState').classList.remove('hidden');
                }
            } else {
                for (const record of fresh) {
                    await processNewAttendance(record, false);
                }
            }
            renderFraudFlags(records);
        } catch (err) {
            console.error("Attendance Poll Error:", err);
        }
    }
    
    pollAttendance();
    attendanceListener = setInterval(pollAttendance, POLL_INTERVAL_MS);

    // Immediate catch-up when the tab regains focus (browsers throttle
    // setInterval to ~1/min in background tabs, which otherwise hides
    // recent scans until the next throttled tick).
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && typeof pollAttendance === 'function') {
            pollAttendance();
        }
    });
}

function generateAttendanceQR(classId) {
    const qrContainer = document.getElementById('qrcode');
    qrContainer.innerHTML = '';
    
    // Re-show Scan Line
    document.getElementById('scanLine').classList.remove('hidden');

    // v2 payload: t = unix issue time, lets the app reject stale codes instantly
    new QRCode(qrContainer, {
        text: JSON.stringify({ 
            v: 2,
            classId: classId, 
            type: 'attendance', 
            date: TODAY_STR,
            nonce: currentNonce,
            t: Math.floor(Date.now() / 1000)
        }),
        width: 160,
        height: 160,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
}

function startQRRefreshCycle(classId) {
    if (qrRefreshInterval) clearInterval(qrRefreshInterval);
    qrRefreshInterval = setInterval(async () => {
        const newNonce = randNonce();
        const prevNonce = currentNonce;
        currentNonce = newNonce;
        
        try {
            await api('/classes.php?id=' + classId, {
                method: 'PUT',
                body: JSON.stringify({ 
                    current_nonce: newNonce,
                    nonce_issued_at: nowSql(),
                    last_nonce: prevNonce
                })
            });
            generateAttendanceQR(classId);
            console.log("QR Refreshed with Nonce:", newNonce);
        } catch (err) {
            console.error("QR Refresh Failure:", err);
        }
    }, 10000); // Refresh every 10 seconds
}

// 2b. Auto-late flip: when the 30-second on-time window elapses, switch the
// projected QR to a late-only code. Every scan from here on is recorded with
// status 'Late' by the server. A 30-second late window then runs and the
// session auto-ends when it expires.
async function flipToLate() {
    if (!currentClassData || currentMode === 'late') return;
    const nonce = randNonce();
    currentNonce = nonce;

    try {
        await api('/classes.php?id=' + currentClassData.id, {
            method: 'PUT',
            body: JSON.stringify({
                session_mode: 'late',
                current_nonce: nonce,
                nonce_issued_at: nowSql()
            })
        });

        setModeUI('late');
        generateAttendanceQR(currentClassData.id);
        if (window.showToast) window.showToast('On-time window closed — scans now record as LATE', 'info');
        startCountdown(remainingLateWindow(currentClassData.session_started_at), () => window.confirmEndSession());
    } catch (err) {
        console.error("Late Mode Flip Failure:", err);
    }
};

async function processNewAttendance(record, silent = false) {
    try {
        // Fetch student info via fetch.php (reads from SQL)
        const students = await api('/fetch.php', {
            method: 'POST',
            body: JSON.stringify({ collection: 'students', uids: [record.student_uid] })
        });
        if (!students || students.length === 0) return;
        const student = students[0];
        
        // Resolve Avatar / Initials
        let avatarUrl = student.profilePicture || student.profile_picture;
        if (!avatarUrl) {
            const initials = `${student.firstName?.[0] || 'S'}${student.lastName?.[0] || 'T'}`.toUpperCase();
            avatarUrl = `https://ui-avatars.com/api/?name=${initials}&background=ea2628&color=fff&bold=true`;
        }
        
        const entry = {
            uid: record.student_uid,
            name: `${student.firstName || ''} ${student.lastName || ''}`.trim() || 'Unknown Student',
            id: student.studentId || 'N/A',
            avatar: avatarUrl,
            time: record.timestamp ? new Date(record.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}) : new Date().toLocaleTimeString(),
            status: record.status || 'Present',
            deviceUuid: record.device_uuid || null,
            ip: record.ip_address || null,
            distance: record.distance_m != null ? Number(record.distance_m) : null,
            suspicious: record.is_suspicious == 1
        };

        verifiedStudentsList.push(entry);
        
        // Update UI Counters
        updateLivePresentCount();
        updateLateCount();
        updateSuspiciousCount();
        
        // Trigger Spotlight (skipped during initial sync on start/resume)
        if (!silent) updateSpotlight(entry);
    } catch (err) {
        console.warn("Student Context Fetch Error:", err);
    }
}

// Fraud detection: same device OR same IP recording 2+ different students
function renderFraudFlags(records) {
    if (!records || records.length === 0) return;
    const byDevice = new Map();
    const byIp = new Map();
    for (const r of records) {
        if (!r.student_uid) continue;
        if (r.device_uuid) {
            if (!byDevice.has(r.device_uuid)) byDevice.set(r.device_uuid, new Set());
            byDevice.get(r.device_uuid).add(r.student_uid);
        }
        if (r.ip_address) {
            if (!byIp.has(r.ip_address)) byIp.set(r.ip_address, new Set());
            byIp.get(r.ip_address).add(r.student_uid);
        }
    }
    const nextFlags = new Map();
    for (const [dev, uids] of byDevice) {
        if (uids.size > 1) for (const u of uids) pushFlag(nextFlags, u, 'Same device scanned multiple students');
    }
    for (const [ip, uids] of byIp) {
        if (uids.size > 1) for (const u of uids) pushFlag(nextFlags, u, 'Same connection scanned multiple students');
    }
    for (const r of records) {
        if (!r.student_uid || r.is_suspicious != 1) continue;
        const dist = r.distance_m != null ? Math.round(Number(r.distance_m)) : null;
        pushFlag(nextFlags, r.student_uid, `Outside geofence${dist != null ? ` — ${dist}m` : ''}`);
    }
    flagMap = nextFlags;
    // Re-render roster only when the spotlight is not covering it
    if (!document.getElementById('spotlightContent').classList.contains('hidden')) return;
    if (verifiedStudentsList.length > 0) {
        document.getElementById('idleListState').classList.remove('hidden');
        renderIdleList();
    }
}

function pushFlag(map, uid, reason) {
    if (!map.has(uid)) map.set(uid, []);
    map.get(uid).push(reason);
}

function updateSuspiciousCount() {
    const el = document.getElementById('suspiciousCount');
    if (!el) return;
    el.innerText = verifiedStudentsList.filter(s => s.suspicious).length;
}

function updateLateCount() {
    const el = document.getElementById('lateCount');
    if (!el) return;
    el.innerText = verifiedStudentsList.filter(s => normalizeStatus(s.status) === 'Late').length;
}

function updateSpotlight(student) {
    const emptyState = document.getElementById('idleEmptyState');
    const listState = document.getElementById('idleListState');
    const contentState = document.getElementById('spotlightContent');
    
    const timeStr = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

    emptyState.classList.add('hidden');
    listState.classList.add('hidden');
    contentState.classList.remove('hidden');
    
    const timerBar = document.getElementById('timerBar');
    timerBar.classList.remove('animate-timer-shrink');
    void timerBar.offsetWidth; 
    timerBar.classList.add('animate-timer-shrink');

    document.getElementById('spotlightAvatar').src = student.avatar;
    document.getElementById('spotlightName').innerText = student.name;
    document.getElementById('spotlightTime').innerText = `Verified at ${student.time}${student.distance != null ? ` — ${Math.round(student.distance)}m from classroom` : ''}${student.suspicious ? ' · SUSPICIOUS' : ''}`;

    feather.replace();

    clearTimeout(spotlightTimeout);
    spotlightTimeout = setTimeout(() => {
        showIdleView();
    }, 5000);
}

function showIdleView() {
    document.getElementById('spotlightContent').classList.add('hidden');
    if (verifiedStudentsList.length > 0) {
        document.getElementById('idleListState').classList.remove('hidden');
        renderIdleList();
    } else {
        document.getElementById('idleEmptyState').classList.remove('hidden');
    }
}

function resetLiveSpotlight() {
    const spotlight = document.getElementById('spotlightContent');
    if (spotlight) spotlight.classList.add('hidden');
    const avatar = document.getElementById('spotlightAvatar');
    if (avatar) avatar.src = '';
    const name = document.getElementById('spotlightName');
    if (name) name.innerText = 'Student Name';
    const time = document.getElementById('spotlightTime');
    if (time) time.innerText = 'Scanned at --:--';
    if (spotlightTimeout) {
        clearTimeout(spotlightTimeout);
        spotlightTimeout = null;
    }
}

function resetLiveFeed() {
    resetLiveSpotlight();
    verifiedStudentsList = [];
    processedUids.clear();
    flagMap.clear();
    initialSyncDone = false;
    document.getElementById('presentCount').innerText = '0';
    updateLateCount();
    updateSuspiciousCount();
    document.getElementById('liveRosterList').innerHTML = '';
    document.getElementById('idleListState').classList.add('hidden');
    document.getElementById('idleEmptyState').classList.remove('hidden');
}

function renderIdleList() {
    const container = document.getElementById('liveRosterList');
    container.innerHTML = [...verifiedStudentsList].reverse().map(s => {
        const displayStatus = normalizeStatus(s.status);
        const badge = statusBadge(displayStatus);
        const suspicious = !!s.suspicious;
        const dist = s.distance != null ? Math.round(s.distance) : null;
        return `
        <div class="p-3 bg-dark-bg/40 border ${suspicious ? 'border-amber-500/40' : displayStatus === 'Late' ? 'border-amber-500/30' : displayStatus === 'Absent' ? 'border-red-500/30' : 'border-dark-border'} rounded-xl hover:bg-white/5 transition-colors">
            <div class="flex items-center">
                <img src="${s.avatar}" class="w-10 h-10 rounded-full object-cover ring-2 ring-dark-bg mr-3">
                <div class="flex-1">
                    <h4 class="text-sm font-bold text-white uppercase italic tracking-tighter">${s.name}</h4>
                    <p class="text-[9px] text-gray-500 font-black uppercase tracking-widest italic opacity-60">${s.time}</p>
                </div>
                ${dist !== null ? `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest italic ${suspicious ? 'bg-red-500/10 text-red-400 border border-red-500/30' : 'bg-primary-500/10 text-primary-400 border border-primary-500/20'}"><i data-feather="map-pin" class="w-3 h-3"></i> ${dist}m</span>` : ''}
                <button type="button" onclick="window.openStatusPicker(this, '${s.uid}')" class="status-badge cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full ${badge.classes} text-[9px] font-black uppercase tracking-widest italic transition-all hover:ring-2 hover:ring-white/15" title="Click to change status">
                    <span class="w-1.5 h-1.5 rounded-full ${badge.dot}"></span>
                    ${suspicious ? '<i data-feather="alert-triangle" class="w-3 h-3 text-red-400"></i>' : ''}
                    ${badge.label}
                    <i data-feather="chevron-down" class="w-3 h-3 opacity-50"></i>
                </button>
                <button onclick="window.discardAttendanceRecord('${s.uid}')" class="ml-2 p-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-lg transition-colors" title="Discard record">
                    <i data-feather="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            ${(flagMap.get(s.uid) || []).map(r => `
                <div class="mt-2 flex items-center gap-1.5 text-[9px] font-bold text-amber-400 italic">
                    <i data-feather="alert-triangle" class="w-3 h-3"></i> ${r}
                </div>`).join('')}
        </div>`;
    }).join('');
    feather.replace();
}

// Navigation Functions
window.switchView = (viewId) => {
    document.getElementById('classSelectionView').classList.add('hidden');
    document.getElementById('liveAttendanceView').classList.add('hidden');
    document.getElementById('sessionSummaryView').classList.add('hidden');
    document.getElementById(viewId).classList.remove('hidden');
};

function upsertClassCache(cls) {
    if (!cls || !cls.id) return;
    const idx = classesCache.findIndex(c => c.id === cls.id);
    if (idx !== -1) classesCache[idx] = cls;
    else classesCache.unshift(cls);
}

async function refreshClassesCache() {
    try {
        classesCache = await api('/classes.php');
    } catch (err) {
        console.error("Class Cache Refresh Failure:", err);
    }
}

window.confirmEndSession = async () => {
    // Capture session_id BEFORE the PUT clears it server-side (the server moves
    // session_id -> last_session_id on session_active=0, so we grab it now).
    endedSessionId = currentClassData?.session_id || null;
    if (currentClassData) {
        try {
            await api('/classes.php?id=' + currentClassData.id, {
                method: 'PUT',
                body: JSON.stringify({ session_active: 0 })
            });
        } catch (err) {
            console.error("Session Clearance Failure:", err);
        }
    }
    await refreshClassesCache();

    if (attendanceListener) clearInterval(attendanceListener);
    if (sessionTimerInterval) clearInterval(sessionTimerInterval);
    if (qrRefreshInterval) clearInterval(qrRefreshInterval);
    
    // Reset mode UI for next session
    setModeUI('open');
    
    // Clear any stale spotlight overlay before moving to the report view
    resetLiveSpotlight();
    
    sessionGradingPending = true;
    recordViewMode = false;
    reportReadyPromise = generateSummaryReport();
    updateRecordViewActions();
    switchView('sessionSummaryView');
};

// Seconds remaining in the 30-second on-time window for a session started at
// `startedAt` (SQL string). Falls back to the full window if the start time is
// missing or unparseable.
function remainingOnTimeWindow(startedAt) {
    const started = parseSql(startedAt);
    if (!started || isNaN(started.getTime())) return ON_TIME_WINDOW_SECONDS;
    const elapsed = Math.max(0, Math.floor((new Date() - started) / 1000));
    return Math.max(0, ON_TIME_WINDOW_SECONDS - elapsed);
}

// Seconds remaining in the 30-second late window (which runs right after the
// on-time window). Falls back to the full late window if unparseable.
function remainingLateWindow(startedAt) {
    const started = parseSql(startedAt);
    if (!started || isNaN(started.getTime())) return LATE_WINDOW_SECONDS;
    const elapsed = Math.max(0, Math.floor((new Date() - started) / 1000));
    return Math.max(0, SESSION_TOTAL_SECONDS - elapsed);
}

// Generic countdown; calls onComplete() when it reaches 0. The on-time window
// flips to late mode; the late window auto-ends the session.
function startCountdown(totalSeconds, onComplete) {
    totalSeconds = Math.max(0, Math.floor(totalSeconds));
    const timerDisplay = document.getElementById('sessionCountdown');
    const timerSpan = document.getElementById('timerValue');

    timerDisplay.classList.remove('hidden');
    timerSpan.classList.remove('text-primary', 'animate-pulse');

    if (sessionTimerInterval) clearInterval(sessionTimerInterval);

    const tick = () => {
        const displayMins = Math.floor(totalSeconds / 60);
        const displaySecs = totalSeconds % 60;

        timerSpan.innerText = `${displayMins}:${displaySecs.toString().padStart(2, '0')}`;

        if (totalSeconds <= 60) {
            timerSpan.classList.add('text-primary', 'animate-pulse');
        }

        if (totalSeconds <= 0) {
            clearInterval(sessionTimerInterval);
            sessionTimerInterval = null;
            if (typeof onComplete === 'function') onComplete();
            return;
        }

        totalSeconds--;
    };
    tick();
    sessionTimerInterval = setInterval(tick, 1000);
}

const STATUS_STYLES = {
    Present: { classes: 'bg-green-500/10 text-green-400 border border-green-500/20', dot: 'bg-green-500', label: 'Present' },
    Late:    { classes: 'bg-amber-500/10 text-amber-400 border border-amber-500/30', dot: 'bg-amber-500', label: 'Late' },
    Absent:  { classes: 'bg-red-500/10 text-red-400 border border-red-500/30', dot: 'bg-red-500', label: 'Absent' }
};

function statusBadge(status) {
    return STATUS_STYLES[status] || STATUS_STYLES.Absent;
}

function normalizeStatus(status) {
    if (status === 'Late') return 'Late';
    if (status === 'Absent') return 'Absent';
    return 'Present';
}

function updateLivePresentCount() {
    const el = document.getElementById('presentCount');
    if (!el) return;
    el.innerText = verifiedStudentsList.filter(s => normalizeStatus(s.status) === 'Present').length;
}

function initialsAvatar(st, uid) {
    const a = `${(st.firstName?.[0] || 'S')}${(st.lastName?.[0] || 'T')}`.toUpperCase();
    return `https://ui-avatars.com/api/?name=${a}&background=ea2628&color=fff&bold=true`;
}

async function generateSummaryReport() {
    const tbody = document.getElementById('summaryTableBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-gray-500 italic"><i data-feather="loader" class="w-4 h-4 inline animate-spin"></i> Compiling roster...</td></tr>`;

    // Refresh the class roster so the report always reflects the current
    // enrollment, even if the snapshot captured when the session started went
    // stale. Fall back to the in-memory copy on failure. Storing the refreshed
    // roster on currentClassData keeps the report, the persisted Absent rows,
    // and the grading grades all derived from the same full roster.
    if (currentClassData && currentClassData.id) {
        try {
            const fresh = await api('/classes.php?id=' + currentClassData.id);
            if (fresh && Array.isArray(fresh.students) && fresh.students.length > 0) {
                currentClassData.students = fresh.students;
            }
        } catch (e) {
            console.warn('Roster refresh failed, using session snapshot:', e);
        }
    }

    const rosterUids = (currentClassData && currentClassData.students) || [];
    const scannedMap = new Map();
    verifiedStudentsList.forEach(s => scannedMap.set(s.uid, s));

    // Load today's existing attendance rows so students who scanned in an
    // EARLIER same-day session keep their Present/Late status (and don't get
    // flipped to Absent / overwritten by a second session's Done).
    todayStatusByUid.clear();
    if (currentClassData && currentClassData.id) {
        try {
            const todayRows = await api('/attendance.php?class_id=' + currentClassData.id + '&date=' + TODAY_STR);
            (todayRows || []).forEach(r => {
                if (!r.student_uid) return;
                todayStatusByUid.set(r.student_uid, {
                    status: normalizeStatus(r.status || 'Absent'),
                    time: r.timestamp ? new Date(r.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}) : '',
                    distance: r.distance_m != null ? Number(r.distance_m) : null,
                    suspicious: r.is_suspicious == 1
                });
            });
        } catch (e) {
            console.warn('Today attendance fetch failed:', e);
        }
    }

    // Batch-fetch name/id/avatar for enrolled students who never scanned.
    const missingUids = rosterUids.filter(u => !scannedMap.has(u));
    let profileMap = new Map();
    if (missingUids.length > 0) {
        try {
            const students = await api('/fetch.php', {
                method: 'POST',
                body: JSON.stringify({ collection: 'students', uids: missingUids })
            });
            students.forEach(st => profileMap.set(st.uid, st));
        } catch (e) {
            console.warn('Roster fetch failed:', e);
        }
    }

    const entries = [];
    for (const uid of rosterUids) {
        const sc = scannedMap.get(uid);
        if (sc) {
            entries.push({
                uid,
                name: sc.name,
                id: sc.id,
                avatar: sc.avatar,
                time: sc.time,
                distance: sc.distance,
                suspicious: !!sc.suspicious,
                status: normalizeStatus(sc.status),
                scanned: true
            });
        } else {
            // Full-day report (one session per day): a student who didn't scan
            // this run but already scanned earlier today (Present/Late) is still
            // shown as attended — the report reflects the whole day.
            const today = todayStatusByUid.get(uid);
            if (today && (today.status === 'Present' || today.status === 'Late')) {
                const st = profileMap.get(uid) || {};
                const avatar = st.profilePicture || st.profile_picture || initialsAvatar(st, uid);
                entries.push({
                    uid,
                    name: `${st.firstName || ''} ${st.lastName || ''}`.trim() || 'Unknown Student',
                    id: st.studentId || 'N/A',
                    avatar,
                    time: today.time,
                    distance: today.distance,
                    suspicious: today.suspicious,
                    status: today.status,
                    scanned: true
                });
            } else {
                const st = profileMap.get(uid) || {};
                const avatar = st.profilePicture || st.profile_picture || initialsAvatar(st, uid);
                entries.push({
                    uid,
                    name: `${st.firstName || ''} ${st.lastName || ''}`.trim() || 'Unknown Student',
                    id: st.studentId || 'N/A',
                    avatar,
                    time: '',
                    distance: null,
                    suspicious: false,
                    status: 'Absent',
                    scanned: false
                });
            }
        }
    }

    // Defensive: scanned students not in the roster still appear.
    for (const s of verifiedStudentsList) {
        if (!entries.some(e => e.uid === s.uid)) {
            entries.push({
                uid: s.uid, name: s.name, id: s.id, avatar: s.avatar,
                time: s.time, distance: s.distance, suspicious: !!s.suspicious,
                status: normalizeStatus(s.status), scanned: true
            });
        }
    }

    reportEntries = entries;
    renderSummaryTable();
}

function updateSummaryCounts() {
    const present = reportEntries.filter(e => e.status === 'Present').length;
    const late = reportEntries.filter(e => e.status === 'Late').length;
    const absent = reportEntries.filter(e => e.status === 'Absent').length;
    const pe = document.getElementById('finalPresentCount');
    const le = document.getElementById('finalLateCount');
    const ae = document.getElementById('finalAbsentCount');
    if (pe) pe.innerText = present;
    if (le) le.innerText = late;
    if (ae) ae.innerText = absent;
}

function renderSummaryTable() {
    const tbody = document.getElementById('summaryTableBody');
    if (!tbody) return;

    updateSummaryCounts();

    const entries = reportEntries;
    if (entries.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-gray-500 italic">No records captured.</td></tr>`;
        return;
    }

    tbody.innerHTML = entries.map((s, idx) => {
        const badge = statusBadge(s.status);
        const dist = s.distance != null ? Math.round(s.distance) : null;
        return `
        <tr class="border-b border-dark-border hover:bg-white/5 transition-colors animate-pop-in" style="animation-delay: ${Math.min(idx * 40, 1200)}ms">
            <td class="p-4 pl-6">
                <div class="flex items-center gap-3">
                    <img src="${s.avatar}" class="w-10 h-10 rounded-full object-cover ring-2 ring-dark-bg">
                    <span class="font-bold text-white uppercase italic tracking-tighter">${s.name}</span>
                </div>
            </td>
            <td class="p-4 text-gray-400 font-mono text-xs uppercase">${s.id}</td>
            <td class="p-4 text-gray-400 text-xs font-bold uppercase tracking-widest italic opacity-60">${s.scanned ? (s.time || '—') : '—'}</td>
            <td class="p-4">
                ${dist !== null ? `<span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest italic ${s.suspicious ? 'text-red-400' : 'text-primary-400'}"><i data-feather="map-pin" class="w-3 h-3"></i> ${dist}m</span>` : `<span class="text-gray-600 text-[10px] font-bold uppercase italic">—</span>`}
            </td>
            <td class="p-4">
                <button type="button" onclick="window.openStatusPicker(this, '${s.uid}')" class="status-badge cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full ${badge.classes} text-[9px] font-black uppercase tracking-widest italic transition-all hover:ring-2 hover:ring-white/15" title="Click to change status">
                    <span class="w-1.5 h-1.5 rounded-full ${badge.dot}"></span>
                    ${s.suspicious ? '<i data-feather="alert-triangle" class="w-3 h-3 text-red-400"></i>' : ''}
                    ${badge.label}
                    <i data-feather="chevron-down" class="w-3 h-3 opacity-50"></i>
                </button>
            </td>
            </tr>
        ${(flagMap.get(s.uid) || []).map(r => `
            <tr class="bg-amber-500/5 border-b border-dark-border">
                <td colspan="5" class="p-2 pl-6 flex items-center gap-1.5 text-[9px] font-bold text-amber-400 italic">
                    <i data-feather="alert-triangle" class="w-3 h-3"></i> FLAG: ${r} — ${s.name}
                </td>
            </tr>`).join('')}`;
    }).join('');
    feather.replace();
}

// --- Manual status switching (live roster + session report) ---
window.updateAttendanceStatus = async (uid, status) => {
    const liveEntry = verifiedStudentsList.find(s => s.uid === uid);
    const reportEntry = reportEntries.find(e => e.uid === uid);
    if (!liveEntry && !reportEntry) return;

    const prevLive = liveEntry ? normalizeStatus(liveEntry.status) : null;
    const prevReport = reportEntry ? normalizeStatus(reportEntry.status) : null;
    if (prevLive === status && prevReport === status) return;

    if (liveEntry) liveEntry.status = status;
    if (reportEntry) reportEntry.status = status;
    if (reportEntry) renderSummaryTable();
    if (liveEntry) {
        updateLivePresentCount();
        updateLateCount();
        renderIdleList();
    }

    try {
        await api('/attendance.php', {
            method: 'POST',
            body: JSON.stringify({
                manual: true,
                class_id: currentClassData.id,
                student_uid: uid,
                status: status,
                date: TODAY_STR
            })
        });
    } catch (err) {
        if (liveEntry && prevLive != null) liveEntry.status = prevLive;
        if (reportEntry && prevReport != null) reportEntry.status = prevReport;
        if (reportEntry) renderSummaryTable();
        if (liveEntry) {
            updateLivePresentCount();
            updateLateCount();
            renderIdleList();
        }
        console.error('Status update failed:', err);
        if (window.showToast) window.showToast('Failed to update status', 'error');
        return;
    }

    if (window.showToast) window.showToast(`Status updated to ${status}`, 'success');

    // Keep the ATT grading component in sync with a status change made on the
    // report. Even if attendanceCompId isn't linked yet (e.g. an edit made in
    // the report before the session's grading sync has run, or after a fresh
    // session reset it to null), resolve the day's M/D/YY column and update it.
    await syncAttendanceScoreToGrading(currentClassData.id, uid, status);
};

// Mirror a status change into the Grading Center's Attendance component for
// the current day (Present=10, Late=5, Absent=0). One column per day named
// M/D/YY: reuse the already-linked component, otherwise search every term for
// the daily column, and auto-create it in the active term as a last resort.
// Non-blocking: a grade-sync failure never rolls back the attendance update.
async function syncAttendanceScoreToGrading(classId, studentUid, status) {
    if (!classId || !studentUid) return;
    const score = status === 'Present' ? 10 : status === 'Late' ? 5 : 0;
    const d = new Date();
    const dateLabel = `${d.getMonth() + 1}/${d.getDate()}/${String(d.getFullYear()).slice(-2)}`;

    let compId = attendanceCompId;
    try {
        if (!compId) {
            for (let q = 1; q <= 3 && !compId; q++) {
                const data = await api(`/grades.php?class_id=${classId}&quarter=${q}`);
                const match = (data.components || []).find(c =>
                    c.category === 'attendance' && c.name === dateLabel
                );
                if (match) compId = match.id;
            }
        }
        if (!compId) {
            let quarter = 1;
            try {
                quarter = Math.min(3, Math.max(1, parseInt(sessionStorage.getItem(`cs_grading_term_${classId}`) || '1') || 1));
            } catch (e) {}
            const res = await api('/grades.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'save_component',
                    class_id: classId,
                    category: 'attendance',
                    name: dateLabel,
                    hps: 10,
                    quarter
                })
            });
            compId = (res.component || {}).id;
            if (compId) attendanceCompId = compId;
        }
        if (compId) {
            await api('/grades.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'save_score', component_id: compId, student_uid: studentUid, score })
            });
            attendanceCompId = compId;
        }
    } catch (gradeErr) {
        console.error('Grade sync failed for status change:', gradeErr);
        const msg = gradeErr.message || '';
        if (msg.includes('weights') || msg.includes('Weights')) {
            if (window.showToast) window.showToast('Set grading weights (total 100%) to sync attendance', 'error');
        }
    }
}

window.openStatusPicker = (btn, uid) => {
    const picker = document.getElementById('statusPicker');
    if (!picker) return;
    pickerUid = uid;

    const liveEntry = verifiedStudentsList.find(s => s.uid === uid);
    const reportEntry = reportEntries.find(e => e.uid === uid);
    const current = normalizeStatus((reportEntry || liveEntry)?.status || 'Absent');

    picker.querySelectorAll('.status-option').forEach(opt => {
        const isActive = opt.dataset.status === current;
        const check = opt.querySelector('.status-check');
        if (check) check.classList.toggle('hidden', !isActive);
        opt.classList.toggle('bg-white/5', isActive);
    });

    const rect = btn.getBoundingClientRect();
    picker.classList.remove('hidden');
    picker.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - 180)) + 'px';
    picker.style.top = (rect.bottom + 6) + 'px';

    const pRect = picker.getBoundingClientRect();
    if (pRect.bottom > window.innerHeight) {
        picker.style.top = Math.max(8, rect.top - pRect.height - 6) + 'px';
    }
    feather.replace();
};

function closeStatusPicker() {
    const picker = document.getElementById('statusPicker');
    if (picker) picker.classList.add('hidden');
    pickerUid = null;
}

window.discardAttendanceRecord = async (studentUid) => {
    const entry = verifiedStudentsList.find(s => s.uid === studentUid);
    if (!entry) return;
    if (!await window.csConfirm({
        title: 'Discard Record?',
        message: `Remove ${entry.name}'s attendance record for today's session?`,
        okText: 'Discard',
        danger: true
    })) return;
    try {
        // Per-day model: remove this student's attendance record for today.
        await api('/attendance.php?class_id=' + currentClassData.id + '&student_uid=' + studentUid + '&date=' + TODAY_STR, { method: 'DELETE' });
        verifiedStudentsList = verifiedStudentsList.filter(s => s.uid !== studentUid);
        processedUids.delete(studentUid);
        flagMap.delete(studentUid);
        updateLivePresentCount();
        updateLateCount();
        updateSuspiciousCount();
        generateSummaryReport();
        renderIdleList();
        showIdleView();
    } catch (err) {
        console.error("Discard Failure:", err);
        alert('Failed to discard record: ' + (err.message || 'Unknown error'));
    }
};

window.discardAllRecords = async () => {
    const hasCleanup = currentClassData && (sessionGradingPending || reportEntries.length > 0 || verifiedStudentsList.length > 0 || attendanceCompId);
    if (!hasCleanup) {
        sessionGradingPending = false;
        goToClassSelection({ finalize: false });
        return;
    }
    if (!await window.csConfirm({
        title: 'Discard All Records?',
        message: recordViewMode
            ? `Discard ${reportEntries.length || verifiedStudentsList.length} attendance records for this session? The session records, today's grading column, and the Modified status will be removed. This cannot be undone.`
            : `Discard ALL ${reportEntries.length || verifiedStudentsList.length} attendance records for this session? This cannot be undone.`,
        okText: 'Discard All',
        danger: true
    })) return;
    try {
        if (currentClassData) {
            const base = '/attendance.php?class_id=' + currentClassData.id;
            if (recordViewMode && endedSessionId) {
                // Reopened record: delete only THIS session's rows.
                await api(base + '&session_id=' + endedSessionId, { method: 'DELETE' });
            } else {
                // Fresh session-end: remove ALL of today's attendance rows.
                await api(base + '&date=' + TODAY_STR, { method: 'DELETE' });
            }
            // Delete today's auto-created grading column (named M/D/YY) in
            // both modes, so discarding reverts the day's grades too.
            const todayName = `${new Date().getMonth() + 1}/${new Date().getDate()}/${String(new Date().getFullYear()).slice(-2)}`;
            let dayCompId = attendanceCompId || null;
            if (!dayCompId) {
                for (let quarter = 1; quarter <= 3 && !dayCompId; quarter++) {
                    try {
                        const res = await api('/grades.php?class_id=' + currentClassData.id + '&quarter=' + quarter);
                        const match = (res.components || []).find(c => c.category === 'attendance' && c.name === todayName);
                        if (match) dayCompId = match.id;
                    } catch (e) {}
                }
            }
            if (dayCompId) {
                try { await api('/grades.php?component_id=' + dayCompId, { method: 'DELETE' }); } catch (e) {}
            }
            // Clear the Modified marker so the card returns to normal.
            try {
                await api('/classes.php?id=' + currentClassData.id, {
                    method: 'PUT',
                    body: JSON.stringify({ last_session_id: null, last_session_ended_at: null })
                });
            } catch (e) {
                // Ignore: a no-op PUT (marker already null) returns 404.
            }
        }
        attendanceCompId = null;
        attendanceCompName = null;
        verifiedStudentsList = [];
        processedUids.clear();
        flagMap.clear();
        sessionGradingPending = false;
        goToClassSelection({ finalize: false });
    } catch (err) {
        console.error("Bulk Discard Failure:", err);
        alert('Failed to discard records: ' + (err.message || 'Unknown error'));
    }
};

window.backToClassSelection = async () => {
    if (!currentClassData) {
        goToClassSelection({ finalize: false });
        return;
    }
    const ok = await csConfirm({
        title: 'Leave Live Session?',
        message: 'The session stays active - students can still scan until you resume or end it.',
        okText: 'Leave',
        danger: false
    });
    if (!ok) return;

    if (attendanceListener) clearInterval(attendanceListener);
    if (sessionTimerInterval) clearInterval(sessionTimerInterval);
    if (qrRefreshInterval) clearInterval(qrRefreshInterval);
    if (spotlightTimeout) clearTimeout(spotlightTimeout);

    switchView('classSelectionView');
    updateSelectionBar();
};

// Warn the teacher that grading weights are required before attendance grades
// can appear in the Grading Sheet. Single-button modal: the ONLY option is to
// open the Grading Center — the teacher can never continue without weights.
async function showWeightsRequiredAlert(classId) {
    if (!window.csConfirm) {
        window.location.href = `class_view.php?id=${classId}&tab=grading`;
        return true;
    }
    return await window.csConfirm({
        title: 'Grading Weights Required',
        message: 'This class has no grading weights configured (they must total 100%). Attendance grades will NOT be added to the Grading Sheet until weights are set in the Grading Center.',
        okText: 'Open Grading Center',
        danger: false,
        single: true
    }).then((ok) => {
        if (ok) window.location.href = `class_view.php?id=${classId}&tab=grading`;
        return ok;
    });
}

// Auto-create an Attendance component (ATT-n, HPS 10) in the grading sheet
// when the teacher finishes a session via the Done button. Runs even if no
// one scanned (everyone counts as Absent), but is skipped when the session
// records were discarded (sessionGradingPending is cleared).
async function syncAttendanceToGrading() {
    if (!sessionGradingPending) return;
    sessionGradingPending = false;
    if (!currentClassData) return;
    // Ensure the full-roster report has finished building so manual status
    // edits made on the report are reflected in the grades.
    if (reportReadyPromise) {
        try { await reportReadyPromise; } catch (e) {}
    }
    const classId = currentClassData.id;
    const roster = currentClassData.students || [];
    if (roster.length === 0) return;

    let term = 1;
    try {
        term = Math.min(3, Math.max(1, parseInt(sessionStorage.getItem(`cs_grading_term_${classId}`) || '1') || 1));
    } catch (e) {}

    const d = new Date();
    const compName = `${d.getMonth() + 1}/${d.getDate()}/${String(d.getFullYear()).slice(-2)}`;

    const statusByUid = new Map();
    reportEntries.forEach(e => statusByUid.set(e.uid, String(e.status || '').toLowerCase()));

    // Persist the session's final status (esp. Absent for never-scanned
    // students) so the attendance table matches the session report and the
    // student attendance pages reflect real absence. Idempotent upsert: a
    // scanned student's Present/Late row is left untouched, and absent
    // students that only existed in the report now get a real DB record.
    const finalStatus = { present: 'Present', late: 'Late', absent: 'Absent' };
    const statusRows = [];
    roster.forEach(uid => {
        const final = finalStatus[statusByUid.get(uid)] || 'Absent';
        // Protect earlier same-day Present/Late rows: if this session's report
        // would mark the student Absent but they already have a scanned
        // (Present/Late) row today from an earlier session, leave it untouched.
        const existingToday = todayStatusByUid.get(uid);
        if (final === 'Absent' && existingToday && (existingToday.status === 'Present' || existingToday.status === 'Late')) {
            return;
        }
        statusRows.push({ student_uid: uid, status: final });
    });
    try {
        const syncBody = { manual: true, class_id: classId, date: TODAY_STR, students: statusRows };
        // Pass the captured session_id so absent rows get linked to this session
        // in the attendance table (the PHP fallback also resolves it server-side).
        if (endedSessionId) syncBody.session_id = endedSessionId;
        await api('/attendance.php', {
            method: 'POST',
            body: JSON.stringify(syncBody)
        });
    } catch (persistErr) {
        console.error('Attendance final-status persist failed:', persistErr);
        if (window.showToast) window.showToast('Failed to save absence records', 'error');
    }

    // Refresh today's attendance snapshot AFTER the persist so the defensive
    // fallback below (missing report entries) reflects current DB state, not a
    // pre-edit snapshot from when the report was generated.
    todayStatusByUid.clear();
    try {
        const todayRows = await api('/attendance.php?class_id=' + classId + '&date=' + TODAY_STR);
        (todayRows || []).forEach(r => {
            if (!r.student_uid) return;
            todayStatusByUid.set(r.student_uid, { status: normalizeStatus(r.status || 'Absent') });
        });
    } catch (e) {
        console.warn('Today attendance refresh failed during sync:', e);
    }

    const grades = {};
    roster.forEach(uid => {
        const st = statusByUid.get(uid);
        if (st === 'present') grades[uid] = 10;
        else if (st === 'late') grades[uid] = 5;
        else if (st === 'absent') grades[uid] = 0;
        else {
            // Defensive fallback only: the report covers the full roster, so a
            // missing entry means the student never made it into the report at
            // all. Keep an earlier same-day Present/Late score rather than
            // zeroing real attendance. NOTE: an explicit 'absent' report status
            // is ALWAYS authoritative above (the report already reflects
            // same-day earlier sessions), so a stale todayStatusByUid snapshot
            // can never flip a marked-absent student back to Present.
            const existingToday = todayStatusByUid.get(uid);
            if (existingToday && (existingToday.status === 'Present' || existingToday.status === 'Late')) {
                grades[uid] = existingToday.status === 'Late' ? 5 : 10;
            } else {
                grades[uid] = 0;
            }
        }
    });

    try {
        const res = await api('/grades.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'save_component',
                class_id: classId,
                category: 'attendance',
                name: compName,
                hps: 10,
                quarter: term,
                session_id: endedSessionId || null
            })
        });
        const comp = res.component || {};
        const compId = comp.id;
        if (!compId) throw new Error('Component id missing');
        attendanceCompId = compId;
        attendanceCompName = comp.name || compName;

        const rows = Object.entries(grades).map(([studentUid, score]) => ({ component_id: compId, student_uid: studentUid, score }));
        await api('/grades.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'save_bulk', class_id: classId, quarter: term, rows })
        });
    } catch (e) {
        console.error('Attendance component save failed:', e);
        const msg = (e && e.message) || '';
        if (msg.includes('weights') || msg.includes('Weights')) {
            await showWeightsRequiredAlert(currentClassData.id);
            return;
        }
        if (window.showToast) window.showToast('Attendance component save failed: ' + msg, 'error');
        return;
    }
    if (window.showToast) window.showToast(`Attendance component ${attendanceCompName} added to grading sheet (${({1: '1st Term', 2: '2nd Term', 3: '3rd Term'}[term] || '1st Term')})`, 'success');
}

window.goToClassSelection = async (opts = {}) => {
    // finalize=true (Done/Update button): persist statuses + sync the ATT
    // grading component. finalize=false (breadcrumb/back/discard navigation):
    // leave the view WITHOUT touching the grading sheet.
    const finalize = opts.finalize !== false;
    if (finalize) {
        await syncAttendanceToGrading();
    }
    recordViewMode = false;
    // Session is fully ended here: reset the live feed so nothing stale
    // (spotlight, list, timers) survives into the next session.
    resetLiveFeed();
    clearSelection();
    switchView('classSelectionView');
    try {
        classesCache = await api('/classes.php');
        renderClassGrid(classesCache);
        if (selectedClassId) updateSelectionBar();
    } catch (err) {
        console.error("Class Cache Refresh Failure:", err);
    }
};

// "Live Session" breadcrumb: open the live view for the current class. If the
// session is still active it resumes; otherwise a fresh session is started.
window.goToLiveView = async () => {
    if (!currentClassData || !currentClassData.id) {
        goToClassSelection({ finalize: false });
        return;
    }
    await window.startAttendanceSession(currentClassData.id);
};

// Swap the report header button label: "Done" on a fresh session-end report,
// "Update" when viewing a reopened session record.
function updateRecordViewActions() {
    const btn = document.getElementById('recordDoneBtn');
    if (!btn) return;
    btn.innerHTML = recordViewMode
        ? '<i data-feather="save" class="w-4 h-4"></i> Update'
        : '<i data-feather="check" class="w-4 h-4"></i> Done';
    feather.replace();
}

// --- Reopen the most recent ended session (today) from the DB ---
function isTodayDate(dtStr) {
    if (!dtStr) return false;
    const d = parseSql(dtStr);
    if (!d || isNaN(d.getTime())) return false;
    const now = new Date();
    return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth() && d.getDate() === now.getDate();
}

async function fetchStudentProfiles(uids) {
    const map = new Map();
    if (!uids.length) return map;
    try {
        const students = await api('/fetch.php', {
            method: 'POST',
            body: JSON.stringify({ collection: 'students', uids })
        });
        students.forEach(st => map.set(st.uid, st));
    } catch (e) {
        console.warn('Profile fetch failed:', e);
    }
    return map;
}

// Link the reopened session to the ATT component that was auto-created at end
// so post-reopen status edits keep the grade in sync. Matches by session_id
// (falls back to the date prefix so older, unnumbered columns still work).
async function linkAttendanceComponent(cls, sessionId) {
    attendanceCompId = null;
    attendanceCompName = null;
    const d = new Date();
    const todayName = `${d.getMonth() + 1}/${d.getDate()}/${String(d.getFullYear()).slice(-2)}`;
    for (let quarter = 1; quarter <= 3; quarter++) {
        try {
            const res = await api('/grades.php?class_id=' + cls.id + '&quarter=' + quarter);
            const comps = (res.components || []).filter(c => c.category === 'attendance');
            const match = comps.find(c => c.name === todayName)
                || (sessionId && comps.find(c => c.session_id === sessionId))
                || comps[comps.length - 1];
            if (match) { attendanceCompId = match.id; attendanceCompName = match.name; return; }
        } catch (e) {}
    }
}

window.loadSessionReportFromServer = async (classId, sessionId) => {
    if (!classId) return;
    const tbody = document.getElementById('summaryTableBody');
    if (tbody) tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-gray-500 italic"><i data-feather="loader" class="w-4 h-4 inline animate-spin"></i> Loading session report...</td></tr>`;

    try {
        const cls = await api('/classes.php?id=' + classId);
        if (!cls || !Array.isArray(cls.students)) throw new Error('Class not found or no roster');
        currentClassData = cls;
        endedSessionId = sessionId || null;
        const titleEl = document.getElementById('reportClassTitle');
        if (titleEl) titleEl.innerText = cls.class_name;

        const rows = await api('/attendance.php?class_id=' + classId + '&date=' + TODAY_STR + (sessionId ? '&session_id=' + sessionId : ''));
        const list = Array.isArray(rows) ? rows : [];

        // Session-scoped record: only rows belonging to this session_id are
        // loaded (Absent rows persisted at Done included); students in the
        // roster without a row are re-derived as Absent below.
        const scannedMap = new Map();
        for (const r of list) {
            if (r.status === 'Absent') continue;
            const prev = scannedMap.get(r.student_uid);
            if (!prev || (r.timestamp || '') > (prev.timestamp || '')) scannedMap.set(r.student_uid, r);
        }

        const rosterUids = cls.students;
        const missingUids = rosterUids.filter(u => !scannedMap.has(u));
        const profileMap = await fetchStudentProfiles(missingUids);

        const entries = [];
        for (const uid of rosterUids) {
            const r = scannedMap.get(uid);
            if (r) {
                const st = profileMap.get(uid) || {};
                entries.push({
                    uid,
                    name: `${st.firstName || ''} ${st.lastName || ''}`.trim() || 'Unknown Student',
                    id: st.studentId || 'N/A',
                    avatar: st.profilePicture || st.profile_picture || initialsAvatar(st, uid),
                    time: r.timestamp ? new Date(r.timestamp).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', second: '2-digit'}) : '',
                    distance: r.distance_m != null ? r.distance_m : null,
                    suspicious: !!r.is_suspicious,
                    status: ['Late', 'Absent'].includes(r.status) ? r.status : 'Present',
                    scanned: true
                });
            } else {
                const st = profileMap.get(uid) || {};
                const avatar = st.profilePicture || st.profile_picture || initialsAvatar(st, uid);
                entries.push({
                    uid,
                    name: `${st.firstName || ''} ${st.lastName || ''}`.trim() || 'Unknown Student',
                    id: st.studentId || 'N/A',
                    avatar,
                    time: '',
                    distance: null,
                    suspicious: false,
                    status: 'Absent',
                    scanned: false
                });
            }
        }

        reportEntries = entries;
        renderSummaryTable();
        reportReadyPromise = Promise.resolve();
        sessionGradingPending = true;

        await linkAttendanceComponent(cls, sessionId);
        recordViewMode = true;
        updateRecordViewActions();
        switchView('sessionSummaryView');
        if (window.showToast) window.showToast('Session record opened', 'success');
    } catch (err) {
        console.error('Session report reopen failed:', err);
        if (window.showToast) window.showToast('Failed to load session report: ' + (err.message || 'Unknown error'), 'error');
    }
};

document.addEventListener('DOMContentLoaded', () => { feather.replace(); });

// Custom confirmation modal (Promise-based)
window.csConfirm = (opts = {}) => new Promise((resolve) => {
    const modal = document.getElementById('csConfirmModal');
    if (!modal) { resolve(true); return; }
    const titleEl = document.getElementById('csConfirmTitle');
    const msgEl = document.getElementById('csConfirmMessage');
    const okBtn = document.getElementById('csConfirmOk');
    const cancelBtn = document.getElementById('csConfirmCancel');

    titleEl.innerText = opts.title || 'Are you sure?';
    msgEl.innerText = opts.message || '';
    okBtn.innerText = opts.okText || 'Confirm';
    cancelBtn.innerText = opts.cancelText || 'Cancel';
    modal.classList.toggle('danger', !!opts.danger);
    modal.classList.toggle('single', !!opts.single);
    feather.replace();

    let settled = false;
    const done = (val) => {
        if (settled) return;
        if (opts.single && val === false) return;
        settled = true;
        modal.classList.remove('show', 'single');
        setTimeout(() => modal.classList.add('hidden'), 300);
        document.removeEventListener('keydown', onKey);
        resolve(val);
    };
    const onKey = (e) => { if (e.key === 'Escape') done(false); };

    okBtn.onclick = () => done(true);
    cancelBtn.onclick = () => done(false);
    modal.querySelector('.absolute').onclick = () => done(false);
    document.addEventListener('keydown', onKey);

    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.add('show'), 10);
});

// Intercept navigation (sidebar, logo, profile links) while a live session view is open.
// The session intentionally stays active server-side so the teacher can resume it.
document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href]');
    if (!link) return;
    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href === window.location.pathname.split('/').pop()) return;

    const liveView = document.getElementById('liveAttendanceView');
    if (!liveView || liveView.classList.contains('hidden') || !currentClassData) return;

    e.preventDefault();
    csConfirm({
        title: 'Leave Live Session?',
        message: 'The session stays active - students can still scan until you resume or end it.',
        okText: 'Leave',
        danger: false
    }).then((ok) => {
        if (ok) window.location.href = link.href;
    });
});

// Sidebar Toggle Mobile
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const sidebar = document.getElementById('sidebar');
const mobileOverlay = document.getElementById('mobileOverlay');
if (mobileMenuBtn) {
    mobileMenuBtn.onclick = () => {
        sidebar.classList.remove('hidden');
        sidebar.classList.add('fixed', 'inset-0', 'z-50', 'w-64');
        mobileOverlay.classList.remove('hidden');
    }
}
if (mobileOverlay) {
    mobileOverlay.onclick = () => {
        sidebar.classList.add('hidden');
        mobileOverlay.classList.add('hidden');
    }
}

// --- Status picker popover events ---
document.addEventListener('click', (e) => {
    const option = e.target.closest('.status-option');
    if (option) {
        if (pickerUid) window.updateAttendanceStatus(pickerUid, option.dataset.status);
        closeStatusPicker();
        return;
    }
    const picker = document.getElementById('statusPicker');
    if (picker && !picker.classList.contains('hidden') &&
        !picker.contains(e.target) && !e.target.closest('.status-badge')) {
        closeStatusPicker();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeStatusPicker();
});

window.addEventListener('scroll', closeStatusPicker, true);
window.addEventListener('resize', closeStatusPicker);