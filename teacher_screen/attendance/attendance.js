import { api, initPage } from '../../assets/js/custom-auth.js';

let currentTeacher = null;
let attendanceListener = null;
let spotlightTimeout = null;
let currentClassData = null;
let verifiedStudentsList = []; // Array of { name, id, avatar, time }
let processedUids = new Set();
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
const LATE_WINDOW_SECONDS = 180; // 3-minute late-arrivals window
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
    document.getElementById('lateWindowBtn').classList.toggle('hidden', late);
    document.getElementById('liveModeTitle').innerText = late ? 'Late Arrivals Window' : 'Scan to Join';
    document.getElementById('liveModeLabel').innerText = late ? 'LATE WINDOW ACTIVE' : 'Live Session Active';
    const dot = document.getElementById('liveModeDot');
    dot.classList.toggle('bg-green-500', !late);
    dot.classList.toggle('bg-amber-500', late);
    const countdown = document.getElementById('sessionCountdown');
    countdown.classList.toggle('bg-amber-500/10', late);
    countdown.classList.toggle('border-amber-500/20', late);
    countdown.classList.toggle('bg-primary-500/10', !late);
    countdown.classList.toggle('border-primary-500/20', !late);
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
        return `
        <div class="glass-panel p-6 rounded-xl border ${live ? 'border-green-500/40' : 'border-dark-border'} hover:border-primary-500/50 transition-all cursor-pointer group ${selected ? 'ring-2 ring-primary-500/60 border-primary-500/60 bg-primary-500/5' : ''}" onclick="window.selectClass('${c.id}')">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-primary-500/10 rounded-lg">
                    <i data-feather="book-open" class="w-6 h-6 text-primary-500"></i>
                </div>
                <span class="flex items-center gap-2">
                    ${live ? `<span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-green-500/10 text-green-400 border border-green-500/30 italic"><span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> LIVE</span>` : ''}
                    ${selected ? `<span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-primary-500/15 text-primary-400 border border-primary-500/30 italic">Selected</span>` : ''}
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic">${c.class_code}</span>
                </span>
            </div>
            <h3 class="text-lg font-bold text-white mb-1 uppercase tracking-tighter italic">${c.class_name}</h3>
            <p class="text-[10px] text-gray-400 font-medium uppercase tracking-widest mb-4 opacity-60">${c.subject} &bull; ${c.section_name}</p>
            <div class="flex items-center gap-2 text-[10px] font-black text-primary-400 uppercase tracking-widest italic">
                <span>${selected ? 'Tap Start to begin session' : (live ? 'Resume Live Session' : 'Select to Start')}</span>
                ${!(live || selected) ? `<span class="text-[9px] font-black text-gray-400 uppercase opacity-60">(${nextLabel})</span>` : ''}
                <i data-feather="arrow-right" class="w-3 h-3 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </div>`;
    }).join('');
    feather.replace();
}

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

        const win = currentClassData.window || {};
        const live = isClassLive(currentClassData);
        const labelEl = document.getElementById('sessionWindowLabel');
        if (win.windowLabel) labelEl.innerText = win.windowLabel;

        // Schedule gate removed — attendance can be started at any time

        // Switch View
        switchView('liveAttendanceView');
        document.getElementById('liveClassName').innerText = currentClassData.class_name;
        document.getElementById('reportClassTitle').innerText = currentClassData.class_name;
        document.getElementById('totalCount').innerText = currentClassData.students ? currentClassData.students.length : 0;
        
    // Clear State
    verifiedStudentsList = [];
    processedUids.clear();
    sessionGradingPending = false;
        document.getElementById('presentCount').innerText = '0';
        document.getElementById('liveRosterList').innerHTML = '';
        document.getElementById('spotlightContent').classList.add('hidden');
        document.getElementById('idleListState').classList.add('hidden');
        document.getElementById('idleEmptyState').classList.remove('hidden');

        // RESUME: class already has a live (unexpired) session — attach to it
        // instead of restarting.
        if (live) {
            currentNonce = currentClassData.current_nonce;
            setModeUI(currentClassData.session_mode === 'late' ? 'late' : 'open');
            const remainingSec = Math.max(0, Math.floor((parseSql(currentClassData.session_expires_at) - new Date()) / 1000));
            generateAttendanceQR(classId);
            startQRRefreshCycle(classId);
            initAttendanceListener(classId);
            labelEl.innerText = (currentClassData.session_expires_at ? 'Until ' + formatSqlTime(currentClassData.session_expires_at) : 'Live session');
            if (remainingSec > 0) {
                startSessionCountdownFrom(remainingSec);
            } else {
                document.getElementById('sessionCountdown').classList.add('hidden');
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

        // Generate nonce + start session via API. Session expiry is owned by
        // the server (= class-window end); we do not send a TTL client-side.
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

        // Refresh to read the server-authoritative session_expires_at
        // (window end) and count down to it. A 0/Live session runs until
        // the teacher stops it manually.
        const refreshed = await api('/classes.php?id=' + classId);
        upsertClassCache(refreshed);
        const exp = refreshed.session_expires_at ? parseSql(refreshed.session_expires_at) : null;
        labelEl.innerText = win.windowLabel || 'Live session';
        if (exp) {
            labelEl.innerText = 'Until ' + formatSqlTime(refreshed.session_expires_at);
            startSessionCountdownFrom(Math.max(0, Math.floor((exp - new Date()) / 1000)));
        } else {
            document.getElementById('sessionCountdown').classList.add('hidden');
        }

        // Start QR Refresh Cycle
        startQRRefreshCycle(classId);

        // Start Listener
        initAttendanceListener(classId);
    } catch (err) {
        console.error("Session Init Failure:", err);
    }
};

