<!-- admin_screen/manage_admins.php -->
<?php
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php';
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    http_response_code(404);
    include dirname(__DIR__) . '/404.php';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense Admin | Admin Accounts</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .toast { transform: translateX(120%); transition: all 0.4s cubic-bezier(0.68, -0.55, 0.26, 1.55); opacity: 0; }
        .toast.show { transform: translateX(0); opacity: 1; }
        #passwordModal { pointer-events: none; }
        #passwordModal.show { opacity: 1; pointer-events: auto; }
        #passwordModal.show > div:last-child { transform: scale(1); }
        .animate-scale-up { transform: scale(0.95); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
    </style>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-amber-500 selection:text-white bg-dark-bg">

    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-[25%] w-[600px] h-[600px] bg-amber-500/5 rounded-full mix-blend-screen filter blur-3xl animate-blob-slow transform -translate-y-1/2"></div>
    </div>

    <?php include 'admin_sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <header class="h-20 glass-panel border-b border-white/5 flex items-center justify-between px-8 z-20">
            <h2 class="text-xl font-black text-white tracking-tighter uppercase leading-none">Admin Accounts
                <span class="text-[10px] text-amber-400 font-bold ml-4 uppercase tracking-[0.2em] opacity-60">Super Admin Only</span>
            </h2>
            <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-4xl mx-auto">
                <div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
                    <div class="px-8 py-6 border-b border-dark-border flex items-center gap-3">
                        <div class="p-2.5 bg-amber-500/10 rounded-xl text-amber-400">
                            <i data-feather="shield" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Administrators</h3>
                            <p class="text-[10px] text-gray-500 uppercase tracking-widest italic font-bold">Reset a password to immediately sign that admin out of every device</p>
                        </div>
                    </div>

                    <div id="adminList" class="divide-y divide-dark-border">
                        <p class="px-8 py-16 text-center text-gray-500 italic text-sm">Loading administrators...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Password Reset Modal -->
    <div id="passwordModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden opacity-0 transition-all duration-300">
        <div class="absolute inset-0 bg-dark-bg/60 backdrop-blur-md"></div>
        <div class="glass-panel w-full max-w-sm rounded-[2.5rem] p-8 border border-white/10 shadow-[0_20px_50px_rgba(245,158,11,0.2)] animate-scale-up relative z-10 text-center">
            <div class="w-20 h-20 bg-amber-500/10 rounded-full flex items-center justify-center mx-auto mb-6 border border-amber-500/20">
                <i data-feather="key" class="w-10 h-10 text-amber-400"></i>
            </div>
            <h3 class="text-2xl font-black text-white italic mb-2 tracking-tight uppercase tracking-tighter">Reset Password</h3>
            <p class="text-gray-400 text-sm mb-6 leading-relaxed font-bold">Set a new password for <span id="passwordTargetName" class="text-white italic font-black">Admin</span>. All their active sessions will be revoked.</p>
            <input type="password" id="newPasswordInput" minlength="6" class="w-full bg-dark-bg border border-dark-border rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-amber-500/50 outline-none transition-all placeholder-gray-600 mb-2" placeholder="New password (min 6 chars)">
            <input type="password" id="confirmPasswordInput" minlength="6" class="w-full bg-dark-bg border border-dark-border rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-amber-500/50 outline-none transition-all placeholder-gray-600 mb-4" placeholder="Confirm new password">
            <div class="space-y-3">
                <button id="confirmPasswordBtn" class="w-full py-4 bg-amber-500 hover:bg-amber-600 rounded-2xl font-black text-dark-bg transition-all shadow-lg shadow-amber-500/20 uppercase tracking-[0.2em] italic text-xs leading-none">Reset &amp; Revoke Sessions</button>
                <button id="cancelPasswordBtn" class="w-full py-4 bg-white/5 hover:bg-white/10 rounded-2xl font-bold text-gray-500 hover:text-white transition-all text-xs uppercase tracking-widest leading-none">Cancel</button>
            </div>
        </div>
    </div>

    <script>window.CURRENT_UID = <?php echo json_encode($_SESSION['uid'] ?? ''); ?>;</script>
    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';

        const list = document.getElementById('adminList');
        const modal = document.getElementById('passwordModal');
        const targetName = document.getElementById('passwordTargetName');
        const newPasswordInput = document.getElementById('newPasswordInput');
        const confirmPasswordInput = document.getElementById('confirmPasswordInput');
        const confirmBtn = document.getElementById('confirmPasswordBtn');
        const cancelBtn = document.getElementById('cancelPasswordBtn');
        const currentUid = window.CURRENT_UID;
        let passwordTarget = null;

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

        const load = async () => {
            try {
                const admins = await api('/admin/manage_admins.php');
                if (!admins.length) {
                    list.innerHTML = '<p class="px-8 py-16 text-center text-gray-500 italic text-sm">No admin accounts found.</p>';
                    return;
                }
                list.innerHTML = admins.map(a => `
                    <div class="p-6 flex flex-col md:flex-row md:items-center gap-4 hover:bg-white/5 transition-colors">
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <div class="w-12 h-12 rounded-2xl ${a.role === 'super_admin' ? 'bg-amber-500/20 text-amber-400 border-amber-500/20' : 'bg-purple-600/20 text-purple-400 border-purple-500/20'} flex items-center justify-center font-black border text-lg italic">
                                ${(a.firstName || a.username || 'A')[0].toUpperCase()}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-black text-white italic uppercase tracking-tight truncate">${a.firstName || ''} ${a.lastName || ''} ${a.isSelf ? '<span class="text-[9px] text-green-400">(you)</span>' : ''}</p>
                                <p class="text-[10px] text-gray-500 font-bold tracking-widest truncate">${a.username}</p>
                                <p class="text-[9px] font-black uppercase tracking-widest mt-1 ${a.role === 'super_admin' ? 'text-amber-400' : 'text-purple-400'}">${a.role === 'super_admin' ? 'Super Admin' : 'Admin'}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button onclick="window.openPasswordModal('${a.uid}', '${(a.firstName || a.username || 'Admin').replace(/'/g, "\\'")}', ${a.isSelf})" class="px-4 py-2.5 bg-white/5 hover:bg-amber-500/10 rounded-xl text-[10px] font-black text-gray-400 hover:text-amber-400 uppercase tracking-widest italic transition-all">Reset Password</button>
                            ${a.isSelf ? '' : `<button onclick="window.deleteAdmin('${a.uid}', '${(a.username || '').replace(/'/g, "\\'")}')" class="px-4 py-2.5 bg-white/5 hover:bg-primary-500/10 rounded-xl text-[10px] font-black text-gray-500 hover:text-primary-400 uppercase tracking-widest italic transition-all">Delete</button>`}
                        </div>
                    </div>
                `).join('');
                feather.replace();
            } catch (err) {
                list.innerHTML = `<p class="px-8 py-16 text-center text-primary-400 italic text-sm">${err.message || 'Failed to load administrators.'}</p>`;
            }
        };

        window.openPasswordModal = (uid, name, isSelf) => {
            passwordTarget = { uid, name, isSelf };
            targetName.textContent = name;
            newPasswordInput.value = '';
            confirmPasswordInput.value = '';
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.add('show'), 10);
        };

        const closeModal = () => {
            modal.classList.remove('show');
            setTimeout(() => modal.classList.add('hidden'), 300);
            passwordTarget = null;
        };

        cancelBtn.onclick = closeModal;

        confirmBtn.onclick = async () => {
            if (!passwordTarget) return;
            const password = newPasswordInput.value;
            if (password.length < 6) return window.showStatus('Password must be at least 6 characters.');
            if (password !== confirmPasswordInput.value) return window.showStatus('Passwords do not match.');

            confirmBtn.disabled = true;
            try {
                const result = await api('/admin/manage_admins.php', {
                    method: 'POST',
                    body: JSON.stringify({ uid: passwordTarget.uid, password })
                });
                const wasSelf = passwordTarget.isSelf;
                closeModal();
                window.showStatus(`Password reset. ${result.sessions_revoked} session(s) revoked.`, 'success');
                if (wasSelf) {
                    sessionStorage.removeItem('cs_token');
                    sessionStorage.removeItem('cs_user');
                    setTimeout(() => { window.location.href = '../login.php?status=session_expired'; }, 1200);
                } else {
                    load();
                }
            } catch (err) {
                window.showStatus(err.message || 'Password reset failed.');
            } finally {
                confirmBtn.disabled = false;
            }
        };

        window.deleteAdmin = async (uid, username) => {
            if (!confirm(`Permanently delete admin account "${username}"? This cannot be undone.`)) return;
            try {
                await api('/admin/manage_admins.php?uid=' + uid, { method: 'DELETE' });
                window.showStatus(`Admin ${username} deleted.`, 'success');
                load();
            } catch (err) {
                window.showStatus(err.message || 'Delete failed.');
            }
        };

        initPage(() => load());
        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
    </script>
    <script src="../assets/js/theme-toggle.js" defer></script>
</body>
</html>
