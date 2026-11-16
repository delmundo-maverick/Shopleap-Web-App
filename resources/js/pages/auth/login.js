document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const password = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');
    const capsWarning = document.getElementById('capsWarning');
    const submit = document.getElementById('loginSubmit');
    const submitLabel = document.getElementById('loginSubmitLabel');
    const submitArrow = document.getElementById('loginSubmitArrow');

    if (!form || !password) {
        return;
    }

    // Show / hide password
    toggle?.addEventListener('click', () => {
        const reveal = password.type === 'password';

        password.type = reveal ? 'text' : 'password';
        eyeOpen?.classList.toggle('hidden', reveal);
        eyeClosed?.classList.toggle('hidden', !reveal);
        toggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', String(reveal));
    });

    // Caps Lock warning
    const checkCaps = (event) => {
        capsWarning?.classList.toggle('hidden', !event.getModifierState('CapsLock'));
    };

    password.addEventListener('keydown', checkCaps);
    password.addEventListener('keyup', checkCaps);
    password.addEventListener('blur', () => capsWarning?.classList.add('hidden'));

    // Prevent double submits and show progress
    const setSubmitting = (isSubmitting) => {
        if (!submit) {
            return;
        }

        submit.disabled = isSubmitting;

        if (submitLabel) {
            submitLabel.textContent = isSubmitting ? 'Logging in…' : 'Log In';
        }

        submitArrow?.classList.toggle('hidden', isSubmitting);
    };

    form.addEventListener('submit', event => {
        if (submit?.disabled) { event.preventDefault(); return; }
        setSubmitting(true);
    });

    // Restore the button if the page is shown again from the back/forward cache
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            setSubmitting(false);
        }
    });
});
