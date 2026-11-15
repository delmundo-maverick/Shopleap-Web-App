@props(['filters', 'statuses'])
<form method="GET" class="flex flex-wrap items-end gap-3 rounded-sm border border-line bg-surface p-4">
    <label class="block text-xs font-semibold" for="history-from">From date<input id="history-from" name="from" type="date" value="{{ $filters['from'] }}" class="mt-1 block w-full rounded-sm border border-line p-2 text-sm"></label>
    <label class="block text-xs font-semibold" for="history-to">To date<input id="history-to" name="to" type="date" value="{{ $filters['to'] }}" class="mt-1 block w-full rounded-sm border border-line p-2 text-sm"></label>
    <label class="block text-xs font-semibold" for="history-status">Status<select id="history-status" name="status" class="mt-1 block rounded-sm border border-line bg-surface p-2 text-sm">@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select></label>
    <button class="btn btn-primary rounded-sm!">Filter history</button>
    <button name="export" value="csv" class="btn btn-outline rounded-sm!">Export CSV</button>
</form>
