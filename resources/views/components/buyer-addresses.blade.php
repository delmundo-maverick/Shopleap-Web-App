@props(['addresses', 'checkout' => false, 'selected' => null])
<section id="addresses" class="rounded-md border border-line bg-white p-5" x-data="addressBook(@js([
    'addresses' => $addresses, 'checkout' => $checkout, 'selected' => $selected,
    'indexUrl' => route('buyer.addresses.index'), 'locationsUrl' => route('buyer.addresses.locations'),
    'name' => auth()->user()->name, 'phone' => auth()->user()->buyerProfile?->contact_no,
]))">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><h2 class="font-bold">{{ $checkout ? 'Delivery address' : 'Addresses' }}</h2><div class="flex gap-2">
        @if ($checkout) <button type="button" class="btn btn-outline" @click="showAll = !showAll" :aria-expanded="showAll">Change</button> @endif
        <button type="button" class="btn btn-primary" @click="openModal()">Add new address</button>
    </div></div>
    <p x-show="!addresses.length" class="text-sm text-charcoal/70">Add a delivery address before placing an order.</p>
    <div class="grid gap-3 {{ $checkout ? '' : 'sm:grid-cols-2' }}">
        <template x-for="address in addresses" :key="address.id">
            <article x-show="!checkout || showAll || selected === address.id" class="rounded border p-4" :class="checkout && selected === address.id ? 'border-primary' : 'border-line'">
                <label class="flex items-start gap-3">
                    @if ($checkout) <input type="radio" name="address_id" :value="address.id" :checked="selected === address.id" @change="choose(address.id)" class="mt-1 accent-primary" required> @endif
                    <span class="min-w-0"><span class="font-semibold" x-text="address.label || 'Address'"></span><span x-show="address.is_default" class="ml-2 rounded bg-ice-blue px-2 py-1 text-xs font-bold text-primary">Default</span>
                    <span class="mt-2 block text-sm font-semibold" x-text="address.recipient_name"></span><span class="block text-sm" x-text="address.phone"></span>
                    <span class="mt-1 block break-words text-sm text-charcoal/70" x-text="fullAddress(address)"></span><span class="block text-xs text-charcoal/60" x-text="address.notes"></span></span>
                </label>
                <div class="mt-3 flex flex-wrap gap-2"><button type="button" class="btn btn-outline" @click="openModal(address)" :disabled="busy">Edit</button><button type="button" class="btn btn-outline" x-show="!address.is_default" @click="makeDefault(address)" :disabled="busy">Set as default</button><button type="button" class="btn btn-danger-outline" @click="remove(address)" :disabled="busy">Delete</button></div>
            </article>
        </template>
    </div>
    <p x-show="busy && !modalOpen" role="status" class="mt-3 text-sm">Updating addresses?</p>
    <p x-show="error && !modalOpen" x-text="error" role="alert" class="mt-3 text-sm text-sale-red"></p>
    <x-address-modal />
</section>
