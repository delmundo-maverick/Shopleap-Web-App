<x-logistics.layout title="Company announcements">
    <x-seller.data-table label="Company announcements" responsive="scroll"><x-slot:head><tr><th class="p-3">Posted</th><th class="p-3">Announcement</th><th class="p-3">Banner</th></tr></x-slot:head>
        @forelse ($receipts as $receipt)<tr><td class="p-3">{{ $receipt->created_at->timezone('Asia/Manila')->format('M d, Y g:i A') }} PHT</td><td class="p-3"><a href="{{ route('logistics.company-announcements.show', $receipt->announcement->public_code) }}" class="font-semibold text-primary">{{ $receipt->announcement->title }}</a></td><td class="p-3">{{ $receipt->dismissed_at ? 'Dismissed' : 'On dashboard' }}</td></tr>@empty<tr><td colspan="3" class="p-6 text-center">No company announcements yet.</td></tr>@endforelse
    </x-seller.data-table>{{ $receipts->links() }}
</x-logistics.layout>
