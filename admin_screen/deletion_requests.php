<!-- admin_screen/deletion_requests.php -->
<?php
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php';
$is_super = (($_SESSION['role'] ?? '') === 'super_admin');
$current_uid = $_SESSION['uid'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense Admin | Deletion Requests</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .toast { transform: translateX(120%); transition: all 0.4s cubic-bezier(0.68, -0.55, 0.26, 1.55); opacity: 0; }
        .toast.show { transform: translateX(0); opacity: 1; }
        .request-details { display: grid; grid-template-rows: 0fr; transition: grid-template-rows 0.3s ease; }
        .request-details.open { grid-template-rows: 1fr; }
        .request-details > div { overflow: hidden; }
    </style>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white bg-dark-bg">

    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-[25%] w-[600px] h-[600px] bg-primary-600/5 rounded-full mix-blend-screen filter blur-3xl animate-blob-slow transform -translate-y-1/2"></div>
    </div>

    <?php include 'admin_sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <header class="h-20 glass-panel border-b border-white/5 flex items-center justify-between px-8 z-20">
            <h2 class="text-xl font-black text-white tracking-tighter uppercase leading-none">Deletion Requests
                <span class="text-[10px] text-primary-400 font-bold ml-4 uppercase tracking-[0.2em] opacity-60"><?php echo $is_super ? 'Approval Queue' : 'My Requests'; ?></span>
            </h2>
            <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-5xl mx-auto space-y-6">

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <button data-status="pending" class="stat-tab glass-panel rounded-2xl border border-white/5 p-4 text-left transition-all">
                        <div class="flex items-center justify-between">
                            <span class="p-2 rounded-lg bg-amber-500/10 border border-amber-500/20"><i data-feather="inbox" class="w-4 h-4 text-amber-400"></i></span>
                            <span id="statCountPending" class="text-2xl font-black text-white italic tracking-tight">0</span>
                        </div>
                        <p class="text-[10px] font-black text-white uppercase tracking-widest italic mt-3">Pending</p>
                        <p id="statSubPending" class="text-[9px] text-gray-500 font-bold uppercase tracking-widest italic mt-0.5"><?php echo $is_super ? 'Needs your review' : 'Awaiting review'; ?></p>
                    </button>

                    <button data-status="approved" class="stat-tab glass-panel rounded-2xl border border-white/5 p-4 text-left transition-all">
                        <div class="flex items-center justify-between">
                            <span class="p-2 rounded-lg bg-green-500/10 border border-green-500/20"><i data-feather="check-circle" class="w-4 h-4 text-green-400"></i></span>
                            <span id="statCountApproved" class="text-2xl font-black text-white italic tracking-tight">0</span>
                        </div>
                        <p class="text-[10px] font-black text-white uppercase tracking-widest italic mt-3">Approved</p>
                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest italic mt-0.5">Account deleted</p>
                    </button>

                    <button data-status="rejected" class="stat-tab glass-panel rounded-2xl border border-white/5 p-4 text-left transition-all">
                        <div class="flex items-center justify-between">
                            <span class="p-2 rounded-lg bg-primary-500/10 border border-primary-500/20"><i data-feather="x-circle" class="w-4 h-4 text-primary-400"></i></span>
                            <span id="statCountRejected" class="text-2xl font-black text-white italic tracking-tight">0</span>
                        </div>
                        <p class="text-[10px] font-black text-white uppercase tracking-widest italic mt-3">Rejected</p>
                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest italic mt-0.5">Request denied</p>
                    </button>

                    <button data-status="cancelled" class="stat-tab glass-panel rounded-2xl border border-white/5 p-4 text-left transition-all">
                        <div class="flex items-center justify-between">
                            <span class="p-2 rounded-lg bg-white/5 border border-white/10"><i data-feather="slash" class="w-4 h-4 text-gray-400"></i></span>
                            <span id="statCountCancelled" class="text-2xl font-black text-white italic tracking-tight">0</span>
                        </div>
                        <p class="text-[10px] font-black text-white uppercase tracking-widest italic mt-3">Cancelled</p>
                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest italic mt-0.5">Withdrawn</p>
                    </button>
                </div>

                <div class="glass-panel rounded-2xl border border-white/5 p-4 flex items-center gap-3">
                    <i data-feather="search" class="w-4 h-4 text-gray-500 flex-shrink-0"></i>
                    <input id="searchInput" type="text" autocomplete="off" class="flex-1 bg-transparent text-xs text-white placeholder-gray-600 outline-none" placeholder="Search by name or username...">
                    <span id="listCount" class="text-[10px] text-gray-500 font-bold uppercase tracking-widest italic flex-shrink-0"></span>
                </div>

                <div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
                    <div id="requestList" class="divide-y divide-dark-border">
                        <p class="px-8 py-16 text-center text-gray-500 italic text-sm">Loading requests...</p>
                    </div>
                </div>

                <?php if (!$is_super): ?>
                <p class="text-[10px] text-gray-500 uppercase tracking-widest italic font-bold text-center">Requests are reviewed and approved by the super admin.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div id="modalBackdrop" class="absolute inset-0 bg-dark-bg/70 backdrop-blur-sm"></div>
        <div class="relative glass-panel w-full max-w-md rounded-2xl border border-white/10 shadow-2xl p-6 animate-fade-in-up">
            <div class="flex items-start gap-4">
                <div id="modalIconBox" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-primary-500/10 border border-primary-500/20">
                    <i data-feather="alert-triangle" class="w-5 h-5 text-primary-400"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 id="modalTitle" class="text-sm font-black text-white italic uppercase tracking-tight"></h3>
                    <p id="modalBody" class="text-xs text-gray-400 mt-2 leading-relaxed"></p>
                </div>
            </div>
            <div id="modalTarget" class="hidden mt-4 p-3 rounded-xl bg-dark-bg border border-dark-border flex items-center gap-3"></div>
            <div class="flex items-center justify-end gap-3 mt-6">
                <button id="modalCancelBtn" class="px-4 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-[10px] font-black text-gray-400 hover:text-white uppercase tracking-widest italic transition-all">Cancel</button>
                <button id="modalConfirmBtn" class="px-4 py-2.5 rounded-xl text-[10px] font-black text-white uppercase tracking-widest italic transition-all"></button>
            </div>
        </div>
    </div>

    <script>
        window.CURRENT_ROLE = <?php echo json_encode($_SESSION['role'] ?? 'admin'); ?>;
        window.CURRENT_UID = <?php echo json_encode($current_uid); ?>;
    </script>
    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';

        const list = document.getElementById('requestList');
        const searchInput = document.getElementById('searchInput');
        const listCount = document.getElementById('listCount');
        const isSuper = window.CURRENT_ROLE === 'super_admin';
        const currentUid = window.CURRENT_UID || '';

        const confirmModal = document.getElementById('confirmModal');
        const modalBackdrop = document.getElementById('modalBackdrop');
        const modalIconBox = document.getElementById('modalIconBox');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const modalTarget = document.getElementById('modalTarget');
        const modalCancelBtn = document.getElementById('modalCancelBtn');
        const modalConfirmBtn = document.getElementById('modalConfirmBtn');

        let requests = [];
        let activeStatus = 'pending';
        let searchTerm = '';
        let confirmLabel = '';

        window.showStatus = (message, type = 'error') => {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            const isError = type === 'error';
            toast.className = `toast flex items-center w-full max-w-xs p-4 space-x-4 text-gray-200 bg-dark-surface rounded-lg shadow-2xl border border-dark-border ${isError ? 'border-l-4 border-l-primary-500' : 'border-l-4 border-l-green-500'}`;
            toast.innerHTML = `<div class="flex-shrink-0"><i data-feather="${isError ? 'alert-circle' : 'check-circle'}" class="w-5 h-5 ${isError ? 'text-primary-500' : 'text-green-500'}"></i></div><div class="text-xs font-semibold">${message}</div>`;
            container.appendChild(toast);
            feather.replace();
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 4000);
        };

        const ROLE_META = {
            teacher:    { label: 'Teacher',    color: 'text-purple-400', avatar: 'from-purple-600 to-purple-900' },
            student:    { label: 'Student',    color: 'text-blue-400',   avatar: 'from-blue-600 to-blue-900' },
            admin:      { label: 'Admin',      color: 'text-amber-400',  avatar: 'from-amber-500 to-amber-800' },
            super_admin: { label: 'Super Admin', color: 'text-amber-500', avatar: 'from-amber-400 to-amber-700' },
        };

        const STATUS_META = {
            pending:  { label: 'Pending',   color: 'bg-amber-500/10 text-amber-400 border-amber-500/20' },
            approved: { label: 'Approved',  color: 'bg-green-500/10 text-green-400 border-green-500/20' },
            rejected: { label: 'Rejected',  color: 'bg-primary-500/10 text-primary-400 border-primary-500/20' },
            cancelled:{ label: 'Cancelled', color: 'bg-gray-500/10 text-gray-400 border-gray-500/20' },
        };

        const STAT_ACTIVE = {
            pending:   ['ring-1', 'ring-amber-500/30', 'bg-amber-500/5'],
            approved:  ['ring-1', 'ring-green-500/30', 'bg-green-500/5'],
            rejected:  ['ring-1', 'ring-primary-500/30', 'bg-primary-500/5'],
            cancelled: ['ring-1', 'ring-gray-500/30', 'bg-white/5'],
        };
        const STAT_ALL = Object.values(STAT_ACTIVE).flat();

        const EMPTY_STATE = {
            pending:   { icon: 'inbox',       title: "No pending requests — you're all caught up.", msg: 'New requests from admins will appear here.' },
            approved:  { icon: 'check-circle', title: 'Nothing approved yet.',                      msg: 'Approved requests will appear here.' },
            rejected:  { icon: 'x-circle',    title: 'Nothing rejected.',                          msg: 'Rejected requests will appear here.' },
            cancelled: { icon: 'slash',       title: 'Nothing cancelled.',                         msg: 'Cancelled requests will appear here.' },
        };

        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        const initials = (name) => (name || '').split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('') || '?';

        const parseDate = (s) => {
            const d = new Date(String(s).replace(' ', 'T').replace(/\.\d+$/, ''));
            return isNaN(d.getTime()) ? null : d;
        };

        const fmtDate = (s) => {
            const d = parseDate(s);
            return d ? d.toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : String(s || '');
        };

        const timeAgo = (s) => {
            const d = parseDate(s);
            if (!d) return String(s || '');
            const secs = Math.max(0, Math.round((Date.now() - d.getTime()) / 1000));
            if (secs < 60) return 'just now';
            const mins = Math.round(secs / 60);
            if (mins < 60) return `${mins}m ago`;
            const hrs = Math.round(mins / 60);
            if (hrs < 24) return `${hrs}h ago`;
            const days = Math.round(hrs / 24);
            if (days < 7) return `${days}d ago`;
            const weeks = Math.round(days / 7);
            if (weeks < 5) return `${weeks}w ago`;
            return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        };

        const skeletonRow = () => `
            <div class="p-6 animate-pulse">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-white/5 flex-shrink-0"></div>
                    <div class="flex-1 space-y-3 py-1">
                        <div class="h-3 w-40 bg-white/5 rounded"></div>
                        <div class="h-2.5 w-64 bg-white/5 rounded"></div>
                        <div class="h-2.5 w-48 bg-white/5 rounded"></div>
                    </div>
                </div>
            </div>`;

        const cardActions = (r) => {
            if (r.status !== 'pending') return '';
            if (isSuper) {
                return `
                    <div class="flex flex-wrap items-center gap-2 justify-end">
                        <button class="resolve-btn px-4 py-2.5 bg-primary-500 hover:bg-primary-600 rounded-xl text-[10px] font-black text-white uppercase tracking-widest italic transition-all" data-id="${r.id}" data-action="approve">Approve</button>
                        <button class="resolve-btn px-4 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-[10px] font-black text-gray-400 hover:text-white uppercase tracking-widest italic transition-all" data-id="${r.id}" data-action="reject">Reject</button>
                        <button class="resolve-btn px-4 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-[10px] font-black text-gray-500 hover:text-white uppercase tracking-widest italic transition-all" data-id="${r.id}" data-action="cancel">Cancel</button>
                    </div>`;
            }
            if (r.requested_by === currentUid) {
                return `<button class="resolve-btn px-4 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-[10px] font-black text-gray-500 hover:text-white uppercase tracking-widest italic transition-all" data-id="${r.id}" data-action="cancel">Cancel</button>`;
            }
            return '';
        };

        const detailsBlock = (r) => {
            if (!isSuper) return '';
            const rows = [
                ['Username', r.target_username || r.target_uid || '—'],
                ['UID', r.target_uid || '—'],
                ['Role', (ROLE_META[r.target_role] || {}).label || r.target_role || '—'],
                ['Requested by', r.requested_username || r.requested_by || '—'],
                ['Requested at', fmtDate(r.requested_at)],
            ];
            if (r.resolved_by) rows.push(['Resolved by', r.resolved_by]);
            if (r.resolved_at) rows.push(['Resolved at', fmtDate(r.resolved_at)]);
            rows.push(['Reason', r.reason || 'No reason provided']);
            return `
                <div class="request-details mt-4">
                    <div>
                        <div class="rounded-xl bg-dark-bg border border-dark-border p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                                ${rows.map(([k, v]) => `
                                    <div class="${k === 'Reason' ? 'sm:col-span-2' : ''}">
                                        <p class="text-[9px] text-gray-500 font-black uppercase tracking-widest italic">${esc(k)}</p>
                                        <p class="text-xs text-gray-200 mt-1 leading-relaxed break-words">${esc(v)}</p>
                                    </div>`).join('')}
                            </div>
                        </div>
                    </div>
                </div>`;
        };

        const card = (r) => {
            const role = ROLE_META[r.target_role] || { label: r.target_role || 'Account', color: 'text-gray-400', avatar: 'from-gray-600 to-gray-900' };
            const status = STATUS_META[r.status] || STATUS_META.cancelled;
            const resolvedLine = (r.status !== 'pending' && r.resolved_by)
                ? `<p class="text-[10px] text-gray-600 mt-2">${esc(status.label)} by <span class="text-gray-300">${esc(r.resolved_by)}</span> &middot; <span title="${esc(fmtDate(r.resolved_at))}">${timeAgo(r.resolved_at)}</span></p>`
                : '';
            return `
                <div class="p-6 hover:bg-white/5 transition-colors" data-request="${r.id}">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br ${role.avatar} flex items-center justify-center text-white font-black text-xs border border-white/10 uppercase italic flex-shrink-0">${esc(initials(r.target_name))}</div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-black text-white italic uppercase tracking-tight">${esc(r.target_name || 'Unknown')}</p>
                                <span class="text-[9px] font-black uppercase tracking-widest italic ${role.color}">${esc(role.label)}</span>
                                <span class="px-2.5 py-1 rounded-lg border text-[9px] font-black uppercase tracking-widest italic ${status.color}">${esc(status.label)}</span>
                            </div>
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">@${esc(r.target_username || r.target_uid || 'unknown')}</p>
                            <p class="text-xs text-gray-400 mt-2 leading-relaxed">${r.reason ? 'Reason: ' + esc(r.reason) : 'No reason provided'}</p>
                            <p class="text-[10px] text-gray-600 mt-2">Requested by <span class="text-gray-300">${esc(r.requested_username || r.requested_by)}</span> &middot; <span title="${esc(fmtDate(r.requested_at))}">${timeAgo(r.requested_at)}</span></p>
                            ${resolvedLine}
                        </div>
                        <div class="flex flex-col items-end gap-2 flex-shrink-0">
                            ${cardActions(r)}
                            ${isSuper ? `
                                <button class="details-toggle flex items-center gap-1.5 px-3 py-1.5 bg-white/5 hover:bg-white/10 rounded-lg text-[9px] font-black text-gray-400 hover:text-white uppercase tracking-widest italic transition-all" data-id="${r.id}">
                                    Details <i data-feather="chevron-down" class="details-chevron w-3.5 h-3.5 transition-transform duration-300"></i>
                                </button>` : ''}
                        </div>
                    </div>
                    ${detailsBlock(r)}
                </div>`;
        };

        const render = () => {
            const term = searchTerm.toLowerCase();
            const filtered = requests.filter(r =>
                r.status === activeStatus &&
                (!term
                    || (r.target_name || '').toLowerCase().includes(term)
                    || (r.target_username || '').toLowerCase().includes(term)
                    || (r.target_uid || '').toLowerCase().includes(term))
            );
            listCount.textContent = `${filtered.length} shown`;

            if (!requests.length) {
                const e = EMPTY_STATE[activeStatus] || EMPTY_STATE.pending;
                list.innerHTML = `
                    <div class="px-8 py-16 text-center">
                        <i data-feather="${e.icon}" class="w-8 h-8 mx-auto text-gray-600"></i>
                        <p class="text-sm text-gray-500 italic mt-4">${e.title}</p>
                        <p class="text-[10px] text-gray-600 font-bold uppercase tracking-widest italic mt-1">${e.msg}</p>
                    </div>`;
                feather.replace();
                return;
            }
            if (!filtered.length) {
                list.innerHTML = `<p class="px-8 py-16 text-center text-gray-500 italic text-sm">No ${esc(activeStatus)} requests match your search.</p>`;
                return;
            }
            list.innerHTML = filtered.map(card).join('');
            feather.replace();
        };

        const renderStats = () => {
            const counts = { pending: 0, approved: 0, rejected: 0, cancelled: 0 };
            requests.forEach(r => { if (counts[r.status] !== undefined) counts[r.status]++; });
            document.getElementById('statCountPending').textContent = counts.pending;
            document.getElementById('statCountApproved').textContent = counts.approved;
            document.getElementById('statCountRejected').textContent = counts.rejected;
            document.getElementById('statCountCancelled').textContent = counts.cancelled;
            if (isSuper) {
                document.getElementById('statSubPending').textContent = counts.pending ? 'Needs your review' : 'All caught up';
            }
            paintStats();
        };

        const statTabs = document.querySelectorAll('.stat-tab');
        const paintStats = () => {
            statTabs.forEach(t => {
                const st = t.dataset.status || 'pending';
                const active = st === activeStatus;
                STAT_ALL.forEach(c => t.classList.remove(c));
                t.classList.toggle('opacity-50', !active);
                if (active) STAT_ACTIVE[st].forEach(c => t.classList.add(c));
            });
        };

        const load = async (showSkeleton = true) => {
            if (showSkeleton) list.innerHTML = skeletonRow().repeat(3);
            try {
                requests = await api('/admin/deletion_requests.php');
                renderStats();
                render();
            } catch (err) {
                list.innerHTML = `<p class="px-8 py-16 text-center text-primary-400 italic text-sm">${esc(err.message || 'Failed to load requests.')}</p>`;
            }
        };

        const showModal = () => { confirmModal.classList.remove('hidden'); confirmModal.classList.add('flex'); modalCancelBtn.focus(); };
        const hideModal = () => { confirmModal.classList.add('hidden'); confirmModal.classList.remove('flex'); };

        const openConfirm = (id, action) => {
            const r = requests.find(x => x.id === id);
            if (!r) return;
            const name = r.target_name || r.target_username || 'this account';
            const role = ROLE_META[r.target_role] || {};
            const status = STATUS_META[r.status] || STATUS_META.pending;
            const isApprove = action === 'approve';

            modalIconBox.className = `w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 border ${isApprove ? 'bg-primary-500/10 border-primary-500/20' : 'bg-white/5 border-white/10'}`;
            modalIconBox.innerHTML = `<i data-feather="${isApprove ? 'alert-triangle' : action === 'reject' ? 'x-circle' : 'slash'}" class="w-5 h-5 ${isApprove ? 'text-primary-400' : 'text-gray-400'}"></i>`;

            modalTitle.textContent = isApprove
                ? 'Permanently delete this account?'
                : action === 'reject' ? 'Reject this request?' : 'Cancel this request?';
            modalBody.textContent = isApprove
                ? `You are about to permanently delete ${name}. The account, attendance, grades, enrollments, and all related data will be removed. This cannot be undone.`
                : action === 'reject'
                    ? `The deletion request for ${name} will be rejected and the account will be kept.`
                    : `This pending deletion request for ${name} will be cancelled.`;

            modalTarget.innerHTML = `
                <div class="w-9 h-9 rounded-lg bg-gradient-to-br ${role.avatar || 'from-gray-600 to-gray-900'} flex items-center justify-center text-white font-black text-[10px] border border-white/10 uppercase italic flex-shrink-0">${esc(initials(r.target_name))}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-black text-white italic uppercase tracking-tight truncate">${esc(r.target_name || 'Unknown')}</p>
                    <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest italic">@${esc(r.target_username || r.target_uid)} &middot; ${esc(role.label || r.target_role)}</p>
                </div>
                <span class="px-2 py-0.5 rounded-lg border text-[9px] font-black uppercase tracking-widest italic ${status.color}">${esc(status.label)}</span>`;
            modalTarget.classList.remove('hidden');

            confirmLabel = isApprove ? 'Yes, Delete' : action === 'reject' ? 'Reject Request' : 'Cancel Request';
            modalConfirmBtn.className = `px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest italic transition-all text-white ${isApprove ? 'bg-red-500 hover:bg-red-600' : 'bg-white/10 hover:bg-white/15'}`;
            modalConfirmBtn.innerHTML = confirmLabel;
            modalConfirmBtn.disabled = false;
            modalCancelBtn.disabled = false;
            modalConfirmBtn.dataset.id = id;
            modalConfirmBtn.dataset.action = action;
            feather.replace();
            showModal();
        };

        modalCancelBtn.onclick = hideModal;
        modalBackdrop.onclick = hideModal;
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !confirmModal.classList.contains('hidden')) hideModal();
        });

        modalConfirmBtn.onclick = async () => {
            const id = Number(modalConfirmBtn.dataset.id);
            const action = modalConfirmBtn.dataset.action;
            modalConfirmBtn.disabled = true;
            modalCancelBtn.disabled = true;
            modalConfirmBtn.innerHTML = '<i data-feather="loader" class="w-4 h-4 animate-spin mx-auto"></i>';
            feather.replace();
            try {
                if (action === 'cancel') {
                    await api('/admin/deletion_requests.php?id=' + id, { method: 'DELETE' });
                    window.showStatus('Request cancelled.', 'success');
                } else {
                    await api('/admin/deletion_requests.php', { method: 'PUT', body: JSON.stringify({ id, action }) });
                    window.showStatus(`Request ${action === 'approve' ? 'approved' : 'rejected'}.`, 'success');
                }
                hideModal();
                load(false);
            } catch (err) {
                modalConfirmBtn.disabled = false;
                modalCancelBtn.disabled = false;
                modalConfirmBtn.innerHTML = confirmLabel;
                feather.replace();
                window.showStatus(err.message || 'Action failed.');
            }
        };

        list.addEventListener('click', (e) => {
            const toggle = e.target.closest('.details-toggle');
            if (toggle) {
                const cardEl = toggle.closest('[data-request]');
                const details = cardEl.querySelector('.request-details');
                const chevron = cardEl.querySelector('.details-chevron');
                details.classList.toggle('open');
                chevron.classList.toggle('rotate-180');
                return;
            }
            const rb = e.target.closest('.resolve-btn');
            if (rb) openConfirm(Number(rb.dataset.id), rb.dataset.action);
        });

        statTabs.forEach(t => t.addEventListener('click', () => {
            activeStatus = t.dataset.status || 'pending';
            paintStats();
            render();
        }));

        let searchTimer = null;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                searchTerm = searchInput.value.trim();
                render();
            }, 200);
        });

        initPage(() => load(true));
        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
    </script>
    <script src="../assets/js/theme-toggle.js" defer></script>
</body>
</html>