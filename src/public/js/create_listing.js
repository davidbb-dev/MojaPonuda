const input = document.getElementById('file_input');
const preview = document.getElementById('images_preview');
// IMPORTANT: bind to the LISTING form specifically. document.querySelector('form')
// would grab the navbar search form (first form on the page) instead.
const form = input.closest('form');
const infoSpan = document.getElementById('custom_file_input_span');

// crypto.randomUUID() exists ONLY in a secure context (https or localhost).
// On http://<ip>:8000 it is undefined and throws — breaking image selection.
// This fallback works everywhere.
function uid() {
    if (window.crypto && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }
    return 'xxxxxxxxxxxx'.replace(/x/g, () => ((Math.random() * 16) | 0).toString(16))
        + '-' + Date.now().toString(16);
}

const MAX_FILES = 15;
let images = [];
let draggedIndex = null;

images = window.existingImages.map(img => ({
    uid: 'db_' + img.image_id,
    type: 'existing',
    id: Number(img.image_id),
    path: img.image_path
}));


document.addEventListener('DOMContentLoaded', () => {
    renderPreview();
});

let imagesForDeletionIds = [];

// VALIDATION OF FORM
function validateField(field) {
    const wrapper = field.closest(".form_group");
    if (!wrapper) return false;

    const errorEl = wrapper.querySelector(".error_message");

    const isFileInput = field.dataset.role === 'images-input';

    if (isFileInput) {
        if (images.length === 0) {
            errorEl.textContent = field.dataset.error || "Ovo polje je obavezno";
            field.classList.add('invalid');
            return false;
        }

        errorEl.textContent = "";
        field.classList.remove('invalid');
        return true;
    }

    if (!field.validity.valid) {
        errorEl.textContent = field.dataset.error || "Ovo polje je obavezno";
        field.classList.add('invalid');
        return false;
    }
    else {
        errorEl.textContent = "";
        field.classList.remove('invalid');
        return true;
    }
}

const fields = form.querySelectorAll('input, textarea, select');

fields.forEach(field => {
    field.addEventListener('input', () => validateField(field));

    field.addEventListener('change', () => validateField(field));

    field.addEventListener('blur', () => validateField(field));
});

function isFormValid() {
    let isValid = true;

    fields.forEach(field => {
        const fieldValid = validateField(field);
        if (!fieldValid) {
            isValid = false;
        }
    });

    return isValid;
}

// ON SUBMIT
form.addEventListener("submit", function (e) {
    // Always take over submission. We load the JS-managed images into the file
    // input via DataTransfer, then submit PROGRAMMATICALLY with form.submit().
    //
    // Why not a native submit: a FileList assigned through DataTransfer is NOT
    // reliably serialized during the browser's native (non-prevented) submit,
    // so the images never reached the server ("Morate selektovati bar jednu
    // sliku"). preventDefault() + form.submit() serializes input.files correctly.
    e.preventDefault();

    const isValid = isFormValid();

    if (!isValid) {
        const firstInvalid = form.querySelector(':invalid');
        if (firstInvalid) firstInvalid.focus();
        return;
    }

    const submitter = e.submitter;

    const hidden = document.createElement('input'); // action is sent as a hidden field (form.submit() ignores the button)
    hidden.type = 'hidden';
    hidden.name = 'action';
    hidden.value = submitter?.value ?? 'publish';
    form.appendChild(hidden);

    if (imagesForDeletionIds.length > 0) {
        const hidden2 = document.createElement('input');
        hidden2.type = 'hidden';
        hidden2.name = 'images_for_deletion';
        hidden2.value = JSON.stringify(imagesForDeletionIds);
        form.appendChild(hidden2);
    }

    const hiddenOrder = document.createElement('input');
    hiddenOrder.type = 'hidden';
    hiddenOrder.name = 'images_order';
    hiddenOrder.value = JSON.stringify(
        images.map((img, index) => ({
            uid: img.uid,
            id: img.id ?? null,
            type: img.type,
            position: index
        }))
    );
    form.appendChild(hiddenOrder);

    // Load the (possibly reordered / multi-selected) images into the file input.
    const dataTransfer = new DataTransfer();
    images.forEach((img) => {
        if (img.type !== 'existing') {
            dataTransfer.items.add(img.file);
        }
    });
    input.files = dataTransfer.files;

    // Loading feedback + prevent double submit.
    if (submitter) {
        submitter.disabled = true;
        submitter.textContent = 'Sačekajte...';
    }

    form.submit();
});

