<!-- Notification Popover (shared component) -->
<div id="notificationPanel" class="hidden absolute top-full right-0 mt-3 w-80 md:w-96 glass-panel rounded-2xl border border-white/10 shadow-2xl z-50 overflow-hidden" style="max-height: 480px;">
    <div class="p-4 border-b border-dark-border flex items-center justify-between">
        <h4 class="text-xs font-black text-white uppercase tracking-widest">Notifications</h4>
        <div class="flex items-center gap-2">
            <span id="notifUnreadBadge" class="hidden px-2 py-0.5 bg-primary-500/20 text-primary-400 text-[9px] font-black rounded-full"></span>
            <button id="markAllReadBtn" class="hidden text-[9px] text-blue-500 hover:text-blue-400 font-black uppercase tracking-wider">Mark All Read</button>
        </div>
    </div>
    <div id="notifList" class="overflow-y-auto" style="max-height: 360px;">
        <div class="p-8 text-center">
            <div class="animate-pulse space-y-3">
                <div class="w-8 h-8 bg-white/5 rounded-full mx-auto"></div>
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest italic">Loading...</p>
            </div>
        </div>
    </div>
</div>

<style>
    .notif-item { transition: background 0.2s; }
    .notif-item.unread { background: rgba(59, 130, 246, 0.05); border-left: 3px solid #3b82f6; }
    .notif-item:hover { background: rgba(255, 255, 255, 0.05); }
</style>
<script>
(function() {
    const panel = document.getElementById('notificationPanel');
    const notifList = document.getElementById('notifList');
    const unreadBadge = document.getElementById('notifUnreadBadge');
    const markAllBtn = document.getElementById('markAllReadBtn');
    const notifyBtn = document.getElementById('headerNotifyBtn');

    const NOTIF_API = (window.CS_ROOT || '/ClassSense/') + 'api/notifications.php';
    const icons = {
        enrollment: { icon: 'user-plus', color: 'text-blue-500', bg: 'bg-blue-500/10' },
        attendance: { icon: 'check-circle', color: 'text-green-500', bg: 'bg-green-500/10' },
        grade: { icon: 'bar-chart-2', color: 'text-yellow-500', bg: 'bg-yellow-500/10' },
        alert: { icon: 'alert-triangle', color: 'text-primary-500', bg: 'bg-primary-500/10' },
    };

    function getIconConfig(type) { return icons[type] || { icon: 'bell', color: 'text-gray-400', bg: 'bg-white/5' }; }

    function timeAgo(dateStr) {
        const now = new Date();
        const date = new Date(dateStr);
        const sec = Math.floor((now - date) / 1000);
        if (sec < 60) return 'just now';
        const min = Math.floor(sec / 60);
        if (min < 60) return min + 'm ago';
        const hr = Math.floor(min / 60);
        if (hr < 24) return hr + 'h ago';
        const day = Math.floor(hr / 24);
        return day + 'd ago';
    }

    function renderNotifications(notifications) {
        if (!notifications.length) {
            notifList.innerHTML = '<div class=\"p-8 text-center\"><div class=\"w-10 h-10 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-3\"><i data-feather=\"bell-off\" class=\"w-5 h-5 text-gray-600\"></i></div><p class=\"text-[11px] text-gray-500 font-medium\">No notifications yet</p></div>';
            feather.replace();
            return;
        }
        notifList.innerHTML = notifications.map(n => {
            const cfg = getIconConfig(n.type);
            return '<div class=\"notif-item ' + (n.isRead ? '' : 'unread') + ' px-4 py-3 border-b border-white/5 cursor-pointer\" data-id=\"' + n.id + '\">' +
                '<div class=\"flex gap-3\">' +
                '<div class=\"flex-shrink-0 w-8 h-8 ' + cfg.bg + ' rounded-full flex items-center justify-center mt-0.5\">' +
                '<i data-feather=\"' + cfg.icon + '\" class=\"w-4 h-4 ' + cfg.color + '\"></i></div>' +
                '<div class=\"min-w-0 flex-1\">' +
                '<p class=\"text-xs font-bold text-white truncate\">' + n.title + '</p>' +
                '<p class=\"text-[11px] text-gray-400 mt-0.5 line-clamp-2\">' + (n.message || '') + '</p>' +
                '<p class=\"text-[9px] text-gray-600 font-bold mt-1 uppercase tracking-wider\">' + timeAgo(n.createdAt) + '</p>' +
                '</div></div></div>';
        }).join('');
        feather.replace();

        notifList.querySelectorAll('.notif-item').forEach(el => {
            el.addEventListener('click', async () => {
                const id = el.dataset.id;
                if (el.classList.contains('unread')) {
                    el.classList.remove('unread');
                    try {
                        await fetch(NOTIF_API, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: parseInt(id) })
                        });
                    } catch (e) {}
                }
            });
        });
    }

    async function loadNotifications() {
        try {
            const res = await fetch(NOTIF_API + '?limit=15');
            const data = await res.json();
            if (!res.ok) throw new Error(data.error);
            renderNotifications(data.notifications || []);
            updateBadge(data.unreadCount || 0);
        } catch (e) {
            notifList.innerHTML = '<div class=\"p-8 text-center\"><p class=\"text-[11px] text-gray-500\">Failed to load</p></div>';
        }
    }

    function updateBadge(count) {
        if (count > 0) {
            unreadBadge.textContent = count > 99 ? '99+' : count;
            unreadBadge.classList.remove('hidden');
            markAllBtn.classList.remove('hidden');
            const dot = notifyBtn ? notifyBtn.querySelector('.notif-dot') : null;
            if (dot) dot.classList.remove('hidden');
        } else {
            unreadBadge.classList.add('hidden');
            markAllBtn.classList.add('hidden');
            const dot = notifyBtn ? notifyBtn.querySelector('.notif-dot') : null;
            if (dot) dot.classList.add('hidden');
        }
    }

    if (notifyBtn) {
        notifyBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = panel.classList.contains('hidden');
            panel.classList.toggle('hidden');
            if (isHidden) loadNotifications();
        });
        document.addEventListener('click', (e) => {
            if (!panel.contains(e.target) && !notifyBtn.contains(e.target)) {
                panel.classList.add('hidden');
            }
        });
    }

    if (markAllBtn) {
        markAllBtn.addEventListener('click', async () => {
            try {
                await fetch(NOTIF_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ markAll: true })
                });
                document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
                updateBadge(0);
            } catch (e) {}
        });
    }

    (async function initUnread() {
        try {
            const res = await fetch(NOTIF_API + '?unread=1&limit=1');
            const data = await res.json();
            if (res.ok) updateBadge(data.unreadCount || 0);
        } catch (e) {}
    })();
})();
</script>
