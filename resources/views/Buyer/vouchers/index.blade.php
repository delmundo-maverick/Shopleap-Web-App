<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/buyer/vouchers.js'])
    <title>My Vouchers | Shopleap</title>
</head>
<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">
<x-Buyer.header />
<main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6" x-data="{ tab: 'available' }">
    <h1 class="text-2xl font-bold">My Vouchers</h1>
    <p class="mt-2 text-sm text-charcoal/60">Claim vouchers from shops you follow. Claimed vouchers stay yours when you unfollow.</p>
    <nav class="my-5 flex flex-wrap gap-2" aria-label="Voucher filters">
        @foreach (['available' => 'Available', 'used' => 'Used / Redeemed', 'expired' => 'Expired'] as $key => $label)
            <button type="button" class="btn btn-outline" @click="tab = '{{ $key }}'" :aria-pressed="tab === '{{ $key }}'">{{ $label }}</button>
        @endforeach
    </nav>
    <section x-show="tab === 'available'">
        <div class="grid items-start gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($available as $voucher) <x-voucher-card :voucher="$voucher" />
            @empty <p class="rounded border border-line bg-white p-5 sm:col-span-2">No available vouchers yet. Follow a shop and claim its vouchers. <a href="{{ route('buyer.home') }}" class="font-semibold text-primary">Browse shops and products</a></p> @endforelse
        </div>
    </section>
    <section x-show="tab === 'used'" x-cloak>
        <div class="grid items-start gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($redeemed as $redemption) <x-voucher-card :voucher="$redemption->voucher" :redemption="$redemption" />
            @empty <p class="rounded border border-line bg-white p-5">No redeemed vouchers yet. Claim a voucher from a followed shop to use at checkout.</p> @endforelse
        </div>
    </section>
    <section x-show="tab === 'expired'" x-cloak>
        <div class="grid items-start gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($expired as $claim) <x-voucher-card :voucher="$claim->voucher" :claim="$claim" :expired="true" />
            @empty <p class="rounded border border-line bg-white p-5">No expired claimed vouchers.</p> @endforelse
        </div>
    </section>
</main>
<x-Buyer.message-widget /><x-footer />
</body></html>
