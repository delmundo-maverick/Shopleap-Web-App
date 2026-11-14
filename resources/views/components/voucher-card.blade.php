@props(['voucher', 'shop' => false, 'claim' => null, 'redemption' => null, 'expired' => false])
<article class="relative grid w-full max-w-[400px] grid-cols-[88px_minmax(0,1fr)] overflow-hidden rounded-md border border-line bg-white"
    @if ($shop) x-data="voucherCard(@js(['url' => route('buyer.vouchers.claim', $voucher), 'sellerId' => $voucher->seller_id, 'claimed' => $claim !== null, 'used' => $claim?->used_at !== null, 'remaining' => $voucher->claim_limit === null ? null : max(0, $voucher->claim_limit - $voucher->claims_count), 'valid' => $voucher->isValidNow(), 'reason' => $voucher->unavailableReason(), 'loginUrl' => route('login')]))" @endif>
    <span aria-hidden="true" @class(['absolute -top-1.5 left-[81px] z-10 size-3 rounded-full border border-line', 'bg-white' => $shop, 'bg-ice-blue' => !$shop])></span>
    <span aria-hidden="true" @class(['absolute -bottom-1.5 left-[81px] z-10 size-3 rounded-full border border-line', 'bg-white' => $shop, 'bg-ice-blue' => !$shop])></span>

    <div class="flex flex-col items-center justify-center gap-1 border-r border-dashed border-primary/25 bg-primary/5 px-2 py-4 text-center">
        <svg aria-hidden="true" class="mb-1 size-4 text-primary/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v3a2 2 0 100 4v3a2 2 0 01-2 2H5a2 2 0 01-2-2v-3a2 2 0 100-4V7a2 2 0 012-2z" />
        </svg>
        <p class="break-all text-xl font-extrabold leading-tight tracking-tight text-primary">
            @if ($voucher->discount_type === 'percentage')
                {{ rtrim(rtrim(number_format($voucher->discount_value, 2), '0'), '.') }}<span class="text-sm">%</span>
            @else
                <span class="block text-[9px] font-semibold tracking-widest">PHP</span>{{ rtrim(rtrim(number_format($voucher->discount_value, 2), '0'), '.') }}
            @endif
        </p>
        <span class="text-[9px] font-bold tracking-[0.18em] text-primary">OFF</span>
        @if ($voucher->max_discount_amount !== null)
            <span class="mt-1 text-[9px] leading-snug text-primary/70">Up to PHP {{ number_format($voucher->max_discount_amount, 2) }}</span>
        @endif
    </div>

    <div class="flex min-w-0 flex-col gap-2 p-3">
        <div class="min-w-0">
            <h3 class="truncate text-xs font-bold text-charcoal" title="{{ $voucher->name ?: $voucher->code }}">{{ $voucher->name ?: $voucher->code }}</h3>
            @if ($voucher->seller_id)
                <a href="{{ route('buyer.shop.show', $voucher->seller_id) }}" class="mt-0.5 block truncate text-[11px] font-medium text-primary hover:underline">{{ $voucher->seller?->sellerProfile?->business_name ?? $voucher->seller?->name }}</a>
            @endif
        </div>
        <div class="space-y-0.5 text-[10px] leading-relaxed text-charcoal/65">
            <p>Min. spend <span class="font-semibold text-charcoal/80">PHP {{ number_format($voucher->min_purchase_amount, 2) }}</span>@if ($voucher->min_quantity) &middot; {{ $voucher->min_quantity }} items @endif</p>
            <p>{{ $voucher->expires_at ? 'Until '.$voucher->expires_at->format('M d, Y').' at '.$voucher->expires_at->format('g:i A') : 'No expiry' }}</p>
            @if ($voucher->remaining_quantity !== null) <p>{{ $voucher->remaining_quantity }} uses left</p> @endif
            @if ($shop) <p x-show="remaining !== null" x-text="`${remaining} claims left`"></p> @endif
        </div>
        <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-dashed border-line pt-2">
            <code class="min-w-0 break-all text-[10px] font-bold tracking-wide text-charcoal/80">{{ $voucher->code }}</code>
            @if ($shop)
                <button type="button" class="btn btn-primary min-h-8 px-3 py-1.5 text-[10px]" @click="take()" :disabled="busy || !!$store.follow.pending[{{ $voucher->seller_id }}] || claimed || !valid || remaining === 0" x-text="busy ? 'Claiming...' : (used ? 'Used' : (claimed ? 'Claimed' : (!valid ? reason : (remaining === 0 ? 'Fully claimed' : ($store.follow.has({{ $voucher->seller_id }}) ? 'Claim' : 'Follow to claim')))))">Claim</button>
            @elseif ($redemption)
                <span class="rounded-sm bg-ice-blue px-2 py-1 text-[10px] font-semibold text-charcoal/65">{{ ucfirst($redemption->status) }}</span>
            @elseif ($expired)
                <span class="rounded-sm bg-sale-red/10 px-2 py-1 text-[10px] font-semibold text-sale-red">Expired</span>
            @else
                <button type="button" data-copy-voucher="{{ $voucher->code }}" class="btn btn-outline min-h-8 px-3 py-1.5 text-[10px]">Copy code</button>
            @endif
        </div>
        @if ($shop)
            <a x-show="claimed" x-cloak href="{{ route('buyer.vouchers.index') }}" class="text-[10px] font-semibold text-primary hover:underline">View My Vouchers &rarr;</a>
            <p x-show="error" x-cloak x-text="error" role="alert" class="text-[10px] text-sale-red"></p>
        @elseif ($redemption)
            <div class="flex flex-wrap items-center justify-between gap-1 text-[10px] text-charcoal/55">
                <span>{{ ($redemption->used_at ?? $redemption->created_at)->format('M d, Y') }}</span>
                @if ($redemption->order_id) <a href="{{ route('buyer.orders.index', ['search' => $redemption->order?->order_number]) }}" class="font-semibold text-primary hover:underline">Order #{{ $redemption->order?->order_number }} &rarr;</a> @endif
            </div>
        @endif
    </div>
</article>
