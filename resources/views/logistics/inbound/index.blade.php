<x-logistics.layout title="Inbound Bags" subtitle="Bags addressed to this hub that still need to be received" active="inbound"
    :scripts="['resources/js/pages/logistics/inbound.js']">

    <div class="space-y-4">
        {{-- QUICK SCAN --}}
        <div x-data="inboundScanner()" class="frost-panel-solid rounded-2xl p-4 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="mb-1 flex items-center gap-2">
                        <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-bold text-charcoal">Quick Scan</h3>
                    </div>
                    <p class="text-sm text-charcoal/60">
                        Scan a parcel waybill or a bag code to receive it at this hub.
                    </p>
                    <p class="mt-1 text-xs text-charcoal/50">
                        Camera, USB scanner, or manual entry. Codes already scanned are rejected.
                    </p>
                </div>

                <button type="button" @click="isScanning ? stopScanner(false) : startScanner()"
                    :disabled="cameraBusy"
                    class="btn shrink-0 disabled:cursor-not-allowed disabled:opacity-50"
                    :class="isScanning ? 'btn-danger' : 'btn-primary'"
                    :aria-label="isScanning ? 'Stop Camera' : 'Start Camera'">
                    <span x-show="!isScanning" class="flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Start Camera
                    </span>
                    <span x-show="isScanning" class="flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Stop Camera
                    </span>
                </button>
            </div>

            <div class="mt-5 grid gap-5 lg:grid-cols-2 lg:gap-6">
                {{-- Camera --}}
                <div class="space-y-3">
                    <div x-show="isScanning" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="min-h-[300px] overflow-hidden rounded-xl bg-charcoal/5 shadow-inner">
                        <div id="inbound-scanner-container"></div>
                    </div>

                    <div x-show="!isScanning"
                        class="flex h-[300px] items-center justify-center rounded-xl border-2 border-dashed border-charcoal/10 bg-light-gray text-center">
                        <div>
                            <svg class="mx-auto h-16 w-16 text-charcoal/30" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <p class="mt-3 text-sm font-semibold text-charcoal/70">Camera Ready</p>
                            <p class="mt-1 text-xs text-charcoal/50">Click "Start Camera" above to scan.</p>
                        </div>
                    </div>

                    <p x-show="cameraError" x-text="cameraError" x-cloak role="alert"
                        class="rounded-lg bg-sale-red/10 px-3 py-2 text-xs font-semibold text-sale-red"></p>
                </div>

                {{-- Manual entry, feedback and running list --}}
                <div class="space-y-4">
                    <form @submit.prevent="handleManualInput()" class="space-y-3">
                        <label for="inbound-manual-code"
                            class="flex items-center gap-2 text-sm font-semibold text-charcoal">
                            <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                            Manual Entry
                        </label>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <input id="inbound-manual-code" x-model="manualCode" type="text"
                                placeholder="Enter waybill or bag code" autocomplete="off"
                                aria-label="Waybill or bag code" :disabled="scanning"
                                class="frost-input w-full flex-1 rounded-lg px-3 py-2 text-sm font-mono outline-none transition focus:ring-4 focus:ring-primary/10 disabled:opacity-50">

                            <button type="submit" :disabled="!manualCode.trim() || scanning"
                                class="btn btn-primary w-full disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                                <span x-show="!scanning" class="flex items-center justify-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    Submit Code
                                </span>
                                <span x-show="scanning" class="flex items-center justify-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                        </path>
                                    </svg>
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </form>

                    <div x-show="lastResult" x-cloak role="status" class="rounded-xl border-2 p-3"
                        :class="lastResult?.ok ? 'border-leaf-green/30 bg-leaf-green/5' : 'border-sale-red/30 bg-sale-red/5'">
                        <p class="text-sm font-semibold" :class="lastResult?.color" x-text="lastResult?.message"></p>
                        <p x-show="lastResult?.reference" class="mt-1 font-mono text-xs text-charcoal/60"
                            x-text="lastResult?.reference"></p>
                    </div>

                    <div x-show="scanned.length > 0" x-cloak class="space-y-2">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-charcoal">
                                Scanned this session
                                <span class="ml-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary"
                                    x-text="scanned.length"></span>
                            </p>
                            <button type="button" @click="clearResults()" class="btn btn-outline btn-sm">
                                Clear
                            </button>
                        </div>

                        <ul class="max-h-64 app-table-body overflow-y-auto rounded-xl border border-charcoal/10">
                            <template x-for="item in scanned" :key="item.key">
                                <li class="flex items-start gap-3 px-3 py-2">
                                    <span class="mt-0.5 rounded-full px-2 py-0.5 text-[11px] font-bold"
                                        :class="item.type === 'Bag' ? 'bg-primary/10 text-primary' : 'bg-leaf-green/10 text-leaf-green'"
                                        x-text="item.type"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-charcoal" x-text="item.message"></p>
                                        <p class="mt-0.5 text-xs text-charcoal/50">
                                            <span class="font-mono" x-text="item.reference"></span>
                                            · <span x-text="item.time"></span>
                                        </p>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div x-show="needsRefresh" x-cloak
                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-primary/20 bg-primary/5 px-3 py-2">
                        <p class="text-xs font-medium text-charcoal/70">The incoming and recent lists are out of
                            date.</p>
                        <button type="button" @click="refreshLists()" class="btn btn-outline btn-sm">
                            Refresh lists
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-section-label">Incoming Bags</h2>

        @if ($incoming->isEmpty())
            <div class="frost-panel-solid flex h-40 flex-col items-center justify-center rounded-2xl text-center">
                <p class="text-sm font-semibold text-charcoal">No bags on the way</p>
                <p class="mt-1 text-xs text-charcoal/50">Bags dispatched to this hub will appear here.</p>
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($incoming as $bag)
                    <div class="frost-panel-solid rounded-2xl p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-base font-bold text-charcoal">{{ $bag->code }}</p>
                                <p class="mt-1 text-sm text-charcoal/70">
                                    From {{ $bag->fromHub?->business_name ?? '—' }}
                                </p>
                                <p class="mt-0.5 text-xs text-charcoal/50">
                                    Dispatched {{ $bag->dispatched_at?->format('M d, Y g:i A') ?? '—' }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $bag->status_color }}">{{ $bag->status_label }}</span>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-white/50 pt-4">
                            <p class="text-xs text-charcoal/60">
                                {{ $bag->items_count }} parcel(s)
                                @if ($bag->received_items_count > 0)
                                    · {{ $bag->received_items_count }} scanned
                                @endif
                            </p>
                            <a href="{{ route('logistics.inbound.show', $bag) }}" class="btn btn-primary">
                                Receive Bag
                            </a>
                        </div>

                        @if ($bag->flagged_at)
                            <p class="mt-2 rounded-lg bg-sale-red/10 px-3 py-2 text-xs font-semibold text-sale-red">
                                {{ $bag->flag_note }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="space-y-4">
        <h2 class="text-section-label">Recently Received</h2>

        <div class="frost-panel-solid overflow-hidden rounded-2xl">
            @if ($recent->isEmpty())
                <div class="flex h-32 flex-col items-center justify-center text-center">
                    <p class="text-sm font-semibold text-charcoal">No received bags yet</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="app-table w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Bag</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">From</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Parcels</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Status</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60">Received</th>
                                <th class="px-5 py-3 font-semibold text-charcoal/60"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent as $bag)
                                <tr class="transition hover:bg-white/40">
                                    <td class="px-5 py-4 font-mono font-bold text-charcoal">{{ $bag->code }}</td>
                                    <td class="px-5 py-4 text-charcoal/70">{{ $bag->fromHub?->business_name ?? '—' }}</td>
                                    <td class="px-5 py-4 text-charcoal/70">
                                        {{ $bag->received_items_count }} / {{ $bag->items_count }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-bold {{ $bag->status_color }}">{{ $bag->status_label }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-charcoal/60">
                                        {{ $bag->received_at?->format('M d, g:i A') ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('logistics.inbound.show', $bag) }}"
                                            class="btn btn-outline btn-sm">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div>{{ $recent->links() }}</div>
    </div>

</x-logistics.layout>
