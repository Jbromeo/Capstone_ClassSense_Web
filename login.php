<!-- login.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <title>ClassSense | Sign In</title>
    <?php include 'includes/head.php'; ?>
</head>
<body class="antialiased min-h-screen overflow-x-hidden selection:bg-primary-500 selection:text-white bg-dark-bg">
    <!-- Ambient Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-[10%] left-[15%] w-[500px] h-[500px] bg-primary-600/20 rounded-full mix-blend-screen filter blur-[120px] animate-blob-slow"></div>
        <div class="absolute bottom-[10%] right-[15%] w-[400px] h-[400px] bg-blue-600/10 rounded-full mix-blend-screen filter blur-[100px] animate-blob-slow" style="animation-delay: 3s"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-purple-600/5 rounded-full mix-blend-screen filter blur-[150px] animate-blob-slow" style="animation-delay: 6s"></div>
    </div>

    <div class="container mx-auto px-4 min-h-screen flex flex-col">
        <!-- Header -->
        <header class="py-8">
            <div class="flex items-center justify-center md:justify-start">
                <a href="login.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-secondary-600 flex items-center justify-center shadow-lg shadow-primary-500/20 group-hover:scale-110 transition-transform">
                        <i data-feather="layers" class="w-5 h-5 text-white"></i>
                    </div>
                    <span class="text-2xl font-bold tracking-tight text-white">ClassSense</span>
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center py-8">
            <div class="w-full max-w-5xl grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center">
                
                <!-- Left Side: Login Card -->
                <div class="space-y-6 order-2 lg:order-1">
                    
                    <div id="loginCard" class="relative animate-fade-in-up">
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary-600/30 to-blue-600/30 rounded-2xl blur-2xl opacity-30"></div>
                        <div class="relative glass-panel rounded-2xl overflow-hidden border border-white/10 shadow-2xl">
                            <div class="p-8 border-b border-white/5 bg-white/5">
                                <h2 class="text-3xl font-extrabold text-white mb-2">Welcome Back</h2>
                                <p class="text-gray-400 text-sm font-medium">Continue your academic journey.</p>
                            </div>
                            
                        <form id="loginForm" class="p-8 space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Username or Student ID</label>
                                    <div class="relative">
                                        <i data-feather="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                                        <input type="text" name="login_identity" required class="w-full bg-dark-bg border border-dark-border rounded-xl pl-11 pr-4 py-3 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all placeholder:text-gray-600" placeholder="johndoe or ST-00000">
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="block text-sm font-medium text-gray-300">Password</label>
                                        <a href="#" onclick="showStatus('Please contact your school administrator to reset your password.', 'error'); return false;" class="text-xs text-primary-400 hover:text-primary-300 font-semibold">Forgot Password?</a>
                                    </div>
                                    <div class="relative">
                                        <i data-feather="lock" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                                        <input type="password" id="loginPassword" name="password" required class="w-full bg-dark-bg border border-dark-border rounded-xl pl-11 pr-11 py-3 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all placeholder:text-gray-600" placeholder="••••••••">
                                        <button type="button" onclick="togglePass('loginPassword')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white transition-colors">
                                            <i data-feather="eye" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </div>

                                <button type="submit" id="submitBtn" class="w-full py-4 px-4 bg-primary-600 hover:bg-primary-700 active:scale-[0.98] rounded-xl font-bold text-white transition-all shadow-[0_0_20px_rgba(234,38,40,0.3)] hover:shadow-[0_0_30px_rgba(234,38,40,0.5)] flex items-center justify-center gap-2 mt-2 text-base uppercase tracking-wider disabled:opacity-50">
                                    <span id="btnText">Sign In</span>
                                    <div id="btnLoader" class="hidden w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                    <i data-feather="arrow-right" id="btnIcon" class="w-5 h-5"></i>
                                </button>
                                
                                <p class="text-center text-sm text-gray-500 font-medium">
                                    New to ClassSense? <a href="#" onclick="window.location.replace('register.php'); return false;" class="text-white hover:text-primary-400 transition-colors font-bold">Create Account</a>
                                </p>
                            </form>
                        </div>
                    </div>

                </div>

                <!-- Right Side: Features -->
                <div class="hidden lg:block order-1 lg:order-2">
                    <div class="mb-12">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold uppercase tracking-widest mb-4">
                            Secure Access Gateway
                        </div>
                        <h1 class="text-5xl font-black text-white mb-4 leading-tight tracking-tight">The Hub for <span class="text-primary-500 text-glow">Educators</span> & <span class="text-blue-500">Students</span>.</h1>
                        <p class="text-gray-400 text-xl leading-relaxed max-w-lg">Access your academic resources, track attendance, and manage your grades in one seamless experience.</p>
                    </div>
                    <div class="space-y-4">
                         <div class="glass-panel rounded-2xl p-5 flex items-start gap-4 hover:border-primary-500/30 transition-all group">
                            <div class="p-3 bg-primary-500/10 rounded-xl text-primary-400"><i data-feather="grid" class="w-6 h-6"></i></div>
                            <div><h3 class="font-bold text-white mb-1">Unified Dashboard</h3><p class="text-sm text-gray-400">Centralized view for all your academic activities.</p></div>
                        </div>
                        <div class="glass-panel rounded-2xl p-5 flex items-start gap-4 hover:border-blue-500/30 transition-all group">
                            <div class="p-3 bg-blue-500/10 rounded-xl text-blue-400"><i data-feather="check-circle" class="w-6 h-6"></i></div>
                            <div><h3 class="font-bold text-white mb-1">Live Attendance</h3><p class="text-sm text-gray-400">Instant updates for classroom participation.</p></div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>

    <script>
        feather.replace();
        function togglePass(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        function showStatus(message, type = 'error') {
            const container = document.getElementById('toastContainer');
            if(!container) return;

            const toast = document.createElement('div');
            const isError = type === 'error';
            
            toast.className = `toast flex items-center w-full max-w-xs p-4 space-x-4 text-gray-200 bg-gray-800 rounded-lg shadow-lg border border-gray-700 ${isError ? 'border-l-4 border-l-primary-500' : 'border-l-4 border-l-green-500'}`;
            
            toast.innerHTML = `
                <div class="flex-shrink-0">
                    <i data-feather="${isError ? 'alert-circle' : 'check-circle'}" class="w-5 h-5 ${isError ? 'text-primary-500' : 'text-green-500'}"></i>
                </div>
                <div class="text-sm font-medium">${message}</div>
            `;
            
            container.appendChild(toast);
            feather.replace();
            
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 4000);
        }
    </script>

    <script type="module">
        import { auth, signInWithEmailAndPassword, onAuthStateChanged, customSignIn } from './assets/js/custom-auth.js';

        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnLoader = document.getElementById('btnLoader');
        const btnIcon = document.getElementById('btnIcon');

        if(loginForm) {
            console.log('[login] form found, adding submit handler');
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(loginForm);
                const username = formData.get('login_identity').trim();
                const password = formData.get('password');
                console.log('[login] submit: username=', username);

                submitBtn.disabled = true;
                btnText.textContent = 'Authenticating...';
                btnLoader.classList.remove('hidden');
                btnIcon.classList.add('hidden');

                const timeoutGuard = setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        btnText.textContent = 'Sign In';
                        btnLoader.classList.add('hidden');
                        btnIcon.classList.remove('hidden');
                        console.log('[login] TIMEOUT after 7s');
                        showStatus("Connection handshake timed out. Please try again.", 'error');
                    }
                }, 7000);

                try {
                    if (username === 'admin@gmail.com') {
                        console.log('[login] admin path');
                        const fullEmail = username.includes('@') ? username : `${username}@classsense.com`;
                        console.log('[login] calling signInWithEmailAndPassword');
                        const userCredential = await signInWithEmailAndPassword(auth, fullEmail, password);
                        const user = userCredential.user;
                        console.log('[login] Firebase login success, uid:', user.uid);
                        clearTimeout(timeoutGuard);
                        btnText.textContent = 'Syncing session...';
                        console.log('[login] getting idToken');
                        const idToken = await user.getIdToken();
                        console.log('[login] got idToken:', idToken.substring(0, 20) + '...');
                        const syncRes = await fetch('/ClassSense/api/sync_session.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ uid: user.uid, role: 'admin' })
                        });
                        const syncData = await syncRes.json();
                        console.log('[login] sync_session result:', syncData);
                        if (!syncRes.ok) throw new Error('Session sync failed');
                        if (syncData.token) {
                            sessionStorage.setItem('cs_token', syncData.token);
                            console.log('[login] cs_token stored for admin');
                        }
                        console.log('[login] sync_session ok, redirecting');
                        window.location.replace('/ClassSense/admin_screen/admin_dashboard.php');
                    } else {
                        console.log('[login] non-admin path');
                        const result = await customSignIn(username, password);
                        const role = result.role;
                        console.log('[login] customSignIn success, role:', role);
                        clearTimeout(timeoutGuard);
                        btnText.textContent = 'Syncing session...';
                        await fetch('/ClassSense/api/sync_session.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ uid: result.uid, role: result.role })
                        });
                        if (role === 'teacher') {
                            window.location.replace('/ClassSense/teacher_screen/teacher_dashboard.php');
                        } else if (role === 'student') {
                            window.location.replace('/ClassSense/student_screen/student_dashboard.php');
                        } else {
                            showStatus('Unknown role', 'error');
                        }
                    }
                } catch (error) {
                    clearTimeout(timeoutGuard);
                    console.error("[login] Error:", error);
                    
                    let msg = "Invalid credentials or connection lag";
                    if(error.message) msg = error.message;
                    
                    showStatus(msg, 'error');
                    
                    submitBtn.disabled = false;
                    btnText.textContent = 'Sign In';
                    btnLoader.classList.add('hidden');
                    btnIcon.classList.remove('hidden');
                }
            });

            window.addEventListener('handshakeProgress', (e) => {
                btnText.textContent = e.detail;
            });

            window.addEventListener('handshakeFailed', (e) => {
                showStatus(`Handshake Error: ${e.detail}`, 'error');
                submitBtn.disabled = false;
                btnText.textContent = 'Sign In';
                btnLoader.classList.add('hidden');
                btnIcon.classList.remove('hidden');
            });
        }
    </script>
</body>
</html>
