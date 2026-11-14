<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChanged;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    private const MESSAGE = 'If an account exists, an OTP has been sent';

    public function requestForm(): View
    {
        return view('auth.password-recovery', ['step' => 1]);
    }

    public function send(Request $request, OtpService $otp): View|RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['nullable', Rule::in($this->allowedRoles())],
        ]);
        $email = Str::lower(trim($data['email']));
        $roles = User::withEmail($email)->whereIn('role', $this->allowedRoles())->pluck('role')->all();
        $role = $data['role'] ?? null;
        $request->session()->forget(['password_reset', 'password_recovery_email', 'password_recovery_role']);
        if (count($roles) > 1 && ! $role) {
            return view('auth.password-recovery', ['step' => 1, 'roles' => $roles, 'email' => $email]);
        }
        $role ??= $roles[0] ?? $this->allowedRoles()[0];
        $this->issue($email, $role, $otp);
        $request->session()->put(['password_recovery_email' => $email, 'password_recovery_role' => $role]);

        return redirect()->route('password.verify')->with('status', self::MESSAGE);
    }

    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        $email = $request->session()->get('password_recovery_email');
        $role = $request->session()->get('password_recovery_role');
        if (! is_string($email) || ! in_array($role, $this->allowedRoles(), true)) {
            return redirect()->route('password.request');
        }
        $this->issue($email, $role, $otp);
        $request->session()->forget('password_reset');

        return redirect()->route('password.verify')->with('status', self::MESSAGE);
    }

    private function issue(string $email, string $role, OtpService $otp): void
    {
        $key = 'password-reset:'.hash('sha256', $email.'|'.$role);
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($key.':cooldown', 1)) {
            throw ValidationException::withMessages(['email' => 'Please wait before requesting another code.']);
        }
        RateLimiter::hit($key, 3600);
        RateLimiter::hit($key.':cooldown', 60);
        try {
            DB::transaction(function () use ($email, $role, $otp): void {
                $user = User::withEmail($email)->where('role', $role)->lockForUpdate()->first();
                if ($user) {
                    OtpVerification::where('email', $email)->where('role', $role)->where('purpose', 'password_reset')->whereNull('used_at')->update(['used_at' => now(), 'reset_token_hash' => null]);
                    $otp->generateAndSend($email, $user->role, 'password_reset');
                }
            });
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_recovery_email');
        $role = $request->session()->get('password_recovery_role');
        if (! is_string($email) || ! in_array($role, $this->allowedRoles(), true)) {
            return redirect()->route('password.request');
        }
        [$local, $domain] = explode('@', $email, 2);

        return view('auth.password-recovery', [
            'step' => 2,
            'role' => $role,
            'maskedEmail' => mb_substr($local, 0, 1).'***@'.$domain,
            'cooldown' => RateLimiter::availableIn('password-reset:'.hash('sha256', $email.'|'.$role).':cooldown'),
        ]);
    }

    public function verify(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate(['otp' => ['required', 'digits:6']]);
        $email = $request->session()->get('password_recovery_email', '');
        $role = $request->session()->get('password_recovery_role');
        if (! in_array($role, $this->allowedRoles(), true)) {
            throw ValidationException::withMessages(['otp' => 'Invalid or expired code. Please request a new code.']);
        }
        $token = Str::random(64);
        $recordId = DB::transaction(function () use ($email, $role, $data, $otp, $token): ?int {
            $user = User::withEmail($email)->where('role', $role)->lockForUpdate()->first();
            if (! $user) {
                return null;
            }
            [$valid] = $otp->verify($email, $user->role, $data['otp'], 'password_reset');
            if (! $valid) {
                return null;
            }
            $record = OtpVerification::where('email', $email)->where('role', $user->role)->where('purpose', 'password_reset')->firstOrFail();
            $record->update(['reset_token_hash' => Hash::make($token), 'reset_expires_at' => now()->addMinutes(15)]);

            return $record->id;
        });
        if (! $recordId) {
            throw ValidationException::withMessages(['otp' => 'Invalid or expired code. Please request a new code.']);
        }
        $request->session()->regenerate();
        $request->session()->put('password_reset', ['record_id' => $recordId, 'token' => $token]);

        return redirect()->route('password.reset');
    }

    public function resetForm(Request $request): View|RedirectResponse
    {
        if (! $this->grant($request)) {
            return redirect()->route('password.request');
        }

        return view('auth.password-recovery', ['step' => 3]);
    }

    private function grant(Request $request, bool $lock = false): ?OtpVerification
    {
        $grant = $request->session()->get('password_reset');
        if (! is_array($grant)) {
            return null;
        }
        $query = OtpVerification::whereKey($grant['record_id'])->where('purpose', 'password_reset')->whereIn('role', $this->allowedRoles());
        if ($lock) {
            $query->lockForUpdate();
        }
        $record = $query->first();

        return $record && $record->used_at && $record->verified_at && $record->reset_token_hash
            && $record->reset_expires_at?->isFuture() && Hash::check($grant['token'], $record->reset_token_hash)
            ? $record : null;
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        [$email, $role] = DB::transaction(function () use ($request, $data): array {
            $record = $this->grant($request, true);
            if (! $record) {
                throw ValidationException::withMessages(['password' => 'Your reset authorization expired. Please request a new code.']);
            }
            $user = User::withEmail($record->email)->where('role', $record->role)->lockForUpdate()->firstOrFail();
            $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
            OtpVerification::where('email', $record->email)->where('role', $record->role)->where('purpose', 'password_reset')->update(['used_at' => now(), 'reset_token_hash' => null, 'reset_expires_at' => null]);

            return [$user->email, $user->role];
        }, 3);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        try {
            Mail::to($email)->send(new PasswordChanged($role));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('login')->with('status', 'Your password has been changed. Please log in.');
    }

    /** @return list<string> */
    private function allowedRoles(): array
    {
        return Portal::loginRoles();
    }
}
