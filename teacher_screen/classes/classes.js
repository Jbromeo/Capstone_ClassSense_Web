import { api, initPage } from '../../assets/js/custom-auth.js';

const API_BASE = '../api';
let currentTeacherData = null;
let searchTerm = '';
let currentStatusFilter = 'all';
let currentView = 'grid';
let allCurrentClasses = [];
let pollInterval = null;
let lastClassesSig = '';
let hasRendered = false;

// --- UI Modal Handlers ---
window.openModal = () => {
    const modal = document.getElementById('createClassModal');
    const modalContent = document.getElementById('modalContent');
    const modalBackdrop = document.getElementById('modalBackdrop');
    if (!modal) return;
    
    document.querySelectorAll('.day-pill').forEach(p => p.classList.remove('active'));
    document.getElementById('scheduleDaysInput').value = '';

    modal.classList.remove('hidden-form', 'hidden'); 
    setTimeout(() => { 
        modalBackdrop.classList.remove('opacity-0'); 
        modalContent.classList.remove('opacity-0', 'scale-95'); 
    }, 10);
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.day-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            pill.classList.toggle('active');
            const activeDays = Array.from(document.querySelectorAll('.day-pill.active')).map(p => p.dataset.day);
            document.getElementById('scheduleDaysInput').value = activeDays.join('');
        });
    });

    document.querySelectorAll('.edit-day-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            pill.classList.toggle('active');
            const activeDays = Array.from(document.querySelectorAll('.edit-day-pill.active')).map(p => p.dataset.day);
            document.getElementById('editScheduleDaysInput').value = activeDays.join('');
        });
    });
});

const formatStandardTime = (military) => {
    if (!military) return 'TBA';
    if (typeof military !== 'string') return military;
    if (military.includes('AM') || military.includes('PM')) return military;
    const parts = military.split(':');
    if (parts.length < 2) return military;
    let hours = parseInt(parts[0]);
    let minutes = parts[1];
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    return `${hours}:${minutes} ${ampm}`;
};

const searchInput = document.getElementById('globalSearchInput');
if (searchInput) {
    searchInput.addEventListener('keyup', () => {
        searchTerm = searchInput.value.toLowerCase().trim();
        applyFilters();
    });
}

document.querySelectorAll('.filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.filter-chip').forEach(c => {
            c.classList.remove('active', 'bg-white/10', 'text-white', 'border-white/10');
            c.classList.add('bg-dark-bg', 'text-gray-400', 'border-dark-border');
        });
        chip.classList.add('active');
        chip.classList.remove('bg-dark-bg', 'text-gray-400', 'border-dark-border');
        chip.classList.add('bg-white/10', 'text-white', 'border-white/10');
        const label = chip.textContent.trim();
        currentStatusFilter = label === 'All Classes' ? 'all' : label;
        applyFilters();
    });
});

document.querySelectorAll('.view-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const view = btn.dataset.view;
        if (view === currentView) return;
        currentView = view;
        document.querySelectorAll('.view-toggle').forEach(b => {
            b.classList.remove('bg-white/10', 'text-white', 'rounded', 'shadow-sm');
            b.classList.add('text-gray-500');
        });
        btn.classList.add('bg-white/10', 'text-white', 'rounded', 'shadow-sm');
        btn.classList.remove('text-gray-500');
        applyFilters();
    });
});

function applyFilters() {
    let data = [...allCurrentClasses];
    if (currentStatusFilter !== 'all') {
        data = data.filter(c => c.status === currentStatusFilter);
    }
    if (searchTerm) {
        const term = searchTerm.toLowerCase();
        data = data.filter(c =>
            (c.class_name && c.class_name.toLowerCase().includes(term)) ||
            (c.subject && c.subject.toLowerCase().includes(term)) ||
            (c.section_name && c.section_name.toLowerCase().includes(term)) ||
            (c.class_code && c.class_code.toLowerCase().includes(term))
        );
    }
    renderClasses(data);
}

window.closeModal = () => {
    const modal = document.getElementById('createClassModal');
    const modalContent = document.getElementById('modalContent');
    const modalBackdrop = document.getElementById('modalBackdrop');
    if (!modal) return;
    modalBackdrop.classList.add('opacity-0');
    modalContent.classList.add('opacity-0', 'scale-95');
    setTimeout(() => modal.classList.add('hidden'), 300);
};

