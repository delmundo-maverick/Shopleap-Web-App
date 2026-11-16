@props(['buyer' => false])
<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
<form id="{{ $buyer ? 'buyerSearchForm' : 'guestSearchForm' }}" action="{{ route($buyer ? 'buyer.products.index' : 'home') }}" method="GET" class="flex min-h-11 overflow-hidden rounded-lg bg-white">
    <input type="search" @focus="open = true" id="{{ $buyer ? 'buyerSearchInput' : 'guestSearchInput' }}" name="search" value="{{ request('search') }}" maxlength="100" placeholder="{{ $buyer ? 'Search products and shops' : 'Search products' }}" aria-label="{{ $buyer ? 'Search products and shops' : 'Search products' }}" class="min-w-0 w-full px-3 py-2.5 text-sm text-charcoal outline-none">
    <button type="submit" aria-label="Search" class="min-h-10 shrink-0 bg-sky-blue px-3 text-white"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-width="2" d="m21 21-5-5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0" /></svg></button>
</form>
@if ($buyer)
    <div x-show="open" x-cloak class="absolute inset-x-0 top-full z-50 mt-2 rounded border border-line bg-white p-3 text-charcoal">
        <div id="recentSearchesSection" class="hidden"><div class="mb-2 flex justify-between"><p class="text-xs font-semibold">Recent searches</p><button id="clearRecentSearchesBtn" type="button" class="min-h-10 px-3 text-xs text-primary">Clear</button></div><div id="recentSearchesList" class="flex flex-wrap gap-2"></div></div>
        <p class="text-xs text-charcoal/70">Search by product or shop name. A word or part of a name is enough.</p>
    </div>
@endif
</div>
