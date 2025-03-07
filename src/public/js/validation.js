// public/js/validation.js
document.addEventListener('DOMContentLoaded', function() {
    // Get all forms that need validation
    const forms = document.querySelectorAll('form');

    // Add validation to each form
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            let valid = true;

            // Validate required fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    // VULNERABILITY: Simple validation, easily bypassable
                    showError(field, 'This field is required.');
                    valid = false;
                } else {
                    removeError(field);
                }
            });

            // Email validation
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(function(field) {
                if (field.value.trim() && !validateEmail(field.value)) {
                    showError(field, 'Please enter a valid email address.');
                    valid = false;
                }
            });

            // Password validation
            const passwordFields = form.querySelectorAll('input[type="password"]');
            passwordFields.forEach(function(field) {
                // VULNERABILITY: Weak password policy
                if (field.value.trim() && field.value.length < 6) {
                    showError(field, 'Password must be at least 6 characters.');
                    valid = false;
                }
            });

            if (!valid) {
                event.preventDefault();
            }
        });
    });

    // Helper functions
    function validateEmail(email) {
        // Basic email validation
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function showError(field, message) {
        // Remove any existing error
        removeError(field);

        // Create error message
        const error = document.createElement('div');
        error.className = 'error-message';
        error.textContent = message;

        // Insert error after field
        field.parentNode.insertBefore(error, field.nextSibling);

        // Add error class to field
        field.classList.add('error-field');
    }

    function removeError(field) {
        // Remove error message if exists
        const error = field.nextElementSibling;
        if (error && error.className === 'error-message') {
            error.parentNode.removeChild(error);
        }

        // Remove error class from field
        field.classList.remove('error-field');
    }
});