window.copyGeneratedCode = () => {
    const code = document.getElementById('generatedCodeDisplay').innerText;
    navigator.clipboard.writeText(code).then(() => window.showToast('Class code copied to clipboard!', 'success'));
};

let classToDelete = null;
window.handleDeleteClassClick = (id, name) => {
    classToDelete = id;
    document.getElementById('purgeClassName').innerText = name;
    const modal = document.getElementById('purgeClassModal');
    const content = document.getElementById('purgeContent');
    const backdrop = document.getElementById('purgeBackdrop');
    modal.classList.remove('hidden');
    setTimeout(() => {
        backdrop.classList.remove('opacity-0');
        content.classList.remove('opacity-0', 'scale-95');
    }, 10);
};

window.closePurgeModal = () => {
    const modal = document.getElementById('purgeClassModal');
    const content = document.getElementById('purgeContent');
    const backdrop = document.getElementById('purgeBackdrop');
    backdrop.classList.add('opacity-0');
    content.classList.add('opacity-0', 'scale-95');
    setTimeout(() => modal.classList.add('hidden'), 300);
};

document.getElementById('confirmPurgeBtn').onclick = async () => {
    if (!classToDelete) return;
    try {
        window.showToast('Purging Grid Identity...', 'info');
        await api(`/classes.php?id=${classToDelete}`, { method: 'DELETE' });
        window.showToast('Class purged successfully.', 'success');
        window.closePurgeModal();
    } catch (err) {
        console.error("Purge Error:", err);
        window.showToast('Purge Protocol Failure.', 'error');
    }
};

window.enterHub = (id) => {
    if (!id) return;
    window.location.href = `class_view.php?id=${id}`;
};

window.handleEditClassClick = (id) => {
    const classData = allCurrentClasses.find(c => c.id === id);
    if (!classData) return;

    document.getElementById('editClassId').value = id;
    document.getElementById('editClassNameInput').value = classData.class_name;
    document.getElementById('editLevelInput').value = ['Junior High School', 'Senior High School'].includes(classData.level) ? classData.level : 'Senior High School';
    document.getElementById('editSubjectInput').value = classData.subject;
    document.getElementById('editSectionNameInput').value = classData.section_name || '';
    document.getElementById('editScheduleDaysInput').value = classData.schedule || '';
    document.getElementById('editStartTimeInput').value = classData.start_time || '';
    document.getElementById('editEndTimeInput').value = classData.end_time || '';
    document.getElementById('editStatusInput').value = classData.status === 'Active' ? 'In Progress' : (classData.status || 'In Progress');

    document.querySelectorAll('.edit-day-pill').forEach(pill => {
        const day = pill.dataset.day;
        if (classData.schedule && classData.schedule.includes(day)) {
            pill.classList.add('active');
        } else {
            pill.classList.remove('active');
        }
    });

    const modal = document.getElementById('editClassModal');
    const content = document.getElementById('editModalContent');
    const backdrop = document.getElementById('editModalBackdrop');
    modal.classList.remove('hidden');
    setTimeout(() => {
        backdrop.classList.remove('opacity-0');
        content.classList.remove('opacity-0', 'scale-95');
    }, 10);
};

window.closeEditModal = () => {
    const modal = document.getElementById('editClassModal');
    const content = document.getElementById('editModalContent');
    const backdrop = document.getElementById('editModalBackdrop');
    backdrop.classList.add('opacity-0');
    content.classList.add('opacity-0', 'scale-95');
    setTimeout(() => modal.classList.add('hidden'), 300);
};

