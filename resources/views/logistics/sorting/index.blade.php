@php
    $field = 'frost-input rounded-xl px-3 py-2 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';

    $cell =
        'flex items-center justify-between gap-3 px-1 py-1.5 before:text-xs before:font-medium before:text-charcoal/50 before:content-[attr(data-label)] md:table-cell md:px-5 md:py-4 md:before:hidden';

@endphp
<x-logistics.layout title="Sort & Assign" subtitle="Sort parcels by destination, then assign a delivery rider"
    active="sorting" :scripts="['resources/js/pages/logistics/sorting.js']">
    <div x-data="sortingScanner()" class="space-y-4 sm:space-y-5">
        {{-- Session data for Alpine --}}
        <script type="application/json" id="sessionData">
            {!! json_encode(['session' => $activeSession, 'serverTime' => $serverTime->toIso8601String()]) !!}
        </script>

        {{-- SESSION CONTROLS --}}
        <div class="frost-panel-solid rounded-2xl p-4 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-[200px] flex-1">
                    <div class="mb-1 flex items-center gap-2">
                        <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-bold text-charcoal">Sorting Session</h3>
                    </div>
                    <p class="text-sm text-charcoal/60">
                        <template x-if="!session">
                            <span>Start a session to enable scanning and sorting actions</span>
                        </template>
                        <template x-if="session">
                            <span>Session is active - scan parcels or sort manually</span>
                        </template>
                    </p>
                </div>

                <div class="flex w-full items-center gap-3 sm:w-auto">
                    <template x-if="!session">
                        <button type="button" @click="startSession()" :disabled="startingSession"
                            class="btn btn-primary w-full disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                            <span x-show="!startingSession" class="flex items-center justify-center gap-2">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Start Sorting
                            </span>
                            <span x-show="startingSession" class="flex items-center justify-center gap-2">
                                <svg class="h-5 w-5 animate-spin" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                                Starting...
                            </span>
                        </button>
                    </template>

                    <template x-if="session">
                        <div class="flex w-full flex-wrap items-center gap-3 sm:w-auto sm:gap-4">
                            <div class="rounded-xl bg-primary/10 px-4 py-2 text-right">
                                <div class="text-xs font-semibold uppercase tracking-wide text-charcoal/60">Session
                                    Active</div>
                                <div class="font-mono text-2xl font-bold text-primary" x-text="formattedElapsed">
                                </div>
                            </div>
                            <button type="button" @click="endSession()" :disabled="endingSession"
                                class="btn btn-danger-outline flex-1 disabled:cursor-not-allowed disabled:opacity-50 sm:flex-none">
                                <span x-show="!endingSession" class="flex items-center justify-center gap-2">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z">
                                        </path>
                                    </svg>
                                    End Session
                                </span>
                                <span x-show="endingSession" class="flex items-center justify-center gap-2">
                                    <svg class="h-5 w-5 animate-spin" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                        </path>
                                    </svg>
                                    Ending...
                                </span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <p x-show="sessionError" x-text="sessionError" x-cloak role="alert"
                class="mt-4 rounded-lg bg-sale-red/10 px-3 py-2 text-sm font-semibold text-sale-red"></p>

            {{-- Progress Bar --}}
            <template x-if="session && progress.total_scans > 0">
                <div class="mt-5 border-t border-white/50 pt-5">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-charcoal">Scan Progress</span>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs font-medium">
                            <span class="text-leaf-green">✓ <span x-text="progress.sorted"></span> sorted</span>
                            <span class="text-orange-600" x-show="progress.needs_review > 0">⚠ <span
                                    x-text="progress.needs_review"></span> review</span>
                            <span class="text-sale-red" x-show="progress.rejected > 0">✕ <span
                                    x-text="progress.rejected"></span> rejected</span>
                            <span class="text-charcoal/60" x-show="progress.duplicates > 0">⟳ <span
                                    x-text="progress.duplicates"></span> duplicate</span>
                        </div>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-light-gray shadow-inner">
                        <div class="h-full bg-primary transition-all duration-300"
                            :style="'width: ' + (progress.total_scans > 0 ? (progress.sorted / progress.total_scans * 100) :
                                0) + '%'">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- SCANNER SECTION (only visible when session is active) --}}
        <template x-if="session">
            <div class="frost-panel-solid rounded-2xl p-4 sm:p-6">
                <div class="mb-5 flex items-center gap-2">
                    <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                        </path>
                    </svg>
                    <h3 class="text-lg font-bold text-charcoal">Scan Parcels</h3>
                </div>

                <div class="grid gap-5 lg:grid-cols-2 lg:gap-6">
                    {{-- Camera Scanner --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <label class="flex items-center gap-2 text-sm font-semibold text-charcoal">
                                <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Camera Scanner
                            </label>
                            <button type="button" @click="isScanning ? stopScanner() : startScanner()"
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

                        <div id="scanner-container"
                            class="min-h-[300px] overflow-hidden rounded-xl bg-charcoal/5 shadow-inner"
                            x-show="isScanning" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"></div>

                        <p x-show="cameraError" x-text="cameraError" x-cloak role="alert"
                            class="rounded-lg bg-sale-red/10 px-3 py-2 text-xs font-semibold text-sale-red"></p>

                        <div x-show="!isScanning" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
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
                                <p class="mt-1 text-xs text-charcoal/50">Click "Start Camera" above to scan</p>
                            </div>
                        </div>
                    </div>

                    {{-- Manual Input & Last Result --}}
                    <div class="space-y-4">
                        <div>
                            <label class="mb-3 flex items-center gap-2 text-sm font-semibold text-charcoal">
                                <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                Manual Entry
                            </label>
                            <form @submit.prevent="handleManualInput()" class="space-y-3">
                                <input x-model="manualCode" type="text"
                                    placeholder="Enter waybill code or order number"
                                    aria-label="Tracking code or order ID" :disabled="scanning"
                                    class="{{ $field }} w-full disabled:opacity-50">
                                <button type="submit" :disabled="!manualCode.trim() || scanning"
                                    class="btn btn-primary w-full disabled:cursor-not-allowed disabled:opacity-50">
                                    <span x-show="!scanning" class="flex items-center justify-center gap-2">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7"></path>
                                        </svg>
                                        Submit Code
                                    </span>
                                    <span x-show="scanning" class="flex items-center justify-center gap-2">
                                        <svg class="h-5 w-5 animate-spin" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                        Processing...
                                    </span>
                                </button>
                            </form>
                        </div>

                        {{-- Last Scan Result --}}
                        <template x-if="lastResult">
                            <div class="rounded-xl border-2 p-4" role="status"
                                :class="{
                                    'border-leaf-green/30 bg-leaf-green/5': lastResult.result === 'sorted',
                                    'border-orange-600/30 bg-orange-600/5': lastResult.result === 'needs_review' ||
                                        lastResult.result === 'duplicate',
                                    'border-sale-red/30 bg-sale-red/5': lastResult.result === 'rejected'
                                }">
                                <div class="mb-2 flex items-start gap-3">
                                    <svg class="mt-0.5 h-6 w-6 shrink-0" :class="lastResult.color" fill="currentColor"
                                        viewBox="0 0 20 20" aria-hidden="true">
                                        <path x-show="lastResult.result === 'sorted'" fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"></path>
                                        <path x-show="lastResult.result !== 'sorted'" fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-bold uppercase tracking-wide"
                                            :class="lastResult.color" x-text="lastResult.result.replace('_', ' ')">
                                        </div>
                                        <p class="mt-1 text-sm text-charcoal" x-text="lastResult.message"></p>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                            <template x-if="lastResult.order_reference">
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-charcoal/10 px-2 py-0.5 font-mono font-semibold text-charcoal">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                                        </path>
                                                    </svg>
                                                    <span x-text="lastResult.order_reference"></span>
                                                </span>
                                            </template>
                                            <template x-if="lastResult.destination">
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-charcoal/10 px-2 py-0.5 font-medium text-charcoal">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                                        </path>
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z">
                                                        </path>
                                                    </svg>
                                                    <span x-text="lastResult.destination"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        {{-- TABS --}}
        <div class="frost-panel-solid flex overflow-hidden rounded-2xl">
            @foreach (['to_sort' => 'To Sort', 'to_assign' => 'To Assign'] as $key => $label)
                <a href="{{ route('logistics.sorting.index', ['tab' => $key]) }}"
                    @if ($tab === $key) aria-current="page" @endif
                    class="flex flex-1 items-center justify-center gap-2 border-b-2 px-4 py-4 text-sm font-bold transition
                        {{ $tab === $key
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-transparent text-charcoal/50 hover:bg-ice-blue hover:text-charcoal' }}">
                    {{ $label }}
                    @if ($counts[$key] > 0)
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold {{ $tab === $key ? 'bg-primary text-white' : 'bg-light-gray text-charcoal/50' }}">
                            {{ $counts[$key] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- EMPTY STATE --}}
        @if ($orders->isEmpty())
            <div class="frost-panel-solid flex h-64 flex-col items-center justify-center rounded-2xl px-4 text-center">
                <svg class="h-16 w-16 text-charcoal/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                    </path>
                </svg>
                <p class="mt-4 text-sm font-semibold text-charcoal">Nothing here</p>
                <p class="mt-1 text-xs text-charcoal/50">
                    {{ $tab === 'to_sort'
                        ? 'Parcels appear once you confirm their arrival under Incoming Parcels.'
                        : 'Sorted parcels waiting for a delivery rider appear here.' }}
                </p>
            </div>

            {{-- TO SORT --}}
        @elseif ($tab === 'to_sort')
            <div class="flex justify-end">
                <form method="POST" action="{{ route('logistics.sorting.sort-all') }}" class="w-full sm:w-auto"
                    @submit="sortAll($event)">
                    @csrf
                    <button type="submit" :disabled="!session || sortingAll"
                        :aria-busy="sortingAll ? 'true' : 'false'"
                        class="btn btn-primary w-full disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                        :title="!session ? 'Start a session first' : ''">
                        <span x-show="!sortingAll" class="flex items-center justify-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Sort all {{ $orders->count() }} parcel(s)
                        </span>
                        <span x-show="sortingAll" class="flex items-center justify-center gap-2">
                            <svg class="h-5 w-5 animate-spin" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            Sorting...
                        </span>
                    </button>
                </form>
            </div>

            <div class="frost-panel-solid overflow-hidden rounded-2xl">
                <div class="overflow-x-auto">
                    <table class="app-table block w-full text-left text-sm md:table">
                        <thead class="hidden md:table-header-group">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Order</th>
                                <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Destination</th>
                                <th scope="col" class="px-5 py-3 font-semibold text-charcoal/60">Zone / Rider</th>
                                <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>

                        <tbody class="block space-y-3 p-3 md:table-row-group md:space-y-0 md:p-0">
                            @foreach ($orders as $order)
                                @php
                                    $dest = $order->ship_to;
                                    $area = $areas->get(
                                        \App\Models\HubServiceArea::keyFor($dest['province'], $dest['municipality']),
                                    );
                                    $destinationHub = $destinations[$order->id] ?? null;
                                    $sameHub = $destinationHub && (int) $destinationHub->id === (int) $hub->id;
                                    $suggestedRider = $suggested[$order->id] ?? null;
                                @endphp
                                <tr
                                    class="block rounded-xl border border-white/50 bg-white/30 p-3 transition hover:bg-primary/5 md:table-row md:rounded-none md:border-0 md:bg-transparent md:p-0">

                                    <td
                                        class="block px-1 pb-1 text-base font-bold text-primary md:table-cell md:px-5 md:py-4 md:text-sm">
                                        #{{ $order->order_number }}
                                    </td>

                                    <td data-label="Destination" class="{{ $cell }} text-charcoal">
                                        <div class="min-w-0 text-right md:text-left">
                                            <div class="font-medium">{{ $dest['municipality'] ?? '—' }},
                                                {{ $dest['province'] ?? '' }}</div>
                                            <div class="mt-0.5 text-xs text-charcoal/50">
                                                Brgy. {{ $dest['barangay'] ?? '—' }}
                                            </div>
                                        </div>
                                    </td>

                                    <td data-label="Zone / Rider" class="{{ $cell }}">
                                        <div class="min-w-0 text-right md:text-left">
                                            @if ($destinationHub && $sameHub)
                                                <div class="font-semibold text-charcoal">
                                                    {{ $area?->zone_name ?: $area?->municipality ?: $dest['municipality'] }}
                                                </div>
                                                <div class="mt-0.5 text-xs text-charcoal/60">
                                                    {{ $suggestedRider?->full_name ?? 'No rider for this barangay' }}
                                                </div>
                                            @elseif ($destinationHub)
                                                <div class="font-semibold text-orange-600">
                                                    Transfer to {{ $destinationHub->business_name }}
                                                </div>
                                                <div class="mt-0.5 text-xs text-charcoal/60">
                                                    Destination hub · {{ $destinationHub->company_name }}
                                                </div>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-orange-600/10 px-2 py-1 text-xs font-semibold text-orange-600">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"
                                                        aria-hidden="true">
                                                        <path fill-rule="evenodd"
                                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                            clip-rule="evenodd"></path>
                                                    </svg>
                                                    No destination hub
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="block pt-2 md:table-cell md:px-5 md:py-4 md:pt-4 md:text-right">
                                        @if ($destinationHub)
                                            <form method="POST"
                                                action="{{ route('logistics.sorting.sort', $order) }}" data-loading>
                                                @csrf
                                                <button type="submit" :disabled="!session"
                                                    class="btn btn-primary btn-sm w-full disabled:cursor-not-allowed disabled:opacity-50 md:w-auto"
                                                    :title="!session ? 'Start a session first' : ''">
                                                    {{ $sameHub ? 'Sort' : 'Sort for transfer' }}
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('logistics.areas.index') }}"
                                                class="text-xs font-semibold text-orange-600 hover:underline">
                                                Check service areas →
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TO ASSIGN --}}
        @else
            <div class="space-y-4 sm:space-y-5">
                {{-- NEEDS REVIEW SECTION --}}
                @if ($needsReviewOrders->isNotEmpty())
                    <div class="frost-panel-solid rounded-2xl border-2 border-orange-600/30 p-4 sm:p-6">
                        <div class="mb-5">
                            <div class="mb-1 flex items-center gap-2">
                                <svg class="h-6 w-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20"
                                    aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <p class="text-base font-bold text-orange-600">⚠ Needs Review</p>
                            </div>
                            <p class="text-sm text-charcoal/70">
                                {{ $needsReviewOrders->count() }} parcel(s) flagged during scanning require manual
                                review
                            </p>
                        </div>

                        <div class="app-table-body">
                            @foreach ($needsReviewOrders as $order)
                                @php
                                    $dest = $order->ship_to;
                                    $area = $areas->get(
                                        \App\Models\HubServiceArea::keyFor($dest['province'], $dest['municipality']),
                                    );
                                    $rider = $suggested[$order->id] ?? null;
                                    $destinationHub = $destinations[$order->id] ?? null;
                                    $assignableHere = $destinationHub && (int) $destinationHub->id === (int) $hub->id;
                                @endphp
                                <div
                                    class="flex flex-wrap items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-charcoal">Order #{{ $order->order_number }}</p>
                                        <p class="mt-1 text-sm text-charcoal/70">
                                            {{ $dest['name'] }}
                                            · {{ $dest['municipality'] }}, {{ $dest['province'] }}
                                            · Brgy. {{ $dest['barangay'] }}
                                        </p>
                                        @if (!$destinationHub)
                                            <p class="mt-2 text-xs font-semibold text-orange-600">
                                                ⚠ No hub in your company serves this barangay
                                            </p>
                                        @elseif (!$assignableHere)
                                            <p class="mt-2 text-xs font-semibold text-orange-600">
                                                ⚠ Destination hub: {{ $destinationHub->business_name }} · transfer
                                                required
                                            </p>
                                        @endif
                                    </div>

                                    @if ($assignableHere)
                                        <form method="POST" action="{{ route('logistics.sorting.assign', $order) }}"
                                            class="flex w-full items-center gap-2 sm:w-auto" data-loading>
                                            @csrf
                                            <select name="rider_id" required aria-label="Select rider"
                                                class="{{ $field }} min-w-0 flex-1 sm:flex-none">
                                                <option value="" disabled @selected(!$rider)>Select
                                                    rider…
                                                </option>
                                                @foreach ($riders as $r)
                                                    <option value="{{ $r->id }}" @selected($rider?->id === $r->id)>
                                                        {{ $r->full_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" @disabled($riders->isEmpty())
                                                class="btn btn-primary btn-sm shrink-0 disabled:cursor-not-allowed disabled:opacity-50">
                                                Assign
                                            </button>
                                        </form>
                                    @elseif (!$destinationHub)
                                        <a href="{{ route('logistics.areas.index') }}"
                                            class="text-xs font-semibold text-orange-600 hover:underline">
                                            Add service area →
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- REGULAR GROUPS --}}
                @foreach ($groups as $group)
                    @php
                        $area = $group['area'];
                        $dest = $group['dest'];
                        $rider = $group['rider'];
                        $destinationHub = $group['hub'];
                        $assignableHere = $destinationHub && (int) $destinationHub->id === (int) $hub->id;
                    @endphp
                    <div class="frost-panel-solid rounded-2xl p-4 sm:p-6">
                        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <p class="flex items-center gap-2 text-base font-bold text-charcoal">
                                    <svg class="h-5 w-5 shrink-0 text-primary" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span
                                        class="min-w-0 truncate">{{ $destinationHub?->business_name ?? 'No destination hub' }}</span>
                                </p>
                                <p class="mt-1 text-xs text-charcoal/60">
                                    {{ $group['orders']->count() }} parcel(s)
                                    @if ($group['barangays']->isNotEmpty())
                                        · Brgy. {{ $group['barangays']->implode(', ') }}
                                    @endif
                                    · {{ $dest['municipality'] ?? 'Unknown destination' }}, {{ $dest['province'] }}
                                </p>
                                @if ($destinationHub?->company_name)
                                    <p class="mt-1 text-[11px] text-charcoal/50">
                                        {{ $destinationHub->company_name }}
                                        @if ($area?->zone_name)
                                            · Zone: {{ $area->zone_name }}
                                        @endif
                                    </p>
                                @endif
                                <p class="mt-2 text-xs">
                                    @if (!$destinationHub)
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-orange-600/10 px-2 py-1 font-medium text-orange-600">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"
                                                aria-hidden="true">
                                                <path fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                            No hub in your company serves this barangay
                                        </span>
                                    @elseif (!$area)
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-orange-600/10 px-2 py-1 font-medium text-orange-600">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"
                                                aria-hidden="true">
                                                <path fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                            Not in your service areas
                                        </span>
                                    @elseif ($rider)
                                        <span class="inline-flex items-center gap-1 font-medium text-leaf-green">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"
                                                aria-hidden="true">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                            Mapped rider: <b>{{ $rider->full_name }}</b>
                                        </span>
                                    @else
                                        <span class="font-medium text-orange-600">
                                            No rider mapped for these barangays
                                        </span>
                                    @endif
                                </p>
                            </div>

                            @if ($rider && $assignableHere)
                                <form method="POST" action="{{ route('logistics.sorting.assign-area') }}"
                                    class="w-full sm:w-auto" data-loading>
                                    @csrf
                                    <input type="hidden" name="rider_id" value="{{ $rider->id }}">
                                    @foreach ($group['orders'] as $groupOrder)
                                        <input type="hidden" name="order_ids[]" value="{{ $groupOrder->order_number }}">
                                    @endforeach
                                    <button type="submit" class="btn btn-secondary w-full sm:w-auto">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                                            </path>
                                        </svg>
                                        Assign all {{ $group['orders']->count() }} to {{ $rider->first_name }}
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="app-table-body">
                            @foreach ($group['orders'] as $order)
                                <div
                                    class="flex flex-wrap items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-charcoal">Order #{{ $order->order_number }}</p>
                                        <p class="mt-1 text-sm text-charcoal/60">
                                            {{ $order->ship_to['name'] }}
                                            · Brgy. {{ $order->ship_to['barangay'] }}
                                            · {{ $order->items->sum('quantity') }} item(s)
                                        </p>
                                    </div>

                                    @if ($assignableHere)
                                        <form method="POST" action="{{ route('logistics.sorting.assign', $order) }}"
                                            class="flex w-full items-center gap-2 sm:w-auto" data-loading>
                                            @csrf
                                            <select name="rider_id" required aria-label="Select rider"
                                                class="{{ $field }} min-w-0 flex-1 sm:flex-none">
                                                <option value="" disabled @selected(!$rider)>Select
                                                    rider…
                                                </option>
                                                @foreach ($riders as $r)
                                                    <option value="{{ $r->id }}" @selected($rider?->id === $r->id)>
                                                        {{ $r->full_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" @disabled($riders->isEmpty())
                                                class="btn btn-primary btn-sm shrink-0 disabled:cursor-not-allowed disabled:opacity-50">
                                                Assign
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-logistics.layout>
