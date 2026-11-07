@props(['title', 'description' => 'Manage your shop products'])
<header {{ $attributes->class(['workspace-header frost-panel-solid sticky top-0 z-10 flex min-h-16 items-center justify-between gap-3 border-b border-white/50 px-4 py-3 sm:min-h-20 sm:px-6']) }}>
    <div class="flex min-w-0 items-center gap-3"><x-nav.toggle target="workspace-navigation" /><div><h1 class="text-page-title">{{ $title }}</h1><p class="text-xs text-charcoal/60">{{ $description }}</p></div></div>
    <div class="flex shrink-0 flex-wrap gap-2">{{ $slot }}</div>
</header>
