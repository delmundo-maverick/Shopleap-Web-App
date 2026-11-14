<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BuyerProfile;
use App\Models\LogisticsProfile;
use App\Models\SellerProfile;
use App\Models\User;
use App\Rules\UniqueRoleEmail;
use App\Services\OtpService;
use App\Support\Portal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    protected array $validRoles = ['buyer', 'seller', 'logistics'];

    protected function checkRole(string $role): void
    {
        abort_unless(in_array($role, $this->validRoles), 404);
        abort_unless(($role === 'logistics') === Portal::isLogistics(), 404);
    }

    // â”€â”€ LOGIN â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function create(Request $request)
    {
        if (Portal::isLogistics($request)) {
            return view('logistics.login');
        }

        if ($request->query('continue') === 'landing') {
            $filters = $request->validate([
                'search' => ['nullable', 'string', 'max:100'],
                'category' => ['nullable', 'string', 'max:100'],
            ]);
            $request->session()->put('url.intended', route('home', $filters));
            $request->session()->flash('status', 'Please log in to continue');
        } elseif ($request->session()->has('url.intended')) {
            $request->session()->flash('status', 'Please log in to continue');
        }

        return view('auth.login');
    }

    public function store(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            throw ValidationException::withMessages(['email' => 'Please log out before signing in to another account.']);
        }

        $roles = Portal::loginRoles($request);
        if ($request->has('role')) {
            $data = $request->validate(['role' => ['required', Rule::in($roles)]]);
            $pending = $request->session()->pull('login_choice');
            $user = null;
            if (is_array($pending) && $pending['expires_at'] >= now()->timestamp && $pending['host'] === $request->getHost()) {
                $candidate = $pending['accounts'][$data['role']] ?? null;
                if ($candidate) {
                    $user = User::whereKey($candidate['id'])->where('role', $data['role'])->first();
                    if ($user && ! hash_equals($candidate['password_hash'], $user->password)) {
                        $user = null;
                    }
                }
            }
            if (! $user) {
                throw ValidationException::withMessages(['email' => 'Please sign in again.']);
            }
            $remember = $pending['remember'];
        } else {
            $request->session()->forget('login_choice');
            $credentials = $request->validate([
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string'],
            ]);
            $matches = User::withEmail($credentials['email'])->whereIn('role', $roles)->get()
                ->filter(fn (User $account): bool => Hash::check($credentials['password'], $account->password));

            if ($matches->isEmpty()) {
                return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
            }
            $remember = $request->boolean('remember');
            if ($matches->count() > 1) {
                $request->session()->regenerate();
                $request->session()->put('login_choice', [
                    'accounts' => $matches->mapWithKeys(fn (User $account): array => [
                        $account->role => ['id' => $account->id, 'password_hash' => $account->password],
                    ])->all(),
                    'remember' => $remember,
                    'host' => $request->getHost(),
                    'expires_at' => now()->addMinutes(5)->timestamp,
                ]);

                return view('auth.choose-role', ['roles' => $matches->pluck('role')->all()]);
            }
            $user = $matches->first();
            Auth::getProvider()->rehashPasswordIfRequired($user, $credentials);
        }

        Auth::login($user, $remember);

        if (is_null($user->email_verified_at)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Please verify your email before logging in.',
            ])->onlyInput('email');
        }

        // Couriers must sign in through the Shopleap Rider app.
        if ($user->role === 'courier') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Riders sign in through the Shopleap Rider app.',
            ])->onlyInput('email');
        }

        $profile = match ($user->role) {
            'seller' => $user->sellerProfile,
            'buyer' => $user->buyerProfile,
            'logistics' => $user->logisticsProfile,
            default => null,
        };

        if ($profile && $profile->status !== 'approved' && $user->role !== 'logistics') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $profile->status === 'rejected'
                ? 'Your registration was not approved. Please contact support for details.'
                : "Your registration is still pending administrator approval. You'll be notified by email once reviewed.";

            return back()->withErrors([
                'email' => $message,
            ])->onlyInput('email');
        }

        if ($user->account_status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $user->account_status === 'suspended'
                ? 'Your account has been suspended. Please contact support.'
                : 'Your account has been deactivated.';

            return back()->withErrors([
                'email' => $message,
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($user->role === 'logistics') {
            $request->session()->forget('url.intended');

            return redirect()->route($profile?->status === 'approved' ? 'logistics.dashboard' : 'logistics.application');
        }

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

    // â”€â”€ REGISTRATION STEP 2: OTP VERIFY â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function showOtpForm(string $role)
    {
        $this->checkRole($role);

        $pending = session('pending_registration');

        abort_if(
            ! $pending || $pending['role'] !== $role,
            403,
            'Please start registration again.'
        );

        return view($role === 'logistics' ? 'logistics.verify-otp' : 'auth.verify-otp', [
            'role' => $role,
            'email' => $pending['email'],
        ]);
    }

    public function verifyOtp(
        Request $request,
        string $role,
        OtpService $otpService
    ) {
        $this->checkRole($role);

        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $pending = session('pending_registration');

        abort_if(
            ! $pending || $pending['role'] !== $role,
            403,
            'Please start registration again.'
        );

        validator(['email' => $pending['email']], ['email' => [new UniqueRoleEmail($role)]])->validate();

        [$ok, $message] = $otpService->verify(
            $pending['email'],
            $role,
            $request->otp
        );

        if (! $ok) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return back()->withErrors([
                'otp' => $message,
            ]);
        }

        try {
            DB::transaction(function () use ($pending, $role) {
                $user = User::create(
                    $pending['user'] + ['email_verified_at' => now()]
                );

                $profileData = $pending['profile'] + [
                    'user_id' => $user->id,
                ];

                match ($role) {
                    'buyer' => BuyerProfile::create($profileData),
                    'seller' => SellerProfile::create($profileData),
                    'logistics' => LogisticsProfile::create($profileData),
                };
            });
        } catch (UniqueConstraintViolationException $exception) {
            validator(['email' => $pending['user']['email']], ['email' => [new UniqueRoleEmail($role)]])->validate();

            throw $exception;
        }

        session()->forget('pending_registration');

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'verified',
            ]);
        }

        return redirect()->route('login')->with(
            'status',
            "Email verified and registration submitted! Please wait for the administrator's approval â€” you'll be notified by email."
        );
    }

    public function resendOtp(
        Request $request,
        string $role,
        OtpService $otpService
    ) {
        $this->checkRole($role);

        $pending = session('pending_registration');

        abort_if(
            ! $pending || $pending['role'] !== $role,
            403,
            'Please start registration again.'
        );

        $otpService->generateAndSend($pending['email'], $role);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'A new code has been sent.',
            ]);
        }

        return back()->with('status', 'A new code has been sent.');
    }
}
