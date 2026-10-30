<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->buyerProfile;

        $following = $user->followedSellers()
            ->eligibleSellers()
            ->select('users.*')
            ->with('sellerProfile')
            ->withCount([
                'followers',
                'products as active_products_count' => fn ($query) => $query->active(),
            ])
            ->orderByDesc('seller_followers.created_at')
            ->paginate(8)
            ->withQueryString()
            ->fragment('following');

        $followingIds = $following->getCollection()->pluck('id')->all();
        $followingCounts = $following->getCollection()
            ->mapWithKeys(fn ($seller) => [$seller->id => $seller->followers_count])
            ->all();

        return view('Buyer.account.index', compact(
            'user',
            'profile',
            'following',
            'followingIds',
            'followingCounts',
        ));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validateWithBag('profile', [
            'contact_no' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
        ]);

        $request->user()->buyerProfile->update([
            'contact_no' => $validated['contact_no'],
        ]);

        return back()->with('status', 'Contact number updated.');
    }

    public function updateAddress(Request $request)
    {
        $validated = $request->validateWithBag('address', [
            'province' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:100'],
            'barangay' => ['required', 'string', 'max:100'],
            'house_number' => ['required', 'string', 'max:100'],
            'street' => ['required', 'string', 'max:255'],
            'additional_address' => ['nullable', 'string', 'max:500'],
        ]);

        $request->user()->buyerProfile->update($validated);

        return back()->with('status', 'Delivery address updated. Shipping fees in your cart may have changed.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validateWithBag('avatar', [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=100,min_height=100'],
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

    public function updatePassword(Request $request)
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('status', 'Password changed successfully.');
    }

    /** A leftover file in storage must never fail the request the buyer actually made. */
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
