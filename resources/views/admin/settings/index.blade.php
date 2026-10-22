@extends('admin.settings.layout')

@php
    $inputClass =
        'frost-input w-full rounded-xl px-4 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    $labelClass = 'mb-1.5 block text-sm font-semibold text-charcoal';
    $sectionTitle = 'mb-1 text-xs font-bold uppercase tracking-wide text-primary';
@endphp

@section('title', 'General Settings')
@section('subtitle', 'Platform name, support contact, and commission rate')

@section('content')
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3" x-data="{
        rate: {{ (float) old('commission_rate', $settings['commission_rate']) }},
        amount: 1000,
        fmt(n) { return '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        get fee() { return (Number(this.amount) || 0) * (Number(this.rate) || 0) / 100; },
        get payout() { return (Number(this.amount) || 0) - this.fee; }
    }">

        <!-- Form -->
        <section class="frost-panel-solid rounded-2xl p-6 xl:col-span-2">
            <p class="{{ $sectionTitle }}">General Settings</p>
            <p class="mb-5 text-xs text-charcoal/50">Basic details shown across the platform and
                used for order commissions.</p>

            <form method="POST" action="{{ route('admin.settings.general') }}" class="space-y-5">
                @csrf

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">Platform Name</label>
                        <input type="text" name="platform_name" required maxlength="100"
                            value="{{ old('platform_name', $settings['platform_name']) }}" class="{{ $inputClass }}">
                        <p class="mt-1.5 text-xs text-charcoal/45">Displayed in headers, e-mails
                            and public pages.</p>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Support E-mail</label>
                        <input type="email" name="support_email" required
                            value="{{ old('support_email', $settings['support_email']) }}" class="{{ $inputClass }}">
                        <p class="mt-1.5 text-xs text-charcoal/45">Where customers and sellers
                            can reach your team.</p>
                    </div>
                </div>

                <div class="border-t border-white/50 pt-5">
                    <label class="{{ $labelClass }}">Platform Commission Rate (%)</label>
                    <div class="relative max-w-xs">
                        <input type="number" name="commission_rate" required min="0" max="100" step="0.1"
                            x-model="rate" class="{{ $inputClass }} pr-10">
                        <span
                            class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-semibold text-charcoal/40">%</span>
                    </div>
                    <p class="mt-1.5 text-xs text-charcoal/50">
                        Applies to new orders only. Orders already placed keep the commission rate
                        that was in effect when they were created.
                    </p>
                </div>

                <div>
                    <label for="return_window_days" class="mb-2 block text-sm font-semibold text-charcoal">
                        Return/refund window (days after delivery)
                    </label>
                    <input type="number" id="return_window_days" name="return_window_days" min="1" max="30"
                        required value="{{ old('return_window_days', $settings['return_window_days']) }}"
                        class="frost-input w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10">
                    <p class="mt-1 text-xs text-charcoal/50">
                        Buyers can request a return or refund for this many days after the courier delivers. It applies to
                        new
                        requests only.
                    </p>
                </div>

                <div class="flex justify-end border-t border-white/50 pt-5">
                    <button type="submit"
                        class="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-sky-blue">
                        Save Settings
                    </button>
                </div>
            </form>
        </section>

        <!-- Sidebar -->
        <aside class="space-y-5">

            <!-- Commission preview -->
            <section class="frost-panel-solid rounded-2xl p-6">
                <p class="{{ $sectionTitle }}">Commission Preview</p>
                <p class="mb-4 text-xs text-charcoal/50">See how the rate splits an order. This is
                    only a preview until you save.</p>

                <label class="mb-1.5 block text-xs font-semibold text-charcoal/60">Sample order
                    amount</label>
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-sm font-semibold text-charcoal/40">₱</span>
                    <input type="number" min="0" step="50" x-model="amount" class="{{ $inputClass }} pl-8">
                </div>

                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-charcoal/60">Platform earns</dt>
                        <dd class="font-bold text-primary" x-text="fmt(fee)"></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-charcoal/60">Seller receives</dt>
                        <dd class="font-bold text-leaf-green" x-text="fmt(payout)"></dd>
                    </div>
                </dl>

                <div class="mt-4 flex h-2 overflow-hidden rounded-full bg-primary/10">
                    <div class="h-full bg-primary transition-all"
                        :style="`width: ${Math.min(Math.max(Number(rate) || 0, 0), 100)}%`"></div>
                </div>
                <p class="mt-1.5 text-[11px] text-charcoal/45">
                    <span x-text="(Number(rate) || 0) + '%'"></span> of each order goes to the
                    platform.
                </p>
            </section>

            <!-- At a glance -->
            <section class="frost-panel-solid rounded-2xl p-6">
                <p class="{{ $sectionTitle }}">Current Configuration</p>
                <dl class="mt-3 divide-y divide-white/60 text-sm">
                    <div class="py-2.5 first:pt-0">
                        <dt class="text-xs text-charcoal/50">Platform name</dt>
                        <dd class="mt-0.5 font-semibold">{{ $settings['platform_name'] }}</dd>
                    </div>
                    <div class="py-2.5">
                        <dt class="text-xs text-charcoal/50">Support e-mail</dt>
                        <dd class="mt-0.5 break-all font-semibold">{{ $settings['support_email'] }}</dd>
                    </div>
                    <div class="py-2.5 last:pb-0">
                        <dt class="text-xs text-charcoal/50">Commission rate</dt>
                        <dd class="mt-0.5 font-semibold">{{ $settings['commission_rate'] }}%</dd>
                    </div>
                </dl>
            </section>

        </aside>
    </div>
@endsection
