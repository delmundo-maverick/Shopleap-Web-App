<?php

namespace App\Services;

use App\Mail\PasswordResetOtp;
use App\Models\OtpVerification;
use App\Notifications\OtpNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class OtpService
{
    protected int $expiryMinutes = 10;

    protected int $maxAttempts = 5;

    public function generateAndSend(
        string $email,
        string $role,
        string $purpose = 'registration'
    ): void {
        $otp = (string) random_int(100000, 999999);

        $record = OtpVerification::updateOrCreate(
            [
                'email' => $email,
                'role' => $role,
                'purpose' => $purpose,
            ],
            [
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
                'expires_at' => Carbon::now()->addMinutes(
                    $this->expiryMinutes
                ),
                'verified_at' => null,
                'used_at' => null,
                'reset_token_hash' => null,
                'reset_expires_at' => null,
            ]
        );

        try {
            if ($purpose === 'password_reset') {
                Mail::to($email)->send(new PasswordResetOtp($otp, $role));
            } else {
                Notification::route('mail', $email)->notify(new OtpNotification($otp, $purpose, $role));
            }
        } catch (\Throwable $e) {
            report($e);

            // Don't leave an OTP the user never received.
            $record->delete();

            throw ValidationException::withMessages([
                'email' => 'We could not send the verification email. Please try again in a moment.',
            ]);
        }
    }

    /** @return array{0: bool, 1: string} */
    public function verify(string $email, string $role, string $otp, ?string $purpose = null): array
    {
        return DB::transaction(function () use ($email, $role, $otp, $purpose): array {
            $record = OtpVerification::where('email', $email)->where('role', $role)
                ->where('purpose', $purpose ?? ($role === 'courier_reset' ? 'reset' : 'registration'))
                ->lockForUpdate()->first();

            if (! $record || $record->used_at || $record->expires_at->isPast() || $record->attempts >= $this->maxAttempts) {
                return [false, 'Invalid or expired code. Please request a new code.'];
            }

            if (! Hash::check($otp, $record->otp_hash)) {
                $record->attempts++;
                if ($record->attempts >= $this->maxAttempts) {
                    $record->used_at = now();
                }
                $record->save();

                return [false, 'Invalid or expired code. Please request a new code.'];
            }

            $record->update(['verified_at' => now(), 'used_at' => now()]);

            return [true, 'OTP verified.'];
        });
    }

    /**
     * Delete the OTP after it has been used,
     * preventing it from being reused.
     */
    public function consume(string $email, string $role): void
    {
        OtpVerification::where('email', $email)
            ->where('role', $role)
            ->delete();
    }
}
