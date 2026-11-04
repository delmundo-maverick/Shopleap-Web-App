<template x-teleport="body">
    <div x-show="modalOpen" x-cloak @keydown.escape.window="closeModal()" class="fixed inset-0 z-[150] flex items-center justify-center bg-charcoal/50 p-3" role="dialog" aria-modal="true" aria-labelledby="address-modal-title">
        <section @click.outside="closeModal()" class="max-h-[90dvh] w-full max-w-xl overflow-y-auto rounded-md bg-white p-5 text-charcoal sm:p-7">
            <div class="mb-5 flex items-center justify-between"><h2 id="address-modal-title" class="text-lg font-bold" x-text="editingId ? 'Edit address' : 'Add new address'"></h2><button type="button" @click="closeModal()" :disabled="busy" aria-label="Close address form" class="btn btn-outline">Close</button></div>
            <form @submit.prevent="save" class="space-y-4" :aria-busy="busy">
                @foreach (['label' => 'Label (optional)', 'recipient_name' => 'Recipient name', 'phone' => 'Phone'] as $name => $label)
                    <div><label for="address-{{ $name }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label><input id="address-{{ $name }}" x-model="form.{{ $name }}" @if ($name !== 'label') required @endif maxlength="{{ $name === 'label' ? 50 : ($name === 'phone' ? 20 : 255) }}" type="{{ $name === 'phone' ? 'tel' : 'text' }}" class="w-full rounded border border-line px-3 py-2.5"><p class="mt-1 text-xs text-sale-red" x-text="errors.{{ $name }}?.[0]"></p></div>
                @endforeach
                @foreach (['province' => 'Province', 'city_municipality' => 'City / Municipality', 'barangay' => 'Barangay'] as $name => $label)
                    <div><label for="address-{{ $name }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label>
                        <select id="address-{{ $name }}" x-model="form.{{ $name }}" @change="locationChanged('{{ $name }}')" :disabled="loadingLocations || busy" required class="w-full rounded border border-line px-3 py-2.5">
                            <option value="">Select {{ strtolower($label) }}</option><template x-for="place in options.{{ $name }}" :key="place.code"><option :value="place.name" x-text="place.name"></option></template>
                        </select><p class="mt-1 text-xs text-sale-red" x-text="errors.{{ $name }}?.[0]"></p>
                    </div>
                @endforeach
                <p x-show="locationError" x-text="locationError" class="text-sm text-sale-red" role="alert"></p>
                <button x-show="locationError" type="button" class="btn btn-outline" @click="loadLocations">Retry locations</button>
                @foreach (['street_address' => 'House number and street', 'postal_code' => 'Postal code (optional)', 'notes' => 'Delivery instructions (optional)'] as $name => $label)
                    <div><label for="address-{{ $name }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label><input id="address-{{ $name }}" x-model="form.{{ $name }}" @if ($name === 'street_address') required @endif maxlength="{{ $name === 'postal_code' ? 4 : 500 }}" @if ($name === 'postal_code') inputmode="numeric" pattern="[0-9]{4}" @endif class="w-full rounded border border-line px-3 py-2.5"><p class="mt-1 text-xs text-sale-red" x-text="errors.{{ $name }}?.[0]"></p></div>
                @endforeach
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="form.is_default"> Set as default</label>
                <p class="text-sm text-sale-red" x-text="error" role="alert"></p>
                <button type="submit" class="btn btn-primary w-full" :disabled="busy || loadingLocations || !!locationError" x-text="busy ? 'Saving?' : 'Save address'"></button>
            </form>
        </section>
    </div>
</template>
