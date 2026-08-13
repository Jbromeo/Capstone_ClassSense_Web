<?php
 $current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Overlay -->
<div id="mobileOverlay" class="mobile-overlay fixed inset-0 z-40 md:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="hidden md:flex flex-col w-64 h-full glass-sidebar z-30 transition-all duration-300 transform border-r border-dark-border">
    <!-- Brand -->
    <div class="h-20 flex items-center px-8 border-b border-dark-border">
        <a href="admin_dashboard.php" class="flex items-center space-x-3 group text-decoration-none">
            <img src="../assets/classsense-logo.png" class="w-8 h-8 rounded-lg object-cover mr-3 transition-transform group-hover:scale-110">
            <span class="text-xl font-bold tracking-tight text-white uppercase tracking-tighter italic">ClassSense</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1 custom-scrollbar">
        <p class="px-4 text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 italic">Administration</p>
        
        <a href="admin_dashboard.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'admin_dashboard.php' ? 'active shadow-lg shadow-purple-500/10 text-purple-500' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="grid" class="w-5 h-5 mr-3 <?php echo $current_page == 'admin_dashboard.php' ? 'text-purple-500' : 'text-gray-500'; ?> group-hover:text-purple-500 transition-colors"></i>
            Dashboard
        </a>

        <a href="manage_teachers.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'manage_teachers.php' ? 'active shadow-lg shadow-purple-500/10 text-purple-500' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="users" class="w-5 h-5 mr-3 <?php echo $current_page == 'manage_teachers.php' ? 'text-purple-500' : 'text-gray-500'; ?> group-hover:text-purple-500 transition-colors"></i>
            Manage Teachers
        </a>

        <a href="pre_approve_students.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'pre_approve_students.php' ? 'active shadow-lg shadow-blue-500/10 text-blue-500' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="user-check" class="w-5 h-5 mr-3 <?php echo $current_page == 'pre_approve_students.php' ? 'text-blue-500' : 'text-gray-500'; ?> group-hover:text-blue-500 transition-colors"></i>
            Pre-Approved Students
        </a>

        <a href="manage_students.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'manage_students.php' ? 'active shadow-lg shadow-blue-500/10 text-blue-500' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="book" class="w-5 h-5 mr-3 <?php echo $current_page == 'manage_students.php' ? 'text-blue-500' : 'text-gray-500'; ?> group-hover:text-blue-500 transition-colors"></i>
            Manage Students
        </a>

        <a href="admin_settings.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'admin_settings.php' ? 'active shadow-lg shadow-purple-500/10 text-purple-500' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="settings" class="w-5 h-5 mr-3 <?php echo $current_page == 'admin_settings.php' ? 'text-purple-500' : 'text-gray-500'; ?> group-hover:text-purple-500 transition-colors"></i>
            Settings
        </a>

        <div class="pt-4 mt-4 border-t border-dark-border">
            <button id="adminProfileTrigger" class="nav-item w-full group flex items-center px-4 py-3 text-sm font-medium text-gray-400 hover:text-white rounded-lg transition-all">
                <div class="relative mr-3">
                    <div id="adminAvatar" class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-600 to-purple-900 flex items-center justify-center text-white font-black text-[10px] border border-white/10 uppercase italic shadow-lg ring-2 ring-dark-bg group-hover:ring-purple-500/30 transition-all">
                        A
                    </div>
                    <div class="absolute -bottom-0.5 -right-0.5 w-2 h-2 bg-green-500 border-2 border-dark-bg rounded-full"></div>
                </div>
                <span id="adminSideName" class="flex-1 text-left text-[11px] font-black text-white truncate italic uppercase tracking-tighter leading-none">Administrator</span>
                <i data-feather="chevron-up" id="adminChevron" class="w-3.5 h-3.5 text-gray-600 group-hover:text-white transition-all"></i>
            </button>
        </div>

    </nav>

</aside>

<!-- FLOATERS -->
<div id="adminProfileDropdown" class="fixed w-56 bg-dark-surface/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl p-2 hidden animate-fade-in-up origin-bottom z-50">
    <div class="px-4 py-3 border-b border-white/5 mb-1 text-center">
        <p id="adminDropdownName" class="text-[11px] font-black text-white truncate italic uppercase tracking-tighter">System Admin</p>
        <p class="text-[9px] text-purple-400 font-bold uppercase mt-1 leading-none tracking-widest italic">Core Access</p>
    </div>
    <div class="space-y-1">
        <button id="adminLogoutBtn" class="w-full flex items-center gap-3 px-3 py-2.5 text-xs font-black text-primary-400 hover:text-white hover:bg-primary-500 rounded-xl transition-all group uppercase tracking-[0.2em] italic leading-none">
            <div class="p-1.5 bg-primary-500/10 rounded-lg group-hover:bg-white/20 transition-colors"><i data-feather="log-out" class="w-3.5 h-3.5"></i></div>
            Sign Out
        </button>
    </div>
</div>

<!-- LOGIC -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        feather.replace();
        const trigger = document.getElementById('adminProfileTrigger');
        const dropdown = document.getElementById('adminProfileDropdown');
        const chevron = document.getElementById('adminChevron');
        const logoutBtn = document.getElementById('adminLogoutBtn');

        if (trigger && dropdown) {
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                const isHidden = dropdown.classList.contains('hidden');
                if(isHidden) {
                    const rect = trigger.getBoundingClientRect();
                    dropdown.style.left = rect.left + 'px';
                    dropdown.style.bottom = (window.innerHeight - rect.top + 10) + 'px';
                    dropdown.style.width = rect.width + 'px';
                    dropdown.classList.remove('hidden');
                    if(chevron) chevron.classList.add('rotate-180');
                } else {
                    dropdown.classList.add('hidden');
                    if(chevron) chevron.classList.remove('rotate-180');
                }
            });

            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
                    dropdown.classList.add('hidden');
                    if(chevron) chevron.classList.remove('rotate-180');
                }
            });
        }

        if(logoutBtn) {
            logoutBtn.onclick = (e) => {
                e.stopPropagation();
                if(dropdown) dropdown.classList.add('hidden');
                if(window.openLogoutModal) window.openLogoutModal();
            };
        }

        // Mobile Menu Global Logic
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileOverlay = document.getElementById('mobileOverlay');
        const sidebar = document.getElementById('sidebar');

        if (mobileMenuBtn && sidebar && mobileOverlay) {
            mobileMenuBtn.onclick = () => {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-y-0');
                sidebar.classList.toggle('left-0');
                sidebar.classList.toggle('w-64');
                mobileOverlay.classList.toggle('open');
            };

            mobileOverlay.onclick = () => {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'w-64');
                mobileOverlay.classList.remove('open');
            };
        }
    });

    // Profile Synchronization
    window.addEventListener('profileLoaded', (e) => {
        const data = e.detail;
        const avatar = document.getElementById('adminAvatar');
        const sideName = document.getElementById('adminSideName');
        const dropdownName = document.getElementById('adminDropdownName');

        if (avatar) {
            avatar.textContent = (data.email?.[0] || 'A').toUpperCase();
        }
        if (sideName) {
            sideName.textContent = data.email || 'Administrator';
        }
        if (dropdownName) {
            dropdownName.textContent = data.email || 'System Admin';
        }
    });
</script>
<?php include dirname(__DIR__) . '/includes/logout_modal.php'; ?>