function updateFileInfo() {
    const remaining = MAX_FILES - images.length;

    if (remaining <= 0) {
        infoSpan.textContent = `Dosegli ste maksimalan broj slika (${MAX_FILES}).`;
    }
    else {
        infoSpan.textContent = `Mozete dodati jos ${remaining} slika (maksimalno ${MAX_FILES}).`;
    }
}

function showFileLimitError() {
    const wrapper = input.closest(".form_group");
    if (!wrapper) return;

    const errorEl = wrapper.querySelector(".error_message");

    const remaining = MAX_FILES - images.length;

    if (remaining > 0) {
        errorEl.textContent = `Mozete dodati jos maksimalno ${remaining} slika (ukupno ${MAX_FILES}).`;
    }
    else {
        errorEl.textContent = `Maksimalan broj slika je ${MAX_FILES}.`;
    }

    input.classList.add('invalid');
}

function clearFileLimitError() {
    const wrapper = input.closest(".form_group");
    if (!wrapper) return;

    const errorEl = wrapper.querySelector(".error_message");
    errorEl.textContent = '';
    input.classList.remove('invalid');
}

input.addEventListener('change', (e) => {
    const newImages = Array.from(e.target.files);

    const remaining = MAX_FILES - images.length;

    if (remaining <= 0) {
        showFileLimitError();
        input.value = '';
        return;
    }

    const filesToAdd = newImages.slice(0, remaining);

    filesToAdd.forEach(file => {
        images.push({
            uid: 'new_' + uid(),
            type: 'new',
            file: file
        });
    });

    input.value = '';

    if (images.length >= MAX_FILES) {
        showFileLimitError();
    }
    else {
        clearFileLimitError();
    }

    renderPreview();
    validateField(input);
    updateFileInfo();
});

function syncFilesWithDOM() {
    const uids = [...preview.querySelectorAll('.image_wrapper')].map(el => el.dataset.uid);

    const map = new Map(images.map(img => [img.uid, img]));

    images = uids.map(uid => map.get(uid)).filter(Boolean);
}


