<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Notifications\OtpNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
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
            ],
            [
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
                'expires_at' => Carbon::now()->addMinutes(
                    $this->expiryMinutes
                ),
                'verified_at' => null,
            ]
        );

        try {
            Notification::route('mail', $email)
                ->notify(new OtpNotification($otp, $purpose));
        } catch (\Throwable $e) {
            report($e);

            // Don't leave an OTP the user never received.
            $record->delete();

            throw ValidationException::withMessages([
                'email' => 'We could not send the verification email. Please try again in a moment.',
            ]);
        }
    }

    public function verify(string $email, string $role, string $otp): array
    {
        $record = OtpVerification::where('email', $email)
            ->where('role', $role)
            ->first();

        if (!$record) {
            return [
                false,
                'No OTP request found. Please request a new code.',
            ];
        }

        if ($record->expires_at->isPast()) {
            return [
                false,
                'OTP expired. Please request a new code.',
            ];
        }

        if ($record->attempts >= $this->maxAttempts) {
            return [
                false,
                'Too many attempts. Please request a new code.',
            ];
        }

        if (!Hash::check($otp, $record->otp_hash)) {
            $record->increment('attempts');

            return [false, 'Invalid OTP.'];
        }

        $record->update([
            'verified_at' => now(),
        ]);

        return [true, 'OTP verified.'];
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
