export function showMessage(text, type = 'info', duration = 3000) {
    const container = document.getElementById('message-container');
    if (!container) return;

    const msg = document.createElement('div');
    msg.className = `message ${type}`;

    const messageText = document.createElement('span');
    messageText.className = 'message-text';
    messageText.textContent = text;

    const closeBtn = document.createElement('span');
    closeBtn.className = 'close-btn';
    closeBtn.textContent = '×';

    msg.append(messageText, closeBtn);
    container.appendChild(msg);

    // Start the transition on the next animation frame.
    requestAnimationFrame(() => {
        msg.classList.add('show', 'progress');
        msg.style.setProperty('--duration', `${duration}ms`);
    });

    let timeoutId = setTimeout(closeMessage, duration);

    function closeMessage() {
        clearTimeout(timeoutId);
        msg.classList.remove('show');

        msg.addEventListener('transitionend', () => {
            msg.remove();
        });
    }

    // Manual close.
    closeBtn.addEventListener('click', closeMessage);
}