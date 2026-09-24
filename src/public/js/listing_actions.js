import { confirmModal } from "./components/confirmModal.js";
import { showMessage } from "./components/message.js";

const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

// ---------------- Favorite toggle ----------------
const favBtn = document.getElementById('btn-favorite');
if (favBtn) {
    favBtn.addEventListener('click', async () => {
        const listingId = Number(favBtn.dataset.listingId);
        try {
            const res = await fetch('/favorites/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify({ listing_id: listingId })
            });
            const data = await res.json();

            if (!data.success) {
                if (data.redirect) { window.location.href = data.redirect; return; }
                showMessage(data.message || 'Greška.', 'error');
                return;
            }

            favBtn.classList.toggle('is-fav', data.favorited);
            favBtn.textContent = data.favorited ? '❤️' : '🤍';
            showMessage(data.favorited ? 'Dodato u omiljeno.' : 'Uklonjeno iz omiljenih.', 'success');
        } catch (err) {
            console.error(err);
            showMessage('Greška na serveru.', 'error');
        }
    });
}

// ---------------- Buy now ----------------
const buyBtn = document.getElementById('btn-buy-now');
if (buyBtn) {
    buyBtn.addEventListener('click', async () => {
        const ok = await confirmModal({
            message: 'Potvrdite kupovinu ovog predmeta.',
            confirmText: 'Kupi',
            cancelText: 'Otkaži'
        });
        if (!ok) return;

        const listingId = Number(buyBtn.dataset.listingId);
        buyBtn.disabled = true;
        buyBtn.textContent = 'Obrada...';

        try {
            const res = await fetch('/buy-now', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify({ listing_id: listingId })
            });
            const data = await res.json();

            if (!data.success) {
                if (data.redirect) { window.location.href = data.redirect; return; }
                showMessage(data.message || 'Kupovina nije uspela.', 'error');
                buyBtn.disabled = false;
                buyBtn.textContent = '🛒 Kupi odmah';
                return;
            }

            showMessage('Kupovina uspešna! Preusmeravanje...', 'success');
            setTimeout(() => window.location.href = data.redirect || '/orders', 900);
        } catch (err) {
            console.error(err);
            showMessage('Greška na serveru.', 'error');
            buyBtn.disabled = false;
            buyBtn.textContent = '🛒 Kupi odmah';
        }
    });
}

// ---------------- Bid history ----------------
const historyBtn = document.getElementById('btn-bid-history');
const historyBox = document.getElementById('bid-history');
if (historyBtn && historyBox) {
    historyBtn.addEventListener('click', async () => {
        if (!historyBox.hidden) { historyBox.hidden = true; return; }

        const listingId = Number(historyBtn.dataset.listingId);
        try {
            const res = await fetch(`/listing/${listingId}/bids`);
            const data = await res.json();
            const bids = data.data || [];

            if (bids.length === 0) {
                historyBox.innerHTML = '<p class="muted">Još uvek nema ponuda.</p>';
            } else {
                historyBox.innerHTML = bids.map(b =>
                    `<div class="bid-row"><span>${escapeHtml(b.username)}</span><strong>${Number(b.price)} din</strong><span class="muted">${escapeHtml(b.bid_date)}</span></div>`
                ).join('');
            }
            historyBox.hidden = false;
        } catch (err) {
            console.error(err);
            showMessage('Ne mogu da učitam istoriju.', 'error');
        }
    });
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}
