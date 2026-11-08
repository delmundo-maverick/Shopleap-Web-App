<x-logistics.layout title="Rider Rate" subtitle="Set your hub's pay per successfully delivered parcel" active="rider-rate">
    <section class="grid gap-4 lg:grid-cols-2">
        <div class="rounded border border-line bg-white p-5">
            <h1 class="text-lg font-bold">Current rider rate</h1>
            <x-seller.stat-card label="Pay per delivered parcel" :value="0" :display-value="$currentRate ? '₱'.number_format($currentRate->amount, 2) : '-'" class="mt-3 rounded bg-white shadow-none" />
            <p class="mt-2 text-sm text-charcoal">{{ $currentRate ? 'Effective '.$currentRate->effective_from->format('M d, Y H:i') : 'No rate configured. Deliveries will have no recorded rider pay until a rate is set.' }}</p>
            <p class="mt-3 text-sm text-charcoal">Only successful deliveries earn rider pay. Changes apply when a parcel is delivered; past earnings stay fixed.</p>
        </div>
        <form method="POST" action="{{ route('logistics.rider-rate.store') }}" class="rounded border border-line bg-white p-5">
            @csrf
            <h2 class="text-sm font-bold">Set a new rate</h2>
            <label for="rider-rate" class="mt-4 block text-sm font-semibold">Rider pay per delivered parcel (PHP)</label>
            <input id="rider-rate" name="amount" type="number" min="0" step="0.01" required value="{{ old('amount') }}" aria-describedby="rate-limit" class="mt-2 w-full rounded border border-line px-3 py-2">
            <p id="rate-limit" class="mt-2 text-sm">Admin's current lowest shipping fee: <strong>{{ $lowestFee !== null ? '₱'.number_format($lowestFee, 2) : '-' }}</strong>. The rider rate must be lower.</p>
            @error('amount')<p class="mt-2 text-sm text-sale-red" role="alert">{{ $message }}</p>@enderror
            <button class="btn btn-primary mt-4" type="submit" @disabled($lowestFee === null)>Save new rate</button>
        </form>
    </section>
    <section class="space-y-3">
        <h2 class="text-sm font-bold">Rate history</h2>
        <x-seller.data-table label="Rider rate history" responsive="scroll" class="rounded bg-white">
            <x-slot:head><tr><th>Effective from ({{ config('app.timezone') }})</th><th>Rider pay</th><th>Set by</th></tr></x-slot:head>
            @forelse ($history as $rate)
                <tr><td>{{ $rate->effective_from->format('M d, Y H:i:s') }}</td><td>&#8369;{{ number_format($rate->amount, 2) }}</td><td>{{ $creators[$rate->created_by] ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="3" class="py-8 text-center">No rider rates have been set.</td></tr>
            @endforelse
        </x-seller.data-table>
        {{ $history->links() }}
    </section>
</x-logistics.layout>
