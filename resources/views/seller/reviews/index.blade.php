<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Customer Reviews | Shopleap</title>
</head>

<body class="min-h-screen text-charcoal">

    <div class="frost-surface"></div>

    <div class="flex min-h-screen">

        <x-navbars.seller active="reviews" />

        <div class="flex flex-1 flex-col min-w-0">

            {{-- TOPBAR --}}
            <header class="frost-panel-solid sticky top-0 z-10 flex h-20 items-center justify-between px-6">

                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="rounded-xl p-2 text-charcoal/60 transition hover:bg-white/70 hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Customer Reviews</h2>
                        <p class="text-sm text-charcoal/55">
                            {{ $sellerProfile->business_name ?? 'Your Store' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2.5 rounded-full py-1 pl-1 pr-3 frost-chip">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">
                            {{ strtoupper(substr($sellerProfile->first_name ?? (auth()->user()->name ?? 'S'), 0, 1)) }}
                        </div>

                        <div class="hidden sm:block">
                            <p class="text-sm font-semibold leading-tight text-charcoal">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs leading-tight text-charcoal/50">Seller</p>
                        </div>
                    </div>
                </div>

            </header>

            {{-- CONTENT --}}
            <main class="flex-1 space-y-6 p-6">

                @if (session('status'))
                    <div
                        class="rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- SUMMARY --}}
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">

                    <div class="frost-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">Average Rating</p>
                        <div class="mt-2 flex items-end gap-2">
                            <p class="text-3xl font-bold text-charcoal">
                                {{ $stats['average'] !== null ? number_format($stats['average'], 1) : '--' }}
                            </p>
                            @if ($stats['average'] !== null)
                                @php $filled = (int) round($stats['average']); @endphp
                                <p class="pb-1 text-base leading-none">
                                    <span class="text-amber-400">{{ str_repeat('★', $filled) }}</span><span
                                        class="text-charcoal/15">{{ str_repeat('★', 5 - $filled) }}</span>
                                </p>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-charcoal/50">Across all your products</p>
                    </div>

                    <div class="frost-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">Total Reviews</p>
                        <p class="mt-2 text-3xl font-bold text-charcoal">{{ number_format($stats['count']) }}</p>
                        <p class="mt-1 text-xs text-charcoal/50">{{ number_format($stats['replied']) }} replied</p>
                    </div>

                    <div class="frost-panel rounded-2xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-charcoal/45">Needs Your Reply</p>
                        <p
                            class="mt-2 text-3xl font-bold {{ $stats['unreplied'] > 0 ? 'text-amber-600' : 'text-charcoal' }}">
                            {{ number_format($stats['unreplied']) }}
                        </p>
                        <p class="mt-1 text-xs text-charcoal/50">Replying builds buyer trust</p>
                    </div>

                </div>

                {{-- FILTERS --}}
                <div class="frost-panel rounded-2xl p-5">

                    {{-- Status pills --}}
                    <div class="flex flex-wrap gap-2">
                        @foreach ([null => ['All', $stats['count']], 'unreplied' => ['Needs reply', $stats['unreplied']], 'replied' => ['Replied', $stats['replied']]] as $key => [$label, $count])
                            <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}"
                                class="rounded-full px-4 py-1.5 text-xs font-semibold transition
                                    {{ $status === $key ? 'bg-primary text-white' : 'frost-chip text-charcoal/70 hover:text-primary' }}">
                                {{ $label }}
                                <span class="ml-1 opacity-70">{{ $count }}</span>
                            </a>
                        @endforeach
                    </div>

                    {{-- Rating + product filters --}}
                    <form method="GET" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        @if ($status)
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif

                        <select name="rating" onchange="this.form.submit()"
                            class="rounded-xl border border-white/70 bg-white/70 px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                            <option value="">All ratings</option>
                            @foreach ([5, 4, 3, 2, 1] as $star)
                                <option value="{{ $star }}" @selected($rating === $star)>
                                    {{ $star }} {{ \Illuminate\Support\Str::plural('star', $star) }}
                                </option>
                            @endforeach
                        </select>

                        <select name="product" onchange="this.form.submit()"
                            class="rounded-xl border border-white/70 bg-white/70 px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10 sm:col-span-1">
                            <option value="">All products</option>
                            @foreach ($productOptions as $option)
                                <option value="{{ $option->id }}" @selected($productId === $option->id)>
                                    {{ \Illuminate\Support\Str::limit($option->name, 50) }}
                                </option>
                            @endforeach
                        </select>

                        @if ($rating || $productId || $status)
                            <a href="{{ route('seller.reviews.index') }}"
                                class="flex items-center justify-center rounded-xl border border-white/70 px-3.5 py-2.5 text-sm font-semibold text-charcoal/60 transition hover:text-primary">
                                Clear filters
                            </a>
                        @endif
                    </form>
                </div>

                {{-- REVIEW LIST --}}
                @if ($reviews->isEmpty())
                    <div class="frost-panel flex flex-col items-center justify-center rounded-2xl p-14 text-center">
                        <p class="text-sm font-semibold text-charcoal/60">
                            {{ $stats['count'] === 0 ? 'No reviews yet' : 'No reviews match these filters' }}
                        </p>
                        <p class="mt-1 text-xs text-charcoal/40">
                            {{ $stats['count'] === 0
                                ? 'Reviews appear here once buyers confirm their orders and rate your products.'
                                : 'Try clearing a filter to see more reviews.' }}
                        </p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($reviews as $review)
                            @php
                                $bag = 'reply_' . $review['id'];
                                $hasError = $errors->hasBag($bag);
                                $replyValue = old('review_id') == $review['id'] ? old('reply') : $review['reply'];

                                $isGoodReview = $review['rating'] >= 4;
                                $templates = collect($replyTemplates[$isGoodReview ? 'positive' : 'negative'])
                                    ->map(
                                        fn($t) => [
                                            'label' => $t['label'],
                                            'text' => str_replace(
                                                ['{product}', '{shop}'],
                                                [$review['product'], $sellerProfile->business_name ?? 'our shop'],
                                                $t['text'],
                                            ),
                                        ],
                                    )
                                    ->values();
                            @endphp

                            <div class="frost-panel rounded-2xl p-5">

                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-charcoal">{{ $review['buyer'] }}</p>
                                        <p class="mt-0.5 text-sm leading-none">
                                            <span
                                                class="text-amber-400">{{ str_repeat('★', $review['rating']) }}</span><span
                                                class="text-charcoal/15">{{ str_repeat('★', 5 - $review['rating']) }}</span>
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        <p class="text-xs text-charcoal/40">
                                            {{ $review['created_at']->format('M d, Y · g:i A') }}</p>
                                        @unless ($review['reply'])
                                            <span
                                                class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-amber-600">
                                                Needs reply
                                            </span>
                                        @endunless
                                    </div>
                                </div>

                                <p class="mt-2 text-xs text-charcoal/50">
                                    {{ $review['product'] }} ·
                                    <a href="{{ route('seller.orders.show', $review['order_id']) }}"
                                        class="font-semibold text-primary hover:underline">Order
                                        #{{ $review['order_id'] }}</a>
                                </p>

                                @if (filled($review['comment']))
                                    <p class="mt-3 whitespace-pre-line text-sm text-charcoal/80">
                                        {{ $review['comment'] }}</p>
                                @else
                                    <p class="mt-3 text-sm italic text-charcoal/35">Rated without a written review.</p>
                                @endif

                                @if ($review['media']->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($review['media'] as $media)
                                            @if ($media['is_video'])
                                                <video src="{{ $media['url'] }}" controls preload="metadata"
                                                    class="h-24 w-32 rounded-lg bg-charcoal/10 object-cover"></video>
                                            @else
                                                <a href="{{ $media['url'] }}" target="_blank" rel="noopener"
                                                    class="block h-24 w-24 overflow-hidden rounded-lg bg-charcoal/10">
                                                    <img src="{{ $media['url'] }}" alt="Review photo" loading="lazy"
                                                        class="h-full w-full object-cover">
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                {{-- SELLER REPLY --}}
                                <div x-data="{
                                    editing: {{ $hasError ? 'true' : 'false' }},
                                    reply: {{ \Illuminate\Support\Js::from($replyValue ?? '') }},
                                    templates: {{ \Illuminate\Support\Js::from($templates) }},
                                    pick(text) {
                                        this.reply = text;
                                        this.$nextTick(() => this.$refs.reply.focus());
                                    }
                                }" class="mt-4 border-t border-white/60 pt-4">

                                    @if ($review['reply'])
                                        <div x-show="!editing" class="rounded-xl bg-primary/5 px-4 py-3">
                                            <p class="text-xs font-bold text-primary">
                                                Your reply · {{ $review['replied_at']?->diffForHumans() }}
                                            </p>
                                            <p class="mt-1 whitespace-pre-line text-sm text-charcoal/80">
                                                {{ $review['reply'] }}</p>

                                            <div class="mt-2 flex items-center gap-3 text-xs font-semibold">
                                                <button type="button" @click="editing = true"
                                                    class="text-primary hover:underline">Edit</button>

                                                <form method="POST"
                                                    action="{{ route('seller.reviews.reply.destroy', $review['id']) }}"
                                                    onsubmit="return confirm('Delete your reply to this review?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="text-sale-red hover:underline">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        <button type="button" x-show="!editing" @click="editing = true"
                                            class="rounded-lg border border-primary px-4 py-1.5 text-xs font-bold text-primary transition hover:bg-primary/5">
                                            Reply
                                        </button>
                                    @endif

                                    <form method="POST" action="{{ route('seller.reviews.reply', $review['id']) }}"
                                        x-show="editing" style="display: none;" class="space-y-2">
                                        @csrf
                                        <input type="hidden" name="review_id" value="{{ $review['id'] }}">

                                        {{-- QUICK REPLIES: click to fill the box, then edit freely or write your own --}}
                                        <div>
                                            <p
                                                class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-charcoal/45">
                                                {{ $isGoodReview ? 'Quick replies for good reviews' : 'Quick replies for low ratings' }}
                                            </p>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="t in templates" :key="t.label">
                                                    <button type="button" @click="pick(t.text)" x-text="t.label"
                                                        :class="reply === t.text ? 'border-primary bg-primary text-white' :
                                                            'border-primary/30 bg-white/70 text-primary hover:bg-primary/5'"
                                                        class="rounded-full border px-3 py-1 text-xs font-semibold transition">
                                                    </button>
                                                </template>
                                            </div>
                                        </div>

                                        <textarea name="reply" rows="3" maxlength="500" required x-ref="reply" x-model="reply"
                                            placeholder="Pick a quick reply above, or write your own…"
                                            class="w-full resize-none rounded-xl border border-white/70 bg-white/70 px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">{{ $replyValue }}</textarea>

                                        @if ($hasError)
                                            <p class="text-xs font-semibold text-sale-red">
                                                {{ $errors->getBag($bag)->first('reply') }}
                                            </p>
                                        @endif

                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-[11px] text-charcoal/40">
                                                <span x-text="reply.length">0</span>/500 · Buyers can see your reply.
                                                <button type="button" x-show="reply.length" @click="reply = ''"
                                                    class="ml-1 font-semibold text-primary hover:underline">Clear</button>
                                            </p>

                                            <div class="flex gap-2">
                                                <button type="button" @click="editing = false"
                                                    class="rounded-lg border border-charcoal/15 px-4 py-1.5 text-xs font-semibold text-charcoal/60 transition hover:text-charcoal">
                                                    Cancel
                                                </button>
                                                <button type="submit"
                                                    class="rounded-lg bg-primary px-4 py-1.5 text-xs font-bold text-white transition hover:bg-sky-blue">
                                                    {{ $review['reply'] ? 'Save reply' : 'Post reply' }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                            </div>
                        @endforeach
                    </div>

                    <div>{{ $reviews->onEachSide(1)->links() }}</div>
                @endif

            </main>

        </div>

    </div>

</body>

</html>
