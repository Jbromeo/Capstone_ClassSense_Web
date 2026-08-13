<?php
// teacher_screen/sidebar.php
 $current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Overlay -->
<div id="mobileOverlay" class="mobile-overlay fixed inset-0 z-40 md:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="hidden md:flex flex-col w-64 h-full glass-sidebar z-30 transition-all duration-300 transform border-r border-dark-border">
    <!-- Brand -->
    <div class="h-20 flex items-center px-8 border-b border-dark-border">
        <a href="teacher_dashboard.php" class="flex items-center space-x-3 group text-decoration-none">
            <img src="../assets/classsense-logo.png" class="w-8 h-8 rounded-lg object-cover mr-3 transition-transform group-hover:scale-110">
            <span class="text-xl font-bold tracking-tight text-white uppercase tracking-tighter italic">ClassSense</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1 custom-scrollbar">
        <p class="px-4 text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 italic">Main Menu</p>
        
        <a href="teacher_dashboard.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'teacher_dashboard.php' ? 'active shadow-lg shadow-primary-500/10 text-primary-500' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="grid" class="w-5 h-5 mr-3 <?php echo $current_page == 'teacher_dashboard.php' ? 'text-primary-500' : 'text-gray-500'; ?> group-hover:text-primary-500 transition-colors"></i>
            Dashboard
        </a>

        <!-- My Classes with Integrated Submenu -->
        <div class="space-y-1">
            <a href="classes.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo ($current_page == 'classes.php' || $current_page == 'class_view.php') ? 'active shadow-lg shadow-primary-500/10 text-primary-500' : 'text-gray-400'; ?> rounded-lg">
                <i data-feather="book-open" class="w-5 h-5 mr-3 <?php echo ($current_page == 'classes.php' || $current_page == 'class_view.php') ? 'text-primary-500' : 'text-gray-500'; ?> group-hover:text-primary-500 transition-colors"></i>
                My Classes
            </a>

            <?php if ($current_page == 'class_view.php'): ?>
            <div class="ml-9 pl-4 border-l border-dark-border/50 space-y-1 py-2 animate-fade-in">
                <button id="nav-students-sidebar" onclick="switchTab('students')" class="w-full class-control-nav group flex items-center px-4 py-2 text-[11px] font-bold text-gray-400 hover:text-white rounded-lg transition-all">
                    <i data-feather="users" class="w-3.5 h-3.5 mr-3 text-gray-500 group-hover:text-primary-500 transition-colors"></i>
                    Class Roster
                </button>

                <button id="nav-grading-sidebar" onclick="switchTab('grading')" class="w-full class-control-nav group flex items-center px-4 py-2 text-[11px] font-bold text-gray-400 hover:text-white rounded-lg transition-all">
                    <i data-feather="bar-chart-2" class="w-3.5 h-3.5 mr-3 text-gray-500 group-hover:text-primary-500 transition-colors"></i>
                    Grading Center
                </button>

                <button id="nav-attendance-sidebar" onclick="switchTab('attendance')" class="w-full class-control-nav group flex items-center px-4 py-2 text-[11px] font-bold text-gray-400 hover:text-white rounded-lg transition-all">
                    <i data-feather="calendar" class="w-3.5 h-3.5 mr-3 text-gray-500 group-hover:text-primary-500 transition-colors"></i>
                    Attendance History
                </button>
            </div>
            <?php endif; ?>
        </div>

        <a href="students.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'students.php' ? 'active shadow-lg shadow-primary-500/10 text-primary-500' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="users" class="w-5 h-5 mr-3 <?php echo $current_page == 'students.php' ? 'text-primary-500' : 'text-gray-500'; ?> group-hover:text-primary-500 transition-colors"></i>
            Students
        </a>

        <a href="attendance.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'attendance.php' ? 'active shadow-lg shadow-primary-500/10 text-primary-500' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="check-square" class="w-5 h-5 mr-3 <?php echo $current_page == 'attendance.php' ? 'text-primary-500' : 'text-gray-500'; ?> group-hover:text-primary-500 transition-colors"></i>
            Scan Attendance
        </a>

        <a href="attendance_history.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'attendance_history.php' ? 'active shadow-lg shadow-primary-500/10 text-primary-500' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="database" class="w-5 h-5 mr-3 <?php echo $current_page == 'attendance_history.php' ? 'text-primary-500' : 'text-gray-500'; ?> group-hover:text-primary-500 transition-colors"></i>
            Attendance Logs
        </a>

        <p class="px-4 text-[10px] font-black text-gray-500 uppercase tracking-widest mt-6 mb-3 italic">Account</p>

        <a href="profile_settings.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'profile_settings.php' ? 'active shadow-lg shadow-primary-500/10 text-primary-500' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="user" class="w-5 h-5 mr-3 <?php echo $current_page == 'profile_settings.php' ? 'text-primary-500' : 'text-gray-500'; ?> group-hover:text-primary-500 transition-colors"></i>
            Profile Settings
        </a>
    </nav>

    <!-- Teacher Profile Footer Trigger -->
    <div class="p-4 border-t border-dark-border relative bg-dark-surface/50">
        <button id="profileTrigger" class="w-full flex items-center gap-3 p-2 rounded-xl hover:bg-white/5 transition-all group border border-transparent hover:border-white/5">
            <div class="relative">
                <img id="sideProfileImg" src="https://ui-avatars.com/api/?name=KR&background=ea2628&color=fff&bold=true" alt="Profile" class="w-9 h-9 rounded-full ring-2 ring-dark-bg object-cover group-hover:ring-primary-500/30 transition-all">
                <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-dark-bg rounded-full"></div>
            </div>
            <div class="flex-1 min-w-0 text-left">
                <p id="sideTeacherName" class="text-[11px] font-black text-white truncate italic uppercase tracking-tighter leading-none">Faculty Account</p>
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest truncate leading-none mt-1.5 italic">Verified Educator</p>
            </div>
            <i data-feather="chevron-up" id="chevronIcon" class="w-4 h-4 text-gray-600 group-hover:text-white transition-all transform animate-bounce-slow"></i>
        </button>
    </div>
