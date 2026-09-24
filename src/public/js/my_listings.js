import { confirmModal } from "./components/confirmModal.js";
import { showMessage } from "./components/message.js";

const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

function syncButtonState(card) {
    if (!card) return;

    const btn = card.querySelector('.btn_pause_listing');
    const status = card.dataset.status;

    if (!btn) return;

    btn.textContent = status === 'active' ? 'Pauziraj' : 'Aktiviraj';
}

function updateUI(card, newStatus) {
    const badge = card.querySelector('.status-badge');
    const btn = card.querySelector('.btn_pause_listing');

    const config = {
        active: {
            label: '🟢 Aktivan',
            btn: 'Pauziraj'
        },
        paused: {
            label: '⏸️ Pauziran',
            btn: 'Aktiviraj'
        }
    };

    if (!config[newStatus]) return;

    card.dataset.status = newStatus;

    card.classList.remove('status-active', 'status-paused');
    card.classList.add(`status-${newStatus}`);

    if (badge) {
        badge.className = `status-badge status-${newStatus}`;
        badge.textContent = config[newStatus].label;
    }

    if (btn) {
        btn.textContent = config[newStatus].btn;
    }
}

document.querySelectorAll('.listing_container').forEach(syncButtonState);

const transitions = {
    active: {
        url: '/pause/listing',
        message: 'Da li želite da pauzirate oglas?',
        confirmText: 'Pauziraj',
        next: 'paused'
    },
    paused: {
        url: '/activate/listing',
        message: 'Da li želite da aktivirate oglas?',
        confirmText: 'Aktiviraj',
        next: 'active'
    },
    draft: {
        blocked: true,
        message: 'Draft oglasi ne mogu biti pauzirani dok se ne objave.',
        type: 'info'
    }
};

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn_pause_listing');
    if (!btn) return;

    const card = btn.closest('.listing_container');
    if (!card) return;

    const listingId = btn.dataset.listingId;
    const currentStatus = card.dataset.status || 'active';

    const transition = transitions[currentStatus];
    if (!transition) return;

    if (transition.blocked) {
        showMessage(transition.message, transition.type || 'info');
        return;
    }

    const ok = await confirmModal({
        message: transition.message,
        confirmText: transition.confirmText,
        cancelText: 'Otkaži'
    });

    if (!ok) return;

    const originalText = btn.textContent;

    btn.disabled = true;
    btn.textContent = 'Obrada...';

    try {
        const response = await fetch(transition.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrf
            },
            body: new URLSearchParams({
                listing_id: listingId
            })
        });

        const data = await response.json();

        if (!data.success) {
            showMessage(data.message || 'Greška pri promeni statusa', 'error');

            btn.disabled = false;
            btn.textContent = originalText;
            return;
        }

        updateUI(card, transition.next);
        showMessage('Status uspešno promenjen!', 'success');
    } catch (err) {
        console.error(err);
        showMessage('Greška na serveru', 'error');
    } finally {
        btn.disabled = false;
    }
});

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn_delete_listing');
    if (!btn) return;

    const card = btn.closest('.listing_container');
    if (!card) return;

    const listingId = btn.dataset.listingId;

    const ok = await confirmModal({
        message: 'Da li ste sigurni da želite da obrišete oglas? Ova akcija je nepovratna.',
        confirmText: 'Obriši',
        cancelText: 'Otkaži'
    });

    if (!ok) return;

    const originalText = btn.textContent;

    btn.disabled = true;
    btn.textContent = 'Brisanje...';

    try {
        const response = await fetch('/delete/listing', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrf
            },
            body: new URLSearchParams({
                listing_id: listingId
            })
        });

        const data = await response.json();

        if (!data.success) {
            showMessage(data.message || 'Greška pri brisanju', 'error');

            btn.disabled = false;
            btn.textContent = originalText;
            return;
        }

        card.remove();

        showMessage('Oglas je obrisan.', 'success');
    } catch (err) {
        console.error(err);
        showMessage('Greška na serveru', 'error');

        btn.disabled = false;
        btn.textContent = originalText;
    }
});