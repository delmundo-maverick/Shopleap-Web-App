<x-logistics.layout title="Review Rider Application" :subtitle="$rider->full_name" active="riders">
    <a href="{{ route('logistics.riders.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-primary hover:underline"><span aria-hidden="true">&larr;</span> Back to applications</a>
    <section class="frost-panel flex flex-wrap items-start justify-between gap-4 rounded-xl border border-line p-4 sm:p-5">
        <div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-wide text-charcoal/55">Rider application #{{ $rider->id }}</p><h1 class="mt-2 break-words text-2xl font-semibold">{{ $rider->full_name }}</h1><p class="mt-1 break-all text-sm text-charcoal/65">{{ $rider->user?->email }}</p></div>
        <div><x-status-label :status="$rider->status" domain="application" /><p class="mt-2 text-xs text-charcoal/60">Submitted {{ $rider->created_at->format('M j, Y g:i A') }}</p>@if ($rider->reviewed_at)<p class="mt-1 text-xs text-charcoal/60">Reviewed {{ $rider->reviewed_at->format('M j, Y g:i A') }}</p>@endif</div>
    </section>

    <div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(17rem,1fr)]">
        <div class="min-w-0 space-y-5">
            @foreach ([
                'Personal information' => [
                    'First name' => $rider->first_name, 'Middle initial' => $rider->middle_initial,
                    'Last name' => $rider->last_name, 'Sex' => ucfirst($rider->sex ?? ''),
                    'Birthday' => $rider->birthday?->format('M j, Y'), 'Age at application' => $rider->age,
                    'Contact number' => $rider->contact_no,
                    'Email address' => $rider->user?->email,
                    'Email verified' => $rider->user?->email_verified_at ? 'Yes' : 'No',
                ],
                'Address' => [
                    'Street address' => $rider->street_address, 'Barangay' => $rider->barangay,
                    'Municipality / city' => $rider->municipality, 'Province' => $rider->province,
                ],
                'Vehicle and service area' => [
                    'Vehicle type' => $rider->vehicle_type, 'Plate number' => $rider->plate_number,
                    'Requested delivery area' => $rider->delivery_area,
                ],
            ] as $heading => $fields)
                <section class="frost-panel rounded-xl border border-line p-4 sm:p-5">
                    <h2 class="text-card-title">{{ $heading }}</h2>
                    <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        @foreach ($fields as $label => $value)
                            <div class="min-w-0"><dt class="text-xs text-charcoal/60">{{ $label }}</dt><dd class="mt-1 break-words text-sm font-medium">{{ filled($value) ? $value : 'Not provided' }}</dd></div>
                        @endforeach
                    </dl>
                </section>
            @endforeach

            <section class="frost-panel rounded-xl border border-line p-4 sm:p-5" aria-labelledby="documents-title">
                <h2 id="documents-title" class="text-card-title">Uploaded documents</h2><p class="mt-1 text-card-hint">Check that the documents are clear and match the applicant and vehicle.</p>
                <div class="mt-4 grid min-w-0 gap-4 sm:grid-cols-2">
                    @foreach (['license' => ['Driver’s license', $rider->license_upload_path], 'or-cr' => ['Vehicle OR / CR', $rider->or_cr_upload_path]] as $document => [$label, $path])
                        <article class="min-w-0 rounded-xl border border-line p-3">
                            <h3 class="text-sm font-semibold">{{ $label }}</h3>
                            @if ($path)
                                @if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
                                    <a href="{{ route('logistics.riders.document', [$rider, $document]) }}" target="_blank" rel="noopener noreferrer" aria-label="Open {{ $label }}"><img loading="lazy" src="{{ route('logistics.riders.document', [$rider, $document]) }}" alt="{{ $label }} submitted by {{ $rider->full_name }}" class="mt-3 h-52 w-full rounded-lg bg-ice-blue object-contain"></a>
                                @else
                                    <div class="mt-3 flex h-32 items-center justify-center rounded-lg bg-ice-blue text-sm text-charcoal/65">PDF document</div>
                                @endif
                                <a href="{{ route('logistics.riders.document', [$rider, $document]) }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-primary hover:underline">Open {{ $label }} <span class="ml-2" aria-hidden="true">&nearr;</span></a>
                            @else
                                <p class="mt-3 text-sm text-charcoal/60">No document was uploaded.</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="min-w-0 space-y-5">
            <section class="frost-panel rounded-xl border border-line p-4 sm:p-5">
                <h2 class="text-card-title">{{ $rider->status === 'pending' ? 'Review decision' : 'Application decision' }}</h2>
                @if ($rider->status === 'pending')
                    <p class="mt-2 text-sm text-charcoal/65">Review the form and open both documents before approving or rejecting this rider.</p>
                    <form method="POST" action="{{ route('logistics.riders.approve', $rider) }}" class="mt-5">
                        @csrf
                        <button type="submit" class="min-h-11 w-full rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-sky-blue">Approve rider</button>
                        <p class="mt-2 text-xs text-charcoal/55">Approval activates the rider and sends an email with next steps.</p>
                    </form>
                    <form method="POST" action="{{ route('logistics.riders.reject', $rider) }}" class="mt-6 border-t border-line pt-5">
                        @csrf
                        <label for="rejection_reason" class="block text-sm font-semibold">Reason for rejection</label>
                        <p id="reason-help" class="mt-1 text-xs text-charcoal/60">Explain what needs to be corrected. This reason is emailed to the rider.</p>
                        <textarea id="rejection_reason" name="rejection_reason" rows="4" required maxlength="500" aria-describedby="reason-help" class="mt-3 w-full rounded-xl border border-line px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">{{ old('rejection_reason') }}</textarea>
                        @error('rejection_reason')<p role="alert" class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                        <button type="submit" class="mt-3 min-h-11 w-full rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-danger transition hover:bg-danger/15">Reject application</button>
                    </form>
                @elseif ($rider->status === 'rejected')
                    <p class="mt-3 whitespace-pre-line text-sm text-charcoal/75">{{ $rider->rejection_reason }}</p>
                    <p class="mt-3 text-xs text-charcoal/60">The rider can correct their application and resubmit from the ShopLeap Rider app.</p>
                @else
                    <p class="mt-3 text-sm">This rider is {{ $rider->is_active ? 'active' : 'inactive' }}.</p>
                    <form method="POST" action="{{ route('logistics.riders.toggle-active', $rider) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="min-h-11 w-full rounded-xl border border-line px-4 py-3 text-sm font-semibold text-primary hover:bg-primary/5">{{ $rider->is_active ? 'Deactivate rider' : 'Activate rider' }}</button>
                    </form>
                @endif
            </section>
        </aside>
    </div>
</x-logistics.layout>
