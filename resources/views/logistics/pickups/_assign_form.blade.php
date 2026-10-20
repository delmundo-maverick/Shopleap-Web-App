@php
    $defaultDate =
        $order->pickup_date && $order->pickup_date->gte(now()->startOfDay())
            ? $order->pickup_date->toDateString()
            : now()->toDateString();

    $field = 'frost-input rounded-xl px-3 py-2 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
@endphp

<form
    method="POST"
    action="{{ route('logistics.pickups.assign', $order) }}"
    class="flex flex-wrap items-end gap-3"
>
    @csrf

    {{-- RIDER --}}
    <div>
        <label class="mb-1 block text-[11px] font-semibold text-charcoal/60">
            Rider
        </label>

        <select name="rider_id" required class="{{ $field }}">
            <option
                value=""
                disabled
                @selected(!($order->pickup_rider_id ?? $suggestedRiderId ?? null))
            >
                Select rider…
            </option>

            @foreach ($riders as $rider)
                <option
                    value="{{ $rider->id }}"
                    @selected(($order->pickup_rider_id ?? $suggestedRiderId ?? null) === $rider->id)
                >
                    {{ $rider->full_name }} — {{ $rider->vehicle_type }} ({{ $rider->plate_number }})
                </option>
            @endforeach
        </select>
    </div>

    {{-- PICKUP DATE --}}
    <div>
        <label class="mb-1 block text-[11px] font-semibold text-charcoal/60">
            Pickup date
        </label>

        <input
            type="date"
            name="pickup_date"
            required
            min="{{ now()->toDateString() }}"
            value="{{ $defaultDate }}"
            class="{{ $field }}"
        >
    </div>

    {{-- PICKUP WINDOW --}}
    <div>
        <label class="mb-1 block text-[11px] font-semibold text-charcoal/60">
            Window
        </label>

        <select name="pickup_time_slot" required class="{{ $field }}">
            @foreach (\App\Models\Order::PICKUP_SLOTS as $key => $label)
                <option
                    value="{{ $key }}"
                    @selected($order->pickup_time_slot === $key)
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- SUBMIT --}}
    <button
        type="submit"
        @disabled($riders->isEmpty())
        class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-blue disabled:cursor-not-allowed disabled:opacity-50"
    >
        {{ $submitLabel }}
    </button>
</form>
