import { termsAgreementState } from '../../shared/terms-modal.js';
import { initPsgcCascade } from '../../shared/psgcCascade.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('companyRegistration', (config = {}) => {
        let locations;

        return {
            ...termsAgreementState(config),
            birthday: '',
            get age() {
                if (!this.birthday) return '';
                const birth = new Date(`${this.birthday}T00:00:00`);
                const today = new Date();
                let years = today.getFullYear() - birth.getFullYear();
                if (today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) years--;
                return Number.isFinite(years) && years >= 0 ? years : '';
            },
            async init() {
                this.initTermsAgreement();
                const root = this.$el;
                this.birthday = root.dataset.birthday || '';
                await this.$nextTick();
                locations = initPsgcCascade({
                    province: this.$refs.province, municipality: this.$refs.municipality, barangay: this.$refs.barangay,
                    helpEl: this.$refs.locationHelp, locationsUrl: root.dataset.locationsUrl,
                    initial: { province: root.dataset.province, municipality: root.dataset.municipality, barangay: root.dataset.barangay },
                });
            },
            retryLocations() { locations?.reload(); },
        };
    });
});
