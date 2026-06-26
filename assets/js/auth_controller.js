import { auth, db } from './firebase-init.js';
import { onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
import { doc, getDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

const CS_ROOT = window.CS_ROOT || '/ClassSense/'; // Managed by includes/head.php

// 0. Instant Cache Restoration (The Zero-Flash Handshake)
const cachedProfile = localStorage.getItem('cs_cached_profile');
if (cachedProfile) {
    const data = JSON.parse(cachedProfile);
    window.csProfile = data; // 🛡️ GLOBAL MEMORY for late-comers
    window.dispatchEvent(new CustomEvent('profileLoaded', { detail: data }));
    window.dispatchEvent(new CustomEvent('studentDataLoaded', { detail: data }));
}

let isAuthSyncing = false; 

// 🛡️ EXPORTED SYNC FUNCTION (Can be forced by login.php)
window.forceIdentityHandshake = async (user) => {
    isAuthSyncing = false; // Reset lock
    return performHandshake(user);
};

async function performHandshake(user) {
    console.log("!!! EMERGENCY HANDSHAKE START !!!", user.uid);
    if (isAuthSyncing) {
        console.warn("Handshake blocked: isAuthSyncing is already TRUE");
        return;
    }
    isAuthSyncing = true;
    
    // 🛡️ DEADMAN'S SWITCH: Prevent infinite "Loading Identity" hang
    const handshakeTimeout = setTimeout(() => {
        if (isAuthSyncing) {
            console.warn("Handshake Timed Out! Forcing basic identity...");
            const fallbackProfile = { uid: user.uid, email: user.email, role: 'teacher', firstName: 'Faculty', lastName: 'Account' };
            window.dispatchEvent(new CustomEvent('profileLoaded', { detail: fallbackProfile }));
            window.dispatchEvent(new CustomEvent('handshakeProgress', { detail: "Identity Restored (Fallback Mode)" }));
            isAuthSyncing = false;
        }
    }, 4500);

    const currentPath = window.location.pathname;
    const isPublicPage = currentPath.includes('login.php') || currentPath.includes('register.php') || currentPath.endsWith(CS_ROOT) || currentPath.includes('index.php');

    try {
        window.dispatchEvent(new CustomEvent('handshakeProgress', { detail: "Pinging Database..." }));
        console.log("Step 1: Pinging Firestore...");

        let role = 'guest';
        let profileData = null;

        // Try Students Individualy to isolate hangs
        try {
            console.log("Step 2: Checking Students collection...");
            const sDoc = await getDoc(doc(db, "students", user.uid));
            if (sDoc.exists()) {
                role = 'student';
                profileData = sDoc.data();
                console.log("Found in Students!");
            }
        } catch (e) { console.error("Students Fetch Failed:", e); }

        if (role === 'guest') {
            try {
                console.log("Step 3: Checking Teachers collection...");
                const tDoc = await getDoc(doc(db, "teachers", user.uid));
                if (tDoc.exists()) {
                    role = 'teacher';
                    profileData = tDoc.data();
                    console.log("Found in Teachers!");
                }
            } catch (e) { console.error("Teachers Fetch Failed:", e); }
        }

        if (role === 'guest' && user.email === "admin@classsense.com") {
            role = 'admin';
            console.log("Identified as ADMIN");
        }

        window.dispatchEvent(new CustomEvent('handshakeProgress', { detail: `Syncing ${role}...` }));

        // 🛡️ SECURITY PATCH: Ensure Profile Data is dispatched to listeners (Sidebar, Dashboard)
        const finalProfile = { uid: user.uid, email: user.email, role: role, ...profileData };
        window.csProfile = finalProfile; // 🛡️ GLOBAL MEMORY
        localStorage.setItem('cs_cached_profile', JSON.stringify(finalProfile));
        window.dispatchEvent(new CustomEvent('profileLoaded', { detail: finalProfile }));

        console.log("Step 4: Syncing with PHP Session...");
        const response = await fetch(CS_ROOT + 'api/sync_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ uid: user.uid, role: role })
        });
        
        const result = await response.json();
        console.log("Step 5: Handshake Complete. Redirecting...");

        if (isPublicPage && result.redirect) {
             window.location.replace(CS_ROOT + result.redirect);
        } else {
            isAuthSyncing = false;
            clearTimeout(handshakeTimeout);
        }
    } catch (error) {
        clearTimeout(handshakeTimeout);
        console.error("!!! CRITICAL HANDSHAKE FAILURE !!!", error);
        window.dispatchEvent(new CustomEvent('handshakeFailed', { detail: error.message }));
        isAuthSyncing = false;
    }
}

// 1. Unified Identity Orchestrator
onAuthStateChanged(auth, (user) => {
    console.log("Auth State Changed: ", user ? "User Authenticated" : "No User");
    if (user) {
        performHandshake(user);
    } else {
        const currentPath = window.location.pathname;
        const isPublicPage = currentPath.includes('login.php') || currentPath.includes('register.php') || currentPath.endsWith(CS_ROOT) || currentPath.includes('index.php');
        if (!isPublicPage) {
             localStorage.removeItem('cs_cached_profile');
             fetch(CS_ROOT + 'api/logout.php').then(() => {
                 window.location.href = CS_ROOT + 'login.php?error=identity_missing';
             });
        }
        isAuthSyncing = false;
    }
});

// 2. Global Professional Sign Out Handshake
window.logoutUser = async () => {
    try {
        localStorage.removeItem('cs_cached_profile');
        await signOut(auth);
        await fetch(CS_ROOT + 'api/logout.php'); 
        window.location.replace(CS_ROOT + 'login.php?status=session_terminated');
    } catch (err) {
        console.error("Identity Termination Failure:", err);
        window.location.replace(CS_ROOT + 'login.php?error=logout_failure');
    }
};
