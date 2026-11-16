@props(['receipts'])
@if ($receipts->isNotEmpty())
    <section aria-label="Company announcements" class="space-y-3">
        @foreach ($receipts as $receipt)<article class="rounded-sm border border-primary bg-surface p-4"><div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0 flex-1"><h2 class="font-semibold">{{ $receipt->announcement->title }}</h2><p class="mt-2 whitespace-pre-wrap break-words text-sm">{{ $receipt->announcement->body }}</p><a href="{{ route('logistics.company-announcements.show', $receipt->announcement->public_code) }}" class="mt-3 inline-block text-sm text-primary">Read announcement</a></div><form method="POST" action="{{ route('logistics.company-announcements.dismiss', $receipt->announcement->public_code) }}">@csrf<button class="btn btn-outline" aria-label="Dismiss announcement: {{ $receipt->announcement->title }}">Dismiss</button></form></div></article>@endforeach
        <a href="{{ route('logistics.company-announcements.index') }}" class="text-sm text-primary">View all company announcements</a>
    </section>
@endif
