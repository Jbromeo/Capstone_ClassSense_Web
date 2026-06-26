<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- classes.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | My Classes</title>
    <?php include '../includes/head.php'; ?>
    <style>
        /* Glass Panel Definition */
        .glass-panel {
            background: rgba(24, 27, 33, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        
        /* Modal Transition States */
        .modal-form-state, .modal-success-state {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .modal.hidden-form .modal-form-state { opacity: 0; position: absolute; pointer-events: none; transform: scale(0.95); }
        .modal.hidden-form .modal-success-state { opacity: 1; position: relative; pointer-events: auto; transform: scale(1); }
        
        .modal:not(.hidden-form) .modal-form-state { opacity: 1; position: relative; pointer-events: auto; transform: scale(1); }
        .modal:not(.hidden-form) .modal-success-state { opacity: 0; position: absolute; pointer-events: none; transform: scale(0.95); }

        /* Code Box Animation */
        @keyframes code-pop {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }
        .code-animate { animation: code-pop 0.4s ease-out forwards; }

        .day-pill.active, .edit-day-pill.active {
            background: #ea2628;
            color: white;
            border-color: #ea2628;
            box-shadow: 0 0 15px rgba(234, 38, 40, 0.3);
        }

        /* Time Picker Dark Mode Visibility */
        input[type="time"]::-webkit-calendar-picker-indicator {
            filter: brightness(2) !important;
            opacity: 1 !important;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 2px;
        }
        input[type="time"]::-webkit-calendar-picker-indicator:hover {
            filter: sepia(100%) saturate(500%) hue-rotate(90deg) brightness(1.2) !important;
            opacity: 1 !important;
            transform: scale(1.3);
            margin-right: 2px;
        }
        input[type="time"] {
            color-scheme: dark !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }
    </style>
</head>
<body class="antialiased min-h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-purple-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 4s"></div>
    </div>
    <div id="toastContainer" class="fixed top-5 right-5 z-[100] flex flex-col gap-3"></div>

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white"><i data-feather="menu"></i></button>
                <h2 class="text-xl font-bold text-white hidden sm:block">My Classes</h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative hidden md:block group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i data-feather="search" class="h-4 w-4 text-gray-500 group-focus-within:text-primary-500 transition-colors"></i>
                    </div>
                    <input type="text" class="bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-full focus:ring-primary-500 focus:border-primary-500 block w-64 pl-10 p-2.5 transition-all focus:w-80 placeholder-gray-600" placeholder="Search classes, students...">
                </div>
                <button class="relative p-2 text-gray-400 hover:text-white transition-colors">
                    <i data-feather="bell"></i>
                    <span class="absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 md:p-8 relative">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-white mb-1">Class Overview</h1>
                    <p class="text-sm text-gray-400">Manage the classes assigned to you by administration.</p>
                </div>
                <div class="flex gap-3">
                    <div class="bg-dark-bg border border-dark-border rounded-lg p-1 flex text-sm">
                        <button class="px-3 py-1.5 bg-white/10 text-white rounded shadow-sm transition-all">Grid</button>
                        <button class="px-3 py-1.5 text-gray-500 hover:text-white transition-all">List</button>
                    </div>
                    <button onclick="openModal()" class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30 transform hover:-translate-y-0.5">
                        <i data-feather="plus" class="w-4 h-4"></i> <span>New Class</span>
                    </button>
                </div>
            </div>

            <!-- Filter Chips -->
            <div class="flex items-center gap-2 overflow-x-auto pb-4 mb-2 scrollbar-hide">
                <button class="filter-chip active px-4 py-1.5 rounded-full text-sm font-medium bg-white/10 text-white border border-white/10 hover:bg-white/20 transition-all whitespace-nowrap">All Classes</button>
                <button class="filter-chip px-4 py-1.5 rounded-full text-sm font-medium bg-dark-bg text-gray-400 border border-dark-border hover:border-gray-600 hover:text-gray-200 transition-all whitespace-nowrap">In Progress</button>
                <button class="filter-chip px-4 py-1.5 rounded-full text-sm font-medium bg-dark-bg text-gray-400 border border-dark-border hover:border-gray-600 hover:text-gray-200 transition-all whitespace-nowrap">Completed</button>
            </div>

            <!-- Dynamic Class Grid -->
            <div id="classGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 pb-12">
                <!-- Data will be injected here -->
                <div class="col-span-full py-20 text-center opacity-40">
                    <div class="animate-pulse space-y-4">
                        <div class="glass-panel h-48 w-full rounded-2xl mx-auto"></div>
                        <p class="text-xs font-black uppercase tracking-widest italic">Syncing with Academia...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Purge Class Confirmation Modal -->
    <div id="purgeClassModal" class="modal fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md transition-opacity opacity-0" id="purgeBackdrop"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="glass-panel w-full max-w-sm rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 border border-primary-500/20 overflow-hidden" id="purgeContent">
                <div class="p-8 text-center">
                    <div class="w-20 h-20 rounded-full bg-primary-500/10 border border-primary-500/20 flex items-center justify-center mx-auto mb-6">
                        <i data-feather="trash-2" class="w-10 h-10 text-primary-500 animate-pulse"></i>
                    </div>
                    <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter leading-none mb-3">Confirm Purge?</h3>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-widest leading-relaxed mb-8 italic opacity-60 px-4">
                        Permanently decommission <span id="purgeClassName" class="text-primary-400">this class</span>? This will remove all student records and cannot be undone.
                    </p>
                    
                    <div class="flex flex-col gap-3">
                        <button id="confirmPurgeBtn" class="w-full py-4 bg-primary-600 hover:bg-primary-700 text-white font-black uppercase tracking-widest italic text-[10px] rounded-xl shadow-lg shadow-primary-500/20 transition-all active:scale-95">
                            Purge Records
                        </button>
                        <button onclick="closePurgeModal()" class="w-full py-4 bg-dark-surface hover:bg-white/5 border border-dark-border text-gray-400 hover:text-white font-black uppercase tracking-widest italic text-[10px] rounded-xl transition-all">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Class Modal -->
    <div id="createClassModal" class="modal fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="glass-panel w-full max-w-lg rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 border border-dark-border overflow-hidden" id="modalContent">
                
                <!-- STATE 1: FORM -->
                <div class="modal-form-state">
                    <div class="p-6 border-b border-dark-border flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-white">Create New Class</h3>
                            <p class="text-xs text-gray-500 mt-1">Fill details to generate a class code.</p>
                        </div>
                        <button onclick="closeModal()" class="p-2 text-gray-500 hover:text-white hover:bg-white/10 rounded-full transition-colors"><i data-feather="x" class="w-5 h-5"></i></button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">Class Name</label>
                            <input type="text" id="classNameInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition-all" placeholder="e.g. CS102: Advanced Python">
                        </div>
                        
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Level</label>
                                <select id="levelInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none italic font-medium">
                                    <option>Senior High School</option>
                                    <option>High School</option>
                                    <option>College / University</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Subject</label>
                                    <select id="subjectInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none italic font-medium"><option>Computer Science</option><option>Mathematics</option><option>Physics</option></select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Section Code</label>
                                    <input id="sectionInput" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none italic font-medium" placeholder="e.g. A-1">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Schedule Days</label>
                                    <div id="daySelector" class="flex gap-1.5 pt-1">
                                        <button type="button" data-day="M" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">M</button>
                                        <button type="button" data-day="T" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">T</button>
                                        <button type="button" data-day="W" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">W</button>
                                        <button type="button" data-day="TH" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">TH</button>
                                        <button type="button" data-day="F" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">F</button>
                                        <button type="button" data-day="S" class="day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">S</button>
                                    </div>
                                    <input type="hidden" id="scheduleDaysInput">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Time Slot</label>
                                    <div class="flex items-center gap-2">
                                        <input type="time" id="startTimeInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-2 py-2 text-xs text-white focus:ring-2 focus:ring-primary-500 outline-none">
                                        <span class="text-gray-600 font-bold">-</span>
                                        <input type="time" id="endTimeInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-2 py-2 text-xs text-white focus:ring-2 focus:ring-primary-500 outline-none">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Session Limit (Minutes)</label>
                                <select id="sessionLimitInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none italic font-medium">
                                    <option value="15">15 Minutes</option>
                                    <option value="30">30 Minutes</option>
                                    <option value="45">45 Minutes</option>
                                    <option value="60">60 Minutes</option>
                                    <option value="0">∞ No Limit (Manual Stop)</option>
                                </select>
                            </div>
                    </div>
                    <div class="p-6 bg-dark-bg/50 border-t border-dark-border flex justify-end gap-3">
                        <button onclick="closeModal()" class="px-5 py-2.5 rounded-lg text-gray-300 hover:text-white hover:bg-white/5 transition-colors text-sm font-medium">Cancel</button>
                        <button onclick="handleCreateClass()" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold shadow-lg shadow-primary-500/20 transition-all transform hover:scale-105">
                            Generate Code
                        </button>
                    </div>
                </div>

                <!-- STATE 2: SUCCESS -->
                <div class="modal-success-state p-8 text-center">
                    <div class="w-16 h-16 rounded-full bg-green-500/10 border border-green-500/20 flex items-center justify-center mx-auto mb-5">
                        <i data-feather="check-circle" class="w-8 h-8 text-green-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Class Created Successfully!</h3>
                    <p class="text-sm text-gray-400 mb-6">Share the code below with your students to let them join.</p>
                    <div class="bg-dark-bg border border-dark-border rounded-xl p-5 flex items-center justify-between mb-6 code-animate">
                        <div class="text-left">
                            <span class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Class Code</span>
                            <span id="generatedCodeDisplay" class="text-3xl font-mono font-bold text-white tracking-widest">XJZ-909</span>
                        </div>
                        <button onclick="copyGeneratedCode()" class="p-3 bg-primary-500/10 text-primary-400 rounded-lg hover:bg-primary-500/20 transition-colors border border-primary-500/20">
                            <i data-feather="clipboard" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <button onclick="closeModal()" class="w-full py-3 bg-dark-surface hover:bg-white/5 border border-dark-border rounded-xl text-white font-medium transition-colors">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div id="editClassModal" class="modal fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity opacity-0" id="editModalBackdrop"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="glass-panel w-full max-w-lg rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 border border-dark-border overflow-hidden" id="editModalContent">
                <div class="p-6 border-b border-dark-border flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white">Edit Class Details</h3>
                        <p class="text-xs text-gray-500 mt-1">Changes will reflect for all enrolled students.</p>
                    </div>
                    <button onclick="closeEditModal()" class="p-2 text-gray-500 hover:text-white hover:bg-white/10 rounded-full transition-colors"><i data-feather="x" class="w-5 h-5"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" id="editClassId">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Class Name</label>
                        <input type="text" id="editClassNameInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Level</label>
                            <select id="editLevelInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none">
                                <option>Senior High School</option>
                                <option>High School</option>
                                <option>College / University</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Subject</label>
                            <select id="editSubjectInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none">
                                <option>Computer Science</option><option>Mathematics</option><option>Physics</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Section Code</label>
                        <input id="editSectionInput" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Schedule Days</label>
                            <div id="editDaySelector" class="flex gap-1.5 pt-1">
                                <button type="button" data-day="M" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">M</button>
                                <button type="button" data-day="T" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">T</button>
                                <button type="button" data-day="W" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">W</button>
                                <button type="button" data-day="TH" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">TH</button>
                                <button type="button" data-day="F" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">F</button>
                                <button type="button" data-day="S" class="edit-day-pill w-8 h-8 rounded-full border border-dark-border text-[10px] font-bold text-gray-500 hover:border-primary-500/50 transition-all">S</button>
                            </div>
                            <input type="hidden" id="editScheduleDaysInput">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Time Slot</label>
                            <div class="flex items-center gap-2">
                                <input type="time" id="editStartTimeInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-2 py-2 text-xs text-white focus:ring-2 focus:ring-primary-500 outline-none">
                                <span class="text-gray-600 font-bold">-</span>
                                <input type="time" id="editEndTimeInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-2 py-2 text-xs text-white focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest italic opacity-60">Session Limit (Minutes)</label>
                        <select id="editSessionLimitInput" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none italic font-medium">
                            <option value="15">15 Minutes</option>
                            <option value="30">30 Minutes</option>
                            <option value="45">45 Minutes</option>
                            <option value="60">60 Minutes</option>
                            <option value="0">∞ No Limit (Manual Stop)</option>
                        </select>
                    </div>
                </div>
                <div class="p-6 bg-dark-bg/50 border-t border-dark-border flex justify-end gap-3">
                    <button onclick="closeEditModal()" class="px-5 py-2.5 rounded-lg text-gray-300 hover:text-white hover:bg-white/5 transition-colors text-sm font-medium">Cancel</button>
                    <button onclick="handleUpdateClass()" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold shadow-lg shadow-primary-500/20 transition-all transform hover:scale-105">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        import { db, auth } from '../assets/js/firebase-init.js';
        import { 
            collection, 
            addDoc, 
            onSnapshot, 
            query, 
            where, 
            serverTimestamp,
            deleteDoc,
            updateDoc,
            doc,
            setDoc
        } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
        import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
        let currentTeacherData = null;

        // --- UI Modal Handlers ---
        window.openModal = () => {
            const modal = document.getElementById('createClassModal');
            const modalContent = document.getElementById('modalContent');
            const modalBackdrop = document.getElementById('modalBackdrop');
            if (!modal) return;
            
            // Reset Day Selector
            document.querySelectorAll('.day-pill').forEach(p => p.classList.remove('active'));
            document.getElementById('scheduleDaysInput').value = '';
            document.getElementById('sessionLimitInput').value = '15';

            modal.classList.remove('hidden-form', 'hidden'); 
            setTimeout(() => { 
                modalBackdrop.classList.remove('opacity-0'); 
                modalContent.classList.remove('opacity-0', 'scale-95'); 
            }, 10);
        };

        // --- Day Selector Handlers ---
        document.addEventListener('DOMContentLoaded', () => {
            // Create Selector
            document.querySelectorAll('.day-pill').forEach(pill => {
                pill.addEventListener('click', () => {
                    pill.classList.toggle('active');
                    const activeDays = Array.from(document.querySelectorAll('.day-pill.active'))
                                            .map(p => p.dataset.day);
                    document.getElementById('scheduleDaysInput').value = activeDays.join('');
                });
            });

            // Edit Selector
            document.querySelectorAll('.edit-day-pill').forEach(pill => {
                pill.addEventListener('click', () => {
                    pill.classList.toggle('active');
                    const activeDays = Array.from(document.querySelectorAll('.edit-day-pill.active'))
                                            .map(p => p.dataset.day);
                    document.getElementById('editScheduleDaysInput').value = activeDays.join('');
                });
            });
        });

        // --- Time Format Helper (Force 12-hour Standard) ---
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

        window.closeModal = () => {
            const modal = document.getElementById('createClassModal');
            const modalContent = document.getElementById('modalContent');
            const modalBackdrop = document.getElementById('modalBackdrop');
            if (!modal) return;
            modalBackdrop.classList.add('opacity-0'); 
            modalContent.classList.add('opacity-0', 'scale-95'); 
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
        };

        window.copyGeneratedCode = () => {
            const code = document.getElementById('generatedCodeDisplay').innerText;
            navigator.clipboard.writeText(code).then(() => {
                window.showToast('Class code copied to clipboard!', 'success');
            });
        };

        // --- Purge Class Logic ---
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
                await deleteDoc(doc(db, "classes", classToDelete));
                window.showToast('Class purged successfully.', 'success');
                window.closePurgeModal();
            } catch (err) {
                console.error("Purge Error:", err);
                window.showToast('Purge Protocol Failure.', 'error');
            }
        };

        // --- Edit Class Logic ---
        let allCurrentClasses = [];

        window.enterHub = (id) => {
            if(!id) return;
            console.log("Entering Hub Sequence:", id);
            window.location.href = `class_view.php?id=${id}`;
        };

        window.handleEditClassClick = (id) => {
            const classData = allCurrentClasses.find(c => c.id === id);
            if (!classData) return;

            document.getElementById('editClassId').value = id;
            document.getElementById('editClassNameInput').value = classData.className;
            document.getElementById('editLevelInput').value = classData.level;
            document.getElementById('editSubjectInput').value = classData.subject;
            document.getElementById('editSectionInput').value = classData.sectionCode;
            document.getElementById('editScheduleDaysInput').value = classData.schedule || '';
            document.getElementById('editStartTimeInput').value = classData.startTime || '';
            document.getElementById('editEndTimeInput').value = classData.endTime || '';
            document.getElementById('editSessionLimitInput').value = classData.sessionLimit || '15';

            // Update Day Pills
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
            const section = document.getElementById('editSectionInput').value;
            const schedule = document.getElementById('editScheduleDaysInput').value;
            const start = document.getElementById('editStartTimeInput').value;
            const end = document.getElementById('editEndTimeInput').value;
            const sessionLimit = document.getElementById('editSessionLimitInput').value;

            if (!name || !section || !schedule || !start || !end) {
                return window.showToast('Details incomplete!', 'error');
            }

            try {
                window.showToast('Updating Class Grid...', 'info');
                const timeSlot = `${formatStandardTime(start)} - ${formatStandardTime(end)}`;
                
                await updateDoc(doc(db, "classes", id), {
                    className: name,
                    level: level,
                    subject: subject,
                    sectionCode: section.toUpperCase(),
                    schedule: schedule,
                    startTime: start,
                    endTime: end,
                    timeSlot: timeSlot,
                    sessionLimit: parseInt(sessionLimit)
                });

                window.showToast('Class updated successfully!', 'success');
                window.closeEditModal();
            } catch (err) {
                console.error("Update Error:", err);
                window.showToast('Update protocol failed.', 'error');
            }
        };

        // --- Firestore: Real-time Rendering ---
        const renderClasses = (classes) => {
            const grid = document.getElementById('classGrid');
            if (!grid) return;

            if (classes.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full py-20 text-center opacity-40">
                        <i data-feather="cloud-off" class="w-12 h-12 mx-auto mb-4"></i>
                        <p class="text-xs font-black uppercase tracking-widest italic tracking-tighter italic">Foundations await... Archive your first grid above.</p>
                    </div>`;
                feather.replace();
                return;
            }

            grid.innerHTML = classes.map(c => `
                <article class="class-card relative glass-panel rounded-2xl overflow-hidden animate-fade-in-up flex flex-col h-full group border border-white/5 hover:border-primary-500/30 transition-all duration-300">
                    <!-- Action Buttons -->
                    <div class="absolute top-4 right-4 flex gap-2 z-20">
                         <button onclick="event.stopPropagation(); window.handleEditClassClick('${c.id}')" class="p-1.5 text-white/40 hover:text-blue-500 bg-black/20 hover:bg-black/40 rounded-md transition-all backdrop-blur-md">
                            <i data-feather="edit-2" class="w-3.5 h-3.5"></i>
                        </button>
                         <button onclick="event.stopPropagation(); window.handleDeleteClassClick('${c.id}', '${c.className.replace(/'/g, "\\'")}')" class="p-1.5 text-white/40 hover:text-primary-500 bg-black/20 hover:bg-black/40 rounded-md transition-all backdrop-blur-md">
                            <i data-feather="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Stretched-Link for Hub Access -->
                    <a href="class_view.php?id=${c.id}" class="absolute inset-0 z-10" aria-label="Enter Hub"></a>

                    <div class="relative h-32 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-primary-600/20 to-dark-surface/80"></div>
                        <div class="absolute top-6 left-6 z-10">
                             <div class="bg-white/5 backdrop-blur-md px-3 py-1 rounded-lg border border-white/10">
                                <span class="text-[10px] font-black text-white italic uppercase tracking-tighter italic">${c.classCode}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 flex flex-col flex-1 relative z-0">
                        <div class="mb-4">
                             <span class="text-[10px] font-black text-primary-400 uppercase tracking-widest italic tracking-tighter italic">${c.sectionCode}</span>
                            <h3 class="text-lg font-bold text-white group-hover:text-primary-400 transition-colors uppercase tracking-widest italic tracking-tighter italic leading-none mt-1">${c.className}</h3>
                            <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mt-2 italic tracking-tighter italic">${c.subject} &bull; ${c.level}</p>
                            
                            <!-- Schedule Badge -->
                            <div class="mt-3 flex items-center gap-2 opacity-70">
                                <i data-feather="clock" class="w-3 h-3 text-primary-400"></i>
                                <span class="text-[9px] font-black text-gray-300 uppercase tracking-widest italic tracking-tighter">
                                    ${c.schedule || 'Schedule TBA'} &bull; 
                                    ${c.timeSlot || 'TBA'} &bull;
                                    <span class="text-primary-400">${c.sessionLimit && c.sessionLimit > 0 ? c.sessionLimit + 'm Limit' : 'Live'}</span>
                                </span>
                            </div>
                        </div>
                        <div class="mt-auto pt-4 border-t border-white/5 flex items-center justify-between">
                            <div class="flex items-center text-[9px] font-black text-gray-400 uppercase tracking-widest italic tracking-tighter italic opacity-60">
                                <i data-feather="users" class="w-3.5 h-3.5 mr-2 text-primary-500"></i> ${c.students?.length || 0} Registered
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

        // --- Firestore: Handlers ---
        window.handleCreateClass = async () => {
            const user = auth.currentUser;
            const nameEl = document.getElementById('classNameInput');
            const sectionEl = document.getElementById('sectionInput');
            const scheduleDays = document.getElementById('scheduleDaysInput').value;
            const startTime = document.getElementById('startTimeInput').value;
            const endTime = document.getElementById('endTimeInput').value;
            const sessionLimit = document.getElementById('sessionLimitInput').value;

            if (!user) return window.showToast('Authentication lag. Refresh required.', 'error');
            
            // 🛡️ Comprehensive Parameter Validation
            if (!nameEl.value || !sectionEl.value || !scheduleDays || !startTime || !endTime) {
                return window.showToast('Schedule parameters incomplete!', 'error');
            }

            // 🛡️ Temporal Guard: Ensure start is strictly before end
            if (startTime === endTime) {
                return window.showToast('Error: Session cannot start and end at the same time!', 'error');
            }
            
            // Compare as numbers for accurate temporal check
            const startVal = parseInt(startTime.replace(':', ''));
            const endVal = parseInt(endTime.replace(':', ''));
            if (startVal > endVal) {
                return window.showToast('Error: Closing time must fall AFTER start time!', 'error');
            }

            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 6; i++) code += chars.charAt(Math.floor(Math.random() * chars.length));

            try {
                window.showToast('Establishing Secure Grid...', 'info');
                
                const timeSlot = `${formatStandardTime(startTime)} - ${formatStandardTime(endTime)}`;

                await addDoc(collection(db, "classes"), {
                    className: nameEl.value,
                    level: document.getElementById('levelInput').value,
                    subject: document.getElementById('subjectInput').value,
                    sectionCode: sectionEl.value.toUpperCase(),
                    classCode: code,
                    schedule: scheduleDays || 'TBA',
                    startTime: startTime,
                    endTime: endTime,
                    timeSlot: timeSlot,
                    sessionLimit: parseInt(sessionLimit),
                    teacherUid: user.uid,
                    teacherName: (currentTeacherData && (currentTeacherData.full_name || `${currentTeacherData.firstName || ''} ${currentTeacherData.lastName || ''}`.trim())) || (user.displayName || 'Faculty Account'),
                    students: [],
                    createdAt: serverTimestamp(),
                    status: 'Active'
                });

                document.getElementById('generatedCodeDisplay').innerText = code;
                document.getElementById('createClassModal').classList.add('hidden-form');
                window.showToast('Hub established successfully.', 'success');

                nameEl.value = '';
                sectionEl.value = '';
                document.getElementById('scheduleDaysInput').value = '';
                document.getElementById('startTimeInput').value = '';
                document.getElementById('endTimeInput').value = '';

            } catch (error) {
                console.error("Grid Sync Failure:", error);
                window.showToast('Cloud architecture error.', 'error');
            }
        };

        onAuthStateChanged(auth, (user) => {
            if (user) {
                const q = query(collection(db, "classes"), where("teacherUid", "==", user.uid));
                
                onSnapshot(q, (snapshot) => {
                    allCurrentClasses = snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
                    renderClasses(allCurrentClasses);
                }, (error) => {
                    console.error("Firestore Sync Error:", error);
                    window.showToast(`Data Sync Error: ${error.message}`, 'error');
                    
                    const grid = document.getElementById('classGrid');
                    if (grid) {
                        grid.innerHTML = `
                            <div class="col-span-full py-20 text-center opacity-40">
                                <i data-feather="alert-triangle" class="w-12 h-12 mx-auto mb-4 text-primary-500 animate-pulse"></i>
                                <p class="text-xs font-black uppercase tracking-widest italic text-primary-400">Sync Protocol Denied</p>
                                <p class="text-[10px] text-gray-500 mt-2 font-mono">${error.code}</p>
                            </div>`;
                        feather.replace();
                    }
                });
            } else {
                window.location.href = '../login.php?status=session_cleared';
            }
        });

        // Initialize from Cache
        const cached = localStorage.getItem('cs_cached_profile');
        if (cached) currentTeacherData = JSON.parse(cached);

        window.addEventListener('profileLoaded', (e) => {
            currentTeacherData = e.detail;
        });

        feather.replace();
    </script>
    <script>
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
    </script>
</body>
</html>