<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>My Vouchers | Shopleap</title>
</head>

<body class="bg-ice-blue text-charcoal">
    <x-Buyer.header />

    <main class="mx-auto max-w-5xl px-3 py-6 md:px-8">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-charcoal md:text-2xl">Shop Vouchers</h1>
            <p class="mt-1 text-sm text-charcoal/60">Discover and use vouchers from your favorite sellers</p>
        </div>

        <section class="mb-8">
            <h2 class="mb-4 text-lg font-bold text-charcoal">Available Vouchers</h2>
            @if($available->isEmpty())
                <div class="rounded-lg bg-white p-8 text-center shadow-sm">
                    <p class="text-sm text-charcoal/60">No available vouchers right now</p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($available as $voucher)
                        <div class="relative overflow-hidden rounded-xl border border-dashed border-primary/40 bg-gradient-to-r from-primary/5 to-white shadow-sm">
                            <div class="absolute -left-3 top-1/2 h-6 w-6 -translate-y-1/2 rounded-full bg-ice-blue"></div>
                            <div class="absolute -right-3 top-1/2 h-6 w-6 -translate-y-1/2 rounded-full bg-ice-blue"></div>
                            <div class="p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-2xl font-bold text-primary">
                                            @if($voucher->discount_type === 'percentage')
                                                {{ rtrim(rtrim(number_format($voucher->discount_value, 2), '0'), '.') }}% OFF
                                            @else
                                                ₱{{ number_format($voucher->discount_value, 2) }} OFF
                                            @endif
                                        </p>
                                        <p class="mt-1 text-xs font-semibold text-charcoal">{{ $voucher->seller?->sellerProfile?->business_name ?? $voucher->seller?->name }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 space-y-1 text-xs text-charcoal/60">
                                    <p>Min. spend: ₱{{ number_format($voucher->min_purchase_amount, 2) }}</p>
                                    @if($voucher->expires_at)
                                        <p>Expires: {{ $voucher->expires_at->format('M d, Y h:i A') }}</p>
                                    @endif
                                    @if($voucher->remaining_quantity !== null)
                                        <p>{{ $voucher->remaining_quantity }} left</p>
                                    @endif
                                </div>
                                <div class="mt-4 flex items-center justify-between rounded-lg bg-white/80 px-3 py-2">
                                    <code class="text-sm font-bold tracking-widest text-charcoal">{{ $voucher->code }}</code>
                                    <button onclick="navigator.clipboard.writeText('{{ $voucher->code }}')" class="text-xs font-semibold text-primary hover:underline">Copy Code</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section>
            <h2 class="mb-4 text-lg font-bold text-charcoal">Redeemed Vouchers</h2>
            @if($redeemed->isEmpty())
                <div class="rounded-lg bg-white p-8 text-center shadow-sm">
                    <p class="text-sm text-charcoal/60">No redeemed vouchers yet</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($redeemed as $redemption)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white p-4 shadow-sm">
                            <div>
                                <p class="font-semibold text-charcoal">{{ $redemption->voucher?->code }}</p>
                                <p class="text-xs text-charcoal/60">{{ $redemption->seller?->sellerProfile?->business_name ?? $redemption->seller?->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-leaf-green">-₱{{ number_format($redemption->discount_amount, 2) }}</p>
                                <p class="text-xs text-charcoal/60">{{ ucfirst($redemption->status) }} @if($redemption->order_id) • Order #{{ $redemption->order_id }} @endif</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </main>

    <x-Buyer.message-widget />
    <x-footer />
</body>
</html>
