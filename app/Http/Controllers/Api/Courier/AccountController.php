<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    /** Contact number and address only. Vehicle, plate and documents change through the hub. */
    public function updateProfile(Request $request): JsonResponse
    {
        $courier = $request->attributes->get('courier');

        $data = $request->validate([
            'contact_no' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'province' => ['required', 'string', 'max:100', 'not_regex:/^\d+$/'],
            'municipality' => ['required', 'string', 'max:100', 'not_regex:/^\d+$/'],
            'barangay' => ['required', 'string', 'max:100', 'not_regex:/^\d+$/'],
            'street_address' => ['required', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:50'],
        ]);

        $courier->update($data);

        return response()->json(['message' => 'Profile updated.', 'courier' => $this->summary($request, $courier->fresh())]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);

        $user = $request->user();

        // Checked by hand: the `current_password` rule looks at the web guard, not the API token.
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }

        $user->update(['password' => $data['password']]);

        // Keep this device signed in, sign out every other one.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password changed.']);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=100,min_height=100'],
        ]);

        $user = $request->user();
        $newPath = $request->file('avatar')->store('avatars', 'r2_public');

        if (! $newPath) {
            return response()->json(['message' => 'Upload failed. Please try again.'], 500);
        }

        $old = $user->avatar_path;
        $user->update(['avatar_path' => $newPath]);
        $this->deleteQuietly($old);

        return response()->json(['message' => 'Profile photo updated.', 'avatar_url' => $user->fresh()->avatar_url]);
    }

    public function destroyAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        $old = $user->avatar_path;

        $user->update(['avatar_path' => null]);
        $this->deleteQuietly($old);

        return response()->json(['message' => 'Profile photo removed.']);
    }

    private function summary(Request $request, $courier): array
    {
        return [
            'id' => $courier->id,
            'name' => $courier->full_name,
            'contact_no' => $courier->contact_no,
            'province' => $courier->province,
            'municipality' => $courier->municipality,
            'barangay' => $courier->barangay,
            'street_address' => $courier->street_address,
            'house_number' => $courier->house_number,
            'avatar_url' => $request->user()->avatar_url,
        ];
    }

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
