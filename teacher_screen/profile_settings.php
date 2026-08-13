<?php 
// 1. Core Verification Handshake
require_once dirname(__DIR__) . '/core/init.php'; 
?>
<!-- profile_settings.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | Profile & Settings</title>
    <?php include '../includes/head.php'; ?>
</head>
<body class="antialiased h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

    <!-- Ambient Background (Consistent with style.css blob animation) -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 2s"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-purple-900/10 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 4s"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LCAyNTUsIDI1NSwgMC4wNSkiLz48L3N2Zz4=')] [mask-image:linear-gradient(to_bottom,white,transparent)]"></div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>

    <?php include 'sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        <!-- Header -->
        <header class="h-20 glass-panel border-b-0 border-dark-border flex items-center justify-between px-6 z-20">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-white">
                    <i data-feather="menu"></i>
                </button>
                <h2 class="text-xl font-bold text-white hidden sm:block">Settings</h2>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative">
                <button id="headerNotifyBtn" class="relative p-2 text-gray-400 hover:text-white transition-colors group">
                    <i data-feather="bell"></i>
                    <span class="notif-dot hidden absolute top-1.5 right-1.5 block h-2 w-2 rounded-full ring-2 ring-dark-bg bg-primary-500"></span>
                </button>
                <?php include '../includes/notification_popover.php'; ?>
            </div>
            </div>
        </header>

        <!-- Scrollable Content -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8 relative custom-scroll">
            <div class="max-w-5xl mx-auto space-y-8 animate-fade-in-up">
                
                <!-- Profile Header Card -->
                <div class="glass-panel rounded-3xl p-8 mb-8 border-l-4 border-l-primary-500 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                        <i data-feather="user" class="w-32 h-32 text-white"></i>
                    </div>

                    <div class="flex flex-col md:flex-row items-center gap-8 relative z-10">
                        <div class="relative">
                            <div id="profileAvatarInit" class="w-32 h-32 rounded-full bg-gradient-to-br from-primary-600 to-primary-900 flex items-center justify-center text-white font-black uppercase border border-white/10 shadow-2xl ring-4 ring-dark-bg" style="font-size:2.5rem">KR</div>
                        </div>
                        <div class="text-center md:text-left">
                            <h1 id="profileNameLarge" class="text-4xl font-black text-white mb-2 leading-none italic animate-fade-in">Loading Profile...</h1>
                            <p id="profileEmailLarge" class="text-sm text-gray-400 font-medium italic mb-4">Connecting account...</p>
                            <div class="flex flex-wrap justify-center md:justify-start gap-3">
                                <span class="px-3 py-1 rounded-full bg-green-500/10 border border-green-500/20 text-green-400 text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5">
                                    <i data-feather="check" class="w-3 h-3"></i> Active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    // Cache recovery: restore identity once profile data is available.
                    const recoverTimer = setInterval(() => {
                        if (!window.handleProfileLoad) return;
                        let profile = window.csProfile;
                        if (!profile) {
                            const cached = localStorage.getItem('cs_cached_profile');
                            if (cached) {
                                try { profile = JSON.parse(cached); } catch (e) { profile = null; }
                            }
                        }
                        if (profile) {
                            window.handleProfileLoad(profile);
                            clearInterval(recoverTimer);
                        }
                    }, 800);
                </script>

                <!-- Settings Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left Column: Navigation -->
                    <div class="lg:col-span-1">
                        <div class="glass-panel rounded-xl p-2 sticky top-8">
                            <nav class="space-y-1">
                                <button onclick="switchTab('profile')" id="tab-btn-profile" class="nav-item active w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-colors">
                                    <i data-feather="user" class="w-5 h-5"></i>
                                    <span class="font-medium">Profile</span>
                                </button>
                                <button onclick="switchTab('security')" id="tab-btn-security" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-colors text-gray-400 hover:text-white">
                                    <i data-feather="shield" class="w-5 h-5"></i>
                                    <span class="font-medium">Security</span>
                                </button>
                                <button onclick="switchTab('notifications')" id="tab-btn-notifications" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-colors text-gray-400 hover:text-white">
                                    <i data-feather="bell" class="w-5 h-5"></i>
                                    <span class="font-medium">Notifications</span>
                                </button>
                                <button onclick="switchTab('preferences')" id="tab-btn-preferences" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-colors text-gray-400 hover:text-white">
                                    <i data-feather="sliders" class="w-5 h-5"></i>
                                    <span class="font-medium">Preferences</span>
                                </button>
                            </nav>
                            <div class="border-t border-dark-border mt-2 pt-2">
                                <button onclick="window.openLogoutModal()" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left text-red-400 hover:bg-red-500/10 transition-colors">
                                    <i data-feather="log-out" class="w-5 h-5"></i>
                                    <span class="font-medium">Log Out</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Content Forms -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- PROFILE TAB -->
                        <div id="tab-content-profile" class="tab-content animate-fade-in">
                            <div class="glass-panel rounded-xl p-8">
                                <h3 class="text-lg font-bold text-white mb-6">Personal Details</h3>
                                <form class="space-y-6" onsubmit="event.preventDefault(); window.updateProfile();">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-400 mb-2 font-black uppercase tracking-widest italic leading-none opacity-60">First Name</label>
                                            <input id="inFirstName" type="text" placeholder="First Name" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition-all italic font-medium">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-400 mb-2 font-black uppercase tracking-widest italic leading-none opacity-60">Last Name</label>
                                            <input id="inLastName" type="text" placeholder="Last Name" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition-all italic font-medium">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-2 font-black uppercase tracking-widest italic leading-none opacity-60">Email Address</label>
                                        <input id="inEmail" type="email" placeholder="Email Address" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition-all italic font-medium">
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-lg transition-all shadow-lg shadow-primary-500/20">
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- SECURITY TAB -->
                        <div id="tab-content-security" class="tab-content hidden">
                            <div class="glass-panel rounded-xl p-8 mb-6 animate-fade-in">
                                <h3 class="text-lg font-bold text-white mb-6">Change Password</h3>
                                <form class="space-y-6" onsubmit="event.preventDefault(); showToast('Password updated', 'success');">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-2">Current Password</label>
                                        <input type="password" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-primary-500 outline-none" placeholder="••••••••">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-2">New Password</label>
                                        <input type="password" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-primary-500 outline-none" placeholder="••••••••">
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" class="px-6 py-2.5 bg-white/10 hover:bg-white/20 text-white font-medium rounded-lg transition-all border border-dark-border">
                                            Update Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- NOTIFICATIONS TAB -->
                        <div id="tab-content-notifications" class="tab-content hidden">
                            <div class="glass-panel rounded-xl p-8 animate-fade-in">
                                <h3 class="text-lg font-bold text-white mb-6">Notification Preferences</h3>
                                <div class="space-y-6">
                                    <div class="flex items-center justify-between p-4 bg-dark-bg/40 rounded-lg border border-dark-border">
                                        <div>
                                            <h4 class="text-white font-medium">Push Notifications</h4>
                                            <p class="text-sm text-gray-500">Receive push notifications on your devices.</p>
                                            <p id="pushStatus" class="text-xs text-gray-600 mt-1">Loading preference...</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="pushToggle" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-primary-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PREFERENCES TAB -->
                        <div id="tab-content-preferences" class="tab-content hidden">
                            <div class="glass-panel rounded-xl p-8 animate-fade-in">
                                <h3 class="text-lg font-bold text-white mb-6">System Preferences</h3>
                                <div class="space-y-6">
                                    <div class="flex items-center justify-between p-4 bg-dark-bg/40 rounded-lg border border-dark-border">
                                        <div>
                                            <h4 class="text-white font-medium">Dark Mode</h4>
                                            <p class="text-sm text-gray-500">Toggle between light and dark themes.</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="darkModeToggle" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-primary-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>

    <script type="module">
        import { api, initPage } from '../assets/js/custom-auth.js';
        window.api = api;
        window.initPage = initPage;
    </script>
    <script type="module">
        import { enablePush, disablePush, savePushSettings, loadPushSettings, onForegroundMessage } from '../assets/js/push-manager.js';

        // --- Push Notification Toggle Logic ---
        const pushToggle = document.getElementById('pushToggle');
        const pushStatus = document.getElementById('pushStatus');
        let lastPushToken = null;

        function setPushStatus(text, type = '') {
            if (!pushStatus) return;
            pushStatus.textContent = text;
            pushStatus.className = 'text-xs mt-1 ' + (type === 'error' ? 'text-red-400' : type === 'success' ? 'text-green-500' : 'text-gray-600');
        }

        async function initPushToggle() {
            if (!pushToggle) return;
            try {
                const state = await loadPushSettings();
                pushToggle.checked = !!state.pushEnabled;
                if (state.deviceCount > 0 && state.pushEnabled) {
                    setPushStatus(`Enabled on ${state.deviceCount} device${state.deviceCount > 1 ? 's' : ''}.`, 'success');
                } else if (!state.pushEnabled) {
                    setPushStatus('Disabled. In-app notifications still work.');
                }
            } catch (e) {
                console.warn('Failed to load push settings:', e);
                setPushStatus('Could not load preference.');
            }
        }

        pushToggle.addEventListener('change', async () => {
            const enabled = pushToggle.checked;
            pushToggle.disabled = true;

            if (enabled) {
                setPushStatus('Requesting permission...');
                try {
                    const token = await enablePush();
                    await savePushSettings(true, token);
                    lastPushToken = token;
                    setPushStatus('Enabled. You will receive device notifications.', 'success');
                    showToast('Push notifications enabled', 'success');
                } catch (e) {
                    pushToggle.checked = false;
                    setPushStatus(e.message || 'Failed to enable push notifications.', 'error');
                    showToast(e.message || 'Failed to enable push notifications', 'error');
                } finally {
                    pushToggle.disabled = false;
                }
            } else {
                setPushStatus('Disabling...');
                try {
                    await savePushSettings(false, lastPushToken || undefined);
                    await disablePush();
                    lastPushToken = null;
                    setPushStatus('Disabled. In-app notifications still work.', 'success');
                    showToast('Push notifications disabled', 'success');
                } catch (e) {
                    pushToggle.checked = true;
                    setPushStatus('Failed to disable push notifications.', 'error');
                    showToast('Failed to disable push notifications', 'error');
                } finally {
                    pushToggle.disabled = false;
                }
            }
        });

        // Foreground delivery while the settings page is open.
        onForegroundMessage((payload) => {
            const title = (payload.notification && payload.notification.title) || 'ClassSense';
            const body = (payload.notification && payload.notification.body) || '';
            showToast(body ? `${title} — ${body}` : title, 'info');
        });

        initPushToggle();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();
        });

        // Mobile Menu Logic
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileOverlay = document.getElementById('mobileOverlay');
        const sidebar = document.getElementById('sidebar');

        mobileMenuBtn.addEventListener('click', () => {
            if(sidebar.style.display === 'flex') {
                sidebar.style.display = ''; 
                sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64');
                mobileOverlay.classList.remove('open');
            } else {
                sidebar.style.display = 'flex';
                sidebar.classList.add('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64');
                mobileOverlay.classList.add('open');
            }
        });

        mobileOverlay.addEventListener('click', () => {
            sidebar.style.display = '';
            sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-50', 'w-64');
            mobileOverlay.classList.remove('open');
        });

        // Tab Switching Logic
        function switchTab(tabName) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            
            // Remove active state from all buttons
            document.querySelectorAll('.nav-item').forEach(el => {
                el.classList.remove('active', 'bg-white/10', 'text-white');
                el.classList.add('text-gray-400');
            });

            // Show selected content
            const activeContent = document.getElementById('tab-content-' + tabName);
            activeContent.classList.remove('hidden');
            // Restart animation
            activeContent.style.animation = 'none';
            activeContent.offsetHeight; /* trigger reflow */
            activeContent.style.animation = null; 

            // Activate button
            const activeBtn = document.getElementById('tab-btn-' + tabName);
            activeBtn.classList.add('active', 'text-white');
            activeBtn.classList.remove('text-gray-400');
        }

        // Toast Logic
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast flex items-center w-full max-w-xs p-4 space-x-4 text-gray-200 bg-gray-800 rounded-lg shadow-lg border border-gray-700 ${type === 'success' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-primary-500'}`;
            toast.innerHTML = `
                <div class="flex-shrink-0">
                    ${type === 'success' 
                        ? '<svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>' 
                        : '<i data-feather="info" class="w-5 h-5 text-primary-500"></i>'}
                </div>
                <div class="text-sm font-normal">${message}</div>
            `;
            container.appendChild(toast);
            feather.replace();
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }

        // --- Identity Sync Logic ---
        let currentUid = null;

        window.updateProfile = async () => {
            const firstName = document.getElementById('inFirstName').value;
            const lastName = document.getElementById('inLastName').value;

            if (!currentUid) return showToast('Session identity lost.', 'error');
            if (!firstName || !lastName) return showToast('Identity parameters incomplete!', 'error');

            try {
                showToast('Syncing with Cloud Hub...', 'info');
                
                await api('/fetch.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        uid: currentUid,
                        role: 'teacher',
                        firstName: firstName,
                        lastName: lastName,
                        full_name: `${firstName} ${lastName}`
                    })
                });

                // Update local cache to prevent refresh flicker
                const cached = JSON.parse(localStorage.getItem('cs_cached_profile') || '{}');
                cached.firstName = firstName;
                cached.lastName = lastName;
                cached.full_name = `${firstName} ${lastName}`;
                localStorage.setItem('cs_cached_profile', JSON.stringify(cached));

                showToast('Professional profile updated.', 'success');
                
                // Update header immediately
                const nameEl = document.getElementById('profileNameLarge');
                nameEl.textContent = `${firstName} ${lastName}`;
                nameEl.classList.remove('italic');
            } catch (error) {
                console.error("Profile Sync Error:", error);
                showToast('Cloud architecture sync failed.', 'error');
            }
        };

        // --- Identity Sync Logic ---
        window.handleProfileLoad = (data) => {
            currentUid = data.uid;

            // Update Headers
            const profileHeader = document.getElementById('profileNameLarge');
            const profileEmail = document.getElementById('profileEmailLarge');
            const profileAvatar = document.getElementById('profileAvatarInit');

            if (profileHeader) {
                profileHeader.textContent = data.full_name || `${data.firstName || ''} ${data.lastName || ''}`.trim() || 'Faculty Account';
                profileHeader.classList.remove('italic');
            }
            if (profileEmail) profileEmail.textContent = data.email || 'No email provided';
            
            // Handle Avatar Initials
            if (profileAvatar) {
                const initials = `${data.firstName?.[0] || data.full_name?.[0] || 'K'}${data.lastName?.[0] || ''}`;
                profileAvatar.textContent = initials.toUpperCase();
            }

            // Populate Form Fields
            const inputs = {
                'inFirstName': data.firstName,
                'inLastName': data.lastName,
                'inEmail': data.email
            };

            for (const [id, value] of Object.entries(inputs)) {
                const el = document.getElementById(id);
                if (el) {
                    el.value = value || '';
                    el.classList.remove('italic');
                }
            }
        };

        window.addEventListener('profileLoaded', (e) => handleProfileLoad(e.detail));

        // 🛡️ IMMEDIATE SYNC: Catch the profile if it loaded before this script
        if (window.csProfile) {
            console.log("Settings: Catching already loaded profile");
            handleProfileLoad(window.csProfile);
        }
    </script>
    <!-- Master Orchestration -->
    <script type="module" src="../assets/js/custom-auth.js"></script>
    <script src="../assets/js/theme-toggle.js" defer></script>
</body>
</html>