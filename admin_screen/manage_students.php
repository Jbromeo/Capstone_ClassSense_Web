<?php
require_once dirname(__DIR__) . '/core/init.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense Admin | Manage Students</title>
    <?php include '../includes/head.php'; ?>
    <style>
        #editModal, #passwordModal { pointer-events: none; }
        #editModal.show, #passwordModal.show { opacity: 1; pointer-events: auto; }
        #editModal.show > div:last-child, #passwordModal.show > div:last-child { transform: scale(1); }
        .toast { transform: translateX(120%); transition: all 0.4s cubic-bezier(0.68, -0.55, 0.26, 1.55); opacity: 0; }
        .toast.show { transform: translateX(0); opacity: 1; }
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
            <h2 class="text-xl font-bold text-white italic">Manage Students <span class="text-xs text-gray-500 font-normal ml-3 tracking-widest uppercase">Admin Panel</span></h2>
            <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="glass-panel rounded-2xl overflow-hidden animate-fade-in-up flex flex-col h-full">
                <div class="p-6 border-b border-dark-border bg-white/5 space-y-4">
                    <div class="relative group">
                        <i data-feather="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 group-focus-within:text-blue-500 transition-colors"></i>
                        <input type="text" id="searchInput" placeholder="Search by name, username, or student ID..." class="w-full bg-dark-bg border border-dark-border rounded-xl pl-11 pr-4 py-3 text-sm text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all placeholder-gray-600 font-medium">
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto custom-scroll">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-dark-surface text-gray-500 uppercase text-xs font-black tracking-widest border-b border-dark-border">
                                <th class="px-6 py-5">Student ID</th>
                                <th class="px-6 py-5">First Name</th>
                                <th class="px-6 py-5">Last Name</th>
                                <th class="px-6 py-5">Username</th>
                                <th class="px-6 py-5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentsBody" class="divide-y divide-dark-border text-sm"></tbody>
                    </table>
                    <div id="emptyState" class="hidden p-20 text-center">
                        <p class="text-gray-500 italic">No students found.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="absolute inset-0 bg-dark-bg/40 backdrop-blur-md"></div>
        <div class="glass-panel w-full max-w-md rounded-[2.5rem] p-8 border border-white/10 shadow-2xl transform scale-95 transition-transform duration-300 relative z-10">
            <div class="w-14 h-14 bg-blue-500/10 rounded-full flex items-center justify-center mx-auto mb-5 border border-blue-500/10">
                <i data-feather="edit-3" class="w-7 h-7 text-blue-500"></i>
            </div>
            <h3 class="text-2xl font-black text-white italic mb-1 tracking-tight text-center">Edit Student</h3>
            <p class="text-gray-400 text-xs mb-6 text-center font-bold uppercase tracking-widest italic">Update name and login credentials</p>
            <form id="editForm" class="space-y-4">
                <input type="hidden" name="uid" id="editUid">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">First Name</label>
                    <input type="text" id="editFirstName" required class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Last Name</label>
                    <input type="text" id="editLastName" required class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Username</label>
                    <input type="text" id="editUsername" required class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div class="grid grid-cols-2 gap-4 pt-2">
                    <button type="button" id="closeEditModal" class="w-full py-3 bg-white/5 hover:bg-white/10 rounded-2xl font-black text-gray-500 hover:text-white transition-all text-xs uppercase tracking-widest leading-none">Cancel</button>
                    <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 rounded-2xl font-black text-white transition-all shadow-lg shadow-blue-500/20 uppercase tracking-[0.2em] italic text-xs leading-none">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div id="passwordModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="absolute inset-0 bg-dark-bg/40 backdrop-blur-md"></div>
        <div class="glass-panel w-full max-w-md rounded-[2.5rem] p-8 border border-white/10 shadow-2xl transform scale-95 transition-transform duration-300 relative z-10">
            <div class="w-14 h-14 bg-primary-500/10 rounded-full flex items-center justify-center mx-auto mb-5 border border-primary-500/10">
                <i data-feather="key" class="w-7 h-7 text-primary-500"></i>
            </div>
            <h3 class="text-2xl font-black text-white italic mb-1 tracking-tight text-center">Reset Password</h3>
            <p id="passwordModalStudent" class="text-gray-400 text-xs mb-6 text-center font-bold uppercase tracking-widest italic">Student Name</p>
            <form id="passwordForm" class="space-y-4">
                <input type="hidden" name="uid" id="passwordUid">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">New Password</label>
                    <div class="relative">
                        <input type="password" id="newPassword" required minlength="6" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all pr-10" placeholder="Min. 6 characters">
                        <button type="button" onclick="togglePass('newPassword')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white"><i data-feather="eye" class="w-4 h-4"></i></button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Confirm Password</label>
                    <div class="relative">
                        <input type="password" id="confirmPassword" required minlength="6" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all pr-10" placeholder="Repeat password">
                        <button type="button" onclick="togglePass('confirmPassword')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white"><i data-feather="eye" class="w-4 h-4"></i></button>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 pt-2">
                    <button type="button" id="closePasswordModal" class="w-full py-3 bg-white/5 hover:bg-white/10 rounded-2xl font-black text-gray-500 hover:text-white transition-all text-xs uppercase tracking-widest leading-none">Cancel</button>
                    <button type="submit" class="w-full py-3 bg-primary-600 hover:bg-primary-700 rounded-2xl font-black text-white transition-all shadow-lg shadow-primary-500/20 uppercase tracking-[0.2em] italic text-xs leading-none">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        #editModal.show, #passwordModal.show { opacity: 1; }
    </style>

    <script>
        function togglePass(id) {
            const el = document.getElementById(id);
            el.type = el.type === 'password' ? 'text' : 'password';
        }
    </script>

    <script type="module">
        import { api } from '../assets/js/custom-auth.js';

        const studentsBody = document.getElementById('studentsBody');
        const emptyState = document.getElementById('emptyState');
        const searchInput = document.getElementById('searchInput');

        let allStudents = [];

        const showStatus = (message, type = 'error') => {
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

        const renderTable = (students) => {
            if (!students.length) {
                studentsBody.innerHTML = '';
                emptyState.classList.remove('hidden');
                return;
            }
            emptyState.classList.add('hidden');
            studentsBody.innerHTML = students.map(s => `
                <tr class="hover:bg-white/5 transition-colors group">
                    <td class="px-6 py-5 font-mono text-blue-400 font-black tracking-tight">${s.studentId || '—'}</td>
                    <td class="px-6 py-5 font-bold text-white">${s.firstName}</td>
                    <td class="px-6 py-5 font-bold text-white">${s.lastName}</td>
                    <td class="px-6 py-5 text-gray-300">${s.username}</td>
                    <td class="px-6 py-5 text-center">
                        <button onclick="window.openEditModal('${s.uid}')" class="p-2 text-blue-500 hover:text-blue-400 transition-all hover:scale-150" title="Edit"><i data-feather="edit-3" class="w-4 h-4"></i></button>
                        <button onclick="window.openPasswordModal('${s.uid}')" class="p-2 text-primary-500 hover:text-primary-400 transition-all hover:scale-150 ml-1" title="Reset Password"><i data-feather="key" class="w-4 h-4"></i></button>
                    </td>
                </tr>
            `).join('');
            feather.replace();
        };

        const loadStudents = async () => {
            try {
                const query = searchInput.value.trim();
                const students = query
                    ? await api('/admin/manage_students.php?search=' + encodeURIComponent(query))
                    : await api('/admin/manage_students.php');
                allStudents = students;
                renderTable(students);
            } catch (err) {
                showStatus(err.message || 'Failed to load students');
            }
        };

        searchInput.addEventListener('input', () => loadStudents());

        // --- Edit Modal ---
        const editModal = document.getElementById('editModal');
        const editForm = document.getElementById('editForm');

        window.openEditModal = (uid) => {
            const student = allStudents.find(s => s.uid === uid);
            if (!student) return;
            document.getElementById('editUid').value = student.uid;
            document.getElementById('editFirstName').value = student.firstName;
            document.getElementById('editLastName').value = student.lastName;
            document.getElementById('editUsername').value = student.username;
            editModal.classList.remove('hidden');
            setTimeout(() => editModal.classList.add('show'), 10);
            feather.replace();
        };

        document.getElementById('closeEditModal').onclick = () => {
            editModal.classList.remove('show');
            setTimeout(() => editModal.classList.add('hidden'), 300);
        };

        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const uid = document.getElementById('editUid').value;
            const firstName = document.getElementById('editFirstName').value.trim();
            const lastName = document.getElementById('editLastName').value.trim();
            const username = document.getElementById('editUsername').value.trim();
            if (!firstName || !lastName || !username) { showStatus('All fields are required'); return; }

            try {
                await api('/admin/manage_students.php', {
                    method: 'PUT',
                    body: JSON.stringify({ uid, firstName, lastName, username })
                });
                editModal.classList.remove('show');
                setTimeout(() => editModal.classList.add('hidden'), 300);
                showStatus('Student updated successfully.', 'success');
                loadStudents();
            } catch (err) {
                showStatus(err.message || 'Update failed');
            }
        });

        // --- Password Modal ---
        const passwordModal = document.getElementById('passwordModal');
        const passwordForm = document.getElementById('passwordForm');

        window.openPasswordModal = (uid) => {
            const student = allStudents.find(s => s.uid === uid);
            if (!student) return;
            document.getElementById('passwordUid').value = student.uid;
            document.getElementById('passwordModalStudent').textContent = `${student.firstName} ${student.lastName}`;
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            passwordModal.classList.remove('hidden');
            setTimeout(() => passwordModal.classList.add('show'), 10);
            feather.replace();
        };

        document.getElementById('closePasswordModal').onclick = () => {
            passwordModal.classList.remove('show');
            setTimeout(() => passwordModal.classList.add('hidden'), 300);
        };

        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const uid = document.getElementById('passwordUid').value;
            const password = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            if (password.length < 6) { showStatus('Password must be at least 6 characters.'); return; }
            if (password !== confirm) { showStatus('Passwords do not match.'); return; }

            try {
                await api('/admin/manage_students.php', {
                    method: 'POST',
                    body: JSON.stringify({ uid, password })
                });
                passwordModal.classList.remove('show');
                setTimeout(() => passwordModal.classList.add('hidden'), 300);
                showStatus('Password reset successfully.', 'success');
            } catch (err) {
                showStatus(err.message || 'Reset failed');
            }
        });

        loadStudents();
    </script>
</body>
</html>
