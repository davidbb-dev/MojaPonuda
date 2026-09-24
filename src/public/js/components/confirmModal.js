export function confirmModal({
    message,
    confirmText = "Yes",
    cancelText = "Cancel"
}){
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const text = document.getElementById('modalText');
        const yes = document.getElementById('confirmYes');
        const no = document.getElementById('confirmNo');

        text.textContent = message;
        yes.textContent = confirmText;
        no.textContent = cancelText;

        modal.classList.remove('hidden');

        const close = () => {
            modal.classList.add('hidden');
            yes.onclick = null;
            no.onclick = null;
        };

        yes.onclick = () => {
            close();
            resolve(true);
        };

        no.onclick = () => {
            close();
            resolve(false);
        };
    });
}