<?php require_once 'core/init.php'; ?>
<!-- register.php -->
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('cs_theme');
                var theme = saved ||
                    (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
                document.documentElement.classList.toggle('dark', theme !== 'light');
                window.csThemeIsLight = theme === 'light';
            } catch (e) {
                document.documentElement.classList.add('dark');
                window.csThemeIsLight = false;
            }
        })();
    </script>
    <title>ClassSense | Create Account</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        @keyframes idShake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }
        .shake { animation: idShake 0.4s ease-in-out; }
    </style>
    <script>
        tailwind.config = {
            darkMode: 'class', theme: { extend: { colors: { primary: { DEFAULT: '#ea2628', 50: '#fef2f2', 100: '#fee2e2', 500: '#ea2628', 600: '#dc2626', 700: '#b91c1c', 900: '#7f1d1d' }, secondary: { 500: '#9d8989', 600: '#826a6a' }, dark: { bg: '#0f1115', surface: '#181b21', border: '#2a2e35' } }, fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] } } }
        }
    </script>
</head>
<body class="antialiased min-h-screen overflow-x-hidden selection:bg-primary-500 selection:text-white">
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
                    <img src="assets/classsense-logo.png" class="w-10 h-10 rounded-lg object-cover group-hover:scale-110 transition-transform">
                    <span class="text-2xl font-bold tracking-tight text-white">ClassSense</span>
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center py-8">
            <div class="w-full max-w-5xl grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center">
                
                <!-- Left Side: Register Card -->
                <div class="space-y-6 order-2 lg:order-1">
                    

                    <!-- Student Register Card -->
                    <div id="studentCard" class="relative animate-fade-in-up">
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary-600/30 to-blue-600/30 rounded-2xl blur-2xl opacity-30"></div>
                        <div class="relative glass-panel rounded-2xl overflow-hidden border border-white/10 shadow-2xl">
                            <div class="p-8 border-b border-white/5 bg-white/5">
                                <h2 class="text-3xl font-extrabold text-white mb-2">Join ClassSense</h2>
                                <p class="text-gray-400 text-sm font-medium">Empower your academic journey today.</p>
                            </div>
                            <form id="registerForm" class="p-8 space-y-6">
                                <input type="hidden" name="role" value="student">
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">First Name</label>
                                        <input type="text" name="fname" required class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all" placeholder="John">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Last Name</label>
                                        <input type="text" name="lname" required class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all" placeholder="Doe">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                                        <div class="relative">
                                            <i data-feather="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                                            <input type="text" name="username" required class="w-full bg-dark-bg border border-dark-border rounded-lg pl-9 pr-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all text-sm" placeholder="johndoe">
                                        </div>
                                    </div>
                                    <div>
                                        <label id="studentIdLabel" class="block text-sm font-medium text-gray-300 mb-2">Student ID</label>
                                        <div class="relative">
                                            <i data-feather="hash" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                                            <input type="text" name="user_id" id="studentIdInput" required class="w-full bg-dark-bg border border-dark-border rounded-lg pl-9 pr-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all text-sm" placeholder="20250001">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label id="phoneLabel" class="block text-sm font-medium text-gray-300 mb-2">Phone Number</label>
                                        <div class="relative">
                                            <i data-feather="phone" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                                            <input type="tel" name="phone" id="phoneInput" required class="w-full bg-dark-bg border border-dark-border rounded-lg pl-9 pr-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all text-sm" placeholder="09123456789">
                                        </div>
                                    </div>
                                    <div>
                                        <label id="guardianPhoneLabel" class="block text-sm font-medium text-gray-300 mb-2">Guardian Phone</label>
                                        <div class="relative">
                                            <i data-feather="users" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                                            <input type="tel" name="guardian_phone" id="guardianPhoneInput" required class="w-full bg-dark-bg border border-dark-border rounded-lg pl-9 pr-4 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all text-sm" placeholder="09123456789">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                                        <div class="relative">
                                            <i data-feather="lock" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                                            <input type="password" name="password" id="studentPass" required class="w-full bg-dark-bg border border-dark-border rounded-lg pl-9 pr-10 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all text-sm" placeholder="••••••••">
                                            <button type="button" onclick="togglePass('studentPass')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white">
                                                <i data-feather="eye" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Confirm Password</label>
                                        <div class="relative">
                                            <i data-feather="shield" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                                            <input type="password" name="confirm_password" id="confirmPass" required class="w-full bg-dark-bg border border-dark-border rounded-lg pl-9 pr-10 py-2.5 text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all text-sm" placeholder="••••••••">
                                            <button type="button" onclick="togglePass('confirmPass')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white">
                                                <i data-feather="eye" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Terms & Conditions -->
                                <div class="flex items-start gap-3 px-1">
                                    <div class="flex items-center h-5">
                                        <input id="terms" type="checkbox" required class="w-4 h-4 bg-dark-bg border border-dark-border rounded text-primary-500 focus:ring-primary-500 focus:ring-offset-dark-bg transition-all cursor-pointer">
                                    </div>
                                    <label for="terms" class="text-xs text-gray-400 leading-tight">
                                        I agree to the Terms of Service and Privacy Policy.
                                    </label>
                                </div>

                                <button type="submit" id="submitBtn" class="w-full py-4 px-4 bg-primary-600 hover:bg-primary-700 active:scale-[0.98] rounded-xl font-bold text-white transition-all shadow-[0_0_20px_rgba(234,38,40,0.3)] hover:shadow-[0_0_30px_rgba(234,38,40,0.5)] flex items-center justify-center gap-2 mt-4 text-base uppercase tracking-wider disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span id="btnText">Create Account</span>
                                    <div id="btnLoader" class="hidden w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                    <i data-feather="arrow-right" id="btnIcon" class="w-5 h-5"></i>
                                </button>
                                
                                <p class="text-center text-sm text-gray-500 font-medium">
                                    Already using ClassSense? <a href="#" onclick="window.location.replace('login.php'); return false;" class="text-white hover:text-primary-400 transition-colors font-bold">Sign In</a>
                                </p>
                            </form>
                        </div>
                    </div>


                </div>

                <div class="hidden lg:block order-1 lg:order-2">
                    <div class="mb-12">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-500/10 border border-primary-500/20 text-primary-400 text-xs font-bold uppercase tracking-widest mb-4">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                            </span>
                            v2.0 Beta Now Live
                        </div>
                        <h1 class="text-5xl font-black text-white mb-4 leading-none tracking-tight">The Future of <span class="text-primary-500">Learning</span>.</h1>
                        <p class="text-gray-400 text-xl leading-relaxed max-w-lg">Everything you need to succeed in your academic career, in one powerful, unified dashboard.</p>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="group relative">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-primary-500 to-blue-500 rounded-2xl blur opacity-0 group-hover:opacity-10 transition duration-500"></div>
                            <div class="relative glass-panel rounded-2xl p-6 flex items-start gap-5 hover:border-primary-500/30 transition-all cursor-default">
                                <div class="p-4 bg-primary-500/10 rounded-2xl text-primary-400 group-hover:scale-110 group-hover:bg-primary-500/20 transition-all"><i data-feather="shield" class="w-7 h-7"></i></div>
                                <div>
                                    <h3 class="font-bold text-xl text-white mb-2">Student-Centric Privacy</h3>
                                    <p class="text-gray-400 leading-relaxed">Your data remains your own. We use end-to-end encryption for all academic records and private communications.</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur opacity-0 group-hover:opacity-10 transition duration-500"></div>
                            <div class="relative glass-panel rounded-2xl p-6 flex items-start gap-5 hover:border-blue-500/30 transition-all cursor-default">
                                <div class="p-4 bg-blue-500/10 rounded-2xl text-blue-400 group-hover:scale-110 group-hover:bg-blue-500/20 transition-all"><i data-feather="zap" class="w-7 h-7"></i></div>
                                <div>
                                    <h3 class="font-bold text-xl text-white mb-2">Lightning Fast Registration</h3>
                                    <p class="text-gray-400 leading-relaxed">Join your university portal in under 60 seconds. Our streamlined onboarding gets you straight to your classes.</p>
                                </div>
                            </div>
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
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Auto remove
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 4000);
        }
    </script>

    <script type="module">
        // 🛡️ Note: auth_controller.js is included globally in includes/head.php
        import { api } from './assets/js/custom-auth.js';

        const registerForm = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnLoader = document.getElementById('btnLoader');
        const btnIcon = document.getElementById('btnIcon');
        const studentIdInput = document.getElementById('studentIdInput');
        const studentIdLabel = document.getElementById('studentIdLabel');
        const phoneInput = document.getElementById('phoneInput');
        const phoneLabel = document.getElementById('phoneLabel');
        const guardianPhoneInput = document.getElementById('guardianPhoneInput');
        const guardianPhoneLabel = document.getElementById('guardianPhoneLabel');

        const vibrateLabel = (label) => {
            if (!label) return;
            label.classList.remove('shake');
            void label.offsetWidth;
            label.classList.add('shake');
        };

        const constrainDigits = (input, label, max) => {
            const digits = input.value.replace(/\D/g, '').slice(0, max);
            if (digits !== input.value) {
                vibrateLabel(label);
                input.value = digits;
            }
        };

        if (studentIdInput) {
            studentIdInput.addEventListener('input', () => constrainDigits(studentIdInput, studentIdLabel, 8));
        }
        if (phoneInput) {
            phoneInput.addEventListener('input', () => constrainDigits(phoneInput, phoneLabel, 11));
        }
        if (guardianPhoneInput) {
            guardianPhoneInput.addEventListener('input', () => constrainDigits(guardianPhoneInput, guardianPhoneLabel, 11));
        }

        if(registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(registerForm);
                const data = Object.fromEntries(formData.entries());

                // Validation
                if (data.password !== data.confirm_password) return showStatus("Passwords do not match!", 'error');
                if (data.password.length < 6) return showStatus("Password must be at least 6 characters.", 'error');
                if (!/^\d{1,8}$/.test(data.user_id)) {
                    vibrateLabel(studentIdLabel);
                    return showStatus("Student ID must be 8 digits maximum.", 'error');
                }
                if (!/^\d{1,11}$/.test(data.phone)) {
                    vibrateLabel(phoneLabel);
                    return showStatus("Phone number must be 11 digits maximum.", 'error');
                }
                if (!/^\d{1,11}$/.test(data.guardian_phone)) {
                    vibrateLabel(guardianPhoneLabel);
                    return showStatus("Guardian phone must be 11 digits maximum.", 'error');
                }

                // Loading State
                submitBtn.disabled = true;
                btnText.textContent = 'Creating Account...';
                btnLoader.classList.remove('hidden');
                btnIcon.classList.add('hidden');

                try {
                    const profileData = {
                        firstName: data.fname,
                        lastName: data.lname,
                        username: data.username,
                        studentId: data.role === 'student' ? data.user_id : null,
                        role: data.role
                    };

                    const result = await api('/auth/register.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            username: data.username,
                            password: data.password,
                            role: data.role,
                            firstName: data.fname,
                            lastName: data.lname,
                            studentId: data.role === 'student' ? data.user_id : null
                        })
                    });

                    showStatus('Account created successfully! Redirecting to login...', 'success');
                    await new Promise(r => setTimeout(r, 1500));
                    window.location.href = 'login.php';
                } catch (error) {
                    console.error("Registration Error:", error);
                    showStatus(error.message, 'error');
                    submitBtn.disabled = false;
                    btnText.textContent = 'Create Account';
                    btnLoader.classList.add('hidden');
                    btnIcon.classList.remove('hidden');
                }
            });
        }
    </script>
    <script type="module" src="assets/js/auth_controller.js"></script>
</body>
</html>