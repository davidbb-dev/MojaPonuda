// Site-wide behaviour for logged-in users: notification badge.
(function () {
    const badge = document.getElementById('notif-badge');
    if (!badge) return;

    async function refresh() {
        try {
            const res = await fetch('/notifications/unread-count', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            const count = Number(data.count || 0);
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.hidden = false;
            } else {
                badge.hidden = true;
            }
        } catch (_) { /* ignore */ }
    }

    refresh();
    setInterval(refresh, 30000); // poll every 30s
})();
