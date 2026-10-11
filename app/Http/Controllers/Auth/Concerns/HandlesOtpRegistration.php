<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Services\OtpService;
use Illuminate\Http\Request;

trait HandlesOtpRegistration
{
    protected function stashForOtpVerification(
        Request $request,
        string $role,
        array $userAttributes,
        array $profileAttributes,
        OtpService $otpService
    ) {
        // Send first. If this fails, OtpService throws a ValidationException
        // and nothing gets stored in the session.
        $otpService->generateAndSend($userAttributes['email'], $role);

        $request->session()->put('pending_registration', [
            'role' => $role,
            'email' => $userAttributes['email'],
            'user' => $userAttributes,
            'profile' => $profileAttributes,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['email' => $userAttributes['email']]);
        }

        return redirect()->route('register.otp.form', $role)
            ->with('status', 'We sent a verification code to your email.');
    }
}
