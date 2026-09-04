// Proactive AI-insight queue drain. XAMPP has no cron, so the student/teacher
// screens poll this lightweight endpoint; generation happens only after the
// 5-minute debounce window since the last grade/attendance change.
import { api } from './custom-auth.js';

let started = false;

export function startInsightPoller(intervalMs = 60000) {
    if (started) return;
    started = true;
    setTimeout(poll, 15000);
    setInterval(poll, intervalMs);
}

async function poll() {
    try {
        const r = await api('/insight_worker.php');
        if (r && r.due > 0 && r.processed > 0) {
            console.log(`[insight-poller] regenerated ${r.processed} insight(s), ${r.remaining} pending`);
        }
    } catch (e) {
        console.debug('[insight-poller] poll failed:', e.message);
    }
}