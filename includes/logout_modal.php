<!-- includes/logout_modal.php -->
<div id="logoutConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <!-- Backdrop Blur -->
    <div class="absolute inset-0 bg-dark-bg/40 backdrop-blur-md"></div>
    
    <!-- Modal -->
    <div class="glass-panel w-full max-w-sm rounded-[2.5rem] p-10 border border-white/10 shadow-2xl transform scale-95 transition-transform duration-300 relative z-10 text-center">
        <!-- Subtle Icon -->
        <div class="w-16 h-16 bg-primary-500/10 rounded-full flex items-center justify-center mx-auto mb-6 border border-primary-500/10">
            <i data-feather="log-out" class="w-8 h-8 text-primary-500"></i>
        </div>
        
        <!-- Text -->
        <h3 class="text-2xl font-black text-white italic mb-2 tracking-tight">Log Out?</h3>
        <p class="text-gray-400 text-sm mb-10 leading-relaxed font-bold italic opacity-80 uppercase tracking-widest text-[10px]">Are you sure you want to end your current session?</p>
        
        <!-- Minimal Actions -->
        <div class="grid grid-cols-2 gap-4">
            <button id="closeLogoutModal" class="w-full py-4 bg-white/5 hover:bg-white/10 rounded-2xl font-black text-gray-500 hover:text-white transition-all text-xs uppercase tracking-widest leading-none">
                No
            </button>
            <button id="confirmLogoutAction" class="w-full py-4 bg-primary-500 hover:bg-primary-600 rounded-2xl font-black text-white transition-all shadow-lg shadow-primary-500/20 uppercase tracking-[0.2em] italic text-xs leading-none">
                Yes, Sign Out
            </button>
        </div>
    </div>
</div>

<style>
    #logoutConfirmModal.show { opacity: 1; }
    #logoutConfirmModal.show > div:last-child { transform: scale(1); }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const logoutModal = document.getElementById('logoutConfirmModal');
        const confirmBtn = document.getElementById('confirmLogoutAction');
        const cancelBtn = document.getElementById('closeLogoutModal');

        const closeLogoutModal = () => {
            logoutModal.classList.remove('show');
            setTimeout(() => logoutModal.classList.add('hidden'), 300);
        };

        window.openLogoutModal = () => {
            logoutModal.classList.remove('hidden');
            setTimeout(() => logoutModal.classList.add('show'), 10);
            feather.replace();
        };

        cancelBtn.onclick = closeLogoutModal;
        confirmBtn.onclick = async () => {
             const root = window.CS_ROOT || '/ClassSense/';
             if(window.logoutUser) {
                 window.logoutUser();
             } else {
                 try {
                     await fetch(root + 'api/logout.php');
                     window.location.replace(root + 'login.php?status=session_terminated');
                 } catch (err) {
                     window.location.replace(root + 'login.php?error=logout_failure');
                 }
             }
        };
    });
</script>
