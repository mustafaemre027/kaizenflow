/**
 * Handles the editing of existing Kaizen evidence (removing, undoing, effective count).
 * Integrates with existing Kaizen Evidence Picker for new uploads.
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Handle toggle removal of existing attachments
    const toggleButtons = document.querySelectorAll('.kf-btn-toggle-remove');
    const removeInputsContainer = document.getElementById('kf-remove-inputs-container');

    toggleButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const wrapper = e.target.closest('.kf-edit-gallery-item');
            const attachmentId = wrapper.getAttribute('data-attachment-id');
            const gallery = e.target.closest('.kf-edit-gallery');
            const context = gallery.getAttribute('data-context');

            const isRemoved = wrapper.classList.contains('is-removed');

            if (isRemoved) {
                // Undo removal
                wrapper.classList.remove('is-removed');
                e.target.textContent = 'Kaldır';
                
                // Remove the hidden input
                const hiddenInput = document.getElementById(`remove_attachment_${attachmentId}`);
                if (hiddenInput) {
                    hiddenInput.remove();
                }
            } else {
                // Mark for removal
                wrapper.classList.add('is-removed');
                e.target.textContent = 'Geri Al';

                // Add the hidden input
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'remove_attachment_ids[]';
                input.value = attachmentId;
                input.id = `remove_attachment_${attachmentId}`;
                removeInputsContainer.appendChild(input);
            }

            // Dispatch a custom event to notify the picker to update effective counts
            document.dispatchEvent(new CustomEvent('kaizen:evidence-removed-toggled', {
                detail: { context: context }
            }));
        });
    });
});
