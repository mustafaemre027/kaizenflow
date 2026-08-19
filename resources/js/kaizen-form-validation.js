document.addEventListener('DOMContentLoaded', function() {
    const kaizenForm = document.querySelector('form[action*="kaizens"]');
    
    if (!kaizenForm) {
        return;
    }

    const requiredFields = kaizenForm.querySelectorAll('[data-kf-required="true"]');

    if (requiredFields.length === 0) {
        return;
    }

    // Function to clear error when user interacts
    const clearError = (element) => {
        // If element has value or is valid
        if (element.value.trim() !== '') {
            element.classList.remove('is-invalid');
            element.setAttribute('aria-invalid', 'false');
        }
    };

    requiredFields.forEach(field => {
        // For textareas and text inputs, clear on input
        if (field.tagName === 'TEXTAREA' || (field.tagName === 'INPUT' && field.type === 'text')) {
            field.addEventListener('input', () => clearError(field));
        } 
        // For selects, clear on change
        else if (field.tagName === 'SELECT') {
            field.addEventListener('change', () => clearError(field));
        }
    });

    kaizenForm.addEventListener('submit', function(e) {
        let isValid = true;
        let firstInvalidField = null;

        requiredFields.forEach(field => {
            if (field.value.trim() === '') {
                isValid = false;
                field.classList.add('is-invalid');
                field.setAttribute('aria-invalid', 'true');
                
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            }
        });

        if (!isValid) {
            e.preventDefault(); // Prevent form submission
            
            if (firstInvalidField) {
                firstInvalidField.focus();
                // Safe scroll so it's vertically centered and visible to the user
                firstInvalidField.scrollIntoView({ behavior: 'auto', block: 'center' });
            }
        }
    });
});
