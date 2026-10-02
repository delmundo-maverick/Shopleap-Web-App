<x-logistics.layout title="Dashboard" subtitle="Sorting center operations overview" active="dashboard">

    @unless ($hasServiceAreas)
        <div class="frost-panel rounded-2xl border border-orange-200 bg-orange-50/70 px-4 py-3 text-sm text-orange-700">
            You haven't set any service areas yet, so only sellers in {{ $hub->municipality }}, {{ $hub->province }}
            can choose your hub, and parcels can't be auto-matched to riders.
            <a href="{{ route('logistics.areas.index') }}" class="font-bold underline">Set up service areas</a>
        </div>
    @endunless

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($cards as $card)
            <a href="{{ $card['href'] }}"
                class="frost-panel rounded-2xl p-5 transition hover:ring-2 hover:ring-primary/20">
                <p class="text-xs font-bold uppercase tracking-wide text-charcoal/50">{{ $card['label'] }}</p>
                <p
                    class="mt-2 text-3xl font-bold {{ $card['alert'] && $card['value'] > 0 ? 'text-primary' : 'text-charcoal' }}">
                    {{ $card['value'] }}</p>
            </a>
        @endforeach
    </div>

</x-logistics.layout>
