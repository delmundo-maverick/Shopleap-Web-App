<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BuyerProfile;
use App\Models\LogisticsProfile;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    protected array $validRoles = ['buyer', 'seller', 'logistics'];

    protected function checkRole(string $role): void
    {
        abort_unless(in_array($role, $this->validRoles), 404);
    }

    // ── LOGIN ──────────────────────────────────────────────

    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        if (is_null($user->email_verified_at)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Please verify your email before logging in.',
            ])->onlyInput('email');
        }

        $profile = match ($user->role) {
            'seller' => $user->sellerProfile,
            'buyer' => $user->buyerProfile,
            'logistics' => $user->logisticsProfile,
            default => null,
        };

        if ($profile && $profile->status !== 'approved') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $profile->status === 'rejected'
                ? 'Your registration was not approved. Please contact support for details.'
                : "Your registration is still pending administrator approval. You'll be notified by email once reviewed.";

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        if ($user->account_status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $user->account_status === 'suspended'
                ? 'Your account has been suspended. Please contact support.'
                : 'Your account has been deactivated.';

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        $request->session()->regenerate();

        return match ($user->role) {
            'super_admin' => redirect()->intended(route('admin.dashboard')),
            'seller' => redirect()->intended(route('seller.dashboard')),
            'buyer' => redirect()->intended(route('buyer.home')),
            'logistics' => redirect()->intended(route('logistics.dashboard')),
            default => redirect()->intended(route('home')),
        };
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // ── REGISTRATION STEP 2: OTP VERIFY ───────────────────────

    public function showOtpForm(string $role)
    {
        $this->checkRole($role);
        $pending = session('pending_registration');

        abort_if(!$pending || $pending['role'] !== $role, 403, 'Please start registration again.');

        return view('auth.verify-otp', ['role' => $role, 'email' => $pending['email']]);
    }

    public function verifyOtp(Request $request, string $role, OtpService $otpService)
    {
        $this->checkRole($role);
        $request->validate(['otp' => 'required|digits:6']);

        $pending = session('pending_registration');
        abort_if(!$pending || $pending['role'] !== $role, 403, 'Please start registration again.');

        [$ok, $message] = $otpService->verify($pending['email'], $role, $request->otp);

        if (!$ok) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['otp' => $message]);
        }

        DB::transaction(function () use ($pending, $role) {
            $user = User::create($pending['user'] + ['email_verified_at' => now()]);

            $profileData = $pending['profile'] + ['user_id' => $user->id];

            match ($role) {
                'buyer' => BuyerProfile::create($profileData),
                'seller' => SellerProfile::create($profileData),
                'logistics' => LogisticsProfile::create($profileData),
            };
        });

        session()->forget('pending_registration');

        if ($request->wantsJson()) {
            return response()->json(['message' => 'verified']);
        }

        return redirect()->route('login')->with(
            'status',
            "Email verified and registration submitted! Please wait for the administrator's approval — you'll be notified by email."
        );
    }

    public function resendOtp(Request $request, string $role, OtpService $otpService)
    {
        $this->checkRole($role);
        $pending = session('pending_registration');
        abort_if(!$pending || $pending['role'] !== $role, 403, 'Please start registration again.');

        $otpService->generateAndSend($pending['email'], $role);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'A new code has been sent.']);
        }

        return back()->with('status', 'A new code has been sent.');
    }
}
