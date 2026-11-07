@props(['label', 'responsive' => 'cards'])
<div {{ $attributes->class(['frost-panel min-w-0 overflow-x-auto rounded-xl border border-line p-3 sm:p-4']) }}>
    <table @class(['app-table', 'products-table' => $responsive === 'cards', 'w-full min-w-[40rem] text-left text-sm' => $responsive === 'scroll']) aria-label="{{ $label }}">
        <thead>{{ $head }}</thead><tbody>{{ $slot }}</tbody>
    </table>
</div>