function renderPreview() {

    preview.innerHTML = '';

    // Adding already existing images from DB
    images.forEach((image) => {

        const wrapper = document.createElement('div');
        wrapper.classList.add('image_wrapper', 'draggable');

        // DRAG&DROP LOGIC
        wrapper.draggable = true;

        wrapper.dataset.uid = image.uid;

        wrapper.addEventListener('dragstart', () => {
            wrapper.classList.add('dragging');
        });

        wrapper.addEventListener('dragend', () => {
            wrapper.classList.remove('dragging');
            syncFilesWithDOM();
            updateFileInfo();
        });
        // END OF DRAG&DROP LOGIC

        // Image
        const img = document.createElement('img');
        img.classList.add('uploaded_image');

        if (image.type === 'existing') {
            img.src = image.path;
        }
        else {
            const url = URL.createObjectURL(image.file);
            img.src = url;
            img.onload = () => URL.revokeObjectURL(url);
        }

        // Overlay div
        const overlay = document.createElement('div');
        overlay.classList.add('image_overlay');

        // VIEW BUTTON
        const viewBtn = document.createElement('button');
        viewBtn.type = 'button';
        viewBtn.classList.add('image_button');

        const viewIcon = document.createElement('img');
        viewIcon.src = '/icons/view_black.svg';
        viewIcon.className = 'image_icon';

        viewBtn.appendChild(viewIcon);

        viewBtn.addEventListener('click', () => {
            const windowMessage = window.open();

            if (!windowMessage) return;

            windowMessage.document.title = 'Pregled';

            windowMessage.document.body.style.margin = '0';
            windowMessage.document.body.style.background = '#000';
            windowMessage.document.body.style.display = 'flex';
            windowMessage.document.body.style.justifyContent = 'center';
            windowMessage.document.body.style.alignItems = 'center';
            windowMessage.document.body.style.height = '100vh';

            const imgEl = windowMessage.document.createElement('img');
            imgEl.src = img.src;
            imgEl.style.maxWidth = '100%';
            imgEl.style.maxHeight = '100%';

            windowMessage.document.body.appendChild(imgEl);
        });

        // DELETE BUTTON
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.classList.add('image_button');

        const deleteIcon = document.createElement('img');
        deleteIcon.src = '/icons/delete_black.svg';
        deleteIcon.className = 'image_icon';

        deleteBtn.appendChild(deleteIcon);

        deleteBtn.addEventListener('click', () => {

            if (image.type === 'existing') {
                if (!imagesForDeletionIds.includes(image.id)) {
                    imagesForDeletionIds.push(image.id);
                }
            }

            images = images.filter(i => i.uid !== image.uid);

            renderPreview();
            clearFileLimitError();
            updateFileInfo();
            validateField(input);
        });

        // Adding buttons to overlay
        overlay.appendChild(viewBtn);
        overlay.appendChild(deleteBtn);

        // Adding to wrapper
        wrapper.appendChild(img);
        wrapper.appendChild(overlay);

        preview.appendChild(wrapper);
    });
}

// DRAG&DROP LOGIC
const imagesContainer = document.getElementById('images_preview');

imagesContainer.addEventListener('dragover', e => {
    e.preventDefault();

    const afterElement = getDragAfterElement(imagesContainer, e.clientX, e.clientY);
    const draggable = document.querySelector('.dragging');

    if (!draggable) return;

    if (afterElement == null) {
        imagesContainer.appendChild(draggable);
    }
    else {
        imagesContainer.insertBefore(draggable, afterElement);
    }
});

function getDragAfterElement(container, x, y) {
    const draggableElements = [...container.querySelectorAll('.draggable:not(.dragging)')];

    let closest = {
        element: null,
        distance: Number.POSITIVE_INFINITY
    };

    for (const el of draggableElements) {
        const box = el.getBoundingClientRect();

        const centerX = box.left + box.width / 2;
        const centerY = box.top + box.height / 2;

        const dx = x - centerX;
        const dy = y - centerY;

        const distance = Math.abs(dx) * 1.2 + Math.abs(dy) * 0.8;

        if (distance < closest.distance) {
            closest = {
                element: el,
                distance
            };
        }
    }

    const last = draggableElements[draggableElements.length - 1];

    if (last) {
        const lastBox = last.getBoundingClientRect();

        const isAfterLast = y > lastBox.bottom ||
            (Math.abs(y - lastBox.bottom) < 50 && x > lastBox.right);

        if (isAfterLast) {
            return null;
        }
    }

    return closest.element;
}
// END OF DRAG&DROP LOGIC

updateFileInfo();



// listing_form.php -> create_listing.js

const auctionDateTime = document.getElementById("auction_date_time");
const listingTypeRadios = document.querySelectorAll('input[name="listing_type"]');
const listingStartingPrice = document.getElementById("listing_starting_price");

listingTypeRadios.forEach(radio => {
    radio.addEventListener('change', () => {
        const selected = document.querySelector('input[name="listing_type"]:checked').value;

        if (selected === 'fixed_price') {
            auctionDateTime.style.display = 'none';
            listingStartingPrice.placeholder = "Unesite cenu predmeta";
        }
        else {
            auctionDateTime.style.display = 'block';
            listingStartingPrice.placeholder = "Unesite pocetnu cenu predmeta";
        }
    });
});