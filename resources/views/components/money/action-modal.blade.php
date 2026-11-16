@props(['title' => 'Confirm transaction'])
<dialog data-money-dialog class="m-auto w-full max-w-md rounded-sm border border-line bg-surface p-5 text-charcoal backdrop:bg-charcoal/60">
    <h2 class="text-lg font-semibold">{{ $title }}</h2>
    <p data-money-dialog-message class="mt-3 text-sm"></p>
    {{ $slot }}
    <div class="mt-5 flex justify-end gap-2"><button type="button" data-money-cancel class="btn btn-outline rounded-sm!">Cancel</button><button type="button" data-money-confirm class="btn btn-primary rounded-sm!">Confirm</button></div>
</dialog>
