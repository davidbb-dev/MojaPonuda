function formatTime(diffSeconds){
    const days = Math.floor(diffSeconds / 86400);
    const hours = Math.floor((diffSeconds % 86400) / 3600);
    const minutes = Math.floor((diffSeconds % 3600) / 60);
    const seconds = diffSeconds % 60;

    if(days > 0) return `${days}d ${hours}h`;
    if(hours > 0) return `${hours}h ${minutes}m`;
    if(minutes > 0) return `${minutes}m ${seconds}s`;

    return `${seconds}s`;
}

const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

document.querySelectorAll('.time-left').forEach(el => {
    const endedAt = new Date(el.dataset.endedAt.replace(' ', 'T') + 'Z');

    let interval = null;
    let expired = false;

    function updateCountdown(){
        const diff = Math.floor((endedAt - new Date()) / 1000);

        if(
            expired ||
            el.dataset.status !== 'active' ||
            diff <= 0
        ){
            el.textContent = '⏳ Isteklo';
            el.classList.remove('warning');
            el.classList.add('critical');

            if(!expired && el.dataset.expired !== '1'){
                expired = true;
                el.dataset.expired = '1';

                const row = el.closest('[data-id]');
                const listingId = row?.dataset.id;

                if(listingId){
                    markExpired(listingId, el);
                }
            }

            clearInterval(interval);
            return;
        }

        el.textContent = `⏳ ${formatTime(diff)}`;

        el.classList.remove('warning', 'critical');

        if(diff < 3600){
            el.classList.add('critical');
        }
        else if(diff < 86400){
            el.classList.add('warning');
        }
    }

    updateCountdown();
    interval = setInterval(updateCountdown, 1000);
});

function markExpired(listingId, el){
    fetch('/auction/expire', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrf
        },
        body: JSON.stringify({
            listing_id: listingId
        })
    })
    .then(res => res.json())
    .then(data => {
        if(!data.success) return;

        el.dataset.status = 'expired';

        const row = document.querySelector(`[data-id="${listingId}"]`);
        const endedAtCell = row?.querySelector('.td_ended_at_actual');

        if(endedAtCell){
            const now = new Date();

            endedAtCell.textContent =
                now.getFullYear() + '-' +
                String(now.getMonth() + 1).padStart(2, '0') + '-' +
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0');
        }
    })
    .catch(console.error);
}
