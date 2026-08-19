export function initializeEvidenceLightbox() {
    let dialog = document.getElementById('kf-evidence-lightbox');

    if (!dialog) {
        // Create the native dialog element
        dialog = document.createElement('dialog');
        dialog.id = 'kf-evidence-lightbox';
        dialog.className = 'kf-lightbox-dialog';
        dialog.setAttribute('aria-labelledby', 'kf-lightbox-title');

        dialog.innerHTML = `
            <div class="kf-lightbox-header">
                <div class="kf-lightbox-counter" id="kf-lightbox-counter" aria-live="polite"></div>
                <button type="button" class="kf-lightbox-close btn-close btn-close-white" aria-label="Kapat" id="kf-lightbox-close"></button>
            </div>
            <div class="kf-lightbox-body">
                <button type="button" class="kf-lightbox-nav kf-lightbox-prev" id="kf-lightbox-prev" aria-label="Önceki fotoğraf">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/></svg>
                </button>
                <div class="kf-lightbox-img-container">
                    <img id="kf-lightbox-img" src="" alt="Büyük Fotoğraf" class="kf-lightbox-img">
                </div>
                <button type="button" class="kf-lightbox-nav kf-lightbox-next" id="kf-lightbox-next" aria-label="Sonraki fotoğraf">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
                </button>
            </div>
            <div class="kf-lightbox-footer">
                <a href="#" id="kf-lightbox-download" class="kf-btn kf-btn-primary" download aria-label="Fotoğrafı indir">İndir</a>
            </div>
        `;
        document.body.appendChild(dialog);
    }

    const imgEl = dialog.querySelector('#kf-lightbox-img');
    const prevBtn = dialog.querySelector('#kf-lightbox-prev');
    const nextBtn = dialog.querySelector('#kf-lightbox-next');
    const closeBtn = dialog.querySelector('#kf-lightbox-close');
    const counterEl = dialog.querySelector('#kf-lightbox-counter');
    const downloadBtn = dialog.querySelector('#kf-lightbox-download');

    let currentContextItems = [];
    let currentIndex = 0;
    let triggerElement = null;

    function openLightbox(items, index, trigger) {
        currentContextItems = items;
        currentIndex = index;
        triggerElement = trigger;

        updateLightboxContent();
        dialog.showModal();
        dialog.focus(); // Basic focus management
    }

    function closeLightbox() {
        dialog.close();
        if (triggerElement) {
            triggerElement.focus();
        }
    }

    function updateLightboxContent() {
        const item = currentContextItems[currentIndex];

        imgEl.src = item.viewUrl;
        imgEl.alt = item.altText;
        downloadBtn.href = item.downloadUrl;

        counterEl.textContent = `${currentIndex + 1} / ${currentContextItems.length}`;

        prevBtn.style.visibility = currentContextItems.length > 1 ? 'visible' : 'hidden';
        nextBtn.style.visibility = currentContextItems.length > 1 ? 'visible' : 'hidden';

        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex === currentContextItems.length - 1;
    }

    function goPrev() {
        if (currentIndex > 0) {
            currentIndex--;
            updateLightboxContent();
        }
    }

    function goNext() {
        if (currentIndex < currentContextItems.length - 1) {
            currentIndex++;
            updateLightboxContent();
        }
    }

    // Event Listeners
    closeBtn.addEventListener('click', closeLightbox);
    prevBtn.addEventListener('click', goPrev);
    nextBtn.addEventListener('click', goNext);

    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) {
            closeLightbox();
        }
    });

    dialog.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            goPrev();
        } else if (e.key === 'ArrowRight') {
            goNext();
        }
    });

    // Attach to triggers on page
    document.querySelectorAll('[data-lightbox-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();

            const context = trigger.getAttribute('data-context');
            // Find all triggers in the same context to build the gallery array
            const contextTriggers = Array.from(document.querySelectorAll(`[data-lightbox-trigger][data-context="${context}"]`));

            const items = contextTriggers.map(t => ({
                viewUrl: t.getAttribute('data-view-url'),
                downloadUrl: t.getAttribute('data-download-url'),
                altText: t.getAttribute('data-alt')
            }));

            const index = contextTriggers.indexOf(trigger);

            if (index !== -1) {
                openLightbox(items, index, trigger);
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeEvidenceLightbox();
});
