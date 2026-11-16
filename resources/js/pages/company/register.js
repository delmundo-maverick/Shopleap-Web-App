import { termsAgreementState } from '../../shared/terms-modal.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('companyRegistration', (config = {}) => ({
        ...termsAgreementState(config),
        init() { this.initTermsAgreement(); },
    }));
});
