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
<body class="antialiased min-h-screen overflow-hidden flex selection:bg-primary-500 selection:text-white">

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
                <button class="relative p-2 text-gray-400 hover:text-white transition-colors">
                    <i data-feather="bell"></i>
                </button>
            </div>
        </header>

        <!-- Scrollable Content -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8 relative custom-scroll">
            <div class="max-w-5xl mx-auto space-y-8 animate-fade-in-up">
                
                <!-- Profile Header Card -->
                <div class="glass-panel rounded-2xl overflow-hidden relative">
                    <div class="h-32 bg-gradient-to-r from-primary-900 to-dark-surface"></div>
                    <div class="px-8 pb-8 -mt-12 relative z-10">
                        <div class="flex flex-col md:flex-row md:items-end gap-6">
                            <div class="relative group">
                                <img id="profileImgLarge" src="https://ui-avatars.com/api/?name=KR&background=ea2628&color=fff&bold=true&size=150" class="w-28 h-28 rounded-2xl border-4 border-dark-bg object-cover shadow-xl">
                                <button onclick="showToast('Upload Avatar clicked')" class="absolute inset-0 bg-black/50 rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                    <i data-feather="camera" class="w-6 h-6 text-white"></i>
                                </button>
                            </div>
                            <div class="flex-1">
                                <h1 id="profileNameLarge" class="text-2xl font-bold text-white italic truncate tracking-tighter uppercase leading-none">Loading Identity...</h1>
                                <p id="profileEmailLarge" class="text-gray-400 mt-2 font-medium italic">Handshaking: <span id="diagStatus" class="text-primary-500 animate-pulse">Waiting for Brain...</span></p>

                                <script>
                                    // 🔍 REAL-TIME DIAGNOSTIC + DIRECT CACHE RECOVERY
                                    setInterval(() => {
                                        const diag = document.getElementById('diagStatus');
                                        if (!diag) return;
                                        
                                        // Try to find profile in memory or cache
                                        let profile = window.csProfile;
                                        if (!profile) {
                                            const cached = localStorage.getItem('cs_cached_profile');
                                            if (cached) {
                                                console.log("Settings: Recovering profile from direct cache.");
                                                profile = JSON.parse(cached);
                                                window.csProfile = profile;
                                            }
                                        }

                                        if (profile) {
                                            diag.textContent = "Identity Restored!";
                                            diag.classList.remove('text-primary-500', 'animate-pulse');
                                            diag.classList.add('text-green-500');

                                            // 🛡️ AGGRESSIVE OVERWRITE
                                            const nameEl = document.getElementById('profileNameLarge');
                                            if (nameEl) {
                                                nameEl.textContent = profile.full_name || `${profile.firstName || ''} ${profile.lastName || ''}`.trim() || 'Faculty Account';
                                                nameEl.classList.remove('italic');
                                            }
                                            
                                            if (window.handleProfileLoad) window.handleProfileLoad(profile);
                                        } else {
                                            diag.textContent = "Establishing Cloud Sync...";
                                        }
                                    }, 800);
                                </script>
                            </div>
                            <div class="flex gap-3">
                                <span class="px-3 py-1 bg-primary-500/10 text-primary-400 border border-primary-500/30 rounded-full text-sm font-medium">Admin</span>
                                <span class="px-3 py-1 bg-green-500/10 text-green-400 border border-green-500/30 rounded-full text-sm font-medium">Active</span>
                            </div>
                        </div>
                    </div>
                </div>

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
                                <button onclick="showToast('Logging out...', 'info')" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left text-red-400 hover:bg-red-500/10 transition-colors">
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
                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-2 font-black uppercase tracking-widest italic leading-none opacity-60">Bio</label>
                                        <textarea id="inBio" rows="3" placeholder="Tell us about yourself..." class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition-all resize-none italic font-medium"></textarea>
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

                            <div class="glass-panel rounded-xl p-8 border border-amber-500/20 bg-amber-500/5 animate-fade-in">
                                <div class="flex items-start gap-4">
                                    <div class="p-2 bg-amber-500/10 rounded-lg text-amber-400">
                                        <i data-feather="shield" class="w-6 h-6"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-white font-bold mb-1">Two-Factor Authentication</h4>
                                        <p class="text-sm text-gray-400 mb-4">Add an extra layer of security to your account by enabling 2FA.</p>
                                        <button class="px-4 py-2 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-amber-400 rounded-lg text-sm font-medium transition-colors">
                                            Enable 2FA
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- NOTIFICATIONS TAB -->
                        <div id="tab-content-notifications" class="tab-content hidden">
                            <div class="glass-panel rounded-xl p-8 animate-fade-in">
                                <h3 class="text-lg font-bold text-white mb-6">Notification Preferences</h3>
                                <div class="space-y-6">
                                    <div class="flex items-center justify-between p-4 bg-dark-bg/40 rounded-lg border border-dark-border">
                                        <div>
                                            <h4 class="text-white font-medium">Email Notifications</h4>
                                            <p class="text-sm text-gray-500">Receive emails about your account activity.</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" checked class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-primary-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                        </label>
                                    </div>
                                    <div class="flex items-center justify-between p-4 bg-dark-bg/40 rounded-lg border border-dark-border">
                                        <div>
                                            <h4 class="text-white font-medium">Push Notifications</h4>
                                            <p class="text-sm text-gray-500">Receive push notifications on your devices.</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer">
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
                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-2">Language</label>
                                        <select class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-primary-500 outline-none">
                                            <option selected>English (US)</option>
                                            <option>Spanish</option>
                                            <option>French</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center justify-between p-4 bg-dark-bg/40 rounded-lg border border-dark-border">
                                        <div>
                                            <h4 class="text-white font-medium">Dark Mode</h4>
                                            <p class="text-sm text-gray-500">Toggle between light and dark themes.</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" checked class="sr-only peer">
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
            const bio = document.getElementById('inBio').value;

            if (!currentUid) return showToast('Session identity lost.', 'error');
            if (!firstName || !lastName) return showToast('Identity parameters incomplete!', 'error');

            try {
                showToast('Syncing with Cloud Hub...', 'info');
                
                const { doc, updateDoc } = await import("https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js");
                const teacherRef = doc(db, "teachers", currentUid);
                
                await updateDoc(teacherRef, {
                    firstName: firstName,
                    lastName: lastName,
                    full_name: `${firstName} ${lastName}`,
                    bio: bio,
                    updatedAt: new Date()
                });

                // Update local cache to prevent refresh flicker
                const cached = JSON.parse(localStorage.getItem('cs_cached_profile') || '{}');
                cached.firstName = firstName;
                cached.lastName = lastName;
                cached.full_name = `${firstName} ${lastName}`;
                cached.bio = bio;
                localStorage.setItem('cs_cached_profile', JSON.stringify(cached));

                showToast('Professional profile updated.', 'success');
                
                // Update header immediately
                document.getElementById('profileNameLarge').textContent = `${firstName} ${lastName}`;
            } catch (error) {
                console.error("Profile Sync Error:", error);
                showToast('Cloud architecture sync failed.', 'error');
            }
        };

        // --- Identity Sync Logic ---
        window.handleProfileLoad = (data) => {
            currentUid = auth.currentUser?.uid || data.uid;

            // Update Headers
            const profileHeader = document.getElementById('profileNameLarge');
            const profileEmail = document.getElementById('profileEmailLarge');
            const profileImg = document.getElementById('profileImgLarge');

            if (profileHeader) {
                profileHeader.textContent = data.full_name || `${data.firstName || ''} ${data.lastName || ''}`.trim() || 'Faculty Account';
                profileHeader.classList.remove('italic');
            }
            if (profileEmail) profileEmail.textContent = data.email || 'No email provided';
            
            // Handle Profile Image with Initials Fallback
            if (profileImg) {
                if (data.profileImage) {
                    profileImg.src = data.profileImage;
                } else {
                    const initials = `${data.firstName?.[0] || data.full_name?.[0] || 'K'}${data.lastName?.[0] || ''}`;
                    profileImg.src = `https://ui-avatars.com/api/?name=${initials}&background=ea2628&color=fff&bold=true&size=150`;
                }
            }

            // Populate Form Fields
            const inputs = {
                'inFirstName': data.firstName,
                'inLastName': data.lastName,
                'inEmail': data.email,
                'inBio': data.bio
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
    <script type="module" src="../assets/js/firebase-init.js"></script>
</body>
</html>