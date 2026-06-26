import { auth, db } from '../assets/js/firebase-init.js';
import { onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
import { doc, getDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

// 1. Auth State Check
onAuthStateChanged(auth, async (user) => {
    if (user) {
        try {
            // Fetch User Data from students collection
            const docRef = doc(db, "students", user.uid);
            const docSnap = await getDoc(docRef);

            if (docSnap.exists()) {
                const data = docSnap.data();
                
                // Update Sidebar UI (Shared across all student pages)
                const sideName = document.getElementById('sideStudentName');
                const sideYear = document.getElementById('sideStudentYear');
                const popName = document.getElementById('popoverName');
                const popEmail = document.getElementById('popoverEmail');

                const initials = ((data.firstName?.[0] || '') + (data.lastName?.[0] || '')).toUpperCase() || 'ST';
                
                if (sideName) {
                    sideName.textContent = `${data.firstName} ${data.lastName}`;
                    sideName.classList.remove('italic');
                }
                const sideImg = document.getElementById('sideProfileImg');
                if (sideImg) sideImg.textContent = initials;

                if (popName) {
                    popName.textContent = `${data.firstName} ${data.lastName}`;
                }
                if (popEmail) {
                    popEmail.textContent = data.email || "student@university.edu";
                }
                if (sideYear) {
                    sideYear.textContent = data.studentId || "Student";
                }

                const dashImg = document.getElementById('dashStudentPhoto');
                if (dashImg) dashImg.textContent = initials;

                // Custom event for pages that need more data (like the dashboard)
                window.dispatchEvent(new CustomEvent('profileLoaded', { detail: { ...data, uid: user.uid } }));
                
            } else {
                console.warn("User authenticated but no student record found.");
                fetch('../api/logout.php').then(() => {
                    window.location.replace('../login.php?error=no_record');
                });
            }
        } catch (error) {
            console.error("Error fetching student profile:", error);
        }
    } else {
        // Not logged in, redirect to login
        fetch('../api/logout.php').then(() => {
            window.location.replace('../login.php?status=session_cleared');
        });
    }
});

// 2. Shared Logout Logic
document.addEventListener('DOMContentLoaded', () => {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                // Clear Frontend Session
                await signOut(auth);
                // Clear Backend Identity
                await fetch('../api/logout.php');
                // Ensure the explicit status is passed to break cache loops
                window.location.replace('../login.php?status=session_cleared');
            } catch (error) {
                console.error("Logout Error:", error);
                window.location.replace('../login.php?error=logout_failure');
            }
        });
    }
});
