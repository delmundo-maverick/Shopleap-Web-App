{{--
    FILE PATH: resources/views/seller/orders/show.blade.php
--}}

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Order #{{ $order->id }} | Shopleap Seller</title>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen text-charcoal" x-data="orderShowPage()">

    <div class="frost-surface"></div>

    <div class="flex min-h-screen">

        <x-navbars.seller active="orders" />

        <div class="flex flex-1 flex-col">

            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <a href="{{ route('seller.orders.index') }}"
                        class="frost-btn rounded-full p-2 text-charcoal/50 transition hover:text-primary">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Order #{{ $order->id }}</h2>
                        <p class="text-sm text-charcoal/55">Placed {{ $order->created_at->format('M d, Y \a\t g:i A') }}
                        </p>
                    </div>
                </div>

                <span :class="`status-badge status-badge--${status}`"
                    class="rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wide"
                    x-text="status.replace('_', ' ')">
                    {{ str_replace('_', ' ', $order->status) }}
                </span>
            </header>

            <main class="flex-1 p-6">

                <div class="mx-auto grid max-w-4xl grid-cols-1 gap-6 lg:grid-cols-3">

                    <div class="space-y-5 lg:col-span-2">

                        <!-- BUYER INFO -->
                        <div class="frost-panel rounded-2xl p-5">
                            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Buyer Information</p>
                            <dl class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt class="text-charcoal/50">Name</dt>
                                    <dd class="font-medium">{{ $order->buyer_name ?? ($order->buyer?->name ?? '—') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-charcoal/50">Contact No.</dt>
                                    <dd class="font-medium">{{ $order->buyer?->buyerProfile->contact_no ?? '—' }}</dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="text-charcoal/50">Delivery Address</dt>
                                    <dd class="font-medium">
                                        @if ($order->buyer?->buyerProfile)
                                            {{ $order->buyer->buyerProfile->house_number }}
                                            {{ $order->buyer->buyerProfile->street }},
                                            {{ $order->buyer->buyerProfile->barangay }},
                                            {{ $order->buyer->buyerProfile->municipality }},
                                            {{ $order->buyer->buyerProfile->province }}
                                        @else
                                            —
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- ITEMS -->
                        <div class="frost-panel rounded-2xl p-5">
                            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Items</p>
                            <div class="divide-y divide-white/50">
                                @foreach ($order->items as $item)
                                    <div class="flex items-center gap-4 py-3">
                                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-ice-blue">
                                            @if ($item->product && $item->product->main_image_url)
                                                <img src="{{ $item->product->main_image_url }}"
                                                    alt="{{ $item->product_name }}" class="h-full w-full object-cover">
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="line-clamp-1 text-sm text-charcoal">{{ $item->product_name }}</p>
                                            <p class="text-xs text-charcoal/50">
                                                ₱{{ number_format($item->price_at_purchase, 2) }} ×
                                                {{ $item->quantity }}</p>
                                        </div>
                                        <p class="shrink-0 text-sm font-bold text-charcoal">
                                            ₱{{ number_format($item->subtotal, 2) }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 space-y-1.5 border-t border-white/50 pt-4 text-sm">
                                <div class="flex justify-between text-charcoal/70">
                                    <span>Order Total</span>
                                    <span
                                        class="font-semibold text-charcoal">₱{{ number_format($order->total_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-charcoal/50">
                                    <span>Platform Commission (10%)</span>
                                    <span>− ₱{{ number_format($order->commission_amount, 2) }}</span>
                                </div>
                                <div
                                    class="flex justify-between border-t border-white/50 pt-1.5 font-bold text-charcoal">
                                    <span>Your Earnings</span>
                                    <span>₱{{ number_format($order->total_amount - $order->commission_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- STATUS UPDATE -->
                    <div class="lg:col-span-1">
                        <div class="frost-panel sticky top-6 rounded-2xl p-5">
                            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-primary">Update Order Status
                            </p>

                            <select x-model="status"
                                class="frost-input mb-3 w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                                <option value="pending">Pending</option>
                                <option value="to_ship">To Ship</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>

                            <button type="button" @click="updateStatus()" :disabled="saving"
                                class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-text="saving ? 'Updating…' : 'Update Status'"></span>
                            </button>

                            <p class="mt-3 text-xs text-charcoal/40">
                                Typical flow: Pending → To Ship → Shipped → Delivered. Use Cancelled only if the order
                                cannot be fulfilled.
                            </p>
                        </div>
                    </div>

                </div>

            </main>

        </div>

    </div>


    <!-- TOAST -->
    <div x-show="toast.show" x-cloak x-transition
        class="fixed bottom-6 right-6 z-[80] max-w-sm rounded-2xl px-5 py-3.5 text-sm font-medium text-white shadow-xl backdrop-blur-md"
        :style="`background: ${toast.error ? 'rgba(228,87,46,0.92)' : 'rgba(76,175,125,0.92)'}`" x-text="toast.message">
    </div>


    <style>
        .status-badge--pending {
            background: rgb(255 237 213);
            color: rgb(234 88 12);
        }

        .status-badge--to_ship {
            background: rgb(219 234 254);
            color: rgb(37 99 235);
        }

        .status-badge--shipped {
            background: rgb(224 231 255);
            color: rgb(67 56 202);
        }

        .status-badge--delivered {
            background: rgb(220 252 231);
            color: rgb(22 163 74);
        }

        .status-badge--cancelled {
            background: rgb(254 226 226);
            color: rgb(220 38 38);
        }
    </style>


    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('orderShowPage', () => ({
                status: @json($order->status),
                saving: false,
                toast: {
                    show: false,
                    message: '',
                    error: false,
                },

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]').content;
                },

                showToast(message, isError = false) {
                    this.toast = {
                        show: true,
                        message,
                        error: isError
                    };
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3500);
                },

                async updateStatus() {
                    this.saving = true;

                    try {
                        const res = await fetch(`/seller/orders/{{ $order->id }}/status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                status: this.status
                            }),
                        });
                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            this.showToast(data.message || 'Could not update status.', true);
                            return;
                        }

                        this.showToast(data.message);
                        this.status = data.status;
                    } catch (err) {
                        this.showToast('Network error — please try again.', true);
                    } finally {
                        this.saving = false;
                    }
                },
            }));
        });
    </script>

</body>

</html>
