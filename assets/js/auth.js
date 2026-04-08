/**
 * Authentication Page JavaScript
 * Additional functionality for login pages
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Auth page loaded');

    // Auto-focus first input
    const firstInput = document.querySelector('input[type="email"], input[type="text"]');
    if (firstInput) {
        firstInput.focus();
    }

    // Enhanced form submission
    const authForm = document.querySelector('.auth-form');
    if (authForm) {
        authForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Signing in...</span>';
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.innerHTML.replace('Signing in...', 'Sign In');
                }, 3000);
            }
        });
    }
});
