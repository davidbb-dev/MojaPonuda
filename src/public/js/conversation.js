// Keep the conversation scrolled to the newest message.
const box = document.getElementById('chat-messages');
if (box) {
    box.scrollTop = box.scrollHeight;
}

// Submit on Enter (Shift+Enter for a newline).
const textarea = document.querySelector('.chat-input textarea');
if (textarea) {
    textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            textarea.closest('form')?.requestSubmit();
        }
    });
}
