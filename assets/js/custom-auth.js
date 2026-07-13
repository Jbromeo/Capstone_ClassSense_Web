import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js";
import { getAuth, signInWithEmailAndPassword, signOut, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

const firebaseConfig = {
    apiKey: "AIzaSyA9rXCyXcOrKrIj4tssFh2weSJTlhiDjUU",
    authDomain: "class-sense-9def0.firebaseapp.com",
    projectId: "class-sense-9def0",
    storageBucket: "class-sense-9def0.firebasestorage.app",
    messagingSenderId: "537462109705",
    appId: "1:537462109705:web:1c156db52f7864a2cd2ad8"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
console.log('[custom-auth] loaded, auth.currentUser:', auth.currentUser);
const API_BASE = '/ClassSense/api';

async function api(url, options = {}) {
    console.log('[api] calling', url, 'method:', options.method || 'GET');
    let token = sessionStorage.getItem('cs_token');
    if (!token && auth.currentUser) {
        console.log('[api] no cs_token, using Firebase token');
        token = await auth.currentUser.getIdToken();
        console.log('[api] got Firebase token:', token ? token.substring(0, 20) + '...' : 'null');
    }
    console.log('[api] token present:', !!token);
    const res = await fetch(API_BASE + url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'Authorization': token ? `Bearer ${token}` : '',
            ...(options.headers || {})
        }
    });
    console.log('[api] response status:', res.status);
    const data = await res.json();
    console.log('[api] response data:', data);
    if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
    return data;
}

async function customSignIn(email, password) {
    console.log('[customSignIn] attempting login for', email);
    const data = await api('/auth/login.php', {
        method: 'POST',
        body: JSON.stringify({ username: email, password })
    });
    console.log('[customSignIn] success, role:', data.role);
    sessionStorage.setItem('cs_token', data.token);
    sessionStorage.setItem('cs_user', JSON.stringify(data.user));
    return data;
}

async function customSignOut() {
    console.log('[customSignOut] starting');
    try { await api('/auth/logout.php', { method: 'POST' }); } catch (e) { console.log('[customSignOut] auth/logout.php error:', e); }
    try { await api('/logout.php'); } catch (e) { console.log('[customSignOut] logout.php error:', e); }
    sessionStorage.removeItem('cs_token');
    sessionStorage.removeItem('cs_user');
    console.log('[customSignOut] done');
}

async function requireAuth() {
    const token = sessionStorage.getItem('cs_token');
    console.log('[requireAuth] cs_token:', !!token, 'auth.currentUser:', !!auth.currentUser);
    if (token) {
        const user = await api('/auth/verify.php');
        return user;
    }
    if (auth.currentUser) {
        return auth.currentUser;
    }
    throw new Error('Not authenticated');
}

async function initPage(loadFn) {
    const token = sessionStorage.getItem('cs_token');
    console.log('[initPage] token:', !!token, 'path:', window.location.pathname);
    if (token) {
        try {
            const user = await api('/auth/verify.php');
            console.log('[initPage] verified custom user:', user.email);
            if (loadFn) setTimeout(() => loadFn(user), 500);
            return user;
        } catch (e) {
            console.log('[initPage] verify failed:', e);
            sessionStorage.removeItem('cs_token');
            sessionStorage.removeItem('cs_user');
            window.location.href = '../login.php?status=session_expired';
            return null;
        }
    }
    console.log('[initPage] no token, waiting for onAuthStateChanged');
    onAuthStateChanged(auth, (user) => {
        console.log('[initPage] onAuthStateChanged fired, user:', !!user, user?.email);
        if (user) {
            if (user.email === 'admin@gmail.com') {
                console.log('[initPage] admin detected, calling loadFn');
                if (loadFn) setTimeout(() => loadFn(user), 500);
            } else {
                console.log('[initPage] non-admin Firebase user, redirecting');
                window.location.href = '../login.php?status=not_authorized';
            }
        } else {
            console.log('[initPage] no Firebase user, destroying PHP session then redirecting');
            fetch(API_BASE + '/logout.php').catch(() => {}).finally(() => {
                window.location.href = '../login.php?status=session_cleared';
            });
        }
    });
}

export { auth, signInWithEmailAndPassword, signOut, onAuthStateChanged, api, customSignIn, customSignOut, requireAuth, initPage, API_BASE };
