<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- student_view/student_classes.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | My Classes</title>
    <?php include '../includes/head.php'; ?>
    <style>
        /* Custom Animation for FAB */
        .fab-pulse {
            animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 0.8; }
            80%, 100% { transform: scale(1.3); opacity: 0; }
        }
        
        /* Modal Transition */
        .modal-overlay {
            transition: opacity 0.3s ease;
        }
        .modal-content {
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        .hidden-modal {
            opacity: 0;
            pointer-events: none;
        }
        .hidden-modal .modal-content {
            transform: scale(0.95);
            opacity: 0;
        }
    </style>
</head>
    <body class="antialiased min-h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">
        <!-- Animated Background Blobs -->
        <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
        </div>

        <?php include 'student_sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
            
            <!-- HEADER -->
            <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                        <i data-feather="menu"></i>
                    </button>
                    <h2 class="text-xl font-bold text-white hidden sm:block">My Classes</h2>
                </div>

                <div class="flex items-center gap-4">
                    <div class="relative hidden md:block group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="search" class="h-4 w-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i>
                        </div>
                        <input type="text" class="bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-full focus:ring-primary-500 focus:border-primary-500 block w-64 pl-10 p-2.5 transition-all focus:w-80 placeholder-gray-600" placeholder="Search subjects...">
                    </div>

                    <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors">
                        <i data-feather="bell"></i>
                        <span class="absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                    </button>
                    <button class="p-2 text-gray-400 hover:text-white transition-colors md:hidden">
                        <i data-feather="search"></i>
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 md:p-8 pb-24"> <!-- Added pb-24 for FAB spacing -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-white">First Semester, A.Y. 2023-2024</h3>
                    <p class="text-sm text-gray-400">You are enrolled in 8 subjects</p>
                </div>

                    <!-- Dynamic Class Grid -->
                <div id="studentClassGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <!-- Data will be injected here -->
                    <div class="col-span-full py-20 text-center opacity-40">
                        <div class="animate-pulse space-y-4">
                            <div class="glass-panel h-48 w-full rounded-2xl mx-auto"></div>
                            <p class="text-[10px] font-black uppercase tracking-widest italic tracking-tighter">Syncing Enrolled Modules...</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- JOIN CLASS FLOATING ACTION BUTTON (FAB) -->
        <div class="fixed bottom-6 right-6 z-40">
            <!-- Pulse Ring Effect -->
            <div class="absolute inset-0 bg-primary-500 rounded-full fab-pulse"></div>
            
            <button id="openJoinModal" class="relative flex items-center justify-center w-14 h-14 bg-primary-500 rounded-full shadow-lg shadow-primary-500/30 hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-offset-2 focus:ring-offset-dark-bg transition-all transform hover:scale-105 active:scale-95">
                <i data-feather="plus" class="w-6 h-6 text-white"></i>
            </button>
        </div>

        <!-- JOIN CLASS MODAL -->
        <div id="joinModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden-modal modal-overlay bg-black/60 backdrop-blur-sm">
            <div class="modal-container w-full max-w-md">
                
                <!-- Modal Content -->
                <div class="modal-content bg-dark-surface border border-dark-border rounded-2xl shadow-2xl overflow-hidden">
                    
                    <!-- Header -->
                    <div class="relative p-6 pb-0 flex justify-between items-start">
                        <div class="p-3 bg-primary-500/10 rounded-xl border border-primary-500/20">
                            <i data-feather="user-plus" class="w-6 h-6 text-primary-400"></i>
                        </div>
                        <button id="closeJoinModal" class="p-1 text-gray-500 hover:text-white hover:bg-white/10 rounded-full transition-colors">
                            <i data-feather="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-white mb-1">Join a Class</h3>
                        <p class="text-sm text-gray-400 mb-6">Enter the class code provided by your teacher to enroll.</p>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2 uppercase tracking-wider">Class Code</label>
                                <input type="text" id="classCodeInput" placeholder="e.g. XJY-342-ZQ" class="w-full bg-dark-bg border border-dark-border text-white text-lg font-mono tracking-widest rounded-lg p-4 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all placeholder:text-gray-600">
                            </div>

                            <div class="bg-amber-500/10 border border-amber-500/20 rounded-lg p-3 flex items-start gap-3">
                                <i data-feather="info" class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5"></i>
                                <p class="text-xs text-amber-200/80">
                                    Make sure the code is correct. You can usually find this on your syllabus or the class board.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-6 pt-2 flex gap-3">
                        <button id="cancelJoinModal" class="flex-1 py-2.5 px-4 text-sm font-medium text-gray-400 bg-dark-bg hover:bg-white/10 border border-dark-border rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button id="submitJoin" class="flex-1 py-2.5 px-4 text-sm font-medium text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-lg shadow-primary-500/20">
                            Join Class
                            <i data-feather="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script type="module">
            import { db, auth } from '../assets/js/firebase-init.js';
            import { collection, query, where, onSnapshot, getDocs, updateDoc, doc, arrayUnion } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
            import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

            let currentStudent = null;

            // --- UI Setup ---
            const renderClasses = (classes) => {
                const grid = document.getElementById('studentClassGrid');
                if (classes.length === 0) {
                    grid.innerHTML = `
                         <div class="col-span-full py-20 text-center opacity-40">
                            <i data-feather="layers" class="w-12 h-12 mx-auto mb-4"></i>
                            <p class="text-[10px] font-black uppercase tracking-widest italic">You haven't joined any classes yet.</p>
                        </div>`;
                } else {
                    grid.innerHTML = classes.map(c => `
                        <div class="glass-panel rounded-xl overflow-hidden border border-dark-border hover:border-primary-500/30 transition-all group flex flex-col animate-fade-in-up">
                            <div class="p-5 border-b border-dark-border flex justify-between items-start">
                                <div>
                                    <span class="text-[10px] font-black text-primary-400 uppercase tracking-widest italic">${c.sectionCode || 'General'}</span>
                                    <h3 class="text-lg font-bold text-white uppercase tracking-tighter italic leading-none">${c.className}</h3>
                                    <p class="text-[9px] text-gray-500 font-bold uppercase mt-1 italic tracking-widest leading-none">${c.teacherName || 'Faculty Account'}</p>
                                </div>
                                <div class="p-2 bg-primary-500/10 rounded-lg text-primary-400 border border-primary-500/20">
                                    <i data-feather="book-open" class="w-5 h-5"></i>
                                </div>
                            </div>
                            <div class="p-5 flex-1 bg-dark-bg/20">
                                <div class="grid grid-cols-2 gap-4 mb-4 text-[10px] uppercase font-black italic tracking-widest opacity-60">
                                    <div class="flex items-center gap-2 text-gray-400">
                                        <i data-feather="calendar" class="w-3.5 h-3.5 text-primary-400"></i> ${c.schedule || 'Days TBA'}
                                    </div>
                                    <div class="flex items-center gap-2 text-gray-400 text-right justify-end">
                                        <i data-feather="clock" class="w-3.5 h-3.5 text-primary-400"></i> ${c.timeSlot || 'TBA'}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest italic text-gray-500">Academic Standing</span>
                                    <span class="text-[10px] font-black uppercase tracking-widest italic text-green-400">Syncing...</span>
                                </div>
                                <div class="w-full h-1 bg-dark-border rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-500/50 rounded-full animate-pulse" style="width: 100%"></div>
                                </div>
                            </div>
                            <div class="p-4 border-t border-dark-border">
                                <a href="student_class_view.php?id=${c.id}" class="w-full py-2.5 text-center text-[10px] font-black uppercase tracking-widest italic text-white bg-dark-bg hover:bg-white/10 rounded-lg transition-colors flex items-center justify-center gap-2 border border-white/5">
                                    OPEN CLASS HUB <i data-feather="chevron-right" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>`).join('');
                }
                feather.replace();
            };

            // --- Joining Logic ---
            window.handleJoinClass = async () => {
                const codeInput = document.getElementById('classCodeInput');
                const code = codeInput.value.trim().toUpperCase();

                if (!code || !currentStudent) {
                    codeInput.classList.add('border-primary-500', 'shake');
                    setTimeout(() => codeInput.classList.remove('shake'), 500);
                    return;
                }

                try {
                    // 1. Find class by code
                    const q = query(collection(db, "classes"), where("classCode", "==", code));
                    const snapshot = await getDocs(q);

                    if (snapshot.empty) {
                        alert("Class code not found. Please try again.");
                        return;
                    }

                    const classDoc = snapshot.docs[0];
                    const classId = classDoc.id;

                    // 2. Check if already joined
                    const classData = classDoc.data();
                    if (classData.students && classData.students.includes(currentStudent.uid)) {
                        alert("You are already enrolled in this class.");
                        return;
                    }

                    // 3. Enroll student
                    await updateDoc(doc(db, "classes", classId), {
                        students: arrayUnion(currentStudent.uid)
                    });

                    alert("Successfully joined class: " + classData.className);
                    codeInput.value = '';
                    window.closeModal();

                } catch (error) {
                    console.error("Join Error:", error);
                    alert("Sync Error: Failed to join class.");
                }
            };

            // --- Modal Helpers ---
            window.closeModal = () => document.getElementById('joinModal').classList.add('hidden-modal');
            window.openModal = () => {
                document.getElementById('joinModal').classList.remove('hidden-modal');
                feather.replace();
            };

            document.getElementById('openJoinModal').onclick = window.openModal;
            document.getElementById('closeJoinModal').onclick = window.closeModal;
            document.getElementById('cancelJoinModal').onclick = window.closeModal;
            document.getElementById('submitJoin').onclick = window.handleJoinClass;

            // --- Identity Handshake ---
            window.addEventListener('profileLoaded', (e) => {
                const data = e.detail;
                const displayName = data.full_name || `${data.firstName || ''} ${data.lastName || ''}`.trim() || 'Student';
                
                // Update Sidebar and Header if they exist in this context
                const sideName = document.getElementById('sideStudentName');
                if (sideName) sideName.textContent = displayName;
                
                const popName = document.getElementById('popoverName');
                if (popName) popName.textContent = displayName;

                const sideYear = document.getElementById('sideStudentYear');
                if (sideYear) sideYear.textContent = data.studentId || "Student Account";
            });

            // --- Lifecycle ---
            onAuthStateChanged(auth, (user) => {
                if (user) {
                    currentStudent = user;
                    // Observe classes where this student is a member
                    const qGrid = query(collection(db, "classes"), where("students", "array-contains", user.uid));
                    onSnapshot(qGrid, (snap) => {
                        const classes = snap.docs.map(d => ({ id: d.id, ...d.data() }));
                        renderClasses(classes);
                    });
                } else {
                    window.location.href = '../login.php';
                }
            });
            
            feather.replace();
        </script>
    </body>
    </html>