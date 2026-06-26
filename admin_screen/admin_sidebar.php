<!-- admin_screen/admin_sidebar.php -->
<?php
 $current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Overlay -->
<div id="mobileOverlay" class="mobile-overlay fixed inset-0 z-40 md:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="hidden md:flex flex-col w-64 h-full glass-sidebar z-30 transition-all duration-300 transform border-r border-dark-border">
    <!-- Brand -->
    <div class="h-20 flex items-center px-8 border-b border-dark-border">
        <a href="admin_dashboard.php" class="flex items-center space-x-3 group">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-600 to-primary-600 flex items-center justify-center shadow-lg shadow-purple-500/20 mr-3 transition-transform group-hover:scale-110">
                <i data-feather="terminal" class="w-5 h-5 text-white"></i>
            </div>
            <span class="text-xl font-bold tracking-tight text-white uppercase tracking-tighter italic leading-none">Console</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
        <p class="px-4 text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 italic">Administration</p>
        
        <a href="admin_dashboard.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'admin_dashboard.php' ? 'active shadow-lg shadow-purple-500/10' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="activity" class="w-5 h-5 mr-3 text-gray-500 group-hover:text-purple-400 transition-colors"></i>
            Control Center
        </a>

        <a href="manage_teachers.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'manage_teachers.php' ? 'active' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="users" class="w-5 h-5 mr-3 text-gray-500 group-hover:text-purple-400 transition-colors"></i>
            Manage Teachers
        </a>

        <a href="#" class="nav-item group flex items-center px-4 py-3 text-sm font-medium text-gray-500 rounded-lg opacity-40">
            <i data-feather="book" class="w-5 h-5 mr-3 text-gray-500 transition-colors"></i>
            Manage Courses
        </a>
    </nav>

    <!-- Admin Account Header (Simple Popover Trigger) -->
    <div class="p-4 border-t border-dark-border relative bg-dark-surface/50">
        <button id="profileTrigger" class="w-full flex items-center gap-3 p-2 rounded-xl hover:bg-white/5 transition-all group border border-transparent hover:border-white/5">
            <div class="w-9 h-9 rounded-full bg-purple-600 flex items-center justify-center text-white font-black ring-2 ring-purple-500/30">A</div>
            <div class="flex-1 min-w-0 text-left">
                <p class="text-[11px] font-black text-white truncate italic uppercase tracking-tighter leading-none">Administrator</p>
                <p class="text-[9px] text-purple-400 font-bold uppercase tracking-widest truncate leading-none mt-1.5 italic">Superuser</p>
            </div>
            <i data-feather="chevron-up" id="chevronIcon" class="w-4 h-4 text-gray-600 transition-all transform animate-bounce-slow"></i>
        </button>
    </div>
</aside>

<!-- FLOATERS -->
<div id="profilePopover" class="fixed w-56 bg-[#181b21]/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl p-2 hidden animate-fade-in-up origin-bottom z-50">
    <div class="px-4 py-3 border-b border-white/5 mb-1 text-center">
        <p class="text-[11px] font-black text-white truncate italic uppercase tracking-tighter leading-none">System Admin</p>
        <p class="text-[9px] text-purple-400 font-bold uppercase mt-1 leading-none tracking-widest italic">Core Access</p>
    </div>
    <div class="space-y-1">
        <button id="adminLogoutBtn" class="w-full flex items-center gap-3 px-3 py-2.5 text-xs font-black text-primary-400 hover:text-white hover:bg-primary-500 rounded-xl transition-all group uppercase tracking-[0.2em] italic leading-none shadow-lg shadow-primary-500/10">
            <div class="p-1.5 bg-primary-500/10 rounded-lg group-hover:bg-white/20 transition-colors"><i data-feather="log-out" class="w-3.5 h-3.5"></i></div>
            Sign Out
        </button>
    </div>
</div>

<!-- LOGIC -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        feather.replace();
        const trigger = document.getElementById('profileTrigger');
        const popover = document.getElementById('profilePopover');
        const chevron = document.getElementById('chevronIcon');
        const logoutBtn = document.getElementById('adminLogoutBtn');

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = popover.classList.contains('hidden');
            if(isHidden) {
                const rect = trigger.getBoundingClientRect();
                popover.style.left = rect.left + 'px';
                popover.style.bottom = (window.innerHeight - rect.top + 10) + 'px';
                popover.style.width = rect.width + 'px';
                popover.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            } else {
                popover.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        });

        if(logoutBtn) {
            logoutBtn.onclick = (e) => {
                e.stopPropagation();
                popover.classList.add('hidden');
                if(window.openLogoutModal) window.openLogoutModal();
            };
        }

        document.addEventListener('click', (e) => {
            if (!popover.contains(e.target) && !trigger.contains(e.target)) {
                popover.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        });
    });
</script>
<?php include dirname(__DIR__) . '/includes/logout_modal.php'; ?>
