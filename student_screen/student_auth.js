function autoFitText(el, maxSize) {
    if (!el) return;
    el.style.fontSize = maxSize + 'rem';
    el.style.whiteSpace = 'nowrap';
    let size = maxSize;
    while (el.scrollWidth > el.clientWidth && size > 0.4) {
        size -= 0.1;
        el.style.fontSize = size + 'rem';
    }
}

function autoFitInitials(el) {
    if (!el) return;
    el.style.fontSize = '1.5rem';
    let size = 1.5;
    while (el.scrollWidth > el.clientWidth && size > 0.5) {
        size -= 0.1;
        el.style.fontSize = size + 'rem';
    }
}

window.addEventListener('profileLoaded', (e) => {
    const data = e.detail;

    const initials = ((data.firstName?.[0] || '') + (data.lastName?.[0] || '')).toUpperCase() || 'ST';

    const sideName = document.getElementById('sideStudentName');
    const sideYear = document.getElementById('sideStudentYear');
    const popName = document.getElementById('popoverName');
    const popEmail = document.getElementById('popoverEmail');

    if (sideName) {
        sideName.textContent = `${data.firstName || ''} ${data.lastName || ''}`.trim() || 'Student';
        sideName.classList.remove('italic');
        autoFitText(sideName, 0.6875);
    }
    if (popName) {
        popName.textContent = `${data.firstName || ''} ${data.lastName || ''}`.trim() || 'Student';
    }
    if (popEmail) {
        popEmail.textContent = data.email || '';
    }
    if (sideYear) {
        sideYear.textContent = data.studentId || 'Student';
    }

    const sideImg = document.getElementById('sideProfileImg');
    if (sideImg) {
        sideImg.textContent = initials;
        autoFitInitials(sideImg);
    }

    const dashImg = document.getElementById('dashStudentPhoto');
    if (dashImg) {
        dashImg.textContent = initials;
        autoFitInitials(dashImg);
    }

    const dashName = document.getElementById('dashStudentName');
    if (dashName) {
        autoFitText(dashName, 1.25);
    }
});

(function() {
    const cached = localStorage.getItem('cs_cached_profile');
    if (cached) {
        try {
            const data = JSON.parse(cached);
            const sideName = document.getElementById('sideStudentName');
            if (sideName) {
                sideName.textContent = `${data.firstName || ''} ${data.lastName || ''}`.trim() || 'Student';
                sideName.classList.remove('italic');
                autoFitText(sideName, 0.6875);
            }
            const initials = ((data.firstName?.[0] || '') + (data.lastName?.[0] || '')).toUpperCase() || 'ST';
            const sideImg = document.getElementById('sideProfileImg');
            if (sideImg) { sideImg.textContent = initials; autoFitInitials(sideImg); }
            const dashImg = document.getElementById('dashStudentPhoto');
            if (dashImg) { dashImg.textContent = initials; autoFitInitials(dashImg); }
        } catch (e) {}
    }
})();
