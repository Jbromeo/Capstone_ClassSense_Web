import { api } from '../custom-auth.js';

const tableBody = document.getElementById('teacherTableBody');
const countLabel = document.getElementById('teacherCount');
const addForm = document.getElementById('addTeacherForm');
const subBtn = document.getElementById('submitBtn');
const searchInput = document.getElementById('teacherSearch');
const sentinel = document.getElementById('loadMoreSentinel');
const scrollArea = document.getElementById('tableScrollArea');
const lazyLoader = document.getElementById('lazyLoader');
const endOfList = document.getElementById('endOfList');

const modal = document.getElementById('confirmModal');
const targetLabel = document.getElementById('targetTeacherName');
const confirmBtn = document.getElementById('confirmPurgeBtn');
const cancelBtn = document.getElementById('cancelPurgeBtn');
let pendingDelete = null;

const employeeIdInput = document.getElementById('employeeIdInput');
const employeeIdLabel = document.getElementById('employeeIdLabel');
const usernameInput = document.getElementById('usernameInput');
const usernameLabel = document.getElementById('usernameLabel');

const vibrateLabel = (label) => {
    if (!label) return;
    label.classList.remove('shake');
    void label.offsetWidth;
    label.classList.add('shake');
};

const isValidEmployeeId = (id) => /^\d{1,8}$/.test(id);
const isValidUsername = (u) => /^[a-zA-Z0-9_.-]+$/.test(u);

if (employeeIdInput && employeeIdLabel) {
    employeeIdInput.addEventListener('input', () => {
        const digits = employeeIdInput.value.replace(/\D/g, '').slice(0, 8);
        if (digits !== employeeIdInput.value) {
            vibrateLabel(employeeIdLabel);
            employeeIdInput.value = digits;
        }
    });
}

let registryCache = [];
let filteredCache = [];
let isBatchLoading = false;
let hasMore = false;
let searchQuery = "";
const BATCH_SIZE = 12;

const requestModal = document.getElementById('requestModal');
const requestTargetName = document.getElementById('requestTeacherName');
const requestReasonInput = document.getElementById('requestReason');
const confirmRequestBtn = document.getElementById('confirmRequestBtn');
const cancelRequestBtn = document.getElementById('cancelRequestBtn');
let pendingRequestTarget = null;

const isSuperAdmin = window.CURRENT_ROLE === 'super_admin';
const pendingRequests = {};
let pendingRequestsLoaded = false;

