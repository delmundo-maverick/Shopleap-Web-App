<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Notifications\OtpNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class OtpService
{
    protected int $expiryMinutes = 10;
    protected int $maxAttempts = 5;

    public function generateAndSend(string $email, string $role): void
    {
        $otp = (string) random_int(100000, 999999);

        OtpVerification::updateOrCreate(
            ['email' => $email, 'role' => $role],
            [
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
                'expires_at' => Carbon::now()->addMinutes($this->expiryMinutes),
                'verified_at' => null,
            ]
        );

        Notification::route('mail', $email)->notify(new OtpNotification($otp));
    }

    public function verify(string $email, string $role, string $otp): array
    {
        $record = OtpVerification::where('email', $email)
            ->where('role', $role)
            ->latest()
            ->first();

        if (!$record) {
            return [false, 'No OTP request found. Please request a new code.'];
        }

        if ($record->expires_at->isPast()) {
            return [false, 'OTP expired. Please request a new code.'];
        }

        if ($record->attempts >= $this->maxAttempts) {
            return [false, 'Too many attempts. Please request a new code.'];
        }

        if (!Hash::check($otp, $record->otp_hash)) {
            $record->increment('attempts');
            return [false, 'Invalid OTP.'];
        }

        $record->update(['verified_at' => now()]);

        return [true, 'OTP verified.'];
    }
}
