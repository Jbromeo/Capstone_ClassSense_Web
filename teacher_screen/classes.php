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

        /* Floating Action Button */
        @keyframes fab-pulse {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(1.6); opacity: 0; }
        }
        .fab-pulse {
            animation: fab-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">
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
                    <input id="globalSearchInput" type="text" class="bg-dark-bg border border-dark-border text-gray-300 text-sm rounded-full focus:ring-primary-500 focus:border-primary-500 block w-64 pl-10 p-2.5 transition-all focus:w-80 placeholder-gray-600" placeholder="Search classes...">
                </div>
                <div class="relative">
                    <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors group">
                        <i data-feather="bell"></i>
                        <span class="notif-dot hidden absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                    </button>
                    <?php include '../includes/notification_popover.php'; ?>
                </div>
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
                        <button id="viewGridBtn" data-view="grid" class="view-toggle px-3 py-1.5 bg-white/10 text-white rounded shadow-sm transition-all">Grid</button>
                        <button id="viewListBtn" data-view="list" class="view-toggle px-3 py-1.5 text-gray-500 hover:text-white transition-all">List</button>
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

    <?php include 'classes/purge_class_modal.php'; ?>
    <?php include 'classes/create_class_modal.php'; ?>
    <?php include 'classes/edit_class_modal.php'; ?>

    <!-- ADD CLASS FLOATING ACTION BUTTON (FAB) -->
    <div class="fixed bottom-6 right-6 z-40">
        <div class="absolute inset-0 bg-primary-500 rounded-full fab-pulse"></div>
        <button onclick="openModal()" class="relative flex items-center justify-center w-14 h-14 bg-primary-500 rounded-full shadow-lg shadow-primary-500/30 hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-offset-2 focus:ring-offset-dark-bg transition-all transform hover:scale-105 active:scale-95">
            <i data-feather="plus" class="w-6 h-6 text-white"></i>
        </button>
    </div>

    <script type="module" src="classes/classes.js"></script>
</body>
</html>