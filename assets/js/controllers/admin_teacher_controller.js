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

let registryCache = [];
let filteredCache = [];
let isBatchLoading = false;
let hasMore = false;
let searchQuery = "";
const BATCH_SIZE = 12;

const renderTeacherRow = (id, data, isNew = false) => {
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
                        <p class="text-xs ${isNew ? 'text-green-400 font-black italic' : 'text-gray-400 font-bold'} uppercase tracking-widest mt-2 italic flex items-center gap-2">
                            ${isNew ? '<span class="px-2 py-0.5 bg-green-500/20 rounded text-[9px]">Just Registered</span>' : 'Verified Educator'}
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
                <button onclick="window.purgeTeacher('${id}', '${data.username || data.email || ''}', '${data.email || ''}')" class="p-4 text-gray-500 hover:text-primary-500 transition-all hover:scale-150" title="Purge Record">
                    <i data-feather="trash-2" class="w-6 h-6"></i>
                </button>
            </td>
        </tr>
    `;
};

const syncRegistry = async () => {
    countLabel.innerHTML = '<span class="animate-pulse">Syncing...</span>';
    try {
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

window.purgeTeacher = (uid, username, email) => {
    pendingDelete = { uid, username, email };
    targetLabel.textContent = username;
    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.add('show'), 10);
};

const closeModal = () => {
    modal.classList.remove('show');
    setTimeout(() => modal.classList.add('hidden'), 300);
    pendingDelete = null;
};

cancelBtn.onclick = closeModal;

confirmBtn.onclick = async () => {
    if(!pendingDelete) return;
    const { uid, username } = pendingDelete;
    closeModal();
    window.showStatus(`Initiating full purge...`, 'success');
    try {
        await api('/fetch.php?uid=' + uid, { method: 'DELETE' });
        registryCache = registryCache.filter(t => t.uid !== uid);
        filteredCache = filteredCache.filter(t => t.uid !== uid);
        document.getElementById(`row-${uid}`)?.remove();
        window.showStatus(`Account ${username} erased.`, 'success');
    } catch (error) {
        console.error("Purge Error:", error);
        window.showStatus(`Purge error: ${error.message || 'Check terminal'}`);
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
    subBtn.disabled = true;
    document.getElementById('btnLoader').classList.remove('hidden');
    document.getElementById('btnText').textContent = "Establishing Identity...";
    
    try {
        const result = await api('/auth/register.php', {
            method: 'POST',
            body: JSON.stringify({
                username: data.username || `${data.username}@classsense.com`,
                password: data.password,
                role: 'teacher',
                firstName: data.fname,
                lastName: data.lname,
                employeeId: data.employee_id,
            })
        });
        const newTeacher = {
            uid: result.uid, firstName: data.fname, lastName: data.lname,
            email: data.username || `${data.username}@classsense.com`,
            username: data.username,
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

syncRegistry();
