<div x-show="termsModalOpen" x-cloak x-transition.opacity
    class="fixed inset-0 z-[70] flex items-center justify-center bg-charcoal/60 px-4 py-6"
    @keydown.escape.window="closeTermsModal()" role="dialog" aria-modal="true" aria-labelledby="terms-modal-title">

    <div @click.outside="closeTermsModal()" x-transition
        class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-light-gray bg-white shadow-2xl shadow-charcoal/25">

        <div class="flex items-start gap-4 border-b border-light-gray px-5 py-4 sm:px-6">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-ice-blue text-primary">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <h3 id="terms-modal-title" class="text-lg font-bold text-charcoal">Terms and Privacy Policy</h3>
                <p class="mt-0.5 text-xs leading-5 text-charcoal/60">
                    Read through both policies, then agree to continue.
                </p>
            </div>

            <button type="button" @click="closeTermsModal()" aria-label="Close"
                class="shrink-0 rounded-lg p-1 text-charcoal/40 transition hover:bg-ice-blue hover:text-charcoal">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="min-h-64 flex-1">
            <div x-show="!policiesLoaded() && failedPolicies().length === 0" x-cloak
                class="flex min-h-64 flex-col items-center justify-center gap-3 px-6 py-12 text-center">
                <span class="h-7 w-7 animate-spin rounded-full border-2 border-primary/20 border-t-primary"
                    aria-hidden="true"></span>
                <p class="text-sm font-semibold text-charcoal/60">Loading Terms and Privacy Policy…</p>
            </div>

            <div x-show="failedPolicies().length > 0" x-cloak
                class="flex min-h-64 flex-col items-center justify-center gap-4 px-6 py-12 text-center">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sale-red/10 text-sale-red">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v3m0 4h.01M10.3 3.8L2.9 17a2 2 0 001.75 3h14.7a2 2 0 001.75-3L13.7 3.8a2 2 0 00-3.4 0z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-charcoal">Could not load the policies</p>

                <template x-for="documentName in failedPolicies()" :key="documentName">
                    <div class="w-full max-w-md rounded-xl border border-sale-red/10 bg-sale-red/5 px-4 py-3">
                        <p class="text-xs font-bold text-charcoal"
                            x-text="termsDocuments[documentName].title || (documentName === 'terms' ? 'Terms and Conditions' : 'Privacy Policy')"></p>
                        <p class="mt-1 text-xs leading-5 text-charcoal/60"
                            x-text="termsDocuments[documentName].error"></p>
                        <button type="button" @click="retryTermsDocument(documentName)"
                            class="mt-2 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white transition hover:bg-sky-blue">
                            Try again
                        </button>
                    </div>
                </template>
            </div>

            <div x-show="policiesLoaded()" x-cloak x-ref="termsScrollArea" @scroll.passive="checkTermsScroll()"
                class="max-h-[62vh] overflow-y-auto px-5 py-4 sm:px-6 sm:py-5">
                <section>
                    <h2 class="text-base font-bold text-charcoal"
                        x-text="termsDocuments.terms.title || 'Terms and Conditions'"></h2>
                    <p class="mt-1 text-[11px] text-charcoal/45" x-show="termsDocuments.terms.status === 'ready'">
                        <span x-show="termsDocuments.terms.version"
                            x-text="'v' + termsDocuments.terms.version"></span>
                        <span x-show="termsDocuments.terms.version">·</span>
                        Last updated <span x-text="termsDocuments.terms.updatedAt || 'recently'"></span>
                    </p>

                    <div x-show="termsDocuments.terms.status === 'ready'"
                        class="mt-4 text-sm leading-7 text-charcoal/80" x-html="termsDocuments.terms.content"></div>

                    <p x-show="termsDocuments.terms.status === 'empty'"
                        class="mt-4 rounded-xl border border-primary/10 bg-ice-blue px-4 py-3 text-xs leading-6 text-charcoal/70"
                        x-text="termsDocuments.terms.message || 'This policy has not been published yet.'"></p>
                </section>

                <section class="mt-8 border-t border-light-gray pt-6">
                    <h2 class="text-base font-bold text-charcoal"
                        x-text="termsDocuments.privacy.title || 'Privacy Policy'"></h2>
                    <p class="mt-1 text-[11px] text-charcoal/45" x-show="termsDocuments.privacy.status === 'ready'">
                        <span x-show="termsDocuments.privacy.version"
                            x-text="'v' + termsDocuments.privacy.version"></span>
                        <span x-show="termsDocuments.privacy.version">·</span>
                        Last updated <span x-text="termsDocuments.privacy.updatedAt || 'recently'"></span>
                    </p>

                    <div x-show="termsDocuments.privacy.status === 'ready'"
                        class="mt-4 text-sm leading-7 text-charcoal/80" x-html="termsDocuments.privacy.content"></div>

                    <p x-show="termsDocuments.privacy.status === 'empty'"
                        class="mt-4 rounded-xl border border-primary/10 bg-ice-blue px-4 py-3 text-xs leading-6 text-charcoal/70"
                        x-text="termsDocuments.privacy.message || 'This policy has not been published yet.'"></p>
                </section>

                <p x-show="!termsScrolledToEnd"
                    class="mt-8 border-t border-light-gray pt-3 text-center text-[11px] font-semibold text-charcoal/45">
                    Scroll to the bottom to enable “I Agree”.
                </p>
            </div>
        </div>

        <div class="border-t border-light-gray bg-ice-blue/50 px-5 py-4 sm:px-6">
            <div class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                <button type="button" @click="declineTerms()"
                    class="order-2 text-xs font-semibold text-charcoal/55 transition hover:text-sale-red sm:order-1">
                    Decline
                </button>

                <div class="order-1 flex w-full flex-col items-center gap-1.5 sm:order-2 sm:w-auto sm:items-end">
                    <button type="button" @click="agreeToTerms()" :disabled="!termsCanAgree"
                        class="w-full rounded-xl bg-primary px-6 py-2.5 text-xs font-bold text-white transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                        I Agree
                    </button>

                    <p x-show="!termsCanAgree" x-cloak
                        class="text-center text-[11px] text-charcoal/45 sm:text-right"
                        x-text="policiesLoaded()
                            ?
                            'Scroll through both policies to enable “I Agree”.' :
                            'Loading policies…'"></p>
                </div>
            </div>
        </div>
    </div>
</div>
