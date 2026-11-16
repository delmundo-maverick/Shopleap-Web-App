<x-company.layout :title="$hub ? 'Edit hub' : 'Create hub'" description="Hub details and manager account" :scripts="['resources/js/pages/company/hub-form.js']">
    <form method="POST" action="{{ $hub ? route('company.hubs.update', $hub->public_code) : route('company.hubs.store') }}" class="max-w-4xl space-y-5 rounded-sm border border-line bg-surface p-5" x-data="companyHubForm" data-locations-url="{{ route('company.locations') }}" data-barangays-url="{{ route('company.barangays') }}" data-hub="{{ $hub?->public_code }}" data-province="{{ old('province', $hub?->province) }}" data-municipality="{{ old('municipality', $hub?->municipality) }}" data-barangay="{{ old('barangay', $hub?->barangay) }}" data-selected="{{ json_encode(old('barangays', $selectedBarangays)) }}">
        @csrf @if ($hub) @method('PUT') @endif
        <fieldset class="grid gap-4 sm:grid-cols-2"><legend class="mb-4 text-lg font-semibold">Hub details</legend>
            <label class="block text-sm font-medium">Hub name<input type="text" name="business_name" value="{{ old('business_name', $hub?->business_name) }}" required maxlength="255" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
            <label class="block text-sm font-medium">Province<select :disabled="loading" name="province" x-model="province" @change="changeProvince()" required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">Choose province</option><template x-for="place in provinces" :key="place.code"><option :value="place.name" :selected="place.name === province" x-text="place.name"></option></template></select></label>
            <label class="block text-sm font-medium">Municipality<select :disabled="loading" name="municipality" x-model="municipality" @change="changeMunicipality()" required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">Choose municipality</option><template x-for="place in municipalities" :key="place.code"><option :value="place.name" :selected="place.name === municipality" x-text="place.name"></option></template></select></label>
            <label class="block text-sm font-medium">Barangay<select :disabled="loading || !municipality" name="barangay" x-model="barangay" @change="locateBarangay()" required class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"><option value="">Choose barangay</option><template x-for="place in locationBarangays" :key="place.code"><option :value="place.name" :selected="place.name === barangay" x-text="place.name"></option></template></select></label>
        </fieldset>
        <fieldset id="hub-location" x-data="companyPinPicker" data-geocoding-url="https://photon.komoot.io/api/" data-latitude="{{ old('latitude', $hub?->latitude) }}" data-longitude="{{ old('longitude', $hub?->longitude) }}" class="space-y-3">
            <legend class="mb-3 text-lg font-semibold">Hub location</legend>
            <p class="text-sm">Choosing a barangay places an approximate pin automatically. Drag it to your branch's exact location, or click the map. You can also enter coordinates below, or leave both blank.</p>
            <p x-show="geocoding" x-cloak role="status" class="text-sm">Locating selected address...</p>
            <p x-show="locationMessage" x-text="locationMessage" x-cloak role="status" class="text-sm"></p>
            <button type="button" @click="locateBarangay()" :disabled="!barangay || geocoding" class="text-sm text-primary">Locate selected address</button>
            <p x-show="pinLoading" role="status" class="text-sm">Loading location map...</p>
            <div x-ref="pinMap" class="company-map company-pin-picker" aria-label="Choose a hub location in the Philippines" role="region"></div>
            <div x-show="mapError" x-cloak><p x-text="mapError" class="text-sm text-danger" role="alert"></p><button type="button" @click="loadPinMap()" class="text-sm text-primary">Retry map</button></div>
            <p x-show="pinError" x-text="pinError" x-cloak role="alert" class="text-sm text-danger"></p>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-medium">Latitude (optional)<input type="number" name="latitude" x-model="latitude" @input="cancelGeocoding()" @change="syncPin(true)" min="4" max="22" step="0.0000001" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
                <label class="block text-sm font-medium">Longitude (optional)<input type="number" name="longitude" x-model="longitude" @input="cancelGeocoding()" @change="syncPin(true)" min="116" max="127" step="0.0000001" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
            </div>
            <button type="button" @click="clearPin()" class="text-sm text-primary">Clear pin</button>
        </fieldset>
        <fieldset><legend class="mb-3 text-sm font-semibold">Covered barangays</legend>
            <p class="mb-3 text-xs">Select one or more barangays. Barangays already assigned to another hub are excluded.</p>
            <p x-show="loading" x-cloak class="text-sm" role="status">Loading locations...</p>
            <p x-show="error" x-text="error" x-cloak role="alert" class="text-sm text-danger"></p>
            <button x-show="error" x-cloak type="button" @click="init()" class="mt-2 text-sm text-primary">Retry loading locations</button>
            <div class="grid max-h-64 gap-3 overflow-y-auto sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="item in barangays" :key="item.barangay_key"><label class="flex items-center gap-2 rounded-sm border border-line p-3 text-sm"><input type="checkbox" name="barangays[]" :value="item.barangay" x-model="selected"><span x-text="item.barangay"></span></label></template>
            </div>
            <p x-show="!loading && municipality && !barangays.length && !error" x-cloak class="text-sm">No available barangays in this municipality.</p>
        </fieldset>
        <fieldset class="grid gap-4 sm:grid-cols-2"><legend class="mb-4 text-lg font-semibold">Hub manager</legend>
            @foreach (['manager_name' => ['Full name', 'text', 'name'], 'manager_email' => ['Email', 'email', 'email'], 'manager_phone' => ['Phone', 'tel', 'phone']] as $name => [$label, $type, $attribute])
                <label class="block text-sm font-medium">{{ $label }}<input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $attribute === 'phone' ? ($hub?->user?->phone ?? $hub?->contact_no) : $hub?->user?->$attribute) }}" required maxlength="{{ $attribute === 'phone' ? 20 : 255 }}" class="mt-1 block w-full rounded-sm border border-line bg-surface p-3"></label>
            @endforeach
        </fieldset>
        <p class="text-sm">The manager receives an email invitation to set their password. Changing the manager email sends a new invitation and resets their access.</p>
        <div class="flex flex-wrap gap-3"><button type="submit" :disabled="loading || !!error" class="btn btn-primary">{{ $hub ? 'Save hub' : 'Create hub and send invite' }}</button><a href="{{ route('company.hubs.index') }}" class="btn btn-outline">Cancel</a></div>
    </form>
</x-company.layout>
