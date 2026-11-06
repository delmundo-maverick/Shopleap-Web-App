<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/seller/account.js'])

    <title>Account | Shopleap Seller</title>
</head>

<body class="min-h-screen text-charcoal">
    <div class="frost-surface"></div>

    @php
        $labelClass = 'mb-2 block text-sm font-semibold text-charcoal';
        $inputClass =
            'frost-input w-full rounded-xl px-4 py-3 text-sm outline-none transition focus:ring-4 focus:ring-primary/10';
        $selectClass = $inputClass . ' disabled:cursor-not-allowed disabled:opacity-60';
        $sectionTitle = 'mb-1 text-xs font-bold uppercase tracking-wide text-primary';
        $addressData = [
            'province' => old('province', $profile->province),
            'municipality' => old('municipality', $profile->municipality),
            'barangay' => old('barangay', $profile->barangay),
        ];
        $statusStyles = [
            'active' => 'bg-leaf-green/10 text-leaf-green',
            'suspended' => 'bg-orange-100 text-orange-600',
            'deactivated' => 'bg-sale-red/10 text-sale-red',
        ];

        // Profile completeness (derived from existing fields)
        $checks = [
            ['label' => 'Profile photo', 'done' => !empty($user->avatar_url), 'href' => '#profile'],
            ['label' => 'Shop description', 'done' => filled($profile->shop_description), 'href' => '#profile'],
            ['label' => 'Contact number', 'done' => filled($profile->contact_no), 'href' => '#profile'],
            [
                'label' => 'Pickup address',
                'done' =>
                    filled($profile->province) &&
                    filled($profile->municipality) &&
                    filled($profile->barangay) &&
                    filled($profile->street_address),
                'href' => '#address',
            ],
        ];
        $doneCount = collect($checks)->where('done', true)->count();
        $percent = (int) round(($doneCount / count($checks)) * 100);
    @endphp

    <div class="flex min-h-screen">
        <x-navbars.seller active="account" />

        <div class="flex min-w-0 flex-1 flex-col">

            <header
                class="frost-panel-solid sticky top-0 z-30 flex h-20 items-center justify-between border-b border-white/50 px-6">
                <div class="flex items-center gap-3">
                    <x-nav.toggle target="workspace-navigation" />
                    <div>
                        <h2 class="text-xl font-bold text-charcoal">Account Management</h2>
                        <p class="text-sm text-charcoal/55">Your shop profile, pickup address and security</p>
                    </div>
                </div>
                <x-notification-bell />
            </header>

            <main class="flex-1 p-6">
                <div class="w-full space-y-6">

                    @if (session('status'))
                        <div
                            class="frost-panel rounded-2xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 2xl:grid-cols-4">

                        <!-- ============ MAIN COLUMN ============ -->
                        <div class="space-y-6 lg:col-span-2 2xl:col-span-3">

                            <!-- ===================== SHOP PROFILE ===================== -->
                            <section id="profile" class="frost-panel scroll-mt-24 rounded-2xl p-6">
                                <p class="{{ $sectionTitle }}">Shop Profile</p>
                                <p class="mb-5 text-xs text-charcoal/50">This is how buyers see your shop.</p>

                                <div
                                    class="flex flex-col items-center gap-5 rounded-xl bg-ice-blue/40 p-4 sm:flex-row sm:items-center">
                                    <div class="relative h-24 w-24 shrink-0">
                                        <x-avatar :user="$user" size="2xl" />
                                        <img id="avatarPreviewImg" src="" alt=""
                                            class="absolute inset-0 hidden h-24 w-24 rounded-full bg-white object-cover ring-2 ring-primary">
                                    </div>

                                    <div class="flex-1 text-center sm:text-left">
                                        <p class="text-sm font-bold text-charcoal">{{ $profile->business_name }}</p>
                                        <p class="mt-0.5 text-xs text-charcoal/50">
                                            Your photo appears next to your shop name on product pages and your shop
                                            page.
                                        </p>

                                        <form method="POST" action="{{ route('seller.account.avatar') }}"
                                            enctype="multipart/form-data" class="mt-3">
                                            @csrf
                                            <input type="file" id="avatarInput" name="avatar"
                                                accept=".jpg,.jpeg,.png,.webp" class="sr-only">

                                            <div
                                                class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                                                <label for="avatarInput"
                                                    class="frost-btn cursor-pointer rounded-xl px-4 py-2 text-xs font-bold text-primary transition hover:bg-primary/5">
                                                    {{ $user->avatar_url ? 'Change photo' : 'Upload photo' }}
                                                </label>

                                                <span id="avatarSaveActions" class="hidden items-center gap-2">
                                                    <button type="submit"
                                                        class="rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white transition hover:bg-sky-blue">
                                                        Save photo
                                                    </button>
                                                    <button type="button" id="avatarCancelBtn"
                                                        class="rounded-xl px-3 py-2 text-xs font-semibold text-charcoal/60 transition hover:text-charcoal">
                                                        Cancel
                                                    </button>
                                                </span>

                                                @if ($user->avatar_url)
                                                    <button type="submit" form="removeAvatarForm"
                                                        class="text-xs font-semibold text-sale-red/80 transition hover:text-sale-red">
                                                        Remove photo
                                                    </button>
                                                @endif
                                            </div>

                                            <p id="avatarClientError"
                                                class="mt-2 hidden text-xs font-semibold text-sale-red"></p>
                                            @error('avatar', 'avatar')
                                                <p class="mt-2 text-xs font-semibold text-sale-red">{{ $message }}</p>
                                            @enderror
                                            <p class="mt-2 text-[11px] text-charcoal/40">JPG, PNG or WEBP · max 2 MB ·
                                                at least 100×100 px. Square images look best.</p>
                                        </form>

                                        @if ($user->avatar_url)
                                            <form id="removeAvatarForm" method="POST"
                                                action="{{ route('seller.account.avatar.destroy') }}"
                                                data-confirm="Remove your profile photo?" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('seller.account.profile') }}"
                                    class="mt-6 space-y-5 border-t border-white/50 pt-6">
                                    @csrf

                                    <div class="grid gap-5 md:grid-cols-2">
                                        <div>
                                            <label for="contact_no" class="{{ $labelClass }}">Contact number <span
                                                    class="text-sale-red">*</span></label>
                                            <input type="tel" id="contact_no" name="contact_no" required
                                                value="{{ old('contact_no', $profile->contact_no) }}"
                                                class="{{ $inputClass }}">
                                            @error('contact_no', 'profile')
                                                <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}
                                                </p>
                                            @enderror
                                            <p class="mt-1.5 text-xs text-charcoal/45">Printed on your shipping labels
                                                so riders can reach you for pickups.</p>
                                        </div>

                                        <div>
                                            <label class="{{ $labelClass }}">Shop name</label>
                                            <input type="text" value="{{ $profile->business_name }}" disabled
                                                class="{{ $inputClass }} cursor-not-allowed opacity-60">
                                        </div>
                                    </div>

                                    <div>
                                        <label for="shop_description" class="{{ $labelClass }}">
                                            Shop description <span
                                                class="font-normal text-charcoal/40">(optional)</span>
                                        </label>
                                        <textarea id="shop_description" name="shop_description" rows="4" maxlength="500"
                                            placeholder="Tell buyers what your shop is about…" class="{{ $inputClass }} resize-none">{{ old('shop_description', $profile->shop_description) }}</textarea>
                                        @error('shop_description', 'profile')
                                            <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}</p>
                                        @enderror
                                        <p class="mt-1.5 flex justify-between text-xs text-charcoal/45">
                                            <span>Shown on your shop page.</span>
                                            <span><span id="descCounter">0</span>/500</span>
                                        </p>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit"
                                            class="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-sky-blue">
                                            Save profile
                                        </button>
                                    </div>
                                </form>
                            </section>

                            <!-- ===================== PICKUP ADDRESS ===================== -->
                            <section id="address" class="frost-panel scroll-mt-24 rounded-2xl p-6">
                                <p class="{{ $sectionTitle }}">Pickup Address</p>
                                <p class="mb-5 text-xs text-charcoal/50">
                                    Used as the "Ship From" address on your waybills. Buyers see your city/province as
                                    "Ships from".
                                </p>

                                <form method="POST" action="{{ route('seller.account.address') }}"
                                    class="space-y-5">
                                    @csrf

                                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                                        <div>
                                            <label for="province" class="{{ $labelClass }}">Province <span
                                                    class="text-sale-red">*</span></label>
                                            <select id="province" name="province" required
                                                class="{{ $selectClass }}">
                                                <option value="{{ $addressData['province'] }}" selected>
                                                    {{ $addressData['province'] }}</option>
                                            </select>
                                            @error('province', 'address')
                                                <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}
                                                </p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="municipality" class="{{ $labelClass }}">Municipality / City
                                                <span class="text-sale-red">*</span></label>
                                            <select id="municipality" name="municipality" required
                                                class="{{ $selectClass }}">
                                                <option value="{{ $addressData['municipality'] }}" selected>
                                                    {{ $addressData['municipality'] }}</option>
                                            </select>
                                            @error('municipality', 'address')
                                                <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}
                                                </p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="barangay" class="{{ $labelClass }}">Barangay <span
                                                    class="text-sale-red">*</span></label>
                                            <select id="barangay" name="barangay" required
                                                class="{{ $selectClass }}">
                                                <option value="{{ $addressData['barangay'] }}" selected>
                                                    {{ $addressData['barangay'] }}</option>
                                            </select>
                                            @error('barangay', 'address')
                                                <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}
                                                </p>
                                            @enderror
                                        </div>
                                    </div>

                                    <p id="addressHelp" class="text-xs text-sale-red"></p>

                                    <div>
                                        <label for="street_address" class="{{ $labelClass }}">Street / House No. /
                                            Building <span class="text-sale-red">*</span></label>
                                        <input type="text" id="street_address" name="street_address" required
                                            maxlength="255"
                                            value="{{ old('street_address', $profile->street_address) }}"
                                            class="{{ $inputClass }}">
                                        @error('street_address', 'address')
                                            <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex items-center justify-between gap-4 border-t border-white/50 pt-5">
                                        <p class="text-xs text-charcoal/50">Riders use this address for parcel pickups.
                                        </p>
                                        <button type="submit"
                                            class="shrink-0 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-sky-blue">
                                            Save address
                                        </button>
                                    </div>
                                </form>
                            </section>

                            <!-- ===================== SECURITY ===================== -->
                            <section id="security" class="frost-panel scroll-mt-24 rounded-2xl p-6">
                                <p class="{{ $sectionTitle }}">Security</p>
                                <p class="mb-5 text-xs text-charcoal/50">Change your password.</p>

                                <div class="grid gap-6 xl:grid-cols-5">
                                    <form method="POST" action="{{ route('seller.account.password') }}"
                                        class="space-y-5 xl:col-span-3">
                                        @csrf

                                        <div>
                                            <label for="current_password" class="{{ $labelClass }}">Current
                                                password
                                                <span class="text-sale-red">*</span></label>
                                            <input type="password" id="current_password" name="current_password"
                                                required autocomplete="current-password" class="{{ $inputClass }}">
                                            @error('current_password', 'password')
                                                <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}
                                                </p>
                                            @enderror
                                        </div>

                                        <div class="grid gap-5 sm:grid-cols-2">
                                            <div>
                                                <label for="password" class="{{ $labelClass }}">New password <span
                                                        class="text-sale-red">*</span></label>
                                                <input type="password" id="password" name="password" required
                                                    minlength="8" autocomplete="new-password"
                                                    class="{{ $inputClass }}">
                                                @error('password', 'password')
                                                    <p class="mt-1.5 text-xs font-semibold text-sale-red">
                                                        {{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="password_confirmation"
                                                    class="{{ $labelClass }}">Confirm
                                                    new password <span class="text-sale-red">*</span></label>
                                                <input type="password" id="password_confirmation"
                                                    name="password_confirmation" required minlength="8"
                                                    autocomplete="new-password" class="{{ $inputClass }}">
                                            </div>
                                        </div>

                                        <button type="submit"
                                            class="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-sky-blue">
                                            Update password
                                        </button>
                                    </form>

                                    <div class="rounded-xl bg-ice-blue/50 p-4 xl:col-span-2">
                                        <p class="text-xs font-bold uppercase tracking-wide text-primary">Password tips
                                        </p>
                                        <ul class="mt-3 space-y-2 text-xs leading-relaxed text-charcoal/65">
                                            <li class="flex gap-2"><span class="text-leaf-green">✓</span> At least 8
                                                characters, different from your current password.</li>
                                            <li class="flex gap-2"><span class="text-leaf-green">✓</span> Mix letters,
                                                numbers and symbols.</li>
                                            <li class="flex gap-2"><span class="text-leaf-green">✓</span> Don't reuse
                                                a
                                                password from another site.</li>
                                            <li class="flex gap-2"><span class="text-leaf-green">✓</span> Never share
                                                it — Shopleap staff will never ask for it.</li>
                                        </ul>
                                    </div>
                                </div>
                            </section>

                        </div>

                        <!-- ============ SIDEBAR COLUMN ============ -->
                        <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">

                            <!-- Profile completeness -->
                            <section class="frost-panel rounded-2xl p-6">
                                <div class="flex items-center justify-between">
                                    <p class="{{ $sectionTitle }} !mb-0">Profile Completeness</p>
                                    <span class="text-sm font-bold text-primary">{{ $percent }}%</span>
                                </div>

                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-primary/10">
                                    <div class="h-full rounded-full bg-primary transition-all"
                                        style="width: {{ $percent }}%"></div>
                                </div>

                                <ul class="mt-4 space-y-2.5 text-sm">
                                    @foreach ($checks as $check)
                                        <li>
                                            <a href="{{ $check['href'] }}"
                                                class="flex items-center gap-2.5 {{ $check['done'] ? 'text-charcoal/70' : 'text-charcoal font-semibold hover:text-primary' }}">
                                                @if ($check['done'])
                                                    <span
                                                        class="flex h-5 w-5 items-center justify-center rounded-full bg-leaf-green/15 text-[11px] text-leaf-green">✓</span>
                                                @else
                                                    <span
                                                        class="h-5 w-5 rounded-full border-2 border-dashed border-primary/40"></span>
                                                @endif
                                                {{ $check['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <p class="mt-4 text-xs text-charcoal/50">
                                    @if ($percent === 100)
                                        Great job! Your shop profile is complete.
                                    @else
                                        A complete profile helps buyers trust your shop.
                                    @endif
                                </p>
                            </section>

                            <!-- Business & verification (read-only) -->
                            <section id="business" class="frost-panel scroll-mt-24 rounded-2xl p-6">
                                <p class="{{ $sectionTitle }}">Business &amp; Verification</p>
                                <p class="mb-4 text-xs text-charcoal/50">Verified against your submitted ID and
                                    business permit.</p>

                                <dl class="app-table-body text-sm">
                                    <div class="py-2.5 first:pt-0">
                                        <dt class="text-xs text-charcoal/50">Business name</dt>
                                        <dd class="mt-0.5 font-semibold">{{ $profile->business_name }}</dd>
                                    </div>
                                    <div class="py-2.5">
                                        <dt class="text-xs text-charcoal/50">Line of business</dt>
                                        <dd class="mt-0.5 font-semibold">{{ $profile->line_of_business }}</dd>
                                    </div>
                                    <div class="py-2.5">
                                        <dt class="text-xs text-charcoal/50">Account holder</dt>
                                        <dd class="mt-0.5 font-semibold">
                                            {{ trim($profile->first_name . ' ' . $profile->last_name) }}</dd>
                                    </div>
                                    <div class="py-2.5">
                                        <dt class="text-xs text-charcoal/50">E-mail</dt>
                                        <dd class="mt-0.5 break-all font-semibold">{{ $user->email }}</dd>
                                    </div>
                                    <div class="py-2.5">
                                        <dt class="text-xs text-charcoal/50">Verification</dt>
                                        <dd class="mt-0.5">
                                            <span
                                                class="rounded-full bg-leaf-green/10 px-2.5 py-1 text-xs font-semibold text-leaf-green">Verified</span>
                                            @if ($profile->reviewed_at)
                                                <span
                                                    class="ml-1 text-xs text-charcoal/50">{{ $profile->reviewed_at->format('M d, Y') }}</span>
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="py-2.5">
                                        <dt class="text-xs text-charcoal/50">Account status</dt>
                                        <dd class="mt-0.5">
                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $statusStyles[$user->account_status] ?? 'bg-light-gray text-charcoal/60' }}">
                                                {{ $user->account_status }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div class="py-2.5 last:pb-0">
                                        <dt class="text-xs text-charcoal/50">Member since</dt>
                                        <dd class="mt-0.5 font-semibold">{{ $user->created_at->format('F j, Y') }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>

                            <!-- Help card -->
                            <section class="frost-panel rounded-2xl bg-ice-blue/60 p-6">
                                <p class="text-sm font-bold text-charcoal">Need to change something locked?</p>
                                <p class="mt-1.5 text-xs leading-relaxed text-charcoal/60">
                                    Your business details and product categories are tied to your line of business, and
                                    changing your e-mail requires re-verification.
                                </p>
                                <a href="{{ route('messages.support') }}"
                                    class="mt-4 inline-flex rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white transition hover:bg-sky-blue">
                                    Contact support
                                </a>
                            </section>

                        </aside>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script id="addressData" type="application/json">@json($addressData)</script>

</body>

</html>