const esc = (value) => String(value ?? '')
    .replace(/\\/g, '\\\\')
    .replace(/'/g, "\\'")
    .replace(/"/g, '&quot;');

const renderTeacherRow = (id, data, isNew = false) => {
    const pending = pendingRequests[id];
    const isActive = (data.active == null) ? true : (data.active !== 0);
    const name = (data.firstName || '') + ' ' + (data.lastName || '');
    let settingsCell;

    if (pending) {
        settingsCell = `
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[9px] font-black uppercase tracking-widest italic" title="Approval request awaiting super admin">
                <i data-feather="clock" class="w-3 h-3"></i> Pending
            </span>`;
    } else if (isSuperAdmin) {
        settingsCell = isActive
            ? `<button onclick="window.deactivateTeacher('${esc(id)}', '${esc(name)}')" class="p-4 text-gray-500 hover:text-amber-400 transition-all hover:scale-150" title="Deactivate"><i data-feather="user-x" class="w-6 h-6"></i></button>`
            : `<button onclick="window.reactivateTeacher('${esc(id)}', '${esc(name)}')" class="p-4 text-gray-500 hover:text-green-400 transition-all hover:scale-150" title="Reactivate"><i data-feather="user-check" class="w-6 h-6"></i></button>`;
    } else {
        settingsCell = isActive
            ? `<button onclick="window.requestDeactivate('${esc(id)}', '${esc(name)}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest italic text-amber-400 bg-amber-500/10 border border-amber-500/20 hover:bg-amber-500/20 transition-all whitespace-nowrap" title="Request Deactivation"><i data-feather="user-x" class="w-3.5 h-3.5"></i> Request Deactivate</button>`
            : `<button onclick="window.requestReactivate('${esc(id)}', '${esc(name)}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest italic text-green-400 bg-green-500/10 border border-green-500/20 hover:bg-green-500/20 transition-all whitespace-nowrap" title="Request Reactivation"><i data-feather="user-check" class="w-3.5 h-3.5"></i> Request Activate</button>`;
    }

    return `
        <tr id="row-${id}" class="hover:bg-white/5 transition-colors group ${isNew ? 'new-entry-highlight' : ''}">
            <td class="px-8 py-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-purple-600/20 text-purple-400 flex items-center justify-center font-black border border-purple-500/20 text-xl italic relative">
                        ${(data.firstName || '')[0] || 'T'}
                        ${isNew ? '<div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-dark-bg animate-pulse"></div>' : ''}
                    </div>
                    <div>
                        <p class="text-xl font-black text-white group-hover:text-purple-400 transition-colors italic tracking-tighter uppercase leading-none">${data.firstName || ''} ${data.lastName || ''}</p>
                        <p class="text-xs ${isNew ? 'text-green-400 font-black italic' : (isActive ? 'text-gray-400 font-bold' : 'text-red-400 font-bold')} uppercase tracking-widest mt-2 italic flex items-center gap-2">
                            ${isNew ? '<span class="px-2 py-0.5 bg-green-500/20 rounded text-[9px]">Just Registered</span>' : (isActive ? 'Verified Educator' : '<span class="px-2 py-0.5 bg-red-500/20 rounded text-[9px] text-red-400">Deactivated</span>')}
                        </p>
                        </div>
                    </div>
                </td>
                <td class="px-8 py-6 font-mono text-gray-400 text-sm font-black tracking-tight">
                    ${data.employeeId || data.employee_id || 'N/A'}
                </td>
            <td class="px-8 py-6 font-mono text-purple-400 text-sm font-black underline decoration-purple-500/30 tracking-tight">
                ${data.username || data.email || ''}
            </td>
            <td class="px-8 py-6 text-center">
                ${settingsCell}
            </td>
        </tr>
    `;
};

const loadPendingRequests = async () => {
    if (isSuperAdmin) return; // super admin approves; no need to badge rows
    try {
        const requests = await api('/admin/deletion_requests.php?status=pending');
        requests.forEach(r => { pendingRequests[r.target_uid] = r; });
    } catch (err) {
        console.warn('Pending request load failed:', err);
    }
    pendingRequestsLoaded = true;
};

const syncRegistry = async () => {
    countLabel.innerHTML = '<span class="animate-pulse">Syncing...</span>';
    try {
        if (!pendingRequestsLoaded) await loadPendingRequests();
        const teachers = await api('/fetch.php?collection=teachers');
        registryCache = teachers;
        filteredCache = [...registryCache];
        renderInitialBatch();
    } catch (err) {
        console.error("Sync Error:", err);
        tableBody.innerHTML = `<tr><td colspan="4" class="px-8 py-20 text-center">
            <p class="text-gray-500 italic text-sm">Failed to load registry</p>
            <p class="text-gray-600 text-[10px] mt-1">${err.message || 'Connection error'}</p>
        </td></tr>`;
        countLabel.innerHTML = '<span class="text-primary-500">Sync Error</span>';
    }
};

const renderInitialBatch = () => {
    const batch = filteredCache.slice(0, BATCH_SIZE);
    tableBody.innerHTML = batch.length ? 
        batch.map(t => renderTeacherRow(t.uid, t)).join('') : 
        `<tr><td colspan="4" class="px-8 py-20 text-center text-gray-500 italic">No matching educators found.</td></tr>`;
    
    countLabel.innerHTML = `<span>${filteredCache.length} Educators</span>`;
    hasMore = filteredCache.length > BATCH_SIZE;
    feather.replace();
};

const loadMoreFromCache = () => {
    if (isBatchLoading || !hasMore) return;
    isBatchLoading = true;
    lazyLoader.style.opacity = '1';
    
    setTimeout(() => {
        const currentCount = tableBody.children.length;
        const nextBatch = filteredCache.slice(currentCount, currentCount + BATCH_SIZE);
        
        if (nextBatch.length) {
            tableBody.insertAdjacentHTML('beforeend', nextBatch.map(t => renderTeacherRow(t.uid, t)).join(''));
            feather.replace();
        }
        
        if (currentCount + nextBatch.length >= filteredCache.length) {
            hasMore = false;
            endOfList.classList.remove('hidden');
        }
        
        isBatchLoading = false;
        lazyLoader.style.opacity = '0';
    }, 300);
};

window.deactivateTeacher = (uid, name) => {
    pendingDelete = { uid, name, action: 'deactivate' };
    targetLabel.textContent = name || 'this teacher';
    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.add('show'), 10);
};

window.reactivateTeacher = async (uid, name) => {
    try {
        await api('/admin/account_status.php', {
            method: 'POST',
            body: JSON.stringify({ target_uid: uid, active: 1 })
        });
        window.showStatus(`${name || 'Teacher'} reactivated.`, 'success');
        syncRegistry();
    } catch (error) {
        window.showStatus(error.message || 'Reactivate failed');
    }
};

const closeModal = () => {
    modal.classList.remove('show');
    setTimeout(() => modal.classList.add('hidden'), 300);
    pendingDelete = null;
};

cancelBtn.onclick = closeModal;

confirmBtn.onclick = async () => {
    if(!pendingDelete) return;
    const { uid, name } = pendingDelete;
    closeModal();
    try {
        await api('/admin/account_status.php', {
            method: 'POST',
            body: JSON.stringify({ target_uid: uid, active: 0 })
        });
        window.showStatus(`${name || 'Teacher'} deactivated.`, 'success');
        syncRegistry();
    } catch (error) {
        console.error("Deactivate Error:", error);
        window.showStatus(error.message || 'Deactivate failed');
    }
};

searchInput.addEventListener('input', (e) => {
    searchQuery = e.target.value.trim().toLowerCase();
    filteredCache = registryCache.filter(t =>
        (t.email || '').toLowerCase().includes(searchQuery) ||
        (t.firstName || '').toLowerCase().includes(searchQuery) ||
        (t.lastName || '').toLowerCase().includes(searchQuery) ||
        (t.employeeId || '').toLowerCase().includes(searchQuery)
    );
    renderInitialBatch();
    scrollArea.scrollTo({ top: 0 });
});

const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && hasMore) loadMoreFromCache();
}, { threshold: 0, root: scrollArea });
observer.observe(sentinel);

addForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(addForm);
    const data = Object.fromEntries(formData.entries());
    if (!isValidEmployeeId(data.employee_id)) {
        vibrateLabel(employeeIdLabel);
        return window.showStatus("Invalid Employee ID: numbers only, 8 digits maximum.");
    }
    const username = (data.username || '').trim();
    if (!isValidUsername(username)) {
        vibrateLabel(usernameLabel);
        return window.showStatus("Invalid username: letters, numbers, dots, underscores or hyphens only (no @ or spaces).");
    }
    subBtn.disabled = true;
    document.getElementById('btnLoader').classList.remove('hidden');
    document.getElementById('btnText').textContent = "Establishing Identity...";
    
    try {
        const result = await api('/auth/register.php', {
            method: 'POST',
            body: JSON.stringify({
                username: username,
                password: data.password,
                role: 'teacher',
                firstName: data.fname,
                lastName: data.lname,
                employeeId: data.employee_id,
            })
        });
        const newTeacher = {
            uid: result.uid, firstName: data.fname, lastName: data.lname,
            email: username,
            username: username,
            employeeId: data.employee_id,
            role: 'teacher'
        };
        registryCache.unshift(newTeacher);
        filteredCache = [...registryCache];
        
        const newRowHtml = renderTeacherRow(result.uid, newTeacher, true);
        tableBody.insertAdjacentHTML('afterbegin', newRowHtml);
        scrollArea.scrollTo({ top: 0, behavior: 'smooth' });
        
        window.showStatus("Identity Established!", "success");
        addForm.reset();
        feather.replace();
    } catch (error) {
        window.showStatus(error.message || "Backend error.");
    } finally {
        subBtn.disabled = false;
        document.getElementById('btnLoader').classList.add('hidden');
        document.getElementById('btnText').textContent = "Establish Account";
        feather.replace();
    }
});

window.requestDeactivate = (uid, name) => {
    pendingRequestTarget = { uid, name, type: 'deactivate' };
    requestTargetName.textContent = name || 'this teacher';
    requestReasonInput.value = '';
    requestModal.classList.remove('hidden');
    setTimeout(() => requestModal.classList.add('show'), 10);
};

window.requestReactivate = (uid, name) => {
    pendingRequestTarget = { uid, name, type: 'reactivate' };
    requestTargetName.textContent = name || 'this teacher';
    requestReasonInput.value = '';
    requestModal.classList.remove('hidden');
    setTimeout(() => requestModal.classList.add('show'), 10);
};

const closeRequestModal = () => {
    requestModal.classList.remove('show');
    setTimeout(() => requestModal.classList.add('hidden'), 300);
    pendingRequestTarget = null;
};

if (cancelRequestBtn) cancelRequestBtn.onclick = closeRequestModal;

if (confirmRequestBtn) confirmRequestBtn.onclick = async () => {
    if (!pendingRequestTarget) return;
    const { uid, name, type } = pendingRequestTarget;
    confirmRequestBtn.disabled = true;
    try {
        const result = await api('/admin/deletion_requests.php', {
            method: 'POST',
            body: JSON.stringify({ target_uid: uid, type, reason: requestReasonInput.value.trim() })
        });
        pendingRequests[uid] = { id: result.id, target_uid: uid, status: 'pending' };
        closeRequestModal();
        renderInitialBatch();
        const verb = type === 'reactivate' ? 'Reactivation' : 'Deactivation';
        window.showStatus(`${verb} request for ${name} submitted for approval.`, 'success');
    } catch (error) {
        window.showStatus(error.message || 'Request failed.');
    } finally {
        confirmRequestBtn.disabled = false;
    }
};

syncRegistry();
