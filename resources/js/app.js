import Alpine from 'alpinejs';
import { productWorkspace } from './pages/seller/product-workspace.js';
import { productList, productNavigation } from './pages/seller/products.js';
import { productForm } from './pages/seller/product-form.js';
import { productDiscounts } from './pages/seller/discounts.js';
import { accountTabs } from './pages/buyer/account-tabs.js';
import { registerLandingEffects } from './pages/landing/landing.js';
import { notificationBell } from './shared/notifications.js';
import { navbar, navToggle } from './shared/navigation.js';
import { voucherCard } from './pages/buyer/vouchers.js';
import { addressBook } from './shared/buyer-addresses.js';
import { checkoutPage, voucherCheckout } from './pages/buyer/checkout.js';
import { termsAgreementState } from './shared/terms-modal.js';

window.Alpine = Alpine;
Alpine.data('productWorkspace', productWorkspace);
Alpine.data('productList', productList);
Alpine.data('productNavigation', productNavigation);
Alpine.data('productForm', productForm);
Alpine.data('productDiscounts', productDiscounts);
Alpine.data('accountTabs', accountTabs);
Alpine.data('shopleapBell', notificationBell);
Alpine.data('navbar', navbar);
Alpine.data('navToggle', navToggle);
Alpine.data('voucherCard', voucherCard);
Alpine.data('addressBook', addressBook);
Alpine.data('checkoutPage', checkoutPage);
Alpine.data('voucherCheckout', voucherCheckout);

Alpine.data('otpRegistration', (config = {}) => ({
    ...termsAgreementState(config),

    verifyUrl: config.verifyUrl,
    resendUrl: config.resendUrl,
    loginUrl: config.loginUrl,

    init() {
        this.initTermsAgreement();
    },

    submitting: false,
    showOtpModal: false,
    otpDigits: ['', '', '', '', '', ''],
    otpError: '',
    formErrors: [],
    email: '',
    verifying: false,
    resending: false,
    resendCooldown: 0,
    resendTimer: null,

    csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    },

    async submitRegistration(event) {
        if (!this.termsAccepted) {
            this.openTermsModal();
            this.termsError =
                'Please read and agree to the Terms and Conditions and Privacy Policy before continuing.';
            return;
        }

        this.termsError = '';
        this.submitting = true;
        this.formErrors = [];

        const form = event.target;
        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken(),
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (response.status === 422) {
                const errors = data.errors ?? {};
                this.formErrors = Object.values(errors).flat();

                if (errors.terms_accepted?.length) {
                    this.termsError = errors.terms_accepted[0];
                    this.openTermsModal();
                }

                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            if (!response.ok) {
                this.formErrors = [data.message || 'Something went wrong. Please try again.'];
                return;
            }

            this.email = data.email;
            this.openOtpModal();
        } catch (error) {
            console.error(error);
            this.formErrors = ['Network error. Please check your connection and try again.'];
        } finally {
            this.submitting = false;
        }
    },

    openOtpModal() {
        this.otpDigits = ['', '', '', '', '', ''];
        this.otpError = '';
        this.showOtpModal = true;
        this.startCooldown();
        this.$nextTick(() => this.$refs.otpInput0?.focus());
    },

    closeOtpModal() {
        this.showOtpModal = false;
        clearInterval(this.resendTimer);
    },

    handleOtpInput(index, event) {
        const digit = event.target.value.replace(/[^0-9]/g, '').slice(0, 1);
        this.otpDigits[index] = digit;
        event.target.value = digit;

        if (digit && index < 5) {
            this.$refs['otpInput' + (index + 1)]?.focus();
        }

        if (this.otpDigits.every((d) => d !== '')) {
            this.verifyOtp();
        }
    },

    handleOtpKeydown(index, event) {
        if (event.key === 'Backspace' && !this.otpDigits[index] && index > 0) {
            this.$refs['otpInput' + (index - 1)]?.focus();
        }
    },

    handleOtpPaste(event) {
        const pasted = (event.clipboardData?.getData('text') || '').replace(/[^0-9]/g, '').slice(0, 6);
        if (!pasted) return;
        event.preventDefault();

        pasted.split('').forEach((digit, i) => {
            this.otpDigits[i] = digit;
            if (this.$refs['otpInput' + i]) this.$refs['otpInput' + i].value = digit;
        });

        if (pasted.length === 6) {
            this.verifyOtp();
        } else {
            this.$refs['otpInput' + pasted.length]?.focus();
        }
    },

    async verifyOtp() {
        const code = this.otpDigits.join('');
        if (code.length !== 6 || this.verifying) return;

        this.verifying = true;
        this.otpError = '';

        try {
            const response = await fetch(this.verifyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ otp: code }),
            });

            const data = await response.json();

            if (!response.ok) {
                this.otpError = data.message || data.errors?.otp?.[0] || 'Invalid code. Please try again.';
                this.otpDigits = ['', '', '', '', '', ''];
                this.$nextTick(() => this.$refs.otpInput0?.focus());
                return;
            }

            window.location.href = this.loginUrl;
        } catch (error) {
            console.error(error);
            this.otpError = 'Network error. Please try again.';
        } finally {
            this.verifying = false;
        }
    },

    startCooldown() {
        this.resendCooldown = 60;
        clearInterval(this.resendTimer);
        this.resendTimer = setInterval(() => {
            this.resendCooldown--;
            if (this.resendCooldown <= 0) clearInterval(this.resendTimer);
        }, 1000);
    },

    async resendOtp() {
        if (this.resendCooldown > 0 || this.resending) return;

        this.resending = true;
        this.otpError = '';

        try {
            const response = await fetch(this.resendUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken(),
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                this.otpError = data.message || 'Could not resend code. Please try again.';
                return;
            }

            this.startCooldown();
        } catch (error) {
            console.error(error);
            this.otpError = 'Network error. Please try again.';
        } finally {
            this.resending = false;
        }
    },
}));