</aside>

<!-- FLOATERS -->
<div id="profilePopover" class="fixed w-56 bg-dark-surface/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl p-2 hidden animate-fade-in-up origin-bottom z-50">
    <div class="px-4 py-3 border-b border-white/5 mb-1 text-center">
        <p class="text-[11px] font-black text-white truncate italic uppercase tracking-tighter leading-none">Professor Panel</p>
        <p class="text-[9px] text-gray-500 font-bold uppercase mt-1 leading-none tracking-widest italic tracking-tighter">Academic Control</p>
    </div>
    <div class="space-y-1">
        <a href="profile_settings.php" class="flex items-center gap-3 px-3 py-2.5 text-xs font-medium text-gray-400 hover:text-white hover:bg-white/5 rounded-xl transition-all group font-bold italic border border-transparent hover:border-white/5">
            <div class="p-1.5 bg-blue-500/10 rounded-lg text-blue-500 group-hover:bg-blue-500/20 transition-colors"><i data-feather="user" class="w-3.5 h-3.5"></i></div>
            Account Profile
        </a>
        <button id="logoutBtn" class="w-full flex items-center gap-3 px-3 py-2.5 text-xs font-black text-primary-400 hover:text-white hover:bg-primary-500 rounded-xl transition-all group uppercase tracking-[0.2em] italic leading-none">
            <div class="p-1.5 bg-primary-500/10 rounded-lg group-hover:bg-white/20 transition-colors"><i data-feather="log-out" class="w-3.5 h-3.5"></i></div>
            Sign Out
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        feather.replace();
        const trigger = document.getElementById('profileTrigger');
        const popover = document.getElementById('profilePopover');
        const chevron = document.getElementById('chevronIcon');
        const logoutBtn = document.getElementById('logoutBtn');

        if (trigger && popover) {
            trigger.onclick = (e) => {
                e.stopPropagation();
                const isHidden = popover.classList.contains('hidden');
                if(isHidden) {
                    const rect = trigger.getBoundingClientRect();
                    popover.style.left = rect.left + 'px';
                    popover.style.bottom = (window.innerHeight - rect.top + 10) + 'px';
                    popover.style.width = rect.width + 'px';
                    popover.classList.remove('hidden');
                    if(chevron) chevron.classList.add('rotate-180');
                } else {
                    popover.classList.add('hidden');
                    if(chevron) chevron.classList.remove('rotate-180');
                }
            };
        }

        if(logoutBtn) {
            logoutBtn.onclick = (e) => {
                e.stopPropagation();
                popover.classList.add('hidden');
                if(window.openLogoutModal) window.openLogoutModal();
            };
        }

        document.addEventListener('click', (e) => {
            if (popover && !popover.contains(e.target) && !trigger.contains(e.target)) {
                popover.classList.add('hidden');
                if(chevron) chevron.classList.remove('rotate-180');
            }
        });

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
        const sideName = document.getElementById('sideTeacherName');
        const headerName = document.getElementById('headerTeacherName');
        const sideImg = document.getElementById('sideProfileImg');
        const fullName = data.full_name || `${data.firstName || ''} ${data.lastName || ''}`.trim() || 'Faculty Account';

        if (sideName) { sideName.textContent = fullName; sideName.classList.remove('italic'); }
        if (headerName) { headerName.textContent = fullName; }
        if (sideImg) {
            if (data.profileImage && !data.profileImage.includes('ui-avatars')) {
                sideImg.src = data.profileImage;
            } else {
                const initials = `${data.firstName?.[0] || ''}${data.lastName?.[0] || ''}`.toUpperCase() || 'FA';
                sideImg.src = `https://ui-avatars.com/api/?name=${initials}&background=ea2628&color=fff&bold=true`;
            }
        }
    });
</script>
<?php include dirname(__DIR__) . '/includes/logout_modal.php'; ?>