@props(['target' => 'workspace-navigation'])
<button type="button" x-data="navToggle(@js($target))" @click="toggle()"
    @navigation-state.window="if ($event.detail.id === @js($target)) expanded = $event.detail.open"
    :aria-expanded="expanded" aria-controls="{{ $target }}" :aria-label="expanded ? 'Close navigation menu' : 'Open navigation menu'"
    {{ $attributes->class(['btn btn-outline nav-toggle lg:hidden']) }}>
    <svg aria-hidden="true" class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" /></svg>
</button>
