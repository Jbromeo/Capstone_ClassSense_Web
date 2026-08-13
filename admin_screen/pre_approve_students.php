<?php
require_once dirname(__DIR__) . '/core/init.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense Admin | Pre-Approved Students</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .toast { transform: translateX(120%); transition: all 0.4s cubic-bezier(0.68, -0.55, 0.26, 1.55); opacity: 0; }
        .toast.show { transform: translateX(0); opacity: 1; }
        @keyframes idShake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }
        .shake { animation: idShake 0.4s ease-in-out; }
    </style>
</head>
<body class="antialiased h-screen overflow-hidden flex bg-dark-bg selection:bg-primary-500 selection:text-white">

    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-[20%] w-[500px] h-[500px] bg-blue-600/10 rounded-full mix-blend-screen filter blur-3xl animate-blob-slow transform -translate-y-1/2"></div>
        <div class="absolute bottom-0 left-[20%] w-[500px] h-[500px] bg-blue-600/10 rounded-full mix-blend-screen filter blur-3xl animate-blob-slow transform translate-y-1/2" style="animation-delay: 4s"></div>
    </div>

    <?php include 'admin_sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 h-full relative">
        <header class="h-20 flex-shrink-0 glass-panel border-b-0 border-dark-border flex items-center justify-between px-8 z-20">
            <h2 class="text-xl font-bold text-white italic">Pre-Approved Students <span class="text-xs text-gray-500 font-normal ml-3 tracking-widest uppercase">Admin Panel</span></h2>
            <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

                <!-- Add Student IDs Form -->
                <div class="xl:col-span-1 space-y-6">
                    <div class="glass-panel rounded-2xl p-6 border-l-4 border-l-blue-500 animate-fade-in-up">
                        <h3 class="text-lg font-bold text-white mb-2">Add Student IDs</h3>
                        <p class="text-gray-400 text-sm mb-6 font-medium leading-relaxed italic uppercase tracking-tighter">Paste comma-separated Student IDs below to pre-approve them for registration.</p>

                        <form id="addIdsForm" class="space-y-4">
                            <div>
                                <label id="studentIdsLabel" class="block text-xs font-bold text-gray-500 uppercase mb-2">Student IDs</label>
                                <textarea id="studentIdsInput" rows="8" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all font-mono placeholder-gray-600" placeholder="20250001, 20250002, 20250003&#10;&#10;or one per line:&#10;20250004&#10;20250005"></textarea>
                            </div>

                            <button type="submit" id="submitBtn" class="w-full py-3 bg-blue-600 hover:bg-blue-700 rounded-xl font-bold text-white transition-all shadow-lg shadow-blue-500/20 mt-4 flex items-center justify-center gap-2 group">
                                <span id="btnText">Add to Pre-Approved List</span>
                                <div id="btnLoader" class="hidden w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></div>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Pre-Approved List Table -->
                <div class="xl:col-span-2 space-y-6">
                    <div class="glass-panel rounded-2xl overflow-hidden animate-fade-in-up flex flex-col h-[700px]" style="animation-delay: 0.1s">
                        <div class="p-6 border-b border-dark-border bg-white/5 space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-bold text-white tracking-tight leading-none uppercase">Approved Students Registry</h3>
                                <div class="flex items-center gap-3">
                                    <div class="px-3 py-1 bg-blue-500/10 border border-blue-500/20 rounded-lg shadow-inner">
                                        <span id="entryCount" class="text-blue-400 font-bold text-[10px] uppercase tracking-widest leading-none italic">Syncing...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="relative group">
                                <i data-feather="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 group-focus-within:text-blue-500 transition-colors"></i>
                                <input type="text" id="entrySearch" placeholder="Search by Student ID or Name..." class="w-full bg-dark-bg border border-dark-border rounded-xl pl-11 pr-4 py-3 text-sm text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all placeholder-gray-600 font-medium italic uppercase tracking-tighter">
                            </div>
                        </div>

                        <div id="tableScrollArea" class="flex-1 overflow-y-auto custom-scroll">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-dark-surface text-gray-500 uppercase text-xs font-black tracking-widest border-b border-dark-border">
                                        <th class="px-6 py-5">Student ID</th>
                                        <th class="px-6 py-5">Status</th>
                                        <th class="px-6 py-5">Registered Name</th>
                                        <th class="px-6 py-5">Registered Username</th>
                                        <th class="px-6 py-5">Date Added</th>
                                        <th class="px-6 py-5">Date Used</th>
                                        <th class="px-6 py-5 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="entriesBody" class="divide-y divide-dark-border text-sm italic"></tbody>
                            </table>
                            <div id="loadMoreSentinel" class="p-10 text-center border-t border-white/[0.02]">
                                <div id="lazyLoader" class="flex flex-col items-center gap-3 opacity-0 transition-opacity duration-300">
                                    <div class="w-8 h-8 border-2 border-blue-500/20 border-t-blue-500 rounded-full animate-spin"></div>
                                    <p class="text-[9px] text-gray-500 font-black uppercase tracking-widest italic animate-pulse">Expanding Registry...</p>
                                </div>
                                <div id="endOfList" class="hidden py-4 text-[9px] text-gray-600 font-bold uppercase tracking-[0.2em] italic">No more entries in registry</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script type="module">
        import { api } from '../assets/js/custom-auth.js';

        const tableBody = document.getElementById('entriesBody');
        const countLabel = document.getElementById('entryCount');
        const addForm = document.getElementById('addIdsForm');
        const subBtn = document.getElementById('submitBtn');
        const searchInput = document.getElementById('entrySearch');
        const sentinel = document.getElementById('loadMoreSentinel');
        const scrollArea = document.getElementById('tableScrollArea');
        const lazyLoader = document.getElementById('lazyLoader');
        const endOfList = document.getElementById('endOfList');
        const studentIdsInput = document.getElementById('studentIdsInput');
        const studentIdsLabel = document.getElementById('studentIdsLabel');

        const vibrateLabel = (label) => {
            if (!label) return;
            label.classList.remove('shake');
            void label.offsetWidth;
            label.classList.add('shake');
        };

        const isValidStudentId = (id) => /^\d{1,8}$/.test(id);

        let registryCache = [];
        let filteredCache = [];
        let isBatchLoading = false;
        let hasMore = false;
        const BATCH_SIZE = 15;

        const renderRow = (data) => {
            const isUsed = data.is_used;
            return `
                <tr id="row-${data.id}" class="hover:bg-white/5 transition-colors group">
                    <td class="px-6 py-5 font-mono text-blue-400 text-sm font-black tracking-tight">${data.student_id}</td>
                    <td class="px-6 py-5">
                        ${isUsed
                            ? '<span class="px-3 py-1 rounded-full bg-gray-500/10 border border-gray-500/20 text-gray-400 text-[10px] font-black uppercase tracking-widest">Used</span>'
                            : '<span class="px-3 py-1 rounded-full bg-green-500/10 border border-green-500/20 text-green-400 text-[10px] font-black uppercase tracking-widest">Available</span>'
                        }
                    </td>
                    <td class="px-6 py-5 font-bold text-white tracking-tight">${isUsed ? (data.first_name + ' ' + data.last_name).trim() || '—' : '—'}</td>
                    <td class="px-6 py-5 text-gray-400 text-xs">${isUsed ? data.email || '—' : '—'}</td>
                    <td class="px-6 py-5 text-gray-500 text-xs">${data.created_at || '—'}</td>
                    <td class="px-6 py-5 text-gray-500 text-xs">${data.used_at || '—'}</td>
                    <td class="px-6 py-5 text-center">
                        ${isUsed
                            ? `<button onclick="window.resetEntry(${data.id})" class="p-2 text-blue-500 hover:text-blue-400 transition-all hover:scale-150" title="Reset to Available"><i data-feather="refresh-ccw" class="w-4 h-4"></i></button>
                               <button onclick="window.deleteEntry(${data.id})" class="p-2 text-gray-500 hover:text-primary-500 transition-all hover:scale-150" title="Remove"><i data-feather="trash-2" class="w-4 h-4"></i></button>`
                            : `<button onclick="window.deleteEntry(${data.id})" class="p-3 text-gray-500 hover:text-primary-500 transition-all hover:scale-150" title="Remove"><i data-feather="trash-2" class="w-4 h-4"></i></button>`
                        }
                    </td>
                </tr>
            `;
        };

        const syncRegistry = async () => {
            countLabel.innerHTML = '<span class="animate-pulse">Syncing...</span>';
            try {
                const entries = await api('/admin/pre_approve.php');
                registryCache = entries;
                filteredCache = [...registryCache];
                renderInitialBatch();
            } catch (err) {
                console.error("Sync Error:", err);
                tableBody.innerHTML = `<tr><td colspan="7" class="px-6 py-20 text-center">
                    <p class="text-gray-500 italic text-sm">Failed to load registry</p>
                    <p class="text-gray-600 text-[10px] mt-1">${err.message || 'Connection error'}</p>
                </td></tr>`;
                countLabel.innerHTML = '<span class="text-primary-500">Sync Error</span>';
            }
        };

        const renderInitialBatch = () => {
            const batch = filteredCache.slice(0, BATCH_SIZE);
            tableBody.innerHTML = batch.length ?
                batch.map(r => renderRow(r)).join('') :
                `<tr><td colspan="7" class="px-6 py-20 text-center text-gray-500 italic">No entries found.</td></tr>`;

            countLabel.innerHTML = `<span>${filteredCache.length} Entry${filteredCache.length !== 1 ? 's' : ''}</span>`;
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
                    tableBody.insertAdjacentHTML('beforeend', nextBatch.map(r => renderRow(r)).join(''));
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

        window.resetEntry = async (id) => {
            showConfirmModal({
                icon: 'refresh-ccw',
                iconBg: 'bg-blue-500/10',
                iconColor: 'text-blue-500',
                title: 'Reset to Available?',
                message: 'This will delete the linked student account and free up their Student ID for reuse.',
                confirmText: 'Yes, Reset',
                confirmBg: 'bg-blue-600 hover:bg-blue-700',
                onConfirm: async () => {
                    try {
                        await api('/admin/pre_approve.php?id=' + id + '&reset=1', { method: 'DELETE' });
                        const entry = registryCache.find(e => e.id === id);
                        if (entry) {
                            entry.is_used = false;
                            entry.used_at = null;
                        }
                        const row = document.getElementById('row-' + id);
                        if (row && entry) row.outerHTML = renderRow(entry);
                        feather.replace();
                        window.showStatus('Entry reset to Available.', 'success');
                    } catch (error) {
                        window.showStatus(error.message || 'Reset failed');
                    }
                }
            });
        };

        window.deleteEntry = async (id) => {
            showConfirmModal({
                icon: 'trash-2',
                iconBg: 'bg-primary-500/10',
                iconColor: 'text-primary-500',
                title: 'Remove Entry?',
                message: 'This will permanently remove this Student ID from the pre-approved list.',
                confirmText: 'Yes, Remove',
                confirmBg: 'bg-primary-600 hover:bg-primary-700',
                onConfirm: async () => {
                    try {
                        await api('/admin/pre_approve.php?id=' + id, { method: 'DELETE' });
                        registryCache = registryCache.filter(e => e.id !== id);
                        filteredCache = filteredCache.filter(e => e.id !== id);
                        document.getElementById('row-' + id)?.remove();
                        window.showStatus('Entry removed.', 'success');
                    } catch (error) {
                        window.showStatus(error.message || 'Delete failed');
                    }
                }
            });
        };

        searchInput.addEventListener('input', (e) => {
            const q = e.target.value.trim().toLowerCase();
            filteredCache = registryCache.filter(e =>
                (e.student_id || '').toLowerCase().includes(q) ||
                (e.first_name || '').toLowerCase().includes(q) ||
                (e.last_name || '').toLowerCase().includes(q) ||
                (e.email || '').toLowerCase().includes(q)
            );
            renderInitialBatch();
            scrollArea.scrollTo({ top: 0 });
        });

        studentIdsInput.addEventListener('input', () => {
            const ids = studentIdsInput.value.split(/[,\n]+/).map(s => s.trim()).filter(s => s);
            if (ids.some(id => !isValidStudentId(id))) vibrateLabel(studentIdsLabel);
        });

        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && hasMore) loadMoreFromCache();
        }, { threshold: 0, root: scrollArea });
        observer.observe(sentinel);

        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('studentIdsInput');
            const raw = input.value.trim();
            if (!raw) return;

            // Split by comma or newline
            const ids = raw.split(/[,\n]+/).map(s => s.trim()).filter(s => s);
            const invalidIds = ids.filter(id => !isValidStudentId(id));
            if (invalidIds.length) {
                vibrateLabel(studentIdsLabel);
                return window.showStatus(`Invalid Student ID: "${invalidIds[0]}" - numbers only, 8 digits maximum.`);
            }

            subBtn.disabled = true;
            document.getElementById('btnLoader').classList.remove('hidden');
            document.getElementById('btnText').textContent = 'Adding...';

            try {
                const result = await api('/admin/pre_approve.php', {
                    method: 'POST',
                    body: JSON.stringify({ student_ids: ids })
                });
                window.showStatus(`${result.inserted} added, ${result.skipped} already in list.`, 'success');
                input.value = '';
                syncRegistry();
            } catch (error) {
                window.showStatus(error.message || 'Failed to add IDs');
            } finally {
                subBtn.disabled = false;
                document.getElementById('btnLoader').classList.add('hidden');
                document.getElementById('btnText').textContent = 'Add to Pre-Approved List';
            }
        });

        syncRegistry();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();
            window.showStatus = (message, type = 'error') => {
                const container = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                const isError = type === 'error';
                toast.className = `toast flex items-center w-full max-w-xs p-4 space-x-4 text-gray-200 bg-dark-surface rounded-lg shadow-2xl border border-dark-border ${isError ? 'border-l-4 border-l-primary-500' : 'border-l-4 border-l-blue-500'}`;
                toast.innerHTML = `<div class="flex-shrink-0"><i data-feather="${isError ? 'alert-circle' : 'check-circle'}" class="w-5 h-5 ${isError ? 'text-primary-500' : 'text-blue-500'}"></i></div><div class="text-xs font-semibold">${message}</div>`;
                container.appendChild(toast);
                feather.replace();
                setTimeout(() => toast.classList.add('show'), 10);
                setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 4000);
            };
        });
    </script>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="absolute inset-0 bg-dark-bg/40 backdrop-blur-md"></div>
        <div class="glass-panel w-full max-w-sm rounded-[2.5rem] p-10 border border-white/10 shadow-2xl transform scale-95 transition-transform duration-300 relative z-10 text-center">
            <div id="confirmIconWrap" class="w-16 h-16 bg-primary-500/10 rounded-full flex items-center justify-center mx-auto mb-6 border border-primary-500/10">
                <i id="confirmIcon" data-feather="alert-triangle" class="w-8 h-8 text-primary-500"></i>
            </div>
            <h3 id="confirmTitle" class="text-2xl font-black text-white italic mb-2 tracking-tight">Confirm</h3>
            <p id="confirmMessage" class="text-gray-400 text-sm mb-10 leading-relaxed font-bold italic opacity-80 uppercase tracking-widest text-[10px]">Are you sure?</p>
            <div class="grid grid-cols-2 gap-4">
                <button id="closeConfirmModal" class="w-full py-4 bg-white/5 hover:bg-white/10 rounded-2xl font-black text-gray-500 hover:text-white transition-all text-xs uppercase tracking-widest leading-none">No</button>
                <button id="confirmAction" class="w-full py-4 bg-primary-600 hover:bg-primary-700 rounded-2xl font-black text-white transition-all shadow-lg shadow-primary-500/20 uppercase tracking-[0.2em] italic text-xs leading-none">Yes</button>
            </div>
        </div>
    </div>

    <style>
        #confirmModal.show { opacity: 1; }
        #confirmModal.show > div:last-child { transform: scale(1); }
    </style>

    <script>
        function showConfirmModal({ icon = 'alert-triangle', iconBg = 'bg-primary-500/10', iconColor = 'text-primary-500', title = 'Confirm', message = 'Are you sure?', confirmText = 'Yes', confirmBg = 'bg-primary-600 hover:bg-primary-700', onConfirm }) {
            const modal = document.getElementById('confirmModal');
            const iconWrap = document.getElementById('confirmIconWrap');
            const iconEl = document.getElementById('confirmIcon');
            const titleEl = document.getElementById('confirmTitle');
            const msgEl = document.getElementById('confirmMessage');
            const confirmBtn = document.getElementById('confirmAction');
            const cancelBtn = document.getElementById('closeConfirmModal');

            iconWrap.className = `w-16 h-16 ${iconBg} rounded-full flex items-center justify-center mx-auto mb-6 border border-primary-500/10`;
            iconEl.setAttribute('data-feather', icon);
            iconEl.className = `w-8 h-8 ${iconColor}`;
            titleEl.textContent = title;
            msgEl.textContent = message;
            confirmBtn.textContent = confirmText;
            confirmBtn.className = `w-full py-4 ${confirmBg} rounded-2xl font-black text-white transition-all shadow-lg shadow-primary-500/20 uppercase tracking-[0.2em] italic text-xs leading-none`;

            const close = () => {
                modal.classList.remove('show');
                setTimeout(() => modal.classList.add('hidden'), 300);
            };

            cancelBtn.onclick = close;
            confirmBtn.onclick = async () => {
                close();
                if (onConfirm) await onConfirm();
            };

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('show');
                feather.replace();
            }, 10);
        }
    </script>
</body>
</html>
