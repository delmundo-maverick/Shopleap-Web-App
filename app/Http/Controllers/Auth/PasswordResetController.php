<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChanged;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    private const MESSAGE = 'If an account exists, an OTP has been sent';

    public function requestForm(): View
    {
        return view('auth.password-recovery', ['step' => 1]);
    }

    public function send(Request $request, OtpService $otp): RedirectResponse
    {
        $email = Str::lower(trim($request->validate(['email' => ['required', 'email', 'max:255']])['email']));
        $this->issue($email, $otp);
        $request->session()->forget('password_reset');
        $request->session()->put('password_recovery_email', $email);

        return redirect()->route('password.verify')->with('status', self::MESSAGE);
    }

    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        $email = $request->session()->get('password_recovery_email');
        if (! is_string($email)) {
            return redirect()->route('password.request');
        }
        $this->issue($email, $otp);
        $request->session()->forget('password_reset');

        return redirect()->route('password.verify')->with('status', self::MESSAGE);
    }

    private function issue(string $email, OtpService $otp): void
    {
        $key = 'password-reset:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($key.':cooldown', 1)) {
            throw ValidationException::withMessages(['email' => 'Please wait before requesting another code.']);
        }
        RateLimiter::hit($key, 3600);
        RateLimiter::hit($key.':cooldown', 60);
        try {
            DB::transaction(function () use ($email, $otp): void {
                $user = User::whereRaw('LOWER(email) = ?', [$email])->whereIn('role', ['buyer', 'seller', 'logistics', 'super_admin'])->lockForUpdate()->first();
                if ($user) {
                    OtpVerification::where('email', $email)->whereNull('used_at')->update(['used_at' => now(), 'reset_token_hash' => null]);
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
        if (! is_string($email)) {
            return redirect()->route('password.request');
        }
        [$local, $domain] = explode('@', $email, 2);

        return view('auth.password-recovery', [
            'step' => 2,
            'maskedEmail' => mb_substr($local, 0, 1).'***@'.$domain,
            'cooldown' => RateLimiter::availableIn('password-reset:'.hash('sha256', $email).':cooldown'),
        ]);
    }

    public function verify(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate(['otp' => ['required', 'digits:6']]);
        $email = $request->session()->get('password_recovery_email', '');
        $token = Str::random(64);
        $recordId = DB::transaction(function () use ($email, $data, $otp, $token): ?int {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->whereIn('role', ['buyer', 'seller', 'logistics', 'super_admin'])->lockForUpdate()->first();
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
        $query = OtpVerification::whereKey($grant['record_id'])->where('purpose', 'password_reset');
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
        $email = DB::transaction(function () use ($request, $data): string {
            $record = $this->grant($request, true);
            if (! $record) {
                throw ValidationException::withMessages(['password' => 'Your reset authorization expired. Please request a new code.']);
            }
            $user = User::whereRaw('LOWER(email) = ?', [$record->email])->where('role', $record->role)->lockForUpdate()->firstOrFail();
            $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
            OtpVerification::where('email', $record->email)->update(['used_at' => now(), 'reset_token_hash' => null, 'reset_expires_at' => null]);

            return $user->email;
        }, 3);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        try {
            Mail::to($email)->send(new PasswordChanged);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('login')->with('status', 'Your password has been changed. Please log in.');
    }
}
