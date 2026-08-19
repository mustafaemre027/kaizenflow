export function initializeEvidencePicker(container) {
    const input = container.querySelector('.picker-input');
    const previewArea = container.querySelector('.picker-preview-area');
    const counter = container.querySelector('.picker-counter');
    const errorRegion = container.querySelector('.picker-error-region');

    if (!input || !previewArea || !counter || !errorRegion) {
        return;
    }

    const maxFiles = parseInt(container.getAttribute('data-max-files') || '8', 10);
    const maxKb = parseInt(container.getAttribute('data-max-kb') || '8192', 10);
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    // Internal state
    let selectedFiles = [];

    // On page load (or back navigation from validation failure), the browser might have preserved input.files
    // We should sync our state with it.
    if (input.files && input.files.length > 0) {
        Array.from(input.files).forEach(f => selectedFiles.push(f));
        render();
    }

    input.addEventListener('change', (e) => {
        errorRegion.textContent = ''; // clear previous errors

        const newFiles = Array.from(e.target.files);
        if (newFiles.length === 0) return;

        // Duplicate guard: check against name+size+lastModified
        const uniqueNewFiles = newFiles.filter(newFile => {
            return !selectedFiles.some(existingFile =>
                existingFile.name === newFile.name &&
                existingFile.size === newFile.size &&
                existingFile.lastModified === newFile.lastModified
            );
        });

        if (uniqueNewFiles.length < newFiles.length) {
            // Some duplicates were skipped silently
        }

        let validFiles = [];
        let errorMessages = [];

        uniqueNewFiles.forEach(file => {
            if (!allowedTypes.includes(file.type)) {
                errorMessages.push(`"${file.name}" desteklenmiyor. Yalnızca JPEG, PNG veya WEBP.`);
                return;
            }

            const fileSizeKb = file.size / 1024;
            if (fileSizeKb > maxKb) {
                errorMessages.push(`"${file.name}" boyutu çok büyük (Maksimum ${Math.round(maxKb / 1024)}MB).`);
                return;
            }

            validFiles.push(file);
        });

        // Check total limit (including effective existing)
        const effectiveCount = getEffectiveCount();
        if (effectiveCount + validFiles.length > maxFiles) {
            errorMessages.push(`Bu alana en fazla ${maxFiles} fotoğraf ekleyebilirsiniz. (Mevcut/Yeni toplamı sınırı aştı)`);
            validFiles = [];
        }

        if (errorMessages.length > 0) {
            errorRegion.innerHTML = errorMessages.join('<br>');
        }

        if (validFiles.length > 0) {
            selectedFiles = [...selectedFiles, ...validFiles];
        }

        syncInputAndRender();
    });

    // Listen for custom event from kaizen-evidence-editor.js
    document.addEventListener('kaizen:evidence-removed-toggled', (e) => {
        const context = container.getAttribute('data-context');
        if (e.detail && e.detail.context === context) {
            syncInputAndRender();
        }
    });

    function getEffectiveCount() {
        const context = container.getAttribute('data-context');
        let existingCount = 0;
        let removedCount = 0;

        if (context) {
            const gallery = document.querySelector(`.kf-edit-gallery[data-context="${context}"]`);
            if (gallery) {
                existingCount = parseInt(gallery.getAttribute('data-existing-count') || '0', 10);
                removedCount = gallery.querySelectorAll('.kf-edit-gallery-item.is-removed').length;
            }
        }

        return existingCount - removedCount + selectedFiles.length;
    }

    function syncInputAndRender() {
        // Sync to actual input
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        input.files = dt.files;

        render();
    }

    function render() {
        // Clear preview area
        previewArea.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            const objectUrl = URL.createObjectURL(file);

            const item = document.createElement('div');
            item.className = 'picker-item position-relative border rounded overflow-hidden d-flex flex-column align-items-center justify-content-center bg-white';
            item.style.width = '100px';
            item.style.height = '100px';

            const img = document.createElement('img');
            img.src = objectUrl;
            img.alt = `Seçilen fotoğraf: ${file.name}`;
            img.className = 'w-100 h-100 object-fit-cover';
            img.onload = () => URL.revokeObjectURL(objectUrl);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0 d-flex align-items-center justify-content-center';
            removeBtn.style.width = '24px';
            removeBtn.style.height = '24px';
            removeBtn.style.borderRadius = '50%';
            removeBtn.style.opacity = '0.9';
            removeBtn.setAttribute('aria-label', `"${file.name}" fotoğrafını kaldır`);
            removeBtn.innerHTML = '&times;';
            removeBtn.onclick = () => {
                selectedFiles.splice(index, 1);
                syncInputAndRender();
            };

            item.appendChild(img);
            item.appendChild(removeBtn);
            previewArea.appendChild(item);
        });

        const effectiveCount = getEffectiveCount();
        counter.textContent = `${effectiveCount} / ${maxFiles} fotoğraf`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-evidence-picker]').forEach(container => {
        initializeEvidencePicker(container);
    });
});
