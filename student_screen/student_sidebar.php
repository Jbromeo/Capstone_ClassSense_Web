<!-- student_view/student_sidebar.php -->
<?php
 $current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Overlay -->
<div id="mobileOverlay" class="mobile-overlay fixed inset-0 z-40 md:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="hidden md:flex flex-col w-64 h-full glass-sidebar z-30 transition-all duration-300 transform border-r border-dark-border">
    <!-- Brand -->
    <div class="h-20 flex items-center px-8 border-b border-dark-border bg-white/[0.02]">
        <a href="student_dashboard.php" class="flex items-center space-x-3 group">
            <img src="../assets/classsense-logo.png" class="w-8 h-8 rounded-lg object-cover mr-3 transition-transform group-hover:scale-110">
            <span class="text-xl font-bold tracking-tight text-white uppercase tracking-tighter italic">ClassSense</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
        <p class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Main Menu</p>
        
        <a href="student_dashboard.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'student_dashboard.php' ? 'active shadow-lg shadow-primary-500/10' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="grid" class="w-5 h-5 mr-3 text-gray-500 group-hover:text-primary-500 transition-colors"></i>
            Dashboard
        </a>

        <a href="student_classes.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'student_classes.php' ? 'active' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="book-open" class="w-5 h-5 mr-3 text-gray-500 group-hover:text-primary-500 transition-colors"></i>
            My Classes
        </a>

        <a href="student_attendance_record.php" class="nav-item group flex items-center px-4 py-3 text-sm font-medium <?php echo $current_page == 'student_attendance_record.php' ? 'active' : 'text-gray-400'; ?> rounded-lg">
            <i data-feather="check-square" class="w-5 h-5 mr-3 text-gray-500 group-hover:text-primary-500 transition-colors"></i>
            Records
        </a>

        <div class="pt-4 mt-4 border-t border-dark-border">
            <button id="profileTrigger" class="w-full flex items-center gap-3 p-2 rounded-xl hover:bg-white/5 transition-all group border border-transparent hover:border-white/5">
                <div class="relative">
                <div id="sideProfileImg" class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-600 to-primary-900 flex items-center justify-center text-white font-black text-xs border border-white/10 uppercase shadow-lg ring-2 ring-dark-bg group-hover:ring-primary-500/30 transition-all" style="font-size:0.65rem">
                    
                    </div>
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-dark-bg rounded-full"></div>
                </div>
                <div class="flex-1 min-w-0 text-left">
                    <p id="sideStudentName" class="text-[11px] font-black text-white truncate uppercase tracking-tighter leading-none overflow-hidden" style="white-space:nowrap">Loading...</p>
                    <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest truncate leading-none mt-1.5">Verified Portal</p>
                </div>
                <i data-feather="chevron-up" id="chevronIcon" class="w-4 h-4 text-gray-600 group-hover:text-white transition-all transform animate-bounce-slow"></i>
            </button>
        </div>
    </nav>
</aside>

<!-- FLOATERS -->
<div id="profilePopover" class="fixed w-56 bg-dark-surface/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl p-2 hidden animate-fade-in-up origin-bottom z-50">
    <div class="px-4 py-3 border-b border-white/5 mb-1">
        <p id="popoverName" class="text-[11px] font-black text-white truncate uppercase tracking-tighter">Student Account</p>
        <p class="text-[9px] text-gray-500 truncate lowercase font-bold tracking-widest mt-1 italic">Active Identity</p>
    </div>
    <div class="space-y-1">
        <a href="student_profile.php" class="flex items-center gap-3 px-3 py-2.5 text-xs font-medium text-gray-400 hover:text-white hover:bg-white/5 rounded-xl transition-all group font-bold italic border border-transparent hover:border-white/5">
            <div class="p-1.5 bg-blue-500/10 rounded-lg text-blue-500 group-hover:bg-blue-500/20"><i data-feather="user" class="w-3.5 h-3.5"></i></div>
            My Profile
        </a>
        <button id="logoutBtn" class="w-full flex items-center gap-3 px-3 py-2.5 text-xs font-black text-primary-400 hover:text-white hover:bg-primary-500 rounded-xl transition-all group uppercase tracking-[0.2em] italic leading-none">
            <div class="p-1.5 bg-primary-500/10 rounded-lg group-hover:bg-white/20"><i data-feather="log-out" class="w-3.5 h-3.5"></i></div>
            Sign Out
        </button>
    </div>
</div>

<!-- LOGIC -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const trigger = document.getElementById('profileTrigger');
        const popover = document.getElementById('profilePopover');
        const chevron = document.getElementById('chevronIcon');
        const logoutBtn = document.getElementById('logoutBtn');

        if (trigger && popover) {
            trigger.addEventListener('click', (e) => {
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
            });
        }

        if(logoutBtn && popover) {
            logoutBtn.onclick = (e) => {
                e.stopPropagation();
                popover.classList.add('hidden');
                console.log('[sidebar] logout clicked, openLogoutModal:', typeof window.openLogoutModal);
                if (window.openLogoutModal) {
                    window.openLogoutModal();
                } else {
                    console.log('[sidebar] openLogoutModal not found, using confirm fallback');
                    if (confirm('Are you sure you want to sign out?')) {
                        window.location.replace('/ClassSense/login.php');
                    }
                }
            };
        }

        document.addEventListener('click', (e) => {
            if (popover && trigger && !popover.contains(e.target) && !trigger.contains(e.target)) {
                popover.classList.add('hidden');
                if(chevron) chevron.classList.remove('rotate-180');
            }
        });
    });
</script>
<?php include dirname(__DIR__) . '/includes/logout_modal.php'; ?>
