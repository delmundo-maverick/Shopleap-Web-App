<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Global Platform Settings | Shopleap Admin</title>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen text-charcoal" x-data="{ announcementModal: false, editingAnnouncement: null }">
    <div class="frost-surface"></div>

    @php
        $inputClass =
            'frost-input w-full rounded-xl px-4 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
        $labelClass = 'mb-1.5 block text-sm font-semibold text-charcoal';
        $sectionTitle = 'mb-1 text-xs font-bold uppercase tracking-wide text-primary';
        $audienceLabels = [
            'all' => 'Everyone',
            'buyer' => 'Buyers',
            'seller' => 'Sellers',
            'logistics' => 'Logistics',
            'courier' => 'Riders',
        ];
        $tabs = [
            'general' => [
                'label' => 'General',
                'icon' =>
                    'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
            ],
            'announcements' => [
                'label' => 'Announcements',
                'icon' =>
                    'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
            ],
            'policies' => [
                'label' => 'Policies',
                'icon' =>
                    'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            ],
        ];
    @endphp

    <div class="flex min-h-screen">
        <x-navbars.admin active="settings" />

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <button type="button" data-sidebar-toggle
                        class="frost-btn rounded-lg p-2 text-charcoal/60 transition hover:bg-ice-blue hover:text-primary lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Global Platform Settings</h2>
                        <p class="text-sm text-charcoal/55">General configuration, announcements, and policies</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-6">
                <div class="w-full space-y-5">

                    @if (session('status'))
                        <div
                            class="frost-panel rounded-2xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                            {{ session('status') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div
                            class="frost-panel rounded-2xl border border-sale-red/20 bg-sale-red/10 px-4 py-3 text-sm text-sale-red">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <!-- TABS -->
                    <nav class="frost-panel-solid flex gap-1.5 overflow-x-auto rounded-2xl p-1.5">
                        @foreach ($tabs as $key => $item)
                            <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
                                class="flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-semibold transition
                                {{ $tab === $key ? 'bg-primary text-white shadow' : 'text-charcoal/60 hover:bg-ice-blue hover:text-primary' }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="{{ $item['icon'] }}" />
                                </svg>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    @if ($tab === 'general')

                        <!-- ===================== GENERAL ===================== -->
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
                                                value="{{ old('platform_name', $settings['platform_name']) }}"
                                                class="{{ $inputClass }}">
                                            <p class="mt-1.5 text-xs text-charcoal/45">Displayed in headers, e-mails
                                                and public pages.</p>
                                        </div>
                                        <div>
                                            <label class="{{ $labelClass }}">Support E-mail</label>
                                            <input type="email" name="support_email" required
                                                value="{{ old('support_email', $settings['support_email']) }}"
                                                class="{{ $inputClass }}">
                                            <p class="mt-1.5 text-xs text-charcoal/45">Where customers and sellers
                                                can reach your team.</p>
                                        </div>
                                    </div>

                                    <div class="border-t border-white/50 pt-5">
                                        <label class="{{ $labelClass }}">Platform Commission Rate (%)</label>
                                        <div class="relative max-w-xs">
                                            <input type="number" name="commission_rate" required min="0"
                                                max="100" step="0.1" x-model="rate"
                                                class="{{ $inputClass }} pr-10">
                                            <span
                                                class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-semibold text-charcoal/40">%</span>
                                        </div>
                                        <p class="mt-1.5 text-xs text-charcoal/50">
                                            Applies to new orders only. Orders already placed keep the commission rate
                                            that was in effect when they were created.
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
                                        <input type="number" min="0" step="50" x-model="amount"
                                            class="{{ $inputClass }} pl-8">
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
                                            <dd class="mt-0.5 break-all font-semibold">
                                                {{ $settings['support_email'] }}
                                            </dd>
                                        </div>
                                        <div class="py-2.5 last:pb-0">
                                            <dt class="text-xs text-charcoal/50">Commission rate</dt>
                                            <dd class="mt-0.5 font-semibold">{{ $settings['commission_rate'] }}%</dd>
                                        </div>
                                    </dl>
                                </section>

                            </aside>
                        </div>
                    @elseif ($tab === 'announcements')
                        <!-- ===================== ANNOUNCEMENTS ===================== -->
                        <section class="frost-panel-solid overflow-hidden rounded-2xl">
                            <div
                                class="flex flex-col gap-3 border-b border-white/50 p-6 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="{{ $sectionTitle }}">Announcements</p>
                                    <p class="text-xs text-charcoal/50">
                                        Banners shown to selected audiences on their dashboards.
                                        @php
                                            $announcementTotal = method_exists($announcements, 'total')
                                                ? $announcements->total()
                                                : $announcements->count();
                                        @endphp
                                        <span class="font-semibold text-charcoal/70">{{ $announcementTotal }}
                                            total</span>
                                    </p>
                                </div>

                                <button type="button" @click="announcementModal = true; editingAnnouncement = null"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Post Announcement
                                </button>
                            </div>

                            @if ($announcements->isEmpty())
                                <div class="flex min-h-64 flex-col items-center justify-center gap-3 p-8 text-center">
                                    <div
                                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                                d="{{ $tabs['announcements']['icon'] }}" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-charcoal">No announcements yet</p>
                                        <p class="mt-1 text-xs text-charcoal/50">Post one to notify buyers, sellers,
                                            logistics or riders.</p>
                                    </div>
                                    <button type="button"
                                        @click="announcementModal = true; editingAnnouncement = null"
                                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                                        + Post Announcement
                                    </button>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[720px] text-left text-sm">
                                        <thead class="bg-primary/5 text-xs uppercase tracking-wide text-charcoal/55">
                                            <tr>
                                                <th class="px-6 py-3 font-semibold">Announcement</th>
                                                <th class="px-6 py-3 font-semibold">Audience</th>
                                                <th class="px-6 py-3 font-semibold">Window</th>
                                                <th class="px-6 py-3 font-semibold">Status</th>
                                                <th class="px-6 py-3"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-white/50">
                                            @foreach ($announcements as $a)
                                                <tr class="transition hover:bg-white/40">
                                                    <td class="max-w-md px-6 py-4">
                                                        <p class="font-semibold text-charcoal">{{ $a->title }}</p>
                                                        <p class="line-clamp-1 text-xs text-charcoal/50">
                                                            {{ $a->body }}</p>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span
                                                            class="rounded-full bg-ice-blue px-2.5 py-1 text-xs font-semibold text-primary">
                                                            {{ $audienceLabels[$a->audience] ?? $a->audience }}
                                                        </span>
                                                    </td>
                                                    <td class="whitespace-nowrap px-6 py-4 text-xs text-charcoal/55">
                                                        {{ $a->starts_at?->format('M d, Y') ?? 'Now' }} –
                                                        {{ $a->ends_at?->format('M d, Y') ?? 'No end date' }}
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span
                                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $a->is_active ? 'bg-leaf-green/10 text-leaf-green' : 'bg-light-gray text-charcoal/50' }}">
                                                            <span
                                                                class="h-1.5 w-1.5 rounded-full {{ $a->is_active ? 'bg-leaf-green' : 'bg-charcoal/30' }}"></span>
                                                            {{ $a->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <div class="flex justify-end gap-2">
                                                            <button type="button"
                                                                @click="announcementModal = true; editingAnnouncement = @js($a)"
                                                                class="rounded-lg bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary/20">
                                                                Edit
                                                            </button>
                                                            <form method="POST"
                                                                action="{{ route('admin.settings.announcements.toggle', $a) }}">
                                                                @csrf
                                                                <button type="submit"
                                                                    class="rounded-lg bg-light-gray px-3 py-1.5 text-xs font-semibold text-charcoal/70 transition hover:bg-charcoal/10">
                                                                    {{ $a->is_active ? 'Deactivate' : 'Activate' }}
                                                                </button>
                                                            </form>
                                                            <form method="POST"
                                                                action="{{ route('admin.settings.announcements.destroy', $a) }}"
                                                                onsubmit="return confirm('Delete this announcement?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="rounded-lg bg-sale-red/10 px-3 py-1.5 text-xs font-semibold text-sale-red transition hover:bg-sale-red/20">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="border-t border-white/50 p-4">
                                    {{ $announcements->onEachSide(1)->links() }}
                                </div>
                            @endif
                        </section>
                    @else
                        <!-- ===================== POLICIES ===================== -->
                        <div class="frost-panel-solid rounded-2xl p-6">
                            <p class="{{ $sectionTitle }}">Policies</p>
                            <p class="text-xs text-charcoal/50">
                                Public legal pages. Every save creates a new version so changes are tracked.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">
                            @forelse ($policies as $policy)
                                <div class="frost-panel-solid rounded-2xl p-6">
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="1.8" d="{{ $tabs['policies']['icon'] }}" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-bold text-charcoal">{{ $policy->title }}</p>
                                                <span
                                                    class="rounded-full bg-ice-blue px-2 py-0.5 text-[11px] font-semibold text-primary">v{{ $policy->version }}</span>
                                            </div>
                                            <p class="mt-0.5 text-xs text-charcoal/50">
                                                {{ $policy->editor?->name ? "Last updated by {$policy->editor->name}, " : 'Updated ' }}{{ $policy->updated_at->diffForHumans() }}
                                            </p>
                                            <a href="{{ route('policies.show', $policy) }}" target="_blank"
                                                class="mt-1.5 inline-block text-xs font-semibold text-primary hover:underline">
                                                View public page →
                                            </a>
                                        </div>
                                    </div>

                                    <details class="group mt-4 border-t border-white/50 pt-4">
                                        <summary
                                            class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded-lg bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary/20">
                                            <svg class="h-3.5 w-3.5 transition group-open:rotate-90" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2.5" d="M9 5l7 7-7 7" />
                                            </svg>
                                            Edit policy
                                        </summary>
                                        <form method="POST"
                                            action="{{ route('admin.settings.policies.update', $policy) }}"
                                            class="mt-4 space-y-3">
                                            @csrf
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-semibold text-charcoal/60">Title</label>
                                                <input type="text" name="title" required maxlength="150"
                                                    value="{{ $policy->title }}" class="{{ $inputClass }}">
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-semibold text-charcoal/60">Content</label>
                                                <textarea name="body" rows="12" required class="{{ $inputClass }} resize-y">{{ $policy->body }}</textarea>
                                            </div>
                                            <div class="flex justify-end">
                                                <button type="submit"
                                                    class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                                                    Save (creates v{{ $policy->version + 1 }})
                                                </button>
                                            </div>
                                        </form>
                                    </details>
                                </div>
                            @empty
                                <div
                                    class="frost-panel-solid flex min-h-48 items-center justify-center rounded-2xl p-8 text-sm text-charcoal/50 xl:col-span-2">
                                    No policies found.
                                </div>
                            @endforelse
                        </div>

                    @endif

                </div>
            </main>
        </div>
    </div>

    <!-- ANNOUNCEMENT MODAL -->
    <div x-show="announcementModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-charcoal/50 px-4 py-6 backdrop-blur-sm">
        <div @click.outside="announcementModal = false" x-transition
            class="w-full max-w-xl rounded-2xl border border-light-gray bg-white p-6 shadow-2xl">

            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-charcoal"
                        x-text="editingAnnouncement ? 'Edit Announcement' : 'Post Announcement'"></h3>
                    <p class="mt-0.5 text-xs text-charcoal/50">Shown as a banner on the selected audience's dashboard.
                    </p>
                </div>
                <button type="button" @click="announcementModal = false"
                    class="rounded-lg p-1.5 text-charcoal/40 transition hover:bg-light-gray hover:text-charcoal">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST"
                :action="editingAnnouncement ? `/admin/settings/announcements/${editingAnnouncement.id}` :
                    '{{ route('admin.settings.announcements.store') }}'"
                class="mt-5 space-y-4">
                @csrf

                <div>
                    <label class="mb-1 block text-sm font-semibold">Title</label>
                    <input type="text" name="title" required maxlength="150"
                        :value="editingAnnouncement?.title ?? ''" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Message</label>
                    <textarea name="body" rows="4" required maxlength="1000" class="{{ $inputClass }} resize-none"
                        x-text="editingAnnouncement?.body ?? ''"></textarea>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Audience</label>
                        <select name="audience" required class="{{ $inputClass }}">
                            @foreach ($audienceLabels as $key => $label)
                                <option value="{{ $key }}"
                                    :selected="editingAnnouncement?.audience === '{{ $key }}'">
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2 pb-2.5">
                        <input type="checkbox" name="is_active" value="1" id="announcementActive"
                            class="h-4 w-4 accent-primary"
                            :checked="editingAnnouncement ? editingAnnouncement.is_active : true">
                        <label for="announcementActive" class="text-sm font-semibold">Active</label>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-charcoal/60">Starts (optional)</label>
                        <input type="date" name="starts_at" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-charcoal/60">Ends (optional)</label>
                        <input type="date" name="ends_at" class="{{ $inputClass }}">
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-light-gray pt-4">
                    <button type="button" @click="announcementModal = false"
                        class="rounded-xl border border-light-gray px-4 py-2.5 text-sm font-semibold text-charcoal/60 transition hover:bg-light-gray">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>
    