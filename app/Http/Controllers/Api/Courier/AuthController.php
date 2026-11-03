<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\HubServiceArea;
use App\Models\HubServiceAreaBarangay;
use App\Models\LogisticsProfile;
use App\Models\User;
use App\Services\HubBarangayCatalog;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const PENDING_MINUTES = 30;

    /** Step 1: validate, upload documents, keep the data in the cache and email the OTP. */
    public function register(Request $request, OtpService $otp): JsonResponse
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $v = $request->validate([
            'hub_id' => ['required', 'integer'],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'sex' => ['required', 'in:male,female'],
            'email' => ['required', 'email', 'unique:users,email'],
            'contact_no' => ['required', 'string', 'max:20'],
            'birthday' => ['required', 'date', 'before:today'],
            'province' => ['required', 'string', 'not_regex:/^\d+$/'],
            'municipality' => ['required', 'string', 'not_regex:/^\d+$/'],
            'barangay' => ['required', 'string', 'not_regex:/^\d+$/'],
            'street_address' => ['required', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['required', 'string', 'max:50'],
            'plate_number' => ['required', 'string', 'max:20'],
            'license_upload' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'or_cr_upload' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $hub = LogisticsProfile::available()->find($v['hub_id']);

        if (! $hub) {
            throw ValidationException::withMessages(['hub_id' => 'Please choose an available sorting center.']);
        }

        $place = HubBarangayCatalog::findForHub(
            $hub,
            $v['province'],
            $v['municipality'],
            $v['barangay']
        );

        if (! $place) {
            throw ValidationException::withMessages([
                'barangay' => "Choose a barangay inside {$hub->business_name}'s service areas.",
            ]);
        }

        $v['province'] = $place['province'];
        $v['municipality'] = $place['municipality'];
        $v['barangay'] = $place['barangay'];

        $email = strtolower($v['email']);

        Cache::put($this->cacheKey($email), [
            'user' => [
                'name' => trim("{$v['first_name']} {$v['last_name']}"),
                'email' => $email,
                'password' => Hash::make($v['password']),
                'role' => 'courier',
                'account_status' => 'active',
            ],
            'profile' => [
                'logistics_profile_id' => $hub->id,
                'last_name' => $v['last_name'],
                'first_name' => $v['first_name'],
                'middle_initial' => $v['middle_initial'] ?? null,
                'sex' => $v['sex'],
                'contact_no' => $v['contact_no'],
                'birthday' => $v['birthday'],
                'age' => Carbon::parse($v['birthday'])->age,
                'province' => $v['province'],
                'municipality' => $v['municipality'],
                'barangay' => $v['barangay'],
                'street_address' => $v['street_address'],
                'house_number' => $v['house_number'] ?? null,
                'vehicle_type' => $v['vehicle_type'],
                'plate_number' => $v['plate_number'],
                'license_upload_path' => $request->file('license_upload')->store('courier_uploads/licenses', 'r2'),
                'or_cr_upload_path' => $request->file('or_cr_upload')->store('courier_uploads/or_cr', 'r2'),
                'status' => 'pending',
                'is_active' => false,
            ],
        ], now()->addMinutes(self::PENDING_MINUTES));

        $otp->generateAndSend($email, 'courier');

        return response()->json(['message' => 'A verification code was sent to your email.', 'email' => $email]);
    }

    /** Step 2: confirm the OTP, then create the account (status pending, waiting for the hub). */
    public function verifyOtp(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $email = strtolower($data['email']);
        $pending = Cache::get($this->cacheKey($email));

        if (! $pending) {
            return response()->json(['message' => 'Your registration expired. Please start again.'], 422);
        }

        [$ok, $message] = $otp->verify($email, 'courier', $data['otp']);

        if (! $ok) {
            return response()->json(['message' => $message], 422);
        }

        DB::transaction(function () use ($pending) {
            $user = User::create($pending['user'] + ['email_verified_at' => now()]);
            CourierProfile::create($pending['profile'] + ['user_id' => $user->id]);
        });

        Cache::forget($this->cacheKey($email));
        $otp->consume($email, 'courier');

        return response()->json(
            ['message' => 'Email verified. Your application was sent to the sorting center for approval.'],
            201
        );
    }

    public function resendOtp(Request $request, OtpService $otp): JsonResponse
    {
        $email = strtolower($request->validate(['email' => ['required', 'email']])['email']);

        if (! Cache::has($this->cacheKey($email))) {
            return response()->json(['message' => 'Your registration expired. Please start again.'], 422);
        }

        $otp->generateAndSend($email, 'courier');

        return response()->json(['message' => 'A new code has been sent.']);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($data['email'])])->first();

        if (! $user || $user->role !== 'courier' || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json(['message' => 'Please verify your email first.'], 403);
        }

        if ($user->account_status !== 'active') {
            return response()->json(['message' => 'Your account is not active. Please contact your sorting center.'], 403);
        }

        $profile = CourierProfile::with('logisticsProfile')->where('user_id', $user->id)->first();

        if (! $profile) {
            return response()->json(['message' => 'No rider profile found for this account.'], 403);
        }

        $token = $user->createToken($data['device_name'] ?? 'rider-app', ['courier'])->plainTextToken;

        return response()->json(['token' => $token, 'courier' => $this->payload($profile)]);
    }

    /** The app polls this to learn whether the hub approved or rejected the application. */
    public function me(Request $request): JsonResponse
    {
        $profile = $request->attributes->get('courier')->load('logisticsProfile');

        return response()->json(['courier' => $this->payload($profile)]);
    }

    /** After a rejection: resubmit to the same hub or a different one. */
    public function reapply(Request $request): JsonResponse
    {
        $courier = $request->attributes->get('courier');

        if ($courier->status !== 'rejected') {
            return response()->json(['message' => 'Only a rejected application can be resubmitted.'], 422);
        }

        $data = $request->validate([
            'hub_id' => ['nullable', 'integer'],
            'province' => ['nullable', 'string', 'max:100', 'not_regex:/^\d+$/'],
            'municipality' => ['nullable', 'string', 'max:100', 'not_regex:/^\d+$/'],
            'barangay' => ['nullable', 'string', 'max:150', 'not_regex:/^\d+$/'],
            'license_upload' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'or_cr_upload' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $hub = LogisticsProfile::available()->find($data['hub_id'] ?? $courier->logistics_profile_id);

        if (! $hub) {
            throw ValidationException::withMessages(['hub_id' => 'Please choose an available sorting center.']);
        }

        $place = HubBarangayCatalog::findForHub(
            $hub,
            $data['province'] ?? $courier->province,
            $data['municipality'] ?? $courier->municipality,
            $data['barangay'] ?? $courier->barangay
        );

        if (! $place) {
            throw ValidationException::withMessages([
                'barangay' => "Choose a barangay inside {$hub->business_name}'s service areas.",
            ]);
        }

        $update = [
            'logistics_profile_id' => $hub->id,
            'province' => $place['province'],
            'municipality' => $place['municipality'],
            'barangay' => $place['barangay'],
            'status' => 'pending',
            'is_active' => false,
            'rejection_reason' => null,
            'reviewed_at' => null,
        ];

        if ($request->hasFile('license_upload')) {
            $update['license_upload_path'] = $request->file('license_upload')->store('courier_uploads/licenses', 'r2');
        }

        if ($request->hasFile('or_cr_upload')) {
            $update['or_cr_upload_path'] = $request->file('or_cr_upload')->store('courier_uploads/or_cr', 'r2');
        }

        DB::transaction(function () use ($courier, $hub, $update) {
            // Moving to another hub: the old hub keeps the barangays, but this rider's coverage is cleared.
            if ($courier->logistics_profile_id !== $hub->id) {
                HubServiceAreaBarangay::where('rider_id', $courier->id)->update(['rider_id' => null]);
                HubServiceArea::where('rider_id', $courier->id)->update(['rider_id' => null]);
            }

            $courier->update($update);
        });

        return response()->json(['message' => 'Application resubmitted.', 'courier' => $this->payload($courier->fresh('logisticsProfile'))]);
    }

    /**
     * Always return the same response to avoid revealing whether a rider
     * account exists for the supplied email address.
     */
    public function forgotPassword(Request $request, OtpService $otp): JsonResponse
    {
        $email = strtolower(trim(
            $request->validate(['email' => ['required', 'email']])['email']
        ));

        $isRider = User::whereRaw('LOWER(email) = ?', [$email])
            ->where('role', 'courier')
            ->whereNotNull('email_verified_at')
            ->exists();

        if ($isRider) {
            try {
                $otp->generateAndSend($email, 'courier_reset', 'reset');
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'message' => 'If that email belongs to a rider account, a code was sent.',
        ]);
    }

    public function resetPassword(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower(trim($data['email']));

        $user = User::whereRaw('LOWER(email) = ?', [$email])
            ->where('role', 'courier')
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Invalid or expired code.',
            ], 422);
        }

        [$ok, $message] = $otp->verify(
            $email,
            'courier_reset',
            $data['otp']
        );

        if (! $ok) {
            return response()->json(['message' => $message], 422);
        }

        // The User model's hashed cast should hash this password.
        $user->update(['password' => $data['password']]);

        // Revoke all Sanctum tokens so every device must sign in again.
        $user->tokens()->delete();

        $otp->consume($email, 'courier_reset');

        return response()->json([
            'message' => 'Password changed. Please sign in.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    private function cacheKey(string $email): string
    {
        return 'courier_registration:'.$email;
    }

    private function payload(CourierProfile $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->full_name,
            'status' => $p->status,                 // pending | approved | rejected
            'is_active' => (bool) $p->is_active,
            'rejection_reason' => $p->rejection_reason,
            'vehicle_type' => $p->vehicle_type,
            'plate_number' => $p->plate_number,
            'contact_no' => $p->contact_no,
            'house_number' => $p->house_number,
            'street_address' => $p->street_address,
            'province' => $p->province,
            'municipality' => $p->municipality,
            'barangay' => $p->barangay,
            'avatar_url' => $p->user?->avatar_url,
            'hub' => $p->logisticsProfile ? [
                'id' => $p->logisticsProfile->id,
                'name' => $p->logisticsProfile->business_name,
                'company' => $p->logisticsProfile->company_name,
                'municipality' => $p->logisticsProfile->municipality,
                'province' => $p->logisticsProfile->province,
            ] : null,
        ];
    }
}
