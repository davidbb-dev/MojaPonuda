import { confirmModal } from "./components/confirmModal.js";
import { showMessage } from "./components/message.js";

const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

const btnBid = document.querySelector('#btn-auction-bid');
const input = document.querySelector('#input-auction-bid');
const btnBidHistory = document.querySelector('#btn-bid-history');
const listingPrice = document.querySelector('.listing-price');

// Only wire bidding on auction pages where the bid button exists.
if (btnBid) btnBid.addEventListener('click', async () => {

    const ok = await confirmModal({
        message: 'Potvrdite licitaciju.',
        confirmText: 'Licitiraj',
        cancelText: 'Otkaži'
    });

    if (!ok) return;

    const listingId = Number(btnBid.dataset.listingId);
    const price = parseFloat(input.value);

    try {
        const response = await fetch('/place/bid', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf
            },
            body: JSON.stringify({
                listing_id: listingId,
                price: price
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {

            showMessage(data.message, 'error');
            console.error('Bid error:', data.message);

            if (data.redirect) {
                window.location.href = data.redirect;
            }

            return;
        }

        console.log('Bid success:', data.data);

        showMessage('Vaša licitacija je prihvaćena!', 'success');

        if (data.data?.new_price) {
            listingPrice.textContent = `${data.data.new_price} din`;
            btnBidHistory.textContent = `${data.data.bid_count} ponuda`;

            const nextMin = data.data.new_price + data.data.min_increment;

            input.value = '';
            input.placeholder = `Min: ${nextMin} din`;
        }
    }
    catch (err) {
        console.error('Network error:', err);
    }
});