function initAttendanceListener(classId) {
    if (attendanceListener) clearInterval(attendanceListener);
    
    async function pollAttendance() {
        try {
            const records = await api('/attendance.php?class_id=' + classId + '&date=' + TODAY_STR);
            for (const record of records) {
                if (!processedUids.has(record.student_uid)) {
                    processedUids.add(record.student_uid);
                    await processNewAttendance(record);
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
        colorDark: currentMode === 'late' ? "#B45309" : "#000000",
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

// 2b. Late Arrivals Window: switch the projected QR to a late-only code.
// Every scan during the window is recorded with status 'Late' by the server.
window.startLateWindow = async () => {
    if (!currentClassData || currentMode === 'late') return;
    if (!await window.csConfirm({
        title: 'Open Late Arrivals Window?',
        message: 'The next 3 minutes of scans will be recorded as LATE.',
        okText: 'Open'
    })) return;

    const nonce = randNonce();
    currentNonce = nonce;

    try {
        await api('/classes.php?id=' + currentClassData.id, {
            method: 'PUT',
            body: JSON.stringify({
                session_mode: 'late',
                current_nonce: nonce,
                nonce_issued_at: nowSql(),
                session_expires_at: sqlFromDate(new Date(Date.now() + LATE_WINDOW_SECONDS * 1000))
            })
        });

        setModeUI('late');
        generateAttendanceQR(currentClassData.id);
        startSessionCountdownFrom(LATE_WINDOW_SECONDS);
    } catch (err) {
        console.error("Late Window Failure:", err);
    }
};

async function processNewAttendance(record) {
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
            ip: record.ip_address || null
        };

        verifiedStudentsList.push(entry);
        
        // Update UI Counters
        document.getElementById('presentCount').innerText = verifiedStudentsList.length;
        
        // Trigger Spotlight
        updateSpotlight(entry);
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
    document.getElementById('spotlightTime').innerText = `Verified at ${student.time}`;

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

function renderIdleList() {
    const container = document.getElementById('liveRosterList');
    container.innerHTML = [...verifiedStudentsList].reverse().map(s => {
        const late = s.status === 'Late';
        return `
        <div class="p-3 bg-dark-bg/40 border ${late ? 'border-amber-500/30' : 'border-dark-border'} rounded-xl hover:bg-white/5 transition-colors">
            <div class="flex items-center">
                <img src="${s.avatar}" class="w-10 h-10 rounded-full object-cover ring-2 ring-dark-bg mr-3">
                <div class="flex-1">
                    <h4 class="text-sm font-bold text-white uppercase italic tracking-tighter">${s.name}</h4>
                    <p class="text-[9px] text-gray-500 font-black uppercase tracking-widest italic opacity-60">${s.time}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${late ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-green-500/10 text-green-400 border border-green-500/20'} italic">
                    <span class="w-1.5 h-1.5 rounded-full ${late ? 'bg-amber-500' : 'bg-green-500'}"></span> ${late ? 'Late' : 'Verified'}
                </span>
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
    
    sessionGradingPending = true;
    
    generateSummaryReport();
    switchView('sessionSummaryView');
};

function startSessionCountdownFrom(totalSeconds) {
    totalSeconds = Math.max(0, Math.floor(totalSeconds));
    const timerDisplay = document.getElementById('sessionCountdown');
    const timerSpan = document.getElementById('timerValue');
    
    timerDisplay.classList.remove('hidden');
    timerSpan.classList.remove('text-primary', 'animate-pulse');
    
    if (sessionTimerInterval) clearInterval(sessionTimerInterval);
    
    sessionTimerInterval = setInterval(() => {
        const displayMins = Math.floor(totalSeconds / 60);
        const displaySecs = totalSeconds % 60;
        
        timerSpan.innerText = `${displayMins}:${displaySecs.toString().padStart(2, '0')}`;
        
        if (totalSeconds <= 60 && currentMode === 'open') {
            timerSpan.classList.add('text-primary', 'animate-pulse');
        }

        if (totalSeconds <= 0) {
            clearInterval(sessionTimerInterval);
            confirmEndSession(); 
        }
        
        totalSeconds--;
    }, 1000);
}

function generateSummaryReport() {
    const tbody = document.getElementById('summaryTableBody');
    const presentCount = verifiedStudentsList.filter(s => s.status !== 'Late').length;
    const lateCount = verifiedStudentsList.length - presentCount;
    document.getElementById('finalPresentCount').innerText = presentCount;
    document.getElementById('finalLateCount').innerText = lateCount;

    if(verifiedStudentsList.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-gray-500 italic">No records captured.</td></tr>`;
        return;
    }

    tbody.innerHTML = verifiedStudentsList.map((s, idx) => {
        const late = s.status === 'Late';
        return `
        <tr class="border-b border-dark-border hover:bg-white/5 transition-colors animate-pop-in" style="animation-delay: ${idx * 50}ms">
            <td class="p-4 pl-6">
                <div class="flex items-center gap-3">
                    <img src="${s.avatar}" class="w-10 h-10 rounded-full object-cover ring-2 ring-dark-bg">
                    <span class="font-bold text-white uppercase italic tracking-tighter">${s.name}</span>
                </div>
            </td>
            <td class="p-4 text-gray-400 font-mono text-xs uppercase">${s.id}</td>
            <td class="p-4 text-gray-400 text-xs font-bold uppercase tracking-widest italic opacity-60">${s.time}</td>
            <td class="p-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${late ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-green-500/10 text-green-400 border border-green-500/20'} italic">
                    <span class="w-1.5 h-1.5 rounded-full ${late ? 'bg-amber-500' : 'bg-green-500'}"></span> ${late ? 'Late' : 'Verified'}
                </span>
            </td>
            <td class="p-4 text-right">
                <button onclick="window.discardAttendanceRecord('${s.uid}')" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 text-[9px] font-black uppercase tracking-widest italic transition-colors" title="Remove from record">
                    <i data-feather="trash-2" class="w-3 h-3"></i> Discard
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
        await api('/attendance.php?class_id=' + currentClassData.id + '&student_uid=' + studentUid + '&date=' + TODAY_STR, { method: 'DELETE' });
        verifiedStudentsList = verifiedStudentsList.filter(s => s.uid !== studentUid);
        processedUids.delete(studentUid);
        flagMap.delete(studentUid);
        document.getElementById('presentCount').innerText = verifiedStudentsList.length;
        generateSummaryReport();
        renderIdleList();
        showIdleView();
    } catch (err) {
        console.error("Discard Failure:", err);
        alert('Failed to discard record: ' + (err.message || 'Unknown error'));
    }
};

window.discardAllRecords = async () => {
    if (!currentClassData || verifiedStudentsList.length === 0) {
        sessionGradingPending = false;
        goToClassSelection();
        return;
    }
    if (!await window.csConfirm({
        title: 'Discard All Records?',
        message: `Discard ALL ${verifiedStudentsList.length} attendance records for this session? This cannot be undone.`,
        okText: 'Discard All',
        danger: true
    })) return;
    try {
        await api('/attendance.php?class_id=' + currentClassData.id + '&date=' + TODAY_STR, { method: 'DELETE' });
        verifiedStudentsList = [];
        processedUids.clear();
        flagMap.clear();
        sessionGradingPending = false;
        goToClassSelection();
    } catch (err) {
        console.error("Bulk Discard Failure:", err);
        alert('Failed to discard records: ' + (err.message || 'Unknown error'));
    }
};

window.backToClassSelection = async () => {
    if (!currentClassData) {
        goToClassSelection();
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

// Auto-create an Attendance component (ATT-n, HPS 10) in the grading sheet
// when the teacher finishes a session via the Done button. Runs even if no
// one scanned (everyone counts as Absent), but is skipped when the session
// records were discarded (sessionGradingPending is cleared).
async function syncAttendanceToGrading() {
    if (!sessionGradingPending) return;
    sessionGradingPending = false;
    if (!currentClassData) return;
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
    verifiedStudentsList.forEach(s => statusByUid.set(s.uid, String(s.status || '').toLowerCase()));

    const grades = {};
    roster.forEach(uid => {
        const st = statusByUid.get(uid);
        if (st === 'late') grades[uid] = 5;
        else if (st) grades[uid] = 10;
        else grades[uid] = 0;
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
                quarter: term
            })
        });
        const compId = (res.component || {}).id;
        if (!compId) throw new Error('Component id missing');

        const rows = Object.entries(grades).map(([studentUid, score]) => ({ component_id: compId, student_uid: studentUid, score }));
        await api('/grades.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'save_bulk', class_id: classId, quarter: term, rows })
        });
    } catch (e) {
        console.error('Attendance component save failed:', e);
        if (window.showToast) window.showToast('Attendance component save failed', 'error');
        return;
    }
    if (window.showToast) window.showToast(`Attendance component ${compName} added to grading sheet (${({1: '1st Term', 2: '2nd Term', 3: '3rd Term'}[term] || '1st Term')})`, 'success');
}

window.goToClassSelection = async () => {
    await syncAttendanceToGrading();
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
    feather.replace();

    let settled = false;
    const done = (val) => {
        if (settled) return;
        settled = true;
        modal.classList.remove('show');
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