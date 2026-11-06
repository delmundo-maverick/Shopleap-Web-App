@props(['id', 'label', 'href' => null, 'hover' => false])
<div data-nav-dropdown class="nav-dropdown"
    @mouseenter="if (@js($hover) && window.matchMedia('(hover: hover) and (pointer: fine)').matches) { dropdownTrigger = $el.querySelector('[data-nav-dropdown-trigger]'); activeDropdown = @js($id); }"
    @mouseleave="if (@js($hover) && window.matchMedia('(hover: hover) and (pointer: fine)').matches && activeDropdown === @js($id)) activeDropdown = null" @click.outside="if (activeDropdown === @js($id)) activeDropdown = null"
    @focusout="if (!$el.contains($event.relatedTarget) && activeDropdown === @js($id)) activeDropdown = null">
    @if ($href)
        <a href="{{ $href }}" data-nav-dropdown-trigger class="btn btn-outline nav-dropdown-toggle" aria-label="{{ $label }}"
            @focus="dropdownTrigger = $el; activeDropdown = @js($id)"
            @keydown.arrow-down.prevent="focusMenu(@js($id), $el)" :aria-expanded="activeDropdown === @js($id)" aria-controls="{{ $id }}">
            {{ $trigger ?? $label }}
        </a>
    @else
    <button data-nav-dropdown-trigger type="button" class="btn btn-outline nav-dropdown-toggle" @click="dropdown(@js($id), $el)"
        @keydown.arrow-down.prevent="focusMenu(@js($id), $el)" :aria-expanded="activeDropdown === @js($id)" aria-controls="{{ $id }}" aria-label="{{ $label }}">
        {{ $trigger ?? $label }}<svg aria-hidden="true" class="size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
    </button>
    @endif
    <div id="{{ $id }}" data-nav-dropdown-menu x-show="activeDropdown === @js($id)" x-cloak x-transition class="nav-dropdown-menu"
        @keydown.arrow-down="moveMenu($event, 1)" @keydown.arrow-up="moveMenu($event, -1)">
        {{ $slot }}
    </div>
</div>
