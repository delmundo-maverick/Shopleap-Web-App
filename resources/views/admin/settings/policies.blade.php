@extends('admin.settings.layout')

@php
    $inputClass =
        'frost-input w-full rounded-xl px-4 py-2.5 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
    $sectionTitle = 'mb-1 text-xs font-bold uppercase tracking-wide text-primary';
    $policyIcon =
        'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
@endphp

@section('title', 'Policies')
@section('subtitle', 'Public legal pages with version tracking')

@section('content')
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
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="{{ $policyIcon }}" />
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
                        <svg class="h-3.5 w-3.5 transition group-open:rotate-90" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                        Edit policy
                    </summary>
                    <form method="POST" action="{{ route('admin.settings.policies.update', $policy) }}"
                        class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-charcoal/60">Title</label>
                            <input type="text" name="title" required maxlength="150" value="{{ $policy->title }}"
                                class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-charcoal/60">Content</label>
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
@endsection
