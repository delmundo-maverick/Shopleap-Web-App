<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Return / Refund | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    <x-Buyer.header :cart-count="$cartCount" :cart-preview-items="$cartPreviewItems" />

    <main class="mx-auto w-full max-w-3xl flex-1 px-3 py-5 md:px-8 md:py-6"
        x-data="{
            reasons: @js($reasons),
            reason: @js(old('reason', '')),
            selected: @js(array_map('strval', old('items', $order->items->pluck('id')->all()))),
            prices: @js($order->items->pluck('subtotal', 'id')),
            shippingFee: {{ (float) $order->shipping_fee }},
            totalItems: {{ $order->items->count() }},
            files: [],
            submitting: false,
            get type() { return this.reason ? this.reasons[this.reason].type : null },
            get needsEvidence() { return this.reason ? this.reasons[this.reason].evidence : false },
            get itemsTotal() { return this.selected.reduce((sum, id) => sum + Number(this.prices[id] || 0), 0) },
            get shipping() { return this.selected.length === this.totalItems ? this.shippingFee : 0 },
            get refund() { return this.itemsTotal + this.shipping },
            money(v) { return '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
            pick(e) { this.files = Array.from(e.target.files).slice(0, {{ \App\Models\OrderReturn::MAX_MEDIA }}).map(f => f.name) },
        }">

        <a href="{{ route('buyer.orders.index') }}"
            class="mb-4 inline-flex items-center gap-1 text-xs font-semibold text-charcoal/60 hover:text-primary">
            ← Back to my orders
        </a>

        <h1 class="mb-1 text-xl font-bold text-charcoal">Return / Refund</h1>
        <p class="mb-5 text-sm text-charcoal/60">
            Order #{{ $order->id }} · {{ $order->seller->sellerProfile->business_name ?? $order->seller->name }}.
            You can send a request until {{ $order->returnWindowEndsAt()->format('M d, Y') }}.
        </p>

        @if ($errors->any())
            <div role="alert"
                class="mb-5 rounded-xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('buyer.orders.return.store', $order) }}" enctype="multipart/form-data"
            @submit="submitting = true" class="space-y-5">
            @csrf

            {{-- 1. Items --}}
            <section class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                <h2 class="mb-3 text-sm font-bold text-charcoal">1. Which items have a problem?</h2>

                <div class="divide-y divide-light-gray">
                    @foreach ($order->items as $item)
                        <label class="flex cursor-pointer items-center gap-3 py-3">
                            <input type="checkbox" name="items[]" value="{{ $item->id }}" x-model="selected"
                                class="h-4 w-4 shrink-0 accent-primary">

                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-ice-blue/60">
                                @if ($item->product && $item->product->main_image_url)
                                    <img src="{{ $item->product->main_image_url }}" alt="{{ $item->product_name }}"
                                        class="h-full w-full object-cover">
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-2 text-sm font-semibold">{{ $item->product_name }}</p>
                                @if ($item->selected_options)
                                    <p class="text-xs text-charcoal/50">
                                        {{ collect($item->selected_options)->map(fn($v, $k) => is_string($k) ? "{$k}: {$v}" : $v)->implode(' · ') }}
                                    </p>
                                @endif
                                <p class="text-xs text-charcoal/50">x{{ $item->quantity }}</p>
                            </div>

                            <p class="shrink-0 text-sm font-semibold">₱{{ number_format($item->subtotal, 2) }}</p>
                        </label>
                    @endforeach
                </div>

                <p class="mt-2 text-[11px] text-charcoal/40">The whole line is returned, not part of its quantity.</p>
            </section>

            {{-- 2. Reason --}}
            <section class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                <h2 class="mb-3 text-sm font-bold text-charcoal">2. What went wrong?</h2>

                <select name="reason" x-model="reason" required
                    class="w-full rounded-xl border border-light-gray bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                    <option value="" disabled>Select a reason…</option>
                    @foreach ($reasons as $key => $reason)
                        <option value="{{ $key }}">{{ $reason['label'] }}</option>
                    @endforeach
                </select>

                <div x-show="type === 'refund_only'" x-cloak
                    class="mt-3 rounded-xl bg-ice-blue/60 p-3 text-xs text-charcoal/70">
                    <b>Refund only.</b> You don't need to send anything back.
                    <span x-show="reason === 'not_received'">
                        Because the courier recorded this parcel as delivered, Shopleap will check the delivery and decide.
                    </span>
                </div>

                <div x-show="type === 'return_and_refund'" x-cloak
                    class="mt-3 rounded-xl bg-ice-blue/60 p-3 text-xs text-charcoal/70">
                    <b>Return and refund.</b> After the seller approves, you ship the item back. The refund is sent once the
                    seller confirms it arrived.
                </div>

                <label for="details" class="mb-2 mt-4 block text-sm font-semibold">Tell us what happened</label>
                <textarea id="details" name="details" rows="4" minlength="10" maxlength="1000" required
                    placeholder="Describe the problem so the seller can understand it."
                    class="w-full resize-none rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">{{ old('details') }}</textarea>
            </section>

            {{-- 3. Evidence --}}
            <section class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                <h2 class="mb-1 text-sm font-bold text-charcoal">
                    3. Photos or videos
                    <span x-show="needsEvidence" x-cloak class="text-sale-red">*</span>
                    <span x-show="!needsEvidence" class="font-normal text-charcoal/40">(optional)</span>
                </h2>
                <p class="mb-3 text-xs text-charcoal/50">Show the problem clearly. Up to
                    {{ \App\Models\OrderReturn::MAX_MEDIA }} files, 20 MB each.</p>

                <label for="media"
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed border-light-gray bg-ice-blue/40 px-4 py-4 text-xs font-semibold text-charcoal/60 transition hover:border-primary/40">
                    Choose files
                    <input type="file" id="media" name="media[]" multiple accept="image/*,video/*" class="sr-only"
                        @change="pick($event)">
                </label>

                <ul class="mt-2 space-y-1 text-xs text-charcoal/60">
                    <template x-for="name in files" :key="name">
                        <li x-text="name" class="truncate"></li>
                    </template>
                </ul>
            </section>

            {{-- 4. Refund payout --}}
            <section class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                <h2 class="mb-1 text-sm font-bold text-charcoal">4. Where should we send your refund?</h2>
                <p class="mb-3 text-xs text-charcoal/50">You paid cash on delivery, so Shopleap sends the refund to this
                    account. Only Shopleap sees the full number.</p>

                <div class="grid gap-3 sm:grid-cols-3">
                    <select name="refund_method" required
                        class="rounded-xl border border-light-gray bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                        @foreach (\App\Models\OrderReturn::REFUND_METHODS as $key => $label)
                            <option value="{{ $key }}" @selected(old('refund_method') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <input type="text" name="refund_account_name" value="{{ old('refund_account_name') }}" required
                        maxlength="150" placeholder="Account name"
                        class="rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">

                    <input type="text" name="refund_account_number" value="{{ old('refund_account_number') }}" required
                        maxlength="60" inputmode="numeric" placeholder="Mobile or account number"
                        class="rounded-xl border border-light-gray px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                </div>
            </section>

            {{-- Summary --}}
            <section class="rounded-lg bg-white p-5 shadow-sm shadow-charcoal/5">
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between text-charcoal/70">
                        <span>Items to refund</span>
                        <span x-text="money(itemsTotal)"></span>
                    </div>
                    <div class="flex justify-between text-charcoal/70">
                        <span>Shipping fee <span class="text-xs text-charcoal/40">(only when every item is included)</span></span>
                        <span x-text="money(shipping)"></span>
                    </div>
                    <div class="flex justify-between border-t border-light-gray pt-2 font-bold text-charcoal">
                        <span>Refund amount</span>
                        <span class="text-primary" x-text="money(refund)"></span>
                    </div>
                </div>

                <button type="submit"
                    :disabled="submitting || !reason || selected.length === 0 || (needsEvidence && files.length === 0)"
                    class="mt-5 w-full rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-text="submitting ? 'Sending…' : 'Submit Request'"></span>
                </button>
            </section>
        </form>
    </main>

    <x-Buyer.message-widget />
    <x-footer />

</body>

</html>
