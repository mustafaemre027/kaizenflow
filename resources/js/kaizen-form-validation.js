document.addEventListener('DOMContentLoaded', function() {
    const kaizenForm = document.querySelector('form[action*="kaizens"]');
    
    if (!kaizenForm) {
        return;
    }

    const currentSituation = document.getElementById('current_situation');
    const proposedSituation = document.getElementById('proposed_situation');

    if (!currentSituation || !proposedSituation) {
        return;
    }

    // Function to clear error when user types
    const clearError = (element) => {
        element.classList.remove('is-invalid');
        element.setAttribute('aria-invalid', 'false');
        
        // Let server-side error handle its own message clearing if it exists,
        // but for client-side we can just rely on CSS displaying the message
        // when is-invalid is present. The view already uses .invalid-feedback
        // which Bootstrap shows only when sibling has .is-invalid.
    };

    currentSituation.addEventListener('input', () => clearError(currentSituation));
    proposedSituation.addEventListener('input', () => clearError(proposedSituation));

    kaizenForm.addEventListener('submit', function(e) {
        let isValid = true;
        let firstInvalidField = null;

        if (currentSituation.value.trim() === '') {
            isValid = false;
            currentSituation.classList.add('is-invalid');
            currentSituation.setAttribute('aria-invalid', 'true');
            if (!firstInvalidField) firstInvalidField = currentSituation;
        }

        if (proposedSituation.value.trim() === '') {
            isValid = false;
            proposedSituation.classList.add('is-invalid');
            proposedSituation.setAttribute('aria-invalid', 'true');
            if (!firstInvalidField) firstInvalidField = proposedSituation;
        }

        if (!isValid) {
            e.preventDefault(); // Prevent form submission
            
            if (firstInvalidField) {
                firstInvalidField.focus();
                // Smooth scroll might not be required, but we can do a safe scroll
                firstInvalidField.scrollIntoView({ behavior: 'auto', block: 'center' });
            }
        }
    });
});
