@extends('admin.settings.layout')

@php
    $inputClass =
        'frost-input w-full rounded-xl px-4 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    $sectionTitle = 'mb-1 text-xs font-bold uppercase tracking-wide text-primary';
    $audienceLabels = [
        'all' => 'Everyone',
        'buyer' => 'Buyers',
        'seller' => 'Sellers',
        'logistics' => 'Logistics',
        'courier' => 'Riders',
    ];
    $megaphoneIcon =
        'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z';
    $announcementTotal = method_exists($announcements, 'total') ? $announcements->total() : $announcements->count();
@endphp

@section('title', 'Announcements')
@section('subtitle', 'Banners shown to selected audiences on their dashboards')

@section('content')
    <section class="frost-panel-solid overflow-hidden rounded-2xl">
        <div class="flex flex-col gap-3 border-b border-white/50 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="{{ $sectionTitle }}">Announcements</p>
                <p class="text-xs text-charcoal/50">
                    Banners shown to selected audiences on their dashboards.
                    <span class="font-semibold text-charcoal/70">{{ $announcementTotal }} total</span>
                </p>
            </div>

            <button type="button" @click="announcementModal = true; editingAnnouncement = null"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-blue">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                Post Announcement
            </button>
        </div>

        @if ($announcements->isEmpty())
            <div class="flex min-h-64 flex-col items-center justify-center gap-3 p-8 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $megaphoneIcon }}" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-charcoal">No announcements yet</p>
                    <p class="mt-1 text-xs text-charcoal/50">Post one to notify buyers, sellers,
                        logistics or riders.</p>
                </div>
                <button type="button" @click="announcementModal = true; editingAnnouncement = null"
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
                                    <p class="line-clamp-1 text-xs text-charcoal/50">{{ $a->body }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-ice-blue px-2.5 py-1 text-xs font-semibold text-primary">
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
@endsection

@section('modals')
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                    <input type="text" name="title" required maxlength="150" :value="editingAnnouncement?.title ?? ''"
                        class="{{ $inputClass }}">
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
                        <input type="date" name="starts_at"
                            :value="editingAnnouncement?.starts_at ? editingAnnouncement.starts_at.slice(0, 10) : ''"
                            class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-charcoal/60">Ends (optional)</label>
                        <input type="date" name="ends_at"
                            :value="editingAnnouncement?.ends_at ? editingAnnouncement.ends_at.slice(0, 10) : ''"
                            class="{{ $inputClass }}">
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
@endsection
