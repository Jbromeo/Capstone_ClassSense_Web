import { auth, onAuthStateChanged, signOut, api, customSignOut } from './custom-auth.js';

console.log('[auth_controller] loaded, auth.currentUser:', !!auth.currentUser, auth.currentUser?.email);

const CS_ROOT = window.CS_ROOT || '/ClassSense/';

let isAuthSyncing = false;

window.forceIdentityHandshake = async (user) => {
    console.log('[auth_controller] forceIdentityHandshake called for', user?.email);
    isAuthSyncing = false;
    return performHandshake(user);
};

async function performHandshake(user) {
    console.log('[auth_controller] performHandshake start, user:', user?.email, 'isAuthSyncing:', isAuthSyncing);
    if (isAuthSyncing) { console.log('[auth_controller] handshake already in progress, skipping'); return; }
    isAuthSyncing = true;

    const handshakeTimeout = setTimeout(() => {
        if (isAuthSyncing) {
            console.log('[auth_controller] handshake timeout (4.5s), using fallback');
            const fallbackProfile = { uid: user.uid, email: user.email, role: 'teacher', firstName: 'Faculty', lastName: 'Account' };
            window.dispatchEvent(new CustomEvent('profileLoaded', { detail: fallbackProfile }));
            isAuthSyncing = false;
        }
    }, 4500);

    const currentPath = window.location.pathname;
    const isPublicPage = currentPath.includes('login.php') || currentPath.includes('register.php') || currentPath.endsWith(CS_ROOT) || currentPath.includes('index.php');
    console.log('[auth_controller] isPublicPage:', isPublicPage, 'path:', currentPath);

    try {
        let role = 'guest';
        let profileData = null;

        if (user.email === "admin@gmail.com") {
            role = 'admin';
            console.log('[auth_controller] admin detected, upserting profile');
            try {
                await api('/fetch.php', {
                    method: 'POST',
                    body: JSON.stringify({ uid: user.uid, role: 'admin', username: user.email, firstName: 'Admin', lastName: 'Account' })
                });
                console.log('[auth_controller] admin profile upserted');
            } catch (e) { console.error("[auth_controller] Admin profile create failed:", e); }
        }

        if (role === 'guest') {
            console.log('[auth_controller] non-admin, fetching profile');
            try {
                const profile = await api('/fetch.php?uid=' + user.uid);
                console.log('[auth_controller] profile fetched:', profile);
                if (profile.role) {
                    role = profile.role;
                    profileData = profile;
                }
            } catch (e) { console.error("[auth_controller] Profile fetch failed:", e); }
        }

        const finalProfile = { uid: user.uid, email: user.email, role: role, ...profileData };
        console.log('[auth_controller] finalProfile:', finalProfile);
        window.csProfile = finalProfile;
        localStorage.setItem('cs_cached_profile', JSON.stringify(finalProfile));
        window.dispatchEvent(new CustomEvent('profileLoaded', { detail: finalProfile }));

        console.log('[auth_controller] calling sync_session.php');
        const response = await fetch(CS_ROOT + 'api/sync_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ uid: user.uid, role: role })
        });
        const result = await response.json();
        console.log('[auth_controller] sync_session result:', result);

        if (isPublicPage && result.redirect) {
            console.log('[auth_controller] redirecting to:', CS_ROOT + result.redirect);
            window.location.replace(CS_ROOT + result.redirect);
        }
    } catch (error) {
        console.error("[auth_controller] Handshake Failure:", error);
        window.dispatchEvent(new CustomEvent('handshakeFailed', { detail: error.message }));
    } finally {
        isAuthSyncing = false;
        clearTimeout(handshakeTimeout);
        console.log('[auth_controller] performHandshake complete');
    }
}

onAuthStateChanged(auth, (user) => {
    console.log('[auth_controller] onAuthStateChanged fired, user:', !!user, user?.email);
    if (user) {
        performHandshake(user);
    } else {
        const token = sessionStorage.getItem('cs_token');
        console.log('[auth_controller] onAuthStateChanged null, cs_token:', !!token);
        if (token) {
            // Custom auth user: verify token and fire profileLoaded
            console.log('[auth_controller] custom token found, verifying...');
            api('/auth/verify.php').then(userData => {
                console.log('[auth_controller] custom token verified, dispatching profileLoaded', userData);
                const profile = {
                    uid: userData.uid,
                    email: userData.email,
                    role: userData.role,
                    firstName: userData.firstName || '',
                    lastName: userData.lastName || '',
                    studentId: userData.studentId || '',
                };
                window.csProfile = profile;
                localStorage.setItem('cs_cached_profile', JSON.stringify(profile));
                window.dispatchEvent(new CustomEvent('profileLoaded', { detail: profile }));
            }).catch(err => {
                console.error('[auth_controller] custom token verify failed:', err);
                sessionStorage.removeItem('cs_token');
                sessionStorage.removeItem('cs_user');
                window.location.href = CS_ROOT + 'login.php?status=session_expired';
            });
            return;
        }
        const currentPath = window.location.pathname;
        const isPublicPage = currentPath.includes('login.php') || currentPath.includes('register.php') || currentPath.endsWith(CS_ROOT) || currentPath.includes('index.php');
        console.log('[auth_controller] no user, isPublicPage:', isPublicPage);
        if (!isPublicPage) {
            localStorage.removeItem('cs_cached_profile');
            // Destroy the PHP session before redirecting so init.php can't pull us back
            console.log('[auth_controller] destroying PHP session before redirect');
            fetch(CS_ROOT + 'api/logout.php').catch(() => {}).finally(() => {
                console.log('[auth_controller] redirecting to login (identity_missing)');
                window.location.href = CS_ROOT + 'login.php?error=identity_missing';
            });
        }
        isAuthSyncing = false;
    }
});

window.logoutUser = async () => {
    console.log('[auth_controller] logoutUser called');
    try {
        localStorage.removeItem('cs_cached_profile');
        console.log('[auth_controller] calling customSignOut');
        await customSignOut();
        console.log('[auth_controller] customSignOut done, calling signOut(auth)');
        try { await signOut(auth); console.log('[auth_controller] signOut(auth) done'); } catch (e) { console.log('[auth_controller] signOut(auth) error:', e); }
        console.log('[auth_controller] redirecting to login');
        window.location.replace(CS_ROOT + 'login.php?status=session_terminated');
    } catch (err) {
        console.error("[auth_controller] Logout Failure:", err);
        window.location.replace(CS_ROOT + 'login.php?error=logout_failure');
    }
};
