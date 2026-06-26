<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- students.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | Students</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased min-h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white"><i data-feather="menu"></i></button>
                <h2 class="text-xl font-bold text-white hidden sm:block">Student Directory</h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative hidden md:block group"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i data-feather="search" class="h-4 w-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i></div><input type="text" class="bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-full focus:ring-primary-500 focus:border-primary-500 block w-64 pl-10 p-2.5 transition-all focus:w-80 placeholder-gray-600" placeholder="Search by name or ID..."></div>
                <button class="relative p-2 text-gray-400 hover:text-white transition-colors"><i data-feather="bell"></i></button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 md:p-8 relative">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="glass-panel p-4 rounded-xl border-l-4 border-l-blue-500"><p class="text-xs font-medium text-gray-400 uppercase">Total Students</p><h3 class="text-2xl font-bold text-white" id="statTotalStudents">0</h3></div>
                <div class="glass-panel p-4 rounded-xl border-l-4 border-l-purple-500"><p class="text-xs font-medium text-gray-400 uppercase">Average GPA</p><h3 class="text-2xl font-bold text-white" id="statAvgGPA">0.0</h3></div>
                <div class="glass-panel p-4 rounded-xl border-l-4 border-l-amber-500"><p class="text-xs font-medium text-gray-400 uppercase">At Risk</p><h3 class="text-2xl font-bold text-amber-400" id="statAtRisk">0</h3></div>
                <div class="glass-panel p-4 rounded-xl border-l-4 border-l-green-500"><p class="text-xs font-medium text-gray-400 uppercase">Top Performer</p><h3 class="text-lg font-bold text-white truncate" id="statTopPerformer">-</h3></div>
            </div>
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <i data-feather="filter" class="absolute left-3 top-2.5 w-4 h-4 text-gray-500"></i>
                        <select id="classFilter" class="w-full bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 pl-10 p-2.5 appearance-none cursor-pointer hover:border-gray-600 transition-colors">
                            <option value="all">All My Classes</option>
                            <!-- Dynamically loaded -->
                        </select>
                    </div>
                </div>
                <button onclick="openAddStudentModal()" class="w-full md:w-auto flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-all shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30">
                    <i data-feather="user-plus" class="w-4 h-4"></i><span>Enroll Student</span>
                </button>
            </div>
            
            <div class="glass-panel rounded-xl border border-dark-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-xs uppercase text-gray-500 font-bold tracking-wider bg-dark-bg/40 border-b border-dark-border">
                                <th class="p-4 pl-6">Student</th>
                                <th class="p-4">Student ID</th>
                                <th class="p-4">Enrolled Class</th>
                                <th class="p-4">Atnd. Rate</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right pr-6">Performance</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTableBody" class="text-sm text-gray-300 divide-y divide-dark-border italic font-medium">
                            <!-- Injected by JS -->
                        </tbody>
                    </table>
                </div>
                <div id="tableEmptyState" class="hidden flex-col items-center justify-center py-20 text-gray-500 italic">
                    <i data-feather="users" class="w-12 h-12 mb-3 opacity-20"></i>
                    <p class="text-xs uppercase tracking-widest font-black">No active enrollments detected on this frequency.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- ENROLL STUDENT MODAL -->
    <div id="addStudentModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity opacity-0" id="addModalBackdrop" onclick="closeAddStudentModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="glass-panel w-full max-w-md rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 border border-dark-border overflow-hidden" id="addModalContent">
                <div class="p-6 border-b border-dark-border flex justify-between items-center text-white italic">
                    <h3 class="text-xl font-black uppercase tracking-tighter">Manual Class Enrollment</h3>
                    <button onclick="closeAddStudentModal()"><i data-feather="x"></i></button>
                </div>
                <form id="addStudentForm" onsubmit="handleAddStudent(event)" class="p-6 space-y-4 font-bold italic">
                    <div>
                        <label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-1 opacity-60">Target Class</label>
                        <select name="classId" id="enrollClassSelect" required class="w-full bg-dark-bg border border-dark-border rounded-xl px-4 py-3 text-white text-sm outline-none focus:ring-2 focus:ring-primary-500">
                            <!-- Loaded via JS -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-1 opacity-60">Student Identifier</label>
                        <input type="text" name="studentId" required class="w-full bg-dark-bg border border-dark-border rounded-xl px-4 py-3 text-white text-sm outline-none focus:ring-2 focus:ring-primary-500" placeholder="Enter Student ID (e.g. 2024-XXXXX)">
                        <p class="text-[9px] text-gray-600 mt-2">Identifier must exist in the global database.</p>
                    </div>
                    <div class="pt-4 flex justify-end gap-3 font-black">
                        <button type="button" onclick="closeAddStudentModal()" class="px-5 py-3 rounded-xl text-gray-500 hover:text-white uppercase text-[10px] tracking-widest transition-all">Abort</button>
                        <button type="submit" id="enrollBtn" class="px-8 py-3 rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-lg shadow-primary-500/20 uppercase text-[10px] tracking-widest transition-all">Submit Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- STUDENT PERFORMANCE MODAL -->
    <div id="performanceModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity opacity-0" id="perfModalBackdrop" onclick="closePerformanceModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="glass-panel w-full max-w-2xl max-h-[90vh] rounded-2xl shadow-2xl transform translate-x-full opacity-0 transition-all duration-300 border border-gray-700 pointer-events-auto flex flex-col overflow-hidden" id="perfModalContent">
                <div class="relative h-32 bg-gradient-to-r from-primary-900 to-gray-900 shrink-0"><div class="absolute inset-0 bg-black/20"></div><button onclick="closePerformanceModal()" class="absolute top-4 right-4 p-2 bg-black/30 hover:bg-black/50 rounded-full text-white transition-colors"><i data-feather="x"></i></button></div>
                <div class="px-8 -mt-12 mb-6 flex justify-between items-end shrink-0 relative z-10">
                    <div class="flex items-end gap-4"><img id="perfAvatar" src="" class="w-24 h-24 rounded-2xl border-4 border-dark-bg object-cover shadow-xl"><div><h2 id="perfName" class="text-2xl font-bold text-white">Student Name</h2><p id="perfClass" class="text-gray-400 text-sm font-medium">Class Name</p></div></div>
                    <div class="mb-2 text-right"><div class="text-xs text-gray-500 uppercase tracking-wider">Overall GPA</div><div id="perfGPA" class="text-3xl font-bold text-primary-500">0.0</div></div>
                </div>
                <div class="px-8 pb-8 overflow-y-auto custom-scroll flex-1">
                    <div class="grid grid-cols-3 gap-4 mb-8"><div class="bg-dark-bg p-3 rounded-lg border border-dark-border text-center"><div class="text-xs text-gray-500 mb-1">Attendance</div><div id="perfAttendance" class="text-lg font-bold text-green-400">0%</div></div><div class="bg-dark-bg p-3 rounded-lg border border-dark-border text-center"><div class="text-xs text-gray-500 mb-1">Assignments</div><div id="perfAssignments" class="text-lg font-bold text-blue-400">0/0</div></div><div class="bg-dark-bg p-3 rounded-lg border border-dark-border text-center"><div class="text-xs text-gray-500 mb-1">Rank</div><div id="perfRank" class="text-lg font-bold text-purple-400">#0</div></div></div>
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-gray-700 pb-2">Subject Grades</h4><div id="perfGradesList" class="space-y-4 mb-8"></div>
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-2">Teacher's Notes</h4><div class="bg-dark-bg/50 rounded-lg p-4 text-sm text-gray-300 border border-dark-border italic">"Student shows great promise in algorithmic thinking but needs to improve submission punctuality."</div>
                </div>
                <div class="p-4 border-t border-gray-700 bg-dark-bg/30 flex justify-end gap-3 shrink-0"><button class="px-4 py-2 text-sm font-medium text-gray-400 hover:text-white transition-colors">Send Message</button><button class="px-4 py-2 text-sm font-medium bg-white/10 hover:bg-white/20 text-white rounded-lg transition-colors" onclick="closePerformanceModal()">Close Report</button></div>
            </div>
        </div>
    </div>

    <script type="module">
        import { db, auth } from '../assets/js/firebase-init.js';
        import { 
            collection, 
            doc, 
            getDoc, 
            getDocs, 
            query, 
            where, 
            onSnapshot,
            updateDoc,
            arrayUnion
        } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
        import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

        let myClasses = [];
        let allStudentsInMyClasses = [];
        let currentFilter = 'all';

        // 1. Initial State Handshake
        onAuthStateChanged(auth, (user) => {
            if (user) {
                initDirectory(user.uid);
            } else {
                window.location.href = '../login.php?error=identity_missing';
            }
        });

        async function initDirectory(uid) {
            const classesRef = collection(db, "classes");
            const q = query(classesRef, where("teacherUid", "==", uid));
            
            try {
                const snapshot = await getDocs(q);
                myClasses = snapshot.docs.map(d => ({ id: d.id, ...d.data() }));
                updateClassFilters();
                await fetchAllStudentsMetadata();
            } catch (error) {
                console.error("Directory Sync Error:", error);
                if(window.showStatus) window.showStatus(`Directory Sync Blocked: ${error.message}`, 'error');
                
                const tbody = document.getElementById('studentsTableBody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="p-20 text-center">
                                <div class="flex flex-col items-center gap-2 opacity-50">
                                    <i data-feather="lock" class="w-8 h-8 text-primary-500 animate-pulse"></i>
                                    <span class="text-xs font-black uppercase tracking-widest italic text-primary-400">Directory Access Denied</span>
                                    <span class="text-[9px] font-mono">${error.code}</span>
                                </div>
                            </td>
                        </tr>`;
                    feather.replace();
                }
            }
        }

        // 2. Data Aggregation (Classes -> Students Profiles)
        async function fetchAllStudentsMetadata() {
            const studentUids = [...new Set(myClasses.flatMap(c => c.students || []))];
            
            if (studentUids.length === 0) {
                allStudentsInMyClasses = [];
                renderTable([]);
                updateStats();
                return;
            }

            // Fetch each profile
            const profiles = await Promise.all(studentUids.map(async (sid) => {
                const sDoc = await getDoc(doc(db, "students", sid));
                if (sDoc.exists()) {
                    const data = sDoc.data();
                    // Associate with their classes for the table view
                    const enrolledClasses = myClasses.filter(c => c.students && c.students.includes(sid));
                    return { uid: sid, ...data, classes: enrolledClasses };
                }
                return null;
            }));

            allStudentsInMyClasses = profiles.filter(p => p !== null);
            applyCurrentFilters();
            updateStats();
        }

        // 3. UI Synchronization
        function updateClassFilters() {
            const filterSelect = document.getElementById('classFilter');
            const enrollSelect = document.getElementById('enrollClassSelect');
            if(!filterSelect || !enrollSelect) return;

            const optionsHTML = myClasses.map(c => `<option value="${c.id}">${c.className}</option>`).join('');
            
            filterSelect.innerHTML = '<option value="all">All My Classes</option>' + optionsHTML;
            enrollSelect.innerHTML = '<option value="" disabled selected>Select Class to Enroll In</option>' + optionsHTML;
        }

        function applyCurrentFilters() {
            let data = [...allStudentsInMyClasses];
            if (currentFilter !== 'all') {
                data = data.filter(s => s.classes.some(c => c.id === currentFilter));
            }
            renderTable(data);
        }

        function renderTable(data) {
            const tbody = document.getElementById('studentsTableBody');
            const emptyState = document.getElementById('tableEmptyState');
            tbody.innerHTML = '';

            if (data.length === 0) {
                emptyState.classList.remove('hidden');
                emptyState.classList.add('flex');
                return;
            }
            emptyState.classList.add('hidden');
            emptyState.classList.remove('flex');

            data.forEach(student => {
                const tr = document.createElement('tr');
                tr.className = "border-b border-dark-border hover:bg-white/5 transition-all group cursor-pointer";
                tr.onclick = () => openPerformanceModal(student.uid);

                const fullName = `${student.firstName || ''} ${student.lastName || ''}`.trim() || 'Unknown Student';
                const classLabels = student.classes.map(c => 
                    `<span class="px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[9px] uppercase font-black mr-1">${c.sectionCode || 'N/A'}</span>`
                ).join('');

                tr.innerHTML = `
                    <td class="p-4 pl-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-600 to-primary-900 flex items-center justify-center text-white font-black text-xs border border-white/10 uppercase italic">
                                ${(student.firstName?.charAt(0) || '') + (student.lastName?.charAt(0) || '')}
                            </div>
                            <div>
                                <div class="font-bold text-white group-hover:text-primary-400 transition-colors tracking-tighter truncate max-w-[150px] italic uppercase">${fullName}</div>
                                <div class="text-[10px] text-gray-500 font-bold uppercase truncate max-w-[150px] opacity-70 italic whitespace-nowrap">${student.email || 'No Email'}</div>
                            </div>
                        </div>
                    </td>
                    <td class="p-4 font-black text-xs text-gray-400 tracking-widest">${student.studentId || 'NOT_SET'}</td>
                    <td class="p-4">${classLabels}</td>
                    <td class="p-4 text-xs font-black text-white italic tracking-tighter">94%</td>
                    <td class="p-4">
                        <span class="px-2 py-1 rounded-md text-[9px] font-black border bg-green-500/10 text-green-400 border-green-500/20 uppercase tracking-widest">Active</span>
                    </td>
                    <td class="p-4 text-right pr-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/5 rounded-lg border border-white/5 group-hover:border-primary-500/30 transition-all">
                             <span class="text-[10px] font-black text-primary-400 italic">3.8</span>
                             <i data-feather="chevron-right" class="w-3 h-3 text-gray-600 group-hover:text-primary-500"></i>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            feather.replace();
        }

        function updateStats() {
            const total = allStudentsInMyClasses.length;
            const topStudent = total > 0 ? allStudentsInMyClasses[0] : null;
            const topName = topStudent ? `${topStudent.firstName || ''} ${topStudent.lastName || ''}`.trim() : "-";

            document.getElementById('statTotalStudents').innerText = total;
            document.getElementById('statAvgGPA').innerText = "3.5"; 
            document.getElementById('statAtRisk').innerText = "0";
            document.getElementById('statTopPerformer').innerText = topName;
        }

        // 4. Action Handlers
        document.getElementById('classFilter').onchange = (e) => {
            currentFilter = e.target.value;
            applyCurrentFilters();
        };

        window.handleAddStudent = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('enrollBtn');
            const originalText = btn.innerText;
            
            const formData = new FormData(e.target);
            const classId = formData.get('classId');
            const studentIdInput = formData.get('studentId').trim();

            if(!classId || !studentIdInput) return window.showStatus('Missing requirements', 'error');

            try {
                btn.disabled = true;
                btn.innerText = 'ENROLLING...';
                
                // Search for student with this ID
                const studentsRef = collection(db, "students");
                const q = query(studentsRef, where("student_id", "==", studentIdInput));
                const querySnapshot = await getDocs(q);

                if (querySnapshot.empty) {
                    window.showStatus("Identifier not found in database", "error");
                    btn.disabled = false;
                    btn.innerText = originalText;
                    return;
                }

                const studentUid = querySnapshot.docs[0].id;
                const studentName = querySnapshot.docs[0].data().full_name;

                // Enroll into class
                const classRef = doc(db, "classes", classId);
                await updateDoc(classRef, {
                    students: arrayUnion(studentUid)
                });

                window.showStatus(`${studentName} Enrolled!`, "success");
                closeAddStudentModal();
            } catch (err) {
                console.error(err);
                if(window.showStatus) window.showStatus("Enrollment Fault", "error");
            } finally {
                btn.disabled = false;
                btn.innerText = originalText;
            }
        };

        // UI Helpers
        window.openAddStudentModal = () => {
            const modal = document.getElementById('addStudentModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('addModalBackdrop').classList.remove('opacity-0');
                document.getElementById('addModalContent').classList.remove('opacity-0', 'scale-95');
            }, 10);
        };

        window.closeAddStudentModal = () => {
            document.getElementById('addModalBackdrop').classList.add('opacity-0');
            document.getElementById('addModalContent').classList.add('opacity-0', 'scale-95');
            setTimeout(() => document.getElementById('addStudentModal').classList.add('hidden'), 300);
        };

        window.openPerformanceModal = async (uid) => {
            const student = allStudentsInMyClasses.find(s => s.uid === uid);
            if(!student) return;

            const fullName = `${student.firstName || ''} ${student.lastName || ''}`.trim() || 'Unknown Student';
            document.getElementById('perfName').innerText = fullName;
            document.getElementById('perfClass').innerText = student.classes.map(c => c.className).join(', ');
            document.getElementById('perfAvatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(fullName)}&background=ea2628&color=fff`;
            
            const modal = document.getElementById('performanceModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('perfModalBackdrop').classList.remove('opacity-0');
                document.getElementById('perfModalContent').classList.remove('translate-x-full', 'opacity-0');
            }, 10);
        };

        window.closePerformanceModal = () => {
            document.getElementById('perfModalBackdrop').classList.add('opacity-0');
            document.getElementById('perfModalContent').classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => document.getElementById('performanceModal').classList.add('hidden'), 300);
        };

        window.showStatus = (msg, type) => {
            if(window.showToast) window.showToast(msg, type); else alert(msg);
        };

        // Reuse existing sidebar script or just replace icons
        feather.replace();
    </script>

    <script>
        // Non-module helpers
        window.showToast = (message, type = 'info') => {
             const container = document.getElementById('toastContainer') || document.body;
             const toast = document.createElement('div');
             toast.className = `fixed bottom-5 right-5 z-[100] flex items-center p-4 space-x-3 text-gray-200 bg-dark-bg rounded-xl shadow-2xl border border-dark-border transition-all duration-300 transform translate-y-20 ${type === 'success' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-primary-500'}`;
             toast.innerHTML = `<div class="font-black uppercase tracking-widest italic text-[10px]">${message}</div>`;
             container.appendChild(toast);
             setTimeout(() => toast.classList.remove('translate-y-20'), 10);
             setTimeout(() => { toast.classList.add('translate-y-20'); setTimeout(() => toast.remove(), 300); }, 3000);
        };

        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        if(mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                sidebar.classList.add('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64');
            });
        }
    </script>
    <script type="module" src="../assets/js/firebase-init.js"></script>
</body>
</html>