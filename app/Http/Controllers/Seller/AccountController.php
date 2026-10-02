<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->sellerProfile;

        return view('seller.account.index', compact('user', 'profile'));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validateWithBag('profile', [
            'contact_no' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'shop_description' => ['nullable', 'string', 'max:500'],
        ]);

        $request->user()->sellerProfile->update([
            'contact_no' => $validated['contact_no'],
            'shop_description' => $validated['shop_description'] ?? null,
        ]);

        return back()->with('status', 'Shop profile updated.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validateWithBag('avatar', [
            'avatar' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:min_width=100,min_height=100',
            ],
        ]);

        $user = $request->user();
        $newPath = $request->file('avatar')->store('avatars', 'r2_public');

        if (! $newPath) {
            return back()->withErrors(['avatar' => 'Upload failed. Please try again.'], 'avatar');
        }

        $oldPath = $user->avatar_path;
        $user->update(['avatar_path' => $newPath]);

        $this->deleteQuietly($oldPath);

        return back()->with('status', 'Profile photo updated.');
    }

    public function destroyAvatar(Request $request)
    {
        $user = $request->user();
        $oldPath = $user->avatar_path;

        $user->update(['avatar_path' => null]);
        $this->deleteQuietly($oldPath);

        return back()->with('status', 'Profile photo removed.');
    }

    public function updateAddress(Request $request)
    {
        $validated = $request->validateWithBag('address', [
            'province' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:100'],
            'barangay' => ['required', 'string', 'max:100'],
            'street_address' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->sellerProfile->update($validated);

        return back()->with('status', 'Pickup address updated.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('status', 'Password changed successfully.');
    }

    /** A leftover file in storage must never fail the request the seller actually made. */
    private function deleteQuietly(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk('r2_public')->delete($path);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