window.handleUpdateClass = async () => {
    const id = document.getElementById('editClassId').value;
    const name = document.getElementById('editClassNameInput').value;
    const level = document.getElementById('editLevelInput').value;
    const subject = document.getElementById('editSubjectInput').value;
    const section = document.getElementById('editSectionNameInput').value;
    const schedule = document.getElementById('editScheduleDaysInput').value;
    const start = document.getElementById('editStartTimeInput').value;
    const end = document.getElementById('editEndTimeInput').value;
    const status = document.getElementById('editStatusInput').value;

    if (!name || !section || !schedule || !start || !end) {
        return window.showToast('Details incomplete!', 'error');
    }

    try {
        window.showToast('Updating Class Grid...', 'info');
        await api(`/classes.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify({
                class_name: name, level, subject, section_name: section,
                schedule, start_time: start, end_time: end, status
            })
        });
        window.showToast('Class updated successfully!', 'success');
        window.closeEditModal();
    } catch (err) {
        console.error("Update Error:", err);
        window.showToast('Update protocol failed.', 'error');
    }
};

const renderClasses = (classes) => {
    if (currentView === 'list') return renderClassesListView(classes);
    renderClassesGridView(classes);
};

const renderClassesGridView = (classes) => {
    const grid = document.getElementById('classGrid');
    if (!grid) return;

    if (classes.length === 0) {
        grid.innerHTML = `<div class="col-span-full py-20 text-center opacity-40"><i data-feather="cloud-off" class="w-12 h-12 mx-auto mb-4"></i><p class="text-xs font-black uppercase tracking-widest italic">Foundations await... Archive your first grid above.</p></div>`;
        feather.replace();
        return;
    }

    grid.className = 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 pb-12';
    const cardAnim = hasRendered ? '' : 'animate-fade-in-up ';
    hasRendered = true;
    grid.innerHTML = classes.map(c => `
        <article class="class-card relative glass-panel rounded-2xl overflow-hidden ${cardAnim}flex flex-col h-full group border border-white/5 hover:border-primary-500/30 transition-all duration-300">
            <div class="absolute top-4 right-4 flex items-center gap-2 z-20">
                <span class="flex items-center gap-1 px-2 py-1 rounded-md text-[8px] font-black uppercase tracking-widest italic backdrop-blur-md ${c.status === 'Completed' ? 'bg-green-500/15 text-green-400' : 'bg-amber-500/15 text-amber-400'}"><span class="w-1.5 h-1.5 rounded-full ${c.status === 'Completed' ? 'bg-green-500' : 'bg-amber-500'}"></span>${c.status === 'Completed' ? 'Completed' : 'In Progress'}</span>
                <button onclick="event.stopPropagation(); window.handleEditClassClick('${c.id}')" class="p-1.5 text-white/40 hover:text-blue-500 bg-black/20 hover:bg-black/40 rounded-md transition-all backdrop-blur-md"><i data-feather="edit-2" class="w-3.5 h-3.5"></i></button>
                <button onclick="event.stopPropagation(); window.handleDeleteClassClick('${c.id}', '${(c.class_name || '').replace(/'/g, "\\'")}')" class="p-1.5 text-white/40 hover:text-primary-500 bg-black/20 hover:bg-black/40 rounded-md transition-all backdrop-blur-md"><i data-feather="trash-2" class="w-3.5 h-3.5"></i></button>
            </div>
            <a href="class_view.php?id=${c.id}" class="absolute inset-0 z-10" aria-label="Enter Hub"></a>
            <div class="relative h-32 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-primary-600/20 to-dark-surface/80"></div>
                <div class="absolute top-6 left-6 z-10">
                    <div class="bg-white/5 backdrop-blur-md px-3 py-1 rounded-lg border border-white/10">
                        <span class="text-[10px] font-black text-white italic uppercase tracking-tighter italic">${c.class_code}</span>
                    </div>
                </div>
            </div>
            <div class="p-5 flex flex-col flex-1 relative z-0">
                <div class="mb-4">
                    <span class="text-[10px] font-black text-primary-400 uppercase tracking-widest italic tracking-tighter italic">${c.section_name}</span>
                    <h3 class="text-lg font-bold text-white group-hover:text-primary-400 transition-colors uppercase tracking-widest italic tracking-tighter italic leading-none mt-1">${c.class_name}</h3>
                    <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mt-2 italic tracking-tighter italic">${c.subject} &bull; ${c.level}</p>
                    <div class="mt-3 flex items-center gap-2 opacity-70">
                        <i data-feather="clock" class="w-3 h-3 text-primary-400"></i>
                        <span class="text-[9px] font-black text-gray-300 uppercase tracking-widest italic tracking-tighter">
                            ${c.schedule || 'Schedule TBA'} &bull; ${c.time_slot || 'TBA'}
                        </span>
                    </div>
                </div>
                <div class="mt-auto pt-4 border-t border-white/5 flex items-center justify-between">
                    <div class="flex items-center text-[9px] font-black text-gray-400 uppercase tracking-widest italic tracking-tighter italic opacity-60">
                        <i data-feather="users" class="w-3.5 h-3.5 mr-2 text-primary-500"></i> ${(c.students || []).length} Registered
                    </div>
                    <div class="p-2 bg-primary-500/10 rounded-lg text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-all">
                        <i data-feather="arrow-right" class="w-4 h-4"></i>
                    </div>
                </div>
            </div>
        </article>
    `).join('');
    feather.replace();
};

const renderClassesListView = (classes) => {
    const grid = document.getElementById('classGrid');
    if (!grid) return;

    if (classes.length === 0) {
        grid.innerHTML = `<div class="col-span-full py-20 text-center opacity-40"><i data-feather="cloud-off" class="w-12 h-12 mx-auto mb-4"></i><p class="text-xs font-black uppercase tracking-widest italic">Foundations await... Archive your first grid above.</p></div>`;
        feather.replace();
        return;
    }

    grid.className = 'pb-12';
    grid.innerHTML = `<div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-white/5 text-[9px] font-black uppercase tracking-widest italic text-gray-500">
                    <th class="text-left p-4 pl-6">Class</th>
                    <th class="text-left p-4 hidden md:table-cell">Code</th>
                    <th class="text-left p-4 hidden lg:table-cell">Schedule</th>
                    <th class="text-center p-4 hidden sm:table-cell">Students</th>
                    <th class="text-center p-4">Status</th>
                    <th class="text-right p-4 pr-6">Actions</th>
                </tr>
            </thead>
            <tbody>
                ${classes.map(c => `
                <tr class="border-b border-white/5 last:border-0 hover:bg-white/5 transition-all cursor-pointer group" onclick="window.location.href='class_view.php?id=${c.id}'">
                    <td class="p-4 pl-6">
                        <p class="text-sm font-bold text-white group-hover:text-primary-400 transition-colors uppercase tracking-tight italic">${c.class_name}</p>
                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest italic mt-0.5">${c.section_name} &bull; ${c.subject}</p>
                    </td>
                    <td class="p-4 hidden md:table-cell">
                        <span class="text-[10px] font-mono font-bold text-gray-400">${c.class_code}</span>
                    </td>
                    <td class="p-4 hidden lg:table-cell">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider italic">${c.schedule || 'TBA'} ${c.time_slot ? '&bull; ' + c.time_slot : ''}</span>
                    </td>
                    <td class="p-4 text-center hidden sm:table-cell">
                        <span class="text-sm font-black text-gray-300">${(c.students || []).length}</span>
                    </td>
                    <td class="p-4 text-center">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-[8px] font-black uppercase tracking-widest italic ${c.status === 'Completed' ? 'bg-green-500/15 text-green-400' : 'bg-amber-500/15 text-amber-400'}"><span class="w-1.5 h-1.5 rounded-full ${c.status === 'Completed' ? 'bg-green-500' : 'bg-amber-500'}"></span>${c.status === 'Completed' ? 'Completed' : 'In Progress'}</span>
                    </td>
                    <td class="p-4 pr-6 text-right">
                        <button onclick="event.stopPropagation(); window.handleEditClassClick('${c.id}')" class="p-1.5 text-gray-500 hover:text-blue-400 hover:bg-white/5 rounded-md transition-all"><i data-feather="edit-2" class="w-3.5 h-3.5"></i></button>
                        <button onclick="event.stopPropagation(); window.handleDeleteClassClick('${c.id}', '${(c.class_name || '').replace(/'/g, "\\'")}')" class="p-1.5 text-gray-500 hover:text-primary-500 hover:bg-white/5 rounded-md transition-all"><i data-feather="trash-2" class="w-3.5 h-3.5"></i></button>
                    </td>
                </tr>`).join('')}
            </tbody>
        </table>
    </div>`;
    feather.replace();
};

window.handleCreateClass = async () => {
    const user = JSON.parse(sessionStorage.getItem('cs_user') || 'null');
    const nameEl = document.getElementById('classNameInput');
    const sectionEl = document.getElementById('sectionNameInput');
    const scheduleDays = document.getElementById('scheduleDaysInput').value;
    const startTime = document.getElementById('startTimeInput').value;
    const endTime = document.getElementById('endTimeInput').value;

    if (!user) return window.showToast('Authentication lag. Refresh required.', 'error');
    if (!nameEl.value || !sectionEl.value || !scheduleDays || !startTime || !endTime) {
        return window.showToast('Schedule parameters incomplete!', 'error');
    }
    if (startTime === endTime) {
        return window.showToast('Error: Session cannot start and end at the same time!', 'error');
    }
    const startVal = parseInt(startTime.replace(':', ''));
    const endVal = parseInt(endTime.replace(':', ''));
    if (startVal > endVal) {
        return window.showToast('Error: Closing time must fall AFTER start time!', 'error');
    }

    try {
        window.showToast('Establishing Secure Grid...', 'info');

        const result = await api('/classes.php', {
            method: 'POST',
            body: JSON.stringify({
                class_name: nameEl.value,
                level: document.getElementById('levelInput').value,
                subject: document.getElementById('subjectInput').value,
                section_name: sectionEl.value,
                schedule: scheduleDays || 'TBA',
                start_time: startTime,
                end_time: endTime,
                teacher_name: (currentTeacherData && (currentTeacherData.full_name || `${currentTeacherData.firstName || ''} ${currentTeacherData.lastName || ''}`.trim())) || (user.displayName || 'Faculty Account'),
            })
        });

        document.getElementById('generatedCodeDisplay').innerText = result.class_code;
        document.getElementById('createClassModal').classList.add('hidden-form');
        window.showToast('Hub established successfully.', 'success');

        nameEl.value = '';
        sectionEl.value = '';
        document.getElementById('scheduleDaysInput').value = '';
        document.getElementById('startTimeInput').value = '';
        document.getElementById('endTimeInput').value = '';

        await loadClasses();
    } catch (error) {
        console.error("Grid Sync Failure:", error);
        window.showToast(error.message || 'Cloud architecture error.', 'error');
    }
};

async function loadClasses() {
    try {
        const classes = await api('/classes.php');
        const sig = JSON.stringify(classes.map(c => [
            c.id, c.class_name, c.level, c.subject, c.section_name, c.class_code,
            c.schedule, c.start_time, c.end_time, c.time_slot, c.session_limit, c.status,
            (c.students || []).slice().sort().join(',')
        ]));
        if (sig === lastClassesSig) return;
        lastClassesSig = sig;
        allCurrentClasses = classes;
        applyFilters();
    } catch (error) {
        console.error("Poll Error:", error);
        const grid = document.getElementById('classGrid');
        if (grid) {
            grid.innerHTML = `<div class="col-span-full py-20 text-center opacity-40"><i data-feather="alert-triangle" class="w-12 h-12 mx-auto mb-4 text-primary-500 animate-pulse"></i><p class="text-xs font-black uppercase tracking-widest italic text-primary-400">Sync Protocol Denied</p></div>`;
            feather.replace();
        }
    }
}

initPage(() => {
    setTimeout(() => loadClasses(), 500);
    pollInterval = setInterval(loadClasses, 10000);
});

const cached = localStorage.getItem('cs_cached_profile');
if (cached) currentTeacherData = JSON.parse(cached);

feather.replace();

// Non-Module UI Scripts
window.showToast = (message, type = 'info') => {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast flex items-center w-full max-w-xs p-4 space-x-3 text-gray-200 bg-dark-surface rounded-lg shadow-xl border border-dark-border transform translate-x-full transition-transform duration-300 ${type === 'success' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-primary-500'}`;
    const icon = type === 'success' ? 'check-circle' : 'info';
    toast.innerHTML = `<div class="flex-shrink-0 text-${type === 'success' ? 'green' : 'primary'}-400"><i data-feather="${icon}" class="w-5 h-5"></i></div><div class="text-[10px] font-black uppercase tracking-widest italic text-white">${message}</div>`;
    container.appendChild(toast); 
    feather.replace();
    setTimeout(() => toast.classList.remove('translate-x-full'), 10);
    setTimeout(() => { toast.classList.add('translate-x-full'); setTimeout(() => toast.remove(), 300); }, 3000);
};

const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileOverlay = document.getElementById('mobileOverlay');
const sidebar = document.getElementById('sidebar');
if(mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', () => {
        sidebar.classList.remove('hidden');
        sidebar.classList.add('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64');
        mobileOverlay.classList.add('open');
    });
}
if(mobileOverlay) {
    mobileOverlay.addEventListener('click', () => {
        sidebar.classList.add('hidden');
        mobileOverlay.classList.remove('open');
    });
}