Alpine.store('wishlist', {
    ids: {},          // { [productId]: true }
    count: 0,
    pending: {},      // { [productId]: true } while a request is in flight
    url: '',
    loginUrl: '/login',

    // Alpine calls init() automatically when the store is registered.
    init() {
        const el = document.getElementById('wishlistData');
        if (el) {
            const data = JSON.parse(el.textContent || '{}');
            (data.ids || []).forEach((id) => (this.ids[id] = true));
            this.count = data.count ?? 0;
            this.url = data.url;
            this.loginUrl = data.loginUrl || '/login';
        }

        // Back/forward cache can show a stale heart; refresh when restored.
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) window.location.reload();
        });
    },

    has(id) {
        return !!this.ids[id];
    },

    set(id, on) {
        if (on) this.ids[id] = true;
        else delete this.ids[id];
    },

    headers(json = false) {
        return {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            ...(json && { 'Content-Type': 'application/json' }),
        };
    },

    toast(message, error = false) {
        window.dispatchEvent(new CustomEvent('wishlist-toast', { detail: { message, error } }));
    },

    /** Optimistic toggle: update the UI now, roll back if the server says no. */
    async toggle(id) {
        if (this.pending[id]) return;

        const wasOn = this.has(id);
        this.pending[id] = true;
        this.set(id, !wasOn);
        this.count += wasOn ? -1 : 1;

        try {
            const res = await fetch(this.url.replace('__ID__', id), {
                method: wasOn ? 'DELETE' : 'POST',
                headers: this.headers(),
            });

            if (res.status === 401 || res.status === 419) {
                window.location.href = this.loginUrl;
                return;
            }

            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
                throw new Error(data.message || 'Could not update your wishlist.');
            }

            this.count = data.wishlist_count; // trust the server's count
            this.toast(data.message);
        } catch (e) {
            this.set(id, wasOn);
            this.count += wasOn ? 1 : -1;
            this.toast(e instanceof TypeError ? 'Network error. Please try again.' : e.message, true);
        } finally {
            delete this.pending[id];
        }
    },

    /** Used by the wishlist page. Calls your existing cart endpoint. */
    async addToCart(url) {
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: this.headers(true),
                body: JSON.stringify({ quantity: 1 }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.success === false) {
                this.toast(data.message || 'Could not add to cart.', true);
                return;
            }

            this.toast(data.message);
            const badge = document.getElementById('buyerCartBadge');
            if (badge && data.cart_count !== undefined) badge.textContent = data.cart_count;
        } catch {
            this.toast('Network error. Please try again.', true);
        }
    },
});

Alpine.store('follow', {
    ids: {},          // { [sellerId]: true }
    counts: {},       // { [sellerId]: follower count }
    total: 0,         // sellers this buyer follows
    pending: {},      // { [sellerId]: true } while a request is in flight
    followUrl: '',
    unfollowUrl: '',
    loginUrl: '/login',

    init() {
        const el = document.getElementById('sellerFollowData');
        if (!el) return;

        const data = JSON.parse(el.textContent || '{}');
        (data.ids || []).forEach((id) => (this.ids[id] = true));
        Object.entries(data.counts || {}).forEach(([id, count]) => {
            this.counts[id] = Number(count);
        });

        this.total = data.total ?? 0;
        this.followUrl = data.followUrl;
        this.unfollowUrl = data.unfollowUrl;
        this.loginUrl = data.loginUrl || '/login';
    },

    has(id) {
        return !!this.ids[id];
    },

    followerCount(id) {
        return this.counts[id] ?? 0;
    },

    /** Seed a seller's state on pages that show a single shop. */
    prime(id, count, following) {
        if (this.pending[id]) return;

        if (following) this.ids[id] = true;
        else delete this.ids[id];

        if (count !== null && count !== undefined) this.counts[id] = Number(count);
    },

    set(id, following) {
        if (following) this.ids[id] = true;
        else delete this.ids[id];
    },

    headers() {
        return {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        };
    },

    toast(message, error = false) {
        window.dispatchEvent(new CustomEvent('seller-follow-toast', { detail: { message, error } }));
    },

    /** Optimistic toggle: update the UI now, roll back if the server says no. */
    async toggle(id) {
        if (this.pending[id]) return;

        const wasFollowing = this.has(id);
        const previousCount = this.followerCount(id);
        const previousTotal = this.total;

        this.pending[id] = true;
        this.set(id, !wasFollowing);
        this.counts[id] = Math.max(0, previousCount + (wasFollowing ? -1 : 1));
        this.total = Math.max(0, previousTotal + (wasFollowing ? -1 : 1));

        try {
            const res = await fetch((wasFollowing ? this.unfollowUrl : this.followUrl).replace('__ID__', id), {
                method: wasFollowing ? 'DELETE' : 'POST',
                headers: this.headers(),
            });

            if (res.status === 401 || res.status === 419) {
                window.location.href = this.loginUrl;
                return;
            }

            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
                throw new Error(data.message || 'Could not update your following list.');
            }

            this.set(id, data.following);
            this.counts[id] = data.followers_count;
            this.total = data.following_count;
            this.toast(data.message);
        } catch (e) {
            this.set(id, wasFollowing);
            this.counts[id] = previousCount;
            this.total = previousTotal;
            this.toast(e instanceof TypeError ? 'Network error. Please try again.' : e.message, true);
        } finally {
            delete this.pending[id];
        }
    },
});

registerLandingEffects(Alpine);
Alpine.start();
