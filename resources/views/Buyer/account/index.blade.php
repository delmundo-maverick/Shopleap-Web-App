<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/buyer/account.js'])

    <title>My Account | Shopleap</title>
</head>

<body class="flex min-h-screen flex-col bg-ice-blue text-charcoal">

    @php
        $labelClass = 'mb-2 block text-sm font-semibold text-charcoal';

        $inputClass =
            'w-full rounded-xl border border-light-gray bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10';

        $selectClass = $inputClass . ' disabled:cursor-not-allowed disabled:opacity-60';

        $cardClass = 'rounded-xl bg-white p-4 shadow-sm shadow-charcoal/5 sm:p-6';

        $sectionTitle = 'text-lg font-bold text-charcoal';

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

        $fullName = trim(
            $profile->first_name .
                ' ' .
                ($profile->middle_initial ? $profile->middle_initial . '. ' : '') .
                $profile->last_name,
        );

        // Reopen the relevant tab when a form fails validation.
        $errorTab = null;

        if ($errors->hasBag('address')) {
            $errorTab = 'address';
        } elseif ($errors->hasBag('password')) {
            $errorTab = 'security';
        } elseif ($errors->hasBag('profile') || $errors->hasBag('avatar')) {
            $errorTab = 'profile';
        }
    @endphp

    <x-Buyer.header />
    <main class="mx-auto w-full max-w-7xl flex-1 px-3 py-5 md:px-8 md:py-6" x-data="{
        tab: @js($errorTab ?? 'profile'),
        forced: @js($errorTab !== null),
        valid: ['profile', 'address', 'security', 'following'],

        init() {
            const hash = location.hash.slice(1);

            if (!this.forced && this.valid.includes(hash)) {
                this.tab = hash;
            }

            window.addEventListener('hashchange', () => {
                const currentHash = location.hash.slice(1);

                if (this.valid.includes(currentHash)) {
                    this.tab = currentHash;
                }
            });
        },

        go(tabName) {
            this.tab = tabName;
            history.replaceState(null, '', '#' + tabName);
        }
    }">

        @if (session('status'))
            <div role="status"
                class="mb-5 rounded-xl border border-leaf-green/20 bg-leaf-green/10 px-4 py-3 text-sm text-leaf-green">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid min-w-0 grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-[240px_minmax(0,1fr)]">

            {{-- ACCOUNT SIDEBAR --}}
            <aside class="min-w-0 lg:sticky lg:top-28 lg:self-start">

                {{-- User information --}}
                <div class="mb-3 flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm shadow-charcoal/5 lg:mb-4">
                    <x-avatar :user="$user" size="lg" />

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-charcoal">
                            {{ $fullName }}
                        </p>

                        <button type="button" @click="go('profile')"
                            class="mt-1 flex items-center gap-1 text-xs text-charcoal/50 transition hover:text-primary">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2.5 2.5 0 113.536 3.536L12.5 16.5 8 17l1-4z" />
                            </svg>
                            Edit Profile
                        </button>
                    </div>
                </div>

                {{-- Account navigation --}}
                <nav aria-label="Account navigation" class="rounded-xl bg-white p-2 shadow-sm shadow-charcoal/5 lg:p-3">
                    <div class="flex gap-1 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible lg:pb-0">

                        <p
                            class="hidden px-3 pb-1 pt-2 text-[11px] font-bold uppercase tracking-wide text-charcoal/40 lg:block">
                            My Account
                        </p>

                        <button type="button" @click="go('profile')" :aria-current="tab === 'profile' ? 'page' : null"
                            :class="tab === 'profile'
                                ?
                                'bg-primary/10 text-primary font-bold' :
                                'text-charcoal/70 hover:bg-ice-blue'"
                            class="flex shrink-0 items-center gap-2.5 whitespace-nowrap rounded-lg px-3 py-2.5 text-left text-sm transition">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile
                        </button>

                        <button type="button" @click="go('address')" :aria-current="tab === 'address' ? 'page' : null"
                            :class="tab === 'address'
                                ?
                                'bg-primary/10 text-primary font-bold' :
                                'text-charcoal/70 hover:bg-ice-blue'"
                            class="flex shrink-0 items-center gap-2.5 whitespace-nowrap rounded-lg px-3 py-2.5 text-left text-sm transition">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Addresses
                        </button>

                        <button type="button" @click="go('security')"
                            :aria-current="tab === 'security' ? 'page' : null"
                            :class="tab === 'security'
                                ?
                                'bg-primary/10 text-primary font-bold' :
                                'text-charcoal/70 hover:bg-ice-blue'"
                            class="flex shrink-0 items-center gap-2.5 whitespace-nowrap rounded-lg px-3 py-2.5 text-left text-sm transition">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Change Password
                        </button>

                        <div class="my-1 hidden h-px bg-light-gray lg:block"></div>

                        <a href="{{ route('buyer.orders.index') }}"
                            class="flex shrink-0 items-center gap-2.5 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm text-charcoal/70 transition hover:bg-ice-blue">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            My Purchases
                        </a>

                        <a href="{{ route('buyer.wishlist.index') }}"
                            class="flex shrink-0 items-center gap-2.5 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm text-charcoal/70 transition hover:bg-ice-blue">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            My Wishlist
                        </a>

                        <button type="button" @click="go('following')"
                            :aria-current="tab === 'following' ? 'page' : null"
                            :class="tab === 'following'
                                ?
                                'bg-primary/10 text-primary font-bold' :
                                'text-charcoal/70 hover:bg-ice-blue'"
                            class="flex shrink-0 items-center gap-2.5 whitespace-nowrap rounded-lg px-3 py-2.5 text-left text-sm transition">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Following
                            <span x-show="$store.follow.total > 0" x-cloak x-text="$store.follow.total"
                                class="ml-auto rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-bold text-primary">{{ $following->total() }}</span>
                        </button>

                        <a href="{{ route('messages.index') }}"
                            class="flex shrink-0 items-center gap-2.5 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm text-charcoal/70 transition hover:bg-ice-blue">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.9A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            Messages
                        </a>

                    </div>
                </nav>
            </aside>

            {{-- ACCOUNT CONTENT --}}
            <div class="min-w-0">

                {{-- PROFILE TAB --}}
                <div x-show="tab === 'profile'" x-cloak class="min-w-0 space-y-4 sm:space-y-5">

                    {{-- Profile information --}}
                    <section class="{{ $cardClass }}">
                        <div class="mb-5">
                            <h2 class="{{ $sectionTitle }}">My Profile</h2>
                            <p class="mt-1 text-xs leading-relaxed text-charcoal/50">
                                Manage your photo and contact details.
                            </p>
                        </div>

                        {{-- Avatar management --}}
                        <div
                            class="flex min-w-0 flex-col items-center gap-4 rounded-xl bg-ice-blue/40 p-4 sm:flex-row sm:gap-5">
                            <div class="relative h-24 w-24 shrink-0">
                                <x-avatar :user="$user" size="2xl" />

                                <img id="avatarPreviewImg" src="" alt="Selected profile photo preview"
                                    class="absolute inset-0 hidden h-24 w-24 rounded-full bg-white object-cover ring-2 ring-primary">
                            </div>

                            <div class="min-w-0 flex-1 text-center sm:text-left">
                                <p class="break-words text-sm font-bold text-charcoal">
                                    {{ $fullName }}
                                </p>

                                <p class="mt-1 text-xs leading-relaxed text-charcoal/50">
                                    Your photo is shown in chats with sellers.
                                </p>

                                <form method="POST" action="{{ route('buyer.account.avatar') }}"
                                    enctype="multipart/form-data" class="mt-3">
                                    @csrf

                                    <input type="file" id="avatarInput" name="avatar"
                                        accept=".jpg,.jpeg,.png,.webp" class="sr-only">

                                    <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                                        <label for="avatarInput"
                                            class="cursor-pointer rounded-xl border border-light-gray bg-white px-4 py-2.5 text-xs font-bold text-primary transition hover:bg-primary/5">
                                            {{ $user->avatar_url ? 'Change photo' : 'Upload photo' }}
                                        </label>

                                        <span id="avatarSaveActions" class="hidden items-center gap-2">
                                            <button type="submit"
                                                class="rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white transition hover:bg-sky-blue">
                                                Save photo
                                            </button>

                                            <button type="button" id="avatarCancelBtn"
                                                class="rounded-xl px-3 py-2.5 text-xs font-semibold text-charcoal/60 transition hover:text-charcoal">
                                                Cancel
                                            </button>
                                        </span>

                                        @if ($user->avatar_url)
                                            <button type="submit" form="removeAvatarForm"
                                                class="rounded-xl px-2 py-2.5 text-xs font-semibold text-sale-red/80 transition hover:text-sale-red">
                                                Remove photo
                                            </button>
                                        @endif
                                    </div>

                                    <p id="avatarClientError" role="alert"
                                        class="mt-2 hidden text-xs font-semibold text-sale-red"></p>

                                    @error('avatar', 'avatar')
                                        <p class="mt-2 text-xs font-semibold text-sale-red">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                    <p class="mt-2 text-[11px] leading-relaxed text-charcoal/40">
                                        JPG, PNG or WEBP · max 2 MB · at least 100×100 px.
                                    </p>
                                </form>

                                @if ($user->avatar_url)
                                    <form id="removeAvatarForm" method="POST"
                                        action="{{ route('buyer.account.avatar.destroy') }}"
                                        data-confirm="Remove your profile photo?" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- Editable profile fields --}}
                        <form method="POST" action="{{ route('buyer.account.profile') }}"
                            class="mt-6 space-y-5 border-t border-light-gray pt-6">
                            @csrf

                            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                                <div class="min-w-0">
                                    <label for="contact_no" class="{{ $labelClass }}">
                                        Contact number <span class="text-sale-red">*</span>
                                    </label>

                                    <input type="tel" id="contact_no" name="contact_no" required
                                        value="{{ old('contact_no', $profile->contact_no) }}"
                                        class="{{ $inputClass }}">

                                    @error('contact_no', 'profile')
                                        <p class="mt-1.5 text-xs font-semibold text-sale-red">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                    <p class="mt-1.5 text-xs leading-relaxed text-charcoal/45">
                                        Riders use this to reach you for deliveries.
                                    </p>
                                </div>

                                <div class="min-w-0">
                                    <label for="full_name" class="{{ $labelClass }}">
                                        Full name
                                    </label>

                                    <input type="text" id="full_name" value="{{ $fullName }}" disabled
                                        class="{{ $inputClass }} cursor-not-allowed opacity-60">
                                </div>
                            </div>

                            <div class="flex justify-end border-t border-light-gray pt-5">
                                <button type="submit"
                                    class="w-full rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-sky-blue sm:w-auto">
                                    Save profile
                                </button>
                            </div>
                        </form>
                    </section>

                    {{-- Account details --}}
                    <section class="{{ $cardClass }}">
                        <div class="mb-4">
                            <h2 class="{{ $sectionTitle }}">Account Details</h2>
                            <p class="mt-1 text-xs leading-relaxed text-charcoal/50">
                                Verified against your submitted ID.
                            </p>
                        </div>

                        <dl class="grid min-w-0 gap-x-8 sm:grid-cols-2">
                            <div class="min-w-0 border-b border-light-gray py-3">
                                <dt class="text-xs text-charcoal/50">Name</dt>
                                <dd class="mt-1 break-words text-sm font-semibold">{{ $fullName }}</dd>
                            </div>

                            <div class="min-w-0 border-b border-light-gray py-3">
                                <dt class="text-xs text-charcoal/50">E-mail</dt>
                                <dd class="mt-1 break-all text-sm font-semibold">{{ $user->email }}</dd>
                            </div>

                            <div class="min-w-0 border-b border-light-gray py-3">
                                <dt class="text-xs text-charcoal/50">Birthday</dt>
                                <dd class="mt-1 text-sm font-semibold">
                                    {{ $profile->birthday?->format('F j, Y') }}
                                </dd>
                            </div>

                            <div class="min-w-0 border-b border-light-gray py-3">
                                <dt class="text-xs text-charcoal/50">Verification</dt>
                                <dd class="mt-1 flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-full bg-leaf-green/10 px-2.5 py-1 text-xs font-semibold text-leaf-green">
                                        Verified
                                    </span>

                                    @if ($profile->reviewed_at)
                                        <span class="text-xs text-charcoal/50">
                                            {{ $profile->reviewed_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div class="min-w-0 border-b border-light-gray py-3 sm:border-b-0">
                                <dt class="text-xs text-charcoal/50">Account status</dt>
                                <dd class="mt-1">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $statusStyles[$user->account_status] ?? 'bg-light-gray text-charcoal/60' }}">
                                        {{ $user->account_status }}
                                    </span>
                                </dd>
                            </div>

                            <div class="min-w-0 py-3">
                                <dt class="text-xs text-charcoal/50">Member since</dt>
                                <dd class="mt-1 text-sm font-semibold">
                                    {{ $user->created_at->format('F j, Y') }}
                                </dd>
                            </div>
                        </dl>

                        <div
                            class="mt-4 flex flex-col items-start justify-between gap-3 rounded-xl bg-ice-blue/60 p-4 sm:flex-row sm:items-center">
                            <p class="text-xs leading-relaxed text-charcoal/60">
                                Name, birthday and ID are tied to your verification, and changing your e-mail requires
                                re-verification.
                            </p>

                            <a href="{{ route('messages.support') }}"
                                class="inline-flex w-full shrink-0 items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white transition hover:bg-sky-blue sm:w-auto">
                                Contact support
                            </a>
                        </div>
                    </section>
                </div>

                {{-- ADDRESS TAB --}}
                <div x-show="tab === 'address'" x-cloak class="min-w-0">
                    <section class="{{ $cardClass }}">
                        <div class="mb-5">
                            <h2 class="{{ $sectionTitle }}">Delivery Address</h2>
                            <p class="mt-1 text-xs leading-relaxed text-charcoal/50">
                                Used at checkout to calculate shipping and check delivery coverage. Existing orders keep
                                the address they were placed with.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('buyer.account.address') }}" class="space-y-5">
                            @csrf

                            <div class="grid min-w-0 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                                <div class="min-w-0">
                                    <label for="province" class="{{ $labelClass }}">
                                        Province <span class="text-sale-red">*</span>
                                    </label>

                                    <select id="province" name="province" required class="{{ $selectClass }}">
                                        <option value="{{ $addressData['province'] }}" selected>
                                            {{ $addressData['province'] }}
                                        </option>
                                    </select>

                                    @error('province', 'address')
                                        <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="min-w-0">
                                    <label for="municipality" class="{{ $labelClass }}">
                                        Municipality / City <span class="text-sale-red">*</span>
                                    </label>

                                    <select id="municipality" name="municipality" required
                                        class="{{ $selectClass }}">
                                        <option value="{{ $addressData['municipality'] }}" selected>
                                            {{ $addressData['municipality'] }}
                                        </option>
                                    </select>

                                    @error('municipality', 'address')
                                        <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="min-w-0">
                                    <label for="barangay" class="{{ $labelClass }}">
                                        Barangay <span class="text-sale-red">*</span>
                                    </label>

                                    <select id="barangay" name="barangay" required class="{{ $selectClass }}">
                                        <option value="{{ $addressData['barangay'] }}" selected>
                                            {{ $addressData['barangay'] }}
                                        </option>
                                    </select>

                                    @error('barangay', 'address')
                                        <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <p id="addressHelp" role="status" class="text-xs text-sale-red"></p>

                            <div class="grid min-w-0 gap-5 sm:grid-cols-3">
                                <div class="min-w-0">
                                    <label for="house_number" class="{{ $labelClass }}">
                                        House / Unit No. <span class="text-sale-red">*</span>
                                    </label>

                                    <input type="text" id="house_number" name="house_number" required
                                        maxlength="100" value="{{ old('house_number', $profile->house_number) }}"
                                        class="{{ $inputClass }}">

                                    @error('house_number', 'address')
                                        <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="min-w-0 sm:col-span-2">
                                    <label for="street" class="{{ $labelClass }}">
                                        Street <span class="text-sale-red">*</span>
                                    </label>

                                    <input type="text" id="street" name="street" required maxlength="255"
                                        value="{{ old('street', $profile->street) }}" class="{{ $inputClass }}">

                                    @error('street', 'address')
                                        <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="min-w-0">
                                <label for="additional_address" class="{{ $labelClass }}">
                                    Landmarks / notes
                                    <span class="font-normal text-charcoal/40">(optional)</span>
                                </label>

                                <textarea id="additional_address" name="additional_address" rows="3" maxlength="500"
                                    placeholder="e.g. Blue gate, near the barangay hall" class="{{ $inputClass }} resize-y">{{ old('additional_address', $profile->additional_address) }}</textarea>

                                @error('additional_address', 'address')
                                    <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end border-t border-light-gray pt-5">
                                <button type="submit"
                                    class="w-full rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-sky-blue sm:w-auto">
                                    Save address
                                </button>
                            </div>
                        </form>
                    </section>
                </div>

                {{-- CHANGE PASSWORD TAB --}}
                <div x-show="tab === 'security'" x-cloak class="min-w-0">
                    <section class="{{ $cardClass }}">
                        <div class="mb-5">
                            <h2 class="{{ $sectionTitle }}">Change Password</h2>
                            <p class="mt-1 text-xs leading-relaxed text-charcoal/50">
                                For your account's security, do not share your password with anyone.
                            </p>
                        </div>

                        <div class="grid min-w-0 gap-6 xl:grid-cols-5">

                            <form method="POST" action="{{ route('buyer.account.password') }}"
                                class="min-w-0 space-y-5 xl:col-span-3">
                                @csrf

                                <div>
                                    <label for="current_password" class="{{ $labelClass }}">
                                        Current password <span class="text-sale-red">*</span>
                                    </label>

                                    <input type="password" id="current_password" name="current_password" required
                                        autocomplete="current-password" class="{{ $inputClass }}">

                                    @error('current_password', 'password')
                                        <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid min-w-0 gap-5 sm:grid-cols-2">
                                    <div class="min-w-0">
                                        <label for="password" class="{{ $labelClass }}">
                                            New password <span class="text-sale-red">*</span>
                                        </label>

                                        <input type="password" id="password" name="password" required
                                            minlength="8" autocomplete="new-password" class="{{ $inputClass }}">

                                        @error('password', 'password')
                                            <p class="mt-1.5 text-xs font-semibold text-sale-red">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="min-w-0">
                                        <label for="password_confirmation" class="{{ $labelClass }}">
                                            Confirm new password <span class="text-sale-red">*</span>
                                        </label>

                                        <input type="password" id="password_confirmation"
                                            name="password_confirmation" required minlength="8"
                                            autocomplete="new-password" class="{{ $inputClass }}">
                                    </div>
                                </div>

                                <div class="flex justify-start pt-1">
                                    <button type="submit"
                                        class="w-full rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-sky-blue sm:w-auto">
                                        Update password
                                    </button>
                                </div>
                            </form>

                            {{-- Password tips --}}
                            <div class="min-w-0 rounded-xl bg-ice-blue/50 p-4 xl:col-span-2">
                                <p class="text-xs font-bold uppercase tracking-wide text-primary">
                                    Password tips
                                </p>

                                <ul class="mt-3 space-y-3 text-xs leading-relaxed text-charcoal/65">
                                    <li class="flex items-start gap-2">
                                        <span class="shrink-0 font-bold text-leaf-green" aria-hidden="true">✓</span>
                                        <span>Use at least 8 characters and choose a password different from your
                                            current one.</span>
                                    </li>

                                    <li class="flex items-start gap-2">
                                        <span class="shrink-0 font-bold text-leaf-green" aria-hidden="true">✓</span>
                                        <span>Mix letters, numbers and symbols.</span>
                                    </li>

                                    <li class="flex items-start gap-2">
                                        <span class="shrink-0 font-bold text-leaf-green" aria-hidden="true">✓</span>
                                        <span>Do not reuse a password from another site.</span>
                                    </li>

                                    <li class="flex items-start gap-2">
                                        <span class="shrink-0 font-bold text-leaf-green" aria-hidden="true">✓</span>
                                        <span>Shopleap staff will never ask for your password.</span>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </section>
                </div>

                {{-- FOLLOWING TAB --}}
                <div x-show="tab === 'following'" x-cloak class="min-w-0">
                    <section class="{{ $cardClass }}">
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="{{ $sectionTitle }}">Following</h2>
                                <p class="mt-1 text-xs leading-relaxed text-charcoal/50">
                                    Shops you follow. Visit them anytime or unfollow to stop seeing them here.
                                </p>
                            </div>

                            <span x-show="$store.follow.total > 0" x-cloak
                                class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                                <span x-text="$store.follow.total">{{ $following->total() }}</span>
                                following
                            </span>
                        </div>

                        {{-- Empty state (also appears after unfollowing the last shop) --}}
                        <div x-show="$store.follow.total === 0" x-cloak
                            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-light-gray px-6 py-14 text-center">
                            <svg class="h-12 w-12 text-charcoal/20" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <p class="mt-3 text-sm font-semibold text-charcoal">You are not following any shops yet</p>
                            <p class="mt-1 max-w-xs text-xs leading-relaxed text-charcoal/50">
                                Follow a seller from their shop page to keep them handy here.
                            </p>
                            <a href="{{ route('buyer.products.index') }}"
                                class="mt-5 rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-white transition hover:bg-sky-blue">
                                Browse Products
                            </a>
                        </div>

                        <div class="grid min-w-0 gap-3 sm:gap-4 md:grid-cols-2">
                            @foreach ($following as $shopSeller)
                                @php($shop = $shopSeller->sellerProfile)

                                <article x-show="$store.follow.has({{ $shopSeller->id }})" x-transition.opacity
                                    class="flex min-w-0 flex-col rounded-xl border border-light-gray bg-white p-4 transition hover:border-primary/30 hover:shadow-sm">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <a href="{{ route('buyer.shop.show', $shopSeller) }}" class="shrink-0">
                                            <x-avatar :user="$shopSeller" size="lg" />
                                        </a>

                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('buyer.shop.show', $shopSeller) }}"
                                                class="block truncate text-sm font-bold text-charcoal transition hover:text-primary">
                                                {{ $shop->shop_name }}
                                            </a>

                                            @if ($shop->location)
                                                <p class="mt-1 flex items-center gap-1 text-xs text-charcoal/50">
                                                    <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1116 0z" />
                                                        <circle cx="12" cy="10" r="2.5" stroke-width="2" />
                                                    </svg>
                                                    <span class="truncate">{{ $shop->location }}</span>
                                                </p>
                                            @endif

                                            <p class="mt-1 text-xs text-charcoal/50">
                                                {{ number_format($shopSeller->active_products_count) }}
                                                {{ \Illuminate\Support\Str::plural('product', $shopSeller->active_products_count) }}
                                                available
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-light-gray pt-3">
                                        <a href="{{ route('buyer.shop.show', $shopSeller) }}"
                                            class="inline-flex items-center gap-1 text-xs font-bold text-primary transition hover:text-sky-blue">
                                            Visit shop
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>

                                        <x-Buyer.follow-button :seller="$shopSeller"
                                            :followers-count="$shopSeller->followers_count" :is-following="true" />
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if ($following->hasPages())
                            <div x-show="$store.follow.total > 0"
                                class="pagination-frost mt-6 flex justify-center">
                                {{ $following->onEachSide(1)->links() }}
                            </div>
                        @endif
                    </section>
                </div>

            </div>
        </div>
    </main>

    <script id="addressData" type="application/json">@json($addressData)</script>

    <x-Buyer.seller-follow-data :ids="$followingIds" :counts="$followingCounts" :total="$following->total()" />

    <x-Buyer.message-widget />
    <x-footer />

</body>

</html>
