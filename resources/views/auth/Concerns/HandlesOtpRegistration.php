<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Services\OtpService;
use Illuminate\Http\Request;

trait HandlesOtpRegistration
{
    /**
     * Stash the fully-validated user + profile data in session, send the
     * OTP, and respond appropriately for either the AJAX (Alpine) flow or
     * a plain form submission. Shared by Buyer/Seller/Logistics
     * registration controllers — only their validation rules and file
     * uploads differ; this tail is identical for all three.
     */
    protected function stashForOtpVerification(
        Request $request,
        string $role,
        array $userAttributes,
        array $profileAttributes,
        OtpService $otpService
    ) {
        $request->session()->put('pending_registration', [
            'role' => $role,
            'email' => $userAttributes['email'],
            'user' => $userAttributes,
            'profile' => $profileAttributes,
        ]);

        $otpService->generateAndSend($userAttributes['email'], $role);

        if ($request->wantsJson()) {
            return response()->json(['email' => $userAttributes['email']]);
        }

        return redirect()->route('register.otp.form', $role)
            ->with('status', 'We sent a verification code to your email.');
    }
}
