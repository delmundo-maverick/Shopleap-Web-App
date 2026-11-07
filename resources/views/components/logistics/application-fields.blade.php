@props(['selected' => [], 'allDocuments' => true])
<fieldset class="portal-region-fieldset">
    <legend>Service regions <span aria-hidden="true">*</span></legend>
    <p>Select one or more regions. Detailed service areas can be configured after approval.</p>
    <div class="portal-region-options">
        @foreach (config('logistics.regions') as $region)
            <label class="portal-check"><input type="checkbox" name="service_regions[]" value="{{ $region }}" @checked(in_array($region, $selected))> {{ $region }}</label>
        @endforeach
    </div>
</fieldset>
<p class="portal-small">Documents: JPG, PNG or PDF. Maximum 5 MB each.</p>
@foreach (array_merge($allDocuments ? ['id_upload' => 'Authorized representative’s valid ID', 'business_permit' => 'Mayor’s / Business Permit'] : [], ['sec_certificate' => 'SEC Certificate', 'bir_registration' => 'BIR Form 2303']) as $field => $label)
    <div class="portal-upload">
        <label for="{{ $field }}">{{ $label }} <span aria-hidden="true">*</span></label>
        <input id="{{ $field }}" name="{{ $field }}" type="file" accept=".jpg,.jpeg,.png,.pdf" required>
    </div>
@endforeach
