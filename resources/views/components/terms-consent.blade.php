<div class="mt-6">
    <div class="flex items-start gap-3">
        <input type="checkbox" id="terms_accepted" name="terms_accepted" value="1" x-model="termsAccepted"
            @click.prevent="openTermsModal()" @checked(old('terms_accepted'))
            class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded border-light-gray accent-primary focus:ring-primary/20">

        <label for="terms_accepted" class="cursor-pointer text-xs leading-5 text-charcoal/65">
            I confirm that the information I provided is accurate and complete, and I have read and agree to the
            <a href="{{ route('terms') }}" target="_blank" rel="noopener"
                class="font-semibold text-primary underline hover:text-sky-blue">Terms and Conditions</a>
            and
            <a href="{{ route('privacy') }}" target="_blank" rel="noopener"
                class="font-semibold text-primary underline hover:text-sky-blue">Privacy Policy</a>.
            <span class="text-sale-red">*</span>
        </label>
    </div>

    <p class="mt-2 text-[11px] leading-5 text-charcoal/45">
        Clicking the checkbox opens both policies to review before agreeing.
    </p>

    <p x-show="termsError" x-cloak x-text="termsError"
        class="mt-1.5 text-xs font-semibold text-sale-red" role="alert"></p>

    @error('terms_accepted')
        <p class="mt-1.5 text-xs font-semibold text-sale-red" role="alert">{{ $message }}</p>
    @enderror
</div>

<x-terms-modal />
