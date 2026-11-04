const DOCUMENTS = ['terms', 'privacy'];

function emptyDocument() {
    return {
        status: 'idle',
        title: '',
        content: '',
        version: null,
        updatedAt: '',
        message: '',
        error: '',
    };
}

/**
 * Shared Alpine state for the combined Terms & Privacy modal on every
 * registration page. Both policies are shown in one continuous scroll, and
 * "I Agree" unlocks once the reader reaches the bottom.
 */
export function termsAgreementState(config = {}) {
    return {
        termsUrls: {
            terms: config.termsUrl || '/terms/content',
            privacy: config.privacyUrl || '/privacy/content',
        },

        termsDocuments: {
            terms: emptyDocument(),
            privacy: emptyDocument(),
        },

        termsModalOpen: false,
        termsAccepted: false,
        termsScrolledToEnd: false,
        termsCanAgree: false,
        termsError: '',

        initTermsAgreement() {
            this.termsAccepted =
                config.termsAccepted === true || config.termsAccepted === 1 || config.termsAccepted === '1';
            this.refreshTermsAgreementState();
        },

        policiesLoaded() {
            return DOCUMENTS.every((documentName) =>
                ['ready', 'empty'].includes(this.termsDocuments[documentName].status),
            );
        },

        failedPolicies() {
            return DOCUMENTS.filter(
                (documentName) => this.termsDocuments[documentName].status === 'error',
            );
        },

        refreshTermsAgreementState() {
            this.termsCanAgree = this.policiesLoaded() && this.termsScrolledToEnd;
        },

        async openTermsModal() {
            this.termsModalOpen = true;
            this.termsScrolledToEnd = false;
            this.refreshTermsAgreementState();

            await Promise.all(DOCUMENTS.map((documentName) => this.loadTermsDocument(documentName)));

            this.$nextTick(() => {
                this.resetTermsScroll();
                this.checkTermsScroll();
            });
        },

        closeTermsModal() {
            this.termsModalOpen = false;
        },

        declineTerms() {
            this.termsAccepted = false;
            this.termsScrolledToEnd = false;
            this.refreshTermsAgreementState();
            this.closeTermsModal();
        },

        agreeToTerms() {
            if (!this.termsCanAgree) {
                return;
            }

            this.termsAccepted = true;
            this.termsError = '';
            this.closeTermsModal();
        },

        async loadTermsDocument(documentName, force = false) {
            const document = this.termsDocuments[documentName];

            if (!document || (!force && ['loading', 'ready', 'empty'].includes(document.status))) {
                return;
            }

            document.status = 'loading';
            document.error = '';
            this.refreshTermsAgreementState();

            try {
                const response = await fetch(this.termsUrls[documentName], {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'Could not load this policy. Please try again.');
                }

                document.title = data.title || '';
                document.content = data.content || '';
                document.version = data.version ?? null;
                document.updatedAt = data.updated_at_human || data.updated_at || '';
                document.message = data.message || '';
                document.status = document.content ? 'ready' : 'empty';

                this.refreshTermsAgreementState();

                if (this.policiesLoaded()) {
                    this.$nextTick(() => this.checkTermsScroll());
                }
            } catch (error) {
                document.status = 'error';
                document.error =
                    error instanceof TypeError
                        ? 'Network error. Please check your connection and try again.'
                        : error.message || 'Could not load this policy. Please try again.';
                this.refreshTermsAgreementState();
            }
        },

        retryTermsDocument(documentName) {
            this.loadTermsDocument(documentName, true);
        },

        resetTermsScroll() {
            const scrollArea = this.$refs.termsScrollArea;

            if (scrollArea) {
                scrollArea.scrollTop = 0;
            }
        },

        checkTermsScroll() {
            const scrollArea = this.$refs.termsScrollArea;

            if (!scrollArea || !this.policiesLoaded() || this.termsScrolledToEnd) {
                return;
            }

            const reachedBottom = scrollArea.scrollTop + scrollArea.clientHeight >= scrollArea.scrollHeight - 8;
            const fitsWithoutScrolling = scrollArea.scrollHeight <= scrollArea.clientHeight + 4;

            if (reachedBottom || fitsWithoutScrolling) {
                this.termsScrolledToEnd = true;
                this.refreshTermsAgreementState();
            }
        },

        guardTermsAgreement(event) {
            if (this.termsAccepted) {
                this.termsError = '';
                return true;
            }

            event.preventDefault();
            this.openTermsModal();
            this.termsError = 'Please read and agree to the Terms and Conditions and Privacy Policy before continuing.';

            return false;
        },
    };
}
