@extends('admin.settings.layout')

@php
    $inputClass =
        'frost-input w-full rounded-xl py-3 pl-10 pr-4 text-sm font-semibold text-charcoal outline-none transition focus:ring-4 focus:ring-primary/10';

    $sectionTitle = 'text-xs font-bold uppercase tracking-[0.12em] text-primary';

    $zoneDescriptions = [
        'same_municipality' => 'Seller and buyer are in the same city or municipality.',
        'same_province' => 'Seller and buyer are in the same province, but different cities or municipalities.',
        'different_province' => 'Seller and buyer are located in different provinces.',
    ];
@endphp

@section('title', 'Shipping Settings')
@section('subtitle', 'Manage distance-based shipping fees for every seller parcel.')

@section('content')
    <div class="space-y-6">
        {{-- INFORMATION CARDS --}}
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">

            {{-- Per parcel --}}
            <div class="frost-panel-solid flex items-start gap-4 rounded-2xl p-5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <path d="M3 10h18" />
                        <path d="M7 15h3" />
                    </svg>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-charcoal">Per-parcel fee</h3>
                    <p class="mt-1 text-xs leading-5 text-charcoal/60">
                        Shipping is charged per seller parcel, not as one combined fee for the entire order.
                    </p>
                </div>
            </div>

            {{-- Protected orders --}}
            <div class="frost-panel-solid flex items-start gap-4 rounded-2xl p-5">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-leaf-green/10 text-leaf-green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-charcoal">Existing orders are protected</h3>
                    <p class="mt-1 text-xs leading-5 text-charcoal/60">
                        Rate updates affect new orders only. Existing orders keep their original shipping fees.
                    </p>
                </div>
            </div>
        </section>

        {{-- SHIPPING RATE FORM --}}
        <section class="frost-panel-solid overflow-hidden rounded-2xl">

            {{-- Section heading --}}
            <div
                class="flex flex-col gap-3 border-b border-white/60 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div>
                    <p class="{{ $sectionTitle }}">Rate Configuration</p>
                    <h2 class="mt-1 text-lg font-bold text-charcoal">
                        Shipping rates by distance
                    </h2>
                    <p class="mt-1 text-xs leading-5 text-charcoal/50">
                        Enter the shipping fee in Philippine pesos (₱) for each delivery zone.
                    </p>
                </div>

                <div
                    class="inline-flex w-fit items-center gap-2 rounded-lg bg-light-gray px-3 py-2 text-xs font-semibold text-charcoal/60">
                    {{-- Wallet icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <rect x="3" y="5" width="18" height="15" rx="2" />
                        <path d="M3 9h18" />
                        <path d="M16 14h.01" />
                        <path d="M3 5.5 17 3" />
                    </svg>
                    Currency: PHP (₱)
                </div>
            </div>

            <form method="POST" action="{{ route('admin.settings.shipping') }}">
                @csrf

                <div class="space-y-4 p-5 sm:p-6">
                    @foreach ($shippingRates as $rate)
                        <div
                            class="group rounded-2xl border border-charcoal/5 bg-white/50 p-4 transition hover:border-primary/20 hover:bg-white/70 sm:p-5">

                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                                {{-- Zone icon and information --}}
                                <div class="flex min-w-0 items-start gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white">

                                        @if ($rate->zone === 'same_municipality')
                                            {{-- Map pin icon --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" />
                                                <circle cx="12" cy="10" r="2.5" />
                                            </svg>
                                        @elseif ($rate->zone === 'same_province')
                                            {{-- Map icon --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                aria-hidden="true">
                                                <path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z" />
                                                <path d="M9 3v15" />
                                                <path d="M15 6v15" />
                                            </svg>
                                        @else
                                            {{-- Route icon --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                aria-hidden="true">
                                                <circle cx="6" cy="19" r="3" />
                                                <path d="M9 19h8a3 3 0 0 0 0-6h-2a3 3 0 0 1 0-6h3" />
                                                <circle cx="18" cy="4" r="3" />
                                            </svg>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold text-charcoal">
                                            {{ $rate->label }}
                                        </h3>

                                        <p class="mt-1 text-xs leading-5 text-charcoal/55">
                                            {{ $zoneDescriptions[$rate->zone] ?? 'Shipping fee based on the seller and buyer locations.' }}
                                        </p>

                                        <div
                                            class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-semibold text-leaf-green">
                                            {{-- Check circle icon --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                aria-hidden="true">
                                                <circle cx="12" cy="12" r="10" />
                                                <path d="m8 12 2.5 2.5L16 9" />
                                            </svg>
                                            Configured rate
                                        </div>
                                    </div>
                                </div>

                                {{-- Fee input --}}
                                <div class="w-full sm:w-44 sm:shrink-0">
                                    <label for="rate_{{ $rate->zone }}"
                                        class="mb-1.5 block text-xs font-semibold text-charcoal/65">
                                        Fee per parcel
                                    </label>

                                    <div class="relative">
                                        {{-- Peso icon --}}
                                        <span
                                            class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-sm font-bold text-charcoal/40">
                                            ₱
                                        </span>

                                        <input id="rate_{{ $rate->zone }}" type="number"
                                            name="rates[{{ $rate->zone }}]" required min="0" max="100000"
                                            step="1" value="{{ old("rates.{$rate->zone}", (float) $rate->fee) }}"
                                            aria-describedby="rate_help_{{ $rate->zone }}" class="{{ $inputClass }}">
                                    </div>

                                    <p id="rate_help_{{ $rate->zone }}" class="mt-1.5 text-[11px] text-charcoal/40">
                                        Whole pesos only
                                    </p>

                                    @error("rates.{$rate->zone}")
                                        <p class="mt-1 text-xs font-medium text-sale-red">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Form footer --}}
                <div
                    class="flex flex-col gap-4 border-t border-white/60 bg-light-gray/50 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="flex items-start gap-2 text-charcoal/50">
                        {{-- Info icon --}}
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round" class="mt-0.5 shrink-0" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M12 16v-4" />
                            <path d="M12 8h.01" />
                        </svg>

                        <p class="max-w-md text-xs leading-5">
                            Changes apply to new orders after saving. Previously placed orders retain their original
                            shipping fees.
                        </p>
                    </div>

                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-sky-blue focus:outline-none focus:ring-4 focus:ring-primary/20 active:scale-[0.98] sm:w-auto">

                        {{-- Save icon --}}
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" />
                            <path d="M17 21v-8H7v8" />
                            <path d="M7 3v5h8" />
                        </svg>

                        Save Shipping Rates
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
