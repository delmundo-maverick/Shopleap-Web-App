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
        $audienceLabels = [
            'all' => 'Everyone',
            'buyer' => 'Buyers',
            'seller' => 'Sellers',
            'logistics' => 'Logistics',
            'courier' => 'Riders',
        ];
    @endphp

    <div class="flex min-h-screen">
        <x-navbars.admin active="settings" />

        <div class="flex flex-1 flex-col">
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

            <main class="flex-1 space-y-5 p-6">

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
                <div class="frost-panel-solid flex rounded-2xl">
                    @foreach (['general' => 'General', 'announcements' => 'Announcements', 'policies' => 'Policies'] as $key => $label)
                        <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
                            class="flex flex-1 items-center justify-center border-b-2 px-3 py-3.5 text-sm font-semibold transition
                                {{ $tab === $key ? 'border-primary text-primary' : 'border-transparent text-charcoal/50 hover:text-charcoal' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                @if ($tab === 'general')

                    <div class="frost-panel-solid max-w-xl rounded-2xl p-6">
                        <p class="mb-4 text-xs font-bold uppercase tracking-wide text-primary">General Settings</p>

                        <form method="POST" action="{{ route('admin.settings.general') }}" class="space-y-5">
                            @csrf
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">Platform Name</label>
                                <input type="text" name="platform_name" required maxlength="100"
                                    value="{{ old('platform_name', $settings['platform_name']) }}"
                                    class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">Support E-mail</label>
                                <input type="email" name="support_email" required
                                    value="{{ old('support_email', $settings['support_email']) }}"
                                    class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">Platform Commission Rate (%)</label>
                                <input type="number" name="commission_rate" required min="0" max="100"
                                    step="0.1" value="{{ old('commission_rate', $settings['commission_rate']) }}"
                                    class="{{ $inputClass }}">
                                <p class="mt-1.5 text-xs text-charcoal/50">
                                    Applies to new orders only. Orders already placed keep the commission rate that was
                                    in effect when they were created.
                                </p>
                            </div>
                            <button type="submit"
                                class="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-sky-blue">
                                Save Settings
                            </button>
                        </form>
                    </div>
                @elseif ($tab === 'announcements')
                    <div class="flex justify-end">
                        <button type="button" @click="announcementModal = true; editingAnnouncement = null"
                            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                            + Post Announcement
                        </button>
                    </div>

                    <div class="frost-panel-solid overflow-hidden rounded-2xl">
                        @if ($announcements->isEmpty())
                            <div class="flex h-48 flex-col items-center justify-center text-center">
                                <p class="text-sm font-semibold text-charcoal">No announcements yet</p>
                            </div>
                        @else
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-white/50 bg-primary/5 text-charcoal/60">
                                    <tr>
                                        <th class="px-5 py-3 font-semibold">Announcement</th>
                                        <th class="px-5 py-3 font-semibold">Audience</th>
                                        <th class="px-5 py-3 font-semibold">Window</th>
                                        <th class="px-5 py-3 font-semibold">Status</th>
                                        <th class="px-5 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/50">
                                    @foreach ($announcements as $a)
                                        <tr>
                                            <td class="px-5 py-4">
                                                <p class="font-semibold text-charcoal">{{ $a->title }}</p>
                                                <p class="line-clamp-1 text-xs text-charcoal/50">{{ $a->body }}
                                                </p>
                                            </td>
                                            <td class="px-5 py-4 text-charcoal/70">
                                                {{ $audienceLabels[$a->audience] ?? $a->audience }}</td>
                                            <td class="px-5 py-4 text-xs text-charcoal/50">
                                                {{ $a->starts_at?->format('M d, Y') ?? 'Now' }} –
                                                {{ $a->ends_at?->format('M d, Y') ?? 'No end date' }}
                                            </td>
                                            <td class="px-5 py-4">
                                                <span
                                                    class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $a->is_active ? 'bg-leaf-green/10 text-leaf-green' : 'bg-light-gray text-charcoal/50' }}">
                                                    {{ $a->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
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
                            <div class="p-4">{{ $announcements->onEachSide(1)->links() }}</div>
                        @endif
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($policies as $policy)
                            <div class="frost-panel-solid rounded-2xl p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-bold text-charcoal">{{ $policy->title }}</p>
                                        <p class="text-xs text-charcoal/50">
                                            v{{ $policy->version }} ·
                                            {{ $policy->editor?->name ? "Last updated by {$policy->editor->name}, " : 'Updated ' }}{{ $policy->updated_at->diffForHumans() }}
                                        </p>
                                        <a href="{{ route('policies.show', $policy) }}" target="_blank"
                                            class="text-xs font-semibold text-primary hover:underline">
                                            View public page →
                                        </a>
                                    </div>
                                </div>

                                <details class="mt-3">
                                    <summary class="cursor-pointer text-xs font-semibold text-primary hover:underline">
                                        Edit</summary>
                                    <form method="POST"
                                        action="{{ route('admin.settings.policies.update', $policy) }}"
                                        class="mt-3 space-y-3">
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
                                        <button type="submit"
                                            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                                            Save (creates v{{ $policy->version + 1 }})
                                        </button>
                                    </form>
                                </details>
                            </div>
                        @endforeach
                    </div>

                @endif

            </main>
        </div>
    </div>

    <!-- ANNOUNCEMENT MODAL -->
    <div x-show="announcementModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-charcoal/50 px-4">
        <div @click.outside="announcementModal = false" x-transition
            class="w-full max-w-lg rounded-2xl border border-light-gray bg-white p-6 shadow-2xl">

            <h3 class="text-lg font-bold text-charcoal"
                x-text="editingAnnouncement ? 'Edit Announcement' : 'Post Announcement'"></h3>

            <form method="POST"
                :action="editingAnnouncement ? `/admin/settings/announcements/${editingAnnouncement.id}` :
                    '{{ route('admin.settings.announcements.store') }}'"
                class="mt-4 space-y-4">
                @csrf

                <div>
                    <label class="mb-1 block text-sm font-semibold">Title</label>
                    <input type="text" name="title" required maxlength="150"
                        :value="editingAnnouncement?.title ?? ''" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Message</label>
                    <textarea name="body" rows="3" required maxlength="1000" class="{{ $inputClass }} resize-none"
                        x-text="editingAnnouncement?.body ?? ''"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
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
                        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 accent-primary"
                            :checked="editingAnnouncement ? editingAnnouncement.is_active : true">
                        <label class="text-sm font-semibold">Active</label>
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

                <div class="flex justify-end gap-2 pt-2">
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
