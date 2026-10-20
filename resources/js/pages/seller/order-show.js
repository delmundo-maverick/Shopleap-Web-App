document.addEventListener('alpine:init', () => {
    Alpine.data('orderShowPage', (config = {}) => ({
        status: config.status ?? '',
        statusLabels: config.statusLabels ?? {},
        orderId: config.orderId ?? '',

        saving: false,
        next: '',

        toast: {
            show: false,
            message: '',
            error: false,
        },

        toastTimeout: null,

        statusLabel(value) {
            return this.statusLabels[value]
                ?? String(value ?? '').replace(/_/g, ' ');
        },

        /**
         * Reuse the semantic status badge classes from app.css.
         */
        badgeVariant(value) {
            const status = String(value ?? '').toLowerCase();

            const successStatuses = [
                'delivered',
                'completed',
            ];

            const warningStatuses = [
                'placed',
                'preparing',
                'ready_for_pickup',
            ];

            const dangerStatuses = [
                'cancelled',
                'delivery_failed',
                'returned',
            ];

            const neutralStatuses = [
                'confirmed',
                'picked_up',
                'at_sorting_center',
                'sorted',
                'assigned_to_rider',
                'out_for_delivery',
            ];

            if (successStatuses.includes(status)) {
                return 'badge--success';
            }

            if (warningStatuses.includes(status)) {
                return 'badge--warning';
            }

            if (dangerStatuses.includes(status)) {
                return 'badge--danger';
            }

            if (neutralStatuses.includes(status)) {
                return 'badge--neutral';
            }

            // Keep unknown or newly introduced statuses readable.
            return 'badge--neutral';
        },

        csrfToken() {
            return document.querySelector(
                'meta[name="csrf-token"]'
            )?.content ?? '';
        },

        showToast(message, isError = false) {
            if (this.toastTimeout) {
                window.clearTimeout(this.toastTimeout);
            }

            this.toast = {
                show: true,
                message,
                error: isError,
            };

            this.toastTimeout = window.setTimeout(() => {
                this.toast.show = false;
                this.toastTimeout = null;
            }, 3500);
        },

        async updateStatus() {
            if (!this.next || this.saving) {
                return;
            }

            if (
                this.next === 'CANCELLED'
                && !window.confirm(
                    'Cancel this order? Stock will be restored and this cannot be undone.'
                )
            ) {
                return;
            }

            this.saving = true;

            try {
                const response = await fetch(
                    `/seller/orders/${encodeURIComponent(this.orderId)}/status`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            status: this.next,
                        }),
                    }
                );

                const data = await response.json();

                if (!response.ok || !data.success) {
                    this.showToast(
                        data.message || 'Could not update status.',
                        true
                    );

                    return;
                }

                this.showToast(
                    data.message || 'Order status updated.'
                );

                this.status = data.status;

                // Reload server-rendered pickup, shipping label, and
                // other order details after the successful update.
                window.setTimeout(() => {
                    window.location.reload();
                }, 700);
            } catch (error) {
                this.showToast(
                    'Network error — please try again.',
                    true
                );
            } finally {
                this.saving = false;
            }
        },
    }));
});
