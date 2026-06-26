<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- student_screen/student_profile.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | My Profile</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased min-h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute bottom-0 left-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
    </div>

    <?php include 'student_sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-8 z-20">
            <h2 class="text-xl font-bold text-white italic">Account Settings <span class="text-xs text-gray-500 font-normal ml-3 tracking-widest uppercase italic">Student Profile</span></h2>
            <div class="flex items-center gap-4">
                <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors">
                    <i data-feather="bell"></i>
                    <span class="absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                </button>
            </div>
            <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-4xl mx-auto">
                
                <!-- Profile Header -->
                <div class="glass-panel rounded-3xl p-8 mb-8 border-l-4 border-l-primary-500 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                        <i data-feather="user" class="w-32 h-32 text-white"></i>
                    </div>
                    
                    <div class="flex flex-col md:flex-row items-center gap-8 relative z-10">
                        <div class="relative">
                            <img src="https://picsum.photos/seed/student123/150/150.jpg" class="w-32 h-32 rounded-3xl object-cover ring-4 ring-dark-bg shadow-2xl">
                            <button class="absolute -bottom-2 -right-2 p-3 bg-primary-600 rounded-2xl text-white shadow-lg shadow-primary-500/30 hover:scale-110 active:scale-95 transition-all">
                                <i data-feather="camera" class="w-5 h-5"></i>
                            </button>
                        </div>
                        <div class="text-center md:text-left">
                            <h1 id="profileFullName" class="text-4xl font-black text-white mb-2 leading-none italic animate-fade-in">Loading Profile...</h1>
                            <div class="flex flex-wrap justify-center md:justify-start gap-3 mt-4">
                                <span class="px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5">
                                    <i data-feather="hash" class="w-3 h-3"></i> <span id="profileStudentId">---</span>
                                </span>
                                <span class="px-3 py-1 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-400 text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5">
                                    <i data-feather="award" class="w-3 h-3"></i> BS Information Technology
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Information Form -->
                <div class="glass-panel rounded-3xl p-8 animate-fade-in-up">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="p-2 bg-primary-500/10 rounded-xl text-primary-500">
                            <i data-feather="edit-3" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Personal Information</h3>
                            <p class="text-xs text-gray-500 uppercase tracking-tighter italic">Update your primary contact and academic details</p>
                        </div>
                    </div>

                    <form id="profileForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1">First Name</label>
                                <input type="text" name="firstName" id="formFname" required class="w-full bg-dark-bg border border-dark-border rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-primary-500/50 outline-none transition-all placeholder:text-gray-700 font-medium">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1">Last Name</label>
                                <input type="text" name="lastName" id="formLname" required class="w-full bg-dark-bg border border-dark-border rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-primary-500/50 outline-none transition-all placeholder:text-gray-700 font-medium">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1">Email Address (Registry Primary)</label>
                            <input type="email" id="formEmail" disabled class="w-full bg-dark-bg/50 border border-dark-border rounded-2xl px-5 py-4 text-gray-500 outline-none italic cursor-not-allowed font-medium" value="student@university.edu">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1">Phone Number</label>
                                <div class="relative">
                                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-500 border-r border-dark-border pr-3 text-sm font-bold">+63</span>
                                    <input type="tel" name="phone" id="formPhone" required class="w-full bg-dark-bg border border-dark-border rounded-2xl pl-16 pr-5 py-4 text-white focus:ring-2 focus:ring-primary-500/50 outline-none transition-all font-medium">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1">Guardian Phone</label>
                                 <div class="relative">
                                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-500 border-r border-dark-border pr-3 text-sm font-bold">+63</span>
                                    <input type="tel" name="guardianPhone" id="formGuardian" required class="w-full bg-dark-bg border border-dark-border rounded-2xl pl-16 pr-5 py-4 text-white focus:ring-2 focus:ring-primary-500/50 outline-none transition-all font-medium">
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-white/5 flex flex-col md:flex-row gap-4 items-center justify-between">
                            <p class="text-xs text-gray-500 max-w-sm">Changes will reflect immediately across all academic records. Security alerts may be sent to your primary email.</p>
                            <button type="submit" id="saveBtn" class="w-full md:w-auto px-8 py-4 bg-primary-600 hover:bg-primary-700 rounded-2xl font-black text-white transition-all shadow-xl shadow-primary-500/20 flex items-center justify-center gap-3 active:scale-95 group uppercase tracking-widest text-xs">
                                <span id="saveBtnText">Save Profile Update</span>
                                <i data-feather="check" class="w-4 h-4 group-hover:scale-125 transition-transform"></i>
                                <div id="saveLoader" class="hidden w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script type="module">
        import { db } from '../assets/js/firebase-init.js';
        import { doc, updateDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

        let currentUid = null;

        // 1. Populate data when student profile is loaded
        window.addEventListener('profileLoaded', (e) => {
            const data = e.detail;
            currentUid = data.uid;

            // Header UI
            document.getElementById('profileFullName').textContent = `${data.firstName || ''} ${data.lastName || ''}`.trim() || data.full_name;
            document.getElementById('profileStudentId').textContent = data.studentId || "---";

            // Form UI
            document.getElementById('formFname').value = data.firstName || '';
            document.getElementById('formLname').value = data.lastName || '';
            document.getElementById('formEmail').value = data.email || '';
            document.getElementById('formPhone').value = data.phone || "";
            document.getElementById('formGuardian').value = data.guardianPhone || "";
            
            // Remove italics
            document.getElementById('profileFullName').classList.remove('italic');
        });

        // 2. Handle Profile Update
        const profileForm = document.getElementById('profileForm');
        const saveBtn = document.getElementById('saveBtn');
        const saveBtnText = document.getElementById('saveBtnText');
        const saveLoader = document.getElementById('saveLoader');

        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if(!currentUid) return;

            const formData = new FormData(profileForm);
            const data = Object.fromEntries(formData.entries());

            // Loading state
            saveBtn.disabled = true;
            saveBtnText.textContent = "Processing Registry...";
            saveLoader.classList.remove('hidden');

            try {
                const docRef = doc(db, "students", currentUid);
                await updateDoc(docRef, {
                    firstName: data.firstName,
                    lastName: data.lastName,
                    phone: data.phone,
                    guardianPhone: data.guardianPhone,
                    updatedAt: new Date().toISOString()
                });

                showStatus("Registry Updated Successfully!", "success");
                
                // Update local UI
                document.getElementById('profileFullName').textContent = `${data.firstName} ${data.lastName}`;
                
                // Manually refresh sidebar items (or it will refresh on next state change)
                document.getElementById('sideStudentName').textContent = `${data.firstName} ${data.lastName}`;
                document.getElementById('popoverName').textContent = `${data.firstName} ${data.lastName}`;

            } catch (error) {
                console.error("Update Error:", error);
                showStatus("System failed to update record. Try again.", "error");
            } finally {
                saveBtn.disabled = false;
                saveBtnText.textContent = "Save Profile Update";
                saveLoader.classList.add('hidden');
            }
        });

        function showStatus(message, type = 'error') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            const isError = type === 'error';
            toast.className = `toast flex items-center w-full max-w-xs p-4 space-x-4 text-gray-200 bg-dark-surface rounded-2xl shadow-2xl border border-dark-border ${isError ? 'border-l-4 border-l-primary-500' : 'border-l-4 border-l-green-500'} animate-fade-in-up`;
            toast.innerHTML = `<div class="flex-shrink-0"><i data-feather="${isError ? 'alert-circle' : 'check-circle'}" class="w-5 h-5 ${isError ? 'text-primary-500' : 'text-green-500'}"></i></div><div class="text-[10px] font-black uppercase tracking-widest">${message}</div>`;
            container.appendChild(toast);
            feather.replace();
            setTimeout(() => { toast.remove(); }, 4000);
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => { feather.replace(); });
    </script>
</body>
</html>
